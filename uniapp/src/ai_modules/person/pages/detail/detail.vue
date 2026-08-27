<template>
    <view class="min-h-screen bg-[#F4F6FA] pb-[400rpx]">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#FFFFFF' }"
            title="人设管理"
            title-color="#1A1A1A"
            back-icon-color="#1A1A1A"
            title-bold />

        <template v-if="loading">
            <view class="px-[32rpx] pt-[24rpx]">
                <view class="bg-white rounded-[32rpx] p-[32rpx] detail-card-shadow animate-pulse">
                    <view class="flex items-center gap-[28rpx]">
                        <view class="w-[124rpx] h-[124rpx] rounded-full bg-[#F3F4F6]"></view>
                        <view class="flex-1">
                            <view class="h-[32rpx] w-[240rpx] rounded-full bg-[#F3F4F6] mb-[20rpx]"></view>
                            <view class="flex gap-[12rpx]">
                                <view class="h-[36rpx] w-[120rpx] rounded-full bg-[#F3F4F6]"></view>
                                <view class="h-[36rpx] w-[120rpx] rounded-full bg-[#F3F4F6]"></view>
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
            <view class="px-[32rpx] pt-[24rpx]">
                <view class="bg-white rounded-[32rpx] p-[32rpx] detail-card-shadow">
                    <view class="flex items-center gap-[28rpx]">
                        <view class="relative shrink-0">
                            <view
                                class="w-[124rpx] h-[124rpx] rounded-full overflow-hidden"
                                style="box-shadow: 0 0 0 6rpx #ffffff, 0 0 0 10rpx #ebf2ff">
                                <image :src="detail.avatar_url" class="w-full h-full" mode="aspectFill" />
                            </view>
                            <view
                                class="absolute right-[4rpx] bottom-[4rpx] w-[24rpx] h-[24rpx] rounded-full bg-[#4ADE80] border-[4rpx] border-solid border-white"></view>
                        </view>

                        <view class="flex-1 min-w-0">
                            <view class="flex items-center justify-between gap-[10rpx] mb-[12rpx]">
                                <view class="flex items-center gap-[10rpx]">
                                    <text class="text-[30rpx] font-bold text-[#1A1A1A] line-clamp-1">
                                        {{ detail.persona_name || "未命名人设" }}
                                    </text>
                                    <text
                                        class="text-[20rpx] font-bold text-white bg-[#374151] px-[14rpx] py-[4rpx] rounded-[10rpx] shrink-0">
                                        {{ personTypeLabel }}
                                    </text>
                                </view>
                                <view
                                    class="px-[18rpx] py-[6rpx] rounded-full flex items-center gap-[8rpx] shrink-0"
                                    style="background: #ebf2ff; border: 2rpx solid #bad4ff"
                                    @click="handleEditType">
                                    <u-icon name="setting" color="#0065FB" size="22"></u-icon>
                                    <text class="text-[22rpx] font-semibold text-primary">修改人设</text>
                                </view>
                            </view>

                            <view class="flex flex-wrap gap-[8rpx] mb-[18rpx]">
                                <template v-if="identityTags.length">
                                    <text
                                        v-for="(tag, index) in identityTags"
                                        :key="`identity-${index}`"
                                        class="persona-tag persona-tag--primary">
                                        {{ tag }}
                                    </text>
                                </template>
                                <text v-else class="persona-tag persona-tag--primary">AI数字员工</text>
                                <text v-if="detail.content_focus" class="persona-tag persona-tag--accent">
                                    {{ detail.content_focus }}
                                </text>
                                <text v-else class="persona-tag persona-tag--accent">内容运营</text>
                            </view>
                        </view>
                    </view>

                    <view
                        class="persona-action-grid mt-[28rpx] pt-[24rpx] border-0 border-t border-solid border-[#F3F4F6]">
                        <view
                            class="persona-action-card active:opacity-80"
                            @click="
                                handleNavigate(
                                    `/ai_modules/person/pages/analysis/analysis?id=${personId}&type=${detail.persona_type}&mode=edit`,
                                )
                            ">
                            <view class="persona-action-icon">
                                <image
                                    src="@/ai_modules/person/static/icons/persona-report.svg"
                                    class="w-[36rpx] h-[36rpx]"
                                    mode="aspectFit"></image>
                            </view>
                            <text class="persona-action-title">人设报告</text>
                            <text class="persona-action-desc">运营分析</text>
                        </view>

                        <view
                            class="persona-action-card active:opacity-80"
                            @click="handleNavigate(`/ai_modules/person/pages/task_flow/task_flow?id=${personId}`)">
                            <view class="persona-action-icon">
                                <image
                                    src="@/ai_modules/person/static/icons/robot.svg"
                                    class="w-[34rpx] h-[34rpx]"
                                    mode="aspectFit"></image>
                            </view>
                            <text class="persona-action-title">任务流</text>
                            <text class="persona-action-desc">24h自动</text>
                        </view>

                        <view
                            class="persona-action-card active:opacity-80"
                            @click="handleNavigate(`/ai_modules/person/pages/ai_employee/ai_employee?id=${personId}`)">
                            <text class="persona-action-new">NEW</text>
                            <view class="persona-action-icon">
                                <image
                                    src="@/ai_modules/person/static/icons/ai.svg"
                                    class="w-[34rpx] h-[34rpx]"
                                    mode="aspectFit"></image>
                            </view>
                            <text class="persona-action-title">AI 员工</text>
                            <text class="persona-action-desc">能力开关</text>
                        </view>
                    </view>
                </view>
            </view>

            <view class="px-[32rpx] mt-[18rpx]">
                <view class="flex items-center bg-[#EEF1F6] p-[8rpx] rounded-[22rpx]">
                    <view
                        v-for="tab in pageTabs"
                        :key="tab.key"
                        class="h-[80rpx] flex-1 min-w-0 rounded-[18rpx] flex items-center justify-center"
                        :class="activeTab === tab.key ? 'bg-white shadow-[0_8rpx_24rpx_rgba(14,35,72,0.08)]' : ''"
                        @click="handleSwitchTab(tab.key)">
                        <text
                            class="text-[28rpx] font-bold line-clamp-1"
                            :class="activeTab === tab.key ? 'text-primary' : 'text-[#6B7280]'">
                            {{ tab.label }}
                        </text>
                    </view>
                </view>
            </view>

            <view class="px-[32rpx] pt-[24rpx] min-h-[360rpx]">
                <view v-if="tabFirstLoading" class="tab-first-loading">
                    <u-loading mode="circle" size="44" color="#2F73F6"></u-loading>
                    <text class="tab-first-loading-text">加载中...</text>
                </view>
                <template v-else>
                    <viral-tab v-if="activeTab === PageTabEnum.HOT" :state="viralTabState" :actions="viralTabActions" />

                    <materials-tab
                        v-if="activeTab === PageTabEnum.MATERIALS"
                        :state="materialsTabState"
                        :actions="materialsTabActions" />

                    <devices-tab
                        v-if="activeTab === PageTabEnum.DEVICES"
                        :state="devicesTabState"
                        :actions="devicesTabActions" />

                    <history-tab
                        v-if="activeTab === PageTabEnum.HISTORY"
                        :state="historyTabState"
                        :actions="historyTabActions" />
                </template>
            </view>
        </template>

        <choose-device ref="chooseDeviceRef" v-model="showChooseDevice" @confirm="handleChooseDeviceConfirm" />
        <upload-category-panel
            v-model="showUploadCategoryPanel"
            :show-categories="uploadCategoryShowCategories"
            :tip="uploadCategoryTip"
            :force-album-type-picker="forceAlbumTypePicker"
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
        <choose-anchor v-model="showChooseAnchor" ref="chooseAnchorRef" @select="handleChooseAnchor" />
        <choose-voice
            v-if="showChooseVoice"
            v-model="showChooseVoice"
            ref="chooseVoiceRef"
            :show-free-tone="false"
            :model-version="PERSONA_MATERIAL_VOICE_MODEL_VERSIONS"
            @select="handleChooseVoice" />
        <choose-voice
            v-if="showAvatarVoicePicker"
            v-model="showAvatarVoicePicker"
            ref="avatarVoicePickerRef"
            :show-free-tone="false"
            :show-original-tone="true"
            :model-version="PERSONA_MATERIAL_VOICE_MODEL_VERSIONS"
            @select="handleAvatarVoiceSelect" />
    </view>
</template>

<script setup lang="ts">
import { getPersonDetail, getPersonTrackingWords, updatePersonTrackingWords } from "@/api/person";
import { DigitalHumanModelVersionEnum, PersonTypeEnum, PersonTypeMap } from "@/enums/appEnums";
import { PERSONA_MATERIAL_VOICE_MODEL_VERSIONS } from "@/ai_modules/person/enums";
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
import ViralTab from "./components/viral-tab.vue";
import ManualImportPopup from "./components/popups/manual-import-popup.vue";
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
} from "./hooks/useMaterialsTab";
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

const HOT_WORD_MAX_COUNT = 20;
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
});

// 剪辑原片库 / 成品直发库：仅本地视图切换，不与人设 detail.publish_mode 绑定，
// 避免 onShow 拉详情后 Tab 被还原、列表未刷新导致错显
const materialViewMode = ref<PublishMode>(PublishModeEnum.AUTO);

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
const showHotKeywordPopup = ref(false);
const showManualImportPopup = ref(false);
const hotKeywordPopupValue = ref("");
const hotKeywordSubmitting = ref(false);
const hotKeywordEditingIndex = ref(-1);
const hotKeywordPopupTitle = computed(() => (hotKeywordEditingIndex.value >= 0 ? "编辑爆款追踪词" : "添加爆款追踪词"));
const hotKeywordPopupConfirmText = computed(() => (hotKeywordEditingIndex.value >= 0 ? "保存" : "添加"));
const hotKeywordPopupTip = computed(() =>
    hotKeywordEditingIndex.value >= 0
        ? "清空内容并保存可删除该关键词。"
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
        if (viralKeywords.value.length >= HOT_WORD_MAX_COUNT) {
            uni.$u.toast(`最多添加 ${HOT_WORD_MAX_COUNT} 个`);
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
    handleDeleteImageRecord,
    handleDeleteVideoRecord,
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
} = useHistoryTab(personId, playItem, showVideoPreview);

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
    directPagingRefreshKey,
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
    materialList,
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

const materialsTabState = computed(() => ({
    activeDirectMaterialFilter: activeDirectMaterialFilter.value,
    activeMaterialFilter: activeMaterialFilter.value,
    activeMaterialTab: activeMaterialTab.value,
    avatars: avatars.value,
    batchDeleteMode: batchDeleteMode.value,
    directPagingRefreshKey: directPagingRefreshKey.value,
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
    materialLoading: materialLoading.value,
    materialPublishModes,
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
    addAvatar: handleAddAvatar,
    addVoice: handleAddVoice,
    cancelBatchDelete: handleCancelBatchDelete,
    confirmBatchDelete: handleConfirmBatchDelete,
    deleteFailedSlices: handleDeleteFailedSlices,
    directMaterialFilter: handleDirectMaterialFilter,
    directPublish: handleOpenDirectPublish,
    directPublishVisible: handleSetDirectPublishVisible,
    materialFilter: handleMaterialFilter,
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
    toggleBatchDelete: handleToggleBatchDelete,
    toggleMaterialSelected: handleToggleMaterialSelected,
    toggleSelectAll: handleToggleSelectAll,
    upload: handleOpenComposeUpload,
    uploadMusic: handleOpenMusicUpload,
    switchCopyTab: handleSwitchCopyTab,
    switchCopyDriveType: handleSwitchCopyDriveType,
    addCopy: handleOpenAddCopy,
    editCopy: handleOpenEditCopy,
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
    copyDeviceCode: handleCopyDeviceCode,
    deviceSetting: handleDeviceSetting,
    phoneManagement: handlePhoneManagement,
    selectDevice: handleSelectDevice,
    unbind: handleUnbindDevice,
};

const historyTabState = computed(() => ({
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
    deleteImageRecord: handleDeleteImageRecord,
    deleteRecord: handleDeleteVideoRecord,
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
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    await loadActiveTabData();
};

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
            await Promise.allSettled([getDetail(), fetchViralList()]);
            loadedState.hot = true;
        } else {
            await getDetail();
        }
    } finally {
        loading.value = false;
        initialized.value = true;
    }
    // 非默认 Tab 首次进入：页面骨架结束后在内容区展示 Tab loading
    if (activeTab.value !== PageTabEnum.HOT) {
        await loadActiveTabData();
    }
};

// 复用视频 AI 文案页：生成完成后通过 eventBus 回传，批量写入文案库
const { on: onEventBus } = useEventBusManager();

onShow(() => {
    if (!initialized.value || !personId.value) return;
    getDetail();
    fetchViralList();
});

onLoad((options: any) => {
    personId.value = options.id ?? "";
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

.tab-first-loading {
    @apply flex flex-col items-center justify-center gap-[16rpx] py-[160rpx];
}

.tab-first-loading-text {
    @apply text-[24rpx] text-[#9CA3AF];
}

// 行业方向 / 内容运营标签：多 tag 自动换行，长文本自动截断
.persona-tag {
    @apply text-[22rpx] px-[16rpx] py-[4rpx] rounded-full line-clamp-1;
    max-width: 240rpx;
}

.persona-tag--primary {
    @apply text-primary bg-[#EBF2FF];
}

.persona-tag--accent {
    @apply text-[#D97706] bg-[#FFF7E6];
}

.persona-action-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
}

.persona-action-card {
    position: relative;
    min-width: 0;
    height: 142rpx;
    border-radius: 24rpx;
    background: #f8faff;
    border: 2rpx solid #eef2f7;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.persona-action-icon {
    width: 52rpx;
    height: 52rpx;
    border-radius: 16rpx;
    background: #ebf2ff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.persona-action-title {
    max-width: 100%;
    margin-top: 10rpx;
    padding: 0 10rpx;
    color: #374151;
    font-size: 24rpx;
    line-height: 32rpx;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.persona-action-desc {
    max-width: 100%;
    margin-top: 2rpx;
    padding: 0 10rpx;
    color: #9ca3af;
    font-size: 20rpx;
    line-height: 28rpx;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.persona-action-new {
    position: absolute;
    top: 10rpx;
    right: 10rpx;
    padding: 2rpx 8rpx;
    border-radius: 8rpx;
    background: #ff8c00;
    color: #ffffff;
    font-size: 16rpx;
    line-height: 22rpx;
    font-weight: 700;
}
</style>
