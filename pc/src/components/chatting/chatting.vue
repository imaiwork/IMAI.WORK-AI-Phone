<template>
    <div class="h-full flex flex-col w-full">
        <div class="h-full flex-1 flex flex-col min-h-0 relative py-5" v-if="contentList.length">
            <ElScrollbar ref="scrollbarRef" height="100%" @scroll="scroll">
                <div class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto h-full">
                    <div ref="containerRef" class="contentList">
                        <div v-for="(item, index) in contentList" :key="index" class="py-[10px]">
                            <div class="message-contain message--his" v-if="item.type === 2">
                                <chat-msg-item
                                    :avatar="item.form_avatar"
                                    :type="item.type"
                                    :loading="item.loading"
                                    :stopping="!!item.reply"
                                    :consume-tokens="item.consume_tokens"
                                    @copy-content="copyContent(item.reply || item.error)">
                                    <template #rob>
                                        <chat-content
                                            :type="item.type"
                                            :loading="item.loading"
                                            :content="item.reply"
                                            :reasoning-content="item.reasoning_content"
                                            :is-reasoning-finished="item.is_reasoning_finished"
                                            :use-markdown="true"
                                            :index="index"
                                            :error="item.error" />
                                    </template>
                                </chat-msg-item>
                            </div>
                            <div class="flex w-full flex-col gap-1 items-end rtl:items-start">
                                <div class="message-contain message--my max-w-[70%]" v-if="item.type === 1">
                                    <chat-msg-item
                                        :type="item.type"
                                        :avatar="item.form_avatar"
                                        :file-lists="item.fileList"
                                        :message="item.message"
                                        @copy-my-content="copyContent(item.message)">
                                        <template #my>
                                            <chat-content :type="item.type" :content="item.message" />
                                        </template>
                                    </chat-msg-item>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div
            class="w-full"
            :class="[contentList.length == 0 ? 'flex-1 flex flex-col items-center justify-center' : 'flex-none mt-2']">
            <slot name="content" v-if="contentList.length == 0" class="mb-6"></slot>
            <div class="w-full">
                <div class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto mb-4 relative">
                    <div class="flex flex-col">
                        <slot name="chat-area-top" />
                        <div class="flex items-center mb-3 relative h-10" v-if="isNewChat">
                            <div class="w-full h-full flex items-center justify-center absolute z-[9]" v-if="isNewChat">
                                <div
                                    class="bg-white rounded-xl w-[114px] h-8 flex items-center justify-center shadow-[0_0_4px_1px_rgba(0,0,0,0.05)] gap-x-1 cursor-pointer hover:bg-[#F5F5F5]"
                                    @click="emit('newChat')">
                                    <Icon name="local-icon-message" color="#989898"></Icon>
                                    <span class="text-[#989898] text-xs">开启全新对话</span>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-white rounded-[24px] border border-[#EBEBEB]"
                            :class="{
                                'shadow-[0_2px_6px_0px_rgba(0,0,0,0.04)]': !showChattingBottom,
                            }">
                            <div
                                class="h-[80px] border-b-[1px] border-[#EBEBEB] w-full px-2"
                                v-if="fileList.length > 0">
                                <file-lists v-model:file-list="fileList" />
                            </div>
                            <div class="flex items-end cursor-pointer px-[18px] relative">
                                <div class="py-[12px] flex-1 mr-8">
                                    <slot name="input" v-if="$slots.input"></slot>
                                    <ElInput
                                        v-else
                                        v-model="inputContent"
                                        type="textarea"
                                        class="content-ipt transition-all duration-300"
                                        resize="none"
                                        :autosize="{
                                            minRows: 1,
                                            maxRows: 10,
                                        }"
                                        :placeholder="placeholder"
                                        @keydown="handleInputEnter" />
                                </div>
                            </div>
                            <div
                                class="flex items-center p-[6px]"
                                :class="[showChattingBottom ? 'justify-between' : 'justify-end']">
                                <div class="flex items-center gap-x-2" v-if="showChattingBottom">
                                    <div
                                        class="flex items-center justify-center gap-x-1 rounded-full h-[34px] px-[12px] hover:bg-[#00000008] cursor-pointer border border-[#EBEBEB]"
                                        :class="{
                                            '!bg-[#1ba7991a] !text-[#1BA799]': selectedNetwork,
                                        }"
                                        v-if="isNetwork"
                                        @click="handleNetwork">
                                        <Icon name="local-icon-network" color="#1BA799" :size="14"></Icon>
                                        <span class="text-xs">联网搜索</span>
                                    </div>
                                    <file-upload
                                        v-model="fileList"
                                        :file-limit="fileLimit"
                                        :accept="uploadAccept"
                                        @update:modelValue="emit('update:fileList', fileList)">
                                        <div
                                            class="flex items-center justify-center gap-x-1 rounded-full h-[34px] px-[12px] hover:bg-[#00000008] cursor-pointer border border-[#EBEBEB]">
                                            <Icon name="local-icon-note_book" color="#FF7919" :size="14"></Icon>
                                            <span class="text-xs">文件上传</span>
                                        </div>
                                    </file-upload>
                                    <div class="flex items-center gap-x-3 relative z-[10]" v-if="!isDisabledHumanize">
                                        <ElSelect
                                            v-model="currModel.model_id"
                                            class="ai-model-select"
                                            popper-class="ai-model-popper"
                                            :show-arrow="false"
                                            @change="handleModelChange">
                                            <ElOption
                                                v-for="(item, index) in getAIModels"
                                                :key="index"
                                                :label="item.name"
                                                :value="item.model_id">
                                                <div class="flex items-center gap-x-2">
                                                    <img
                                                        :src="item.logo"
                                                        class="w-[18px] h-[18px] rounded-md object-cover" />
                                                    <span class="text-xs text-black">{{ item.name }}</span>
                                                </div>
                                            </ElOption>
                                            <template #label="{ label, value }">
                                                <div class="flex items-center gap-x-2">
                                                    <img
                                                        :src="getCurrModel.logo"
                                                        class="w-[18px] h-[18px] rounded-full object-cover" />
                                                    <span class="text-xs text-black">{{ getCurrModel.name }}</span>
                                                </div>
                                            </template>
                                        </ElSelect>
                                        <humanize-pop
                                            ref="humanizePopRef"
                                            :model-id="currModel.model_id"
                                            :model-sub-id="currModel.model_sub_id" />
                                    </div>
                                </div>
                                <div class="w-8 h-8">
                                    <button
                                        v-if="isStop"
                                        @click="emit('close')"
                                        class="flex w-full h-full items-center justify-center rounded-full bg-primary-light-9">
                                        <Icon name="local-icon-chat_stop" :size="18"></Icon>
                                    </button>
                                    <button
                                        v-else
                                        :disabled="isSendDisabled"
                                        class="flex w-full h-full items-center justify-center rounded-full bg-primary-light-9 text-white disabled:bg-[#F6F6F6] disabled:text-[#f4f4f4] disabled:hover:opacity-100"
                                        @click="contentPost">
                                        <Icon
                                            name="local-icon-arrow_up"
                                            :color="isSendDisabled ? '#a9a9a9' : 'var(--color-primary)'"
                                            :size="18"></Icon>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <slot name="chat-area-bottom" />
                    </div>
                </div>
            </div>
            <div class="flex justify-center">
                <div class="text-xs flex justify-center mb-8 gap-2 items-center p-1 bg-[#00000008] rounded-full">
                    <Icon name="local-icon-tips2" :size="16"></Icon>
                    <span class="text-[#0000004d] text-xs">免责声明：内容由AI大模型生成，请仔细甄别。</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElInput, ElScrollbar } from "element-plus";
import feedback from "@/utils/feedback";
import { useElementSize } from "@vueuse/core";
import cloneDeep from "lodash/cloneDeep";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { FileParams } from "@/composables/usePasteImage";
import FileUpload from "./file-upload.vue";
import FileLists from "./file-lists.vue";
import HumanizePop from "./humanize-pop.vue";

const emit = defineEmits(["contentPost", "close", "top", "update:fileList", "newChat", "update:network"]);
const props = defineProps({
    contentList: {
        type: Array as any,
        default: () => [],
    },
    sendDisabled: {
        type: Boolean,
    },
    isStop: {
        type: Boolean,
    },
    avatar: {
        type: String,
    },
    placeholder: {
        type: String,
        default: "在这里输入任何问题 ...",
    },
    isNetwork: {
        type: Boolean,
    },
    isUploadFile: {
        type: Boolean,
        default: true,
    },
    isDisabledHumanize: {
        type: Boolean,
        default: false,
    },
    isNewChat: {
        type: Boolean,
        default: false,
    },
    isAuthLogin: {
        type: Boolean,
        default: true,
    },
});

const appStore = useAppStore();
const { getAiModelConfig } = appStore;

const userStore = useUserStore();
const { isLogin, toggleShowLogin } = userStore;

const currModel = ref({
    id: "",
    name: "",
    model_id: "",
    model_sub_id: "",
});

const getCurrModel = computed(() => {
    return getAIModels.value.find((item) => item.model_id == currModel.value.model_id);
});

const humanizePopRef = shallowRef<InstanceType<typeof HumanizePop>>();

//输入框输入内容.
const inputContent = ref("");
const containerRef = ref<HTMLDivElement>(null);
//滚动条ref
const scrollbarRef = ref<InstanceType<typeof ElScrollbar>>(null);

const fileList = ref<FileParams[]>([]);
const fileLimit = ref(1);
const fileIsLoad = ref(false);

const uploadAccept = computed(() => {
    return ".html,.xml,.doc,.docx,.txt,.pdf,.csv,.xlsx";
});

const previousScrollTop = ref(0);
const disabledScroll = ref(false);

const getAIModels = computed(() => {
    const models = cloneDeep((getAiModelConfig?.channel || []).filter((item) => item.status == "1"));
    models.length && (currModel.value = cloneDeep(models[0]));
    return models;
});

const showChattingBottom = computed(() => {
    return props.isUploadFile && props.isNetwork;
});

// 判断发送框是否禁用
const isSendDisabled = computed(() => {
    // 发送框禁用 1. 发送框禁用 2. 输入框为空 3. 文件列表为空 4. 文件上传中
    const flag = fileList.value.length === 0 ? !inputContent.value : !fileIsLoad.value;
    return props.sendDisabled || flag;
});

const handleModelChange = (value: string) => {
    const data = getAiModelConfig?.channel?.find((item) => item.model_id == value);
    if (data) {
        Object.assign(currModel.value, data);
    }
};

//复制文本
const { copy } = useCopy();
const copyContent = async (content) => {
    await copy(content);
};

const selectedNetwork = ref(false);
const handleNetwork = () => {
    selectedNetwork.value = !selectedNetwork.value;
    emit("update:network", selectedNetwork.value);
};

//计算滚动到底部高度
const toScrollHeight = () => {
    return scrollbarRef.value?.wrapRef.scrollHeight - scrollbarRef.value?.wrapRef.clientHeight;
};

//对话框滚动
const scroll = (value) => {
    const currentScrollTop = value.scrollTop;
    if (currentScrollTop < previousScrollTop.value) {
        disabledScroll.value = true;
    } else if (currentScrollTop > previousScrollTop.value - 50) {
        disabledScroll.value = false;
    }
    previousScrollTop.value = value.scrollTop;
    refresh(value);
};

//滚动至顶部加载
const refresh = ({ scrollTop }) => {
    if (scrollTop == 0) {
        emit("top");
    }
};

// 重置滚动
const resetScroll = () => {
    disabledScroll.value = false;
    previousScrollTop.value = 0;
};

//滚动到底部
const scrollToBottom = async () => {
    if (disabledScroll.value) return;
    const scrollH = toScrollHeight();
    await nextTick();
    scrollbarRef.value?.setScrollTop(scrollH);
};

// 滚动到指定位置
const scrollTo = async (top: number) => {
    await nextTick();
    scrollbarRef.value?.setScrollTop(top);
};

const { height } = useElementSize(containerRef);
watch(height, (value) => {
    scrollToBottom();
});

//清空输入框
const cleanInput = () => {
    inputContent.value = "";
    fileList.value = [];
    fileIsLoad.value = false;
};

// 设置输入
const setInput = (val: string) => {
    inputContent.value = val;
};

//点击回车键
const handleInputEnter = (e: any) => {
    if (e.shiftKey && e.keyCode === 13) {
        return;
    }
    if (!isLogin && props.isAuthLogin) {
        toggleShowLogin();
        return;
    }
    if (e.keyCode === 13) {
        contentPost();
        return e.preventDefault();
    }
};

//发送
const contentPost = () => {
    if (inputContent.value.replace(/(^\s*)|(\s*$)/g, "") == "" && fileList.value.length == 0) {
        feedback.msgError("输入为空！");
        return;
    }

    if (props.sendDisabled) return;
    if (!fileIsLoad.value && fileList.value.length > 0) {
        feedback.msgError("文件正在上传中...");
        return;
    }

    emit("contentPost", inputContent.value);
    resetScroll();
    nextTick(() => {
        scrollToBottom();
    });
    cleanInput();
};

watch(
    () => fileList.value,
    (value) => {
        fileIsLoad.value = value?.some((item) => item.status === UPLOAD_STATUS.SUCCESS);
    },
    {
        deep: true,
    }
);

defineExpose({
    scrollToBottom,
    scrollTo,
    resetScroll,
    cleanInput,
    setInput,
    getChatConfig: () => {
        return {
            ...humanizePopRef.value?.formData,
            model_id: currModel.value.model_id || undefined,
            model_sub_id: currModel.value.model_sub_id || undefined,
        };
    },
});
</script>

<style scoped lang="scss">
.content-ipt {
    :deep(.el-textarea__inner) {
        @apply px-0 text-base text-gray-950;
        transition: all;
        transition-duration: 300ms;
        box-shadow: none;
        background-color: transparent;
        &::-webkit-scrollbar {
            cursor: pointer;
            width: 8px;
            background-color: #f5f5f5;
        }
        &::-webkit-scrollbar-thumb {
            cursor: pointer;
            background-color: #ccc;
            border-radius: 4px;
        }
        &::placeholder {
            @apply text-[#CACACA];
        }
        &.is-focus {
            min-height: 100px !important;
        }
    }
}
:deep(.el-scrollbar__view) {
    @apply h-full;
}

:deep(.ai-model-select) {
    width: 135px;
    .el-select__wrapper {
        min-height: 34px;
        border-radius: 20px;
        box-shadow: none;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
}
textarea {
    resize: none;
    &:focus-visible {
        outline: none;
    }
}
</style>
<style lang="scss">
.ai-model-popper.el-popper {
    @apply rounded-xl w-[240px];
    .el-select-dropdown {
        @apply py-1;
        .el-select-dropdown__list {
            @apply flex flex-col gap-y-2 pr-4 pl-2;
        }
        .el-select-dropdown__item {
            @apply rounded-md h-10 flex items-center px-4;
            &:hover,
            &.is-selected {
                @apply bg-[#f6f6f6];
            }
        }
    }
}
</style>
