<script setup lang="ts">
import { useAppStore } from "./stores/app";
import { useUserStore } from "./stores/user";
import cache from "./utils/cache";
import { SHARE_ID, USER_SN, USER_ID } from "./enums/constantEnums";
import { strToParams } from "./utils/util";
import { useRoute, useRouter } from "uniapp-router-next";

const router = useRouter();
const route = useRoute();
const appStore = useAppStore();
const userStore = useUserStore();

const cacheInvite = (query: any = {}) => {
    const { share_id, code } = query;
    const user_sn = query.user_sn || strToParams(decodeURIComponent(query["scene"]))["user_sn"];
    if (share_id) {
        cache.set(SHARE_ID, share_id);
    }
    if (user_sn) {
        cache.set(USER_SN, user_sn);
    }
    if (code) {
        cache.set(USER_ID, code);
    }
};
//#ifdef H5
const setH5WebIcon = () => {
    const config = appStore.getWebsiteConfig;
    let favicon: HTMLLinkElement = document.querySelector('link[rel="icon"]')!;
    if (favicon) {
        favicon.href = config.shop_logo;
        return;
    }
    favicon = document.createElement("link");
    favicon.rel = "icon";
    favicon.href = config.shop_logo;
    document.head.appendChild(favicon);
};

//#endif
const getConfig = async () => {
    await appStore.getConfig();
    //#ifdef H5
    setH5WebIcon();
    //#endif
    const { status, page_status, page_url } = appStore.getH5Config;
    if (route.meta.webview) return;
};

onLaunch(async (opinion) => {
    getConfig();
    userStore.getUser();
    // appStore.getWssConfigParams();
    cacheInvite(opinion?.query);
});
</script>
<style lang="scss">
//
</style>
