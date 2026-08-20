import { defineStore } from "pinia";
import { getConfig, getAiModelsLists, checkOem } from "@/api/app";
import { getChatConfig } from "@/api/chat";
import { getMemberQuota } from "@/api/user";
import { getToken } from "@/utils/auth";

interface AppSate {
    config: Record<string, any>;
    wssConfig: Record<string, any>;
    chatConfig: Record<string, any>;
    aiModels: Record<string, any>;
    /** OEM 站点配置(oem.oem/check) */
    oem: Record<string, any>;
    /** 完整会员配额(/user/memberQuota)，未拉过为 null */
    memberQuota: Record<string, any> | null;
    /** null = 不限制(空配额 / 未登录 / 接口失败)，string[] = 会员允许的模型 id */
    allowedModelIds: string[] | null;
    memberQuotaLoaded: boolean;
}

const filterAllowedChatModels = (channel: any[], allowedModelIds: string[] | null) => {
    const enabled = (channel || []).filter((item: any) => item.status == "1");
    if (!allowedModelIds?.length) return enabled;
    const idSet = new Set(allowedModelIds.map(String));
    // 会员等级 allowed_models 存的是模型库 id，与 channel.model_id / 智能体 model_id 对齐
    // 不能用 channel.id 做 OR：不同模型 id 与 model_id 可能交叉，会误放行
    return enabled.filter((item: any) => idSet.has(String(item.model_id)));
};

/** 模块级 in-flight，避免把 Promise 放进 pinia state */
let memberQuotaLoading: Promise<void> | null = null;

export const useAppStore = defineStore({
    id: "appStore",
    state: (): AppSate => ({
        config: {},
        wssConfig: {},
        chatConfig: {},
        aiModels: {
            chatModels: [],
            drawModels: [],
            humanModels: [],
        },
        oem: {},
        memberQuota: null,
        allowedModelIds: null,
        memberQuotaLoaded: false,
    }),
    getters: {
        getWebsiteConfig: (state) => state.config.website || {},
        getLoginConfig: (state) => state.config.login || {},
        getRegisterConfig: (state) => state.config.register || {},
        getVersion: (state) => state.config.version || {},
        getTabbarConfig: (state) => state.config.tabbar || {},
        getTabbarStyle: (state) => state.config.style || {},
        getH5Config: (state) => state.config.webPage || {},
        getDigitalHumanConfig: (state) => state.config.digital_human || {},
        getWssConfig: (state) => state.wssConfig || {},
        getShareConfig: (state) => state.config.mnp_share_config || {},
        getMeetingConfig: (state) => state.config.meeting_config || {},
        getLadderConfig: (state) => state.config.lianlian || {},
        getCardCodeConfig: (state) => state.config.card_code || {},
        /** recharge: is_ios_open / is_and_open / mnp_pay_type(1普通微信 2虚拟支付) */
        getRechargeConfig: (state) => state.config.recharge || {},
        /** 页脚备案：OEM 用品牌字段(空则不展示主站主体)；主站用平台 copyright */
        getCopyRightConfig: (state) => {
            const oem = state.oem || {};
            const fromConfig = Array.isArray(state.config.copyright) ? state.config.copyright : [];
            if (Number(oem.is_oem) === 1) {
                if ("icp_number" in oem || "company_name" in oem) {
                    const list: { key: string; value?: string }[] = [];
                    const icp = String(oem.icp_number || "").trim();
                    const company = String(oem.company_name || "").trim();
                    if (icp) list.push({ key: icp, value: "" });
                    if (company) list.push({ key: company, value: "" });
                    return list;
                }
                return fromConfig;
            }
            return fromConfig;
        },
        getByName: (state) => state.config.by_name || "",
        getAiModelConfig: (state) => state.aiModels || {},
        getIsShowRobot: (state) => state.config.is_robot_show,
        getCommentFilterConfig: (state) => state.config.comment_screening || [],
        getCommentContentConfig: (state) => state.config.touch_speech || [],
        getChatModel: (state) => state.config.ai_model?.channel || [],
        /** 生图/生视频模型（IndexLogic.draw_model.channel，对齐 PC getDrawModel） */
        getDrawModel: (state) => state.config.draw_model?.channel || [],
        getOemConfig: (state) => state.oem || {},
        /** 当前是否 OEM 独立站点 */
        isOemSite: (state) => Number(state.oem?.is_oem) === 1,
        /** 团队 OEM 已解散/关闭：全屏拦截，不回落主站 */
        isSiteClosed: (state) =>
            Number(state.oem?.site_closed) === 1 || Number((state.config as any)?.site_closed) === 1,
        /** 完整会员配额信息 */
        getMemberQuota: (state) => state.memberQuota,
        /** 当前会员等级可选用的对话模型（与 chatModel 取交集） */
        getAllowedChatModel(state): any[] {
            // 登录用户配额未就绪时不回落完整列表，避免刷新闪一下「全部大模型」
            if (!state.memberQuotaLoaded) return [];
            return filterAllowedChatModels(state.config.ai_model?.channel || [], state.allowedModelIds);
        },
    },
    actions: {
        getImageUrl(url: string) {
            return url.indexOf("http") ? `${this.config.domain}${url}` : url;
        },
        async getConfig(payload?: any) {
            const data = await getConfig(payload);
            this.config = data;
            this.getAiModelsData();
        },
        async getOem() {
            try {
                const data = await checkOem();
                this.oem = data || {};
            } catch {
                this.oem = { is_oem: 0 };
            }
        },
        /** 统一打开充值：OEM 进算力中心(页内展示联系二维码+兑换)，主站进套餐购买 */
        openRecharge() {
            uni.$u.route({ url: "/packages/pages/recharge/recharge" });
        },
        async getChatConfig() {
            const data = await getChatConfig();
            this.chatConfig = data;
        },
        async getAiModelsData() {
            const { chatModels, drawModels, humanModels } = await getAiModelsLists();
            this.aiModels = {
                chatModels,
                drawModels,
                humanModels,
            };
        },
        clearMemberQuota() {
            this.memberQuota = null;
            this.allowedModelIds = null;
            // 退出登录后视为未登录不限制，避免模型列表卡在空状态
            this.memberQuotaLoaded = true;
            memberQuotaLoading = null;
        },
        async fetchMemberQuota() {
            try {
                const data = await getMemberQuota();
                this.memberQuota = data || {};
                const allowed = data?.quota?.allowed_models;
                if (allowed && typeof allowed === "object" && Object.keys(allowed).length) {
                    this.allowedModelIds = Object.keys(allowed).map(String);
                } else {
                    this.allowedModelIds = null;
                }
            } catch {
                this.memberQuota = { is_member: false, level_name: "普通用户" };
                this.allowedModelIds = null;
            } finally {
                this.memberQuotaLoaded = true;
            }
        },
        async ensureMemberQuota(force = false) {
            if (!getToken()) {
                this.memberQuota = null;
                this.allowedModelIds = null;
                this.memberQuotaLoaded = true;
                return;
            }
            if (!force && this.memberQuotaLoaded) return;
            if (memberQuotaLoading) return memberQuotaLoading;
            memberQuotaLoading = this.fetchMemberQuota().finally(() => {
                memberQuotaLoading = null;
            });
            return memberQuotaLoading;
        },
    },
});
