<template>
    <view class="h-screen flex flex-col page-bg">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :is-back="chatContentList.length > 0"
            :background="{
                background: 'transparent',
            }"
            is-custom-back-icon
            :custom-back="backChat">
            <template #custom-back-icon>
                <u-icon name="arrow-left" :size="32"></u-icon>
            </template>
            <view
                class="font-bold text-xl"
                :class="{
                    'mx-4': chatContentList.length == 0,
                }">
                智能聊天
            </view>
        </u-navbar>
        <view class="grow min-h-0 relative z-10">
            <chat-scroll-view
                ref="chattingRef"
                v-model:file-list="fileList"
                :is-stop="isStopChat"
                :content-list="chatContentList"
                :send-disabled="isReceiving"
                :tokens="tokensValue"
                @close="handleChatClose"
                @add-session="handleAddSession"
                @update:network="handleUpdateNetwork"
                @content-post="handleContentPost">
                <template #content>
                    <view
                        v-if="chatContentList.length == 0"
                        class="mb-[64rpx] flex flex-col items-center justify-center">
                        <view>
                            <image :src="websiteConfig.shop_logo" class="w-[128rpx] h-[128rpx] rounded-full"></image>
                        </view>
                        <view class="text-[40rpx] font-bold text-center mt-[48rpx]"> 有什么可以帮忙的？ </view>
                    </view>
                </template>
            </chat-scroll-view>
            <view class="w-full flex justify-center mb-2 absolute bottom-0" v-if="chatContentList.length == 0">
                <view
                    class="flex items-center justify-center rounded-full px-[16rpx] h-[76rpx] w-[228rpx] border border-solid border-[#EDEDEE]"
                    @click="showHistory = true">
                    <text class="text-xs text-[#989898]">历史记录</text>
                </view>
            </view>
        </view>

        <tabbar v-if="chatContentList.length === 0" />
    </view>
    <popup-bottom
        v-model:show="showHistory"
        title="历史记录"
        show-close-btn
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
                            <view class="line-clamp-3 mt-4 text-[26rpx]">
                                {{ item.message || item.file_info.name }}
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
import { TokensSceneEnum } from "@/enums/appEnums";

// 类型定义
interface ChatMessage {
    type: 1 | 2; // 1: 用户消息, 2: AI回复
    message?: string;
    fileList?: FileInfo[];
    loading?: boolean;
    reply?: string;
    reasoning_content?: string;
    consume_tokens?: Record<string, any>;
    is_reasoning_finished?: boolean;
    tokens_info?: Record<string, any>;
    file_info?: Record<string, any>;
}

interface FileInfo {
    url: string;
    name: string;
    size: number;
    type: string;
}

interface ChatConfig {
    id: string | number;
    avatar: string;
    name: string;
}

interface ChatLogParams {
    page_no: number;
    page_size: number;
    assistant_id: number;
    task_id?: string;
}

// 状态管理
const appStore = useAppStore();
const userStore = useUserStore();
const { chatConfig } = toRefs(appStore);
const websiteConfig = computed(() => appStore.getWebsiteConfig);
const { userTokens, isLogin } = toRefs(userStore);
const tokensValue = userStore.getTokenByScene(TokensSceneEnum.CHAT)?.score;

// 组件引用
const rechargePopupRef = ref();
const chattingRef = shallowRef();
const pagingRef = shallowRef();

// 页面状态
const safeAreaTop = ref<number>(50);
const isNetwork = ref(false);
const showHistory = ref(false);
const isReceiving = ref(false);
const isStopChat = ref(false);
const fileList = ref<FileInfo[]>([]);
const chatContentList = ref<ChatMessage[]>([]);
const taskId = ref<string>("");
const recordLists = ref<any[]>([]);

// 流式请求读取器
let streamReader: any = null;

// 聊天记录请求参数
const chatLogParams = reactive<ChatLogParams>({
    page_no: 1,
    page_size: 1500,
    assistant_id: 0,
});

/**
 * 网络状态更新处理
 */
const handleUpdateNetwork = (value: boolean) => {
    isNetwork.value = value;
};

/**
 * 历史记录选择处理
 */
const handleSelectRecord = async (item: any) => {
    const { robot_id, avatar, robot_name, task_id } = item;
    chattingRef.value?.setAgentConfig({
        id: robot_id,
        avatar,
        name: robot_name,
    });
    taskId.value = task_id;
    await getChatList();
    showHistory.value = false;
};

/**
 * 查询历史记录列表
 */
const handleQueryRecordList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getCreativeRecord({
            page_no,
            page_size,
            scene_id: 0,
            type: 1,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

/**
 * 获取聊天记录
 */
const getChatList = async () => {
    try {
        const data = await getChatLog({
            ...chatLogParams,
            task_id: taskId.value,
        });
        const transformData = data?.map((item: ChatMessage) => {
            if (item.type === 1)
                return {
                    ...item,
                    fileList: item?.file_info
                        ? Array.isArray(item.file_info)
                            ? item.file_info
                            : [item.file_info]
                        : [],
                };
            return {
                ...item,
                is_reasoning_finished: true,
                consume_tokens: item.tokens_info,
            };
        });

        chatContentList.value = transformData;
        console.log("chatContentList", chatContentList.value);

        await nextTick();
        chattingRef.value.scrollToBottom();
    } catch (err) {
        console.error("获取聊天记录失败:", err);
    }
};

/**
 * 发送消息处理
 */
const handleContentPost = async (userInput?: string, isNewChat: boolean = false) => {
    if (userTokens.value <= 1) {
        uni.$u.toast("算力不足，请充值！");
        rechargePopupRef.value?.open();
        return;
    }
    if (isReceiving.value) return;

    // 添加用户消息
    if (!isNewChat) {
        chatContentList.value.push({
            type: 1,
            message: userInput,
            fileList: fileList.value,
        });
    }

    // 准备AI回复消息
    const result = reactive<ChatMessage>({
        type: 2,
        loading: true,
        reply: "",
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
                ...(chattingRef.value?.getChatConfig?.() || {}),
            },
            {
                onstart(reader) {
                    streamReader = reader;
                    isStopChat.value = true;
                },
                onmessage(value) {
                    handleStreamMessage(value, result);
                },
                onclose() {
                    handleStreamClose(result);
                },
            }
        );
    } catch (error: any) {
        handleStreamError(error, result);
    }

    nextTick(() => chattingRef.value.scrollToBottom());
};

/**
 * 处理流式消息
 */
const handleStreamMessage = (value: string, result: ChatMessage) => {
    value
        .trim()
        .split("data:")
        .forEach((text) => {
            if (!text) return;
            try {
                const { object, content, task_id, usage, reasoning_content } = JSON.parse(text);
                if ((content || reasoning_content) && object === "loading") {
                    if (reasoning_content) {
                        result.reasoning_content += reasoning_content;
                    } else {
                        result.reply += content;
                    }
                }
                if (object === "finished") {
                    result.loading = false;
                    result.consume_tokens = usage;
                    if (!taskId.value) {
                        taskId.value = task_id;
                    }
                    return;
                }
                chattingRef.value.scrollToBottom();
            } catch (error) {
                console.error("解析流式消息失败:", error);
            }
        });
};

/**
 * 处理流式请求关闭
 */
const handleStreamClose = (result: ChatMessage) => {
    result.loading = false;
    resetChat();
    userStore.getUser();
    setTimeout(() => chattingRef.value.scrollToBottom(), 600);
};

/**
 * 处理流式请求错误
 */
const handleStreamError = (error: any, result: ChatMessage) => {
    result.reply = error.errno === 600004 ? "用户已停止内容生成" : error || "发生错误";
    if (error.errno !== 600004) {
        uni.$u.toast(error);
    }
    result.loading = false;
    resetChat();
};

/**
 * 添加新会话
 */
const handleAddSession = () => {
    if (!taskId.value) {
        uni.$u.toast("当前会话已经是最新的了");
        return;
    }
    taskId.value = "";
    chatContentList.value = [];
    resetChat();
    handleChatClose();
    handleContentPost(chatConfig.value.new_chat_prompt, true);
};

/**
 * 重置聊天状态
 */
const resetChat = () => {
    fileList.value = [];
    isReceiving.value = false;
    isStopChat.value = false;
};

/**
 * 返回聊天
 */
const backChat = () => {
    chatContentList.value = [];
    resetChat();
    handleChatClose();
};

/**
 * 关闭聊天
 */
const handleChatClose = () => {
    //#ifdef H5
    streamReader?.cancel();
    //#endif
    //#ifdef MP-WEIXIN
    streamReader?.abort();
    //#endif
    isReceiving.value = false;
    isStopChat.value = false;
};

/**
 * 监听文件选择
 */
const watchFile = () => {
    uni.$on("chooseFile", (data: FileInfo[]) => {
        fileList.value = data;
    });
};

/**
 * 初始化
 */
const init = async (options?: Record<string, any>) => {
    await nextTick();
    if (options?.agent_name) {
        chattingRef.value?.setAgentConfig({
            name: options.agent_name,
            id: options.agent_id,
            avatar: options.agent_logo,
        });
    }
};

// 生命周期钩子
onMounted(() => {
    const { safeArea } = uni.$u.sys();
    safeAreaTop.value = safeArea.top;
});

onLoad((options?: Record<string, any>) => {
    if (options?.task_id) {
        taskId.value = options.task_id;
        getChatList();
    }
    init(options);
    watchFile();
});

onUnload(() => {
    uni.$off("chooseFile");
});

onShow(() => {
    appStore.getChatConfig();
});
</script>

<style lang="scss" scoped></style>
