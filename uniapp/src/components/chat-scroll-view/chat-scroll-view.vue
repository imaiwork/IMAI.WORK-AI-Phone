<template>
    <view class="h-full flex flex-col min-h-0">
        <view class="flex flex-col flex-1 min-h-0 py-4 relative" v-if="contentList.length">
            <view class="scroll-view-content flex-1 flex min-h-0">
                <scroll-view
                    scroll-y
                    ref="contentRef"
                    :scroll-top="scrollTop"
                    :scroll-with-animation="false"
                    @scroll="handleScroll"
                    @scrolltolower="handleScrollToLower">
                    <view v-if="contentList.length" class="content-box">
                        <view v-for="(item, index) in contentList" :key="`${item.id} + ${index} + ''`">
                            <view class="pb-4">
                                <chat-record-item
                                    :type="item.type"
                                    :avatar="item.form_avatar"
                                    :content="item.type == 1 ? item.message : item.reply"
                                    :reasoning-content="item.reasoning_content"
                                    :is-reasoning-finished="item.is_reasoning_finished"
                                    :loading="item.loading"
                                    :consume-tokens="item.consume_tokens"
                                    :file-list="item.fileList"
                                    :index="index"
                                    :is-markdown="item.type == 2"
                                    :showCopyBtn="item.type == 2"></chat-record-item>
                            </view>
                        </view>
                    </view>
                    <slot v-else name="empty"></slot>
                </scroll-view>
            </view>
        </view>
        <view class="grow min-h-0 relative" v-if="contentList.length == 0">
            <scroll-view class="h-full w-full" scroll-y>
                <slot name="content"></slot>
            </scroll-view>
            <view
                v-if="!isCoze && !isStaff"
                class="absolute bottom-[-40rpx] left-0 right-0 h-20 z-10 pointer-events-none"
                style="background: linear-gradient(360deg, #eef0f6, transparent)">
            </view>
        </view>
        <view
            class="px-[20rpx] pt-1 mb-[20rpx] relative flex-shrink-0"
            :class="[isCoze || isStaff ? 'mb-[40rpx]' : 'mb-[20rpx]']">
            <view class="relative z-[79] chat-bottom-box">
                <view class="flex flex-col">
                    <scroll-view v-if="!isCoze && !isStaff" scroll-x class="mb-1">
                        <view class="flex items-center gap-x-2 whitespace-nowrap pt-1">
                            <view
                                v-if="currModel.id && !currAgent.id"
                                class="text-xs bg-white rounded-[16rpx] px-2 h-[60rpx] inline-flex items-center gap-x-1"
                                @click="
                                    showModel = true;
                                    hideKeyboard();
                                ">
                                <image :src="currModel.logo" class="w-[28rpx] h-[28rpx] rounded-full"></image>
                                <text class="whitespace-nowrap">{{ currModel.name }}</text>
                                <view class="ml-1 inline-block">
                                    <u-icon name="arrow-down" size="20" color="#a8abb2"></u-icon>
                                </view>
                            </view>
                            <view
                                v-if="currAgent.id"
                                class="bg-white rounded-[16rpx] px-2 h-[60rpx] gap-x-1 border border-solid border-[#E9EBEC] inline-flex items-center relative"
                                @click="openAgentPopup">
                                <image
                                    :src="currAgent.avatar"
                                    class="w-[28rpx] h-[28rpx] rounded-[24rpx]"
                                    mode="aspectFill" />
                                <text class="max-w-[200rpx] text-ellipsis overflow-hidden whitespace-nowrap text-xs">
                                    {{ currAgent.name }}
                                </text>
                                <view
                                    class="absolute right-[-10rpx] top-[-10rpx] flex items-center justify-center w-[32rpx] h-[32rpx] rounded-full bg-[#0000004C]"
                                    @click.stop="handleAgentClear">
                                    <u-icon name="close" color="#ffffff" :size="14"></u-icon>
                                </view>
                            </view>
                            <view
                                class="flex-shrink-0 flex items-center justify-center gap-x-1 text-xs bg-white rounded-[16rpx] h-[60rpx] px-2"
                                :class="{
                                    '!bg-primary !text-white': selectedNetwork,
                                }"
                                @click="handleNetwork">
                                <u-icon
                                    name="/static/images/icons/deep.svg"
                                    :size="24"
                                    v-if="!selectedNetwork"></u-icon>
                                <u-icon name="/static/images/icons/deep_white.svg" :size="24" v-else></u-icon>
                                <text class="text-xs">联网</text>
                            </view>
                            <view
                                class="flex-shrink-0 flex items-center justify-center gap-x-1 text-xs bg-white rounded-[16rpx] h-[60rpx] px-2"
                                @click="
                                    emit('showHistory');
                                    hideKeyboard();
                                ">
                                <u-icon name="/static/images/icons/history.svg" :size="24"></u-icon>
                                <text class="text-xs">历史</text>
                            </view>
                            <view
                                class="flex-shrink-0 leading-[0] h-[60rpx] w-[60rpx] flex items-center justify-center rounded-full bg-white"
                                @click="handleSetting">
                                <u-icon name="/static/images/icons/setting.svg" :size="28"></u-icon>
                            </view>
                            <view
                                class="flex-shrink-0 flex items-center justify-center gap-x-1 text-xs bg-white rounded-[16rpx] h-[60rpx] px-2"
                                @click="toPage('ladder_player')">
                                <u-icon
                                    :name="`${config.baseUrl}static/images/mp/ladder_player.svg`"
                                    :size="24"></u-icon>
                                <text class="text-xs">AI陪练</text>
                            </view>
                            <view
                                class="flex-shrink-0 flex items-center justify-center gap-x-1 text-xs bg-white rounded-[16rpx] h-[60rpx] px-2"
                                @click="toPage('meeting_minutes')">
                                <u-icon
                                    :name="`${config.baseUrl}static/images/mp/meeting_minutes.svg`"
                                    :size="24"></u-icon>
                                <text class="text-xs">AI会议</text>
                            </view>
                            <view
                                class="flex-shrink-0 flex items-center justify-center gap-x-1 text-xs bg-white rounded-[16rpx] h-[60rpx] px-2"
                                @click="toPage('interview')">
                                <u-icon :name="`${config.baseUrl}static/images/mp/interview.svg`" :size="24"></u-icon>
                                <text class="text-xs">智能人事</text>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-if="$slots.chatAreaTop" class="mb-2">
                        <slot name="chatAreaTop"></slot>
                    </view>
                    <view class="flex-1 flex gap-x-2 items-center">
                        <slot name="sendLeft" v-if="$slots.sendLeft"></slot>
                        <view
                            class="flex-1 bg-white rounded-[48rpx] rounded-tr-[48rpx] border border-solid border-[#F1F1F2] overflow-hidden relative py-[6rpx]">
                            <view v-if="fileList.length" class="p-2 flex">
                                <view v-for="(item, index) in fileList" :key="index">
                                    <FileItem :item="item" :index="index" @on-delete="deleteFile" />
                                </view>
                            </view>
                            <view class="flex">
                                <view v-if="!isCoze && !isStaff" class="ml-3 mb-2 mt-2 flex flex-col justify-center">
                                    <view
                                        class="flex-shrink-0 w-[44rpx] h-[44rpx] flex items-center justify-center"
                                        @click="handleFileUpload">
                                        <image src="/static/images/icons/add2.svg" class="w-full h-full"></image>
                                    </view>
                                </view>
                                <textarea
                                    class="!w-full max-h-[300rpx] overflow-y-auto text-[26rpx] px-2 py-[24rpx]"
                                    ref="textareaRef"
                                    v-model="userInput"
                                    confirm-type="done"
                                    maxlength="-1"
                                    hold-keyboard
                                    placeholder-style="color: rgba(0, 0, 0, 0.2); font-size: 26rpx;"
                                    auto-height
                                    :adjust-position="false"
                                    :placeholder="placeholder"
                                    :show-confirm-bar="false"
                                    :disable-default-padding="true"
                                    @input="handleInput"></textarea>
                                <view class="flex-shrink-0 flex items-end gap-2.5 mr-3 mb-1">
                                    <view class="send-btn bg-primary-light-9" v-if="isStop" @click="chatClose">
                                        <u-icon name="/static/images/icons/chat_stop.svg" :size="36"></u-icon>
                                    </view>
                                    <view
                                        class="send-btn"
                                        :class="[!isSendDisabled ? 'bg-primary-light-9' : 'bg-[#F2F2F2]']"
                                        @click.prevent="contentPost"
                                        v-else>
                                        <u-icon
                                            v-if="!isSendDisabled"
                                            name="/static/images/icons/arrow_up_primary.svg"
                                            :size="36"></u-icon>
                                        <u-icon v-else name="/static/images/icons/arrow_up.svg" :size="36"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view
                        class="flex justify-center mt-[20rpx]"
                        v-if="contentList.length > 0 || currAgent.id || currModel.id">
                        <view class="flex items-center rounded-full bg-[#00000008] gap-x-1.5 p-[6rpx]">
                            <u-icon name="/static/images/icons/tips.svg" :size="32"></u-icon>
                            <view class="text-[rgba(0,0,0,0.3)] text-xs">
                                免责声明：内容由AI大模型生成，请仔细甄别。
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view
            class="flex-shrink-0"
            :style="{
                height: spacerHeight + 'rpx',
            }"></view>
    </view>
    <popup-bottom v-model="showHumanize" title="参数设置" height="85%" custom-class="bg-white" is-disabled-touch>
        <template #content>
            <view class="h-[85%] p-4 flex flex-col gap-y-4">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view>
                            <view class="mb-4">上下文数</view>
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1">
                                    <slider
                                        :value="humanizeParams.context_num"
                                        active-color="#0065FB"
                                        background-color="#e5e5e5"
                                        :block-size="16"
                                        :min="0"
                                        :max="5"
                                        @change="changeHumanizeParams($event, 'context_num', 0)" />
                                </view>
                                <view class="text-xs flex-shrink-0 w-[80rpx] text-center">
                                    {{ humanizeParams.context_num }}条
                                </view>
                            </view>
                        </view>
                        <view v-if="currModel.model_id != ModelIdEnum.CLAUDE_SONNET_4_5">
                            <view class="mb-4">词汇多样性</view>
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1">
                                    <slider
                                        :value="humanizeParams.top_p"
                                        active-color="#0065FB"
                                        background-color="#e5e5e5"
                                        :block-size="16"
                                        :min="0"
                                        :max="1"
                                        :step="0.1"
                                        @change="changeHumanizeParams($event, 'top_p', 1)" />
                                </view>
                                <view class="text-xs flex-shrink-0 w-[80rpx] text-center">{{
                                    humanizeParams.top_p
                                }}</view>
                            </view>
                        </view>
                        <view v-if="currModel.model_id != ModelIdEnum.DEEPSEEK">
                            <view class="mb-4">重复词频率</view>
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1">
                                    <slider
                                        :value="humanizeParams.frequency_penalty"
                                        active-color="#0065FB"
                                        background-color="#e5e5e5"
                                        :block-size="16"
                                        :min="-2"
                                        :max="2"
                                        :step="0.1"
                                        @change="changeHumanizeParams($event, 'frequency_penalty', 1)" />
                                </view>
                                <view class="text-xs flex-shrink-0 w-[80rpx] text-center">{{
                                    humanizeParams.frequency_penalty
                                }}</view>
                            </view>
                        </view>
                        <view v-if="currModel.model_id != ModelIdEnum.DEEPSEEK">
                            <view class="mb-4">特定词重复率</view>
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1">
                                    <slider
                                        :value="humanizeParams.presence_penalty"
                                        active-color="#0065FB"
                                        background-color="#e5e5e5"
                                        :block-size="16"
                                        :min="0"
                                        :max="1"
                                        :step="0.1"
                                        @change="changeHumanizeParams($event, 'presence_penalty', 1)" />
                                </view>
                                <view class="text-xs flex-shrink-0 w-[80rpx] text-center">{{
                                    humanizeParams.presence_penalty
                                }}</view>
                            </view>
                        </view>
                        <view>
                            <view class="mb-4">结果相似性</view>
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1">
                                    <slider
                                        :value="humanizeParams.temperature"
                                        active-color="#0065FB"
                                        background-color="#e5e5e5"
                                        :block-size="16"
                                        :min="0"
                                        :max="getMaxTemperature"
                                        :step="0.1"
                                        @change="changeHumanizeParams($event, 'temperature', 1)" />
                                </view>
                                <view class="text-xs flex-shrink-0 w-[80rpx] text-center">{{
                                    humanizeParams.temperature
                                }}</view>
                            </view>
                        </view>
                        <view v-if="currModel.model_id != ModelIdEnum.DEEPSEEK">
                            <view class="mb-4">显示前几个候选词对数概率</view>
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1">
                                    <slider
                                        :value="humanizeParams.top_logprobs"
                                        active-color="#0065FB"
                                        background-color="#e5e5e5"
                                        :block-size="16"
                                        :min="0"
                                        :max="1"
                                        :step="0.1"
                                        @change="changeHumanizeParams($event, 'top_logprobs', 1)" />
                                </view>
                                <view class="text-xs flex-shrink-0 w-[80rpx] text-center">{{
                                    humanizeParams.top_logprobs
                                }}</view>
                            </view>
                        </view>
                        <view>
                            <view class="mb-4">返回长度</view>
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1">
                                    <slider
                                        :value="humanizeParams.max_tokens"
                                        active-color="#0065FB"
                                        background-color="#e5e5e5"
                                        :block-size="16"
                                        :min="1"
                                        :max="getMaxTokens"
                                        :step="1"
                                        @change="changeHumanizeParams($event, 'max_tokens', 1)" />
                                </view>
                                <view class="text-xs flex-shrink-0 w-[80rpx] text-center">{{
                                    humanizeParams.max_tokens
                                }}</view>
                            </view>
                        </view>
                        <view v-if="currModel.model_id != ModelIdEnum.DEEPSEEK">
                            <view class="mb-4">显示候选词</view>
                            <view class="flex items-center gap-x-2">
                                <u-switch
                                    v-model="humanizeParams.logprobs"
                                    :active-value="1"
                                    :inactive-value="0"
                                    size="40" />
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="mt-4 flex-shrink-0">
                    <u-button
                        type="primary"
                        :custom-style="{ height: '100rpx', fontSize: '28rpx' }"
                        @click="handelChatConfig"
                        >保存设置</u-button
                    >
                </view>
            </view>
        </template>
    </popup-bottom>
    <popup-bottom v-model="showModel" title="选择模型" height="55%">
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="pb-[150rpx]">
                    <view class="p-4 flex flex-col gap-y-4">
                        <view
                            v-for="(item, index) in getAIModels"
                            :key="index"
                            class="border border-solid border-[#E9EBEC] rounded-[10rpx] px-4 py-3 flex items-center gap-x-2"
                            :class="{
                                '!border-primary text-primary font-medium': currModel.id == item.id,
                            }"
                            @click="handleModel(item)">
                            <image
                                :src="item.logo"
                                class="w-[48rpx] h-[48rpx] rounded-[12rpx]"
                                mode="aspectFill"></image>
                            <text class="text-xs line-clamp-1">{{ item.name }}</text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
    <popup-bottom v-model="showAgent" title="选择智能体" height="85%" is-disabled-touch>
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="agentPagingRef"
                    v-model="agentList"
                    :fixed="false"
                    :auto="false"
                    :safe-area-inset-bottom="true"
                    @query="getAgentList">
                    <view class="flex flex-col gap-4 px-[32rpx] mt-4">
                        <view
                            class="agent-item"
                            :class="{
                                active: currAgent.id == item.id,
                            }"
                            v-for="(item, index) in agentList"
                            :key="index"
                            @click="handleAgent(item)">
                            <view class="flex-shrink-0">
                                <image
                                    :src="item.image"
                                    class="w-[108rpx] h-[108rpx] rounded-[24rpx]"
                                    mode="aspectFill">
                                </image>
                            </view>
                            <view class="flex-1 overflow-hidden">
                                <view class="text-[28rpx] text-ellipsis overflow-hidden whitespace-nowrap">
                                    {{ item.name }}
                                </view>
                                <view class="text-[#9C9C9E] text-[20rpx] mt-2 line-clamp-2">
                                    {{ item.intro }}
                                </view>
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
</template>

<script lang="ts" setup>
import config from "@/config";
import { getUserChatConfig, saveUserChatConfig } from "@/api/chat";
import { getAllAgentList as getAgentListApi, getAgentDetail } from "@/api/agent";
import { getRect, setFormData } from "@/utils/util";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";
import { useAppStore } from "@/stores/app";
import { ModelIdEnum } from "@/enums/appEnums";
import FileItem from "./components/file-item.vue";
import { useUserStore } from "@/stores/user";

const props = withDefaults(
    defineProps<{
        contentList: any[];
        fileList?: any[];
        placeholder?: string;
        sendDisabled: boolean;
        tokens: number | string;
        isStop: boolean;
        isNetwork?: boolean;
        isCoze?: boolean;
        isStaff?: boolean;
        isHome?: boolean;
    }>(),
    {
        contentList: () => [],
        fileList: () => [],
        placeholder: "在这里输入任何问题 ...",
        sendDisabled: false,
        tokens: 0,
        isNetwork: true,
        isCoze: false,
        isStaff: false,
    }
);

const emit = defineEmits<{
    (event: "update:modelValue", value: any[]): void;
    (event: "contentPost", value: any): void;
    (event: "close"): void;
    (event: "add-session"): void;
    (event: "update:fileList", value: any): void;
    (event: "update:network", value: boolean): void;
    (event: "showHistory"): void;
    (event: "update:agent", value: any): void;
    (event: "update:model", value: any): void;
}>();

const appStore = useAppStore();
const isLogin = computed(() => useUserStore().isLogin);

const currModel = ref<any>({
    id: "",
    name: "",
    model_id: "",
    model_sub_id: "",
});

const getAIModels = computed(() =>
    (appStore.getAiModelConfig?.channel || []).filter((item: any) => item.status == "1")
);

const selectedNetwork = ref(false);
const handleNetwork = () => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/pages/login/login" });
        return;
    }
    selectedNetwork.value = !selectedNetwork.value;
    emit("update:network", selectedNetwork.value);
};

const fileList = computed({
    get() {
        return props.fileList;
    },
    set(value) {
        emit("update:fileList", value);
    },
});

const isSendDisabled = computed(() => {
    const flag = fileList.value.length === 0 ? !userInput.value : false;
    return props.sendDisabled || flag;
});

const handleFileUpload = () => {
    checkLogin();
    uni.$u.route({
        url: "/packages/pages/choose_file/choose_file",
        params: { limit: 1 },
    });
};

const showModel = ref(false);
const handleModel = (item: any) => {
    currModel.value = JSON.parse(JSON.stringify(item));
    showModel.value = false;
    getChatConfig();
    emit("update:model", item);
};

const handleModelClear = () => {
    emit("update:model", null);
};

const showAgent = ref(false);
const currAgent = reactive({
    id: "",
    name: "",
    avatar: "",
});

const handleAgent = async (item: any) => {
    if (!item.name) {
        try {
            const { name, image } = await getAgentDetail({ id: item.id });
            item.name = name;
            item.image = image;
        } catch (error: any) {}
    }
    setFormData({ ...item, avatar: item.avatar || item.image }, currAgent);
    userInput.value = "";
    showAgent.value = false;
    emit("update:agent", item);
};

const handleAgentClear = () => {
    currAgent.id = "";
    currAgent.name = "";
    currAgent.avatar = "";
    emit("update:agent", null);
};

const showHumanize = ref(false);
const humanizeParams = reactive({
    top_p: 0.5,
    temperature: 1,
    presence_penalty: 0.1,
    frequency_penalty: 2,
    context_num: 3,
    top_logprobs: 10,
    logprobs: 0,
    max_tokens: 4096,
});

const getMaxTemperature = computed(() => {
    if (currModel.value.model_id == ModelIdEnum.DEEPSEEK) return 2;
    return 1;
});

const getMaxTokens = computed(() => {
    if (currModel.value.model_id == ModelIdEnum.DEEPSEEK) return 4096;
    return 10000;
});

const changeHumanizeParams = (event: any, key: string, step: number) => {
    let { value } = event.detail;
    if (step == 0) {
        humanizeParams[key as keyof typeof humanizeParams] = value;
    } else {
        if (Number.isInteger(value)) {
            humanizeParams[key as keyof typeof humanizeParams] = value;
        } else {
            humanizeParams[key as keyof typeof humanizeParams] = value.toFixed(step);
        }
    }
};

const handleSetting = () => {
    checkLogin();
    getChatConfig();
    showHumanize.value = true;
    hideKeyboard();
};

const handelChatConfig = async () => {
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        await saveUserChatConfig({
            model_id: currModel.value.model_id,
            model_sub_id: currModel.value.model_sub_id,
            ...humanizeParams,
        });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        showHumanize.value = false;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "保存失败", icon: "none", duration: 3000 });
    }
};

const contentRef = shallowRef();
const userInput = ref("");
const scrollTop = ref<number>(0);

// ===== 滚动控制相关变量 =====
// 是否禁用自动滚动到底部（用户手动向上滑动时禁用）
const disabledScroll = ref(false);
// 记录上一次的滚动位置，用于判断滚动方向
const previousScrollTop = ref(0);
// 标记当前是否是由代码触发的滚动，避免误判用户行为
const isProgrammaticScroll = ref(false);
// scrolltolower 防抖定时器
let scrollToLowerTimer: ReturnType<typeof setTimeout> | null = null;

const { dynamicHeight, hideKeyboard } = useKeyboardHeight();
const { safeAreaInsets, windowWidth, platform } = uni.getSystemInfoSync();

const tabbarHeight = computed(() => {
    const fixedHeight = platform === "android" ? 95 : 125;
    return fixedHeight + (safeAreaInsets?.bottom ?? 0);
});

const bottomOffset = computed(() => {
    const otherHeight = 70 + (props.isStaff ? 20 : 0);
    return props.isHome ? tabbarHeight.value + otherHeight : otherHeight;
});

const spacerHeight = computed(() => {
    const height = dynamicHeight.value;
    return height > 0 ? (height * 750) / windowWidth - bottomOffset.value : 0;
});

const handleInput = (e: any) => {
    if (userInput.value.indexOf("@") == 0 && userInput.value.length == 1) {
        openAgentPopup();
        uni.hideKeyboard();
    }
};

/**
 * scroll 事件处理
 * - 代码触发的滚动（isProgrammaticScroll=true）：只更新 previousScrollTop，不改变 disabledScroll
 * - 用户手动向上滑动超过 50px：禁用自动滚动
 * - 注意：不在此处做"接近底部恢复"的判断，避免用户手动滑到接近底部时被误触发滚底
 */
const handleScroll = (e: any) => {
    const currentScrollTop = e.detail.scrollTop;

    if (isProgrammaticScroll.value) {
        // ✅ 代码滚动期间完全忽略，不更新任何状态
        return;
    }

    if (currentScrollTop < previousScrollTop.value - 50) {
        disabledScroll.value = true;
    }

    previousScrollTop.value = currentScrollTop;
};

/**
 * scrolltolower 事件：用户真正滚动到底部时才恢复自动滚动
 * 加防抖避免边界抖动反复触发
 */
const handleScrollToLower = () => {
    if (scrollToLowerTimer) clearTimeout(scrollToLowerTimer);
    scrollToLowerTimer = setTimeout(() => {
        disabledScroll.value = false;
        // ✅ 不在这里赋 previousScrollTop，由 handleScroll 自己维护
    }, 100);
};

const contentPost = () => {
    checkLogin();
    if (userInput.value.replace(/(^\s*)|(\s*$)/g, "") == "" && fileList.value.length == 0) {
        uni.$u.toast("输入为空");
        return;
    }
    if (props.sendDisabled) return;
    emit("contentPost", userInput.value);

    // 发送消息时重置滚动禁用状态，确保新消息可以自动滚到底部
    disabledScroll.value = false;
    previousScrollTop.value = 0;

    nextTick(() => {
        scrollToBottom();
    });
    inputBlur();
    userInput.value = "";
    fileList.value = [];
    emit("update:fileList", []);
};

const chatClose = () => {
    emit("close");
};

const openAgentPopup = () => {
    showAgent.value = true;
    agentPagingRef.value?.reload();
};

const checkLogin = () => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/pages/login/login" });
        return;
    }
};

const { proxy }: any = getCurrentInstance();

/**
 * scrollToBottom
 * 1. disabledScroll 为 true 时直接跳过
 * 2. 标记 isProgrammaticScroll，避免代码滚动被误判为用户手动向上滑动
 * 3. 解决 scrollTop 值相同时 scroll-view 不响应的问题：先赋 height-1，再赋 height
 */
const scrollToBottom = async () => {
    if (disabledScroll.value) return;

    await nextTick();
    getRect(".content-box", false, proxy).then((res: any) => {
        const targetTop = res.height;

        // ✅ 先同步设置标记，再修改 scrollTop，避免时序问题
        isProgrammaticScroll.value = true;

        if (scrollTop.value === targetTop) {
            scrollTop.value = targetTop - 1;
            nextTick(() => {
                scrollTop.value = targetTop;
            });
        } else {
            scrollTop.value = targetTop;
        }

        // ✅ 用固定延迟重置，覆盖滚动动画时间
        setTimeout(() => {
            isProgrammaticScroll.value = false;
            // 同步一次实际位置，给下次方向判断用
            previousScrollTop.value = targetTop;
        }, 300);
    });
};

const deleteFile = (index: number) => {
    fileList.value.splice(index, 1);
};

const setUserInput = (value = "") => {
    userInput.value = value;
};

const textareaRef = shallowRef();
const inputBlur = () => {
    textareaRef.value?.blur && textareaRef.value?.blur();
    uni.hideKeyboard();
};

const getChatConfig = async () => {
    if (!currModel.value?.model_id) {
        const res = getAIModels.value[0];
        currModel.value = res ? JSON.parse(JSON.stringify(res)) : {};
    }
    if (currModel.value?.model_id) {
        const res = await getUserChatConfig({
            model_id: currModel.value.model_id,
            model_sub_id: currModel.value.model_sub_id,
        });
        Object.keys(res).forEach((key) => {
            res[key] = parseFloat(res[key]);
        });
        setFormData(res, humanizeParams);
    }
};

const agentList = ref<any[]>([]);
const agentPagingRef = shallowRef();
const getAgentList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getAgentListApi({ page_no, page_size });
        agentPagingRef.value?.complete(lists);
    } catch (error: any) {
        agentPagingRef.value?.complete([]);
    }
};

const toPage = (page: string) => {
    uni.$u.route({ url: `/ai_modules/${page}/pages/index/index` });
};

watch(
    () => appStore.getAiModelConfig,
    (val) => {
        if (val) getChatConfig();
    },
    { deep: true, immediate: true }
);

watch(
    () => props.contentList,
    (newVal) => {
        // 列表清空时，重置所有滚动状态
        if (newVal.length === 0) {
            disabledScroll.value = false;
            previousScrollTop.value = 0;
            isProgrammaticScroll.value = false;
            scrollTop.value = 0;
        }
    },
    { deep: false }
);

onUnmounted(() => {
    chatClose();
    hideKeyboard();
    // 清理防抖定时器，避免内存泄漏
    if (scrollToLowerTimer) clearTimeout(scrollToLowerTimer);
});

defineExpose({
    scrollToBottom,
    resetScroll: () => {
        disabledScroll.value = false;
        previousScrollTop.value = 0;
        isProgrammaticScroll.value = false;
        scrollTop.value = 0;
    },
    setUserInput,
    getChatConfig: () => {
        return {
            model_id: currModel.value.model_id || undefined,
            model_sub_id: currModel.value.model_sub_id || undefined,
            robot_id: currAgent.id || undefined,
            ...humanizeParams,
        };
    },
    setAgentConfig: (params: any) => {
        setFormData(params, currAgent);
    },
    handleAgent,
    handleAgentClear,
    handleModel,
    handleModelClear,
    hideKeyboard,
    openKeyboard: () => {},
});
</script>

<style lang="scss" scoped>
.send-btn {
    @apply w-[60rpx] h-[60rpx] rounded-full flex items-center justify-center;
}
.agent-item {
    @apply flex gap-x-4 items-center bg-white rounded-[24rpx] p-[24rpx] border border-solid border-[#EFEFEF];
    box-shadow: 0px 2px 4px #eff3f8;
    &.active {
        @apply border-primary bg-primary-light-9;
    }
}
textarea {
    height: inherit;
}
</style>
