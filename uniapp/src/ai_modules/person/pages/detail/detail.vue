<template>
    <view class="persona-detail min-h-screen bg-[#F5F7FA]" :class="setupPending ? 'persona-detail--setup' : ''">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#F5F8FE' }"
            :height="navigationBarHeight"
            title="人设详情"
            title-color="#1D2129"
            back-icon-color="#1D2129"
            title-bold />

        <template v-if="loading">
            <view class="px-[32rpx] pt-[24rpx]">
                <view class="animate-pulse">
                    <view class="flex items-center gap-[24rpx]">
                        <view class="w-[112rpx] h-[112rpx] rounded-full bg-[#F2F3F5]"></view>
                        <view class="flex-1">
                            <view class="h-[32rpx] w-[240rpx] rounded-full bg-[#F2F3F5] mb-[20rpx]"></view>
                            <view class="flex gap-[12rpx]">
                                <view class="h-[36rpx] w-[120rpx] rounded-full bg-[#F2F3F5]"></view>
                                <view class="h-[36rpx] w-[120rpx] rounded-full bg-[#F2F3F5]"></view>
                            </view>
                        </view>
                    </view>
                </view>
                <view class="grid grid-cols-2 gap-[20rpx] mt-[24rpx] animate-pulse">
                    <view v-for="item in 4" :key="item" class="h-[136rpx] rounded-[28rpx] bg-white"></view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="persona-hero">
                <view class="flex items-center gap-[26rpx]">
                    <view class="relative shrink-0">
                        <view class="persona-avatar">
                            <image :src="detail.avatar_url" class="w-full h-full" mode="aspectFill" />
                        </view>
                        <view class="persona-dot" :style="{ backgroundColor: heroDotColor }"></view>
                    </view>

                    <view class="flex-1 min-w-0">
                        <view class="flex items-center gap-[16rpx]">
                            <text class="text-[44rpx] leading-[60rpx] font-bold text-[#202937] line-clamp-1">
                                {{ detail.persona_name || "未命名人设" }}
                            </text>
                            <text class="persona-type">{{ personTypeLabel }}</text>
                        </view>
                        <view class="persona-meta">
                            <template v-if="identityTags.length">
                                <text
                                    v-for="(tag, index) in identityTags"
                                    :key="`identity-${index}`"
                                    class="persona-tag">
                                    {{ tag }}
                                </text>
                            </template>
                            <text v-else class="persona-tag">AI数字员工</text>
                            <text v-if="detail.content_focus" class="persona-tag">
                                {{ detail.content_focus }}
                            </text>
                            <view class="persona-meta-separator"></view>
                            <text>{{ runModeLabel }}</text>
                        </view>
                    </view>

                    <view class="persona-edit active:opacity-70" aria-label="编辑人设" @click="handleEditType">
                        <u-icon name="edit-pen" color="#5B6B80" size="40"></u-icon>
                    </view>
                </view>
            </view>

            <view class="px-[32rpx]">
                <view class="quick-card">
                    <view
                        class="quick-item active:opacity-70"
                        @click="
                            handleNavigate(
                                `/ai_modules/person/pages/analysis/analysis?id=${personId}&type=${detail.persona_type}&mode=edit`,
                            )
                        ">
                        <view class="quick-icon">
                            <image
                                src="@/ai_modules/person/static/icons/persona-report.svg"
                                class="w-[46rpx] h-[46rpx]"
                                mode="aspectFit"></image>
                        </view>
                        <text class="quick-label">人设报告</text>
                    </view>

                    <view
                        class="quick-item active:opacity-70"
                        @click="handleNavigate(`/ai_modules/person/pages/task_flow/task_flow?id=${personId}`)">
                        <view class="quick-icon">
                            <image
                                src="@/ai_modules/person/static/icons/robot.svg"
                                class="w-[46rpx] h-[46rpx]"
                                mode="aspectFit"></image>
                        </view>
                        <text class="quick-label">任务流</text>
                    </view>

                    <view
                        class="quick-item active:opacity-70"
                        @click="handleNavigate(`/ai_modules/person/pages/ai_employee/ai_employee?id=${personId}`)">
                        <text class="quick-new">NEW</text>
                        <view class="quick-icon">
                            <image
                                src="@/ai_modules/person/static/icons/ai.svg"
                                class="w-[46rpx] h-[46rpx]"
                                mode="aspectFit"></image>
                        </view>
                        <text class="quick-label">AI 员工</text>
                    </view>
                </view>
            </view>

            <view v-if="setupPending" class="px-[32rpx] mt-[32rpx]">
                <setup-checklist
                    v-model:expanded="checklistExpanded"
                    :state="checklistState"
                    @step="handleSetupStep"
                    @edit="handleSetupEdit"
                    @manage-material="handleManageSetupMaterials"
                    @option="handleSetupOption" />
            </view>

            <view v-if="showRunCard" class="px-[32rpx] mt-[18rpx]">
                <run-status-card
                    :run-mode="detail.run_mode"
                    :today-success="setupSummary?.today_success ?? 0"
                    :generating="setupSummary?.generating ?? 0"
                    :today-failed="setupSummary?.today_failed ?? 0"
                    :next-run-at="nextRunAt"
                    :publish-slots="setupSummary?.publish_slots ?? []"
                    :daily-count="Number(setupSummary?.daily_video_count) || 0"
                    :is-product-direct="isProductDirect"
                    :direct-material-count="setupSummary?.direct_material_count ?? 0"
                    :has-issue="hasIssue"
                    :is-first-run-pending="isFirstRunPending"
                    @flow="handleNavigate(`/ai_modules/person/pages/task_flow/task_flow?id=${personId}`)" />
            </view>

            <view v-if="showIssueBanner && topIssue" class="px-[32rpx] mt-[18rpx]">
                <issue-banner
                    :issue="topIssue"
                    :rest-count="issues.length - 1"
                    @handle="handleIssue" />
            </view>

            <view v-if="showEnhanceEntry" class="px-[32rpx] mt-[18rpx]">
                <enhance-entry
                    :percent="enhancePercent"
                    :remain-count="enhanceRemainCount"
                    @open="showEnhanceSheet = true" />
            </view>

            <view class="page-tabs-shell" :style="{ top: `${resourceTabsTop}px` }">
                <view class="page-tabs">
                    <view
                        v-for="tab in pageTabs"
                        :key="tab.key"
                        class="page-tab active:opacity-70"
                        @click="handleSwitchTab(tab.key)">
                        <view class="flex items-baseline gap-[6rpx]">
                            <text class="page-tab-label" :class="activeTab === tab.key ? 'page-tab-label--on' : ''">
                                {{ tab.label }}
                            </text>
                            <text
                                v-if="tabBadge(tab.key).count"
                                class="text-[22rpx] font-semibold"
                                :class="activeTab === tab.key ? 'text-primary' : 'text-disabled'">
                                {{ tabBadge(tab.key).count }}
                            </text>
                            <view v-if="tabBadge(tab.key).alert" class="page-tab-dot"></view>
                        </view>
                        <view class="page-tab-line" :class="activeTab === tab.key ? 'page-tab-line--on' : ''"></view>
                    </view>
                </view>
            </view>

            <view class="px-[32rpx] pt-[32rpx] min-h-[360rpx]">
                <view v-if="tabFirstLoading" class="tab-first-loading">
                    <u-loading mode="circle" size="44" color="#0065FB"></u-loading>
                    <text class="tab-first-loading-text">加载中...</text>
                </view>
                <template v-else>
                    <viral-tab v-if="activeTab === PageTabEnum.HOT" :state="viralTabState" :actions="viralTabActions" />

                    <materials-tab
                        v-if="activeTab === PageTabEnum.MATERIALS"
                        :square-picker-visible="showSquarePicker"
                        :public-copy-picker-visible="showPublicCopyPicker"
                        :state="materialsTabState"
                        :actions="materialsTabActions" />

                    <devices-tab
                        v-if="activeTab === PageTabEnum.DEVICES"
                        :state="devicesTabState"
                        :actions="devicesTabActions"
                        :is-product-direct="isProductDirect" />

                    <history-tab
                        v-if="activeTab === PageTabEnum.HISTORY"
                        :state="historyTabState"
                        :actions="historyTabActions" />
                </template>
            </view>
        </template>

        <view v-if="showSetupBar" class="setup-bar">
            <view class="flex-1 min-w-0 flex flex-col gap-[6rpx]">
                <text class="text-[26rpx] font-semibold text-[#202937]">完善人设</text>
                <text class="text-[22rpx] text-[#647184] leading-[34rpx]">{{ setupBarHint }}</text>
            </view>
            <view class="setup-bar-btn active:opacity-85" @click="handleSetupBar">
                <text class="text-[28rpx] font-semibold text-white">{{ setupBarText }}</text>
                <u-icon name="arrow-right" color="#FFFFFF" size="24"></u-icon>
            </view>
        </view>

        <copy-source-sheet
            v-model="showCopySourceSheet"
            :copywriting-count="Number(setupSummary?.copywriting_count) || 0"
            @confirm="handleConfirmCopySource" />
        <enhance-sheet
            v-model="showEnhanceSheet"
            :percent="enhancePercent"
            :items="enhanceItems"
            :remain-count="enhanceRemainCount"
            :required-done="setupDoneCount"
            :required-total="setupTotalStep"
            :top-item="enhanceTopItem"
            @item="handleEnhanceItem" />
        <choose-device ref="chooseDeviceRef" v-model="showChooseDevice" plain @confirm="handleChooseDeviceConfirm" />
        <upload-category-panel
            v-model="showUploadCategoryPanel"
            plain
            :title="uploadPanelTitle"
            :show-categories="uploadCategoryShowCategories"
            :tip="uploadCategoryTip"
            :force-album-type-picker="forceAlbumTypePicker"
            :mask-close-able="true"
            @popup-visibility-change="uploadOverlayVisible = $event"
            @select="handleSelectCategory" />
        <cut-mode-popup v-model="showCutModePopup" @confirm="handleConfirmCutMode" />
        <choose-history v-model="showHistory" :limit="9" @select="handleSelectHistory" />
        <choose-material
            v-model="showMaterialLibrary"
            :mode="materialType"
            :type="chooseMaterialContentType"
            :limit="replaceMaterialIndex === -1 ? 9 : 1"
            @select="handleSelectMaterial" />
        <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
        <keyword-edit-popup
            plain
            v-model="showHotKeywordPopup"
            :title="hotKeywordPopupTitle"
            label="追踪关键词"
            :value="hotKeywordPopupValue"
            placeholder="如：敏感肌、换季护肤"
            :tip="hotKeywordPopupTip"
            :confirm-text="hotKeywordPopupConfirmText"
            @confirm="handleConfirmHotKeyword" />
        <manual-import-popup
            v-model="showManualImportPopup"
            :persona-id="personId"
            @success="handleManualImportSuccess" />
        <square-picker-popup
            v-model="showSquarePicker"
            :persona-id="personId"
            :joined-ids="materialsTabState.squareJoinedIds || []"
            @added="querySquareRefs" />
        <public-copy-picker-popup
            v-model="showPublicCopyPicker"
            :persona-id="personId"
            :library-type="copyTab"
            :driver-type="currentDriverType"
            @added="queryCopyList" />
        <video-detail-popup
            v-model="showVideoDetail"
            :item="currentVideoDetail"
            :tags="currentVideoDetailTags"
            :can-play="currentVideoCanPlay"
            @play="handlePlayVideoDetail" />
        <image-detail-popup
            v-model="showImageDetail"
            :item="currentImageDetail"
            :images="currentImageDetailUrls"
            :tags="currentImageDetailTags"
            :title="currentImageDetailTitle"
            :platform-badge="currentImageDetailPlatform"
            :time="currentImageDetailTime" />
        <video-preview-v2
            v-model:show="showVideoPreview"
            :video-url="playItem.url"
            :poster="playItem.pic"
            @update:show="showVideoPreview = false" />
        <copy-edit-popup
            v-model="showCopyEdit"
            :form="editingCopy"
            :library-type="copyTab"
            :driver-type="currentDriverType"
            @confirm="handleSubmitCopy" />
        <copy-import-popup
            v-model="showCopyImport"
            :library-type="copyTab"
            @select="handleImportPick"
            @download-template="handleDownloadImportTemplate" />
        <choose-agent
            v-if="showChooseCopyAgent"
            v-model="showChooseCopyAgent"
            :system-agent-ids="copyAgentSystemIds"
            @select="handleCopyAgentSelected" />
        <choose-anchor v-model="showChooseAnchor" plain ref="chooseAnchorRef" @select="handleChooseAnchor" />
        <choose-voice
            v-if="showChooseVoice"
            plain
            v-model="showChooseVoice"
            ref="chooseVoiceRef"
            :show-free-tone="false"
            :model-version="PERSONA_MATERIAL_VOICE_MODEL_VERSIONS"
            @select="handleChooseVoice" />
        <choose-voice
            v-if="showAvatarVoicePicker"
            plain
            v-model="showAvatarVoicePicker"
            ref="avatarVoicePickerRef"
            :limit="1"
            :show-free-tone="false"
            :model-version="PERSONA_MATERIAL_VOICE_MODEL_VERSIONS"
            @select="handleAvatarVoiceSelect" />
    </view>
</template>

<script setup lang="ts">
import {
    getPersonaSetupProgress,
    getPersonDetail,
    getPersonTrackingWords,
    updatePerson,
    updatePersonTrackingWords,
} from "@/api/person";
import { DigitalHumanModelVersionEnum, PersonTypeEnum, PersonTypeMap } from "@/enums/appEnums";
import {
    PERSONA_MATERIAL_VOICE_MODEL_VERSIONS,
    MaterialKindEnum,
    MaterialUseModeApiEnum,
    ProductReuseModeEnum,
    ProductUseModeEnum,
    isCloudRunMode,
    RunModeApiEnum,
    SETUP_THRESHOLD,
    SetupStepEnum,
    type EnhanceItemEnum,
    EnhanceItemEnum as EnhanceItem,
    type CopywritingSourceEnum,
    type PersonaIssueEnum,
    PersonaIssueEnum as PersonaIssue,
} from "@/ai_modules/person/enums";
import { setFormData } from "@/utils/util";
import ChooseAnchor from "@/ai_modules/person/components/choose-anchor/choose-anchor.vue";
import ChooseDevice from "@/ai_modules/person/components/choose-device/choose-device.vue";
import ChooseVoice from "@/ai_modules/person/components/choose-voice/choose-voice.vue";
import KeywordEditPopup from "@/ai_modules/person/pages/employee_setting/components/keyword-edit-popup.vue";
import ChooseAgent from "@/ai_modules/person/components/choose-agent/choose-agent.vue";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import CopyEditPopup from "./components/copy-edit-popup.vue";
import CopyImportPopup from "./components/copy-import-popup.vue";
import DevicesTab from "./components/devices-tab.vue";
import HistoryTab from "./components/history-tab.vue";
import ImageDetailPopup from "./components/popups/image-detail-popup.vue";
import VideoDetailPopup from "./components/popups/video-detail-popup.vue";
import MaterialsTab from "./components/materials-tab.vue";
import EnhanceEntry from "./components/enhance-entry.vue";
import IssueBanner from "./components/issue-banner.vue";
import RunStatusCard from "./components/run-status-card.vue";
import CopySourceSheet from "./components/popups/copy-source-sheet.vue";
import EnhanceSheet from "./components/popups/enhance-sheet.vue";
import SetupChecklist from "./components/setup-checklist.vue";
import ViralTab from "./components/viral-tab.vue";
import ManualImportPopup from "./components/popups/manual-import-popup.vue";
import SquarePickerPopup from "@/ai_modules/person/components/square-picker-popup/square-picker-popup.vue";
import PublicCopyPickerPopup from "@/ai_modules/person/components/public-copy-picker-popup/public-copy-picker-popup.vue";
import CutModePopup from "./components/popups/cut-mode-popup.vue";
import { copyDriveTypes, copyTabs, useCopyLibrary } from "./hooks/useCopyLibrary";
import { useDevicesTab } from "./hooks/useDevicesTab";
import { HistoryTabEnum, useHistoryTab, VideoStatus } from "./hooks/useHistoryTab";
import {
    MaterialTabEnum,
    materialFilters,
    materialPublishModes,
    materialSubTabs,
    type PublishMode,
    useMaterialsTab,
    type ChooseListRef,
    materialKindOptions,
} from "./hooks/useMaterialsTab";
import { useEnhanceList } from "./hooks/useEnhanceList";
import { usePersonaIssues } from "./hooks/usePersonaIssues";
import { formatNextRun, type PersonaSetupSummary, useSetupChecklist } from "./hooks/useSetupChecklist";
import { useViralTab } from "./hooks/useViralTab";

enum PageTabEnum {
    HOT = "hot",
    HISTORY = "history",
    MATERIALS = "materials",
    DEVICES = "devices",
}

enum PublishModeEnum {
    AUTO = 1,
    MANUAL = 2,
}

// 复用视频 AI 文案页（ai_copywriter）回传的 eventBus 事件名，对应 digital_human ListenerTypeEnum.AI_COPYWRITER
const AI_COPYWRITER_EVENT = "ai-copywriter";
// 发布文案 AI 生成页回传
const PUBLISH_AI_COPYWRITER_EVENT = "publish-ai-copywriter";

interface PersonDetail {
    id: string;
    persona_name: string;
    persona_type: keyof typeof PersonTypeMap | undefined;
    avatar_url: string;
    publish_mode: 1 | 2;
    /** 素材模式：1 自动适配 / 2 原图，人设表为准 */
    material_use_mode: number;
    /** 运行模式：1 手机模式 / 2 无手机模式 */
    run_mode: number;
    hot_words?: any;
    individual?: Record<string, any>;
    enterprise?: Record<string, any>;
    local?: Record<string, any>;
    identity?: string[];
    content_focus?: string;
}

type PersonDetailFormKey = "individual" | "enterprise" | "local";

const pageTabs = [
    { key: PageTabEnum.HOT, label: "爆款库" },
    { key: PageTabEnum.MATERIALS, label: "素材库" },
    { key: PageTabEnum.DEVICES, label: "关联设备" },
    { key: PageTabEnum.HISTORY, label: "内容记录" },
];

const loading = ref(true);
const initialized = ref(false);
const personId = ref("");
const initialOpen = ref('');
// 与 u-navbar 共用实际平台高度，资源导航吸顶时不覆盖系统状态栏。
const personaSystemInfo = uni.getSystemInfoSync();
const navigationBarHeights = { default: 44, android: 48 } as const;
let navigationBarHeight: number = navigationBarHeights.default;
// #ifdef MP
navigationBarHeight = personaSystemInfo.platform === "ios" ? navigationBarHeights.default : navigationBarHeights.android;
// #endif
const resourceTabsTop = Number(personaSystemInfo.statusBarHeight || 0) + navigationBarHeight;
const activeTab = ref<PageTabEnum>(PageTabEnum.HOT);
/** 四个主 Tab（及素材子 Tab）首次拉数时的内容区 loading */
const tabFirstLoading = ref(false);
let tabLoadToken = 0;
const loadedState = reactive({
    hot: false,
    materials: false,
    avatars: false,
    voices: false,
    copy: false,
    music: false,
    devices: false,
    history: false,
});

const PERSON_DETAIL_FORM_KEY: Record<PersonTypeEnum, PersonDetailFormKey> = {
    [PersonTypeEnum.PERSONAL_IP]: "individual",
    [PersonTypeEnum.BUSINESS_SERVICE]: "enterprise",
    [PersonTypeEnum.LOCAL_BUSINESS]: "local",
};

const detail = reactive<PersonDetail>({
    id: "",
    persona_name: "",
    persona_type: undefined,
    avatar_url: "",
    publish_mode: PublishModeEnum.AUTO,
    material_use_mode: MaterialUseModeApiEnum.AUTO,
    // 必须在这里占位：getDetail 用的 setFormData 只拷贝目标对象已有的 key，
    // 不预置的话后端返回了 run_mode 也会被静默丢掉
    run_mode: RunModeApiEnum.PHONE,
});

// 剪辑原片库 / 成品直发库：仅本地视图切换，不与人设 detail.publish_mode 绑定，
// 避免 onShow 拉详情后 Tab 被还原、列表未刷新导致错显
const materialViewMode = ref<PublishMode>(PublishModeEnum.AUTO);
const uploadPanelTitle = computed(() => {
    if (forceAlbumTypePicker.value) return "添加音乐";
    return materialViewMode.value === PublishModeEnum.AUTO ? "添加剪辑素材" : "添加成品素材";
});

const personTypeLabel = computed(() => {
    if (!detail.persona_type) return "未分类";
    return PersonTypeMap[detail.persona_type as keyof typeof PersonTypeMap] || "企业";
});

// 行业方向 / 身份标签：可能 1~3 项，UI 全部铺平展示
const identityTags = computed<string[]>(() => {
    const raw = detail.identity;
    if (!Array.isArray(raw)) return [];
    return raw.map((item) => String(item || "").trim()).filter(Boolean);
});

const showChooseDevice = ref(false);
const showVideoPreview = ref(false);
const playItem = ref({ url: "", pic: "" });
const chooseDeviceRef = shallowRef();
const chooseAnchorRef = ref<ChooseListRef | null>(null);
const chooseVoiceRef = ref<ChooseListRef | null>(null);
const avatarVoicePickerRef = ref<ChooseListRef | null>(null);
/**
 * 成品直发没有运行模式可选（合成规则弹窗里那一节只在「视频剪辑」下渲染），
 * 而发布 cron 要求成品直发走手机模式。历史数据里残留的 run_mode=2 不该在标题上
 * 显示成「无手机模式」——那会让用户以为不用绑设备，正好与任务停摆的原因相反。
 */
const isProductDirect = computed(() => detail.publish_mode === PublishModeEnum.MANUAL);
const runModeLabel = computed(() =>
    !isProductDirect.value && isCloudRunMode(detail.run_mode) ? "无手机模式" : "手机模式",
);

const showHotKeywordPopup = ref(false);
const showManualImportPopup = ref(false);
const showSquarePicker = ref(false);
const showPublicCopyPicker = ref(false);
const hotKeywordPopupValue = ref("");
const hotKeywordSubmitting = ref(false);
const hotKeywordEditingIndex = ref(-1);
const hotKeywordPopupTitle = computed(() => (hotKeywordEditingIndex.value >= 0 ? "编辑爆款追踪词" : "添加爆款追踪词"));
const hotKeywordPopupConfirmText = computed(() => (hotKeywordEditingIndex.value >= 0 ? "保存" : "添加"));
const hotKeywordPopupTip = computed(() =>
    hotKeywordEditingIndex.value >= 0
        ? "清空内容并保存可删除该关键词。"
        : isCloudRunMode(detail.run_mode)
          ? "用于保留内容方向；自动追踪需切换手机模式并关联设备。"
          : "保存后，AI 会按这个关键词追踪全平台爆款内容。",
);

const normalizeKeywordItem = (item: any): string => {
    if (typeof item === "string" || typeof item === "number") return String(item).trim();
    if (!item || typeof item !== "object") return "";
    return String(item.keyword ?? item.word ?? item.name ?? item.title ?? item.value ?? "").trim();
};

const normalizeKeywordList = (value: any): string[] => {
    if (Array.isArray(value)) {
        return Array.from(new Set(value.map(normalizeKeywordItem).filter(Boolean)));
    }
    if (typeof value === "string") {
        const source = value.trim();
        if (!source) return [];
        try {
            const parsed = JSON.parse(source);
            if (Array.isArray(parsed)) return normalizeKeywordList(parsed);
        } catch {
            // 非 JSON 字符串按分隔符解析
        }
        return Array.from(
            new Set(
                source
                    .split(/[,，、\n]/)
                    .map((item) => item.trim())
                    .filter(Boolean),
            ),
        );
    }
    return [];
};

const getHotWordsByPersonaType = (data: Record<string, any>): string[] => {
    const personaType = Number(data.persona_type || detail.persona_type) as PersonTypeEnum;
    const formKey = PERSON_DETAIL_FORM_KEY[personaType];
    const typedDetail = formKey ? data[formKey] || {} : {};
    const hotWords = typedDetail.hot_words ?? data.hot_words;
    if (hotWords && typeof hotWords === "object" && !Array.isArray(hotWords)) {
        return normalizeKeywordList(hotWords[personaType] ?? hotWords[String(personaType)] ?? hotWords[formKey]);
    }
    return normalizeKeywordList(hotWords);
};

const syncViralKeywordsFromDetail = (data: Record<string, any>): void => {
    const keywords = getHotWordsByPersonaType(data);
    viralKeywords.value = keywords;
};

const handleOpenHotKeywordPopup = (keyword?: string, index?: number): void => {
    if (typeof index === "number" && index >= 0) {
        hotKeywordEditingIndex.value = index;
        hotKeywordPopupValue.value = keyword ?? viralKeywords.value[index] ?? "";
    } else {
        hotKeywordEditingIndex.value = -1;
        hotKeywordPopupValue.value = "";
    }
    showHotKeywordPopup.value = true;
};

const handleRemoveHotKeyword = async (keyword: string, index: number): Promise<void> => {
    if (!personId.value || hotKeywordSubmitting.value) return;
    if (index < 0 || index >= viralKeywords.value.length) return;
    if (viralKeywords.value[index] !== keyword) return;

    const confirmRes: UniApp.ShowModalRes = await uni.showModal({
        title: "删除关键词",
        content: `确定删除「${keyword}」吗？`,
        confirmText: "删除",
        confirmColor: "#FF3C26",
    });
    if (!confirmRes?.confirm) return;

    const nextKeywords = viralKeywords.value.filter((_, i) => i !== index);

    try {
        hotKeywordSubmitting.value = true;
        uni.showLoading({ title: "删除中...", mask: true });
        await updatePersonTrackingWords({
            id: personId.value,
            persona_type: detail.persona_type,
            hot_words: nextKeywords,
        });
        viralKeywords.value = nextKeywords;
        detail.hot_words = nextKeywords;
        // 追踪词是清单第一步，改完立刻重算进度，不等防抖那 600ms
        fetchSetupProgress();
        uni.showToast({ title: "已删除", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.showToast({ title: error || "删除失败，请重试", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
        hotKeywordSubmitting.value = false;
    }
};

const handleConfirmHotKeyword = async (value: string): Promise<void> => {
    if (!personId.value || hotKeywordSubmitting.value) return;
    const editingIndex = hotKeywordEditingIndex.value;
    const isEditing = editingIndex >= 0;
    const keyword = value.trim();
    let nextKeywords: string[];
    let successToast: string;

    if (isEditing) {
        if (!keyword) {
            // 编辑态清空 = 删除
            nextKeywords = viralKeywords.value.filter((_, i) => i !== editingIndex);
            successToast = "已删除";
        } else {
            const duplicateIndex = viralKeywords.value.findIndex((w) => w === keyword);
            if (duplicateIndex >= 0 && duplicateIndex !== editingIndex) {
                uni.$u.toast("该关键词已存在");
                return;
            }
            if (keyword === viralKeywords.value[editingIndex]) {
                // 无变更，直接关闭（editingIndex 留给下次 open 重置，避免关闭动画期间 popup props 抖动报 nodeValue 错）
                showHotKeywordPopup.value = false;
                return;
            }
            nextKeywords = viralKeywords.value.map((w, i) => (i === editingIndex ? keyword : w));
            successToast = "已保存";
        }
    } else {
        if (!keyword) {
            uni.$u.toast("请输入追踪关键词");
            return;
        }
        if (viralKeywords.value.includes(keyword)) {
            uni.$u.toast("该关键词已存在");
            return;
        }
        nextKeywords = viralKeywords.value.concat(keyword);
        successToast = "添加成功";
    }

    try {
        hotKeywordSubmitting.value = true;
        uni.showLoading({ title: "保存中...", mask: true });
        await updatePersonTrackingWords({
            id: personId.value,
            persona_type: detail.persona_type,
            hot_words: nextKeywords,
        });
        viralKeywords.value = nextKeywords;
        detail.hot_words = nextKeywords;
        showHotKeywordPopup.value = false;
        // editingIndex 不在这里重置，留给下次 handleOpenHotKeywordPopup；
        // 否则关闭动画期间标题/按钮文案 props 抖动会触发 nodeValue 异常打断 render flush
        uni.showToast({ title: successToast, icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.showToast({ title: error || "保存失败，请重试", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
        hotKeywordSubmitting.value = false;
    }
};

const getDetail = async () => {
    const detailResult = await getPersonDetail({ id: personId.value });
    setFormData(detailResult, detail);
    syncViralKeywordsFromDetail(detailResult);
};

const setupSummary = ref<PersonaSetupSummary | null>(null);
// 清单可收起，但四个资源入口始终可见，不再要求用户先完成配置。
const checklistExpanded = ref(true);
const materialsOverlayVisible = ref(false);
const uploadOverlayVisible = ref(false);

const {
    checklistState,
    doneCount: setupDoneCount,
    isFirstRunPending,
    isSetupDone,
    nextRunAt,
    totalStep: setupTotalStep,
} = useSetupChecklist(setupSummary);

const {
    items: enhanceItems,
    percent: enhancePercent,
    remainCount: enhanceRemainCount,
    topItem: enhanceTopItem,
} = useEnhanceList(setupSummary, setupDoneCount, setupTotalStep);

const { hasIssue, issues, topIssue } = usePersonaIssues(setupSummary);

const showEnhanceSheet = ref(false);

// 引导期的底部主行动：始终指向当前那一步，不用用户在页面里自己找
const setupBarText = computed(() => {
    const key = checklistState.value.currentStepKey;
    if (!key) return "";
    return key === SetupStepEnum.MATERIAL ? "继续上传" : "继续配置";
});

const setupBarHint = computed(() => {
    const key = checklistState.value.currentStepKey;
    if (key === SetupStepEnum.MATERIAL && materialShortage.value !== null) {
        return `还差 ${materialShortage.value} 段剪辑素材`;
    }
    const step = checklistState.value.steps.find((item) => item.key === key);
    return step ? `下一步：${step.title}` : "";
});

const handleSetupBar = async (): Promise<void> => {
    const key = checklistState.value.currentStepKey;
    if (key) await handleSetupStep(key);
};

// 头像上那个点就是人设的健康度，跟页面形态一致
const heroDotColor = computed(() => {
    if (!setupSummary.value) return "#19BE6B";
    if (setupPending.value) return setupDoneCount.value ? "#FF9900" : "#C8C9CC";
    return hasIssue.value ? "#FF9900" : "#19BE6B";
});

/** tab 只带「需要你注意」的信号：爆款库带新增数，设备带异常红点 */
const tabBadge = (key: PageTabEnum): { count: number; alert: boolean } => {
    if (key === PageTabEnum.HOT) return { count: viralList.value.length, alert: false };
    if (key === PageTabEnum.DEVICES) {
        return { count: 0, alert: Number(setupSummary.value?.device_offline_count) > 0 };
    }
    return { count: 0, alert: false };
};

// 接口没拿到就保持 null，页面退回原来的四 tab 形态，不影响存量人设
const setupPending = computed(() => Boolean(setupSummary.value) && !isSetupDone.value);
// 必须项齐了才谈完善度，没齐的时候用户该看的是清单
const showEnhanceEntry = computed(() => Boolean(setupSummary.value) && isSetupDone.value);
// 配置完成后固定展示运行概况，首次等待也保留统计，不再切换成大幅就绪说明卡。
const showRunCard = computed(
    () => Boolean(setupSummary.value) && isSetupDone.value,
);
const showIssueBanner = computed(() => showEnhanceEntry.value && hasIssue.value);

const fetchSetupProgress = async (): Promise<void> => {
    if (!personId.value) return;
    try {
        setupSummary.value = await getPersonaSetupProgress({ id: personId.value });
    } catch {
        setupSummary.value = null;
    }
};

const {
    activeViralPlatform,
    expandedViralIds,
    fetchViralList,
    filteredViralList,
    handleCopyText,
    handleDismissViral,
    handleSaveViralCopy,
    handleToggleViralCopy,
    handleUpdateViralCopy,
    handleViewDismissedViral,
    handleViralPlatform,
    listLoading: viralListLoading,
    personaId: viralPersonaId,
    viralKeywords,
    viralList,
    viralPlatformCounts,
    viralPlatformTabs,
} = useViralTab();

const {
    formatRecordTime,
    getImagePlatformBadge,
    getImageRecordTime,
    getImageTags,
    getImageTitle,
    getImageUrls,
    getRecordStatusClass,
    getRecordStatusLabel,
    getVideoTagList,
    handleCopyVideoText,
    handleDeleteImageRecord,
    handleDeleteVideoRecord,
    handleDownloadVideo,
    handleImageClick,
    handlePlayVideoDetail,
    handleRetryRecord,
    handleSwitchHistoryTab,
    handleVideoClick,
    handleViewFailReason,
    historyTabs,
    activeHistoryTab,
    currentImageDetail,
    currentVideoDetail,
    imageFinished,
    imageList,
    imageLoading,
    loadNextImagePage,
    loadNextVideoPage,
    resetVideoList,
    showImageDetail,
    showVideoDetail,
    videoFinished,
    videoList,
    videoLoading,
} = useHistoryTab(personId, playItem, showVideoPreview, toRef(detail, "run_mode"));

const currentVideoDetailTags = computed(() =>
    currentVideoDetail.value ? getVideoTagList(currentVideoDetail.value) : [],
);
const currentVideoCanPlay = computed(
    () =>
        Number(currentVideoDetail.value?.status) === VideoStatus.videoSuccess &&
        Boolean(String(currentVideoDetail.value?.video_result_url || "").trim()),
);
const currentImageDetailUrls = computed(() => (currentImageDetail.value ? getImageUrls(currentImageDetail.value) : []));
const currentImageDetailTags = computed(() => (currentImageDetail.value ? getImageTags(currentImageDetail.value) : []));
const currentImageDetailTitle = computed(() =>
    currentImageDetail.value ? getImageTitle(currentImageDetail.value) : "",
);
const currentImageDetailPlatform = computed(() =>
    currentImageDetail.value ? getImagePlatformBadge(currentImageDetail.value) : null,
);
const currentImageDetailTime = computed(() =>
    currentImageDetail.value ? getImageRecordTime(currentImageDetail.value) : "",
);

const {
    deviceList,
    deviceTotal,
    deviceLoading,
    deviceFinished,
    getDeviceList,
    loadNextDevicePage,
    getDeviceAccounts,
    getDeviceIcon,
    getPlatformStyle,
    getDeviceStatusStyle,
    handleChooseDeviceConfirm,
    handleCopyDeviceCode,
    handleDeviceSetting,
    handlePhoneManagement,
    handleSelectDevice,
    handleUnbindDevice,
} = useDevicesTab(personId, showChooseDevice, chooseDeviceRef);

const {
    activeMaterialFilter,
    activeDirectMaterialFilter,
    activeMaterialTab,
    avatars,
    batchDeleteMode,
    cleanup,
    formatMaterialTime,
    getAvatarList,
    getVoiceList,
    handleAddAvatar,
    handleAddVoice,
    handleAvatarVoiceSelect,
    handleChooseAnchor,
    handleChooseVoice,
    handleCancelBatchDelete,
    handleConfirmBatchDelete,
    handleDeleteFailedSlices,
    handleConfirmCutMode,
    handleDirectMaterialFilter,
    handleMaterialFilter,
    handleMoreMaterial,
    handleOpenComposeUpload,
    handleOpenDirectPublish,
    handleOpenDirectUpload,
    handleOpenMusicUpload,
    handleOpenVoiceForAvatar,
    handlePlayMaterial,
    handlePlayMusic,
    handlePlayVoice,
    handlePreviewDirectMaterial,
    handlePreviewMaterial,
    handleRemoveMaterial,
    handleRemoveDirectMaterial,
    handleRemoveAvatar,
    handleRemoveMusic,
    handleRemoveVoice,
    handleToggleMusicBatch,
    handleToggleMusicSelected,
    handleToggleSelectAllMusic,
    handleCancelMusicBatch,
    handleConfirmMusicBatchDelete,
    handleSelectCategory,
    handleSelectHistory,
    handleSelectMaterial,
    handleSetDirectPublishVisible,
    handleSwitchPublishMode,
    handleSwitchMaterialKind,
    handleSwitchMaterialTab,
    handleToggleBatchDelete,
    handleToggleMaterialSelected,
    handleToggleSelectAll,
    hasOverusedMaterial,
    hasSlicingMaterial,
    hasSlicingTask,
    isAllMaterialSelected,
    isCurrentPlaying,
    isCurrentMusicPlaying,
    isMaterialSelected,
    isMaterialSlicing,
    isMaterialSliceFailed,
    failedMaterialCount,
    loadNextMaterialPage,
    loadNextMusicPage,
    materialFinished,
    materialKind,
    materialList,
    querySquareRefs,
    squareRefs,
    materialLoading,
    materialType,
    chooseMaterialContentType,
    musicBatchMode,
    musicFinished,
    musicList,
    musicLoading,
    selectedMusicCount,
    isMusicSelected,
    isAllMusicSelected,
    queryDirectMaterialList,
    replaceMaterialIndex,
    resetMaterialList,
    resetMusicList,
    selectedMaterialCount,
    sliceStatistics,
    showAvatarVoicePicker,
    showAvatarVoiceMenu,
    showChooseAnchor,
    showChooseVoice,
    showCutModePopup,
    showDirectPublishPanel,
    showHistory,
    showMaterialLibrary,
    showUploadCategoryPanel,
    showUploadProgress,
    forceAlbumTypePicker,
    uploadCategoryShowCategories,
    uploadCategoryTip,
    uploadMaterialList,
    voices,
} = useMaterialsTab({
    personId,
    publishMode: materialViewMode,
    materialUseMode: toRef(detail, "material_use_mode"),
    playItem,
    showVideoPreview,
    chooseAnchorRef,
    chooseVoiceRef,
    avatarVoicePickerRef,
});

const {
    copyTab,
    copyDriveType,
    copyList,
    copyLoading,
    showCopyEdit,
    editingCopy,
    currentDriverType,
    queryCopyList,
    handleSwitchCopyTab,
    handleSwitchCopyDriveType,
    handleOpenAddCopy,
    handleOpenEditCopy,
    handleSubmitCopy,
    handleCopyText: handleCopyLibraryText,
    handleRefreshCopy,
    handleRemoveCopy,
    copyBatchMode,
    selectedCopyCount,
    isCopySelected,
    isAllCopySelected,
    handleToggleCopyBatch,
    handleToggleCopySelected,
    handleToggleSelectAllCopy,
    handleCancelCopyBatch,
    handleConfirmCopyBatchDelete,
    showCopyImport,
    handleOpenImport,
    handleImportPick,
    handleDownloadImportTemplate,
    showChooseCopyAgent,
    copyAgentSystemIds,
    handleOpenAiGenerate,
    handleCopyAgentSelected,
    addGeneratedCopies,
} = useCopyLibrary(personId, toRef(detail, "persona_name"));

// 兼容数组 / 字符串 / 含 hot_words(可按人设类型分组) 的多种返回结构
const extractTrackingWords = (data: any): string[] => {
    if (Array.isArray(data) || typeof data === "string") return normalizeKeywordList(data);
    if (!data || typeof data !== "object") return [];
    const directWords = data.hot_words ?? data.words ?? data.keywords ?? data.list ?? data.lists;
    if (directWords !== undefined) {
        return getHotWordsByPersonaType({
            persona_type: data.persona_type || detail.persona_type,
            hot_words: directWords,
        });
    }
    return getHotWordsByPersonaType(data);
};

// 换一批：调用 getPersonTrackingWords 拉取新的爆款追踪词并回显（替代原先的跳转编辑）
const viralKeywordsRefreshing = ref(false);
const handleRefreshTrackingWords = async (): Promise<void> => {
    if (viralKeywordsRefreshing.value) return;
    try {
        viralKeywordsRefreshing.value = true;
        uni.showLoading({ title: "换一批中...", mask: true });
        const data = await getPersonTrackingWords({ id: personId.value });
        const keywords = extractTrackingWords(data);
        if (keywords.length) {
            viralKeywords.value = keywords;
            detail.hot_words = keywords;
        }
        uni.hideLoading();
        uni.$u.toast(keywords.length ? "已换一批" : "暂无可推荐的追踪词");
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(error || "换一批失败，请重试");
    } finally {
        viralKeywordsRefreshing.value = false;
    }
};

const viralTabState = computed(() => ({
    runMode: detail.run_mode,
    activeViralPlatform: activeViralPlatform.value,
    expandedViralIds: expandedViralIds.value,
    filteredViralList: filteredViralList.value,
    viralKeywords: viralKeywords.value,
    viralList: viralList.value,
    viralPlatformCounts: viralPlatformCounts.value,
    viralPlatformTabs,
}));

const handleOpenManualImport = () => {
    showManualImportPopup.value = true;
};

const handleManualImportSuccess = async () => {
    await fetchViralList();
};

const viralTabActions = {
    copyText: handleCopyText,
    dismiss: handleDismissViral,
    editKeywords: handleOpenHotKeywordPopup,
    removeKeyword: handleRemoveHotKeyword,
    history: () => handleSwitchTab(PageTabEnum.HISTORY),
    platform: handleViralPlatform,
    refresh: () => fetchViralList(),
    saveCopy: handleSaveViralCopy,
    toggleCopy: handleToggleViralCopy,
    updateCopy: handleUpdateViralCopy,
    viewDismissed: handleViewDismissedViral,
    refreshKeywords: handleRefreshTrackingWords,
    openManualImport: handleOpenManualImport,
};

// 素材缺口用 setupProgress 的真实总数算，别拿分页 length 猜；接口没返回就传 null 不显示
const materialShortage = computed<number | null>(() => {
    if (!setupSummary.value) return null;
    // 人设是成品直发时不做 AI 剪辑，「剪辑素材够不够」无从谈起，不提示
    if (Number(setupSummary.value.publish_mode) === PublishModeEnum.MANUAL) return null;
    const owned = Number(setupSummary.value.material_count) || 0;
    return Math.max(0, SETUP_THRESHOLD.MATERIAL_MIN - owned);
});

// 资源类型 chip 上挂真实数量：不用点进去就知道哪一块还没配
const materialTabCounts = computed<Record<string, number>>(() => {
    const data = setupSummary.value;
    if (!data) return {};
    return {
        // 剪辑原片库 / 成品直发库是本地视图切换，chip 要跟着用户正在看的那个库走
        [MaterialTabEnum.COMPOSE]:
            materialViewMode.value === PublishModeEnum.MANUAL
                ? Number(data.direct_material_count) || 0
                : Number(data.material_count) || 0,
        // 冻结的绑定照样列在 tab 里，chip 用绑定总数才不会出现「写 0 个、下面列 3 个」；
        // 清单和告警条判「能不能用」仍走 avatar_count / voice_count
        [MaterialTabEnum.AVATAR]: Number(data.avatar_total ?? data.avatar_count) || 0,
        [MaterialTabEnum.VOICE]: Number(data.voice_total ?? data.voice_count) || 0,
        [MaterialTabEnum.COPY]: Number(data.copywriting_count) || 0,
        [MaterialTabEnum.MUSIC]: Number(data.music_count) || 0,
    };
});

/** 顺序使用会循环取用，只有随机使用时才按随机规则判断能否重复 */
const directReuseLabel = computed(() =>
    Number(setupSummary.value?.product_use_mode) === ProductUseModeEnum.SEQUENCE ||
    Number(setupSummary.value?.product_reuse_mode) === ProductReuseModeEnum.REPEAT
        ? "可重复使用"
        : "每条只能使用一次",
);

const materialsTabState = computed(() => ({
    materialTabCounts: materialTabCounts.value,
    directReuseLabel: directReuseLabel.value,
    materialShortage: materialShortage.value,
    materialMin: SETUP_THRESHOLD.MATERIAL_MIN,
    activeDirectMaterialFilter: activeDirectMaterialFilter.value,
    activeMaterialFilter: activeMaterialFilter.value,
    activeMaterialTab: activeMaterialTab.value,
    avatars: avatars.value,
    batchDeleteMode: batchDeleteMode.value,
    formatMaterialTime,
    hasOverusedMaterial: hasOverusedMaterial.value,
    isAllMaterialSelected: isAllMaterialSelected.value,
    isCurrentPlaying,
    isCurrentMusicPlaying,
    isMaterialSelected,
    isMaterialSlicing,
    isMaterialSliceFailed,
    failedMaterialCount: failedMaterialCount.value,
    hasSlicingMaterial: hasSlicingMaterial.value,
    hasSlicingTask: hasSlicingTask.value,
    materialFilters,
    materialFinished: materialFinished.value,
    materialList: materialList.value,
    squareJoinedIds: squareRefs.value.map((item) => Number(item.asset_id || 0)).filter(Boolean),
    materialLoading: materialLoading.value,
    materialPublishModes,
    materialKind: materialKind.value,
    materialKindOptions,
    materialSubTabs,
    musicBatchMode: musicBatchMode.value,
    musicFinished: musicFinished.value,
    musicList: musicList.value,
    musicLoading: musicLoading.value,
    selectedMusicCount: selectedMusicCount.value,
    isMusicSelected,
    isAllMusicSelected: isAllMusicSelected.value,
    publishMode: materialViewMode.value,
    selectedMaterialCount: selectedMaterialCount.value,
    sliceStatistics: sliceStatistics.value,
    showDirectPublishPanel: showDirectPublishPanel.value,
    voices: voices.value,
    copyTab: copyTab.value,
    copyDriveType: copyDriveType.value,
    copyList: copyList.value,
    copyLoading: copyLoading.value,
    copyTabs,
    copyDriveTypes,
    copyBatchMode: copyBatchMode.value,
    selectedCopyCount: selectedCopyCount.value,
    isCopySelected,
    isAllCopySelected: isAllCopySelected.value,
}));

const materialsTabActions = {
    popupChange: (visible: boolean) => { materialsOverlayVisible.value = visible; },
    addAvatar: handleAddAvatar,
    addVoice: handleAddVoice,
    cancelBatchDelete: handleCancelBatchDelete,
    confirmBatchDelete: handleConfirmBatchDelete,
    deleteFailedSlices: handleDeleteFailedSlices,
    directMaterialFilter: handleDirectMaterialFilter,
    directPublish: handleOpenDirectPublish,
    directPublishVisible: handleSetDirectPublishVisible,
    materialFilter: handleMaterialFilter,
    refreshSquareRefs: querySquareRefs,
    openSquarePicker: () => {
        showSquarePicker.value = true;
    },
    closeSquarePicker: () => {
        showSquarePicker.value = false;
    },
    openPublicCopyPicker: () => {
        showPublicCopyPicker.value = true;
    },
    closePublicCopyPicker: () => {
        showPublicCopyPicker.value = false;
    },
    moreMaterial: handleMoreMaterial,
    openDirectUpload: handleOpenDirectUpload,
    openVoiceForAvatar: handleOpenVoiceForAvatar,
    playMaterial: handlePlayMaterial,
    playMusic: handlePlayMusic,
    playVoice: handlePlayVoice,
    previewDirectMaterial: handlePreviewDirectMaterial,
    previewMaterial: handlePreviewMaterial,
    queryDirectMaterial: queryDirectMaterialList,
    removeMaterial: handleRemoveMaterial,
    removeDirectMaterial: handleRemoveDirectMaterial,
    removeAvatar: handleRemoveAvatar,
    removeMusic: handleRemoveMusic,
    removeVoice: handleRemoveVoice,
    toggleMusicBatch: handleToggleMusicBatch,
    toggleMusicSelected: handleToggleMusicSelected,
    toggleSelectAllMusic: handleToggleSelectAllMusic,
    cancelMusicBatch: handleCancelMusicBatch,
    confirmMusicBatchDelete: handleConfirmMusicBatchDelete,
    switchMaterialTab: async (tab: MaterialTabEnum) => {
        handleSwitchMaterialTab(tab);
        await loadMaterialTabData(tab);
    },
    switchPublishMode: (mode: PublishMode) => handleSwitchPublishMode(mode),
    switchMaterialKind: (kind: MaterialKindEnum) => handleSwitchMaterialKind(kind),
    toggleBatchDelete: handleToggleBatchDelete,
    toggleMaterialSelected: handleToggleMaterialSelected,
    toggleSelectAll: handleToggleSelectAll,
    upload: handleOpenComposeUpload,
    uploadMusic: handleOpenMusicUpload,
    switchCopyTab: handleSwitchCopyTab,
    switchCopyDriveType: handleSwitchCopyDriveType,
    addCopy: handleOpenAddCopy,
    editCopy: handleOpenEditCopy,
    copyText: handleCopyLibraryText,
    refreshCopy: handleRefreshCopy,
    removeCopy: handleRemoveCopy,
    importCopy: handleOpenImport,
    aiGenerate: handleOpenAiGenerate,
    toggleCopyBatch: handleToggleCopyBatch,
    toggleCopySelected: handleToggleCopySelected,
    toggleSelectAllCopy: handleToggleSelectAllCopy,
    cancelCopyBatch: handleCancelCopyBatch,
    confirmCopyBatchDelete: handleConfirmCopyBatchDelete,
};

const devicesTabState = computed(() => ({
    runMode: detail.run_mode,
    deviceList: deviceList.value,
    deviceTotal: deviceTotal.value,
    deviceLoading: deviceLoading.value,
    deviceFinished: deviceFinished.value,
    getDeviceAccounts,
    getDeviceIcon,
    getPlatformStyle,
    getDeviceStatusStyle,
}));

const devicesTabActions = {
    runMode: () =>
        handleSwitchRunMode(
            isCloudRunMode(detail.run_mode) ? RunModeApiEnum.PHONE : RunModeApiEnum.CLOUD,
        ),
    copyDeviceCode: handleCopyDeviceCode,
    deviceSetting: handleDeviceSetting,
    phoneManagement: handlePhoneManagement,
    selectDevice: handleSelectDevice,
    unbind: handleUnbindDevice,
};

const historyTabState = computed(() => ({
    runMode: detail.run_mode,
    nextRunLabel: formatNextRun(nextRunAt.value),
    activeHistoryTab: activeHistoryTab.value,
    formatRecordTime,
    getImagePlatformBadge,
    getImageRecordTime,
    getImageTags,
    getImageTitle,
    getImageUrls,
    getRecordStatusClass,
    getRecordStatusLabel,
    getVideoTagList,
    historyTabs,
    imageFinished: imageFinished.value,
    imageList: imageList.value,
    imageLoading: imageLoading.value,
    videoFailed: VideoStatus.videoFailed,
    videoFinished: videoFinished.value,
    videoList: videoList.value,
    videoLoading: videoLoading.value,
    videoSuccess: VideoStatus.videoSuccess,
}));

const historyTabActions = {
    copyVideoText: handleCopyVideoText,
    deleteImageRecord: handleDeleteImageRecord,
    deleteRecord: handleDeleteVideoRecord,
    downloadVideo: handleDownloadVideo,
    imageClick: handleImageClick,
    retryRecord: handleRetryRecord,
    switchHistoryTab: handleSwitchHistoryTab,
    videoClick: handleVideoClick,
    viewFailReason: handleViewFailReason,
};

const runTabFirstLoad = async (key: keyof typeof loadedState, loader: () => Promise<unknown>) => {
    if (loadedState[key]) return;
    const token = ++tabLoadToken;
    tabFirstLoading.value = true;
    try {
        await loader();
        if (token === tabLoadToken) loadedState[key] = true;
    } finally {
        if (token === tabLoadToken) tabFirstLoading.value = false;
    }
};

const loadMaterialTabData = async (tab = activeMaterialTab.value): Promise<void> => {
    // 主 Tab「素材库」首次：内容区 loading；子 Tab 仍走各自列表内加载，避免盖住二级导航
    if (tab === MaterialTabEnum.COMPOSE) {
        await runTabFirstLoad("materials", () => resetMaterialList());
        return;
    }
    if (tab === MaterialTabEnum.AVATAR && !loadedState.avatars) {
        loadedState.avatars = true;
        getAvatarList();
        return;
    }
    if (tab === MaterialTabEnum.VOICE && !loadedState.voices) {
        loadedState.voices = true;
        getVoiceList();
        return;
    }
    if (tab === MaterialTabEnum.COPY && !loadedState.copy) {
        loadedState.copy = true;
        queryCopyList();
        return;
    }
    if (tab === MaterialTabEnum.MUSIC && !loadedState.music) {
        loadedState.music = true;
        resetMusicList();
    }
};

const loadActiveTabData = async (): Promise<void> => {
    if (activeTab.value === PageTabEnum.HOT) {
        await runTabFirstLoad("hot", () => fetchViralList());
        return;
    }
    if (activeTab.value === PageTabEnum.MATERIALS) {
        await loadMaterialTabData();
        return;
    }
    if (activeTab.value === PageTabEnum.DEVICES) {
        await runTabFirstLoad("devices", () => getDeviceList());
        return;
    }
    if (activeTab.value === PageTabEnum.HISTORY) {
        await runTabFirstLoad("history", () => resetVideoList());
    }
};

const handleSwitchTab = async (tab: PageTabEnum) => {
    checklistExpanded.value = false;
    activeTab.value = tab;
    await loadActiveTabData();
};

// 清单里点某一步：能在本页完成的就地打开，需要换页的才跳走
// 无手机模式直接改 run_mode（服务端 update 是局部更新，内部还会跑
// assertPersonaRunModeCompatible，不兼容时把它的报错原样透出来）
const RUN_MODE_COPY: Record<number, { title: string; content: string; done: string }> = {
    [RunModeApiEnum.CLOUD]: {
        title: "切到无手机模式",
        content: "成片将在云端生成，需要你自己下载后发布。确定要切换吗？",
        done: "已切到无手机模式",
    },
    [RunModeApiEnum.PHONE]: {
        title: "切回手机模式",
        content: "成片将由绑定的 AI 手机自动发布，需要先绑一台设备。确定要切换吗？",
        done: "已切回手机模式",
    },
};

/**
 * 切模式被文案来源挡住时的引导。
 * 这不是用户操作失误，而是一个有前置条件的选择，所以不按错误态处理：
 * 不用 toast（一闪而过、点不了），改用能留在屏幕上、直接给出路的确认框。
 */
const showCopySourceSheet = ref(false);
const showRunModeConfirm = ref(false);

// 本页弹窗、素材库内弹窗/批量栏及上传二级选择器显式互斥，不能只靠层级盖住按钮。
const showSetupBar = computed(() =>
    !loading.value && setupPending.value && Boolean(setupBarText.value) && ![
        materialsOverlayVisible.value,
        uploadOverlayVisible.value,
        showCopySourceSheet.value,
        showRunModeConfirm.value,
        showEnhanceSheet.value,
        showChooseDevice.value,
        showUploadCategoryPanel.value,
        showCutModePopup.value,
        showHistory.value,
        showMaterialLibrary.value,
        showUploadProgress.value,
        showHotKeywordPopup.value,
        showManualImportPopup.value,
        showSquarePicker.value,
        showPublicCopyPicker.value,
        showVideoDetail.value,
        showImageDetail.value,
        showVideoPreview.value,
        showCopyEdit.value,
        showCopyImport.value,
        showChooseCopyAgent.value,
        showChooseAnchor.value,
        showChooseVoice.value,
        showAvatarVoicePicker.value,
        showAvatarVoiceMenu.value,
    ].some(Boolean),
);

const handleRunModeError = (target: RunModeApiEnum, message: string): void => {
    if (target !== RunModeApiEnum.CLOUD || !message.includes("爆款仿写")) {
        uni.showToast({ title: message || "切换失败", icon: "none", duration: 3000 });
        return;
    }
    showCopySourceSheet.value = true;
};

// 一次请求把文案来源和运行模式一起改掉，用户留在清单里，不用跑第二趟
const handleConfirmCopySource = async (source: CopywritingSourceEnum): Promise<void> => {
    showCopySourceSheet.value = false;
    uni.showLoading({ title: "切换中...", mask: true });
    try {
        await updatePerson({
            id: personId.value,
            run_mode: RunModeApiEnum.CLOUD,
            copywriting_source: source,
        });
        uni.showToast({ title: "已切到无手机模式", icon: "none" });
        await Promise.allSettled([getDetail(), fetchSetupProgress()]);
    } catch (error: any) {
        uni.showToast({ title: error || "切换失败", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

const handleSwitchRunMode = (target: RunModeApiEnum): void => {
    // 成品直发没有云端出片这条路，服务端会把 run_mode 归一回手机模式。
    // 不在这里拦住，用户点完会看到「已切到无手机模式」，刷新后又变回手机模式
    if (target === RunModeApiEnum.CLOUD && isProductDirect.value) {
        uni.showToast({ title: "成品直发由绑定的 AI 手机发布，没有无手机模式", icon: "none", duration: 3000 });
        return;
    }
    const copy = RUN_MODE_COPY[target];
    showRunModeConfirm.value = true;
    uni.showModal({
        title: copy.title,
        content: copy.content,
        complete: () => { showRunModeConfirm.value = false; },
        success: async ({ confirm }) => {
            if (!confirm) return;
            uni.showLoading({ title: "切换中...", mask: true });
            try {
                await updatePerson({ id: personId.value, run_mode: target });
                uni.showToast({ title: copy.done, icon: "none" });
                await Promise.allSettled([getDetail(), fetchSetupProgress()]);
            } catch (error: any) {
                uni.hideLoading();
                handleRunModeError(target, String(error ?? ""));
                return;
            } finally {
                uni.hideLoading();
            }
        },
    });
};

// 第 4 步的两条路：绑手机直接开选择设备弹窗，无手机模式就地切换
const handleSetupOption = async (key: SetupStepEnum, value: number): Promise<void> => {
    if (key !== SetupStepEnum.PUBLISH_MODE) {
        await handleSetupStep(key);
        return;
    }
    if (isCloudRunMode(value)) {
        handleSwitchRunMode(RunModeApiEnum.CLOUD);
        return;
    }
    handleSelectDevice();
};

const handleSetupStep = async (key: SetupStepEnum): Promise<void> => {
    if (key === SetupStepEnum.KEYWORD) {
        handleOpenHotKeywordPopup();
        return;
    }
    if (key === SetupStepEnum.PUBLISH_PLAN) {
        handleNavigate(isCloudRunMode(detail.run_mode)
            ? `/ai_modules/person/pages/material_library/material_library?id=${personId.value}&open=rule`
            : `/ai_modules/person/pages/task_flow/task_flow?id=${personId.value}`);
        return;
    }
    if (key === SetupStepEnum.MATERIAL) {
        const materialsLoading = handleManageSetupMaterials();
        // 主动上传入口立即弹出来源选择，不等列表请求结束，避免关闭或切 Tab 后迟到重开。
        // 上传场景决定素材落进哪个库（DIRECT 固定写 publish_mode=2），必须按人设发布方式选
        if (detail.publish_mode === PublishModeEnum.MANUAL) {
            handleOpenDirectUpload();
        } else {
            handleOpenComposeUpload();
        }
        await materialsLoading;
        return;
    }
    if (key === SetupStepEnum.PUBLISH_MODE) {
        await handleSwitchTab(PageTabEnum.DEVICES);
        return;
    }
    await handleSwitchTab(PageTabEnum.MATERIALS);
    handleSwitchMaterialTab(MaterialTabEnum.AVATAR);
};

const handleManageSetupMaterials = async (): Promise<void> => {
    // 只重置浏览态，不改人设持久化的发布方式或素材模式。
    // 该看哪个库取决于人设自己的发布方式：成品直发的人设要配的是成品，不是剪辑原片
    materialViewMode.value =
        detail.publish_mode === PublishModeEnum.MANUAL ? PublishModeEnum.MANUAL : PublishModeEnum.AUTO;
    activeMaterialFilter.value = 0;
    handleCancelBatchDelete();
    handleSwitchMaterialTab(MaterialTabEnum.COMPOSE);
    loadedState.materials = false;
    await handleSwitchTab(PageTabEnum.MATERIALS);
};

const handleSetupEdit = (key: SetupStepEnum): void => {
    if (key === SetupStepEnum.MATERIAL) {
        handleManageSetupMaterials();
        return;
    }
    handleSetupStep(key);
};

const handleInitialOpen = async (): Promise<void> => {
    if (initialOpen.value !== 'material') return;
    initialOpen.value = '';
    await handleSetupStep(SetupStepEnum.MATERIAL);
};

// 加分项和清单共用同一批落点，点完关掉半屏，避免用户回来还要先关弹窗
const handleEnhanceItem = async (key: EnhanceItemEnum): Promise<void> => {
    showEnhanceSheet.value = false;
    if (key === EnhanceItem.KEYWORD) {
        handleOpenHotKeywordPopup();
        return;
    }
    if (key === EnhanceItem.ACCOUNT) {
        await handleSwitchTab(PageTabEnum.DEVICES);
        return;
    }
    // 有现成弹窗的直接开，别把用户丢到一个他可能已经在看的 tab 上
    const directOpen: Partial<Record<EnhanceItemEnum, () => void>> = {
        [EnhanceItem.MATERIAL]: () => handleSetupStep(SetupStepEnum.MATERIAL),
        [EnhanceItem.MUSIC]: handleOpenMusicUpload,
        [EnhanceItem.AVATAR]: handleAddAvatar,
        [EnhanceItem.VOICE]: handleAddVoice,
    };
    if (directOpen[key]) {
        directOpen[key]?.();
        return;
    }
    await handleSwitchTab(PageTabEnum.MATERIALS);
    handleSwitchMaterialTab(MaterialTabEnum.COPY);
};

const handleIssue = async (key: PersonaIssueEnum): Promise<void> => {
    // 冻结是会员到期造成的，人设页面里没有任何开关能改回来，只能把用户送到订阅入口
    if (key === PersonaIssue.PERSONA_DISABLED) {
        uni.showModal({
            title: "人设已冻结",
            content: "会员权益到期后，超出免费额度的人设、形象与音色会被冻结，自动生成与发布随之停止。恢复会员后会自动解冻。",
            confirmText: "去开通",
            cancelText: "知道了",
            success: ({ confirm }) => {
                if (confirm) uni.$u.route({ url: "/packages/pages/user/user" });
            },
        });
        return;
    }
    if (key === PersonaIssue.GENERATE_FAILED) {
        await handleSwitchTab(PageTabEnum.HISTORY);
        return;
    }
    if (key === PersonaIssue.NO_DEVICE || key === PersonaIssue.DEVICE_OFFLINE) {
        await handleSwitchTab(PageTabEnum.DEVICES);
        return;
    }
    await handleSwitchTab(PageTabEnum.MATERIALS);
    handleSwitchMaterialTab(
        key === PersonaIssue.AVATAR_MISSING ? MaterialTabEnum.AVATAR : MaterialTabEnum.VOICE,
    );
};

// 素材、形象、音色、设备、追踪词都在本页弹窗里配，不触发 onShow，
// 清单进度、素材缺口、资源 chip 计数全都来自 setupProgress，得自己盯着变化重拉。
// 素材/音乐/文案是分页列表，滚动加载也会改长度，所以做防抖，一串变化只发一次
let setupRefreshTimer: ReturnType<typeof setTimeout> | null = null;

const scheduleSetupRefresh = (): void => {
    if (!initialized.value) return;
    if (setupRefreshTimer) clearTimeout(setupRefreshTimer);
    setupRefreshTimer = setTimeout(() => {
        setupRefreshTimer = null;
        fetchSetupProgress();
    }, 600);
};

watch(
    () => [
        avatars.value.length,
        voices.value.length,
        deviceList.value.length,
        viralKeywords.value.length,
        materialList.value.length,
        musicList.value.length,
        copyList.value.length,
    ].join("/"),
    scheduleSetupRefresh,
);

onUnmounted(() => {
    if (setupRefreshTimer) clearTimeout(setupRefreshTimer);
    setupRefreshTimer = null;
    materialsOverlayVisible.value = false;
    uploadOverlayVisible.value = false;
});

const handleEditType = (): void => {
    uni.navigateTo({
        url: "/ai_modules/person/pages/employee_setting/employee_setting?mode=edit&id=" + personId.value,
    });
};

const handleNavigate = (path: string): void => {
    if (!path) return;
    if (path.includes("?")) {
        uni.navigateTo({ url: path });
        return;
    }
    uni.$u.route({ url: path, params: { id: personId.value } });
};

const init = async (): Promise<void> => {
    loading.value = true;
    try {
        if (activeTab.value === PageTabEnum.HOT) {
            await Promise.allSettled([getDetail(), fetchSetupProgress(), fetchViralList()]);
            loadedState.hot = true;
        } else {
            await Promise.allSettled([getDetail(), fetchSetupProgress()]);
        }
    } finally {
        loading.value = false;
        initialized.value = true;
    }
    // 非默认 Tab 首次进入：页面骨架结束后在内容区展示 Tab loading
    if (activeTab.value !== PageTabEnum.HOT) {
        await loadActiveTabData();
    }
    await handleInitialOpen();
};

// 复用视频 AI 文案页：生成完成后通过 eventBus 回传，批量写入文案库
const { on: onEventBus } = useEventBusManager();

onShow(() => {
    if (!initialized.value || !personId.value) return;
    getDetail();
    fetchSetupProgress();
    fetchViralList();
});

onLoad((options: any) => {
    personId.value = options.id ?? "";
    initialOpen.value = options.open ?? '';
    viralPersonaId.value = personId.value;
    const targetTab = options.tab as PageTabEnum;
    if (Object.values(PageTabEnum).includes(targetTab)) {
        activeTab.value = targetTab;
    }
    onEventBus("confirm", (res: any) => {
        if (res?.type === AI_COPYWRITER_EVENT || res?.type === PUBLISH_AI_COPYWRITER_EVENT) {
            addGeneratedCopies(res.data);
        }
    });
    init();
});

onReachBottom(() => {
    if (activeTab.value === PageTabEnum.HISTORY && activeHistoryTab.value === HistoryTabEnum.VIDEOS) {
        loadNextVideoPage();
    }
    if (activeTab.value === PageTabEnum.HISTORY && activeHistoryTab.value === HistoryTabEnum.IMAGES) {
        loadNextImagePage();
    }
    if (activeTab.value === PageTabEnum.MATERIALS && activeMaterialTab.value === MaterialTabEnum.COMPOSE) {
        loadNextMaterialPage();
    }
    if (activeTab.value === PageTabEnum.MATERIALS && activeMaterialTab.value === MaterialTabEnum.MUSIC) {
        loadNextMusicPage();
    }
    if (activeTab.value === PageTabEnum.DEVICES) {
        loadNextDevicePage();
    }
});

onUnload(() => {
    cleanup();
});
</script>

<style scoped lang="scss">
.detail-card-shadow {
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}

.persona-detail {
    @apply pb-[calc(40rpx+env(safe-area-inset-bottom))];
}

.persona-detail--setup {
    @apply pb-[calc(184rpx+env(safe-area-inset-bottom))];
}

.setup-bar {
    @apply fixed left-0 right-0 bottom-0 z-10 bg-white px-[32rpx] pt-[20rpx] flex items-center gap-[24rpx]
        border-[0] border-t-[2rpx] border-solid border-light;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}

.setup-bar-btn {
    @apply min-h-[94rpx] min-w-[290rpx] shrink-0 px-[34rpx] rounded-[24rpx] bg-primary flex items-center justify-center gap-[14rpx];
}

.persona-hero {
    @apply px-[32rpx] pt-[32rpx] pb-[24rpx];
}

.persona-avatar {
    @apply w-[116rpx] h-[116rpx] rounded-full overflow-hidden border-[6rpx] border-solid border-white;
    box-shadow: 0 6rpx 24rpx #263e5710;
}

.persona-dot {
    @apply absolute right-[2rpx] bottom-[2rpx] w-[26rpx] h-[26rpx] rounded-full
        border-[4rpx] border-solid border-white;
}

.persona-type {
    @apply shrink-0 text-[22rpx] font-medium text-[#4F617B] rounded-[10rpx] px-[10rpx] py-[2rpx] border-[2rpx] border-solid border-[#DCE7F7];
}

.persona-meta {
    @apply flex flex-wrap items-center gap-[14rpx] mt-[12rpx] text-[24rpx] leading-[36rpx] text-[#647184];
}

.persona-meta-separator {
    @apply w-[6rpx] h-[6rpx] shrink-0 rounded-full bg-[#B0BBCA];
}

.persona-tag {
    @apply max-w-[240rpx] line-clamp-1;
}

.persona-edit {
    @apply shrink-0 flex items-center justify-center w-[88rpx] h-[88rpx] mr-[-16rpx];
}

.quick-card {
    @apply bg-white rounded-[36rpx] py-[20rpx] grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.quick-item {
    @apply relative min-w-0 min-h-[100rpx] flex flex-col items-center justify-center gap-[14rpx];
    & + & {
        @apply border-[0] border-l-[2rpx] border-solid border-[#EEF1F6];
    }
}

.quick-icon {
    @apply w-[48rpx] h-[48rpx] flex items-center justify-center;
}

.quick-label {
    @apply text-[24rpx] text-[#465369];
}

.quick-new {
    @apply absolute top-[-6rpx] right-[16rpx] text-[16rpx] font-bold text-[#936118] bg-[#FFF0D8] rounded-[8rpx] px-[8rpx];
    line-height: 26rpx;
}

// 页面级切换用下划线 tab：4 项时药丸太挤，且会和内容区的 chip 打架
.page-tabs-shell {
    @apply sticky z-[2] px-[32rpx] mt-[32rpx] bg-[#F5F7FA];
}

.page-tabs {
    @apply flex border-[0] border-b-[2rpx] border-solid border-[#DFE5ED];
}

.page-tab {
    @apply flex-1 min-w-0 flex flex-col items-center justify-end gap-[24rpx] pt-[24rpx] min-h-[102rpx];
}

.page-tab-label {
    @apply text-[28rpx] font-medium text-[#657185] line-clamp-1;
}

.page-tab-label--on {
    @apply font-bold text-[#1F2D42];
}

.page-tab-dot {
    @apply w-[12rpx] h-[12rpx] rounded-full bg-error self-start mt-[4rpx];
}

.page-tab-line {
    @apply w-[48rpx] h-[6rpx] rounded-[4rpx] bg-[transparent];
}

.page-tab-line--on {
    @apply bg-primary;
}

.tab-first-loading {
    @apply flex flex-col items-center justify-center gap-[16rpx] py-[160rpx];
}

.tab-first-loading-text {
    @apply text-[24rpx] text-[#999999];
}

@media (prefers-reduced-motion: reduce) {
    .animate-pulse {
        animation: none;
    }
}

</style>
