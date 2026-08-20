import { getUserCenter, getTokensConfig, getAgentUserParentQrcode } from "@/api/user";
import { TOKEN_KEY, VISITOR_ID } from "@/enums/cacheEnums";
import { useLocalStorage } from "@vueuse/core";
import { defineStore } from "pinia";
import { LoginPopupTypeEnum } from "~/enums/appEnums";
import { useAppStore } from "@/stores/app";

interface UserSate {
    userInfo: Record<string, any>;
    token: string | null;
    temToken: string | null;
    showLogin: boolean;
    loginPopupType: LoginPopupTypeEnum;
    tokensConfig: any[];
    visitorId: string;
    agentUserParentQrcode: string;
    agentQrcodeLoaded: boolean;
    /** 加入/退出/切换企业后递增,头部监听后刷新企业名 */
    teamVersion: number;
    /** 控制台刚拉到的企业快照,头部先同步再二次确认 */
    teamHeaderHint: { in_team: number; name: string } | null;
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
            agentUserParentQrcode: "",
            agentQrcodeLoaded: false,
            teamVersion: 0,
            teamHeaderHint: null,
        };
    },
    getters: {
        isLogin: (state) => !!state.token,
        userTokens: (state) => parseFloat(state.userInfo.tokens || 0),
        getTokenByScene: (state) => (scene: string) => state.tokensConfig.find((item) => item.scene === scene) || {},
    },
    actions: {
        async getUser() {
            const data = await getUserCenter();
            this.userInfo = data;
            this.getTokensConfig();
            // 等待会员可选用模型就绪，避免页面先渲染完整大模型列表
            await useAppStore().ensureMemberQuota(true);
        },
        /** 轻量刷新右上角算力(不含会员配额),供定时/切回页签/账单页同步 */
        async refreshTokens() {
            if (!this.token) return;
            try {
                const data = await getUserCenter();
                if (!data) return;
                this.userInfo = {
                    ...this.userInfo,
                    tokens: data.tokens,
                    personal_tokens: data.personal_tokens ?? data.tokens,
                };
            } catch {
                // 静默:后台消耗同步失败不影响主流程
            }
        },
        /**
         * 通知布局头刷新当前企业名称(加入/退出/开通/改名后调用)
         * @param hint 控制台已拿到的最新状态,头部可立刻切换,避免仍显示旧企业名
         */
        notifyTeamChanged(hint?: { in_team?: number; name?: string } | null) {
            if (hint && typeof hint === "object") {
                this.teamHeaderHint = {
                    in_team: Number(hint.in_team) === 1 ? 1 : 0,
                    name: String(hint.name || ""),
                };
            } else {
                this.teamHeaderHint = null;
            }
            this.teamVersion += 1;
        },
        // 获取算力消耗配置
        async getTokensConfig() {
            const data = await getTokensConfig();
            this.tokensConfig = data || [];
        },
        // 获取代理用户上级二维码（全局共享，刷新页面只调用一次）
        async getAgentParentQrcode() {
            if (this.agentQrcodeLoaded) return;
            this.agentQrcodeLoaded = true;
            try {
                const res = await getAgentUserParentQrcode();
                this.agentUserParentQrcode = res?.qr_code || "";
            } catch {
                // 静默失败，允许后续重试；组件回退使用站点客服二维码
                this.agentQrcodeLoaded = false;
            }
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
            this.agentUserParentQrcode = "";
            this.agentQrcodeLoaded = false;
            this.teamHeaderHint = null;
            this.teamVersion = 0;
            TOKEN.value = null;
            useAppStore().clearMemberQuota();
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
