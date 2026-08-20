import { useAppStore } from "@/stores/app";
import { computed, ref, watch } from "vue";
import { useLockFn } from "@/hooks/useLockFn";
import {
    OALogin,
    login,
    register,
    mnpLogin as mnpLoginApi,
    pcLogin as pcLoginApi,
    updateUser,
    uniAppLogin,
    mnpAuthBind,
} from "@/api/account";
import { bindUser } from "@/api/user";
import { useUserStore } from "@/stores/user";
import { useRouter, useRoute } from "uniapp-router-next";
import { onLoad } from "@dcloudio/uni-app";
import cache from "@/utils/cache";
import { BACK_URL, USER_SN, USER_ID } from "@/enums/constantEnums";
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

// 后端注册模式:1=开放手机号 2=邀请码 4=关闭注册
export enum RegisterModeEnum {
    OPEN = 1,
    INVITE = 2,
    CLOSED = 4,
}

// 关闭注册时后端明确拒绝建号(如「当前平台暂停注册，请联系管理员」)
const REGISTER_CLOSED_KEYWORDS = ["暂停注册"];
// 用户未注册/需先注册的后端提示(如「账号不存在，请先注册」)
// 仅在后台确认为「关闭注册」时才据此弹客服二维码;邀请码/开放注册时只 toast,引导去注册
const UNREGISTERED_USER_KEYWORDS = ["未注册", "请先注册", "账号不存在"];
const isKeywordError = (msg: any, keywords: string[]) =>
    typeof msg === "string" && keywords.some((k) => msg.includes(k));
const isRegisterClosedError = (msg: any) => isKeywordError(msg, REGISTER_CLOSED_KEYWORDS);
const isUnregisteredUserError = (msg: any) => isKeywordError(msg, UNREGISTERED_USER_KEYWORDS);
const shouldShowRegisterClosedPopup = (error: any, closed: boolean) =>
    isRegisterClosedError(error) || (closed && isUnregisteredUserError(error));
// 一键登录命中注册关闭时的 reject 标记,避免上层 wxLoginLock 再次 toast
const REGISTER_CLOSED_FLAG = "__register_closed__";

export function useLoginWay() {
    const appStore = useAppStore();
    const userStore = useUserStore();
    const router = useRouter();
    const route = useRoute();

    const showLoginPopup = ref(false);
    const showBindMobilePopup = ref(false);
    const showRegisterClosedPopup = ref(false);
    const loginWay = ref<string | number>(LoginWayEnum.WEIXIN);
    const isWeixinLogin = computed(() => loginWay.value == LoginWayEnum.WEIXIN);
    const isMobileLogin = computed(() => loginWay.value == LoginWayEnum.MOBILE);
    const hasWeixinLogin = computed(() => appStore.getLoginConfig.login_way.includes(LoginWayEnum.WEIXIN));
    const hasMobileLogin = computed(() => appStore.getLoginConfig.login_way.includes(LoginWayEnum.MOBILE));
    const showOtherWayBtn = computed(() => appStore.getLoginConfig.login_way?.length > 1);
    const isLoginAfter = ref(true);
    const websiteConfig = computed(() => appStore.getWebsiteConfig);
    const loginConfig = computed(() => appStore.getLoginConfig);
    const registerConfig = computed(() => appStore.getRegisterConfig);
    // closed/require_invite 优先按 register_mode 判定,兼容后端已派生的布尔字段
    const registerMode = computed(() => Number(registerConfig.value?.register_mode) || RegisterModeEnum.OPEN);
    const isRegisterClosed = computed(
        () => registerMode.value === RegisterModeEnum.CLOSED || !!registerConfig.value?.closed,
    );
    const isRequireInvite = computed(
        () => registerMode.value === RegisterModeEnum.INVITE || !!registerConfig.value?.require_invite,
    );
    const changeLoginWay = (way: LoginWayEnum) => {
        loginWay.value = way;
    };
    const loginData = ref<any>({});

    // 邀请码取值优先级:手填 -> 代理二维码 sn(USER_SN) -> 旧版 query.code(USER_ID)
    // 注意:default_invite_source 是用户列表「邀请来源」展示文案,不是邀请码,切勿当作 invite_code 提交
    const resolveInviteCode = (manual?: string) =>
        (typeof manual === "string" && manual.trim()) ||
        cache.get(USER_SN) ||
        cache.get(USER_ID) ||
        "";

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
                invite_code: resolveInviteCode(params?.invite_code),
            });
            if (data.is_new_user) {
                //是新用户
                showLoginPopup.value = true;
            }
            return data;
        } catch (error: any) {
            // 「暂停注册」由后端在新用户建号时抛出;旧用户登录不会走到这里
            if (shouldShowRegisterClosedPopup(error, isRegisterClosed.value)) {
                showRegisterClosedPopup.value = true;
                return Promise.reject(REGISTER_CLOSED_FLAG);
            }
            uni.showToast({
                title: error,
                icon: "none",
                duration: 3000,
            });
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
                    url: "/packages/pages/home/home",
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
            router.reLaunch("/packages/pages/home/home");
        }
        cache.remove(BACK_URL);
        // #endif
    };

    const loginAfter = (() => {
        const bindUsers = async () => {
            const user_sn = cache.get(USER_SN);
            try {
                if (user_sn) {
                    await bindUser({ sn: user_sn }, loginData.value.token);
                    cache.remove(USER_SN);
                }
            } catch (error: any) {
                // 注册时已用 invite_code 写入上级时,二次绑定会报「已存在上级」,属预期,静默清理缓存
                const msg = typeof error === "string" ? error : "";
                if (msg.includes("已存在上级")) {
                    cache.remove(USER_SN);
                    return;
                }
                uni.showToast({
                    title: error || "绑定失败",
                    icon: "none",
                    duration: 3000,
                });
            }
        };
        const updateUsers = async () => {
            if (loginData.value.is_new_user && !showLoginPopup.value) {
                try {
                    await updateUser(
                        {
                            avatar: loginData.value.avatar,
                            nickname: loginData.value.nickname,
                        },
                        { token: loginData.value.token },
                    );
                } catch (error) {}
            } else if (showLoginPopup.value) {
                return Promise.reject();
            }
        };
        return series(bindUsers, updateUsers, checkIsBindMobile);
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
            if (error === REGISTER_CLOSED_FLAG) return;
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
            // 邀请码统一按 手填 -> 代理 sn -> 旧版 code 解析
            const invite_code = resolveInviteCode(formData.invite_code);
            // 注册走独立接口;登录走 account(用户不存在直接报错)
            const data =
                Number(formData.is_register) === 1
                    ? await register({
                          account: formData.account,
                          code: formData.code,
                          invite_code,
                      })
                    : await login({
                          ...formData,
                          invite_code,
                          is_register: 0,
                      });
            loginData.value = data;
            // #ifdef MP-WEIXIN
            bindWx();
            // #endif
            await loginAfter();
            uni.hideLoading();
        } catch (error: any) {
            uni.hideLoading();
            if (shouldShowRegisterClosedPopup(error, isRegisterClosed.value)) {
                showRegisterClosedPopup.value = true;
                return;
            }
            uni.showToast({
                title: error || "登录失败",
                icon: "none",
                duration: 3000,
            });
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
        },
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
        registerConfig,
        isRegisterClosed,
        isRequireInvite,
        loginData,
        showLoginPopup,
        showBindMobilePopup,
        showRegisterClosedPopup,
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
