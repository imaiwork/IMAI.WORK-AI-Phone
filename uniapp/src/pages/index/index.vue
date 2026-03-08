<template>
    <view class="h-screen flex flex-col bg-[#EEF0F6]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :is-back="chatContentList.length > 0 || currAgent || currModel"
            :background="{
                background: 'transparent',
            }"
            is-custom-back-icon
            :custom-back="backChat">
            <template #custom-back-icon v-if="chatContentList.length > 0 || currAgent || currModel">
                <u-icon name="arrow-left" :size="32"></u-icon>
            </template>
            <view
                class="w-full flex items-center gap-x-2 px-4"
                v-if="chatContentList.length == 0 && !currAgent && !currModel">
                <view class="mx-4 w-full" v-if="tabList.length > 1">
                    <view class="underline-tabs-container">
                        <view
                            v-for="(tab, index) in tabList"
                            :key="`${tab.type}-${tab.name}`"
                            class="underline-tab-item"
                            :class="{ 'underline-tab-active': currTab === index }"
                            @click="handleTabChange(index)">
                            <text
                                class="underline-tab-text"
                                :class="{ 'underline-tab-text-active': currTab === index }">
                                {{ tab.name }}
                            </text>
                            <view class="underline-tab-slider" v-if="currTab === index"></view>
                        </view>
                    </view>
                </view>
                <view class="mx-4 text-xl font-medium" v-else>AI智能体</view>
            </view>
        </u-navbar>
        <view class="grow min-h-0 relative z-10 pt-2">
            <view v-if="chatContentList.length == 0 && currType === 0" class="absolute top-0 h-full w-full z-[33]">
                <view class="empty-container" v-if="!currAgent && !currModel">
                    <view
                        class="w-[120rpx] h-[120rpx] rounded-full shadow-[0_6rpx_10rpx_4rpx_rgba(105,120,255,0.5)] absolute top-[-30rpx] right-6 mx-auto">
                        <image :src="websiteConfig.shop_logo" class="w-full h-full rounded-full"></image>
                    </view>
                    <view class="pt-[65rpx]">
                        <view class="px-[56rpx]">
                            <view class="text-[36rpx] font-medium"> HI~我是AI大管家 </view>
                            <view class="text-[22rpx] text-[#000000]/30 mt-[12rpx]">你可以试试：</view>
                        </view>
                        <view class="mt-[18rpx] flex flex-col gap-y-[20rpx] px-2">
                            <view class="flex flex-wrap gap-x-2 gap-y-2 justify-center">
                                <view
                                    v-for="(item, index) in getAIModels"
                                    :key="index"
                                    class="bg-[#ffffff]/50 rounded-[100rpx] flex items-center gap-x-2 border-2 border-solid border-white p-[24rpx] shadow-[0_6rpx_10rpx_0_rgba(0,0,0,0.03)]"
                                    @click="handleSelectModel(item)">
                                    <image :src="item.logo" class="w-[40rpx] h-[40rpx] rounded-full"></image>
                                    <text class="text-[28rpx] font-medium">{{ item.name }}</text>
                                    <text class="tag-text" v-if="item.id == 10">推荐</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
                <view class="w-full h-full flex flex-col items-center pt-20" v-else-if="currAgent">
                    <image
                        :src="currAgent.avatar || currAgent.image"
                        class="w-[120rpx] h-[120rpx] rounded-full"
                        mode="aspectFill"></image>
                    <view class="text-[34rpx] font-medium mt-4">
                        {{ currAgent.name }}
                    </view>
                    <view class="text-xs text-[#000000]/50 mt-2">
                        {{ currAgent.intro || currAgent.introduced }}
                    </view>
                </view>
                <view class="w-full h-full flex flex-col items-center pt-20" v-else-if="currModel">
                    <image :src="currModel.logo" class="w-[120rpx] h-[120rpx] rounded-full" mode="aspectFill"></image>
                    <view class="text-[34rpx] font-medium mt-4">
                        {{ currModel.name }}
                    </view>
                </view>
            </view>
            <view class="h-full w-full" v-show="currType === 0">
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
                    @show-history="showHistory = true"
                    @update:agent="handleUpdateAgent"
                    @update:model="handleUpdateModel">
                    <template #content> </template>
                </chat-scroll-view>
            </view>
            <view class="h-full w-full flex flex-col" v-show="currType === 1">
                <view class="mt-4">
                    <scroll-view scroll-x>
                        <view class="flex gap-2 px-[32rpx]">
                            <view
                                v-for="item in agentCateLists"
                                class="text-[#959FAF] font-medium px-[24rpx] h-[64rpx] flex items-center rounded-full whitespace-nowrap"
                                :class="{
                                    'bg-black !text-white': currAgentType == item.value,
                                }"
                                :key="item.value"
                                @click="handleAgentCateClick(item)">
                                <view>{{ item.label }}</view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="grow min-h-0 mt-4">
                    <z-paging
                        ref="pagingCozeRef"
                        v-model="agentList"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="handleQueryAgentList">
                        <view class="space-y-2 px-[32rpx]">
                            <view
                                class="bg-white rounded-[20rpx] flex p-[28rpx] relative"
                                v-for="(item, index) in agentList"
                                :key="index"
                                @click="handleSelectAgent(item)">
                                <view class="w-[100rpx] h-[100rpx] shrink-0">
                                    <image
                                        :src="item.image || item.avatar"
                                        class="w-full h-full rounded-full"
                                        mode="aspectFill"></image>
                                </view>
                                <view class="flex-1 ml-[30rpx] mt-1">
                                    <view class="flex items-center gap-x-2">
                                        <view class="line-clamp-1 break-all font-medium text-[30rpx]">{{
                                            item.name
                                        }}</view>
                                        <view
                                            class="shrink-0 rounded-[60rpx] px-[14rpx] py-[6rpx] text-[22rpx] font-medium"
                                            :class="[
                                                item.source == 1
                                                    ? 'bg-[#0065fb]/5 text-primary'
                                                    : 'bg-[#FDF3E3] text-[#A13016]',
                                            ]">
                                            {{ item.source == 0 ? "官方" : "用户" }}
                                        </view>
                                    </view>
                                    <view class="mt-1 line-clamp-2 text-xs text-[#737373] break-all">
                                        {{ item.intro || item.introduced }}
                                    </view>
                                    <view class="mt-2">
                                        <view
                                            class="text-primary bg-[#0065fb]/5 rounded-[60rpx] px-[26rpx] py-[12rpx] font-medium w-fit">
                                            <u-icon name="chat" :size="28" color="#0065fb"></u-icon>
                                            <text class="ml-1">去对话</text></view
                                        >
                                    </view>
                                </view>
                                <view
                                    class="p-1 shrink-0 h-fit absolute top-2 right-2 z-[22]"
                                    v-if="item.source == 1"
                                    @click.stop="handleMore(item)">
                                    <u-icon name="more-dot-fill" :size="24" color="#999999"></u-icon>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
            </view>
            <view class="h-full w-full" v-show="currType === 2">
                <view class="m-4 mt-2 relative z-10">
                    <u-search
                        v-model="queryParams.name"
                        bg-color="#ffffff"
                        :show-action="false"
                        placeholder="请输入关键词"
                        @search="search"
                        @clear="clear"></u-search>
                </view>
                <view class="grow min-h-0 relative z-10">
                    <view class="h-full flex">
                        <view
                            class="w-[248rpx] h-full flex flex-col overflow-hidden flex-shrink-0 rounded-tr-[36rpx] bg-white">
                            <view class="grow min-h-0">
                                <scroll-view scroll-y class="h-full">
                                    <view
                                        v-for="(item, index) in optionsData.robotCate"
                                        class="robot-cate"
                                        :key="index"
                                        :class="[
                                            {
                                                'robot-cate-active': robotCateActiveMenu == index,
                                            },
                                            {
                                                'robot-cate-brother':
                                                    robotSubCateIndex == item.sub_list.length - 1 &&
                                                    isCurrMenu(item.sub_list),
                                            },
                                            {
                                                'robot-cate-first': robotSubCateIndex == 0 && isCurrMenu(item.sub_list),
                                            },
                                        ]"
                                        @click="handleRobotCate(index)">
                                        <view class="robot-cate-item">
                                            <view class="robot-cate-item-wrap">
                                                <view class="flex items-center gap-2 w-full">
                                                    <view class="flex-1 text-[#6D6E70] text-xs font-medium">{{
                                                        item.name
                                                    }}</view>
                                                    <u-icon
                                                        :name="
                                                            robotCateActiveMenu == index ? 'arrow-down' : 'arrow-right'
                                                        "
                                                        :size="24"
                                                        color="#707173"></u-icon>
                                                </view>
                                                <view class="text-[20rpx] text-[#D0D0D0] mt-1">
                                                    {{ item.sub_list.length }}
                                                </view>
                                            </view>
                                        </view>
                                        <template v-if="robotCateActiveMenu == index">
                                            <view
                                                v-for="(subItem, subIndex) in item.sub_list"
                                                :key="`${index}-${subIndex}`"
                                                class="sub-robot"
                                                :class="{
                                                    'sub-robot-active': currSubId == subItem.id,
                                                    'sub-robot-last':
                                                        robotSubCateIndex != 0 &&
                                                        robotSubCateIndex - 1 == subIndex &&
                                                        isCurrMenu(item.sub_list),
                                                }"
                                                @click.stop="handleRobotSubCate(subIndex, subItem.id)">
                                                <view class="sub-robot-item">
                                                    <view class="text-xs text-[#9A9A9C] line-clamp-1">
                                                        {{ subItem.name }}
                                                    </view>
                                                </view>
                                            </view>
                                        </template>
                                    </view>
                                </scroll-view>
                            </view>
                        </view>
                        <view class="flex-1 flex flex-col min-h-0 overflow-hidden">
                            <view class="text-[20rpx] font-medium text-[#6D6E70] mt-2 mx-[24rpx] mb-4">
                                {{ total }}个智能体
                            </view>
                            <view class="grow relative">
                                <view
                                    class="flex justify-center items-center absolute w-full h-full"
                                    v-if="queryLoading">
                                    <view class="loader"> </view>
                                </view>
                                <z-paging
                                    ref="pagingRobotRef"
                                    v-model="robots"
                                    :auto="true"
                                    :fixed="false"
                                    :safe-area-inset-bottom="true"
                                    @query="queryRobotList">
                                    <view class="pl-[24rpx] pr-[16rpx] flex flex-col gap-4">
                                        <view
                                            v-for="(item, index) in robots"
                                            :key="index"
                                            class="bg-white p-[24rpx] rounded-[24rpx]"
                                            @click="handleRobot(item)">
                                            <view class="flex gap-2">
                                                <image
                                                    :src="item.logo"
                                                    lazy
                                                    class="rounded-full w-[108rpx] h-[108rpx] flex-shrink-0"
                                                    mode="aspectFill"></image>
                                                <view class="">
                                                    <text class="font-medium mt-1 line-clamp-1">{{ item.name }}</text>
                                                    <view class="text-[20rpx] mt-1 text-[#9C9C9E] line-clamp-2">
                                                        {{ item.description }}
                                                    </view>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                    <template #empty>
                                        <view class="mx-4">
                                            <empty />
                                        </view>
                                    </template>
                                </z-paging>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <tabbar v-if="chatContentList.length === 0 && !currAgent && !currModel" />
    </view>
    <popup-bottom v-model="showHistory" title="历史记录" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
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
import { robotCategory, robotLists, getAgentList, getCozeAgentList, deleteAgent, deleteCozeAgent } from "@/api/agent";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useDictOptions } from "@/hooks/useDictOptions";

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
    LOGIN = "login",
    CREATE_TASK = "create_task",
    VIEW_SCHEDULE = "view_schedule",
    AI_CUSTOMER = "ai_customer",
    DH = "dh",
    DIGITAL_CREATION = "dh_creation",
    DIGITAL_PERSON_CLONE = "digital_person_clone",
    PUBLISH_IMG = "publish_img",
    PUBLISH_VIDEO = "publish_video",
    MY_CREATION = "my_creation",
    AI_PUZZLE = "ai_puzzle",
    MATERIAL_LIBRARY = "material_library",
    MEETING_MINUTES = "meeting_minutes",
    DIGITAL_PERSON_BROADCAST = "digital_person_broadcast",
    ORAL_VIDEO_MIX = "oral_video_mix",
    REAL_PERSON_BROADCAST = "real_person_broadcast",
    MATERIAL_MIX = "material_mix",
    ONE_SENTENCE_GENERATION = "one_sentence_generation",
    NEWS_BODY_GENERATION = "news_body_generation",
    DEVICE_BIND = "device_bind",
    DEVICE_CONFIG = "device_config",
}

// 状态管理
const appStore = useAppStore();
const userStore = useUserStore();
const { chatConfig } = toRefs(appStore);
const websiteConfig = computed(() => appStore.getWebsiteConfig);
const { userTokens, isLogin } = toRefs(userStore);
const tokensValue = userStore.getTokenByScene(TokensSceneEnum.CHAT)?.score;

const getAIModels = computed(() =>
    (appStore.getAiModelConfig?.channel || []).filter((item: any) => item.status == "1")
);

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

const currAgent = ref<any>(null);
const currModel = ref<any>(null);

const isShowRobot = computed(() => appStore.getIsShowRobot);

// 初始化Tab列表
const tabList = ref<any[]>([
    { name: "对话", type: 0 },
    { name: "智能体", type: 1 },
]);
const currTab = ref(0);
const currType = ref(tabList.value[0].type);

// ✅ 新增：记录选择智能体前所在的 tab 索引，用于返回时恢复
const prevTab = ref<number | null>(null);

const handleTabChange = async (index: number) => {
    currType.value = tabList.value[index].type;
    currTab.value = index;
    if (currTab.value == 1) {
        // setTimeout(() => {
        //     // pagingRobotRef.value?.reload();
        // }, 300);
    }
};

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
 * 选择模型处理
 */
const handleSelectModel = (item: any) => {
    currModel.value = item;
    chattingRef.value?.handleModel(item);
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

    if (!isNewChat) {
        chatContentList.value.push({
            type: 1,
            message: userInput,
            fileList: fileList.value,
        });
    }

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
                const { object, content, task_id, usage, check_robot_id, reasoning_content } = JSON.parse(text);
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
                    if (check_robot_id) {
                        chattingRef.value?.handleAgent({
                            id: check_robot_id,
                        });
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
const backChat = async () => {
    chatContentList.value = [];
    resetChat();
    handleChatClose();
    await nextTick();
    chattingRef.value?.handleModelClear();
    chattingRef.value?.handleAgentClear();

    // ✅ 如果是从智能体 tab 跳转过来的，返回时恢复到智能体 tab
    if (prevTab.value !== null) {
        currTab.value = prevTab.value;
        currType.value = tabList.value[prevTab.value].type;
        prevTab.value = null;
    }
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

// 页面状态
const state = reactive({
    cate_id: "",
});

// AI助理（机器人）部分
const robotCateIndex = ref<number>(0);
const robotCateActiveMenu = ref<number>(-1);
const pagingRobotRef = shallowRef();
const robots = ref<any[]>([]);
const total = ref<number>(0);
const queryParams = reactive({
    type: 3,
    scene_id: "",
    name: "",
});
const queryLoading = ref<boolean>(false);

// AI智能体部分
const pagingCozeRef = shallowRef();
const agentCateLists = [
    { label: "智能体", value: 1 },
    { label: "智能体RPO", value: 2 },
    { label: "工作流", value: 3 },
];
const currAgentType = ref<any>(1);
const agentList = ref<any[]>([]);

const { optionsData } = useDictOptions<{
    robotCate: any[];
}>({
    robotCate: {
        api: robotCategory,
        params: {
            pageSize: 9999,
            pid: 0,
        },
        transformData: (data) => {
            if (data.lists.length) {
                if (state.cate_id) {
                    robotCateIndex.value = data.lists.findIndex((item: any) => item.id == state.cate_id);
                }
                // pagingRobotRef.value?.reload();
            }
            return data.lists;
        },
    },
});

const robotSubCateIndex = ref<number>(-1);
const currSubId = ref<number>();

/**
 * 搜索机器人
 */
const search = async () => {
    pagingRobotRef.value?.reload();
};

/**
 * 清除搜索关键词并重新加载
 */
const clear = async () => {
    pagingRobotRef.value?.reload();
};

/**
 * 查询机器人列表
 */
const queryRobotList = async (pageNo: number, pageSize: number) => {
    try {
        const { lists = [], count } = await robotLists({
            page_size: pageSize,
            page_no: pageNo,
            ...queryParams,
        });
        total.value = count;
        pagingRobotRef.value?.complete(lists);
        queryLoading.value = false;
    } catch (error) {
        console.error("查询机器人列表失败:", error);
        queryLoading.value = false;
        pagingRobotRef.value?.complete(false);
    }
};

/**
 * 点击一级机器人分类
 */
const handleRobotCate = (index: number) => {
    if (index == robotCateActiveMenu.value) {
        robotCateActiveMenu.value = -1;
        return;
    }
    robotCateActiveMenu.value = index;
};

/**
 * 点击二级机器人分类
 */
const handleRobotSubCate = (index: number, id: number) => {
    if (index == robotSubCateIndex.value && id == currSubId.value) {
        return;
    }
    currSubId.value = id;
    robotSubCateIndex.value = index;
    queryLoading.value = true;

    const currSubLists = optionsData.robotCate[robotCateActiveMenu.value]?.sub_list;
    queryParams.scene_id = currSubLists[index]?.id;
    pagingRobotRef.value?.reload();
};

/**
 * 检查当前二级分类是否属于给定的一级分类列表
 */
const isCurrMenu = (lists: any[]) => {
    return lists.some((item) => item.id == currSubId.value);
};

/**
 * 点击机器人项
 */
const handleRobot = (data: any) => {
    uni.$u.route({
        url: "/packages/pages/robot_chat/robot_chat",
        params: {
            id: data.id,
        },
    });
};

/**
 * 点击智能体分类
 */
const handleAgentCateClick = (item: any) => {
    currAgentType.value = item.value;
    pagingCozeRef.value?.reload();
};

/**
 * 点击智能体项
 */
const handleSelectAgent = async (item: any) => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/pages/login/login" });
        return;
    }
    if (currAgentType.value == 1) {
        prevTab.value = currTab.value;
        currType.value = tabList.value[0].type;
        currTab.value = 0;
        await nextTick();
        chattingRef.value?.handleAgent(item);
    } else {
        uni.$u.route({
            url: "/packages/pages/coze_chat/coze_chat",
            params: {
                id: item.id,
                type: item.type,
            },
        });
    }
};

/**
 * 更新智能体
 */
const handleUpdateAgent = (item: any) => {
    currAgent.value = item;
};

/**
 * 更新模型
 */
const handleUpdateModel = (item: any) => {
    currModel.value = item;
};

/**
 * 更多操作
 */
const handleMore = (item: any) => {
    uni.showActionSheet({
        itemList: ["删除"],
        success: (res) => {
            if (res.tapIndex == 0) {
                uni.showModal({
                    title: "提示",
                    content: "确定删除该智能体吗？",
                    success: async (res) => {
                        if (res.confirm) {
                            uni.showLoading({
                                title: "删除中...",
                                mask: true,
                            });
                            try {
                                const api = currAgentType.value == 1 ? deleteAgent : deleteCozeAgent;
                                await api({ id: item.id });
                                uni.hideLoading();
                                uni.showToast({
                                    title: "删除成功",
                                    icon: "none",
                                    duration: 3000,
                                });
                                agentList.value = agentList.value.filter((value) => value.id != item.id);
                            } catch (error: any) {
                                uni.hideLoading();
                                uni.showToast({
                                    title: error,
                                    icon: "none",
                                    duration: 3000,
                                });
                            }
                        }
                    },
                });
            }
        },
    });
};

/**
 * 查询智能体列表
 */
const handleQueryAgentList = async (page_no: number, page_size: number) => {
    try {
        const isType1 = currAgentType.value === 1;
        const api = isType1 ? getAgentList : getCozeAgentList;
        const params = {
            page_no,
            page_size,
            ...(isType1 ? {} : { type: currAgentType.value === 2 ? 1 : 2 }),
        };
        // @ts-ignore
        const response = await api(params);
        pagingCozeRef.value?.complete(response.lists || []);
    } catch (error) {
        console.error("查询智能体列表失败:", error);
        pagingCozeRef.value?.complete(false);
    }
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

// 🔥 关键逻辑：动态添加AI助理Tab
watch(
    () => isShowRobot.value,
    (newVal) => {
        const assistantTabIndex = tabList.value.findIndex((tab) => tab.type === 2);
        if (assistantTabIndex > -1) {
            tabList.value.splice(assistantTabIndex, 1);
            if (currTab.value >= tabList.value.length) {
                currTab.value = 0;
                currType.value = tabList.value[0].type;
            }
        }

        if (newVal == "1") {
            tabList.value.push({ name: "AI助理", type: 2 });
        }

        if (currTab.value >= tabList.value.length) {
            currTab.value = 0;
            currType.value = tabList.value[0].type;
        }
    },
    { immediate: true }
);

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

<style lang="scss" scoped>
.underline-tabs-container {
    @apply flex items-center justify-center;
}

.underline-tab-item {
    @apply flex-1 flex flex-col items-center justify-center pt-[16rpx] relative;
}

.underline-tab-text {
    @apply text-[28rpx]  text-[#999999] pb-[16rpx] whitespace-nowrap;
}

.underline-tab-text-active {
    @apply text-[30rpx] font-semibold text-[#333333];
}

.underline-tab-slider {
    transform: translateX(-50%);
    transition: all 0.25s ease;
    @apply bg-primary rounded-[6rpx] h-[6rpx] w-[40rpx] absolute bottom-[-2rpx] left-1/2;
}
.empty-container {
    @apply w-[90%] mx-auto mt-10 rounded-[30rpx] relative;
    background: linear-gradient(180deg, rgba(208, 231, 255, 1) 20%, rgba(232, 236, 252, 1) 70%, transparent 100%);
}
.tag-text {
    @apply text-white text-[22rpx] font-medium px-[12rpx] py-[4rpx] rounded-[100rpx];
    background: linear-gradient(90deg, rgba(244, 137, 36, 1) 0%, rgba(241, 89, 61, 1) 100%);
}

.robot-cate {
    @apply overflow-hidden gap-2 relative;
    &-active {
        &.robot-cate-first {
            .robot-cate-item {
                @apply relative z-40;
                .robot-cate-item-wrap {
                    @apply rounded-br-[36rpx];
                }
                &::after {
                    content: "";
                    @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6] z-10;
                }
            }
            .robot-cate-item {
                @apply rounded-br-[36rpx];
            }
        }
        &.robot-cate-brother {
            & + .robot-cate {
                .robot-cate-item-wrap {
                    @apply rounded-tr-[36rpx];
                }
                .robot-cate-item {
                    &::after {
                        content: "";
                        @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6] z-10;
                    }
                }
            }
        }
    }
}

.robot-cate-item-wrap {
    @apply relative z-30 px-[24rpx] py-3 bg-white;
}

.sub-robot-item {
    @apply w-full h-full flex items-center pl-[24rpx] relative z-10;
}

.sub-robot {
    @apply h-[100rpx] relative;
    &-last {
        &::after {
            content: "";
            @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6];
        }
        .sub-robot-item {
            @apply rounded-br-[36rpx] bg-white overflow-hidden;
        }
        .sub-robot-item-bg {
            @apply bg-[#F5F6F6];
        }
    }
}

.sub-robot-active {
    .sub-robot-item {
        @apply bg-[#F5F6F6] rounded-tl-[36rpx] rounded-bl-[36rpx];
    }

    & + .sub-robot {
        &::after {
            content: "";
            @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6];
        }
        .sub-robot-item {
            @apply rounded-tr-[36rpx] bg-white overflow-hidden;
        }
        .sub-robot-item-bg {
            @apply bg-[#F5F6F6];
        }
    }
}
</style>
