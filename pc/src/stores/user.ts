import { getUserCenter, getTokensConfig } from "@/api/user";
import { TOKEN_KEY, VISITOR_ID } from "@/enums/cacheEnums";
import { useLocalStorage } from "@vueuse/core";
import { defineStore } from "pinia";
import { LoginPopupTypeEnum } from "~/enums/appEnums";

interface UserSate {
    userInfo: Record<string, any>;
    token: string | null;
    temToken: string | null;
    showLogin: boolean;
    loginPopupType: LoginPopupTypeEnum;
    tokensConfig: any[];
    visitorId: string;
}
export const useUserStore = defineStore("userStore", {
    state: (): UserSate => {
        const TOKEN = useCookie(TOKEN_KEY);
        const visitorId = useLocalStorage(VISITOR_ID, "");

        return {
            visitorId: visitorId.value || "",
            userInfo: {},
            token: TOKEN.value,
            temToken: null,
            showLogin: false,
            loginPopupType: LoginPopupTypeEnum.LOGIN,
            tokensConfig: [],
        };
    },
    getters: {
        isLogin: (state) => !!state.token,
        userTokens: (state) => parseFloat(state.userInfo.tokens),
        getTokenByScene: (state) => (scene: string) => state.tokensConfig.find((item) => item.scene === scene) || {},
    },
    actions: {
        async getUser() {
            const data = await getUserCenter();
            this.userInfo = data;
            this.getTokensConfig();
        },
        // 获取算力消耗配置
        async getTokensConfig() {
            const data = await getTokensConfig();
            this.tokensConfig = data || [];
        },
        //弹起登录二维码
        toggleShowLogin(toggle?: boolean) {
            this.showLogin = toggle ?? !this.showLogin;
        },
        setLoginPopupType(type: LoginPopupTypeEnum = LoginPopupTypeEnum.LOGIN) {
            this.loginPopupType = type;
        },
        login(token: string) {
            const oneYear = 360 * 24 * 60 * 60 * 1000;
            const TOKEN = useCookie(TOKEN_KEY, {
                expires: new Date(Date.now() + oneYear),
            });

            this.token = token;
            TOKEN.value = token;
        },
        logout() {
            const TOKEN = useCookie(TOKEN_KEY);
            this.token = null;
            this.userInfo = {};
            TOKEN.value = null;
        },
        async getFingerprint() {
            const visitorId = useLocalStorage(VISITOR_ID, "");
            if (this.visitorId) return this.visitorId;
            this.visitorId = uniqueId();
            visitorId.value = this.visitorId;
            return this.visitorId;
        },
    },
});
