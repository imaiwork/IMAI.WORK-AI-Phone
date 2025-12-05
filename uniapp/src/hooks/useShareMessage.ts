import router from "@/router";
import appConfig from "@/config";
import { paramsToStr } from "@/utils/util";
import { useNavigationBarTitleStore } from "@/stores/navigationBarTitle";
import { useAppStore } from "@/stores/app";
export interface ShareOptions {
    desc: string;
    title: string;
    imageUrl: string;
    path: string;
}

export type UserShareOptions = Partial<ShareOptions>;

//生成分享路径，首页和当前页面两种
export async function generateSharePath(isHome = false) {
    const route = router.currentRoute.value;
    let origin = "";
    //#ifdef H5
    origin = `${window.location.origin}/mobile`;
    //#endif
    //#ifdef APP-PLUS
    origin = `${appConfig.baseUrl}mobile`;
    //#endif

    const config = {
        path: isHome ? "/pages/index/index" : route.path,
        query: isHome ? {} : route.query,
    };
    const path = `${origin}${config.path}`;
    const options: any = config.query;
    return `${path}${paramsToStr(options)}`;
}

export function useShareMessage() {
    const resolvedH5Options = (options: ShareOptions) => {
        return {
            desc: options.desc,
            img_url: options.imageUrl,
            link: options.path,
            title: options.title,
        };
    };

    const resolvedMpOptions = (options: ShareOptions) => {
        return {
            imageUrl: options.imageUrl,
            path: options.path,
            title: options.title,
        };
    };
    /**
     * @description  解析分享参数，将外部参数与默认参数合并
     * @param options
     * @returns
     */
    const resolveOptions = async (options: UserShareOptions = {}): Promise<ShareOptions> => {
        const navigationBarTitleStore = useNavigationBarTitleStore();
        const route = router.currentRoute.value;
        const appStore = useAppStore();
        const { style } = (router.routeMatcher.getRouteByPath(route.path) as any) || {};
        uni.showLoading({
            title: "请稍后...",
        });

        const { share_title, share_image, share_desc } = appStore.getShareConfig;
        const { shop_name, shop_logo } = appStore.getWebsiteConfig;
        // 分享为首页
        const isShareWithHome = false;
        const link = await generateSharePath(isShareWithHome);
        let resolved = {
            title: share_title,
            path: link,
            desc: share_desc,
            imageUrl: share_image,
        };

        // 非首页可以合并外部参数
        if (!isShareWithHome) {
            resolved = {
                ...resolved,
                ...options,
            };
        }
        if (!resolved.title) {
            if (isShareWithHome) {
                resolved.title = shop_name;
            } else {
                // 1. 用户点击进入页面的后台配置标题
                // 2. 页面内pagesjson组册的页面标题
                // 3. 网站名称
                resolved.title = navigationBarTitleStore.getTitle || style?.navigationBarTitleText || shop_name;
            }
        }

        if (!resolved.imageUrl) {
            resolved.imageUrl = shop_logo;
        }
        // #ifdef H5
        resolved = resolvedH5Options(resolved) as any;
        // #endif
        // #ifdef MP-WEIXIN
        resolved = resolvedMpOptions(resolved) as any;
        // #endif
        uni.hideLoading();
        return resolved as ShareOptions;
    };

    // 使用分享，可以单独在页面中使用
    const useShare = (options: UserShareOptions = {}) => {
        // #ifdef H5
        const registerEvent = () => {
            WeixinJSBridge.on("menu:share:appmessage", async function () {
                const resolved = await resolveOptions(options);
                //@ts-ignore
                WeixinJSBridge.invoke("sendAppMessage", resolved);
            });
            WeixinJSBridge.on("menu:share:timeline", async function () {
                const resolved = await resolveOptions(options);
                //@ts-ignore
                WeixinJSBridge.invoke("shareTimeline", resolved);
            });
        };
        if (typeof WeixinJSBridge === "object") {
            registerEvent();
        } else {
            document.addEventListener("WeixinJSBridgeReady", registerEvent, false);
        }
        // #endif
    };

    return {
        resolveOptions,
        useShare,
    };
}

export async function useSharedId() {
    const options = uni.getEnterOptionsSync();
    const share_id = options.query.share_id;
}
