import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { isEmptyObject } from "@/utils/validate";

export default defineNuxtRouteMiddleware(async (to, from) => {
    const userStore = useUserStore();
    const appStore = useAppStore();
    try {
        if (isEmptyObject(appStore.config)) {
            await appStore.getConfig();
        }
        if (isEmptyObject(appStore.oem)) {
            appStore.getOem();
        }
        if (userStore.isLogin) {
            if (isEmptyObject(userStore.userInfo)) {
                await userStore.getUser();
            }
        }
    } catch (error) {
        userStore.$reset();
    }
    if (userStore.isLogin) {
        if (isEmptyObject(userStore.userInfo)) {
            appStore.getSurvey();
        }
    }
    if (isEmptyObject(appStore.chatConfig)) {
        appStore.getChatConfig();
    }
    if (isEmptyObject(appStore.menuList)) {
        await appStore.getMenu();
    }
    if (isEmptyObject(appStore.scenePrompt)) {
        appStore.getScenePrompt();
    }
});
