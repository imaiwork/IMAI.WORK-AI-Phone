import { defineStore } from "pinia";
import { getConfig } from "@/api/app";
import { getChatConfig } from "@/api/chat";
import { getVoiceChatWssUrl } from "@/api/voice_chat";
interface AppSate {
    config: Record<string, any>;
    wssConfig: Record<string, any>;
    chatConfig: Record<string, any>;
    uploadAssistantId: string;
}
export const useAppStore = defineStore({
    id: "appStore",
    state: (): AppSate => ({
        config: {},
        wssConfig: {},
        chatConfig: {},
        uploadAssistantId: "",
    }),
    getters: {
        getWebsiteConfig: (state) => state.config.website || {},
        getLoginConfig: (state) => state.config.login || {},
        getVersion: (state) => state.config.version || "",
        getTabbarConfig: (state) => state.config.tabbar || {},
        getH5Config: (state) => state.config.webPage || {},
        getDigitalHumanConfig: (state) => state.config.digital_human || {},
        getWssConfig: (state) => state.wssConfig || {},
        getShareConfig: (state) => state.config.mnp_share_config || {},
        getMeetingConfig: (state) => state.config.meeting_config || {},
        getLadderConfig: (state) => state.config.lianlian || {},
        getCardCodeConfig: (state) => state.config.card_code || {},
        getRechargeConfig: (state) => state.config.recharge || {},
        getCopyRightConfig: (state) => state.config.copyright || [],
        getByName: (state) => state.config.by_name || "",
        getAiModelConfig: (state) => state.config.ai_model || {},
        getIsShowRobot: (state) => state.config.is_robot_show ,
    },
    actions: {
        getImageUrl(url: string) {
            return url.indexOf("http") ? `${this.config.domain}${url}` : url;
        },
        async getConfig(payload?: any) {
            const data = await getConfig(payload);
            this.config = data;
        },
        async getWssConfigParams() {
            const data = await getVoiceChatWssUrl();
            this.wssConfig = data;
        },
        async getChatConfig() {
            const data = await getChatConfig();
            this.uploadAssistantId = data.assistants_id;
            this.chatConfig = data;
        },
    },
});
