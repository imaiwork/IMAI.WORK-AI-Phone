<template>
    <div class="chatting" ref="chattingRef">
        <div class="grow relative min-h-0" :class="{ 'pb-[200px]': showShare }" v-if="contentList.length">
            <div ref="scrollContainerRef" class="scroll-container" @scroll="scroll">
                <div
                    class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto"
                    :style="contentContainerStyle">
                    <div ref="containerRef" :class="{ 'space-y-1': showShare }">
                        <div
                            v-for="(item, index) in contentList"
                            :key="index"
                            :ref="(el) => setChatMessageRef(el, index)"
                            class="chat-message"
                            :class="{ 'is-share': showShare, 'is-selected': shareContentIndexList.includes(index) }"
                            @click="handleItem(index)">
                            <div class="message-contain message--his" v-if="item.type === 2">
                                <div
                                    class="absolute top-1/2 left-[10px] translate-y-[-50%] cursor-pointer"
                                    v-if="showShare">
                                    <Icon
                                        name="local-icon-checkbox"
                                        color="#8A939D"
                                        :size="16"
                                        v-if="!shareContentIndexList.includes(index)" />
                                    <Icon name="local-icon-checkbox_s" color="var(--color-primary)" :size="16" v-else />
                                </div>
                                <chat-msg-item
                                    :avatar="item.form_avatar"
                                    :type="item.type"
                                    :loading="item.loading"
                                    :stopping="!!item.reply"
                                    :consume-tokens="item.consume_tokens"
                                    :show-edit="isEdit && !showShare"
                                    :show-quote="isQuote && !showShare"
                                    :show-share="isShare && !showShare"
                                    :show-copy="!showShare"
                                    @share="handleShare(item)"
                                    @quote="handleQuote(item)"
                                    @edit="handleEdit(item)"
                                    @copy-content="copyContent($event, item.reply || item.error)">
                                    <template #rob>
                                        <chat-content
                                            :type="item.type"
                                            :loading="item.loading"
                                            :content="item.reply"
                                            :stop-reply="item.stop_reply"
                                            :reasoning-content="item.reasoning_content"
                                            :is-reasoning-finished="item.is_reasoning_finished"
                                            :use-markdown="true"
                                            :index="index"
                                            :error="item.error" />
                                    </template>
                                </chat-msg-item>
                            </div>
                            <div class="flex w-full flex-col gap-1 items-end rtl:items-start">
                                <div class="max-w-[70%]" v-if="item.type === 1">
                                    <div class="mb-1 flex items-center justify-end" v-if="item.quotes">
                                        <quote-item :quote="item.quotes" />
                                    </div>
                                    <div class="message-contain message--my">
                                        <div
                                            class="absolute top-1/2 left-[10px] translate-y-[-50%] cursor-pointer"
                                            v-if="showShare">
                                            <Icon
                                                name="local-icon-checkbox"
                                                color="#8A939D"
                                                :size="16"
                                                v-if="!shareContentIndexList.includes(index)" />
                                            <Icon
                                                name="local-icon-checkbox_s"
                                                color="var(--color-primary)"
                                                :size="16"
                                                v-else />
                                        </div>
                                        <chat-msg-item
                                            :type="item.type"
                                            :avatar="item.form_avatar"
                                            :file-lists="item.fileList"
                                            :message="item.message"
                                            :show-share="isShare && !showShare"
                                            :show-edit="isEdit && !showShare"
                                            :show-quote="isQuote && !showShare"
                                            :show-copy="!showShare"
                                            @share="handleShare(item)"
                                            @quote="handleQuote(item)"
                                            @edit="handleEdit(item)"
                                            @copy-my-content="copyContent($event, item.message)">
                                            <template #my>
                                                <chat-content
                                                    :type="item.type"
                                                    :content="item.message"
                                                    :quotes="item.quotes" />
                                            </template>
                                        </chat-msg-item>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Transition name="fade-slide-up">
                <div v-if="showBackToBottom && !showShare" class="back-to-bottom-btn" @click="handleBackToBottom">
                    <Icon name="el-icon-ArrowDown" :size="20" />
                    <span class="back-to-bottom-text">回到底部</span>
                </div>
            </Transition>
        </div>

        <div
            v-show="!showShare"
            ref="chattingAreaRef"
            class="w-full"
            :class="[contentList.length == 0 ? 'flex-1 flex flex-col items-center justify-center' : 'flex-none mt-2']">
            <slot name="content" v-if="contentList.length == 0" class="mb-6"></slot>
            <div class="w-full">
                <div class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto mb-4 relative">
                    <slot name="customSendArea" v-if="$slots.customSendArea"></slot>
                    <div class="flex flex-col" v-else>
                        <slot name="chat-area-top" />
                        <div
                            class="bg-white rounded-[24px] border border-[#EBEBEB]"
                            :class="{
                                'shadow-[0_2px_6px_0px_rgba(0,0,0,0.04)]': !showChattingBottom,
                            }">
                            <div class="px-[18px] py-2 border-b border-br" v-if="quoteContent">
                                <div
                                    class="text-sm text-gray-500 bg-[#F1F2F3] p-2 flex items-center gap-x-2 rounded-md">
                                    <Icon name="local-icon-double_quotes_l" :size="16"></Icon>
                                    <div class="line-clamp-1 break-all flex-1">
                                        {{ quoteContent }}
                                    </div>
                                    <div class="w-4 h-4" @click.stop="quoteContent = ''">
                                        <close-btn :icon-size="12" />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="h-[80px] border-b-[1px] border-[#EBEBEB] w-full px-2"
                                v-if="fileList.length > 0">
                                <file-lists v-model:file-list="fileList" />
                            </div>
                            <div class="flex items-end cursor-text px-[18px] relative">
                                <div class="py-[12px] flex-1">
                                    <slot name="input" v-if="$slots.input"></slot>
                                    <ElInput
                                        v-else
                                        ref="inputRef"
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
                                            '!bg-primary !text-white': selectedNetwork,
                                        }"
                                        v-if="isNetwork"
                                        @click="handleNetwork">
                                        <Icon name="local-icon-network" :size="14"></Icon>
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
                                            :size="18">
                                        </Icon>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <slot name="chat-area-bottom" />
                    </div>
                </div>
            </div>
            <div class="flex justify-center">
                <div class="text-xs flex justify-center mb-2 gap-2 items-center p-1 bg-[#00000008] rounded-full">
                    <Icon name="local-icon-tips2" :size="16"></Icon>
                    <span class="text-[#0000004d] text-xs">免责声明：内容由AI大模型生成,请仔细甄别。</span>
                </div>
            </div>
        </div>

        <Transition name="slide-up">
            <div class="share-floating-bar" v-if="showShare">
                <div class="share-glass-container">
                    <div class="flex items-center gap-3 shrink-0">
                        <div class="share-icon-box" @click="handleSelectAll">
                            <Icon
                                :name="isAllSelected ? 'local-icon-checkbox_s' : 'local-icon-checkbox'"
                                color="#0065fb"
                                :size="18" />
                            <span class="share-label ml-2">全选</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="share-label">SELECTED</span>
                            <span class="share-count-text">
                                已选 <span class="text-primary">{{ shareContentIndexList.length }}</span> 条
                            </span>
                        </div>
                    </div>

                    <div class="share-divider"></div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button class="action-item-btn group" v-if="false" @click="handleGenerateLink">
                            <div
                                class="action-icon bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white">
                                <Icon name="el-icon-Link" :size="16" />
                            </div>
                            <span class="action-label">分享链接</span>
                        </button>

                        <button class="action-item-btn group" @click="handleGenerateImage">
                            <div
                                class="action-icon bg-orange-50 text-orange-600 group-hover:bg-orange-600 group-hover:text-white">
                                <Icon name="el-icon-Picture" :size="16" />
                            </div>
                            <span class="action-label">生成图片</span>
                        </button>

                        <button class="action-item-btn group" @click="handleGeneratePDF">
                            <div
                                class="action-icon bg-red-50 text-red-600 group-hover:bg-red-600 group-hover:text-white">
                                <Icon name="el-icon-Document" :size="16" />
                            </div>
                            <span class="action-label">导出 PDF</span>
                        </button>

                        <div class="w-[8px]"></div>

                        <button class="cancel-circle-btn" @click="handleCancelShare" title="取消选择">
                            <Icon name="el-icon-Close" :size="16" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
        <preview-share v-if="showPreviewShare" ref="previewShareRef" />
        <edit-pop v-model:show="showEditDrawer" v-model:content="editContent" />
    </div>
</template>

<script setup lang="ts">
import { ElInput, ElSelect, ElOption } from "element-plus";
import feedback from "@/utils/feedback";
import QRCode from "qrcode";
import removeMarkdown from "remove-markdown";
import cloneDeep from "lodash/cloneDeep";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { FileParams } from "@/composables/usePasteImage";
import jsPDF from "jspdf";
import html2Canvas from "@/utils/html2canvas";
import FileUpload from "./file-upload.vue";
import FileLists from "./file-lists.vue";
import HumanizePop from "./humanize-pop.vue";
import PreviewShare from "./preview-share.vue";
import EditPop from "./edit-pop.vue";
import QuoteItem from "./quote-item.vue";

const emit = defineEmits([
    "contentPost",
    "close",
    "top",
    "update:fileList",
    "newChat",
    "update:network",
    "quote",
    "update:inputContent",
]);

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
    isQuote: {
        type: Boolean,
        default: false,
    },
    isShare: {
        type: Boolean,
        default: false,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
});

const appStore = useAppStore();
const { getAiModelConfig, getWebsiteConfig } = appStore;

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
const chattingAreaRef = ref<HTMLDivElement>(null);
// 新增：滚动容器ref，替代原来的 scrollbarRef
const scrollContainerRef = ref<HTMLDivElement>(null);
// 输入框ref
const inputRef = ref<InstanceType<typeof ElInput>>(null);

const fileList = ref<FileParams[]>([]);
const fileLimit = ref(1);
const fileIsLoad = ref(false);

const uploadAccept = computed(() => {
    return ".html,.xml,.doc,.docx,.txt,.pdf,.csv,.xlsx";
});

const previousScrollTop = ref(0);
const disabledScroll = ref(false);

// 回到底部按钮相关状态
const showBackToBottom = ref(false);
const BACK_TO_BOTTOM_THRESHOLD = 200;

// 内容上推相关状态
const containerCurrentHeight = ref(0);
const chattingContainerHeight = ref(0);
const chatMessageRefs = ref<Map<number, HTMLElement>>(new Map());

// ===== 新增：稳定高度计算 =====
const stableContainerHeight = ref(0);

// 设置聊天消息元素引用
const setChatMessageRef = (el: any, index: number) => {
    if (el) {
        chatMessageRefs.value.set(index, el);
    } else {
        chatMessageRefs.value.delete(index);
    }
};

// ===== 修改：计算内容容器样式 =====
const contentContainerStyle = computed(() => {
    // 分享模式下不应用最小高度，避免生成图片时底部空白
    if (showShare.value) {
        return {
            minHeight: "auto",
        };
    }
    if (props.contentList.length > 0 && containerCurrentHeight.value > 0) {
        return {
            minHeight: `${containerCurrentHeight.value}px`,
        };
    }
});

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
const copyContent = async (type: "markdown" | "text", content) => {
    if (type == "markdown") {
        await copy(content);
    }
    if (type == "text") {
        // 这里要转把markdown的格式转成纯文本
        await copy(removeMarkdown(content));
    }
};

const selectedNetwork = ref(false);
const handleNetwork = () => {
    selectedNetwork.value = !selectedNetwork.value;
    emit("update:network", selectedNetwork.value);
};

// ===== 新增：更新稳定高度的函数 =====
const updateStableHeight = () => {
    if (!scrollContainerRef.value) return;
    stableContainerHeight.value = scrollContainerRef.value.clientHeight;
};

// 触发内容上推效果（用户发送消息后调用）
const triggerContentPushUp = () => {
    nextTick(() => {
        if (!scrollContainerRef.value || !containerRef.value) return;

        // 获取可视区域高度
        const viewportHeight = scrollContainerRef.value.clientHeight;

        // 获取当前内容总高度
        const currentContentHeight = containerRef.value.scrollHeight;

        // 设置容器最小高度，确保旧内容可以被顶上去
        // 最小高度 = 可视高度 + 旧内容高度
        containerCurrentHeight.value = viewportHeight + currentContentHeight;

        // 等待DOM更新后滚动
        setTimeout(() => {
            scrollToBottom(true);
        }, 100);
    });
};

// 修改：计算滚动到底部高度
const toScrollHeight = () => {
    if (!scrollContainerRef.value) return 0;
    return scrollContainerRef.value.scrollHeight - scrollContainerRef.value.clientHeight;
};

// 修改：检查是否接近底部
const checkNearBottom = (scrollTop: number) => {
    if (!scrollContainerRef.value) return;

    const scrollHeight = scrollContainerRef.value.scrollHeight;
    const clientHeight = scrollContainerRef.value.clientHeight;
    const maxScrollTop = scrollHeight - clientHeight;
    const distanceFromBottom = maxScrollTop - scrollTop;

    // 如果距离底部超过阈值，显示回到底部按钮
    // 但是如果AI正在回复且用户主动向上滚动了，也要显示按钮
    const shouldShow = distanceFromBottom > BACK_TO_BOTTOM_THRESHOLD;
    showBackToBottom.value = shouldShow;
};

// 修改：对话框滚动事件处理
const scroll = (event: Event) => {
    const target = event.target as HTMLElement;
    const currentScrollTop = target.scrollTop;

    // 检查是否需要显示回到底部按钮
    checkNearBottom(currentScrollTop);

    // 修正 disabledScroll 逻辑：
    // 如果用户向上滚动超过50px，则禁用自动滚动
    // 如果用户向下滚动，则启用自动滚动
    if (currentScrollTop < previousScrollTop.value - 50) {
        // 用户向上滚动超过50px，禁用自动滚动
        disabledScroll.value = true;
    } else if (currentScrollTop >= previousScrollTop.value) {
        // 用户向下滚动或保持不变，启用自动滚动
        disabledScroll.value = false;
    }

    previousScrollTop.value = currentScrollTop;
    refresh(currentScrollTop);
};

// 修改：滚动至顶部加载
const refresh = (scrollTop: number) => {
    if (scrollTop === 0) {
        emit("top");
    }
};

// 重置滚动
const resetScroll = () => {
    disabledScroll.value = false;
    previousScrollTop.value = 0;
    showBackToBottom.value = false;
};

// 修改：滚动到底部
const scrollToBottom = async (smooth = false) => {
    if (disabledScroll.value || !scrollContainerRef.value) return;

    const scrollH = toScrollHeight();
    await nextTick();
    if (smooth) {
        // 平滑滚动
        scrollContainerRef.value.scrollTo({
            top: scrollH,
            behavior: "smooth",
        });
    } else {
        // 立即滚动
        scrollContainerRef.value.scrollTop = scrollH;
    }

    showBackToBottom.value = false;
};

// 修改：处理回到底部按钮点击
const handleBackToBottom = async () => {
    if (!scrollContainerRef.value) return;

    const scrollH = toScrollHeight();
    await nextTick();

    scrollTo(scrollH);

    showBackToBottom.value = false;
    disabledScroll.value = false;
};

// 修改：滚动到指定位置
const scrollTo = async (top: number, smooth = true) => {
    if (!scrollContainerRef.value) return;

    await nextTick();

    if (smooth) {
        scrollContainerRef.value.scrollTo({
            top: top,
            behavior: "smooth",
        });
    } else {
        scrollContainerRef.value.scrollTop = top;
    }
};

//清空输入框
const cleanInput = () => {
    inputContent.value = "";
    fileList.value = [];
    fileIsLoad.value = false;
    // 清空输入框时，重置内容上推高度
    containerCurrentHeight.value = 0;
    chattingContainerHeight.value = 0;
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
    triggerContentPushUp();

    emit("contentPost", inputContent.value);

    // 用户发送消息后，触发内容上推效果
    resetScroll();
    cleanInput();
};

const handleItem = (index: number) => {
    if (showShare.value) {
        const { error, stop_reply } = props.contentList[index];
        if (error || stop_reply) return;
        handleShareContent(index);
    }
};

//分享
const showShare = ref(false);
const previewShareRef = shallowRef<InstanceType<typeof PreviewShare>>();
const showPreviewShare = ref(false);
// 分享选择的内容索引列表
const shareContentIndexList = ref<number[]>([]);
const handleShare = (item: any) => {
    showShare.value = true;
    shareContentIndexList.value = props.contentList.map((item, index) => !item.error && !item.stop_reply && index);
};

// 计算属性：是否全选
const isAllSelected = computed(() => {
    return shareContentIndexList.value.length === props.contentList.length && props.contentList.length > 0;
});

// 全选/取消全选
const handleSelectAll = () => {
    if (isAllSelected.value) {
        // 取消全选
        shareContentIndexList.value = [];
    } else {
        // 全选
        shareContentIndexList.value = props.contentList.map((_, index) => index);
    }
};

// 取消分享模式
const handleCancelShare = () => {
    showShare.value = false;
    shareContentIndexList.value = [];
};

const generateShareContent = async (isPDF = false) => {
    if (shareContentIndexList.value.length === 0) {
        feedback.msgError("请选择要导出的内容");
        return;
    }

    showPreviewShare.value = true;
    await nextTick();

    try {
        const clonedElement = containerRef.value.cloneNode(true) as HTMLElement;

        const chatMessages = clonedElement.querySelectorAll(".chat-message");
        chatMessages.forEach((el, index) => {
            if (!shareContentIndexList.value.includes(index)) {
                el.remove();
            } else {
                el.classList.remove("is-selected");
                el.classList.remove("is-share");
                (el as HTMLElement).style.backgroundColor = "transparent";
            }
        });

        const container = document.createElement("div");
        container.style.position = "absolute";
        container.style.left = "-9999px";
        container.style.top = "0";

        container.style.width = `${containerRef.value.offsetWidth}px`;
        container.style.height = "auto"; // 高度必须自适应
        container.style.backgroundColor = "#FFFFFF";

        const websiteNameContainer = document.createElement("div");
        websiteNameContainer.style.padding = "20px";
        websiteNameContainer.innerHTML = `
            <div style="height: 100px; display: flex; align-items: center; justify-content: center; gap: 10px; border-bottom: 1px solid #DDDEE0; margin-bottom: 20px;">
                <img src="${getWebsiteConfig.shop_logo}" style="width: 60px; height: 60px; border-radius: 50%;" />
                <div style="font-size: 24px; font-weight: bold;">${getWebsiteConfig.shop_name}</div>
            </div>
        `;
        container.appendChild(websiteNameContainer);

        container.appendChild(clonedElement);

        const qrcodeDataURL = await QRCode.toDataURL(window.location.href, { width: 256 });
        const shareBottomContainer = document.createElement("div");
        shareBottomContainer.innerHTML = `
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 0;">
                <div style="width: 100px; height: 100px; border: 1px solid #E5E7EB; border-radius: 4px; padding: 4px; background-color: #FFFFFF;">
                    <img src="${qrcodeDataURL}" alt="QRCode" style="width: 100%; height: 100%;" />
                </div>
                <div style="margin-top: 10px; color: #666;">${getWebsiteConfig.shop_name}</div>
            </div>
        `;
        container.appendChild(shareBottomContainer);

        document.body.appendChild(container);

        await nextTick();

        if (isPDF) {
            const canvas = await html2Canvas(container, {
                useCORS: true,
                backgroundColor: "#FFFFFF",
                scale: 2,
            });

            const imgData = canvas.toDataURL("image/png");
            const contentWidth = container.offsetWidth;
            const contentHeight = container.offsetHeight;

            const pdf = new jsPDF("p", "mm", "a4");
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();

            const imgHeightInPdf = (contentHeight * pdfWidth) / contentWidth;

            let heightLeft = imgHeightInPdf;
            let position = 0;

            pdf.addImage(imgData, "PNG", 0, position, pdfWidth, imgHeightInPdf);
            heightLeft -= pdfHeight;

            while (heightLeft > 0) {
                position = heightLeft - imgHeightInPdf;
                pdf.addPage();
                pdf.addImage(imgData, "PNG", 0, position, pdfWidth, imgHeightInPdf);
                heightLeft -= pdfHeight;
            }

            pdf.save(`${getWebsiteConfig.shop_name}-对话记录.pdf`);
            feedback.msgSuccess("PDF生成成功！");
        } else {
            const canvas = await html2Canvas(container, {
                useCORS: true,
                backgroundColor: "#ffffff",
                scale: 2,
            });
            const dataURL = canvas.toDataURL("image/png", 1.0);
            previewShareRef.value.open();
            previewShareRef.value.setContent(dataURL);
        }

        document.body.removeChild(container);
    } catch (error) {
        console.error(`${isPDF ? "PDF" : "图片"}生成失败:`, error);
        feedback.msgError("导出失败，请重试");
    }
};

const handleGenerateImage = async () => {
    await generateShareContent(false);
};

const handleGeneratePDF = async () => {
    await generateShareContent(true);
};

// 生成链接
const handleGenerateLink = () => {
    if (shareContentIndexList.value.length === 0) return;
};

const handleShareContent = (index: number) => {
    const currentItem = props.contentList[index];
    const currentType = currentItem.type;

    const isCurrentSelected = shareContentIndexList.value.includes(index);

    if (currentType === 1) {
        const nextIndex = index + 1;
        const nextItem = props.contentList[nextIndex];

        if (isCurrentSelected) {
            shareContentIndexList.value = shareContentIndexList.value.filter((i) => i !== index);
            if (nextItem && nextItem.type === 2) {
                shareContentIndexList.value = shareContentIndexList.value.filter((i) => i !== nextIndex);
            }
        } else {
            shareContentIndexList.value.push(index);
            if (nextItem && nextItem.type === 2) {
                shareContentIndexList.value.push(nextIndex);
            }
        }
    } else if (currentType === 2) {
        const prevIndex = index - 1;
        const prevItem = props.contentList[prevIndex];

        if (isCurrentSelected) {
            shareContentIndexList.value = shareContentIndexList.value.filter((i) => i !== index);
            if (prevItem && prevItem.type === 1) {
                shareContentIndexList.value = shareContentIndexList.value.filter((i) => i !== prevIndex);
            }
        } else {
            shareContentIndexList.value.push(index);
            if (prevItem && prevItem.type === 1) {
                shareContentIndexList.value.push(prevIndex);
            }
        }
    }

    shareContentIndexList.value = [...new Set(shareContentIndexList.value)].sort((a, b) => a - b);
};

const quoteContent = ref("");
const handleQuote = (item: any) => {
    const { type, message, reply } = item;
    if (type === 1) {
        quoteContent.value = `**${message}**`;
    } else if (type === 2) {
        quoteContent.value = reply;
    }
    emit("quote", quoteContent.value);
};

const clearQuote = () => {
    quoteContent.value = "";
};

const showEditDrawer = ref(false);
const editContent = ref("");
const handleEdit = (item: any) => {
    const { type, message, reply } = item;
    if (type === 1) {
        setInput(message);
        emit("update:inputContent", message);
    } else if (type === 2) {
        showEditDrawer.value = true;
        editContent.value = reply;
    }
};

// ===== 新增：生命周期钩子 =====
onMounted(() => {
    nextTick(() => {
        updateStableHeight();
    });

    // 监听窗口大小变化
    window.addEventListener("resize", updateStableHeight);
});

onUnmounted(() => {
    window.removeEventListener("resize", updateStableHeight);
});

// 监听文件列表变化
watch(
    () => fileList.value,
    (value) => {
        fileIsLoad.value = value?.some((item) => item.status === UPLOAD_STATUS.SUCCESS);
    },
    {
        deep: true,
    }
);

// 监听对话列表变化 - 但不在这里触发内容上推
watch(
    () => props.contentList,
    (newVal, oldVal) => {
        if (newVal.length === 0) {
            containerCurrentHeight.value = 0; // 重置高度
        }
        shareContentIndexList.value = [];
        showShare.value = false;
        if (!disabledScroll.value) {
            showBackToBottom.value = false;
        }
    },
    {
        deep: true,
    }
);

watch(
    () => showShare.value,
    (newVal) => {
        if (newVal) {
            containerCurrentHeight.value = 0;
            chattingContainerHeight.value = 0;
        }
    }
);

defineExpose({
    scrollToBottom,
    scrollTo,
    resetScroll,
    cleanInput,
    setInput,
    clearQuote,
    triggerContentPushUp,
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
.chatting {
    @apply h-full flex flex-col w-full relative;
}

/* 新增：滚动容器样式 */
.scroll-container {
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;

    /* 自定义滚动条样式 */
    &::-webkit-scrollbar {
        width: 8px;
    }

    &::-webkit-scrollbar-track {
        background: #f5f5f5;
        border-radius: 4px;
    }

    &::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 4px;

        &:hover {
            background: #999;
        }
    }
}

.chat-message {
    @apply py-[10px];
    animation: fade-in 0.3s ease-out forwards;
    will-change: transform, opacity;

    &.is-share {
        @apply pl-10 pt-4 pb-3 pr-3 relative rounded-lg hover:bg-[#F5F5F5] cursor-pointer;
    }

    &.is-selected {
        @apply bg-[#F5F5F5];
    }
}

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

        // &.is-focus {
        //     min-height: 100px !important;
        // }
    }
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

/* 回到底部按钮样式 */
.back-to-bottom-btn {
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: #666;
    font-size: 14px;
    font-weight: 500;

    &:hover {
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
        color: var(--color-primary, #0065fb);
    }

    &:active {
        transform: translateY(0);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
}

.back-to-bottom-text {
    white-space: nowrap;
}

/* 回到底部按钮动画 */
.fade-slide-up-enter-active,
.fade-slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-up-enter-from,
.fade-slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.9);
}

.fade-slide-up-enter-to,
.fade-slide-up-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.share-floating-bar {
    position: absolute;
    bottom: 32px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2000;
    pointer-events: none;
}

.share-glass-container {
    pointer-events: auto;
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.05);
    min-width: 420px;
}

.share-floating-bar {
    position: absolute;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2000;
}

.share-glass-container {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 10px 14px 10px 24px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.share-icon-box {
    min-width: 36px;
    height: 36px;
    border-radius: 10px;
    padding: 0 6px;
    background: #0065fb10;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.share-label {
    font-size: 9px;
    font-weight: 900;
    color: #94a3b8;
    letter-spacing: 0.1em;
}

.share-count-text {
    font-size: 13px;
    font-weight: 1000;
    color: #1e293b;
}

.share-divider {
    width: 1px;
    height: 28px;
    background: #f1f5f9;
}

/* 功能按钮样式 */
.action-item-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px 6px 6px;
    border-radius: 14px;
    transition: all 0.2s;
    border: 1px solid transparent;

    &:hover:not(:disabled) {
        background: #fff;
        border-color: #f1f5f9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transform: translateY(-1px);
    }

    &:active {
        transform: translateY(0);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

.action-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.action-label {
    font-size: 13px;
    font-weight: 900;
    color: #475569;
}

/* 取消按钮 */
.cancel-circle-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    color: #94a3b8;
    transition: all 0.2s;

    &:hover {
        background: #ef4444;
        color: white;
    }
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translate(-50%, 30px) scale(0.9);
}

.message-contain {
    transition: all 0.3s ease;

    &.share-active {
        @apply ring-2 ring-[#0065fb]/20 bg-[#0065fb]/5;
    }
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
