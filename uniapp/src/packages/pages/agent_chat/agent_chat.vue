<template>
    <view class="h-full flex flex-col bg-[#EEF0F6]">
        <u-navbar
            v-if="!isAccessDenied"
            :border-bottom="false"
            :is-fixed="false"
            :is-back="true"
            :background="{ background: 'transparent' }"
            is-custom-back-icon
            :custom-back="handleBack">
            <template #custom-back-icon>
                <view class="flex items-center gap-x-4 leading-[0]">
                    <view>
                        <u-icon name="arrow-left" :size="30"></u-icon>
                    </view>
                    <view v-if="!isShowWelcome" @click.stop="handleAddSession">
                        <image src="/static/images/icons/chat_new.svg" class="w-[28rpx] h-[28rpx]" />
                    </view>
                </view>
            </template>
            <view class="w-full flex items-center justify-center px-4">
                <view class="flex flex-col items-center" v-if="agentInfo">
                    <view class="agent-title-row">
                        <text class="agent-title-text">{{ agentInfo.name }}</text>
                        <text
                            v-if="shouldShowAgentAccessTag(agentInfo)"
                            class="agent-access-tag"
                            :class="getAgentAccessTagClass(agentInfo)">
                            {{ getAgentAccessTagText(agentInfo) }}
                        </text>
                    </view>
                </view>
            </view>
        </u-navbar>

        <view v-if="isAccessDenied" class="grow flex flex-col items-center justify-center px-[60rpx]">
            <view class="flex flex-col items-center">
                <text class="text-[34rpx] font-bold text-[#1E3A5F] mb-[20rpx] text-center">无法访问该智能体</text>
                <text class="text-[26rpx] text-[#94A3B8] text-center leading-[1.7] mb-[56rpx]"
                    >该智能体为用户私有，暂不支持通过分享链接访问。</text
                >
                <view
                    class="w-full h-[88rpx] rounded-[44rpx] text-white text-[30rpx] font-bold flex items-center justify-center bg-primary"
                    @click="handleBack"
                    >返回上一页</view
                >
            </view>
        </view>

        <view v-else class="grow min-h-0 pt-2">
            <chat-scroll-view
                ref="chattingRef"
                v-model:file-list="fileList"
                :placeholder="`发送消息给 ${agentInfo?.name || 'AI'}`"
                :is-stop="isStopChat"
                :content-list="chatContentList"
                :send-disabled="isReceiving"
                :tokens="tokensValue"
                :is-home="false"
                :is-agent="true"
                @close="handleChatClose"
                @add-session="handleAddSession"
                @update:network="handleUpdateNetwork"
                @content-post="handleContentPost"
                @show-history="showHistory = true">
                <template #content v-if="chatContentList.length === 0">
                    <view class="h-full flex flex-col items-center justify-center">
                        <view class="w-[120rpx] h-[120rpx] rounded-full overflow-hidden">
                            <image :src="agentInfo?.image" class="w-full h-full" mode="aspectFill"></image>
                        </view>
                        <view class="text-[20rpx] text-[#94A3B8] mt-[24rpx]">{{ agentInfo?.intro }}</view>
                    </view>
                </template>
            </chat-scroll-view>
        </view>
    </view>

    <popup-bottom
        v-if="showHistory"
        v-model="showHistory"
        title="历史记录"
        :is-disabled-touch="true"
        custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full py-[30rpx]">
                <z-paging
                    ref="pagingRef"
                    v-model="recordLists"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="handleQueryRecordList">
                    <view class="flex flex-col gap-4 px-[32rpx]">
                        <view
                            class="bg-white rounded-[24rpx] p-[24rpx]"
                            v-for="(item, index) in recordLists"
                            :key="index"
                            @click="handleSelectRecord(item)">
                            <view class="flex items-center justify-between">
                                <view class="text-[#AEAFB0] text-xs bg-[#F9FAFB] rounded-[12rpx] py-[4rpx] px-[8rpx]">
                                    {{ item.create_time }}
                                </view>
                            </view>
                            <view class="line-clamp-3 mt-4">
                                {{ item.message || item.file_info?.name }}
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </template>
    </popup-bottom>

    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script lang="ts" setup>
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { chatSendTextStream, getChatLog, getCreativeRecord } from "@/api/chat";
import { getAgentDetail as getAgentDetailApi } from "@/api/agent";
import { TokensSceneEnum } from "@/enums/appEnums";
import { RequestCodeEnum } from "@/enums/requestEnums";
import { parseChatStreamErrorPayload, resolveChatErrorMessage } from "@/utils/chatStream";
import {
    AGENT_UNAVAILABLE_TIP,
    canUseAgent,
    getAgentAccessStatus,
    getAgentAccessTagText as getAgentPermissionTagText,
    shouldShowAgentAccessTag,
} from "@/utils/agentPermission";

// ─────────────────────────────────────────────
// 类型定义
// ─────────────────────────────────────────────
interface FileInfo {
    url: string;
    name: string;
    size: number;
    type: string;
}

interface ChatMessage {
    type: 1 | 2;
    message?: string;
    fileList?: FileInfo[];
    loading?: boolean;
    reply?: string;
    error?: string;
    reasoning_content?: string;
    consume_tokens?: Record<string, any>;
    is_reasoning_finished?: boolean;
    tokens_info?: Record<string, any>;
    file_info?: Record<string, any>;
    is_welcome?: boolean;
}

// ─────────────────────────────────────────────
// Store
// ─────────────────────────────────────────────
const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens, userInfo, isLogin } = toRefs(userStore);
const tokensValue = userStore.getTokenByScene(TokensSceneEnum.CHAT)?.score;

// ─────────────────────────────────────────────
// 组件 Refs
// ─────────────────────────────────────────────
const rechargePopupRef = ref();
const chattingRef = shallowRef();
const pagingRef = shallowRef();

// ─────────────────────────────────────────────
// 页面状态
// ─────────────────────────────────────────────
const agentInfo = ref<any>(null);
const isFromShare = ref(false);
const isAccessDenied = ref(false);
const isNetwork = ref(false);
const showHistory = ref(false);
const isReceiving = ref(false);
const isStopChat = ref(false);
const fileList = ref<FileInfo[]>([]);
const chatContentList = ref<ChatMessage[]>([]);
const taskId = ref("");
const recordLists = ref<any[]>([]);

let streamReader: any = null;

// ─────────────────────────────────────────────
// 计算属性
// ─────────────────────────────────────────────
const isShowWelcome = computed(
    () => chatContentList.value.length > 0 && chatContentList.value.some((item) => item.is_welcome),
);

const canUseCurrentAgent = computed(() => canUseAgent(agentInfo.value, userInfo.value));

const ensureAgentAvailable = () => {
    if (canUseCurrentAgent.value) return true;
    uni.$u.toast(AGENT_UNAVAILABLE_TIP);
    return false;
};

const getAgentAccessTagText = (agent: any) => getAgentPermissionTagText(agent, userInfo.value);

const getAgentAccessTagClass = (agent: any) =>
    getAgentAccessStatus(agent, userInfo.value) === "free" ? "agent-access-tag--free" : "agent-access-tag--member";

// ─────────────────────────────────────────────
// 智能体初始化
// ─────────────────────────────────────────────
const initAgentDetail = async (id: number) => {
    try {
        const detail = await getAgentDetailApi({ id });
        agentInfo.value = detail;
        if (isFromShare.value && detail.user_id && detail.user_id !== userInfo.value?.id) {
            isAccessDenied.value = true;
            return;
        }

        insertWelcomeMessage(detail);
    } catch (e) {
        console.error("获取智能体详情失败:", e);
    }
};

/** 插入欢迎语（仅在列表为空时插入） */
const insertWelcomeMessage = (agent: any) => {
    const welcomeText = agent.welcome_introducer;
    if (!welcomeText || chatContentList.value.length > 0) return;

    chatContentList.value.push({
        type: 2,
        loading: false,
        reply: welcomeText,
        error: "",
        reasoning_content: "",
        consume_tokens: {},
        is_reasoning_finished: true,
        is_welcome: true,
    });

    nextTick(() => setTimeout(() => chattingRef.value?.scrollToBottom(), 150));
};

// ─────────────────────────────────────────────
// 聊天记录
// ─────────────────────────────────────────────
const getChatList = async () => {
    try {
        const data = await getChatLog({
            page_no: 1,
            page_size: 1500,
            assistant_id: 0,
            task_id: taskId.value,
        });

        chatContentList.value = data?.map((item: ChatMessage) => {
            if (item.type === 1) {
                return {
                    ...item,
                    fileList: item.file_info ? (Array.isArray(item.file_info) ? item.file_info : [item.file_info]) : [],
                };
            }
            return {
                ...item,
                is_reasoning_finished: true,
                consume_tokens: item.tokens_info,
            };
        });

        await nextTick();
        setTimeout(() => chattingRef.value?.scrollToBottom(), 150);
    } catch (err) {
        console.error("获取聊天记录失败:", err);
    }
};

const handleQueryRecordList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getCreativeRecord({
            page_no,
            page_size,
            chat_type: 9006,
            robot_id: agentInfo.value?.id,
            scene_id: 0,
            type: 1,
        });
        pagingRef.value?.complete(lists);
    } catch {
        pagingRef.value?.complete([]);
    }
};

const handleSelectRecord = async (item: any) => {
    taskId.value = item.task_id;
    await getChatList();
    showHistory.value = false;
};

// ─────────────────────────────────────────────
// 发送消息 & 流式处理
// ─────────────────────────────────────────────

const handleContentPost = async (userInput?: string, isNewChat = false) => {
    if (!isLogin.value) {
        uni.$u.navigateTo({ url: "/pages/login/login" });
        return;
    }
    if (!ensureAgentAvailable()) return;
    if (userTokens.value <= 1) {
        powerInsufficientTip();
        rechargePopupRef.value?.open();
        return;
    }
    if (isReceiving.value) return;

    if (!isNewChat) {
        chatContentList.value.push({ type: 1, message: userInput, fileList: fileList.value });
    }

    const result = reactive<ChatMessage>({
        type: 2,
        loading: true,
        reply: "",
        error: "",
        reasoning_content: "",
        consume_tokens: {},
    });
    chatContentList.value.push(result);
    isReceiving.value = true;

    try {
        await chatSendTextStream(
            {
                message: userInput,
                task_id: taskId.value,
                open_reasoning: 0,
                is_network_search: isNetwork.value ? 1 : 0,
                file_info: fileList.value[0],
                model_id: agentInfo.value?.model_id,
                model_sub_id: agentInfo.value?.model_sub_id,
                robot_id: agentInfo.value?.id,
            },
            {
                onstart: (reader) => {
                    streamReader = reader;
                    isStopChat.value = true;
                },
                onmessage: (value) => onStreamMessage(value, result),
                onclose: () => onStreamClose(result),
            },
        );
    } catch (error: any) {
        onStreamError(error, result);
    }

    nextTick(() => chattingRef.value?.scrollToBottom());
};

const onStreamMessage = (value: string, result: ChatMessage) => {
    value
        .trim()
        .split("data:")
        .forEach((text) => {
            if (!text) return;
            try {
                const data = JSON.parse(text);
                const streamError = parseChatStreamErrorPayload(data);
                if (streamError) {
                    result.error = streamError;
                    result.loading = false;
                    return;
                }
                const { object, content, task_id, usage, reasoning_content } = data;
                if ((content || reasoning_content) && object === "loading") {
                    reasoning_content ? (result.reasoning_content += reasoning_content) : (result.reply += content);
                }
                if (object === "finished") {
                    result.loading = false;
                    result.consume_tokens = usage;
                    if (!taskId.value) taskId.value = task_id;
                }
                chattingRef.value?.scrollToBottom();
            } catch (error) {
                console.error("解析智能体流式消息失败:", error);
            }
        });
};

const onStreamClose = (result: ChatMessage) => {
    result.loading = false;
    resetChatState();
    userStore.getUser();
    setTimeout(() => chattingRef.value?.scrollToBottom(), 600);
};

const onStreamError = (error: any, result: ChatMessage) => {
    const message = resolveChatErrorMessage(error);
    result.error = message;
    result.loading = false;
    if (error?.errno !== RequestCodeEnum.ABORT) uni.$u.toast(message);
    resetChatState();
};

// ─────────────────────────────────────────────
// 会话管理
// ─────────────────────────────────────────────
const handleAddSession = () => {
    resetChatState();
    taskId.value = "";
    chatContentList.value = [];
    handleChatClose();
    if (agentInfo.value) insertWelcomeMessage(agentInfo.value);
};

const handleChatClose = () => {
    // #ifdef H5
    streamReader?.cancel();
    // #endif
    // #ifdef MP-WEIXIN
    streamReader?.abort();
    // #endif
    isReceiving.value = false;
    isStopChat.value = false;
};

const resetChatState = () => {
    fileList.value = [];
    isReceiving.value = false;
    isStopChat.value = false;
};

// ─────────────────────────────────────────────
// 导航
// ─────────────────────────────────────────────
const handleBack = () => {
    if (isFromShare.value) {
        uni.$u.route({ url: "/packages/pages/chat/chat", type: "redirect" });
        return;
    }
    handleChatClose();
    uni.navigateBack();
};

const handleUpdateNetwork = (value: boolean) => {
    isNetwork.value = value;
};

// ─────────────────────────────────────────────
// 生命周期
// ─────────────────────────────────────────────
onLoad(async (options?: Record<string, any>) => {
    uni.$on("chooseFile", (data: FileInfo[]) => (fileList.value = data));

    const id = Number(options?.id);
    if (!id) {
        uni.$u.toast("智能体参数缺失");
        setTimeout(() => uni.navigateBack(), 1500);
        return;
    }
    isFromShare.value = options?.from === "share";
    if (options?.task_id) taskId.value = options.task_id;

    await initAgentDetail(id);

    if (options?.task_id && !isAccessDenied.value) {
        await getChatList();
    }
});

onUnload(() => {
    uni.$off("chooseFile");
    chattingRef.value?.hideKeyboard();
    handleChatClose();
});

onHide(() => chattingRef.value?.hideKeyboard());

onShow(() => appStore.getChatConfig());

onShareAppMessage(() => ({
    title: agentInfo.value?.name || "AI智能体",
    path: `/packages/pages/agent_chat/agent_chat?id=${agentInfo.value?.id}&from=share`,
    imageUrl: agentInfo.value?.logo || "",
}));
</script>

<style lang="scss" scoped>
.agent-title-row {
    @apply flex max-w-full items-center justify-center gap-x-[8rpx];
}

.agent-title-text {
    @apply min-w-0 line-clamp-1 text-[30rpx] font-bold text-[#1E3A5F];
}

.agent-access-tag {
    @apply shrink-0 rounded-full border border-solid px-[12rpx] py-[4rpx] text-[20rpx] font-semibold leading-none;
}

.agent-access-tag--free {
    @apply border-[#BBF7D0] bg-[#F0FDF4] text-[#16A34A];
}

.agent-access-tag--member {
    @apply border-[#DDD6FE] bg-[#F5F3FF] text-[#8B5CF6];
}
</style>
