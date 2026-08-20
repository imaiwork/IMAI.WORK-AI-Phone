import { defineStore } from "pinia";
import { robotCategory } from "@/api/robot";
import { getConfig, getScenePrompt, checkSurvey, checkOem, getAiModelsLists as getAiModelsListsApi } from "@/api/app";
import { getChatConfig as getChatConfigApi } from "@/api/chat";
import { getMemberQuota } from "@/api/user";
import { TOKEN_KEY } from "@/enums/cacheEnums";

interface AppSate {
    config: Record<string, any>;
    menuList: any[];
    hideSidebar: boolean;
    // 进入 /app/* 时缓存外层 sidebar 的展开态，离开时还原；null 表示当前没有自动折叠
    sidebarStash: boolean | null;
    chatConfig: Record<string, any>;
    showSurvey: boolean;
    scenePrompt: Array<{
        id: number;
        prompt_name: string;
        prompt_text: string;
    }>;
    oem: Record<string, any>;
    aiModels: Record<string, any>;
    showDataPackage: boolean;
    /** OEM 站点：联系管理员二维码 + 卡密兑换 */
    showOemRecharge: boolean;
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

export const useAppStore = defineStore("appStore", {
    state: (): AppSate => ({
        config: {},
        hideSidebar: true,
        sidebarStash: null,
        menuList: [],
        // 通用聊天配置
        chatConfig: {},
        showSurvey: false,
        scenePrompt: [],
        oem: {},
        aiModels: {
            chatModels: [],
            drawModels: [],
            humanModels: [],
        },
        showDataPackage: false,
        showOemRecharge: false,
        memberQuota: null,
        allowedModelIds: null,
        memberQuotaLoaded: false,
    }),
    getters: {
        getChatData: (state) => state.chatConfig || {},
        getWebsiteConfig: (state) => state.config.website || {},
        /** 页脚备案：OEM 用品牌字段(空则不展示主站主体)；主站用平台 copyright */
        getCopyRightConfig: (state) => {
            const oem = state.oem || {};
            const fromConfig = Array.isArray(state.config.copyright) ? state.config.copyright : [];
            if (Number(oem.is_oem) === 1) {
                // 新接口带 icp_number/company_name：只信品牌字段，避免露出主站备案
                if ("icp_number" in oem || "company_name" in oem) {
                    const list: { key: string; value: string }[] = [];
                    const icp = String(oem.icp_number || "").trim();
                    const company = String(oem.company_name || "").trim();
                    if (icp) list.push({ key: icp, value: "" });
                    if (company) list.push({ key: company, value: "" });
                    return list;
                }
                // 旧接口兼容：回落 /pc/config 覆盖后的 copyright
                return fromConfig;
            }
            return fromConfig;
        },
        // 侧栏等旧入口仍用 getCopyright，与 getCopyRightConfig 保持一致
        getCopyright(): { key: string; value: string }[] {
            return this.getCopyRightConfig as { key: string; value: string }[];
        },
        getVersion: (state) => state.config.version || "",
        getIndexConfig: (state) => state.config.index_config || [],
        getDigitalHumanConfig: (state) => state.config.digital_human || {},
        getMeetingConfig: (state) => state.config.meeting_config || {},
        getCardCodeConfig: (state) => state.config.card_code || {},
        getAppLiveConfig: (state) => state.config.ai_live || {},
        getHdConfig: (state) => state.config.draw || {},
        getAppConfig: (state) => state.config.app_config || {},
        getByName: (state) => state.config.by_name || "",
        getAiModels: (state) => state.aiModels || {},
        getOemConfig: (state) => state.oem || {},
        /** 当前是否 OEM 独立站点(团队 OEM / 旧版 OEM) */
        isOemSite: (state) => Number(state.oem?.is_oem) === 1,
        /** 团队 OEM 已解散/关闭：全屏拦截，不回落主站 */
        isSiteClosed: (state) =>
            Number(state.oem?.site_closed) === 1 || Number((state.config as any)?.site_closed) === 1,
        /** 完整会员配额信息 */
        getMemberQuota: (state) => state.memberQuota,
        getChatModel: (state) => state.config.ai_model?.channel || [],
        // 生图/生视频模型（PcLogic.draw_model.channel）
        getDrawModel: (state) => state.config.draw_model?.channel || [],
        /** 当前会员等级可选用的对话模型（与 chatModel 取交集） */
        getAllowedChatModel(state): any[] {
            // 登录用户配额未就绪时不回落完整列表，避免刷新闪一下「全部大模型」
            if (!state.memberQuotaLoaded) return [];
            return filterAllowedChatModels(state.config.ai_model?.channel || [], state.allowedModelIds);
        },
    },
    actions: {
        /** 统一打开充值：OEM 站点走联系管理员+兑换码，主站走算力套餐购买 */
        openRecharge() {
            if (Number(this.oem?.is_oem) === 1) {
                this.showOemRecharge = true;
                return;
            }
            this.showDataPackage = true;
        },
        async getConfig() {
            const config = await getConfig();
            this.config = config;
            this.getAiModelsData();
        },
        async getMenu() {
            const data = await robotCategory({ page_size: 9999, pid: 0 });
            this.menuList = data.lists;
        },
        async getChatConfig() {
            const data = await getChatConfigApi();
            this.chatConfig = data;
        },
        async getSurvey() {
            const { remind } = await checkSurvey();
            this.showSurvey = remind == 1;
        },
        toggleSidebar(toggle?: boolean) {
            this.hideSidebar = toggle ?? !this.hideSidebar;
            // 用户手动操作过侧边栏后，放弃自动还原（让用户的选择优先）
            this.sidebarStash = null;
        },
        // 进入 /app/* 路由：缓存当前展开态并自动折叠，让内容区拿到更多空间
        autoCollapseSidebar() {
            if (this.sidebarStash !== null) return;
            this.sidebarStash = this.hideSidebar;
            this.hideSidebar = true;
        },
        // 离开 /app/* 路由：若是自动折叠的，按缓存还原
        restoreSidebar() {
            if (this.sidebarStash === null) return;
            this.hideSidebar = this.sidebarStash;
            this.sidebarStash = null;
        },
        async getScenePrompt() {
            const data = await getScenePrompt();
            this.scenePrompt = data;
        },
        async getOem() {
            const data = await checkOem();
            this.oem = data;
        },
        async getAiModelsData() {
            const { chatModels, drawModels, humanModels } = await getAiModelsListsApi();
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
            const token = useCookie(TOKEN_KEY).value;
            if (!token) {
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
