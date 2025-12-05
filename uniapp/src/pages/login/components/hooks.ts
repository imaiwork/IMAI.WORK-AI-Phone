import { useAppStore } from "@/stores/app";
import { computed, ref, watch } from "vue";
import { useLockFn } from "@/hooks/useLockFn";
import {
    OALogin,
    login,
    mnpLogin as mnpLoginApi,
    pcLogin as pcLoginApi,
    updateUser,
    uniAppLogin,
    mnpAuthBind,
} from "@/api/account";
import { useUserStore } from "@/stores/user";
import { useRouter, useRoute } from "uniapp-router-next";
import { onLoad } from "@dcloudio/uni-app";
import cache from "@/utils/cache";
import { BACK_URL } from "@/enums/constantEnums";
import { series } from "@/utils/util";
import { getClient } from "@/utils/client";
// #ifdef H5
import wechatOa, { UrlScene } from "@/utils/wechat";
// #endif

export enum LoginWayEnum {
    WEIXIN = "1",
    MOBILE = "2",
    PC = "4",
}

export function useLoginWay() {
    const appStore = useAppStore();
    const userStore = useUserStore();
    const router = useRouter();
    const route = useRoute();

    const showLoginPopup = ref(false);
    const showBindMobilePopup = ref(false);
    const loginWay = ref<string | number>(LoginWayEnum.WEIXIN);
    const isWeixinLogin = computed(() => loginWay.value == LoginWayEnum.WEIXIN);
    const isMobileLogin = computed(() => loginWay.value == LoginWayEnum.MOBILE);
    const hasWeixinLogin = computed(() => appStore.getLoginConfig.login_way.includes(LoginWayEnum.WEIXIN));
    const hasMobileLogin = computed(() => appStore.getLoginConfig.login_way.includes(LoginWayEnum.MOBILE));
    const showOtherWayBtn = computed(() => appStore.getLoginConfig.login_way?.length > 1);
    const isLoginAfter = ref(true);
    const websiteConfig = computed(() => appStore.getWebsiteConfig);
    const loginConfig = computed(() => appStore.getLoginConfig);
    const changeLoginWay = (way: LoginWayEnum) => {
        loginWay.value = way;
    };
    const loginData = ref<any>({});

    const oaLogin = async (options: any = { getUrl: true }) => {
        const { code, getUrl } = options;
        if (getUrl) {
            await wechatOa.getUrl(UrlScene.LOGIN);
        } else {
            const data = await OALogin({
                code,
            });
            return data;
        }
        return Promise.reject();
    };

    const mnpLogin = async (params?: any) => {
        try {
            const { code }: any = await uni.login({
                provider: "weixin",
            });
            const data = await mnpLoginApi({
                code,
                ...params,
            });
            if (data.is_new_user) {
                //是新用户
                showLoginPopup.value = true;
            }
            return data;
        } catch (error) {
            uni.$u.toast(error);
            return Promise.reject();
        }
    };

    const appLogin = async () => {
        return new Promise((resolve, reject) => {
            uni.login({
                provider: "weixin",
                onlyAuthorize: true,
                success: async (res) => {
                    //@ts-ignore
                    const data = await uniAppLogin({
                        code: res.code,
                        terminal: getClient(),
                    });
                    resolve(data);
                },
                fail: (err) => {
                    reject(err);
                },
            });
        });
    };

    const pcLogin = async (res: any) => {
        uni.showLoading({
            title: "正在登录中，请稍后...",
            mask: true,
        });
        try {
            const { phoneNumber, authKey } = res;
            await pcLoginApi({
                account: phoneNumber,
                scene: 4,
                terminal: LoginWayEnum.PC,
                token: loginData.value.token,
                auth_key: authKey,
            });
            uni.hideLoading();
            uni.showToast({
                icon: "none",
                title: "扫码成功，请在PC页面查看",
                duration: 3000,
            });
            setTimeout(() => {
                uni.$u.route({
                    url: "/pages/index/index",
                    type: "redirect",
                });
            }, 3000);
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({
                title: error || "登录失败",
                icon: "none",
                duration: 3000,
            });
        }
    };

    const checkIsBindMobile = async () => {
        // #ifndef MP-WEIXIN
        if (!loginData.value.mobile && appStore.getLoginConfig.coerce_mobile) {
            showBindMobilePopup.value = true;
        } else {
            loginHandle();
        }
        // #endif
        // #ifdef MP-WEIXIN
        loginHandle();
        // #endif
    };

    const loginHandle = async () => {
        const { token } = loginData.value;
        userStore.login(token);
        userStore.getUser();
        appStore.getChatConfig();
        // #ifdef APP-PLUS
        router.navigateBack();
        // #endif
        // #ifndef APP-PLUS
        const pages = getCurrentPages();
        if (pages.length > 1) {
            const prevPage = pages[pages.length - 1];
            await router.navigateBack();
            // @ts-ignore
            const { onLoad, options } = prevPage;
            // 刷新上一个页面
            onLoad && onLoad(options);
        } else if (cache.get(BACK_URL)) {
            try {
                router.redirectTo(cache.get(BACK_URL));
            } finally {
                router.switchTab(cache.get(BACK_URL));
            }
        } else {
            router.reLaunch("/pages/index/index");
        }
        cache.remove(BACK_URL);
        // #endif
    };

    const loginAfter = (() => {
        const updateUsers = async () => {
            if (loginData.value.is_new_user && !showLoginPopup.value) {
                try {
                    await updateUser(
                        {
                            avatar: loginData.value.avatar,
                            nickname: loginData.value.nickname,
                        },
                        { token: loginData.value.token }
                    );
                } catch (error) {}
            } else if (showLoginPopup.value) {
                return Promise.reject();
            }
        };
        return series(updateUsers, checkIsBindMobile);
    })();

    const bindWx = async () => {
        const { code }: any = await uni.login({
            provider: "weixin",
        });
        await mnpAuthBind({
            code,
        });
    };

    const bindMobileSuccess = () => {
        showBindMobilePopup.value = false;
        loginHandle();
    };

    const { lockFn: wxLoginLock, isLock: wxIsLock } = useLockFn(async (res?: any) => {
        let data: any = null;
        try {
            // #ifdef H5
            data = await oaLogin();
            // #endif

            // #ifdef MP-WEIXIN
            data = await mnpLogin(res);
            // #endif

            // #ifdef APP-PLUS
            data = await appLogin();
            // #endif
            if (data) {
                loginData.value = data;
                if (isLoginAfter.value) {
                    loginAfter();
                }
            }
        } catch (error: any) {
            uni.showToast({
                title: error || "登录失败",
                icon: "none",
                duration: 3000,
            });
        }
    });

    const { lockFn: mobileLoginLock } = useLockFn(async (formData: any) => {
        uni.showLoading({
            title: "请稍后...",
        });
        try {
            const data = await login(formData);
            loginData.value = data;
            // #ifdef MP-WEIXIN
            bindWx();
            // #endif
            await loginAfter();
            uni.hideLoading();
        } catch (error: any) {
            uni.hideLoading();
            uni.$u.toast(error);
        }
    });

    const handleUpdateUser = async (value: any) => {
        await updateUser(value, { token: loginData.value.token });
        showLoginPopup.value = false;
        checkIsBindMobile();
    };

    watch(
        () => appStore.getLoginConfig,
        (value) => {
            // loginWay.value = value.default_login_way.toString();
            // if (value.login_way) {
            // 	loginWay.value = value.login_way[0];
            // }
            // if (value.login_way.includes(LoginWayEnum.WEIXIN)) {
            // 	loginWay.value = LoginWayEnum.WEIXIN;
            // }
        },
        {
            immediate: true,
        }
    );

    const removeWxQuery = () => {
        const options = route.query;
        if (options.code && options.state) {
            delete options.code;
            delete options.state;
            router.redirectTo({ path: route.path, query: options });
        }
    };

    onLoad(async () => {
        //#ifdef H5
        const options = wechatOa.getAuthData();
        try {
            if (options.code && options.scene === UrlScene.LOGIN) {
                uni.showLoading({
                    title: "请稍后...",
                });
                const data = await oaLogin(options);
                if (data) {
                    loginData.value = data;

                    await loginAfter();
                }
            }
        } catch (error) {
            removeWxQuery();
        } finally {
            uni.hideLoading();
            //清除保存的授权数据
            wechatOa.setAuthData();
        }
        //#endif
    });
    return {
        loginConfig,
        websiteConfig,
        loginData,
        showLoginPopup,
        showBindMobilePopup,
        showOtherWayBtn,
        loginWay,
        wxIsLock,
        isLoginAfter,
        bindMobileSuccess,
        mobileLoginLock,
        wxLoginLock,
        pcLogin,
        removeWxQuery,
        handleUpdateUser,
    };
}
