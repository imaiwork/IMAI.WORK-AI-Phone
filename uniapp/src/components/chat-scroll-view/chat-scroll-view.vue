<template>
    <view class="chat-scroll-view h-full flex flex-col min-h-0">
        <view class="flex flex-col flex-1 min-h-0 py-4 relative" v-if="contentList.length">
            <view class="scroll-view-content flex-1 flex min-h-0">
                <scroll-view scroll-y ref="contentRef" :scroll-top="scrollTop">
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
            <view class="w-full flex justify-center mb-2 absolute bottom-0" v-if="dynamicHeight == 0">
                <view
                    class="flex items-center justify-center rounded-full px-[16rpx] h-[76rpx] w-[228rpx] border border-solid border-[#EDEDEE]"
                    @click="chatAdd()">
                    <text class="text-xs text-[#989898]">开启全新对话</text>
                </view>
            </view>
        </view>
        <view
            class="flex flex-col justify-center px-[20rpx] mb-[20rpx]"
            :class="{
                'flex-1': contentList.length == 0,
            }">
            <view
                :class="{
                    'flex-1 flex flex-col items-center justify-center': isCoze || isStaff,
                }">
                <slot name="content"></slot>
            </view>
            <view class="relative z-[79] chatBottomBox">
                <view class="flex flex-col">
                    <view class="mb-2" v-if="currModel.id && !currAgent.id && !isCoze && !isStaff">
                        <view
                            class="text-xs text-[#808080] bg-[#f6f6f6] rounded-[100rpx] pl-2 pr-4 h-[66rpx] inline-flex items-center gap-x-1"
                            @click="showModel = true">
                            <image :src="currModel.logo" class="w-[32rpx] h-[32rpx] rounded-full"></image>
                            {{ currModel.name }}
                            <view class="ml-1 inline-block">
                                <u-icon name="arrow-down" size="20" color="#a8abb2"></u-icon>
                            </view>
                        </view>
                    </view>
                    <view class="mb-2" v-if="currAgent.id && !isCoze && !isStaff">
                        <view
                            class="bg-white rounded-[100rpx] px-4 h-[66rpx] gap-x-1 border border-solid border-[#E9EBEC] inline-flex items-center relative"
                            @click="showAgent = true">
                            <image
                                :src="currAgent.avatar"
                                class="w-[32rpx] h-[32rpx] rounded-[24rpx]"
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
                    </view>
                    <view class="flex-1 flex gap-x-2 items-center">
                        <slot name="sendLeft" v-if="$slots.sendLeft && !isInputFocus"></slot>
                        <view
                            class="flex-1 bg-white rounded-tl-[48rpx] rounded-tr-[48rpx] border border-solid border-b-0 border-[#F1F1F2] overflow-hidden relative py-[6rpx]"
                            :class="{
                                'rounded-bl-[48rpx] rounded-br-[48rpx] !border-b': isCoze || isStaff,
                            }">
                            <view v-if="fileList.length" class="p-2 flex">
                                <view v-for="(item, index) in fileList" :key="index">
                                    <FileItem :item="item" :index="index" @on-delete="deleteFile" />
                                </view>
                            </view>
                            <view class="flex">
                                <textarea
                                    class="!w-full max-h-[300rpx] overflow-y-auto text-[26rpx] px-[36rpx] py-[24rpx] transition-all duration-300"
                                    :style="{
                                        minHeight: isInputFocus ? '120rpx' : '0',
                                    }"
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
                                    @focus="handleInputFocus"
                                    @blur="isInputFocus = false"
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
                        v-if="!isCoze && !isStaff"
                        class="flex items-center justify-between gap-x-[12rpx] h-[100rpx] bg-[#F6F6F6] px-[12rpx] rounded-bl-[48rpx] rounded-br-[48rpx] border border-solid border-[#F1F1F2] border-t-0">
                        <view class="flex items-center gap-x-[12rpx] flex-1">
                            <view
                                v-if="isNetwork"
                                class="flex items-center justify-center gap-x-1 w-[168rpx] h-[60rpx] rounded-full text-[#323232]"
                                :class="{
                                    '!bg-[#1ba7991a] !text-[#1ba799]': selectedNetwork,
                                }"
                                @click="handleNetwork">
                                <u-icon name="/static/images/icons/deep.svg" :size="28"></u-icon>
                                <text class="text-xs">联网搜索</text>
                            </view>
                            <view class="h-[28rpx]">
                                <u-line direction="vertical" color="#F1F1F2"></u-line>
                            </view>
                            <view
                                class="flex items-center justify-center gap-x-1 w-[168rpx] h-[60rpx] rounded-full text-[#323232]"
                                @click="handleFileUpload">
                                <u-icon name="/static/images/icons/note_book.svg" :size="28"></u-icon>
                                <text class="text-xs">文件上传</text>
                            </view>
                        </view>
                        <view class="leading-[0] p-2" @click="handleSetting">
                            <u-icon name="/static/images/icons/setting.svg" :size="36"></u-icon>
                        </view>
                    </view>
                    <view class="flex justify-center mt-[40rpx]">
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
                height:
                    contentList.length || isCoze || isStaff
                        ? dynamicHeight + 'px'
                        : dynamicHeight - chatBottomHeight + 'px',
            }"></view>
    </view>
    <popup-bottom
        v-model:show="showHumanize"
        title="参数设置"
        height="85%"
        show-close-btn
        custom-class="bg-white"
        is-disabled-touch>
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
                                <view class="text-xs flex-shrink-0 w-[80rpx] text-center"
                                    >{{ humanizeParams.context_num }}条</view
                                >
                            </view>
                        </view>
                        <view>
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
                        <view v-if="currModel.model_id == ModelIdEnum.GPT_4O">
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
                        <view v-if="currModel.model_id == ModelIdEnum.GPT_4O">
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
                        <view v-if="currModel.model_id == ModelIdEnum.GPT_4O">
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
                        <view v-if="currModel.model_id == ModelIdEnum.GPT_4O">
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
    <popup-bottom v-model:show="showModel" title="选择模型" height="55%">
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="pb-[150rpx]">
                    <view class="p-4 flex flex-col gap-y-4">
                        <view
                            v-for="(item, index) in getAIModels"
                            :key="index"
                            class="border border-solid border-[#E9EBEC] rounded-[10rpx] px-4 py-3 flex items-center gap-x-2"
                            :class="{
                                '!border-primary text-primary font-bold': currModel.id == item.id,
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
    <popup-bottom v-model:show="showAgent" title="选择智能体" height="85%" show-close-btn is-disabled-touch>
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="agentPagingRef"
                    v-model="agentList"
                    :fixed="false"
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
import { getUserChatConfig, saveUserChatConfig } from "@/api/chat";
import { getAgentList as getAgentListApi } from "@/api/agent";
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
        uni.$u.route({
            url: "/pages/login/login",
        });
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

// 判断发送框是否禁用
const isSendDisabled = computed(() => {
    const flag = fileList.value.length === 0 ? !userInput.value : false;
    return props.sendDisabled || flag;
});

const handleFileUpload = () => {
    checkLogin();
    uni.$u.route({
        url: "/packages/pages/choose_file/choose_file",
        params: {
            limit: 1,
        },
    });
};

const showModel = ref(false);
const handleModel = (item: any) => {
    currModel.value = JSON.parse(JSON.stringify(item));
    showModel.value = false;
    getChatConfig();
};

const showAgent = ref(false);
const currAgent = reactive({
    id: "",
    name: "",
    avatar: "",
});

const handleAgent = (item: any) => {
    setFormData(item, currAgent);
    userInput.value = "";
    showAgent.value = false;
};

const handleAgentClear = () => {
    currAgent.id = "";
    currAgent.name = "";
    currAgent.avatar = "";
};
const showHumanize = ref(false);
const humanizeParams = reactive({
    top_p: 0.5, //词汇多样性（0.01-1）
    temperature: 1, //结果相似性（0-2）
    presence_penalty: 0.1, //特定词重复率 (0-1)
    frequency_penalty: 2, //重复词频率(-2到2）
    context_num: 3, //上下文数量（1-5）
    top_logprobs: 10, //显示前几个候选词对数概率(0到20)
    logprobs: 0, //显示候选词 0关闭 1开启
});

const getMaxTemperature = computed(() => {
    if (currModel.value.model_id == ModelIdEnum.GPT_4O) {
        return 2;
    }
    return 1;
});

const changeHumanizeParams = (event: any, key: string, step: number) => {
    let { value } = event.detail;
    if (step == 0) {
        humanizeParams[key as keyof typeof humanizeParams] = value;
    } else {
        //判断value是否为正整数，如果是，则直接显示，不是则保留step位小数
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
};

const handelChatConfig = async () => {
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await saveUserChatConfig({
            model_id: currModel.value.model_id,
            model_sub_id: currModel.value.model_sub_id,
            ...humanizeParams,
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        showHumanize.value = false;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "保存失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const contentRef = shallowRef();

const userInput = ref("");
const scrollTop = ref<number>(0);

const { dynamicHeight } = useKeyboardHeight();

const handleInput = (e: any) => {
    if (userInput.value.indexOf("@") == 0 && userInput.value.length == 1) {
        showAgent.value = true;
    }
};

const isInputFocus = ref(false);
const handleInputFocus = () => {
    isInputFocus.value = true;
};

const contentPost = () => {
    checkLogin();
    if (userInput.value.replace(/(^\s*)|(\s*$)/g, "") == "" && fileList.value.length == 0) {
        uni.$u.toast("输入为空");
        return;
    }
    if (props.sendDisabled) return;
    emit("contentPost", userInput.value);
    nextTick(() => {
        scrollToBottom();
    });
    inputBlur();
    userInput.value = "";
    fileList.value = [];
    emit("update:fileList", []);
};

const chatAdd = () => {
    emit("add-session");
};

const chatClose = () => {
    emit("close");
};

const checkLogin = () => {
    if (!isLogin.value) {
        uni.$u.route({
            url: "/pages/login/login",
        });
        return;
    }
};

const { proxy }: any = getCurrentInstance();
const scrollToBottom = async () => {
    await nextTick();
    getRect(".content-box", false, proxy).then((res: any) => {
        scrollTop.value = res.height;
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
const chatBottomHeight = ref(0);
const getChatBottomHeight = async () => {
    await nextTick();
    const { safeAreaInsets } = uni.$u.sys();
    getRect(".chatBottomBox", false, proxy).then((res: any) => {
        chatBottomHeight.value = res.height + safeAreaInsets.bottom + 100;
    });
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
        const { lists } = await getAgentListApi({
            page_no,
            page_size,
            source: 1,
        });
        agentPagingRef.value?.complete(lists);
    } catch (error: any) {
        agentPagingRef.value?.complete([]);
    }
};

watch(
    () => appStore.getAiModelConfig,
    (val) => {
        if (val) {
            getChatConfig();
        }
    },
    {
        deep: true,
        immediate: true,
    }
);

onMounted(() => {
    getChatBottomHeight();
});

onUnmounted(() => {
    chatClose();
});

defineExpose({
    scrollToBottom,
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
});
</script>

<style lang="scss" scoped>
.chat-scroll-view {
    .send-btn {
        @apply w-[60rpx] h-[60rpx] rounded-full flex items-center justify-center;
    }
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
