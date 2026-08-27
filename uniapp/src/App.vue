<script setup lang="ts">
import { bindUser } from "./api/user";
import { useAppStore } from "./stores/app";
import { useUserStore } from "./stores/user";
import cache from "./utils/cache";
import { SHARE_ID, USER_SN, USER_ID } from "./enums/constantEnums";
import { strToParams } from "./utils/util";
import { checkMiniProgramUpdate } from "./utils/update";
import { useRoute, useRouter } from "uniapp-router-next";

const router = useRouter();
const route = useRoute();
const appStore = useAppStore();
const userStore = useUserStore();

const SITE_CLOSED_URL = "/packages/pages/site_closed/site_closed";

const isOnSiteClosedPage = () => {
    try {
        const pages = getCurrentPages();
        const cur: any = pages[pages.length - 1];
        const routePath = String(cur?.route || "");
        return routePath.includes("site_closed");
    } catch {
        return false;
    }
};

/** OEM 关站：全屏拦截，不进业务页 */
const redirectIfSiteClosed = () => {
    if (!appStore.isSiteClosed) return false;
    if (!isOnSiteClosedPage()) {
        uni.reLaunch({ url: SITE_CLOSED_URL });
    }
    return true;
};

const cacheInvite = async (query: any = {}) => {
    const { share_id, code } = query;
    if (share_id) {
        cache.set(SHARE_ID, share_id);
    }
    if (code) {
        cache.set(USER_ID, code);
    }
};

//#ifdef H5
const setH5WebIcon = () => {
    // OEM 站点用 OEM logo,主站回落平台 shop_logo
    const icon = appStore.getSiteShopLogo;
    let favicon: HTMLLinkElement = document.querySelector('link[rel="icon"]')!;
    if (favicon) {
        favicon.href = icon;
        return;
    }
    favicon = document.createElement("link");
    favicon.rel = "icon";
    favicon.href = icon;
    document.head.appendChild(favicon);
};

//#endif
const getConfig = async () => {
    // 先识别 OEM/关站，关站则不拉业务配置、不进主站
    await appStore.getOem();
    if (redirectIfSiteClosed()) return;
    await appStore.getConfig();
    //#ifdef H5
    setH5WebIcon();
    //#endif
    const { status, page_status, page_url } = appStore.getH5Config;
    if (route.meta.webview) return;
};

const cacheAgent = async (query: any = {}) => {
    const user_sn = query.sn || strToParams(decodeURIComponent(query["scene"]))["sn"];
    if (user_sn) {
        if (userStore.isLogin) {
            try {
                await bindUser({ sn: user_sn }, userStore.token!);
                userStore.getUser();
                cache.remove(USER_SN);
                return;
            } catch (error: any) {
                uni.showToast({
                    title: error || "绑定失败",
                    icon: "none",
                    duration: 3000,
                });
            }
        }
        cache.set(USER_SN, user_sn);
    }
};

onShow((opinion) => {
    if (appStore.isSiteClosed) {
        redirectIfSiteClosed();
        return;
    }
    cacheAgent(opinion?.query);
});

onLaunch(async (opinion) => {
    checkMiniProgramUpdate();
    await getConfig();
    if (appStore.isSiteClosed) return;
    userStore.getUser();
    // appStore.getWssConfigParams();
    cacheInvite(opinion?.query);
});
</script>
<style lang="scss">
//
</style>
