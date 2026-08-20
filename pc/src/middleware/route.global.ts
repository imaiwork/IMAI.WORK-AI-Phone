import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { isEmptyObject } from "@/utils/validate";

export default defineNuxtRouteMiddleware(async (to, from) => {
    const userStore = useUserStore();
    const appStore = useAppStore();
    try {
        // 先拉 OEM 识别，关站则不再拉业务配置
        if (isEmptyObject(appStore.oem)) {
            await appStore.getOem();
        }
        if (appStore.isSiteClosed) {
            return;
        }
        if (isEmptyObject(appStore.config)) {
            await appStore.getConfig();
        }
        if (userStore.isLogin) {
            if (isEmptyObject(userStore.userInfo)) {
                await userStore.getUser();
            }
        }
    } catch (error) {
        userStore.$reset();
    }
    if (appStore.isSiteClosed) {
        return;
    }
    if (userStore.isLogin) {
        if (isEmptyObject(userStore.userInfo)) {
            appStore.getSurvey();
        }
        userStore.getAgentParentQrcode();
    }
    if (isEmptyObject(appStore.chatConfig)) {
        appStore.getChatConfig();
    }
    if (isEmptyObject(appStore.menuList)) {
        appStore.getMenu();
    }
    if (isEmptyObject(appStore.scenePrompt)) {
        appStore.getScenePrompt();
    }

    const toIsApp = to.path.startsWith("/app/");
    const fromIsApp = from?.path?.startsWith("/app/");
    if (toIsApp && !fromIsApp) {
        appStore.autoCollapseSidebar();
    } else if (!toIsApp && fromIsApp) {
        appStore.restoreSidebar();
    }
});
