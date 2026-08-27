<template>
    <div class="welcome-shell h-full w-full relative">
        <div
            class="welcome-hero h-full w-full px-8 flex flex-col items-center"
            :class="[
                isImmersive
                    ? 'immersive py-6 overflow-hidden'
                    : hasOutput
                    ? 'justify-start pt-16 py-10 overflow-y-auto'
                    : 'justify-center py-10 overflow-y-auto',
            ]">
            <div class="w-full max-w-[760px] flex flex-col" :class="isImmersive ? 'h-full min-h-0' : 'items-center'">
                <div
                    v-if="!isImmersive"
                    class="hero-greeting text-[32px] font-semibold text-[#1f2937] tracking-wide text-center">
                    Hello，今天聊点什么？
                </div>

                <div v-if="!isImmersive" class="hero-models mt-5 flex items-center gap-2.5">
                    <div class="model-stack flex items-center">
                        <div
                            v-for="(m, i) in modelBadges"
                            :key="m.name + i"
                            class="model-logo"
                            :class="[i === 0 ? 'ml-0' : '-ml-2.5']"
                            :title="m.title">
                            <Icon :name="m.icon" :size="22" />
                        </div>
                    </div>
                    <span class="text-[13px] text-[#9ca3af] ml-1">多模型协作 · 一句话直达答案</span>
                </div>

                <!-- 地图模式：类目快捷入口（仅地图模式、且未进入对话态时显示） -->
                <map-cats v-if="displayedMode === 'map' && !isImmersive" class="w-full" @pick="onPickCat" />

                <!-- ============ 输出区(图片多轮 / 其它单轮) ============ -->
                <div
                    class="output-stream w-full"
                    :class="{
                        'as-chat': isImmersive,
                        'flex-1 overflow-y-auto min-h-0 px-1 py-2': isImmersive,
                    }"
                    :style="outputStreamStyle"
                    ref="outputStreamRef">
                    <image-chat-pane
                        v-if="displayedMode === 'image'"
                        :messages="imageChat"
                        :user-avatar="userAvatar"
                        @regenerate="onImageRegenerate" />
                    <video-chat-pane
                        v-else-if="displayedMode === 'video'"
                        :messages="videoChat"
                        :user-avatar="userAvatar"
                        @regenerate="onVideoRegenerate" />
                    <digital-chat-pane
                        v-else-if="displayedMode === 'digital'"
                        :messages="digitalChat"
                        :user-avatar="userAvatar"
                        :assistant-avatar="assistantAvatar"
                        @followup-confirm="onDigitalFollowupConfirm"
                        @followup-cancel="onDigitalFollowupCancel"
                        @copy-text="copyOptimized"
                        @regenerate-copy="regenerateOneCopy"
                        @use-one-copy="useOneCopy"
                        @use-all-copies="useAllCopies" />
                    <ppt-chat-pane
                        v-else-if="displayedMode === 'ppt'"
                        :messages="pptChat"
                        :user-avatar="userAvatar"
                        :assistant-avatar="assistantAvatar"
                        @followup-confirm="onFollowupConfirm"
                        @followup-cancel="onFollowupCancel"
                        @view-ppt="onViewPpt"
                        @regenerate-ppt="onRegeneratePpt"
                        @export-ppt="onExportPptMsg" />

                    <!-- 地图始终挂载(v-show),避免切走其它类型时组件销毁导致会话内容丢失 -->
                    <output-map
                        v-show="displayedMode === 'map'"
                        ref="mapOutRef"
                        :current-user-text="displayedMode === 'map' && isImmersive ? lastUserText : ''"
                        @pick-cat="onPickCat"
                        @export="onMapExport"
                        @progress="syncMapSnapshot"
                        @conversation-change="onMapConversationChange"
                        @restored="onMapRestored" />
                </div>

                <WelcomeModeTabs
                    v-if="!isImmersive"
                    class="mt-7"
                    variant="outside"
                    :tabs="modeTabs"
                    :active="activeMode"
                    @change="switchMode" />

                <div
                    class="input-box w-full mt-5"
                    :class="{ dragover: isDragover, immersive: isImmersive, 'shrink-0': isImmersive }"
                    @dragover.prevent="isDragover = true"
                    @dragleave.prevent="isDragover = false"
                    @drop.prevent="onDrop">
                    <WelcomeModeTabs
                        v-if="isImmersive"
                        class="mb-3"
                        variant="inside"
                        :tabs="modeTabs"
                        :active="activeMode"
                        @change="switchMode" />
                    <!-- 数字人口播混剪:输入模式切换(AI 文案生成 / 爆款仿写) -->
                    <div
                        v-if="activeMode === 'digital' && digitalState.mode === 'dh-montage'"
                        class="input-mode-switch flex gap-1 mb-2">
                        <div
                            v-for="m in DIGITAL_INPUT_MODES"
                            :key="m.value"
                            class="ims-tab"
                            :class="{ active: digitalInputMode === m.value }"
                            @click="digitalInputMode = m.value">
                            <Icon :name="m.icon" :size="14" />
                            <span>{{ m.label }}</span>
                        </div>
                    </div>

                    <Chatting
                        ref="welcomeChattingRef"
                        v-model="inputText"
                        send-area-only
                        send-area-variant="welcome"
                        :content-list="[]"
                        :agent-list="agentListComputed"
                        :disable-mention="activeMode !== 'chat'"
                        :placeholder="currentPlaceholder"
                        :send-enabled="canSend"
                        :send-disabled="false"
                        :is-network="false"
                        :is-upload-file="false"
                        :is-disabled-humanize="activeMode !== 'chat' || !!selectedAgent"
                        @content-post="handleChattingSend"
                        @mention-agent="handleWelcomeMentionAgent">
                        <template v-if="refImages.length" #fileList>
                            <FileLists :file-list="welcomeFileList" @update:file-list="onWelcomeToolbarFiles" />
                        </template>

                        <template #toolbarLeftPrefix>
                            <input-toolbar-left
                                :active-mode="activeMode"
                                :open-popup="openPopup"
                                :image-state="imageState"
                                :ppt-state="pptState"
                                :video-state="videoState"
                                :digital-state="digitalState"
                                :current-image-draw-model="currentImageDrawModel"
                                :current-ppt-draw-model="currentPptDrawModel"
                                :current-video-draw-model="currentVideoDrawModel"
                                :image-draw-models="imageDrawModels"
                                :ppt-draw-models="pptDrawModels"
                                :video-draw-models="videoDrawModels"
                                :selected-image-model-id="selectedImageModelId"
                                :selected-ppt-model-id="selectedPptModelId"
                                :selected-video-model-id="selectedVideoModelId"
                                :current-digital-avatar="currentDigitalAvatar"
                                :current-digital-persona="currentDigitalPersona"
                                :current-digital-style="currentDigitalStyle"
                                :digital-personas="DIGITAL_PERSONAS"
                                :custom-pages-input="customPagesInput"
                                :is-custom-pages-valid="isCustomPagesValid"
                                :available-material-modes="availableMaterialModes"
                                :show-material-settings="showMaterialSettings"
                                :show-setting-voice="showSettingVoice"
                                :image-max-count="imageMaxCount"
                                :web-search="webSearch"
                                :prompt-optimizing="promptOptimizing"
                                :digital-followup-on="digitalFollowupOn"
                                :followup-on="followupOn"
                                @toggle="togglePopup"
                                @update:open-popup="openPopup = $event"
                                @select-image-model="selectImageDrawModel"
                                @select-ppt-model="selectPptDrawModel"
                                @select-video-model="selectVideoDrawModel"
                                @set-ratio="setRatio"
                                @set-video-ratio="setVideoRatio"
                                @change-count="changeCount"
                                @change-video-count="changeVideoCount"
                                @change-digital-count="changeDigitalCount"
                                @apply-custom-pages="applyCustomPages"
                                @preview-style="onPreviewStyle"
                                @optimize-prompt="handleOptimizePrompt"
                                @update:web-search="webSearch = $event"
                                @update:digital-followup-on="digitalFollowupOn = $event"
                                @update:followup-on="followupOn = $event"
                                @update:custom-pages-input="customPagesInput = $event" />
                        </template>

                        <template #toolbarRight>
                            <input-toolbar-right
                                ref="toolbarRightRef"
                                :active-mode="activeMode"
                                :show-file-upload="showFileUploadInToolbar"
                                :upload-button-label="uploadButtonLabel"
                                :upload-accept="uploadAccept"
                                :file-list="welcomeFileList"
                                @update:file-list="onWelcomeToolbarFiles"
                                @sync-files="onWelcomeToolbarFiles"
                                @open-map-history="openMapHistory"
                                @open-image-case-library="openImageCaseLibrary"
                                @open-history="openHistoryPopup()" />
                        </template>
                    </Chatting>
                    <!-- 提示词优化中：遮住整块输入区，禁止点击/编辑 -->
                    <div v-if="promptOptimizing" class="prompt-optimize-mask" @click.stop.prevent>
                        <span class="animate-spin inline-flex">
                            <Icon name="el-icon-Loading" :size="18" />
                        </span>
                        <span>正在优化提示词…</span>
                    </div>
                </div>

                <div v-if="!hasOutput" class="hero-disclaimer mt-3 text-[12px] text-[#b8bcc4] flex items-center gap-1">
                    <Icon name="el-icon-InfoFilled" :size="12" />
                    <span>免责声明：内容由AI大模型生成，请仔细甄别。</span>
                </div>
            </div>
        </div>

        <!-- PPT 右侧抽屉:渲染 activePptMsg 的 slides -->
        <output-ppt
            class="ppt-drawer"
            :visible="activeMode === 'ppt' && pptOpen && !!activePptMsg"
            :topic="activePptMsg?.topic ?? ''"
            :pages="activePptMsg?.pageCount ?? 0"
            :slides="(activePptMsg?.slides ?? []) as any"
            :loading="activePptMsg?.state === 'generating'"
            @close="onClosePpt"
            @regenerate="activePptMsgId && onRegeneratePpt(activePptMsgId)"
            @regenerate-slide="(slideId: number) => activePptMsgId && regenerateSlideImage(activePptMsgId, slideId)"
            @export="onPptExport" />

        <history-popup
            :visible="openPopup === 'history' && ['image', 'ppt', 'video', 'digital'].includes(activeMode)"
            :position-style="historyPopupStyle"
            :list="historyList"
            :loading="historyLoading"
            :deleting-id="historyDeletingId"
            :active-id="activeDrawConvId"
            :show-new="activeMode === 'image' || activeMode === 'video' || activeMode === 'ppt'"
            @new="onNewDrawSession"
            @restore="onRestoreHistory"
            @delete="onDeleteHistory" />

        <!-- 图片创作：优秀案例库（挂到 body，避免沉浸态 overflow 影响） -->
        <Teleport to="body">
            <CaseImage ref="imageCaseRef" @choose="onChooseImageCase" />
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import {
    drawOptimizeImagePrompt,
    drawOptimizeVideoPrompt,
    drawPptFollowup,
} from "@/api/drawing";
import useDrawTaskPoll from "../_composables/useDrawTaskPoll";
import {
    loadDrawHistory,
    fetchDrawConversationDetail,
    deleteDrawConversation,
    lastUserTextFromDetail,
    type DrawHistorySessionItem,
} from "../_composables/useDrawConversation";
import { useWelcomeDrawShared, resolveDrawErrorMsg } from "../_composables/useWelcomeDrawShared";
import { useWelcomeImageDraw, type ImgAttachment } from "../_composables/useWelcomeImageDraw";
import { useWelcomeVideoDraw } from "../_composables/useWelcomeVideoDraw";
import { useWelcomePptDraw } from "../_composables/useWelcomePptDraw";
import OutputPpt from "./welcome/output-ppt.vue";
import OutputMap from "./welcome/output-map.vue";
import MapCats from "./welcome/map-cats.vue";
import CaseImage from "./welcome/case-image.vue";
import ImageChatPane from "./welcome/image-chat-pane.vue";
import VideoChatPane from "./welcome/video-chat-pane.vue";
import DigitalChatPane from "./welcome/digital-chat-pane.vue";
import PptChatPane from "./welcome/ppt-chat-pane.vue";
import WelcomeModeTabs from './welcome/mode-tabs.vue'
import InputToolbarLeft from './welcome/input-toolbar-left.vue'
import InputToolbarRight from './welcome/input-toolbar-right.vue'
import HistoryPopup from './welcome/history-popup.vue'
import {
    DIGITAL_AVATARS,
    DIGITAL_STYLES,
    DIGITAL_VOICES,
    IMAGE_REF_MAX,
    MATERIAL_MODES
} from '../_enums/welcome-toolbar'
import { useWelcomeFiles, isBlobUrl } from '../_composables/useWelcomeFiles'
import { useChatStore } from '../_modules/stores/chat'
import Chatting from '@/components/chatting/chatting.vue'
import FileLists from '@/components/chatting/file-lists.vue'
import type { MentionItem } from "@/components/chatting/at-mention-pop.vue";
import {
    genDigitalFollowupForm,
    genDigitalFollowupParams,
    genDigitalFinalCopy,
    genVideoTitle,
    type CozeField,
} from "@/utils/coze";
import { createHotWrite } from "@/api/hot_write";
import {
    createShanjianVideo,
    createPlainDigitalHumanVideo,
    type ShanjianType,
    type ShanjianVideoPayload,
    type MaterialItem,
} from "@/api/digital_video";
import { uploadImage, uploadVideo } from "@/api/app";
import { getPersonList } from "@/api/person";
import { storeToRefs } from "pinia";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { replaceState } from "@/utils/util";
import feedback from "@/utils/feedback";
import { mapLeadExport } from "@/api/map_lead";
import { CHAT_UPLOAD_ACCEPT } from "@/components/chatting/upload-rules";

type WorkflowMode = "chat" | "image" | "ppt" | "map" | "video" | "digital";

const route = useRoute();

const userStore = useUserStore();
const appStore = useAppStore();
const { userInfo, userTokens } = storeToRefs(userStore);
const userAvatar = computed(() => userInfo.value?.avatar || "");
// 当前进行中的会话 id(仅内存,不再落 localStorage)
const currentSessionId = ref<string | null>(null);
// 每个模式各自记一个"当前会话 id" — 切 tab 时把现在的 id 暂存,切回来再恢复
const sessionIdByMode = reactive<Record<string, string | null>>({
    chat: null,
    image: null,
    ppt: null,
    video: null,
    map: null,
});

function ensureSession(mode: string, _title: string) {
    if (currentSessionId.value) return;
    currentSessionId.value = `${mode}_${Date.now()}`;
    sessionIdByMode[mode] = currentSessionId.value;
}

interface ModeTab {
    key: string;
    label: string;
    icon: string;
    placeholder: string;
    badge?: { text: string; type: "new" | "hot" };
}
interface ModelOpt {
    name: string;
    title: string;
    icon: string;
}
/** 气泡展示用附件：远程 URL 直接复用；blob 另建一份，避免发送后 revoke 输入区预览导致裂图 */
const buildBubbleAttachments = (files: { id: number; url: string; name: string; raw: File }[]): ImgAttachment[] =>
    files.map((f) => {
        const src = String(f.url || "");
        const url = isBlobUrl(src) && f.raw ? URL.createObjectURL(f.raw) : src;
        return { id: f.id, url, name: f.name };
    });

const props = withDefaults(
    defineProps<{
        agentList?: MentionItem[];
    }>(),
    {
        agentList: () => [],
    },
);

const emit = defineEmits<{
    (
        e: "send",
        payload: {
            text: string;
            files: File[];
            fileList: Array<{
                uid: number;
                id?: string | number;
                file_id?: string | number;
                url: string;
                name: string;
                type?: string;
                size?: number;
            }>;
            mode: string;
            webSearch: boolean;
            agent: MentionItem | null;
        },
    ): void;
    (e: "change-mode", key: string): void;
    (e: "mention-agent", agent: MentionItem | null): void;
}>();

const CHAT_MODELS: ModelOpt[] = [
    { name: "ChatGPT", title: "ChatGPT", icon: "local-icon-models-chatgpt" },
    { name: "Claude", title: "Claude", icon: "local-icon-models-claude" },
    { name: "DeepSeek", title: "DeepSeek", icon: "local-icon-models-deepseek" },
    { name: "通义千问", title: "通义千问", icon: "local-icon-models-tongyi" },
    { name: "豆包", title: "豆包", icon: "local-icon-models-doubao" },
];
const IMAGE_MODELS: ModelOpt[] = [
    {
        name: "ChatGPT",
        title: "ChatGPT (DALL·E 3)",
        icon: "local-icon-models-chatgpt",
    },
    { name: "即梦", title: "即梦 AI", icon: "local-icon-models-jimeng" },
];
const PPT_MODELS: ModelOpt[] = [
    {
        name: "ChatGPT",
        title: "ChatGPT",
        icon: "local-icon-models-chatgpt",
    },
];

const modelBadges = computed<ModelOpt[]>(() => {
    if (activeMode.value === "ppt") return PPT_MODELS;
    if (activeMode.value === "image") return IMAGE_MODELS;
    return CHAT_MODELS;
});
const availableModels = computed<ModelOpt[]>(() => modelBadges.value);
const selectedModelIdx = ref(2); // 默认 DeepSeek(chat 模式下)
const currentModel = computed(
    () => availableModels.value[Math.min(selectedModelIdx.value, availableModels.value.length - 1)],
);

const modeTabs: ModeTab[] = [
    {
        key: "chat",
        label: "AI对话",
        icon: "el-icon-ChatLineRound",
        placeholder: "发送消息，输入 @ 选择智能体",
    },
    {
        key: "image",
        label: "图片创作",
        icon: "el-icon-Picture",
        placeholder: "输入图片生成的提示词，例如：浩瀚的银河中一艘宇宙飞船驶过",
    },
    {
        key: "ppt",
        label: "PPT生成",
        icon: "el-icon-Monitor",
        placeholder: "输入演讲主题，例如：2026年Q1业务总结",
        badge: { text: "NEW", type: "new" },
    },
    {
        key: "map",
        label: "地图获客",
        icon: "el-icon-LocationFilled",
        placeholder: "告诉我你想找什么商家，例如：帮我找北京东城区的咖啡店",
    },
    {
        key: "video",
        label: "视频生成",
        icon: "el-icon-VideoCamera",
        placeholder: "输入视频脚本或画面描述",
        badge: { text: "HOT", type: "hot" },
    },
    // 数字人入口暂隐藏，功能代码保留，恢复时取消注释即可
    // {
    //     key: "digital",
    //     label: "数字人",
    //     icon: "el-icon-User",
    //     placeholder: "描述你想要的数字分身，例如：女性、亲切、温柔的客服形象",
    // },
];

const activeMode = ref("chat");
// 「显示用」模式:控制聊天输出区渲染哪条对话历史;与 activeMode 在切换 tab 时同步
const displayedMode = ref("chat");
// 数字人口播混剪:输入模式
type DigitalInputMode = "ai-copy" | "douyin-clone";
const DIGITAL_INPUT_MODES: {
    value: DigitalInputMode;
    label: string;
    icon: string;
}[] = [
    { value: "ai-copy", label: "AI 文案生成", icon: "el-icon-MagicStick" },
    { value: "douyin-clone", label: "爆款仿写", icon: "el-icon-VideoCamera" },
];
const digitalInputMode = ref<DigitalInputMode>("ai-copy");

const currentPlaceholder = computed(() => {
    // 数字人口播混剪 — 按输入模式给不同提示
    if (activeMode.value === "digital" && digitalState.mode === "dh-montage") {
        return digitalInputMode.value === "douyin-clone"
            ? "粘贴抖音视频分享链接,例如:https://v.douyin.com/xxxxxx 或 8.88 复制打开抖音…"
            : "输入文案话题或要表达的内容,AI 会自动生成口播文案";
    }
    return modeTabs.find((t) => t.key === activeMode.value)?.placeholder ?? "";
});

// 爆款仿写:校验输入是否含抖音分享链接(支持 v.douyin.com / www.douyin.com / iesdouyin.com)
function isDouyinShareLink(text: string): boolean {
    return /(https?:\/\/)?(v\.douyin\.com|www\.douyin\.com|www\.iesdouyin\.com|iesdouyin\.com)\/\S+/i.test(text);
}

/** 把 UI 上的 materialMode 映射成后端的 visual_material_source(1/2/3) */
function visualMaterialSourceFromUI(): 1 | 2 | 3 {
    const m = digitalState.settings.materialMode;
    if (m === "ai") return 1;
    if (m === "ai_persona") return 2;
    if (m === "persona") return 3;
    // "current"(当前素材)在爆款仿写后端没有对应项,兜底 AI+人设素材
    return 2;
}

/** 把 mock 人设 ID(pe-1...)抽取尾部数字当 persona_id;真接后端时换成真实下拉的 id */
function digitalPersonaIdNum(): number {
    const m = /(\d+)$/.exec(digitalState.personaId);
    return m ? parseInt(m[1], 10) : 0;
}

/** 数字人 AI 追问入口:推一个 thinking 气泡 + 触发第 1 步 */
function startDigitalFollowup(sourceText: string) {
    const fId = ++_digitalChatId;
    digitalChat.value.push({
        id: fId,
        role: "followup",
        sourceText,
        state: "thinking-form",
    });
    runDigitalFollowupStep1(fId, sourceText);
}

async function runDigitalFollowupStep1(msgId: number, sourceText: string) {
    const msg = digitalChat.value.find((m) => m.id === msgId);
    if (!msg || msg.role !== "followup") return;
    msg.state = "thinking-form";
    try {
        const schema = await genDigitalFollowupForm(sourceText);
        msg.description = schema.description;
        msg.pptType = schema.ppt_type;
        msg.fields = schema.fields;
        msg.state = "form-ready";
    } catch (e: any) {
        msg.state = "failed";
        msg.errorMsg = e?.message ?? String(e);
        feedback.msgError(`追问表单生成失败:${msg.errorMsg}`);
    }
}

async function onDigitalFollowupConfirm(
    followupId: number,
    payload: { answers: Record<string, any>; summary: Record<string, string> },
) {
    const followupMsg = digitalChat.value.find((m) => m.id === followupId);
    if (!followupMsg || followupMsg.role !== "followup") return;
    // 1) 折叠表单气泡,保留答案概要
    followupMsg.summary = payload.summary;
    followupMsg.answers = payload.answers;
    followupMsg.state = "collapsed";

    // 2) 新开一个 copies 气泡,跑 params → copy 两步
    const copiesId = ++_digitalChatId;
    const newCopiesMsg: DigitalCopiesMsg = {
        id: copiesId,
        role: "copies",
        state: "thinking-params",
        copies: [],
        regenerating: [],
    };
    digitalChat.value.push(newCopiesMsg);
    const copiesMsg = digitalChat.value.find((m) => m.id === copiesId);
    if (!copiesMsg || copiesMsg.role !== "copies") return;

    try {
        const params = await genDigitalFollowupParams(payload.answers);
        copiesMsg.params = params;
        copiesMsg.state = "thinking-copy";
        const list = await genDigitalFinalCopy(params);
        copiesMsg.copies = list;
        copiesMsg.regenerating = list.map(() => false);
        copiesMsg.state = "ready";
    } catch (e: any) {
        copiesMsg.state = "failed";
        copiesMsg.errorMsg = e?.message ?? String(e);
        feedback.msgError(`文案生成失败:${copiesMsg.errorMsg}`);
    }
}

function onDigitalFollowupCancel(msgId: number) {
    // 取消问卷 → 直接从对话里移除这条 followup
    const idx = digitalChat.value.findIndex((m) => m.id === msgId);
    if (idx >= 0) digitalChat.value.splice(idx, 1);
}

/** 单条文案重新生成:用同一 params + Amount=1 再请求一次,替换该条 */
/** AI 追问 OFF 时的直跑流程:
 *   用户文本 → 7650725914385612840(params 工作流,如有人设一起带) → params
 *   params + Amount=settings.count → 7639931710104633370 → copies[]
 *   自动调 startVideoTask(copies)(走对应模式的视频接口) */
async function startDigitalDirectFlow(sourceText: string) {
    const persona = currentDigitalPersona.value;
    // 给 params 工作流的入参:文本主键固定叫 Text(沿用现有约定),人设字段尽量平铺以兼容
    const answers: Record<string, any> = { Text: sourceText, input: sourceText };
    if (persona) {
        answers.persona_name = persona.name;
        answers.persona_introduced = persona.introduced || persona.name;
    }
    // 推一个 copies 气泡当过程载体
    const copiesId = ++_digitalChatId;
    digitalChat.value.push({
        id: copiesId,
        role: "copies",
        state: "thinking-params",
        copies: [],
        regenerating: [],
    });
    const live = digitalChat.value.find((m) => m.id === copiesId);
    if (!live || live.role !== "copies") return;

    try {
        const params = await genDigitalFollowupParams(answers);
        live.params = params;
        live.state = "thinking-copy";
        const list = await genDigitalFinalCopy(params, {
            amountOverride: digitalState.settings.count,
        });
        live.copies = list;
        live.regenerating = list.map(() => false);
        live.state = "ready";
        // 自动用全部文案提交视频任务,然后折叠这个 copies 气泡
        live.state = "collapsed";
        startVideoTask(list);
    } catch (e: any) {
        live.state = "failed";
        live.errorMsg = typeof e === "string" ? e : e?.message ?? String(e);
        feedback.msgError(`文案生成失败:${live.errorMsg}`);
    }
}

async function regenerateOneCopy(copiesId: number, idx: number) {
    const msg = digitalChat.value.find((m) => m.id === copiesId);
    if (!msg || msg.role !== "copies" || !msg.params) return;
    if (msg.regenerating[idx]) return;
    msg.regenerating[idx] = true;
    try {
        const list = await genDigitalFinalCopy(msg.params, { amountOverride: 1 });
        if (list[0]) msg.copies[idx] = list[0];
    } catch (e: any) {
        feedback.msgError(`重新生成失败:${e?.message ?? e}`);
    } finally {
        msg.regenerating[idx] = false;
    }
}

/** 用单条文案生成视频:折叠当前 copies 气泡,推出 video-task 气泡(占位) */
function useOneCopy(copiesId: number, idx: number) {
    const msg = digitalChat.value.find((m) => m.id === copiesId);
    if (!msg || msg.role !== "copies") return;
    const copy = msg.copies[idx];
    if (!copy?.trim()) {
        feedback.msgWarning("这条文案为空,不能生成");
        return;
    }
    msg.state = "collapsed";
    startVideoTask([copy]);
}

/** 用所有文案生成视频 */
function useAllCopies(copiesId: number) {
    const msg = digitalChat.value.find((m) => m.id === copiesId);
    if (!msg || msg.role !== "copies") return;
    const list = msg.copies.filter((c) => c && c.trim());
    if (!list.length) {
        feedback.msgWarning("没有可用的文案");
        return;
    }
    msg.state = "collapsed";
    startVideoTask(list);
}

/** 已经上传到 OSS 的 refImage 缓存:本地文件 id → OSS url + 类型
 *  避免同一批文件用户连点几次"使用全部生成视频"时重复上传 */
const uploadedMaterialUrls = reactive<Record<number, MaterialItem>>({});

/** 把当前 refImages 里所有文件上传到 OSS,带进度回写到 task 气泡 */
async function uploadRefImagesWithProgress(taskMsg: {
    uploadProgress?: { done: number; total: number };
}): Promise<MaterialItem[]> {
    if (!refImages.value.length) return [];
    const results: MaterialItem[] = [];
    const total = refImages.value.length;
    if (taskMsg.uploadProgress) taskMsg.uploadProgress = { done: 0, total };
    for (const f of refImages.value) {
        if (uploadedMaterialUrls[f.id]) {
            results.push(uploadedMaterialUrls[f.id]);
            if (taskMsg.uploadProgress) taskMsg.uploadProgress.done++;
            continue;
        }
        const isVideo = f.raw.type.startsWith("video/");
        const resp: any = isVideo
            ? await uploadVideo({ file: f.raw, name: "file" })
            : await uploadImage({ file: f.raw, name: "file" });
        const url = resp?.uri || resp?.url || resp?.src || resp?.data?.uri || resp?.data?.url;
        if (!url) {
            console.warn("[material-upload] 返回无 url:", resp);
            throw new Error(`「${f.name}」上传无 URL 返回`);
        }
        const item: MaterialItem = {
            url,
            type: isVideo ? "video" : "image",
            name: f.name,
        };
        uploadedMaterialUrls[f.id] = item;
        results.push(item);
        if (taskMsg.uploadProgress) taskMsg.uploadProgress.done++;
    }
    return results;
}

/** 把 UI 上的 digitalState.mode 映射成后端的 shanjian_type(1/2/3/4) */
function shanjianTypeFromMode(mode: string): ShanjianType | null {
    switch (mode) {
        case "dh-montage":
            return 1;
        case "human-montage":
            return 2;
        case "material-montage":
            return 3;
        case "news":
            return 4;
        default:
            return null; // dh-video 走 /human/createVideo,不走 shanjian
    }
}
/** UI 的 materialMode 映射成 extra.material(顺序/随机)+ volume */
function materialFlagFromMode(mode: string): 0 | 1 {
    // ai / current → 随机 ; persona / ai_persona → 按序(用人设素材库)
    return mode === "persona" || mode === "ai_persona" ? 1 : 0;
}

/** 创建 video-task 气泡 + 真实调后端 */
async function startVideoTask(copies: string[]) {
    const id = ++_digitalChatId;
    digitalChat.value.push({
        id,
        role: "video-task",
        copies,
        state: refImages.value.length > 0 ? "uploading" : "starting",
        uploadProgress: refImages.value.length > 0 ? { done: 0, total: refImages.value.length } : undefined,
    });
    const live = digitalChat.value.find((m) => m.id === id);
    if (!live || live.role !== "video-task") return;

    // 先把素材上传到 OSS(已上传过会直接命中缓存)
    let materials: MaterialItem[] = [];
    if (refImages.value.length > 0) {
        try {
            materials = await uploadRefImagesWithProgress(live);
        } catch (e: any) {
            live.state = "failed";
            live.errorMsg = e?.message ?? "素材上传失败";
            return;
        }
    }
    live.state = "starting";

    try {
        const mode = digitalState.mode;
        const avatar = currentDigitalAvatar.value;
        if (!avatar) throw new Error("请先选择形象");

        // 数字人视频(纯口播)→ /human/createVideo,用蝉镜 anchor_id(C-... 格式)
        if (mode === "dh-video") {
            if (!avatar.chanjingAnchorId) throw new Error("当前形象没有蝉镜 anchor_id");
            const tasks = await Promise.allSettled(
                copies.map((c) =>
                    createPlainDigitalHumanVideo({
                        anchor_id: avatar.chanjingAnchorId,
                        voice_id: digitalState.voiceId || undefined,
                        text: c,
                        title: c.slice(0, 20),
                    }),
                ),
            );
            const failed = tasks.filter((r) => r.status === "rejected");
            if (failed.length === copies.length) {
                throw new Error((failed[0] as PromiseRejectedResult).reason?.message ?? "全部失败");
            }
            live.state = "ready";
            return;
        }

        // 其余 4 种走闪剪 shanjianVideoSetting,用闪剪 anchor_id
        const shanjianType = shanjianTypeFromMode(mode);
        if (shanjianType === null) throw new Error(`不支持的模式:${mode}`);
        if (!avatar.shanjianAnchorId) throw new Error("当前形象没有闪剪 anchor_id");

        const persona = currentDigitalPersona.value;
        // 每条文案各自调一遍 7639931710104633370(Serial_Number=8)拿标题,失败回退到截断文案
        const titles = await Promise.all(copies.map((c) => genVideoTitle(c).catch(() => c.slice(0, 20))));
        const payload: ShanjianVideoPayload = {
            shanjian_type: shanjianType,
            title: titles[0] || copies[0]?.slice(0, 20) || "数字人视频",
            anchor: [{ anchor_id: avatar.shanjianAnchorId }],
            copywriting: copies.map((c, i) => ({
                content: c,
                title: titles[i] || c.slice(0, 20),
            })),
            // 后端要求 character_design 必须非空(即使用户没选人设),用空字段占位
            character_design: persona
                ? [
                      {
                          name: persona.name,
                          introduced: persona.introduced || persona.name,
                      },
                  ]
                : [{ name: "", introduced: "" }],
            extra: {
                video_count: digitalState.settings.count,
                volume: digitalState.settings.useOriginalAudio ? 0.3 : 0,
                // 后端在 material=0(随机)且素材为空时会 random_int(0,-1) 崩;
                // 没有素材时强制 1(顺序/使用全部) — 走到使用全部素材的安全分支
                material: materials.length > 0 ? materialFlagFromMode(digitalState.settings.materialMode) : 1,
                human: 1,
                music: 1, // 同样兜底,避免空 music 数组配 0 触发同类问题
                clip: 1, // clip 也用顺序,空 clipData 时走 fallback 模板分支(更稳)
            },
        };
        // 真人口播 / 新闻体:用户没"音色选择",不传 voice;其它两个传
        if (mode === "dh-montage" || mode === "material-montage") {
            payload.voice = digitalState.voiceId ? [{ voice_id: digitalState.voiceId }] : [];
        }
        // 已上传素材
        if (materials.length > 0) {
            payload.material = materials;
        }

        const res = await createShanjianVideo(payload);
        // 后端返回里通常带 setting id;Toast 给个反馈
        const settingId = (res as any)?.id ?? (res as any)?.setting_id ?? "?";
        feedback.msgSuccess(`视频任务已提交 #${settingId},渲染需要几分钟`);
        live.state = "ready";
    } catch (e: any) {
        live.state = "failed";
        // ⚠️ $request 失败时直接 Promise.reject(msg) — e 经常就是字符串
        live.errorMsg = typeof e === "string" ? e : e?.message ?? e?.msg ?? String(e);
        feedback.msgError(`视频任务提交失败:${live.errorMsg}`);
    }
}

async function submitHotWrite(url: string) {
    try {
        const res = await createHotWrite({
            url,
            persona_id: digitalPersonaIdNum(),
            visual_material_source: visualMaterialSourceFromUI(),
        });
        feedback.msgSuccess(`爆款仿写任务已提交(ID:${(res as any)?.id ?? "?"}),稍后到我的作品查看`);
    } catch (e: any) {
        feedback.msgError(`爆款仿写提交失败:${e?.message ?? e?.msg ?? e}`);
    }
}

const inputText = ref("");
const webSearch = ref(false);
const promptOptimizing = ref(false);
const followupOn = ref(false);
const {
    refImages,
    isDragover,
    isRefUploading,
    welcomeFileList,
    syncWelcomeFiles,
    getChatFilePayload,
    onDrop,
    clearRefs,
    uploadFilesToUrls,
    chooseCaseImage,
} = useWelcomeFiles({
    getMaxRefs: () => (activeMode.value === "image" ? IMAGE_REF_MAX : 9),
});
const chatStore = useChatStore();
const openPopup = ref<string>("");
const lastUserText = ref("");
const lastUserAttachments = ref<{ id: number; name: string; url: string }[]>([]);
const welcomeChattingRef = ref<InstanceType<typeof Chatting> | null>(null);
const agentListComputed = computed(() => props.agentList);
const selectedAgent = ref<MentionItem | null>(null);

function onWelcomeToolbarFiles(list: any[] = []) {
    syncWelcomeFiles(list);
}

function handleWelcomeMentionAgent(agent: MentionItem | null) {
    selectedAgent.value = agent;
    emit("mention-agent", agent);
    if (agent && openPopup.value === "model") openPopup.value = "";
}

function resetMention() {
    selectedAgent.value = null;
    welcomeChattingRef.value?.setSelectedAgent(0);
    emit("mention-agent", null);
}

function clearSelectedAgent() {
    resetMention();
}

function handleChattingSend(text: string) {
    inputText.value = text;
    handleSend();
}

function getChatConfig() {
    return welcomeChattingRef.value?.getChatConfig?.() || {};
}

// 每个模式各记一个后端会话 id(0=未建),发送时带上,后端返回后回填
const drawConvId = reactive<{ image: number; video: number; ppt: number }>({ image: 0, video: 0, ppt: 0 });

const { pollTask: pollDrawTask } = useDrawTaskPoll();

const { ensureHasTokens, ensureEnoughTokens, bindDrawConversation, clearDrawConversationUrl } =
    useWelcomeDrawShared({
        drawConvId,
        userTokens,
        userStore,
    });

const historyList = ref<DrawHistorySessionItem[]>([]);
const historyLoading = ref(false);
const historyRestoring = ref(false);
const historyDeletingId = ref("");

async function loadModeHistory() {
    if (activeMode.value !== "image" && activeMode.value !== "video" && activeMode.value !== "ppt") {
        historyLoading.value = false;
        historyList.value = [];
        return;
    }
    historyLoading.value = true;
    try {
        historyList.value = await loadDrawHistory(activeMode.value);
    } catch {
        historyList.value = [];
    } finally {
        historyLoading.value = false;
    }
}

function refreshHistoryIfOpen() {
    if (openPopup.value === "history") loadModeHistory();
}

const {
    imageChat,
    imageState,
    imageHasOutput,
    imageDrawModels,
    selectedImageModelId,
    currentImageDrawModel,
    imageMaxCount,
    selectImageDrawModel,
    nextImageChatId,
    createImageTaskShell,
    callGptImage,
    setRatio,
    changeCount,
    onImageRegenerate,
    restoreImageConversation,
} = useWelcomeImageDraw({
    drawConvId,
    appStore,
    pollDrawTask,
    uploadFilesToUrls,
    refreshHistoryIfOpen,
    bindDrawConversation,
    ensureEnoughTokens,
    ensureHasTokens,
    displayedMode,
    openPopup,
});

const {
    videoChat,
    videoState,
    videoDrawModels,
    selectedVideoModelId,
    currentVideoDrawModel,
    selectVideoDrawModel,
    nextVideoChatId,
    createVideoTaskShell,
    startVideoGen,
    setVideoRatio,
    changeVideoCount,
    onVideoRegenerate,
    restoreVideoConversation,
    DRAW_VIDEO_DEFAULT,
} = useWelcomeVideoDraw({
    drawConvId,
    appStore,
    pollDrawTask,
    refreshHistoryIfOpen,
    bindDrawConversation,
    ensureEnoughTokens,
    ensureHasTokens,
    displayedMode,
    openPopup,
});

const {
    pptChat,
    pptState,
    pptOpen,
    activePptMsgId,
    activePptMsg,
    pptDrawModels,
    selectedPptModelId,
    currentPptDrawModel,
    selectPptDrawModel,
    customPagesInput,
    isCustomPagesValid,
    applyCustomPages,
    nextPptId,
    resolvePptDrawModel,
    streamPptSlides,
    regenerateSlideImage,
    restorePptConversation,
    onFollowupConfirm,
    onFollowupCancel,
    onViewPpt,
    onRegeneratePpt,
    onExportPptMsg,
    onClosePpt,
    onPptExport,
} = useWelcomePptDraw({
    drawConvId,
    appStore,
    userTokens,
    pollDrawTask,
    refreshHistoryIfOpen,
    bindDrawConversation,
    ensureHasTokens,
    displayedMode,
    openPopup,
    lastUserText,
});

// ─── 数字人模式:5 种视频形态 + 形象/人设/风格/音色/设置(目前先用假数据占位) ──────
// ⭐ 形象:目前线上只有一条「默认形象」是真克隆好的(克隆出来的视频包含了音频,所以同时拿到形象+音色)
//    每条形象同时挂两个 anchor_id:
//      - shanjianAnchorId:闪剪体系(数字人口播混剪/真人/素材/新闻体)
//      - chanjingAnchorId:蝉镜体系(纯数字人视频 /human/createVideo)
const DIGITAL_PERSONAS = ref<{ id: string; name: string; emoji: string; bg: string; introduced?: string }[]>([]);
// ⭐ 音色:目前线上只有一条「默认音色」(随默认形象克隆出来的)
// 设置浮窗里的音色选项:多一项"视频原声"作为默认
const digitalState = reactive({
    mode: "dh-montage",
    // 默认选中"默认形象"
    avatarId: "default",
    // 默认不选人设,选了之后才出现"使用人设素材"选项
    personaId: "",
    styleId: "random",
    // 默认音色
    voiceId: "6a2d1196b864400031887248",
    settings: {
        count: 1,
        useOriginalAudio: false,
        materialMode: "current" as "ai" | "persona" | "ai_persona" | "current",
        voice: "original",
    },
});
const currentDigitalAvatar = computed(() => DIGITAL_AVATARS.find((a) => a.id === digitalState.avatarId));
const currentDigitalPersona = computed(() => DIGITAL_PERSONAS.value.find((p) => p.id === digitalState.personaId));
const currentDigitalStyle = computed(() => DIGITAL_STYLES.find((s) => s.id === digitalState.styleId));
const currentDigitalVoice = computed(() => DIGITAL_VOICES.find((v) => v.id === digitalState.voiceId));
// 音色选择仅在 数字人口播混剪 / 数字人视频 / 素材混剪 显示
const showDigitalVoice = computed(() => ["dh-montage", "dh-video", "material-montage"].includes(digitalState.mode));
// 设置浮窗里的"音色选择"在 新闻体 / 真人口播混剪 模式下隐藏
const showSettingVoice = computed(() => !["news", "human-montage"].includes(digitalState.mode));
// 数字人视频模式没有"素材原声 / 素材应用方式"两栏
const showMaterialSettings = computed(() => digitalState.mode !== "dh-video");

// 工具栏的"上传"按钮:
//   - 地图模式:不显示
//   - PPT 模式:上传文档暂未接入生成链路,不显示(与小程序同步)
//   - 数字人模式:除"数字人视频"外都显示("素材上传")
//   - 其它模式:沿用原有逻辑(图片为参考图上传;聊天为文件上传)
const showFileUploadInToolbar = computed(() => {
    if (activeMode.value === "map" || activeMode.value === "ppt") return false;
    if (activeMode.value === "digital") return digitalState.mode !== "dh-video";
    return true;
});
const uploadButtonLabel = computed(() => {
    if (activeMode.value === "digital") return "素材上传";
    if (activeMode.value === "chat") return "文件上传";
    if (activeMode.value === "ppt") return "上传文档";
    return "参考图上传";
});
const uploadAccept = computed(() => {
    if (activeMode.value === "digital") return "image/*,video/*"; // 素材可以是图/视频
    if (activeMode.value === "chat") return CHAT_UPLOAD_ACCEPT;
    if (activeMode.value === "ppt") return ".pdf,.doc,.docx,.txt";
    return "image/*";
});
// 素材应用方式:
//   - 只有"数字人口播混剪"有 AI 找素材 / AI + 人设素材
//   - 没选人设(personaId 为空)时,移除"使用人设素材"
const availableMaterialModes = computed(() => {
    const base =
        digitalState.mode === "dh-montage"
            ? MATERIAL_MODES
            : MATERIAL_MODES.filter((m) => m.value !== "ai" && m.value !== "ai_persona");
    if (!digitalState.personaId) {
        // 没选人设 → "使用人设素材"和"AI+人设素材"都没意义
        return base.filter((m) => m.value !== "persona" && m.value !== "ai_persona");
    }
    return base;
});
function changeDigitalCount(delta: number) {
    digitalState.settings.count = Math.max(1, Math.min(9, digitalState.settings.count + delta));
}
function onPreviewStyle(s: { id: string; name: string }) {
    feedback.msg(`预览「${s.name}」(占位)`);
}
// 切模式 / 切人设时,若当前选中的素材方式不可用,自动回退到第一个可用项
watch(
    () => [digitalState.mode, digitalState.personaId],
    () => {
        const options = availableMaterialModes.value.map((m) => m.value);
        if (!options.includes(digitalState.settings.materialMode)) {
            digitalState.settings.materialMode = (options[0] as any) ?? "current";
        }
    },
);

// ─── 数字人 AI 追问:3 步 Coze 链路(form → params → final copy) ─────────
interface DigitalFollowupMsg {
    id: number;
    role: "followup";
    sourceText: string;
    state: "thinking-form" | "form-ready" | "collapsed" | "failed";
    description?: string;
    pptType?: string;
    fields?: CozeField[];
    /** 折叠态展示用:用户填好的概要(label: value) */
    summary?: Record<string, string>;
    /** 用户原始答案(给后续步骤复用) */
    answers?: Record<string, any>;
    errorMsg?: string;
}
interface DigitalCopiesMsg {
    id: number;
    role: "copies";
    state: "thinking-params" | "thinking-copy" | "ready" | "collapsed" | "failed";
    /** 第 2 步拿到的参数(给单条"重新生成"复用) */
    params?: any;
    /** 多条文案 */
    copies: string[];
    /** 每条文案是否正在单独重新生成 */
    regenerating: boolean[];
    errorMsg?: string;
}
interface DigitalVideoTaskMsg {
    id: number;
    role: "video-task";
    /** 这次提交的文案 */
    copies: string[];
    state: "uploading" | "starting" | "ready" | "failed";
    /** 已上传素材数 / 总数 */
    uploadProgress?: { done: number; total: number };
    errorMsg?: string;
}
type DigitalChatMsg =
    | { id: number; role: "user"; text: string }
    | DigitalFollowupMsg
    | DigitalCopiesMsg
    | DigitalVideoTaskMsg;
const digitalChat = ref<DigitalChatMsg[]>([]);
let _digitalChatId = 1;
const digitalFollowupOn = ref(false);

const canSend = computed(() => {
    const hasContent = inputText.value.trim().length > 0 || refImages.value.length > 0;
    return hasContent && !isRefUploading.value;
});
// 地图是否有可展示内容(不能依赖 mapOutRef.batches 深读,组件 ref 内层变化不会触发 computed)
const mapHasOutput = ref(false);
const hasMapOutput = computed(() => mapHasOutput.value);
// 地图模式:用户一发送就立刻进沉浸态(不等 Coze + 高德返回)
const mapInteracting = ref(false);
// 是否有「当前显示模式」的输出 — 不能跨模式共用,否则地图有结果时切到 PPT 会 justify-start 把输入顶上去
const hasOutput = computed(() => {
    switch (displayedMode.value) {
        case "image":
            return imageChat.value.length > 0;
        case "ppt":
            return pptChat.value.length > 0;
        case "video":
            return videoChat.value.length > 0;
        case "digital":
            return digitalChat.value.length > 0;
        case "map":
            return hasMapOutput.value;
        default:
            return false;
    }
});
// 「沉浸式 / 对话模式」:发送后顶部 hello + 模型组隐藏,只留 tabs + 输入 + 输出
const isImmersive = computed(
    () =>
        (displayedMode.value === "image" && imageChat.value.length > 0) ||
        (displayedMode.value === "ppt" && pptChat.value.length > 0) ||
        (displayedMode.value === "video" && videoChat.value.length > 0) ||
        (displayedMode.value === "digital" && digitalChat.value.length > 0) ||
        (displayedMode.value === "map" && (mapInteracting.value || hasMapOutput.value)),
);

// 最近一次用户输入(用于显示用户气泡)
// lastUserText / lastUserAttachments 已在 composable 初始化前声明

// 助手头像(气泡左侧)— 跟当前正在展示的对话走,而不是工具栏选的 tab
const assistantAvatar = computed(() => {
    if (displayedMode.value === "map") return { label: "高", bg: "linear-gradient(135deg, #60a5fa, #2563eb)" };
    // image / ppt / video:用当前模型的图标
    return { icon: currentModel.value?.icon, bg: "#fff", label: "AI" };
});

// 地图状态条数据(取自 output-map)
const mapTotal = computed(() => mapOutRef.value?.totalTarget ?? 0);
const mapFetched = computed(() => mapOutRef.value?.totalFetched ?? 0);
const mapDone = computed(() => mapTotal.value > 0 && mapFetched.value >= mapTotal.value);

const mapOutRef = ref<any>(null);
/** 地图会话 id 存父级,切走再切回时即使子组件异常重建也能按 id 恢复 */
const mapConversationId = ref("");
/** 地图最新用户文案(与其它模式的 lastUserText 隔离) */
const mapLastUserText = ref("");
const outputStreamRef = ref<HTMLElement | null>(null);
const workflowBottomSpacer = ref(0);
const outputStreamStyle = computed(() =>
    isImmersive.value && workflowBottomSpacer.value > 0
        ? { paddingBottom: `${workflowBottomSpacer.value}px` }
        : undefined,
);

function triggerWorkflowContentPushUp() {
    nextTick(() => {
        const container = outputStreamRef.value;
        if (!container) return;

        const userMessages = container.querySelectorAll<HTMLElement>(".msg.user");
        const latestUserMessage = userMessages[userMessages.length - 1];
        if (!latestUserMessage) return;

        workflowBottomSpacer.value = Math.max(0, container.clientHeight - latestUserMessage.offsetHeight - 16);

        nextTick(() => {
            const targetTop =
                latestUserMessage.getBoundingClientRect().top -
                container.getBoundingClientRect().top +
                container.scrollTop;
            container.scrollTo({ top: Math.max(0, targetTop), behavior: "smooth" });
        });
    });
}

// 沉浸模式下,有新消息时自动滚到底
watch(
    () => [imageChat.value.length, pptChat.value.length, mapOutRef.value?.batches?.length ?? 0],
    () => {
        nextTick(() => {
            if (outputStreamRef.value && workflowBottomSpacer.value === 0) {
                outputStreamRef.value.scrollTop = outputStreamRef.value.scrollHeight;
            }
        });
    },
);

function switchMode(key: string) {
    if (activeMode.value === key) return;
    // ⚠️ 不再清空各模式的对话历史 — 用户可能只是想"瞄一眼别的 tab",
    // 等他们真的在新 tab 里发送内容时才创建新会话。
    // 当前模式的 sessionId 存进 map,切回来再恢复。
    sessionIdByMode[activeMode.value] = currentSessionId.value;

    // 离开地图:URL 清掉避免刷新误进,但内存里保留 conversation_id / 用户文案供切回恢复
    if (activeMode.value === "map" && key !== "map") {
        const id = mapOutRef.value?.getConversationId?.() || mapConversationId.value;
        if (id) mapConversationId.value = id;
        if (lastUserText.value) mapLastUserText.value = lastUserText.value;
        clearMapConversationUrl();
    }

    // 离开图片/视频/PPT:清 draw URL,内存里保留 drawConvId 供切回
    if (
        (activeMode.value === "image" || activeMode.value === "video" || activeMode.value === "ppt") &&
        key !== "image" &&
        key !== "video" &&
        key !== "ppt"
    ) {
        clearDrawConversationUrl();
    }

    activeMode.value = key;
    // 切换类型时同步内容区,避免 tab 已是「AI对话」却仍显示地图结果
    displayedMode.value = key;
    // 离开沉浸布局时清掉推上去的 spacer,避免空模式输入框被顶到上方
    workflowBottomSpacer.value = 0;
    openPopup.value = "";
    if (key !== "chat") clearSelectedAgent();
    // 切到新 tab,加载该 tab 之前已建立的 sessionId(没有就是 null = 这次发送会新建会话)
    currentSessionId.value = sessionIdByMode[key] ?? null;
    // 新模式下默认模型:image / ppt → ChatGPT,其它 → DeepSeek
    selectedModelIdx.value = key === "image" || key === "ppt" ? 0 : 2;
    // 切回地图:恢复 URL + 沉浸态;若内存态丢了则按 conversation_id 拉记录
    if (key === "map") {
        void resumeMapSession();
    }
    // 切回图片/视频/PPT:把当前会话 id 写回 URL
    if (key === "image" || key === "video" || key === "ppt") {
        const id = key === "image" ? drawConvId.image : key === "video" ? drawConvId.video : drawConvId.ppt;
        if (id) bindDrawConversation(key, id);
        else clearDrawConversationUrl();
    }
    emit("change-mode", key);
}

/** 切回地图获客时恢复会话展示 */
async function resumeMapSession() {
    const id = mapOutRef.value?.getConversationId?.() || mapConversationId.value;
    if (id) {
        mapConversationId.value = id;
        onMapConversationChange(id);
    }
    if (mapLastUserText.value) lastUserText.value = mapLastUserText.value;

    const hasLocal = (mapOutRef.value?.batches?.length ?? 0) > 0 || (mapOutRef.value?.priorTurns?.length ?? 0) > 0;
    if (hasLocal) {
        mapInteracting.value = true;
        mapHasOutput.value = true;
        if (!lastUserText.value) {
            lastUserText.value = mapOutRef.value?.batches?.[0]?.query || mapLastUserText.value || "";
        }
        return;
    }

    if (!id) return;
    await nextTick();
    const result = await mapOutRef.value?.restoreConversation?.(id);
    if (result?.lastUserText) {
        lastUserText.value = result.lastUserText;
        mapLastUserText.value = result.lastUserText;
    }
    mapInteracting.value = true;
    mapHasOutput.value = true;
}

function togglePopup(name: string) {
    openPopup.value = openPopup.value === name ? "" : name;
}
function selectModel(idx: number) {
    selectedModelIdx.value = idx;
    openPopup.value = "";
}

const imageCaseRef = ref<{ open: () => void } | null>(null);
function openImageCaseLibrary() {
    imageCaseRef.value?.open();
}
async function onChooseImageCase(payload: { title: string; pic: string }) {
    await chooseCaseImage(payload, inputText);
}

async function handleSend() {
    if (isRefUploading.value) {
        feedback.msgWarning("文件正在上传中，请稍候再发送");
        return;
    }
    if (!canSend.value) return;
    // 生图 / 生视频：没算力直接不让提交
    if (
        (activeMode.value === "image" || activeMode.value === "video" || activeMode.value === "ppt") &&
        !ensureHasTokens()
    ) {
        return;
    }
    workflowBottomSpacer.value = 0;
    const text = inputText.value.trim();
    const files = refImages.value.map((f) => f.raw);
    const agent = selectedAgent.value;
    // 捕获上一轮文本(给地图模式归档历史用),再覆盖
    const prevUserText = lastUserText.value;
    lastUserText.value = text;

    // 用户真正发送内容时,才把"显示用"的对话窗口切到当前 tab
    // (在此之前用户可能只是切了 tab 在瞄,显示的还是前一轮对话)
    displayedMode.value = activeMode.value;
    // 快照当前参考图,用于气泡内"图+文同一气泡"展示（blob 会另建，避免随后 revoke 裂图）
    lastUserAttachments.value = buildBubbleAttachments(refImages.value);

    // ⭐ 一发送立刻在"最近会话"中新增/更新一条记录(标题用首条输入)
    const modeForSession: WorkflowMode =
        activeMode.value === "chat" ||
        activeMode.value === "image" ||
        activeMode.value === "ppt" ||
        activeMode.value === "map" ||
        activeMode.value === "video" ||
        activeMode.value === "digital"
            ? (activeMode.value as WorkflowMode)
            : "chat";
    ensureSession(modeForSession, text);

    if (activeMode.value === "chat") {
        // 只发送已上传完成的附件；未完成则拦截
        const fileList = getChatFilePayload();
        if (refImages.value.length && !fileList.length) {
            feedback.msgWarning("文件尚未上传完成，请稍候再发送");
            return;
        }
        // 双保险：先写入 chat store，再通过事件传给父级 sendMessage
        if (fileList.length) {
            chatStore.setFiles(fileList as any);
        }
        emit("send", {
            text,
            files,
            fileList,
            mode: "chat",
            webSearch: webSearch.value,
            agent,
        });
        resetMention();
    } else if (activeMode.value === "image") {
        // 一轮新对话:用户消息 + 助手任务消息(图+文同气泡)
        const attachments = buildBubbleAttachments(refImages.value);
        imageChat.value.push({
            id: nextImageChatId(),
            role: "user",
            text,
            attachments,
        });

        const taskShell = createImageTaskShell({
            prompt: text,
            ratio: imageState.ratio,
            resolution: imageState.resolution,
            width: imageState.width,
            height: imageState.height,
            count: Math.min(imageState.count, imageMaxCount.value),
            optimized: false,
        });
        const assistantId = nextImageChatId();
        imageChat.value.push({
            id: assistantId,
            role: "assistant",
            task: taskShell,
        });
        // ⚠️ 同 PPT 修复:必须拿数组里的 reactive 版本再启动 setTimeout
        const assistantMsg = imageChat.value.find((m) => m.id === assistantId);
        if (assistantMsg && assistantMsg.role === "assistant") {
            callGptImage(
                assistantMsg.task,
                text,
                refImages.value.map((f) => f.raw),
            );
        }
        imageHasOutput.value = true;
    } else if (activeMode.value === "ppt") {
        if (!resolvePptDrawModel()) {
            feedback.msgError("PPT 生成仅支持 image-2 模型，请先在后台启用");
            return;
        }
        // 一轮新对话
        pptChat.value.push({ id: nextPptId(), role: "user", text });
        // 优先匹配区间 X-Y(取中位数);否则匹配单值 "N页"(自定义);兜底 16
        const rangeMatch = pptState.pages.match(/(\d+)\s*-\s*(\d+)/);
        const singleMatch = pptState.pages.match(/^(\d+)\s*页$/);
        const pageCount = rangeMatch
            ? Math.round((parseInt(rangeMatch[1]) + parseInt(rangeMatch[2])) / 2)
            : singleMatch
            ? parseInt(singleMatch[1])
            : 16;
        const assistantId = nextPptId();
        pptChat.value.push({
            id: assistantId,
            role: "assistant",
            topic: text,
            pageCount,
            slides: [],
            state: followupOn.value ? "thinking" : "generating",
            // 快照当前选择的场景,避免用户后续切场景影响这次生成
            pptScene: pptState.scene,
        });
        // ⚠️ 必须拿数组里的 reactive 版本,直接用 push 进去的对象引用是 raw,
        //    在 setTimeout / await 里改 .state 会绕过 set 拦截 → 触发不了渲染
        const assistant = pptChat.value.find((it) => it.id === assistantId);
        if (!assistant || assistant.role !== "assistant") return;
        if (followupOn.value) {
            // 调后端 PPT 追问（中台 Coze，不计费）
            drawPptFollowup({ topic: text })
                .then((res: any) => {
                    assistant.fuDescription = res?.description ?? "";
                    assistant.fuPptType = res?.ppt_type ?? "";
                    assistant.fuFields = Array.isArray(res?.fields) ? res.fields : [];
                    assistant.state = "followup";
                })
                .catch((e) => {
                    feedback.msgError(`生成问卷失败:${resolveDrawErrorMsg(e)}`);
                    // 失败降级:不展示表单,直接生成
                    assistant.state = "generating";
                    streamPptSlides(assistant);
                });
        } else {
            // 直接生成:流式灌 slides 到这个助手 msg + 打开抽屉
            streamPptSlides(assistant);
        }
    } else if (activeMode.value === "map") {
        // 第二次以及之后的发送 → 把当前轮(prevText + 现有 batches)归档到 priorTurns
        // 这样向上滚就能看到之前的对话,而不是被新搜索覆盖掉
        if (hasMapOutput.value && prevUserText) {
            mapOutRef.value?.archiveCurrent?.(prevUserText);
        }
        mapLastUserText.value = text;
        // ⭐ 立刻翻进沉浸态(在 await 之前),让用户气泡 + 思考 loading 同步显现
        mapInteracting.value = true;
        mapHasOutput.value = true;
        triggerWorkflowContentPushUp();
        nextTick(() => {
            mapOutRef.value?.startFetch({ query: text, isFirst: true });
        });
    } else if (activeMode.value === "video") {
        // 一轮新对话:用户消息(可含参考图) + 助手视频任务
        const attachments = buildBubbleAttachments(refImages.value);
        videoChat.value.push({
            id: nextVideoChatId(),
            role: "user",
            text,
            attachments,
        });

        // 有参考图 → 图生视频(type=1),目前仅取第一张；仅用远程 URL，本地文件发送前再上传
        const hasRefImg = refImages.value.length > 0;
        let refImgUrl = "";
        if (hasRefImg) {
            const first = refImages.value[0];
            const url = String(first.url || "");
            if (/^https?:\/\//i.test(url)) {
                refImgUrl = url;
            } else if (first.raw) {
                const uploaded = await uploadFilesToUrls([first.raw]);
                refImgUrl = uploaded[0] || "";
            }
            if (!refImgUrl) {
                feedback.msgError("参考图上传失败，请重试");
                // 撤回刚推入的用户气泡，避免空跑；恢复输入便于重试
                videoChat.value.pop();
                inputText.value = text;
                return;
            }
        }
        const modelObj = currentVideoDrawModel.value;
        const taskShell = createVideoTaskShell({
            prompt: text,
            ratio: videoState.ratio,
            resolution: videoState.resolution,
            type: hasRefImg ? 1 : 0,
            typeName: hasRefImg ? "图生视频" : "文生视频",
            model: Number(modelObj?.id) || 0,
            modelName: modelObj?.name || DRAW_VIDEO_DEFAULT.model_name,
            count: 1,
            imageUrl: refImgUrl,
        });
        const assistantId = nextVideoChatId();
        videoChat.value.push({
            id: assistantId,
            role: "assistant",
            task: taskShell,
        });
        // ⚠️ 同图片修复:必须拿数组里的 reactive 版本启动轮询
        const assistantMsg = videoChat.value.find((m) => m.id === assistantId);
        if (assistantMsg && assistantMsg.role === "assistant") {
            startVideoGen(assistantMsg.task);
        }
    } else if (activeMode.value === "digital") {
        // 爆款仿写(只有数字人口播混剪 + douyin-clone 才走这条)
        if (digitalState.mode === "dh-montage" && digitalInputMode.value === "douyin-clone") {
            if (!isDouyinShareLink(text)) {
                feedback.msgWarning("请粘贴抖音视频分享链接(v.douyin.com / www.douyin.com)");
                return;
            }
            if (!digitalState.personaId) {
                feedback.msgWarning("请先在工具栏选择一个人设(后端用它来匹配素材库 + 文案风格)");
                return;
            }
            submitHotWrite(text);
            return;
        }
        // 推用户气泡
        digitalChat.value.push({ id: ++_digitalChatId, role: "user", text });
        // AI 追问 ON → 表单流程(用户填问卷)
        if (digitalFollowupOn.value) {
            startDigitalFollowup(text);
            triggerWorkflowContentPushUp();
            return;
        }
        // AI 追问 OFF → 直接跑:text → params → copies → 自动提交视频
        startDigitalDirectFlow(text);
    }

    if (activeMode.value !== "chat") triggerWorkflowContentPushUp();
    inputText.value = "";
    // 只回收输入区预览用的 blob；气泡里的附件 URL 已单独拷贝/用远程地址
    clearRefs();
}

function openMapHistory() {
    if (activeMode.value !== "map") activeMode.value = "map";
    nextTick(() => mapOutRef.value?.openHistory());
}

// ===== 历史会话(图片 / 视频 / PPT 走后端 draw 会话)=====
const activeDrawConvId = computed(() => {
    if (activeMode.value === "image") return drawConvId.image;
    if (activeMode.value === "video") return drawConvId.video;
    if (activeMode.value === "ppt") return drawConvId.ppt;
    return 0;
});

const toolbarRightRef = ref<InstanceType<typeof InputToolbarRight> | null>(null);
const historyPopupStyle = ref<Record<string, string>>({});

function updateHistoryPopupPosition() {
    const el = toolbarRightRef.value?.getHistoryAnchor?.() ?? null;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    historyPopupStyle.value = {
        position: "fixed",
        right: `${Math.max(8, window.innerWidth - rect.right)}px`,
        bottom: `${Math.max(8, window.innerHeight - rect.top + 8)}px`,
        left: "auto",
        top: "auto",
    };
}

function openHistoryPopup() {
    const willOpen = openPopup.value !== "history";
    togglePopup("history");
    if (willOpen) {
        loadModeHistory();
        nextTick(() => {
            updateHistoryPopupPosition();
        });
    }
}

function onHistoryPopupReposition() {
    if (openPopup.value === "history") updateHistoryPopupPosition();
}

async function onRestoreHistory(item: DrawHistorySessionItem) {
    openPopup.value = "";
    const mode = activeMode.value;
    if (mode !== "image" && mode !== "video" && mode !== "ppt") return;
    if (historyRestoring.value) return;
    historyRestoring.value = true;
    try {
        const detail = await fetchDrawConversationDetail(item.id);
        if (!detail) {
            feedback.msgError("会话不存在或已删除");
            return;
        }
        const text = lastUserTextFromDetail(detail);
        if (text) lastUserText.value = text;
        if (mode === "image") restoreImageConversation(detail);
        else if (mode === "video") restoreVideoConversation(detail);
        else restorePptConversation(detail);
        bindDrawConversation(mode, detail.id);
        triggerWorkflowContentPushUp();
    } catch (e: any) {
        feedback.msgError(e?.msg || e?.message || "恢复会话失败");
    } finally {
        historyRestoring.value = false;
    }
}

async function onDeleteHistory(id: string) {
    if (historyDeletingId.value) return;
    historyDeletingId.value = id;
    feedback.loading("删除中...");
    try {
        await deleteDrawConversation(id);
        historyList.value = historyList.value.filter((s) => s.id !== id);
        feedback.msgSuccess("删除成功");
        const numId = Number(id) || 0;
        if (activeMode.value === "image" && drawConvId.image === numId) {
            drawConvId.image = 0;
            imageChat.value = [];
            imageHasOutput.value = false;
            clearDrawConversationUrl();
        }
        if (activeMode.value === "video" && drawConvId.video === numId) {
            drawConvId.video = 0;
            videoChat.value = [];
            clearDrawConversationUrl();
        }
        if (activeMode.value === "ppt" && drawConvId.ppt === numId) {
            drawConvId.ppt = 0;
            pptChat.value = [];
            pptOpen.value = false;
            activePptMsgId.value = null;
            clearDrawConversationUrl();
        }
    } catch (e: any) {
        feedback.msgError(e?.msg || e?.message || "删除失败");
    } finally {
        feedback.closeLoading();
        historyDeletingId.value = "";
    }
}

/** 新会话：清空当前模式聊天，下次生成新建 conversation */
function onNewDrawSession() {
    openPopup.value = "";
    if (activeMode.value === "image") {
        drawConvId.image = 0;
        imageChat.value = [];
        imageHasOutput.value = false;
    } else if (activeMode.value === "video") {
        drawConvId.video = 0;
        videoChat.value = [];
    } else if (activeMode.value === "ppt") {
        drawConvId.ppt = 0;
        pptChat.value = [];
        pptOpen.value = false;
        activePptMsgId.value = null;
    }
    clearDrawConversationUrl();
    lastUserText.value = "";
    lastUserAttachments.value = [];
    clearRefs();
    inputText.value = "";
}
function onPickCat(name: string) {
    inputText.value = `帮我找${name}的商家`;
    handleSend();
}
function onFetchMore() {
    // 续页走组件内 continueFetchAll:会带 query + page(=next_page)
    mapOutRef.value?.continueFetchAll?.();
}
async function onMapExport() {
    try {
        await mapLeadExport({ message_id: mapOutRef.value?.messageId });
        feedback.msgSuccess("导出成功");
    } catch (error: any) {
        feedback.msgError(error || "导出失败");
    }
}

/** 后端返回 conversation_id 后写入 URL,刷新可据此拉聊天记录 */
function onMapConversationChange(id: string) {
    if (!id) return;
    mapConversationId.value = id;
    replaceState({ conversation_id: id });
}

function clearMapConversationUrl() {
    replaceState({ conversation_id: undefined });
}

function onMapRestored(payload: { lastUserText: string }) {
    if (payload?.lastUserText) {
        lastUserText.value = payload.lastUserText;
        mapLastUserText.value = payload.lastUserText;
    }
    // 打开历史会话后必须进沉浸态,否则用户气泡/结果区会被欢迎页布局挡住
    activeMode.value = "map";
    displayedMode.value = "map";
    mapInteracting.value = true;
    mapHasOutput.value = true;
    emit("change-mode", "map");
}

/** 地图抓取进度/恢复完成后同步是否有输出 */
function syncMapSnapshot() {
    const batchesLen = mapOutRef.value?.batches?.length ?? 0;
    const priorLen = mapOutRef.value?.priorTurns?.length ?? 0;
    mapHasOutput.value = batchesLen > 0 || priorLen > 0;
}

// ─── 提示词优化：点击优化当前输入框文案（回填 inputText，发送仍走正常生成） ──
async function requestPromptOptimize(text: string, mode: WorkflowMode): Promise<string> {
    const data =
        mode === "video"
            ? await drawOptimizeVideoPrompt({ keywords: text })
            : await drawOptimizeImagePrompt({ keywords: text });
    return String(data?.content ?? "").trim();
}

async function handleOptimizePrompt() {
    const text = inputText.value.trim();
    if (!text) {
        feedback.msgWarning("请先输入提示词");
        return;
    }
    if (promptOptimizing.value) return;
    openPopup.value = "";
    if (typeof document !== "undefined") {
        (document.activeElement as HTMLElement | null)?.blur?.();
    }
    promptOptimizing.value = true;
    try {
        const optimized = (await requestPromptOptimize(text, activeMode.value)).trim();
        if (!optimized) {
            feedback.msgWarning("优化结果为空，请重试");
            return;
        }
        inputText.value = optimized;
    } catch (e: any) {
        feedback.msgError(`提示词优化失败:${e?.message ?? e?.msg ?? e}`);
    } finally {
        promptOptimizing.value = false;
    }
}

function copyOptimized(text: string) {
    if (!text) return;
    navigator.clipboard?.writeText(text).then(
        () => feedback.msgSuccess("已复制"),
        () => feedback.msgWarning("复制失败,请手动选择"),
    );
}

function onDocClick() {
    openPopup.value = "";
}
onMounted(() => {
    document.addEventListener("click", onDocClick);
    window.addEventListener("resize", onHistoryPopupReposition);
    window.addEventListener("scroll", onHistoryPopupReposition, true);
});
onBeforeUnmount(() => {
    document.removeEventListener("click", onDocClick);
    window.removeEventListener("resize", onHistoryPopupReposition);
    window.removeEventListener("scroll", onHistoryPopupReposition, true);
});

// 数字人:首次进入时拉真实人设列表
const PERSONA_BG_POOL = [
    "linear-gradient(135deg,#fef3c7,#fbbf24)",
    "linear-gradient(135deg,#dbeafe,#3b82f6)",
    "linear-gradient(135deg,#fce7f3,#ec4899)",
    "linear-gradient(135deg,#dcfce7,#16a34a)",
    "linear-gradient(135deg,#e0e7ff,#6366f1)",
    "linear-gradient(135deg,#fef9c3,#f59e0b)",
];
async function loadDigitalPersonas() {
    try {
        const res: any = await getPersonList({ page_no: 1, page_size: 100 });
        const list = res?.lists ?? res?.data?.lists ?? res?.list ?? res ?? [];
        DIGITAL_PERSONAS.value = (Array.isArray(list) ? list : []).map((p: any, idx: number) => ({
            id: String(p.id),
            name: p.persona_name ?? p.name ?? `人设 ${idx + 1}`,
            emoji: (p.persona_name ?? "人")[0] ?? "人",
            bg: PERSONA_BG_POOL[idx % PERSONA_BG_POOL.length],
            introduced: p.quick_desc ?? p.persona_desc ?? "",
        }));
    } catch (e: any) {
        console.warn("[digital] 拉取人设列表失败:", e?.message ?? e);
    }
}
onMounted(loadDigitalPersonas);

/** URL 带 conversation_id 时切到地图模式并拉后端消息记录 */
async function tryRestoreMapFromRoute(): Promise<boolean> {
    const raw = route.query.conversation_id;
    const id = Array.isArray(raw) ? raw[0] : raw;
    if (!id || typeof id !== "string" || id === "undefined") return false;

    activeMode.value = "map";
    displayedMode.value = "map";
    mapInteracting.value = true;
    emit("change-mode", "map");
    await nextTick();
    await nextTick();
    const result = await mapOutRef.value?.restoreConversation?.(id);
    if (result?.lastUserText) lastUserText.value = result.lastUserText;
    ensureSession("map", result?.lastUserText || "地图获客");
    mapHasOutput.value = true;
    return true;
}

/** URL 带 draw_conversation_id 时恢复图片/视频/PPT 会话 */
async function tryRestoreDrawFromRoute(): Promise<boolean> {
    const rawId = route.query.draw_conversation_id;
    const rawMode = route.query.draw_mode;
    const id = Array.isArray(rawId) ? rawId[0] : rawId;
    const modeRaw = Array.isArray(rawMode) ? rawMode[0] : rawMode;
    const mode = modeRaw === "video" ? "video" : modeRaw === "ppt" ? "ppt" : modeRaw === "image" ? "image" : "";
    if (!id || typeof id !== "string" || id === "undefined" || !mode) return false;

    const numId = Number(id) || 0;
    if (numId <= 0) return false;

    activeMode.value = mode;
    displayedMode.value = mode;
    emit("change-mode", mode);
    await nextTick();

    try {
        const detail = await fetchDrawConversationDetail(numId);
        if (!detail) {
            clearDrawConversationUrl();
            return false;
        }
        const text = lastUserTextFromDetail(detail);
        if (text) lastUserText.value = text;
        if (mode === "image") restoreImageConversation(detail);
        else if (mode === "video") restoreVideoConversation(detail);
        else restorePptConversation(detail);
        bindDrawConversation(mode, detail.id);
        triggerWorkflowContentPushUp();
        return true;
    } catch (e) {
        console.warn("[session-restore] 恢复绘图会话失败:", e);
        clearDrawConversationUrl();
        return false;
    }
}

onMounted(async () => {
    try {
        const restoredMap = await tryRestoreMapFromRoute();
        if (!restoredMap) await tryRestoreDrawFromRoute();
    } catch (e) {
        console.warn("[session-restore] 恢复会话失败:", e);
    }
});

function clearAllState() {
    imageHasOutput.value = false;
    imageChat.value = [];
    videoChat.value = [];
    digitalChat.value = [];
    pptOpen.value = false;
    pptChat.value = [];
    activePptMsgId.value = null;
    mapOutRef.value?.reset?.();
    mapInteracting.value = false;
    mapHasOutput.value = false;
    mapConversationId.value = "";
    mapLastUserText.value = "";
    clearRefs();
    inputText.value = "";
    lastUserText.value = "";
    lastUserAttachments.value = [];
    workflowBottomSpacer.value = 0;
    openPopup.value = "";
    resetMention();
}

/** 清空状态回到 Hello 首页(不再写本地工作流缓存) */
function reset() {
    currentSessionId.value = null;
    drawConvId.image = 0;
    drawConvId.video = 0;
    drawConvId.ppt = 0;
    (Object.keys(sessionIdByMode) as Array<keyof typeof sessionIdByMode>).forEach((k) => {
        sessionIdByMode[k] = null;
    });
    clearAllState();
    clearMapConversationUrl();
    clearDrawConversationUrl();
    activeMode.value = "chat";
    displayedMode.value = "chat";
    emit("change-mode", "chat");
}

defineExpose({ reset, switchMode, getChatConfig });
</script>

<style lang="scss" scoped>
.welcome-shell {
    @apply flex items-stretch bg-[#f5f6f8];
}
.welcome-hero {
    @apply min-w-0 flex-1 transition-[padding] duration-200 ease-[ease];
}
/* PPT 抽屉作为 flex 子元素,welcome-hero flex:1 会自动让出 480px */
.ppt-drawer {
    @apply z-[50] w-[480px] flex-shrink-0;
}

.model-logo {
    @apply flex h-[34px] w-[34px] items-center justify-center overflow-hidden rounded-full border-2 border-white bg-white shadow-[0_2px_6px_rgba(0,0,0,0.12)] transition-transform duration-150 ease-[ease];

    :deep(svg) {
        @apply h-[22px] w-[22px];
    }
}
.model-stack .model-logo:hover {
    @apply z-[9] -translate-y-[3px];
}

/* ───── 对话区布局(气泡样式已下沉到 welcome/*-chat-pane) ───── */
.output-stream {
    &.as-chat {
        @apply mt-2 flex flex-col gap-3.5;
    }
}

</style>
