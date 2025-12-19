<template>
    <view class="h-screen flex flex-col bg-[#EEF0F6]">
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
        </u-navbar>
        <view class="grow min-h-0 relative z-10">
            <chat-scroll-view
                ref="chattingRef"
                v-model:file-list="fileList"
                placeholder="发送消息、输入@选择智能体"
                :is-stop="isStopChat"
                :content-list="chatContentList"
                :send-disabled="isReceiving"
                :tokens="tokensValue"
                @close="handleChatClose"
                @add-session="handleAddSession"
                @update:network="handleUpdateNetwork"
                @content-post="handleContentPost"
                @show-history="showHistory = true">
                <template #content v-if="!isAgent">
                    <view v-if="chatContentList.length == 0" class="h-full w-full pb-4 px-[20rpx]">
                        <view class="flex items-center justify-center">
                            <image :src="websiteConfig.shop_logo" class="w-[128rpx] h-[128rpx] rounded-full"></image>
                        </view>
                        <view class="grid grid-cols-2 gap-4 mt-[40rpx]">
                            <view class="bg-white rounded-[20rpx] px-[38rpx] h-[240rpx] flex flex-col">
                                <view
                                    class="flex items-center justify-center gap-x-2 py-[30rpx] flex-1"
                                    @click="toPage(PageKeyEnum.CREATE_TASK)">
                                    <image src="/static/images/icons/add.svg" class="w-[32rpx] h-[32rpx] -ml-3"></image>
                                    <text class="font-bold text-[30rpx]">创建任务</text>
                                </view>
                                <view class="h-[1rpx] bg-[rgba(0,0,0,0.05)] flex-shrink-0 my-1"></view>
                                <view
                                    class="flex items-center justify-center gap-x-2 py-[30rpx] flex-1"
                                    @click="toPage(PageKeyEnum.VIEW_SCHEDULE)">
                                    <image
                                        src="/static/images/icons/calendar.svg"
                                        class="w-[32rpx] h-[32rpx] -ml-3"></image>
                                    <text class="font-bold text-[30rpx]">查看日程</text>
                                </view>
                            </view>
                            <view
                                class="bg-white rounded-[20rpx] px-[38rpx] py-[30rpx] h-[240rpx] relative overflow-hidden"
                                @click="toPage(PageKeyEnum.DIGITAL_PERSON_CLONE)">
                                <view class="text-[30rpx] font-bold"> 数字人克隆 </view>
                                <view class="flex items-center gap-x-1 mt-1">
                                    <text class="text-[22rpx] text-[#0000004d]">立即定制</text>
                                    <u-icon name="arrow-right" :size="20" color="#0000004d"></u-icon>
                                </view>
                                <view class="absolute right-2 bottom-0 leading-[0]">
                                    <image
                                        :src="`${config.baseUrl}static/images/index_img_15.png`"
                                        class="w-[150rpx] h-[130rpx]"
                                        mode="widthFix"></image>
                                </view>
                            </view>
                        </view>
                        <view class="bg-white rounded-[20rpx] mt-[20rpx] py-2">
                            <view class="text-[30rpx] font-bold px-[34rpx] py-1">快速应用</view>
                            <view class="grid grid-cols-4 gap-4 py-2 mt-1">
                                <view
                                    class="flex flex-col items-center justify-center"
                                    v-for="item in quickApplicationList"
                                    :key="item.key"
                                    @click="toPage(item.key)">
                                    <image
                                        :src="`${config.baseUrl}static/images/${item.icon}`"
                                        class="w-[68rpx] h-[68rpx]"
                                        mode="widthFix"></image>
                                    <view class="mt-1"> {{ item.name }} </view>
                                </view>
                            </view>
                        </view>
                        <view class="bg-white rounded-[20rpx] mt-[20rpx] py-2">
                            <view class="flex items-center justify-between px-[34rpx] py-1">
                                <view class="text-[30rpx] font-bold">AI创作视频</view>
                                <navigator
                                    url="/ai_modules/digital_human/pages/index/index"
                                    hover-class="none"
                                    class="flex items-center">
                                    <text class="text-xs text-[#0000004d]">更多</text>
                                    <u-icon name="arrow-right" :size="20" color="#0000004d"></u-icon>
                                </navigator>
                            </view>
                            <view class="grid grid-cols-3 gap-2 px-[24rpx] mt-2">
                                <view
                                    class="flex flex-col items-center justify-between bg-[#F6F6F6] rounded-[20rpx] px-[36rpx] py-[30rpx] pb-0"
                                    v-for="item in aiCreationVideoList"
                                    :key="item.key"
                                    @click="toPage(item.key)">
                                    <view class="font-bold"> {{ item.name }} </view>
                                    <view class="mt-4 leading-[0]">
                                        <image
                                            :src="`${config.baseUrl}static/images/${item.icon}`"
                                            class="w-[140rpx] h-[140rpx]"></image>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </template>
            </chat-scroll-view>
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
import config from "@/config";

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

interface ChatLogParams {
    page_no: number;
    page_size: number;
    assistant_id: number;
    task_id?: string;
}

enum PageKeyEnum {
    CREATE_TASK = "create_task",
    VIEW_SCHEDULE = "view_schedule",
    AI_CUSTOMER = "ai_customer",
    DH = "dh",
    DIGITAL_PERSON_CLONE = "digital_person_clone",
    PUBLISH_IMG = "publish_img",
    PUBLISH_VIDEO = "publish_video",
    MY_CREATION = "my_creation",
    AI_PUZZLE = "ai_puzzle",
    AI_PRACTICE = "ladder_player",
    MEETING_MINUTES = "meeting_minutes",
    DIGITAL_PERSON_BROADCAST = "digital_person_broadcast",
    ORAL_VIDEO_MIX = "oral_video_mix",
    REAL_PERSON_BROADCAST = "real_person_broadcast",
    MATERIAL_MIX = "material_mix",
    ONE_SENTENCE_GENERATION = "one_sentence_generation",
    NEWS_BODY_GENERATION = "news_body_generation",
}

// 快速应用列表
const quickApplicationList = [
    {
        key: PageKeyEnum.AI_CUSTOMER,
        name: "AI获客",
        icon: "index_img_1.png",
    },
    {
        key: PageKeyEnum.DH,
        name: "数字人",
        icon: "index_img_2.png",
    },
    {
        key: PageKeyEnum.PUBLISH_IMG,
        name: "发布图文",
        icon: "index_img_3.png",
    },
    {
        key: PageKeyEnum.PUBLISH_VIDEO,
        name: "发布视频",
        icon: "index_img_4.png",
    },
    {
        key: PageKeyEnum.MY_CREATION,
        name: "我的创作",
        icon: "index_img_5.png",
    },
    {
        key: PageKeyEnum.AI_PUZZLE,
        name: "AI拼图",
        icon: "index_img_6.png",
    },
    {
        key: PageKeyEnum.AI_PRACTICE,
        name: "AI陪练",
        icon: "index_img_7.png",
    },
    {
        key: PageKeyEnum.MEETING_MINUTES,
        name: "会议助手",
        icon: "index_img_8.png",
    },
];

// AI创作视频
const aiCreationVideoList = [
    {
        key: PageKeyEnum.DIGITAL_PERSON_BROADCAST,
        name: "数字人口播",
        icon: "index_img_9.png",
    },
    {
        key: PageKeyEnum.ORAL_VIDEO_MIX,
        name: "口播混剪",
        icon: "index_img_10.png",
    },
    {
        key: PageKeyEnum.REAL_PERSON_BROADCAST,
        name: "真人口播",
        icon: "index_img_11.png",
    },
    {
        key: PageKeyEnum.MATERIAL_MIX,
        name: "素材混剪",
        icon: "index_img_12.png",
    },
    {
        key: PageKeyEnum.ONE_SENTENCE_GENERATION,
        name: "一句话生成",
        icon: "index_img_13.png",
    },
    {
        key: PageKeyEnum.NEWS_BODY_GENERATION,
        name: "新闻体生成",
        icon: "index_img_14.png",
    },
];

/**
 * 跳转页面
 */
const toPage = (key: PageKeyEnum) => {
    const urls = {
        [PageKeyEnum.CREATE_TASK]: "/ai_modules/device/pages/choose_task_type/choose_task_type",
        [PageKeyEnum.VIEW_SCHEDULE]: "/ai_modules/device/pages/task_calendar_full/task_calendar_full",
        [PageKeyEnum.DH]: "/ai_modules/digital_human/pages/index/index",
        [PageKeyEnum.DIGITAL_PERSON_CLONE]: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
        [PageKeyEnum.PUBLISH_IMG]: "/ai_modules/device/pages/create_task/create_task?type=2",
        [PageKeyEnum.PUBLISH_VIDEO]: "/ai_modules/device/pages/create_task/create_task?type=1",
        [PageKeyEnum.MY_CREATION]: "/packages/pages/creation/creation",
        [PageKeyEnum.AI_CUSTOMER]: "/ai_modules/sph/pages/create_task/create_task",
        [PageKeyEnum.AI_PUZZLE]: "/ai_modules/drawing/pages/index/index",
        [PageKeyEnum.AI_PRACTICE]: "/ai_modules/ladder_player/pages/index/index",
        [PageKeyEnum.MEETING_MINUTES]: "/ai_modules/meeting_minutes/pages/index/index",
        [PageKeyEnum.DIGITAL_PERSON_BROADCAST]: "/ai_modules/digital_human/pages/szr_create/szr_create",
        [PageKeyEnum.ORAL_VIDEO_MIX]: "/ai_modules/digital_human/pages/montage_create/montage_create",
        [PageKeyEnum.REAL_PERSON_BROADCAST]:
            "/ai_modules/digital_human/pages/montage_person_create/montage_person_create",
        [PageKeyEnum.MATERIAL_MIX]: "/ai_modules/digital_human/pages/montage_material_create/montage_material_create",
        [PageKeyEnum.ONE_SENTENCE_GENERATION]: "/ai_modules/digital_human/pages/sora_create/sora_create",
        [PageKeyEnum.NEWS_BODY_GENERATION]: "/ai_modules/digital_human/pages/montage_news_create/montage_news_create",
    };
    uni.navigateTo({
        url: urls[key as keyof typeof urls],
    });
};

// 状态管理
const appStore = useAppStore();
const userStore = useUserStore();
const { chatConfig } = toRefs(appStore);
const websiteConfig = computed(() => appStore.getWebsiteConfig);
const { userTokens } = toRefs(userStore);
const tokensValue = userStore.getTokenByScene(TokensSceneEnum.CHAT)?.score;

// 组件引用
const rechargePopupRef = ref();
const chattingRef = shallowRef();
const pagingRef = shallowRef();

// 页面状态
const safeAreaTop = ref<number>(50);
const isAgent = ref(false);
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
        isAgent.value = true;
        await nextTick();
        chattingRef.value?.openKeyboard();
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
    chattingRef.value?.hideKeyboard();
});

onHide(() => {
    chattingRef.value?.hideKeyboard();
});

onShow(() => {
    appStore.getChatConfig();
});
</script>

<style lang="scss" scoped></style>
