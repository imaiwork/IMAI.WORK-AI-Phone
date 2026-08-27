<template>
    <div class="chatting" :class="{ 'is-welcome-send-area': sendAreaVariant === 'welcome' }" ref="chattingRef">
        <div
            class="grow relative min-h-0"
            :class="{ 'pb-[200px]': showShare }"
            v-if="!sendAreaOnly && contentList.length">
            <div ref="scrollContainerRef" class="scroll-container" @scroll="scroll">
                <div
                    class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto"
                    :style="contentContainerStyle">
                    <div ref="containerRef" :class="{ 'space-y-1': showShare }">
                        <div
                            v-for="(item, index) in contentList"
                            :key="item._key ?? index"
                            :ref="(el) => setChatMessageRef(el, index)"
                            class="chat-message"
                            :class="{
                                'is-share': showShare,
                                'is-selected': shareContentIndexList.includes(Number(index)),
                            }"
                            @click="handleItem(index)">
                            <div class="message-contain message--his" v-if="item.type === 2">
                                <div
                                    class="absolute top-1/2 left-[10px] translate-y-[-50%] cursor-pointer"
                                    v-if="showShare">
                                    <Icon
                                        name="local-icon-checkbox"
                                        color="#8A939D"
                                        :size="16"
                                        v-if="!shareContentIndexList.includes(Number(index))" />
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
                                    @share="handleShare()"
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
                            <div class="flex w-full flex-col gap-1 items-end rtl:items-start" v-if="item.type === 1">
                                <div class="max-w-[70%]">
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
                                                v-if="!shareContentIndexList.includes(Number(index))" />
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
                                            @share="handleShare()"
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
                    <span class="whitespace-nowrap">回到底部</span>
                </div>
            </Transition>
        </div>

        <div
            v-show="!showShare"
            ref="chattingAreaRef"
            class="w-full"
            :class="[
                sendAreaOnly
                    ? 'flex-none'
                    : contentList.length == 0
                    ? 'flex-1 flex flex-col items-center justify-center'
                    : 'flex-none mt-2',
            ]">
            <div class="grow w-full min-h-0 mb-6" v-if="!sendAreaOnly && contentList.length == 0">
                <slot name="content"></slot>
            </div>
            <div class="w-full shrink-0">
                <div
                    class="md:max-w-3xl lg:max-w-[42rem] xl:max-w-[48rem] 2xl:max-w-[52rem] mx-auto mb-4 relative"
                    :class="{ 'send-area-container': sendAreaOnly }">
                    <slot name="customSendArea" v-if="$slots.customSendArea"></slot>
                    <div class="flex flex-col relative" v-else>
                        <slot name="chat-area-top" />

                        <!-- 与欢迎页沉浸态一致：模式 Tab 放在输入框外上方 -->
                        <div v-if="modeTabs.length" class="chat-mode-tabs">
                            <button
                                v-for="mode in modeTabs"
                                :key="mode.key"
                                type="button"
                                class="chat-mode-tab"
                                :class="{ active: activeMode === mode.key }"
                                @click="emit('change-mode', mode.key)">
                                <span class="chat-mode-tab-ic">
                                    <Icon :name="mode.icon" :size="14" />
                                </span>
                                <span>{{ mode.label }}</span>
                                <span v-if="mode.badge" class="chat-mode-badge" :class="mode.badge.type">
                                    {{ mode.badge.text }}
                                </span>
                            </button>
                        </div>

                        <div
                            class="input-box-wrapper relative bg-white rounded-[18px] border border-[#ebedf0] pt-[14px] shadow-[0_4px_16px_rgba(0,0,0,0.05)]"
                            :class="{
                                'is-focused': isInputBoxFocused,
                            }"
                            @mouseenter="isInputBoxHovered = true"
                            @mouseleave="isInputBoxHovered = false">
                            <at-mention-pop
                                :list="mentionAgentList"
                                :keyword="mentionKeyword"
                                :visible="showMentionPop"
                                ref="mentionPopRef"
                                @select="handleMentionSelect"
                                @close="closeMentionPop" />

                            <div
                                ref="dragHandleRef"
                                class="drag-handle"
                                :class="{ 'is-dragging': isDragging }"
                                @mousedown="startDrag"
                                @touchstart.prevent="startTouchDrag">
                                <div class="drag-handle-bar"></div>
                            </div>

                            <Transition name="agent-tag">
                                <div v-if="selectedAgent" class="welcome-selected-agent" @click="clearSelectedAgent">
                                    <img
                                        v-if="hasAgentAvatar(selectedAgent)"
                                        :src="selectedAgent.image"
                                        class="w-4 h-4 rounded-full object-cover"
                                        @error="hideBrokenAgentAvatar" />
                                    <span class="min-w-0 truncate">@{{ selectedAgent.name }}</span>
                                    <svg class="shrink-0" width="10" height="10" viewBox="0 0 12 12" fill="none">
                                        <path
                                            d="M2 2L10 10M10 2L2 10"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round" />
                                    </svg>
                                </div>
                            </Transition>

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
                                class="h-[80px] border-b border-[#EBEBEB] w-full px-2"
                                v-if="$slots.fileList || fileList.length > 0">
                                <slot name="fileList">
                                    <file-lists v-model:file-list="fileList" />
                                </slot>
                            </div>

                            <div
                                class="flex items-end cursor-text px-[18px] relative overflow-y-auto"
                                :style="{ height: `${inputAreaHeight}px` }">
                                <div class="py-[12px] flex-1 h-full">
                                    <slot name="input" v-if="$slots.input"></slot>
                                    <textarea
                                        v-else
                                        ref="inputRef"
                                        v-model="inputContent"
                                        class="content-ipt"
                                        :placeholder="placeholder"
                                        @input="handleInput"
                                        @keydown="handleInputEnter"
                                        @focus="isInputFocused = true"
                                        @blur="isInputFocused = false" />
                                </div>
                            </div>

                            <div
                                class="flex items-center p-[6px]"
                                :class="[
                                    $slots.toolbarLeft || $slots.toolbarLeftPrefix || showChattingBottom
                                        ? 'justify-between'
                                        : 'justify-end',
                                ]">
                                <div
                                    class="flex items-center gap-x-2"
                                    v-if="$slots.toolbarLeft || $slots.toolbarLeftPrefix || showChattingBottom">
                                    <slot name="toolbarLeftPrefix" />
                                    <slot name="toolbarLeft">
                                        <div
                                            class="chat-toolbar-btn"
                                            :class="{ 'is-on': selectedNetwork }"
                                            v-if="isNetwork"
                                            @click="handleNetwork">
                                            <span>联网搜索</span>
                                            <span class="chat-toolbar-switch" :class="{ on: selectedNetwork }">
                                                <span class="knob" />
                                            </span>
                                        </div>
                                        <chat-model-toolbar
                                            v-if="!isDisabledHumanize && !selectedAgent"
                                            ref="chatModelToolbarRef"
                                            :show-settings="false"
                                            @model-change="onToolbarModelChange" />
                                    </slot>
                                </div>
                                <div class="flex items-center gap-x-2">
                                    <slot name="toolbarRight" />
                                    <!-- 与欢迎页一致：上传 / 设置 与发送同排靠右 -->
                                    <file-upload
                                        v-if="isUploadFile"
                                        v-model="fileList"
                                        :file-limit="fileLimit"
                                        :accept="uploadAccept"
                                        :image-max-size="CHAT_IMAGE_MAX_SIZE"
                                        :file-max-size="CHAT_FILE_MAX_SIZE"
                                        @change="(list) => (fileList = list)"
                                        @update:modelValue="emit('update:fileList', fileList)">
                                        <div class="chat-toolbar-icon-btn dashed" title="文件上传">
                                            <Icon name="el-icon-Paperclip" :size="14" />
                                        </div>
                                    </file-upload>
                                    <humanize-pop
                                        v-if="!isDisabledHumanize && !selectedAgent"
                                        ref="humanizePopRef"
                                        variant="toolbar"
                                        :model-id="toolbarModelId"
                                        :model-sub-id="toolbarModelSubId" />
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
                                            class="flex w-full h-full items-center justify-center rounded-full bg-primary-light-9 text-white disabled:bg-[#F6F6F6] disabled:text-[#f4f4f4]"
                                            @click="contentPost">
                                            <Icon
                                                name="local-icon-arrow_up"
                                                :color="isSendDisabled ? '#a9a9a9' : 'var(--color-primary)'"
                                                :size="18" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <slot name="chat-area-bottom" />
                    </div>
                </div>
            </div>
            <div class="flex justify-center" v-if="!sendAreaOnly">
                <div class="text-xs flex justify-center mb-2 gap-2 items-center p-1 bg-[#00000008] rounded-full">
                    <Icon name="local-icon-tips2" :size="16"></Icon>
                    <span class="text-[#0000004d] text-xs">免责声明：内容由AI大模型生成,请仔细甄别。</span>
                </div>
            </div>
        </div>

        <share-floating-bar
            :show="showShare"
            :is-all-selected="isAllSelected"
            :selected-count="shareContentIndexList.length"
            @select-all="handleSelectAll"
            @cancel="handleCancelShare"
            @generate-image="handleGenerateImage"
            @generate-pdf="handleGeneratePDF"
            @generate-link="handleGenerateLink" />

        <preview-share v-if="showPreviewShare" ref="previewShareRef" />
        <edit-pop v-model:show="showEditDrawer" v-model:content="editContent" />
    </div>
</template>

<script setup lang="ts">
import type { PropType } from "vue";
import removeMarkdown from "remove-markdown";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { FileParams, UPLOAD_STATUS } from "@/composables/usePasteImage";
import { useScroll } from "./composables/useScroll";
import { useDragResize } from "./composables/useDragResize";
import { useMention } from "./composables/useMention";
import { useShare } from "./composables/useShare";
import FileUpload from "./file-upload.vue";
import { CHAT_UPLOAD_ACCEPT, CHAT_IMAGE_MAX_SIZE, CHAT_FILE_MAX_SIZE } from "./upload-rules";
import FileLists from "./file-lists.vue";
import ChatModelToolbar from "./chat-model-toolbar.vue";
import HumanizePop from "./humanize-pop.vue";
import PreviewShare from "./preview-share.vue";
import EditPop from "./edit-pop.vue";
import QuoteItem from "./quote-item.vue";
import AtMentionPop, { type MentionItem } from "./at-mention-pop.vue";
import ShareFloatingBar from "./share-floating-bar.vue";

const emit = defineEmits([
    "update:modelValue",
    "contentPost",
    "close",
    "top",
    "update:fileList",
    "update:network",
    "quote",
    "update:inputContent",
    "mention-agent",
    "change-mode",
]);

interface ModeTab {
    key: string;
    label: string;
    icon: string;
    badge?: { text: string; type: "new" | "hot" };
}

const props = defineProps({
    contentList: { type: Array as any, default: () => [] },
    sendDisabled: { type: Boolean },
    isStop: { type: Boolean },
    avatar: { type: String },
    placeholder: { type: String, default: "在这里输入任何问题 ..." },
    isNetwork: { type: Boolean },
    /** 联网搜索开关的当前值，配合 update:network 可用 v-model:network */
    network: { type: Boolean, default: false },
    isUploadFile: { type: Boolean, default: true },
    isDisabledHumanize: { type: Boolean, default: false },
    isNewChat: { type: Boolean, default: false },
    isAuthLogin: { type: Boolean, default: true },
    isQuote: { type: Boolean, default: false },
    isShare: { type: Boolean, default: false },
    isEdit: { type: Boolean, default: false },
    isAgent: { type: Boolean, default: false },
    agentList: { type: Array as () => MentionItem[], default: () => [] },
    disableMention: { type: Boolean, default: true },
    modeTabs: { type: Array as () => ModeTab[], default: () => [] },
    activeMode: { type: String, default: "chat" },
    sendAreaOnly: { type: Boolean, default: false },
    sendAreaVariant: { type: String, default: "" },
    sendEnabled: {
        type: Boolean as PropType<boolean | undefined>,
        default: undefined,
    },
    modelValue: { type: String, default: undefined },
});

const appStore = useAppStore();
const { getWebsiteConfig } = appStore;
const userStore = useUserStore();
const { isLogin, toggleShowLogin } = userStore;

// ===== Refs =====

const containerRef = ref<HTMLDivElement>(null);
const inputRef = ref<HTMLTextAreaElement>(null);
// 模板 ref 统一在这里声明后传给各 composable，不要让 composable 自己 new 一个再 return
const scrollContainerRef = ref<HTMLDivElement | null>(null);
const dragHandleRef = ref<HTMLDivElement | null>(null);
const mentionPopRef = ref<any>(null);
const previewShareRef = ref<any>(null);
const chatModelToolbarRef = shallowRef<InstanceType<typeof ChatModelToolbar>>();
const humanizePopRef = shallowRef<InstanceType<typeof HumanizePop>>();
const toolbarModelId = ref("");
const toolbarModelSubId = ref("");
const onToolbarModelChange = (model: { model_id?: string; model_sub_id?: string }) => {
    toolbarModelId.value = model?.model_id || "";
    toolbarModelSubId.value = model?.model_sub_id || "";
};
const chatMessageRefs = ref<Map<number, HTMLElement>>(new Map());

// ===== 基础状态 =====
const inputContent = ref("");
const fileList = ref<FileParams[]>([]);
const fileLimit = ref(1);
const fileIsLoad = ref(false);
const quoteContent = ref("");
const showEditDrawer = ref(false);
const editContent = ref("");
const selectedNetwork = ref(props.network);
const isInputFocused = ref(false);
const isInputBoxHovered = ref(false);
const containerCurrentHeight = ref(0);
/** 上一次上推的锚点（新消息的落点），resize 时按新视口重算预留用 */
const pushUpAnchorTop = ref(0);

const isInputBoxFocused = computed(() => isInputFocused.value || isInputBoxHovered.value);
const uploadAccept = computed(() => CHAT_UPLOAD_ACCEPT);
const showChattingBottom = computed(() => props.isUploadFile && props.isNetwork);
const isSendDisabled = computed(() => {
    if (typeof props.sendEnabled === "boolean") return props.sendDisabled || !props.sendEnabled;
    const flag = fileList.value.length === 0 ? !inputContent.value : !fileIsLoad.value;
    return props.sendDisabled || flag;
});
const contentContainerStyle = computed(() => {
    if (showShare.value) return { minHeight: "auto" };
    if (props.contentList.length > 0 && containerCurrentHeight.value > 0)
        return { minHeight: `${containerCurrentHeight.value}px` };
});

// ===== 使用 composables =====
const {
    disabledScroll,
    showBackToBottom,
    scroll,
    resetScroll,
    scrollToBottom,
    scrollTo,
    handleBackToBottom,
} = useScroll(scrollContainerRef, emit);

const { inputAreaHeight, isDragging, startDrag, startTouchDrag, resetHeight } = useDragResize(dragHandleRef);

const agentListComputed = computed(() => props.agentList);
const {
    showMentionPop,
    mentionKeyword,
    selectedAgent,
    mentionAgentList,
    closeMentionPop,
    clearSelectedAgent,
    handleMentionSelect,
    handleTextareaInput,
    handleMentionKeydown,
} = useMention({
    agentList: agentListComputed,
    inputRef,
    inputContent,
    mentionPopRef,
    emit,
    disableMention: computed(() => props.disableMention),
});

/** 无有效 logo 时不展示头像（也不用首字兜底） */
const hasAgentAvatar = (agent: { image?: string } | null) => !!agent?.image?.trim();
const hideBrokenAgentAvatar = (event: Event) => {
    const img = event.target as HTMLImageElement | null;
    if (img) img.style.display = "none";
};

const contentListComputed = computed(() => props.contentList);
const {
    showShare,
    showPreviewShare,
    shareContentIndexList,
    isAllSelected,
    handleShare,
    handleSelectAll,
    handleCancelShare,
    handleShareContent,
    handleGenerateImage,
    handleGeneratePDF,
    handleGenerateLink,
} = useShare(contentListComputed, containerRef, getWebsiteConfig, previewShareRef);

// ===== 内容上推 =====
/**
 * 发送时把上一轮对话顶上去，让刚发送的内容正好落在可视区顶部。
 * 1. anchorTop 在新消息插入前同步测量，它就是新消息的落点；
 * 2. 视口高度要等输入区复位后再取（附件条 / 引用条 / 拖高的高度都会消失），否则预留会少一截；
 * 3. 直接滚到 anchorTop，而不是滚到底 —— 新消息超过一屏时滚到底会滚过头。
 */
const triggerContentPushUp = () => {
    const scroller = scrollContainerRef.value;
    const container = containerRef.value;
    if (!scroller || !container) return;

    const anchorTop = container.scrollHeight;
    pushUpAnchorTop.value = anchorTop;
    // 先按当前视口预留，避免本轮渲染出现“先跳到底再回弹”
    containerCurrentHeight.value = anchorTop + scroller.clientHeight;

    nextTick(() => {
        requestAnimationFrame(async () => {
            const el = scrollContainerRef.value;
            if (!el) return;
            // 输入区已复位，此时的 clientHeight 才是最终视口
            containerCurrentHeight.value = anchorTop + el.clientHeight;
            await nextTick(); // 等 min-height 落到 DOM，否则 scrollTop 会被裁剪
            el.scrollTo({ top: anchorTop, behavior: "smooth" });
        });
    });
};

// ===== 输入框操作 =====
const { copy } = useCopy();
const copyContent = async (type: "markdown" | "text", content: string) => {
    if (type == "markdown") await copy(content);
    if (type == "text") await copy(removeMarkdown(content));
};

const handleNetwork = () => {
    selectedNetwork.value = !selectedNetwork.value;
    emit("update:network", selectedNetwork.value);
};

const handleInput = () => {
    handleTextareaInput();
    emit("update:modelValue", inputContent.value);
};

const cleanInput = () => {
    inputContent.value = "";
    fileList.value = [];
    fileIsLoad.value = false;
    containerCurrentHeight.value = 0;
    resetHeight();
    emit("update:modelValue", "");
    emit("update:fileList", []);
};

const setInput = (val: string) => {
    inputContent.value = val;
};
const clearQuote = () => {
    quoteContent.value = "";
};

const hasUploadingFiles = () =>
    fileList.value.some(
        (item) => item.loading === true || item.status === UPLOAD_STATUS.UPLOADING || item.status === "uploading",
    );

const handleInputEnter = (e: KeyboardEvent) => {
    if (handleMentionKeydown(e)) return;
    if (e.shiftKey && (e.key === "Enter" || e.keyCode === 13)) return;
    if (!isLogin && props.isAuthLogin) {
        toggleShowLogin();
        return;
    }
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        // 与发送按钮一致：禁用时回车也不可发送（上传中 / sendEnabled=false 等）
        if (isSendDisabled.value) {
            const hasText = inputContent.value.replace(/(^\s*)|(\s*$)/g, "") !== "";
            if (hasUploadingFiles() || (typeof props.sendEnabled === "boolean" && !props.sendEnabled && hasText)) {
                feedback.msgWarning("文件正在上传中，请稍候再发送");
            }
            return;
        }
        contentPost();
    }
};

const contentPost = () => {
    const hasSendEnabledOverride = typeof props.sendEnabled === "boolean";
    if (
        !hasSendEnabledOverride &&
        inputContent.value.replace(/(^\s*)|(\s*$)/g, "") == "" &&
        fileList.value.length == 0
    ) {
        feedback.msgError("输入为空！");
        return;
    }
    if (hasSendEnabledOverride && !props.sendEnabled) {
        // 欢迎页：有内容但 sendEnabled=false 时通常是附件仍在上传
        if (
            inputContent.value.replace(/(^\s*)|(\s*$)/g, "") !== "" ||
            fileList.value.length > 0 ||
            hasUploadingFiles()
        ) {
            feedback.msgWarning("文件正在上传中，请稍候再发送");
        }
        return;
    }
    if (props.sendDisabled) return;
    if (hasUploadingFiles() || (!fileIsLoad.value && fileList.value.length > 0)) {
        feedback.msgError("文件正在上传中...");
        return;
    }
    emit("contentPost", inputContent.value);
    resetScroll();
    cleanInput();
    // 放在 cleanInput 之后：输入区已清空，且不会被 cleanInput 里的清零覆盖；
    // 此刻新消息还没插入 DOM（父级 addMessage 要等下一次渲染），锚点仍是准确的
    triggerContentPushUp();
};

const setChatMessageRef = (el: any, index: any) => {
    if (el) chatMessageRefs.value.set(index, el);
    else chatMessageRefs.value.delete(index);
};

const handleItem = (index: any) => {
    if (showShare.value) {
        const { error, reply, message } = props.contentList[index];
        // 与 useShare 的筛选口径保持一致：只拦报错和空气泡，停止生成但有正文的可以选
        if (error || (!reply && !message)) return;
        handleShareContent(index);
    }
};

const handleQuote = (item: any) => {
    const { type, message, reply } = item;
    if (type === 1) quoteContent.value = `**${message}**`;
    else if (type === 2) quoteContent.value = reply;
    emit("quote", quoteContent.value);
};

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

// ===== 生命周期 =====
/** 窗口尺寸变化后按新视口重算上推预留，否则锚点会随视口变化而漂移 */
const updateStableHeight = () => {
    if (!containerCurrentHeight.value || !scrollContainerRef.value) return;
    containerCurrentHeight.value = pushUpAnchorTop.value + scrollContainerRef.value.clientHeight;
};

onMounted(() => {
    window.addEventListener("resize", updateStableHeight);
});
onUnmounted(() => {
    window.removeEventListener("resize", updateStableHeight);
});

watch(
    () => fileList.value,
    (value) => {
        // 全部成功才算可发送；任一上传中则视为未就绪
        const list = value || [];
        fileIsLoad.value =
            list.length > 0 &&
            list.every((item) => item.status === UPLOAD_STATUS.SUCCESS) &&
            !list.some((item) => item.loading === true || item.status === UPLOAD_STATUS.UPLOADING);
    },
    { deep: true },
);

// 只关心「条数变化」和「整表被替换（切换会话）」两件事。
// 不能用 deep：流式回复每个 token 都在改数组里的内容，deep 会让这里每帧做一次全量深遍历，
// 而且会把刚进入的分享模式立刻重置掉
watch([() => props.contentList, () => props.contentList.length], () => {
    if (props.contentList.length === 0) {
        containerCurrentHeight.value = 0;
        pushUpAnchorTop.value = 0;
    }
    shareContentIndexList.value = [];
    showShare.value = false;
    if (!disabledScroll.value) showBackToBottom.value = false;
});

// 外部（如欢迎页发送后切到对话页）改变联网开关时，保持工具栏状态一致
watch(
    () => props.network,
    (value) => {
        if (value !== selectedNetwork.value) selectedNetwork.value = value;
    },
);

watch(
    () => props.modelValue,
    (value) => {
        if (typeof value === "string" && value !== inputContent.value) {
            inputContent.value = value;
        }
    },
    { immediate: true },
);

watch(
    () => showShare.value,
    (newVal) => {
        if (newVal) containerCurrentHeight.value = 0;
    },
);

// ===== 对外暴露 =====
defineExpose({
    scrollToBottom,
    scrollTo,
    resetScroll,
    cleanInput,
    setInput,
    clearQuote,
    triggerContentPushUp,
    setSelectedAgent: (agentId: any) => {
        if (agentId === 0) {
            selectedAgent.value = null;
            return;
        }
        const agentItem = agentListComputed.value.find((item) => item.id == agentId);
        if (agentItem) {
            selectedAgent.value = agentItem;
        }
    },
    getSelectedAgent: () => selectedAgent.value,
    getChatConfig: () => {
        const toolbar = chatModelToolbarRef.value;
        if (selectedAgent.value) {
            return {
                robot_id: selectedAgent.value?.id || undefined,
            };
        }
        const humanize = humanizePopRef.value?.formData;
        const humanizeData = humanize ? (({ model_id: _id, model_sub_id: _sub, ...rest }) => rest)(humanize) : {};
        // 拟人化参数在前；模型配置必须后覆盖，避免 formData 默认 model_id 盖住真实选择
        return {
            ...humanizeData,
            ...toolbar?.getModelConfig?.(),
            robot_id: undefined,
        };
    },
});
</script>

<style scoped lang="scss">
.chatting {
    @apply h-full flex flex-col w-full relative;
}

.chatting.is-welcome-send-area {
    @apply h-auto;
}

.send-area-container {
    @apply max-w-none md:max-w-none lg:max-w-none xl:max-w-none 2xl:max-w-none;
}

.scroll-container {
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
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
    &.is-share {
        @apply pl-10 pt-4 pb-3 pr-3 relative rounded-lg hover:bg-[#F5F5F5] cursor-pointer;
    }
    &.is-selected {
        @apply bg-[#F5F5F5];
    }
}

.input-box-wrapper {
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    &:hover {
        border-color: var(--color-primary);
    }
    &.is-focused {
        border-color: var(--color-primary) !important;
    }
}

.chat-mode-tabs {
    /* 对齐欢迎页沉浸态：Tab 在输入框外上方 */
    @apply mb-3 flex flex-wrap items-center gap-2;
}

.chat-mode-tab {
    @apply inline-flex cursor-pointer items-center gap-1.5 rounded-2xl border border-[#ebedf0] bg-white px-3 py-[5px] text-xs text-[#6b7280] transition-all duration-150 ease-[ease];

    &:hover {
        @apply border-[#93c5fd] text-[#2563eb];
    }

    &.active {
        @apply border-[#93c5fd] bg-gradient-to-br from-[#ecf2ff] to-[#dbeafe] font-semibold text-[#2563eb];
    }
}

.chat-mode-tab-ic {
    @apply inline-flex h-3.5 w-3.5 items-center justify-center;
}

.chat-mode-badge {
    @apply ml-0.5 -translate-y-px rounded-lg px-1.5 py-px text-[9px] font-bold leading-none text-white;

    &.new {
        @apply bg-gradient-to-br from-[#4f8ef7] to-[#2563eb];
    }

    &.hot {
        @apply bg-gradient-to-br from-[#fb923c] to-[#ef4444] px-1 py-px;
    }
}

.welcome-selected-agent {
    @apply mx-[18px] mt-3 inline-flex max-w-[calc(100%-36px)] cursor-pointer items-center gap-1.5 self-start rounded-full border border-[rgba(37,99,235,0.2)] bg-[rgba(37,99,235,0.06)] px-3 py-1.5 text-xs font-semibold text-primary transition-opacity duration-150 hover:opacity-80;
}

.drag-handle {
    /* 低于工具栏弹层(z-100)，避免盖住「选择比例」等 popup */
    @apply absolute top-0 left-0 z-[1] w-full h-[16px] flex items-center justify-center cursor-ns-resize select-none rounded-t-[18px];
    &::before {
        content: "";
        @apply absolute inset-x-0 top-0 h-full rounded-t-[18px] opacity-0;
        background: linear-gradient(to bottom, rgba(37, 99, 235, 0.08), transparent);
        transition: opacity 0.25s ease;
    }
    &:hover::before,
    &.is-dragging::before {
        @apply opacity-100;
    }
    &:hover,
    &.is-dragging {
        .drag-handle-bar {
            @apply w-[46px] bg-primary;
            box-shadow: 0 1px 6px rgba(37, 99, 235, 0.35);
        }
    }
    &.is-dragging .drag-handle-bar {
        @apply w-[56px];
    }
}

.drag-handle-bar {
    @apply relative w-[36px] h-[4px] bg-[#dcdfe6] rounded-full;
    transition: width 0.25s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.25s ease, box-shadow 0.25s ease;
    pointer-events: none;
}

.content-ipt {
    @apply w-full h-full text-sm;
    resize: none;
    border: none;
    outline: none;
    background: transparent;
    line-height: 1.6;
    color: inherit;
    &::placeholder {
        @apply text-[#CACACA];
    }
}

.chat-toolbar-btn {
    @apply inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-[18px] border border-[#ebedf0] bg-white px-3 py-1.5 text-[13px] text-[#4b5563] transition-colors duration-150 ease-[ease];

    &:hover {
        @apply border-[#93c5fd] text-[#2563eb];
    }

    &.is-on {
        @apply border-[#93c5fd] bg-[#f5f8ff] text-[#2563eb];
    }
}

.chat-toolbar-switch {
    @apply relative inline-block h-3.5 w-[26px] rounded-lg bg-[#d1d5db] transition-colors duration-150 ease-[ease];

    &.on {
        @apply bg-[#2563eb];
    }

    .knob {
        @apply absolute left-px top-px h-3 w-3 rounded-full bg-white shadow-[0_1px_2px_rgba(0,0,0,0.15)] transition-transform duration-150 ease-[ease];
    }

    &.on .knob {
        @apply translate-x-3;
    }
}

.chat-toolbar-icon-btn {
    @apply inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-full border border-[#ebedf0] bg-white p-0 text-[#4b5563] transition-colors duration-150 ease-[ease];

    &:hover {
        @apply border-[#93c5fd] text-[#2563eb];
    }

    &.dashed {
        @apply border-dashed;
    }
}

.back-to-bottom-btn {
    @apply absolute bottom-20 right-20 z-[100] flex items-center gap-4 px-4 py-3 bg-[#ffffff]/95 backdrop-blur-[10px] border border-gray-100 rounded-[24px] shadow-[rgba(0,0,0,0.15)] cursor-pointer transition-all duration-300 text-gray-600 text-base hover:shadow-[rgba(0,0,0,0.25)] hover:-translate-y-2 active:translate-y-0;

    &:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
        color: var(--color-primary);
    }
    &:active {
        transform: translateY(0);
    }
}

.message-contain {
    transition: all 0.3s ease;
}

.fade-slide-up-enter-active,
.fade-slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-slide-up-enter-from,
.fade-slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.9);
}

.agent-tag-enter-active,
.agent-tag-leave-active {
    transition: all 0.2s ease;
}
.agent-tag-enter-from,
.agent-tag-leave-to {
    opacity: 0;
    transform: translateY(6px);
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
