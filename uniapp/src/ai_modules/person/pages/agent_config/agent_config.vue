<template>
    <view class="min-h-screen bg-[#F7F8FA] pb-[200rpx]" v-if="!loading">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#FFFFFF' }"
            title="智能体配置"
            title-bold></u-navbar>

        <view class="flex items-center gap-[16rpx] px-[32rpx] py-[24rpx] bg-white mb-[20rpx]">
            <view class="w-[64rpx] h-[64rpx] bg-[#F0F5FF] rounded-full flex items-center justify-center">
                <u-icon name="account-fill" color="#3B71E8" size="32"></u-icon>
            </view>
            <view class="flex gap-2">
                <text class="text-[24rpx] text-[#999999]">当前配置账号</text>
                <text class="text-[28rpx] font-bold text-[#1A1A1A]">{{ detail.persona_name }}</text>
            </view>
        </view>

        <view class="px-[24rpx]">
            <view class="mb-[24rpx]">
                <text class="block text-[26rpx] font-medium text-[#999999] mb-[16rpx] px-[16rpx]">日常互动</text>
                <view class="bg-white rounded-[24rpx] px-[32rpx] shadow-sm">
                    <view
                        class="flex items-center justify-between gap-x-1 py-[32rpx] border-b border-[#F5F7FA] active:opacity-70 transition-opacity"
                        @click="openSetting('comment')">
                        <text class="text-[30rpx] font-medium text-[#333333] shrink-0">评论区接管</text>
                        <view class="flex items-center gap-[12rpx]">
                            <text
                                class="text-[26rpx] line-clamp-1 break-all"
                                :class="isConfigured('comment') ? 'text-primary' : 'text-[#999999]'"
                                >{{ getSummary("comment") }}</text
                            >
                            <u-icon name="arrow-right" color="#C0C4CC" size="24"></u-icon>
                        </view>
                    </view>
                    <view
                        class="flex items-center justify-between gap-x-1 py-[32rpx] active:opacity-70 transition-opacity"
                        @click="openSetting('moments_interact')">
                        <text class="text-[30rpx] font-medium text-[#333333] shrink-0">朋友圈互动接管</text>
                        <view class="flex items-center gap-[12rpx]">
                            <text
                                class="text-[26rpx] line-clamp-1 break-all"
                                :class="isConfigured('moments_interact') ? 'text-primary' : 'text-[#999999]'"
                                >{{ getSummary("moments_interact") }}</text
                            >
                            <u-icon name="arrow-right" color="#C0C4CC" size="24"></u-icon>
                        </view>
                    </view>
                </view>
            </view>

            <view class="mb-[24rpx]">
                <text class="block text-[26rpx] font-medium text-[#999999] mb-[16rpx] px-[16rpx]">私信处理</text>
                <view class="bg-white rounded-[24rpx] px-[32rpx] shadow-sm">
                    <view
                        v-for="(item, index) in dmConfigList"
                        :key="item.id"
                        class="flex items-center justify-between gap-x-1 py-[32rpx] active:opacity-70 transition-opacity"
                        :class="index !== dmConfigList.length - 1 ? 'border-b border-[#F5F7FA]' : ''"
                        @click="openSetting(item.id)">
                        <text class="text-[30rpx] font-medium text-[#333333] shrink-0">{{ item.title }}</text>
                        <view class="flex items-center gap-[12rpx]">
                            <text
                                class="text-[26rpx] line-clamp-1 break-all"
                                :class="isConfigured(item.id) ? 'text-primary' : 'text-[#999999]'"
                                >{{ getSummary(item.id) }}</text
                            >
                            <u-icon name="arrow-right" color="#C0C4CC" size="24"></u-icon>
                        </view>
                    </view>
                </view>
            </view>

            <view class="mb-[24rpx]">
                <text class="block text-[26rpx] font-medium text-[#999999] mb-[16rpx] px-[16rpx]">截流转化</text>
                <view class="bg-white rounded-[24rpx] px-[32rpx] shadow-sm">
                    <view
                        v-for="(item, index) in shutoffConfigList"
                        :key="item.id"
                        class="flex items-center justify-between gap-x-1 py-[32rpx] active:opacity-70 transition-opacity"
                        :class="index !== shutoffConfigList.length - 1 ? 'border-b border-[#F5F7FA]' : ''"
                        @click="openSetting(item.id)">
                        <text class="text-[30rpx] font-medium text-[#333333] shrink-0">{{ item.title }}</text>
                        <view class="flex items-center gap-[12rpx]">
                            <text
                                class="text-[26rpx] line-clamp-1 break-all"
                                :class="isConfigured(item.id) ? 'text-primary' : 'text-[#999999]'"
                                >{{ getSummary(item.id) }}</text
                            >
                            <u-icon name="arrow-right" color="#C0C4CC" size="24"></u-icon>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-2 z-50">
            <u-button
                type="primary"
                shape="circle"
                :ripple="true"
                :custom-style="{
                    height: '96rpx',
                    fontSize: '30rpx',
                    fontWeight: '900',
                    border: 'none',
                    boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                }"
                @click="handleSaveConfig">
                保存配置
            </u-button>
            <view class="text-center mt-2.5">
                <text class="text-[22rpx] text-[#b4b4b4]">配置自动同步至关联设备</text>
            </view>
        </view>

        <popup-bottom v-model="showSettingPopup" custom-class="bg-[#F7F8FA]" :title="activeSettingTitle">
            <template #content>
                <view class="flex flex-col h-full">
                    <view class="grow min-h-0">
                        <scroll-view
                            scroll-y
                            class="h-full"
                            :scroll-top="scrollTop"
                            scroll-with-animation
                            @scroll="onScroll">
                            <view class="p-[24rpx] pb-[100rpx]">
                                <view
                                    v-if="activeSettingKey === 'moments_interact'"
                                    class="bg-white rounded-[24rpx] p-[24rpx] mb-[24rpx] shadow-sm">
                                    <text class="block text-[26rpx] font-medium text-[#333333] mb-[20rpx]"
                                        >执行动作</text
                                    >
                                    <view class="flex bg-[#F4F5F7] rounded-[16rpx] p-[6rpx] gap-[6rpx]">
                                        <view
                                            v-for="tab in momentsTabs"
                                            :key="tab.value"
                                            class="flex-1 py-[16rpx] text-center text-[26rpx] font-medium rounded-[12rpx] transition-all"
                                            :class="
                                                activeItem?.momentsAction == tab.value
                                                    ? 'bg-white text-primary shadow-sm'
                                                    : 'text-[#666666]'
                                            "
                                            @click="activeItem.momentsAction = tab.value">
                                            <text>{{ tab.label }}</text>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    v-if="!isShutoffItem(activeSettingKey) && !isLikeOnlyMode"
                                    class="bg-white rounded-[24rpx] p-[24rpx] mb-[24rpx] shadow-sm">
                                    <text class="block text-[26rpx] font-medium text-[#333333] mb-[20rpx]"
                                        >回复方式</text
                                    >
                                    <view class="flex bg-[#F4F5F7] rounded-[16rpx] p-[6rpx] gap-[6rpx]">
                                        <view
                                            v-for="tab in currentTabs"
                                            :key="tab.value"
                                            class="flex-1 py-[16rpx] text-center text-[26rpx] font-medium rounded-[12rpx] transition-all"
                                            :class="
                                                activeItem?.replyMode == tab.value
                                                    ? 'bg-white text-primary shadow-sm'
                                                    : 'text-[#666666]'
                                            "
                                            @click="
                                                activeItem.replyMode = tab.value;
                                                scriptError = false;
                                            ">
                                            <text>{{ tab.label }}</text>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    v-if="activeItem?.replyMode == CommentReplyModeEnum.AI && !isLikeOnlyMode"
                                    class="bg-white rounded-[24rpx] p-[24rpx] mb-[24rpx] shadow-sm">
                                    <view
                                        class="flex items-center justify-between bg-[#F4F5F7] rounded-[16rpx] px-[24rpx] py-[32rpx] active:bg-[#EBECEF] transition-colors"
                                        @click="openChooseAgent">
                                        <text class="text-[28rpx] text-[#333333]">关联智能体</text>
                                        <view class="flex items-center gap-[8rpx]">
                                            <text
                                                class="text-[28rpx] font-medium"
                                                :class="activeItem?.agentId ? 'text-primary' : 'text-[#999999]'">
                                                {{ activeItem?.agentId ? activeItem?.agentName : "请选择" }}
                                            </text>
                                            <u-icon name="arrow-right" color="#C0C4CC" size="28"></u-icon>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    v-if="activeItem?.replyMode == CommentReplyModeEnum.FIXED && !isLikeOnlyMode"
                                    class="bg-white rounded-[24rpx] p-[24rpx] mb-[24rpx] shadow-sm">
                                    <text class="block text-[26rpx] font-medium text-[#333333] mb-[20rpx]"
                                        >话术列表</text
                                    >

                                    <view class="flex flex-col gap-[16rpx] mb-[24rpx]">
                                        <view
                                            v-if="!activeItem?.fixedScripts.length"
                                            class="py-[32rpx] text-center bg-[#F4F5F7] rounded-[16rpx]">
                                            <text class="text-[26rpx] text-[#999999]">暂无话术，请在下方添加</text>
                                        </view>

                                        <view
                                            v-for="(s, i) in activeItem?.fixedScripts"
                                            :key="scriptKeys[i]"
                                            class="flex items-center gap-[16rpx] script-item">
                                            <input
                                                v-model="activeItem.fixedScripts[i]"
                                                class="flex-1 h-[80rpx] rounded-[16rpx] px-[24rpx] text-[28rpx] text-[#333333] bg-[#F4F5F7] border border-[transparent] focus:border-primary focus:bg-[#F0F5FF] transition-all"
                                                placeholder="请输入话术内容" />
                                            <view
                                                class="w-[80rpx] h-[80rpx] flex items-center justify-center bg-[#FFF1F0] rounded-[16rpx] shrink-0 active:opacity-70 transition-opacity"
                                                @click="removeScript(i)">
                                                <u-icon name="trash" color="#FF4D4F" size="36"></u-icon>
                                            </view>
                                        </view>
                                    </view>

                                    <view class="flex items-center gap-[16rpx] pt-[24rpx] border-t border-[#F5F7FA]">
                                        <input
                                            v-model="popupScriptInput"
                                            class="flex-1 h-[80rpx] rounded-[16rpx] px-[24rpx] text-[28rpx] text-[#333333] transition-all border"
                                            :class="
                                                scriptError
                                                    ? 'bg-[#FFF1F0] border-[#FF4D4F]'
                                                    : 'bg-[#F4F5F7] border-[transparent] focus:border-primary focus:bg-[#F0F5FF]'
                                            "
                                            placeholder="输入新话术内容..."
                                            @confirm="handleAddScript" />
                                        <view
                                            class="w-[80rpx] h-[80rpx] rounded-[16rpx] bg-primary flex items-center justify-center shrink-0 active:opacity-80 transition-opacity shadow-sm"
                                            @click="handleAddScript">
                                            <u-icon name="plus" color="#FFFFFF" size="36"></u-icon>
                                        </view>
                                    </view>
                                    <view v-if="scriptError" class="mt-[12rpx] text-[24rpx] text-[#FF4D4F] pl-[8rpx]"
                                        >请至少保留或添加一条话术</view
                                    >
                                </view>

                                <view
                                    v-if="activeItem?.replyMode == CommentReplyModeEnum.LIKE_REPLY || isLikeOnlyMode"
                                    class="bg-white rounded-[24rpx] p-[32rpx] flex items-center justify-center shadow-sm">
                                    <text class="text-[26rpx] text-[#999999]"
                                        >当前模式下，系统仅执行点赞操作，不发表评论</text
                                    >
                                </view>
                            </view>
                        </scroll-view>
                    </view>

                    <view class="p-[24rpx] pb-2 bg-white border-[0] border-t border-solid border-[#F0F0F0] shrink-0">
                        <u-button
                            type="primary"
                            shape="circle"
                            :custom-style="{
                                height: '88rpx',
                                fontSize: '30rpx',
                                fontWeight: '900',
                                border: 'none',
                            }"
                            @click="handlePopupConfirm">
                            完成设置
                        </u-button>
                    </view>
                </view>
            </template>
        </popup-bottom>

        <choose-agent v-model="showChooseAgent" ref="chooseAgentRef" @confirm="handleAgentConfirm" />
    </view>
</template>

<script setup lang="ts">
import { getPersonDetail, getAgentDetail, updateAgent } from "@/api/person";
import ChooseAgent from "@/ai_modules/person/components/choose-agent/choose-agent.vue";

enum CommentReplyModeEnum {
    AI = 1,
    FIXED = 2,
    LIKE_REPLY = 3,
}
enum MomentsActionEnum {
    LIKE_ONLY = 1,
    COMMENT_ONLY = 2,
    BOTH = 3,
}

const commentTabs = [
    { label: "AI 智能", value: CommentReplyModeEnum.AI },
    { label: "固定话术", value: CommentReplyModeEnum.FIXED },
    { label: "仅点赞", value: CommentReplyModeEnum.LIKE_REPLY },
];
const dmTabs = [
    { label: "AI 智能", value: CommentReplyModeEnum.AI },
    { label: "固定话术", value: CommentReplyModeEnum.FIXED },
];
const momentsTabs = [
    { label: "仅点赞", value: MomentsActionEnum.LIKE_ONLY },
    { label: "仅评论", value: MomentsActionEnum.COMMENT_ONLY },
    { label: "评论+点赞", value: MomentsActionEnum.BOTH },
];

const SHUTOFF_IDS = new Set(["shutoff_comment", "shutoff_msg"]);
const isShutoffItem = (id: string): boolean => SHUTOFF_IDS.has(id);

interface PersonDetail {
    id: string;
    persona_name: string;
    [key: string]: any;
}
interface ConfigItem {
    id: string;
    title: string;
    agentIdField: string;
    agentNameField: string;
    replyModeField: string;
    fixedScriptsField: string;
    momentsActionField?: string;
    agentId: string;
    agentName: string;
    replyMode: CommentReplyModeEnum;
    fixedScripts: string[];
    momentsAction?: MomentsActionEnum;
}

const personId = ref<string>("");
const loading = ref<boolean>(true);
const detail = ref<PersonDetail>({ id: "", persona_name: "" });
const agentDetail = ref<any>({});

// --- 数据状态 ---
const commentConfig = ref<ConfigItem>({
    id: "comment",
    title: "评论区接管",
    agentIdField: "comment_agent_id",
    agentNameField: "comment_agent_name",
    replyModeField: "comment_type",
    fixedScriptsField: "comment_speech",
    agentId: "",
    agentName: "",
    replyMode: CommentReplyModeEnum.AI,
    fixedScripts: [],
});

const dmConfigList = ref<ConfigItem[]>([
    {
        id: "social_dm",
        title: "社媒私信接管",
        agentIdField: "dm_agent_id",
        agentNameField: "dm_agent_name",
        replyModeField: "dm_type",
        fixedScriptsField: "dm_speech",
        agentId: "",
        agentName: "",
        replyMode: CommentReplyModeEnum.AI,
        fixedScripts: [],
    },
    {
        id: "wechat_1v1",
        title: "微信私信接管",
        agentIdField: "wechat_chat_agent_id",
        agentNameField: "wechat_chat_agent_name",
        replyModeField: "wechat_chat_type",
        fixedScriptsField: "wechat_chat_speech",
        agentId: "",
        agentName: "",
        replyMode: CommentReplyModeEnum.AI,
        fixedScripts: [],
    },
]);

const momentsItem = ref<ConfigItem>({
    id: "moments_interact",
    title: "朋友圈互动接管",
    agentIdField: "moments_agent_id",
    agentNameField: "moments_agent_name",
    replyModeField: "moments_type",
    fixedScriptsField: "moments_speech",
    momentsActionField: "moments_action",
    agentId: "",
    agentName: "",
    replyMode: CommentReplyModeEnum.AI,
    fixedScripts: [],
    momentsAction: MomentsActionEnum.BOTH,
});

const shutoffConfigList = ref<ConfigItem[]>([
    {
        id: "shutoff_comment",
        title: "截流评论",
        agentIdField: "shutoff_comment_agent_id",
        agentNameField: "shutoff_commnet_agent_name",
        replyModeField: "shutoff_comment_type",
        fixedScriptsField: "shutoff_comment_speech",
        agentId: "",
        agentName: "",
        replyMode: CommentReplyModeEnum.FIXED,
        fixedScripts: [],
    },
    {
        id: "shutoff_msg",
        title: "截流私信",
        agentIdField: "shutoff_msg_agent_id",
        agentNameField: "shuoff_msg_agent_name",
        replyModeField: "shutoff_msg_type",
        fixedScriptsField: "shutoff_msg_speech",
        agentId: "",
        agentName: "",
        replyMode: CommentReplyModeEnum.FIXED,
        fixedScripts: [],
    },
]);

const allConfigList = computed(() => [
    commentConfig.value,
    ...dmConfigList.value,
    momentsItem.value,
    ...shutoffConfigList.value,
]);

// --- 弹窗交互状态 ---
const showSettingPopup = ref(false);
const activeSettingKey = ref("");
const popupScriptInput = ref("");
const scriptError = ref(false);

const activeItem = ref<ConfigItem>({} as ConfigItem);
const activeSettingTitle = computed(() => activeItem.value?.title || "");

const currentTabs = computed(() => {
    if (activeSettingKey.value === "comment") return commentTabs;
    return dmTabs;
});

const isLikeOnlyMode = computed(() => {
    if (activeSettingKey.value === "moments_interact")
        return activeItem.value?.momentsAction === MomentsActionEnum.LIKE_ONLY;
    return false;
});

const scriptKeys = ref<number[]>([]);
let keyCounter = 0;

const scrollTop = ref(0);
const _currentScrollTop = ref(0);

const onScroll = (e: any) => {
    _currentScrollTop.value = e.detail.scrollTop;
};

const scrollToBottom = async () => {
    await nextTick();
    scrollTop.value = _currentScrollTop.value;
    await nextTick();
    scrollTop.value = 99999;
};

// --- 摘要与高亮逻辑 ---
const isConfigured = (id: string): boolean => {
    const item = allConfigList.value.find((c) => c.id === id);
    if (!item) return false;
    if (id === "moments_interact" && item.momentsAction === MomentsActionEnum.LIKE_ONLY) return true;
    if (item.replyMode == CommentReplyModeEnum.AI) return !!item.agentId;
    if (item.replyMode == CommentReplyModeEnum.FIXED) return item.fixedScripts.length > 0;
    return true;
};

const getSummary = (id: string): string => {
    const item = allConfigList.value.find((c) => c.id === id);
    if (!item) return "";
    if (id === "moments_interact" && item.momentsAction === MomentsActionEnum.LIKE_ONLY) return "仅点赞";
    if (item.replyMode == CommentReplyModeEnum.LIKE_REPLY) return "仅点赞";
    if (item.replyMode == CommentReplyModeEnum.AI) return item.agentName ? `AI · ${item.agentName}` : "未关联智能体";
    return item.fixedScripts.length ? `固定话术 · ${item.fixedScripts.length}条` : "未添加话术";
};

// --- 操作逻辑 ---
const openSetting = (id: string) => {
    activeSettingKey.value = id;
    popupScriptInput.value = "";
    scriptError.value = false;
    showSettingPopup.value = true;
    activeItem.value = allConfigList.value.find((c) => c.id === id) || ({} as ConfigItem);

    scriptKeys.value = activeItem.value.fixedScripts.map(() => keyCounter++);
    scrollTop.value = 0;
    _currentScrollTop.value = 0;
};

const removeScript = (idx: number) => {
    if (activeItem.value) {
        activeItem.value.fixedScripts.splice(idx, 1);
        scriptKeys.value.splice(idx, 1);
    }
};

const handleAddScript = async () => {
    const val = popupScriptInput.value.trim();
    if (!val || !activeItem.value) return;
    activeItem.value.fixedScripts.push(val);
    scriptKeys.value.push(keyCounter++);
    popupScriptInput.value = "";
    scriptError.value = false;
    await scrollToBottom();
};

const handlePopupClose = () => {
    if (activeItem.value) {
        activeItem.value.fixedScripts = activeItem.value.fixedScripts.filter((s) => s.trim() !== "");
        // 同步清理 keys
        scriptKeys.value = activeItem.value.fixedScripts.map(() => keyCounter++);
    }
};

const handlePopupConfirm = () => {
    if (popupScriptInput.value.trim() && activeItem.value) {
        activeItem.value.fixedScripts.push(popupScriptInput.value.trim());
        scriptKeys.value.push(keyCounter++);
        popupScriptInput.value = "";
    }
    handlePopupClose();
    showSettingPopup.value = false;
};

// --- 智能体选择 ---
const chooseAgentRef = shallowRef<InstanceType<typeof ChooseAgent>>();
const showChooseAgent = ref(false);

const openChooseAgent = () => {
    showChooseAgent.value = true;
    chooseAgentRef.value?.setChooseLists([{ id: activeItem.value?.agentId ?? "" }]);
};

const handleAgentConfirm = (agent: any) => {
    if (activeItem.value) {
        activeItem.value.agentId = agent.id;
        activeItem.value.agentName = agent.name;
    }
};

// --- 保存提交 ---
const validateAll = (): boolean => {
    for (const item of allConfigList.value) {
        const isLikeOnly = item.id == "moments_interact" && item.momentsAction == MomentsActionEnum.LIKE_ONLY;
        if (isLikeOnly || item.replyMode == CommentReplyModeEnum.LIKE_REPLY) continue;

        if (!isShutoffItem(item.id) && item.replyMode == CommentReplyModeEnum.AI && Number(item.agentId) <= 0) {
            uni.showToast({
                title: `【${item.title}】请选择关联智能体`,
                icon: "none",
                duration: 2000,
            });
            openSetting(item.id);
            return false;
        }
        if (item.replyMode == CommentReplyModeEnum.FIXED && !item.fixedScripts.length) {
            scriptError.value = true;
            uni.showToast({
                title: `【${item.title}】请至少添加一条话术`,
                icon: "none",
                duration: 2000,
            });
            openSetting(item.id);
            return false;
        }
    }
    return true;
};

const handleSaveConfig = async () => {
    allConfigList.value.forEach((item) => {
        item.fixedScripts = item.fixedScripts.filter((s) => s.trim() !== "");
    });
    if (!validateAll()) return;

    try {
        uni.showLoading({ title: "保存中...", mask: true });

        const params: Record<string, any> = { id: agentDetail.value.id, persona_id: personId.value };

        allConfigList.value.forEach((item) => {
            params[item.agentIdField] = item.agentId;
            params[item.replyModeField] = item.replyMode;
            params[item.fixedScriptsField] = item.fixedScripts;
            if (item.momentsActionField) params[item.momentsActionField] = item.momentsAction;
        });

        await updateAgent(params);
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        setTimeout(() => uni.navigateBack(), 1500);
    } catch {
        uni.showToast({ title: "保存失败，请重试", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

// --- 初始化 ---
const normalizeScripts = (raw: any): string[] => (Array.isArray(raw) ? raw : raw ? [String(raw)] : []);

const getDetail = async () => {
    try {
        const [personRes, agentRes] = await Promise.all([
            getPersonDetail({ id: personId.value }),
            getAgentDetail({ persona_id: personId.value }),
        ]);
        detail.value = personRes;
        agentDetail.value = agentRes;

        allConfigList.value.forEach((item) => {
            item.agentId = String(agentRes[item.agentIdField] ?? "");
            item.agentName = agentRes[item.agentNameField] ?? "";
            item.fixedScripts = normalizeScripts(agentRes[item.fixedScriptsField]);

            if (isShutoffItem(item.id)) {
                item.replyMode = CommentReplyModeEnum.FIXED;
            } else {
                item.replyMode = agentRes[item.replyModeField] ?? CommentReplyModeEnum.AI;
            }
            if (item.id === "moments_interact") {
                item.momentsAction = agentRes[item.momentsActionField!] ?? MomentsActionEnum.BOTH;
            }
        });
    } finally {
        loading.value = false;
    }
};

onLoad((options: any) => {
    personId.value = options.id ?? "";
    getDetail();
});
</script>

<style scoped>
.script-item {
    animation: slideInFromLeft 0.28s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
}

@keyframes slideInFromLeft {
    from {
        opacity: 0;
        transform: translateX(-40px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>
