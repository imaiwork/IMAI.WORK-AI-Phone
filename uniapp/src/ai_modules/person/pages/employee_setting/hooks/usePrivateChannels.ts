import { ref, reactive, type InjectionKey, type Ref } from "vue";
import ImageGreenIcon from "@/ai_modules/person/static/icons/employee/image-green.svg";
import MessageGreenIcon from "@/ai_modules/person/static/icons/employee/message-green.svg";
import UserOrangeIcon from "@/ai_modules/person/static/icons/employee/user-orange.svg";
import UserPlusGreenIcon from "@/ai_modules/person/static/icons/employee/user-plus-green.svg";
import type { KeywordEditOptions } from "./useLeadFeatures";

export type PrivatePanelKey = "add" | "group" | "moments";
export type GroupTriggerMode = 1 | 2;

export interface PrivatePanelMeta {
    key: PrivatePanelKey;
    title: string;
    icon: string;
}

export interface UsePrivateChannelsDeps {
    /** 共享的关键词编辑弹窗入口，由父层提供 */
    openKeywordEditPopup: (list: Ref<string[]> | string[], index: number, options: KeywordEditOptions) => void;
    /** 当前人设 id（朋友圈设置跳转用），返回 null/空表示未保存 */
    getPersonId: () => string | number | null | undefined;
}

// 私域板块（加好友 / 自动加群 / 朋友圈）状态与操作
export function usePrivateChannels(deps: UsePrivateChannelsDeps) {
    const { openKeywordEditPopup, getPersonId } = deps;

    // ===== 常量 =====
    const privatePanelList: PrivatePanelMeta[] = [
        { key: "add", title: "加好友设置", icon: MessageGreenIcon },
        { key: "group", title: "自动加群设置", icon: UserPlusGreenIcon },
        { key: "moments", title: "朋友圈设置", icon: ImageGreenIcon },
    ];
    const groupNameTemplateTags = ["{客户名}", "{销售名}", "{日期}"];
    const userOrangeIcon = UserOrangeIcon;

    // ===== 状态 =====
    const privatePanelStatus = reactive<Record<PrivatePanelKey, boolean>>({
        add: true,
        group: true,
        moments: true,
    });
    const openPrivatePanel = ref<PrivatePanelKey | "">("");
    const privateClueCount = ref(0);
    const friendApplyText = ref("您好我是AI导师，曾带百人团队做AI落地，可帮您解决AI应用相关疑问");
    const saleWechatInput = ref("");
    const saleWechatList = ref<string[]>([]);
    const groupNameTemplate = ref("{客户名}的专属VIP服务群");
    const groupWelcomeEnabled = ref(true);
    const groupWelcomeText = ref("哈喽{客户名}，欢迎！我是您的专属销售顾问，以后有任何问题都可以直接在这个群里找我哦~");
    const carryHistoryEnabled = ref(true);
    const groupTriggerMode = ref<GroupTriggerMode>(1);
    const groupKeywords = ref(["加群", "进群"]);
    const circleCount = ref(20);

    // ===== 工具 =====
    const removeKeyword = (list: string[], word: string) => {
        const index = list.indexOf(word);
        if (index >= 0) list.splice(index, 1);
    };

    // ===== 操作 =====
    const togglePrivatePanel = (key: PrivatePanelKey) => {
        openPrivatePanel.value = openPrivatePanel.value === key ? "" : key;
    };

    const openSaleWechatPopup = (index = -1) =>
        openKeywordEditPopup(saleWechatList, index, {
            title: index >= 0 ? "编辑销售微信" : "添加销售微信",
            label: "销售微信号",
            placeholder: "请输入微信号",
            tip: "建议填写微信号，或在机器人端统一设置备注名。",
            maxlength: 32,
            maxItems: 5,
        });

    const openGroupKeywordPopup = (index = -1) =>
        openKeywordEditPopup(groupKeywords, index, {
            title: index >= 0 ? "编辑拉群触发词" : "添加拉群触发词",
            label: "触发词",
            placeholder: "如：加群、进群",
            tip: "客户消息命中触发词后，将自动进入拉群流程。",
        });

    const addSaleWechat = () => {
        const value = saleWechatInput.value.trim();
        if (!value) {
            openSaleWechatPopup();
            return;
        }
        if (saleWechatList.value.length >= 5) {
            uni.$u.toast("最多添加 5 个销售微信");
            return;
        }
        if (!saleWechatList.value.includes(value)) saleWechatList.value.push(value);
        saleWechatInput.value = "";
    };

    const insertTemplate = (target: "groupName" | "welcome", template: string) => {
        if (target === "groupName") {
            groupNameTemplate.value = `${groupNameTemplate.value}${template}`.slice(0, 32);
            return;
        }
        groupWelcomeText.value = `${groupWelcomeText.value}${template}`;
    };

    const setGroupTriggerMode = (mode: GroupTriggerMode) => {
        groupTriggerMode.value = mode;
    };

    const clearGroupKeywords = () => {
        if (!groupKeywords.value.length) return;
        uni.showModal({
            title: "提示",
            content: "确定清空全部自定义触发词吗？",
            success: (res) => {
                if (res.confirm) {
                    groupKeywords.value = [];
                }
            },
        });
    };

    const changeCircleCount = (delta: number) => {
        circleCount.value = Math.max(0, circleCount.value + delta);
    };

    const openCircleSetting = () => {
        const personId = getPersonId();
        if (!personId) {
            uni.$u.toast("请先完成人设信息保存");
            return;
        }
        uni.navigateTo({
            url: `/ai_modules/person/pages/setting_circle_publish/setting_circle_publish?person_id=${personId}`,
        });
    };

    return {
        // 常量
        privatePanelList,
        groupNameTemplateTags,
        userOrangeIcon,
        // 状态
        privatePanelStatus,
        openPrivatePanel,
        privateClueCount,
        friendApplyText,
        saleWechatList,
        saleWechatInput,
        groupNameTemplate,
        groupWelcomeEnabled,
        groupWelcomeText,
        carryHistoryEnabled,
        groupTriggerMode,
        groupKeywords,
        circleCount,
        // 工具/操作
        removeKeyword,
        togglePrivatePanel,
        openSaleWechatPopup,
        openGroupKeywordPopup,
        addSaleWechat,
        insertTemplate,
        setGroupTriggerMode,
        clearGroupKeywords,
        changeCircleCount,
        openCircleSetting,
    };
}

export type PrivateChannelsContext = ReturnType<typeof usePrivateChannels>;
export const PrivateChannelsKey: InjectionKey<PrivateChannelsContext> = Symbol("PrivateChannels");
