import { getUserCenter, getTokensConfig } from "@/api/user";
import { CHAT_LIMIT_KEY, TOKEN_KEY } from "@/enums/constantEnums";
import { useSharedId } from "@/hooks/useShareMessage";
import { getToken } from "@/utils/auth";
import cache from "@/utils/cache";
import { defineStore } from "pinia";
import { useAppStore } from "@/stores/app";
import { useAppStore } from "@/stores/app";

interface UserSate {
    userInfo: Record<string, any>;
    token: string | null;
    temToken: string | null;
    tokensConfig: any[];
}
export const useUserStore = defineStore({
    id: "userStore",
    state: (): UserSate => ({
        userInfo: {},
        token: getToken() || null,
        temToken: null,
        tokensConfig: [],
    }),
    getters: {
        isLogin: (state) => !!state.token,
        userTokens: (state) => parseFloat(state.userInfo.tokens) || 0,
        // 个人算力原值(团队空间下 tokens 是企业钱包口径;未部署新后端时回退 tokens)
        personalTokens: (state) => parseFloat(state.userInfo.personal_tokens ?? state.userInfo.tokens) || 0,
        getTokenByScene: (state) => (scene: string) => state.tokensConfig.find((item) => item.scene === scene) || {},
    },
    actions: {
        async getUser() {
            const data = await getUserCenter({
                token: this.token,
            });
            this.userInfo = data;
            this.getTokensConfig();
            // 等待会员可选用模型就绪，避免页面先渲染完整大模型列表
            await useAppStore().ensureMemberQuota(true);
        },
        /** 轻量刷新算力(不含配置/会员配额),供工作台出结果后同步消耗 */
        async refreshTokens() {
            if (!this.token) return;
            try {
                const data = await getUserCenter({ token: this.token });
                if (!data) return;
                this.userInfo = {
                    ...this.userInfo,
                    tokens: data.tokens,
                    personal_tokens: data.personal_tokens ?? data.tokens,
                };
            } catch {
                // 静默:同步失败不影响主流程
            }
        },
        // 获取算力消耗配置
        async getTokensConfig() {
            const data = await getTokensConfig();
            this.tokensConfig = data || [];
        },

        login(token: string) {
            this.token = token;
            cache.set(TOKEN_KEY, token);
            useSharedId();
        },
        logout() {
            this.token = "";
            this.userInfo = {};
            cache.remove(TOKEN_KEY);
            cache.remove(CHAT_LIMIT_KEY);
            useAppStore().clearMemberQuota();
        },
    },
});
