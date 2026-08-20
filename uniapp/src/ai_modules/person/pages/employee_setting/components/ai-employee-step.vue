<template>
    <view v-if="props.loading" class="space-y-[24rpx] pb-[20rpx] animate-pulse">
        <view v-if="!props.hideOverview" class="employee-overview skeleton-overview">
            <view class="flex items-center gap-[20rpx] min-w-0">
                <view class="w-[72rpx] h-[72rpx] rounded-[24rpx] bg-[#E8F0FF]"></view>
                <view class="flex-1 min-w-0">
                    <view class="w-[220rpx] h-[32rpx] rounded-full bg-[#EEF3FA] mb-[14rpx]"></view>
                    <view class="w-[320rpx] h-[24rpx] rounded-full bg-[#F3F6FA]"></view>
                </view>
            </view>
            <view class="w-[138rpx] h-[52rpx] rounded-full bg-[#EEF3FA]"></view>
        </view>

        <view class="emp-list">
            <view v-for="n in 6" :key="n" class="emp-row">
                <view class="flex items-center gap-[24rpx] px-[30rpx] py-[32rpx]">
                    <view class="w-[88rpx] h-[88rpx] rounded-[26rpx] bg-[#EEF3FA]"></view>
                    <view class="flex-1 min-w-0">
                        <view class="w-[210rpx] h-[30rpx] rounded-full bg-[#EEF3FA] mb-[16rpx]"></view>
                        <view class="w-full max-w-[360rpx] h-[24rpx] rounded-full bg-[#F3F6FA]"></view>
                    </view>
                    <view class="w-[72rpx] h-[44rpx] rounded-full bg-[#EEF3FA]"></view>
                </view>
                <view class="skeleton-row-foot">
                    <view class="flex items-center gap-[12rpx]">
                        <view class="w-[120rpx] h-[40rpx] rounded-[16rpx] bg-white"></view>
                        <view class="w-[120rpx] h-[40rpx] rounded-[16rpx] bg-white"></view>
                    </view>
                    <view class="w-[92rpx] h-[34rpx] rounded-full bg-[#E8F0FF]"></view>
                </view>
            </view>
        </view>
    </view>

    <view v-else class="space-y-[24rpx] pb-[20rpx]">
        <view v-if="!props.hideOverview" class="employee-overview">
            <view class="flex items-center gap-[20rpx] min-w-0">
                <view class="overview-icon">
                    <image :src="CpuIcon" mode="aspectFit" class="w-[30rpx] h-[30rpx]" />
                </view>
                <view class="min-w-0">
                    <text class="block text-[32rpx] font-extrabold text-[#1D2129] leading-[1.2]"> AI 员工团队 </text>
                    <text class="block text-[22rpx] text-[#86909C] mt-[6rpx] line-clamp-1">
                        轻点员工卡片配置执行细节
                    </text>
                </view>
            </view>
            <view class="overview-counts">
                <view class="overview-count-item">
                    <view class="status-dot"></view>
                    <text class="font-bold text-[#1D2129]">{{ runningCount }}</text>
                    <text>运行中</text>
                </view>
                <text class="text-[#E5E7EB]">·</text>
                <view class="overview-count-item">
                    <view class="status-dot off"></view>
                    <text class="font-bold text-[#1D2129]">{{ pausedCount }}</text>
                    <text>已暂停</text>
                </view>
            </view>
        </view>

        <view class="emp-list">
            <template v-for="item in employeeDisplayRows" :key="item.key">
                <view v-if="item.type === 'group'" class="emp-group-title">
                    <view class="emp-group-bar"></view>
                    <text>{{ item.title }}</text>
                    <text class="emp-group-count">{{ item.count }} 个员工</text>
                </view>

                <view v-else class="emp-row" :class="{ paused: !employeeStatus[item.row.key] }">
                    <view class="emp-row-hd" @click="handleOpenEmployee(item.row.key)">
                        <view
                            class="emp-row-icon"
                            :style="{
                                background: employeeStatus[item.row.key] ? item.row.iconBg : '#F3F4F6',
                            }">
                            <image :src="item.row.icon" mode="aspectFit" class="w-[36rpx] h-[36rpx]" />
                            <view class="emp-status-dot" :class="{ off: !employeeStatus[item.row.key] }"></view>
                        </view>
                        <view class="emp-row-info">
                            <view class="flex items-center gap-[12rpx] min-w-0">
                                <text class="block emp-row-name line-clamp-1">{{ item.row.name }}</text>
                                <text class="emp-badge" :class="employeeStatus[item.row.key] ? 'running' : 'paused'">
                                    {{ employeeStatus[item.row.key] ? "运行中" : "已暂停" }}
                                </text>
                            </view>
                            <text class="block emp-row-desc">{{ item.row.desc }}</text>
                        </view>
                        <view v-if="!item.row.hideStatusSwitch" class="shrink-0" @click.stop>
                            <u-switch v-model="employeeStatus[item.row.key]" :size="34" inactive-color="#E5E7EB" />
                        </view>
                    </view>

                    <view class="emp-row-foot" @click="handleOpenEmployee(item.row.key)">
                        <view class="emp-foot-tags">
                            <text v-for="tag in getEmployeePreviewTags(item.row)" :key="tag" class="emp-foot-tag">
                                {{ tag }}
                            </text>
                            <text v-if="getEmployeePreviewMore(item.row)" class="emp-foot-more">
                                +{{ getEmployeePreviewMore(item.row) }}个
                            </text>
                        </view>
                        <view class="emp-foot-cfg">
                            <text>配置</text>
                            <u-icon name="arrow-right" color="#2F73F6" size="22"></u-icon>
                        </view>
                    </view>
                </view>
            </template>
        </view>
    </view>

    <employee-sheet-popup
        v-if="activeEmployeeRow"
        v-model="employeeSheetVisible"
        :title="activeEmployeeRow.name"
        :subtitle="activeEmployeeRow.desc"
        :icon="activeEmployeeRow.icon"
        :icon-bg="activeEmployeeRow.iconBg"
        :footer-hidden="showKeywordEditPopup || showChooseAgent || showRuleSettingPop"
        @close="handleCloseEmployeeSheet(activeEmployeeRow.key)"
        @save="handleSaveEmployee(activeEmployeeRow)">
        <template v-if="activeEmployeeRow.key === EmployeeKey.TRENDING">
            <TrendingPanel
                :keywords="trendingKeywords"
                :loading="trendingKeywordLoading"
                v-model:tracking-mode="trendingTrackingMode"
                v-model:duration="trendingDuration"
                v-model:publish-day="trendingPublishDay"
                v-model:tracking-account-config="trendingAccountConfig"
                @recommend="handleRecommendTrendingKeywords"
                @edit-keyword="(index) => handleOpenTrendingKeywordPopup(index ?? -1)"
                @remove-keyword="(word) => handleRemoveKeyword(trendingKeywords, word)" />
        </template>

        <template v-else-if="activeEmployeeRow.key === EmployeeKey.OPERATOR">
            <OperatorPanel
                :platform-configs="publishPlatformConfigs"
                v-model:active-platform="publishActivePlatform"
                v-model:cart-enabled="publishCartEnabled"
                v-model:store-enabled="publishStoreLocationEnabled"
                v-model:store-text="publishStoreLocation" />
        </template>

        <template v-else-if="activeEmployeeRow.key === EmployeeKey.CS">
            <CsPanel
                :cs-social="csSocial"
                :cs-social-platforms="csSocialPlatforms"
                :cs-social-current="csSocialCurrent"
                :cs-wechat="csWechat"
                :cs-moments="csMoments"
                :cs-shutoff-comment="csShutoffComment"
                :cs-shutoff-msg="csShutoffMsg"
                v-model:cs-shutoff-open="csShutoffOpen"
                @open-agent="openCsAgent"
                @add-script="csAddScript"
                @open-script-editor="csOpenScriptEditor"
                @remove-script="csRemoveScript"
                @move-script="csMoveScript" />
        </template>

        <template v-else-if="activeEmployeeRow.key === EmployeeKey.LEADS">
            <LeadsPanel />
        </template>

        <template v-else>
            <PrivatePanel />
        </template>
    </employee-sheet-popup>
    <keyword-edit-popup
        v-model="showKeywordEditPopup"
        :title="keywordEditTitle"
        :label="keywordEditLabel"
        :value="keywordEditValue"
        :placeholder="keywordEditPlaceholder"
        :tip="keywordEditTip"
        :maxlength="keywordEditMaxlength"
        @confirm="handleSaveKeywordItem" />
    <choose-agent-v2
        ref="chooseAgentRef"
        v-model="showChooseAgent"
        :z-index="89999"
        @confirm="handleChooseAgentConfirm" />
    <rule-setting-pop
        ref="ruleSettingPopRef"
        v-model="showRuleSettingPop"
        :is-material-empty="isMaterialEmpty"
        :person-id="props.personId"
        @confirm="handleRulesConfirm" />
</template>

<script setup lang="ts">
import {
    getInteractionConfig,
    getMaterialLibraryList,
    getPersonTrackingWords,
    getPublishConfigDetail,
    getTrafficConfig,
    updateInteractionConfig,
    updatePersonOption,
    updatePersonTrackingWords,
    updatePublishConfig,
    updateTrafficConfig,
} from "@/api/person";
import {
    PersonTypeEnum,
    PUBLISH_PLATFORM_LIST,
    PublishPlatformEnum,
    PublishMediaTypeEnum,
    PublishCopySourceEnum,
    PublishGenerateModeEnum,
    PublishBasisEnum,
    getPublishPlatformDefault,
    type PlatformPublishConfig,
} from "@/ai_modules/person/enums";
import ChooseAgentV2 from "@/ai_modules/person/components/choose-agent-v2/choose-agent-v2.vue";
import { useCustomerServiceConfig } from "../hooks/useCustomerServiceConfig";
import {
    useLeadFeatures,
    LeadFeaturesKey,
    type Gender,
    type DualAction,
    type KeywordEditOptions,
} from "../hooks/useLeadFeatures";
import CpuIcon from "@/ai_modules/person/static/icons/employee/cpu-white.svg";
import FlameIcon from "@/ai_modules/person/static/icons/employee/flame-blue.svg";
import MegaphoneIcon from "@/ai_modules/person/static/icons/employee/megaphone-blue.svg";
import MessageBlueIcon from "@/ai_modules/person/static/icons/employee/message-blue.svg";

import TargetIcon from "@/ai_modules/person/static/icons/employee/target-blue.svg";
import UsersIcon from "@/ai_modules/person/static/icons/employee/users-blue.svg";
import VideoBlueIcon from "@/ai_modules/person/static/icons/employee/video-blue.svg";
import KeywordEditPopup from "./keyword-edit-popup.vue";
import RuleSettingPop from "../../material_library/components/rule-setting-pop.vue";
import TrendingPanel from "./ai-employee-step/panels/trending-panel.vue";
import OperatorPanel from "./ai-employee-step/panels/operator-panel.vue";
import CsPanel from "./ai-employee-step/panels/cs-panel.vue";
import LeadsPanel from "./ai-employee-step/panels/leads-panel.vue";
import PrivatePanel from "./ai-employee-step/panels/private-panel.vue";
import EmployeeSheetPopup from "./popups/employee-sheet-popup.vue";
import { usePrivateChannels, PrivateChannelsKey } from "../hooks/usePrivateChannels";

enum EmployeeKey {
    TRENDING = "trending",
    EDITOR = "editor",
    OPERATOR = "operator",
    CS = "cs",
    LEADS = "leads",
    PRIVATE = "private",
}
type TrendingTrackingMode = 1 | 2;
type TrendingDuration = 0 | 1 | 2 | 3;
type TrendingPublishDay = 0 | 1 | 2 | 3;

interface TrackingAccountItem {
    account: string;
    homepage_url: string;
}

type TrackingAccountConfig = Record<string, TrackingAccountItem>;

type PersonDetailFormKey = "individual" | "enterprise" | "local";

interface RulesState {
    synthTypes: string[];
    materialSource: string;
    copySource: string;
    copyStyle: string;
    coverSource: string;
    coverImage: string;
}

interface EmployeeRow {
    key: EmployeeKey;
    name: string;
    desc: string;
    icon: string;
    iconBg: string;
    previewTags: string[];
    previewMore?: number;
    /** 隐藏右上角运行状态开关（如热点追踪 / 视频剪辑 / 内容发布默认常开，不暴露开关） */
    hideStatusSwitch?: boolean;
}

type EmployeeDisplayItem =
    | {
          type: "group";
          key: string;
          title: string;
          count: number;
      }
    | {
          type: "row";
          key: EmployeeKey;
          row: EmployeeRow;
      };

interface InteractionPreservedConfig {
    isLike: number;
    isComment: number;
    commentMethod: number;
    commentSpeech: string[];
    commentRobotPrompt: string;
}

const props = defineProps<{
    personId: string;
    configStatus: Record<string, any>;
    loading?: boolean;
    personaType?: PersonTypeEnum | number;
    trackingWords?: any;
    globalOption?: Record<string, any> | null;
    hideOverview?: boolean;
    grouped?: boolean;
}>();
const emit = defineEmits<{
    (event: "popup-visible-change", value: boolean): void;
}>();

const employeeRows: EmployeeRow[] = [
    {
        key: EmployeeKey.TRENDING,
        name: "热点追踪",
        desc: "追踪全网爆款热点内容",
        icon: FlameIcon,
        iconBg: "#EFF6FF",
        previewTags: ["护肤推荐", "敏感肌", "换季护肤"],
        previewMore: 5,
        hideStatusSwitch: true,
    },
    {
        key: EmployeeKey.EDITOR,
        name: "视频剪辑",
        desc: "自动生成短视频内容",
        icon: VideoBlueIcon,
        iconBg: "#EFF6FF",
        previewTags: ["数字人口播", "新闻体混剪", "纯素材混剪"],
        hideStatusSwitch: true,
    },
    {
        key: EmployeeKey.OPERATOR,
        name: "内容发布",
        desc: "AI 自动生成标题与正文",
        icon: MegaphoneIcon,
        iconBg: "#EFF6FF",
        previewTags: ["自动生成", "根据人设", "挂载购物车"],
        hideStatusSwitch: true,
    },
    {
        key: EmployeeKey.CS,
        name: "智能客服",
        desc: "24小时自动回复私信评论",
        icon: MessageBlueIcon,
        iconBg: "#EFF6FF",
        previewTags: ["抖音", "智能体回复", "预设话术"],
    },
    {
        key: EmployeeKey.LEADS,
        name: "自动获客",
        desc: "主动触达潜在目标客户",
        icon: TargetIcon,
        iconBg: "#EFF6FF",
        previewTags: ["视频号获客", "截流设置", "团购截流"],
        previewMore: 1,
    },
    {
        key: EmployeeKey.PRIVATE,
        name: "私域运营",
        desc: "维护微信私域客户关系",
        icon: UsersIcon,
        iconBg: "#EFF6FF",
        previewTags: ["加好友", "自动加群", "朋友圈互动"],
    },
];

const employeeStatus = reactive<Record<EmployeeKey, boolean>>({
    trending: true,
    editor: true,
    operator: true,
    cs: true,
    leads: true,
    private: false,
});
const openEmployee = ref<EmployeeKey | "">("");
const employeeSheetVisible = ref(false);
const activeEmployeeRow = computed(() => employeeRows.find((row) => row.key === openEmployee.value));
const runningCount = computed(() => Object.values(employeeStatus).filter(Boolean).length);
const pausedCount = computed(() => Object.values(employeeStatus).filter((item) => !item).length);
const employeeDisplayRows = computed<EmployeeDisplayItem[]>(() => {
    if (!props.grouped) {
        return employeeRows.map((row) => ({
            type: "row",
            key: row.key,
            row,
        }));
    }

    const contentRows = employeeRows.slice(0, 3);
    const growthRows = employeeRows.slice(3);
    return [
        {
            type: "group",
            key: "content-group",
            title: "内容生产",
            count: contentRows.length,
        },
        ...contentRows.map((row) => ({
            type: "row" as const,
            key: row.key,
            row,
        })),
        {
            type: "group",
            key: "growth-group",
            title: "获客运营",
            count: growthRows.length,
        },
        ...growthRows.map((row) => ({
            type: "row" as const,
            key: row.key,
            row,
        })),
    ];
});

const PERSON_DETAIL_FORM_KEY: Record<PersonTypeEnum, PersonDetailFormKey> = {
    [PersonTypeEnum.PERSONAL_IP]: "individual",
    [PersonTypeEnum.BUSINESS_SERVICE]: "enterprise",
    [PersonTypeEnum.LOCAL_BUSINESS]: "local",
};

const currentPersonaType = ref<PersonTypeEnum | 0>(0);
const trendingKeywords = ref<string[]>([]);
const trendingOriginKeywords = ref<string[]>([]);
const trendingKeywordLoading = ref(false);
const trendingSaving = ref(false);
const trendingTrackingMode = ref<TrendingTrackingMode>(1);
const trendingDuration = ref<TrendingDuration>(0);
const trendingPublishDay = ref<TrendingPublishDay>(0);
const getDefaultTrackingAccountConfig = (): TrackingAccountConfig => ({
    "3": { account: "", homepage_url: "" },
    "4": { account: "", homepage_url: "" },
});
const trendingAccountConfig = ref<TrackingAccountConfig>(getDefaultTrackingAccountConfig());
const showKeywordEditPopup = ref(false);
const editingKeywordIndex = ref(-1);
const keywordEditValue = ref("");
const keywordEditTitle = ref("编辑词条");
const keywordEditLabel = ref("词条内容");
const keywordEditPlaceholder = ref("请输入词条内容");
const keywordEditTip = ref("保存后，AI 会按这个词条执行对应配置。");
const keywordEditMaxlength = ref(20);
const keywordEditMaxItems = ref(0);
const keywordEditAllowMultiple = ref(false);
let keywordEditTarget: Ref<string[]> | string[] | null = null;
const KEYWORD_BATCH_SEPARATOR_REGEXP = /[;；]/;
const getKeywordEditList = (): string[] | null => {
    if (!keywordEditTarget) return null;
    return Array.isArray(keywordEditTarget) ? keywordEditTarget : keywordEditTarget.value;
};
const normalizeKeywordItems = (value: string): string[] => {
    const list = keywordEditAllowMultiple.value ? value.split(KEYWORD_BATCH_SEPARATOR_REGEXP) : [value];
    return list.map((item) => item.trim()).filter((item, index, array) => item && array.indexOf(item) === index);
};
const showRuleSettingPop = ref(false);
const ruleSummary = ref("数字人口播 · AI+素材库 · 找爆款仿写");
const currentRulesState = ref<RulesState | null>(null);
const ruleSettingPopRef = ref<InstanceType<typeof RuleSettingPop>>();
const isMaterialEmpty = ref(false);

const createPublishPlatformConfigs = (): Record<number, PlatformPublishConfig> =>
    PUBLISH_PLATFORM_LIST.reduce<Record<number, PlatformPublishConfig>>((acc, { platform }) => {
        acc[platform] = getPublishPlatformDefault(platform);
        return acc;
    }, {});

const publishPlatformConfigs = reactive<Record<number, PlatformPublishConfig>>(createPublishPlatformConfigs());
const publishActivePlatform = ref<PublishPlatformEnum>(PublishPlatformEnum.XIAOHONGSHU);
const publishCartEnabled = ref(false);
const publishStoreLocationEnabled = ref(false);
const publishStoreLocation = ref("");

const publishConfigLoading = ref(false);
const publishConfigSaving = ref(false);
const publishGoodsName = ref("");
// 保留后端 content_publish_config 顶层字段（version 2 下由 platform_configs 驱动，顶层原样回传）
const publishConfigBase = ref<Record<string, any>>({});

// ===== 智能客服配置（复用 agent_config 数据模型）=====
const showChooseAgent = ref(false);
const chooseAgentRef = shallowRef<InstanceType<typeof ChooseAgentV2>>();
const csShutoffOpen = ref<"" | "comment" | "dm">("");
const employeePopupVisible = computed(() =>
    Boolean(
        employeeSheetVisible.value || showKeywordEditPopup.value || showRuleSettingPop.value || showChooseAgent.value,
    ),
);

watch(
    employeePopupVisible,
    (visible) => {
        emit("popup-visible-change", visible);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    emit("popup-visible-change", false);
});

const closePopups = (): void => {
    employeeSheetVisible.value = false;
    openEmployee.value = "";
    showKeywordEditPopup.value = false;
    showRuleSettingPop.value = false;
    showChooseAgent.value = false;
};

defineExpose({ closePopups });

const {
    social: csSocial,
    socialCurrent: csSocialCurrent,
    socialPlatforms: csSocialPlatforms,
    wechat: csWechat,
    moments: csMoments,
    shutoffComment: csShutoffComment,
    shutoffMsg: csShutoffMsg,
    beginPickAgent: csBeginPickAgent,
    applyAgent: csApplyAgent,
    addScript: csAddScript,
    removeScript: csRemoveScript,
    moveScript: csMoveScript,
    loadConfig: loadCsConfig,
    saveConfig: saveCsConfig,
} = useCustomerServiceConfig(toRef(props, "personId"));

// 打开选择智能体：记录目标块并回显当前选中项
const openCsAgent = (target: "social-dm" | "social-comment" | "wechat" | "moments") => {
    const presetId = csBeginPickAgent(target);
    showChooseAgent.value = true;
    nextTick(() => chooseAgentRef.value?.setChooseLists(presetId ? [{ id: presetId }] : []));
};

const csOpenScriptEditor = (scripts: string[], index: number, blockLabel: string, maxlength: number) => {
    if (index < 0) return;
    openKeywordEditPopup(scripts, index, {
        title: `编辑${blockLabel}话术`,
        label: "话术内容",
        placeholder: "请输入话术内容",
        tip: `保存后 AI 将按这条话术作为回复内容（最多 ${maxlength} 字）。`,
        maxlength,
    });
};

const employeeConfigLoading = ref(false);
const employeeConfigSaving = ref(false);

const leadFeatures = useLeadFeatures({
    openKeywordEditPopup: (list, index, options) => openKeywordEditPopup(list, index, options),
});
provide(LeadFeaturesKey, leadFeatures);

const {
    leadFeatureStatus,
    openLeadFeature,
    showLeadRiskPanel,
    wxvKeywords,
    wxvMoreWords,
    wxvLimit,
    jlSearchWords,
    jlSearchMoreWords,
    jlCommentWords,
    jlCommentMoreWords,
    jlLimit,
    tgForm,
    tgKeywords,
    tgActions,
    tgExcludeWords,
    tcForm,
    tcActions,
    tcExcludeWords,
    riskConfig,
    removeKeyword: handleRemoveKeyword,
} = leadFeatures;

const privateChannels = usePrivateChannels({
    openKeywordEditPopup: (list, index, options) => openKeywordEditPopup(list, index, options),
    getPersonId: () => props.personId,
});
provide(PrivateChannelsKey, privateChannels);

const {
    privatePanelStatus,
    openPrivatePanel,
    privateClueCount,
    friendApplyText,
    saleWechatList,
    groupNameTemplate,
    groupWelcomeEnabled,
    groupWelcomeText,
    carryHistoryEnabled,
    groupTriggerMode,
    groupKeywords,
    circleCount,
} = privateChannels;

const GLOBAL_OPTION_TOP_KEY_MAP = {
    trending: "hot_words",
    editor: "video_clip",
    operator: "content_publish",
    cs: "customer_service",
} as const;
const GLOBAL_OPTION_LEAD_KEY_MAP = {
    wxv: "sph_clues",
    jl: "video_shutoff",
    tg: "group_clues",
    tc: "city_clues",
} as const;
const GLOBAL_OPTION_PRIVATE_KEY_MAP = {
    add: "add_friend",
    group: "auto_add_group",
    moments: "circle_config",
} as const;

let hydratingGlobalOption = false;

const toSwitchBool = (val: any, fallback = false): boolean => {
    if (val === 1 || val === "1" || val === true) return true;
    if (val === 0 || val === "0" || val === false) return false;
    return fallback;
};

const applyGlobalOption = (data: Record<string, any> | null | undefined): void => {
    if (!data || typeof data !== "object") return;
    hydratingGlobalOption = true;
    try {
        (Object.keys(GLOBAL_OPTION_TOP_KEY_MAP) as Array<keyof typeof GLOBAL_OPTION_TOP_KEY_MAP>).forEach((uiKey) => {
            const apiKey = GLOBAL_OPTION_TOP_KEY_MAP[uiKey];
            if (data[apiKey] !== undefined) {
                employeeStatus[uiKey] = toSwitchBool(data[apiKey], employeeStatus[uiKey]);
            }
        });

        const autoClues = data.auto_clues || {};
        if (autoClues.status !== undefined) {
            employeeStatus.leads = toSwitchBool(autoClues.status, employeeStatus.leads);
        }
        const autoCluesOptions = autoClues.options || {};
        (Object.keys(GLOBAL_OPTION_LEAD_KEY_MAP) as Array<keyof typeof GLOBAL_OPTION_LEAD_KEY_MAP>).forEach((uiKey) => {
            const apiKey = GLOBAL_OPTION_LEAD_KEY_MAP[uiKey];
            if (autoCluesOptions[apiKey] !== undefined) {
                leadFeatureStatus[uiKey] = toSwitchBool(autoCluesOptions[apiKey], leadFeatureStatus[uiKey]);
            }
        });

        const privateOp = data.private_operation || {};
        if (privateOp.status !== undefined) {
            employeeStatus.private = toSwitchBool(privateOp.status, employeeStatus.private);
        }
        const privateOptions = privateOp.options || {};
        (Object.keys(GLOBAL_OPTION_PRIVATE_KEY_MAP) as Array<keyof typeof GLOBAL_OPTION_PRIVATE_KEY_MAP>).forEach(
            (uiKey) => {
                const apiKey = GLOBAL_OPTION_PRIVATE_KEY_MAP[uiKey];
                if (privateOptions[apiKey] !== undefined) {
                    privatePanelStatus[uiKey] = toSwitchBool(privateOptions[apiKey], privatePanelStatus[uiKey]);
                }
            },
        );
    } finally {
        // 等开关响应式更新完再放开 watch
        nextTick(() => {
            hydratingGlobalOption = false;
        });
    }
};

const buildGlobalOptionPayload = (): Record<string, any> => ({
    hot_words: employeeStatus.trending ? 1 : 0,
    video_clip: employeeStatus.editor ? 1 : 0,
    content_publish: employeeStatus.operator ? 1 : 0,
    customer_service: employeeStatus.cs ? 1 : 0,
    auto_clues: {
        status: employeeStatus.leads ? 1 : 0,
        options: {
            sph_clues: leadFeatureStatus.wxv ? 1 : 0,
            video_shutoff: leadFeatureStatus.jl ? 1 : 0,
            group_clues: leadFeatureStatus.tg ? 1 : 0,
            city_clues: leadFeatureStatus.tc ? 1 : 0,
        },
    },
    private_operation: {
        status: employeeStatus.private ? 1 : 0,
        options: {
            add_friend: privatePanelStatus.add ? 1 : 0,
            auto_add_group: privatePanelStatus.group ? 1 : 0,
            circle_config: privatePanelStatus.moments ? 1 : 0,
        },
    },
});

const globalOptionSaving = ref(false);
let globalOptionSaveTimer: ReturnType<typeof setTimeout> | null = null;

// 频繁切换合并为一次请求，避免重复打接口
const persistGlobalOption = async (): Promise<void> => {
    if (!props.personId) return;
    if (globalOptionSaving.value) return;
    globalOptionSaving.value = true;
    try {
        await updatePersonOption({
            id: props.personId,
            global_option: buildGlobalOptionPayload(),
        });
    } catch (error) {
        uni.$u.toast?.(error || "开关保存失败，请稍后重试");
    } finally {
        globalOptionSaving.value = false;
    }
};

const scheduleGlobalOptionSave = (): void => {
    if (hydratingGlobalOption) return;
    if (!props.personId) return;
    if (globalOptionSaveTimer) clearTimeout(globalOptionSaveTimer);
    globalOptionSaveTimer = setTimeout(() => {
        globalOptionSaveTimer = null;
        persistGlobalOption();
    }, 300);
};

watch(employeeStatus, scheduleGlobalOptionSave, { deep: true });
watch(leadFeatureStatus, scheduleGlobalOptionSave, { deep: true });
watch(privatePanelStatus, scheduleGlobalOptionSave, { deep: true });

watch(
    () => props.globalOption,
    (val) => applyGlobalOption(val),
    { immediate: true, deep: true },
);
// =================================================

// 私域板块所有 state 已迁至 hooks/usePrivateChannels.ts
const interactionConfigLoading = ref(false);
const interactionConfigSaving = ref(false);
const interactionPreservedConfig = reactive<InteractionPreservedConfig>({
    isLike: 1,
    isComment: 1,
    commentMethod: 1,
    commentSpeech: [],
    commentRobotPrompt: "",
});

const normalizeKeywordItem = (item: any): string => {
    if (item === null || item === undefined) return "";
    if (typeof item === "string" || typeof item === "number") return String(item).trim();
    return String(item.word || item.keyword || item.name || item.title || "").trim();
};

const normalizeKeywordList = (value: any): string[] => {
    let list: any[] = [];
    if (Array.isArray(value)) {
        list = value;
    } else if (typeof value === "string") {
        const text = value.trim();
        if (!text) return [];
        try {
            const parsed = JSON.parse(text);
            list = Array.isArray(parsed) ? parsed : text.split(/[，,、；;\n]/);
        } catch {
            list = text.split(/[，,、；;\n]/);
        }
    }

    const keywords = list.map(normalizeKeywordItem).filter(Boolean);
    return Array.from(new Set(keywords));
};

// 跟踪账号发布入口已隐藏，统一固定为自动找爆款
const normalizeTrendingTrackingMode = (_value?: unknown): TrendingTrackingMode => 1;

const normalizeTrendingDuration = (value: unknown): TrendingDuration => {
    const num = Number(value);
    return num === 1 || num === 2 || num === 3 ? num : 0;
};

const normalizeTrendingPublishDay = (value: unknown): TrendingPublishDay => {
    const num = Number(value);
    return num === 1 || num === 2 || num === 3 ? num : 0;
};

const normalizeTrackingAccountConfig = (value: any): TrackingAccountConfig => {
    const defaults = getDefaultTrackingAccountConfig();
    if (!value || typeof value !== "object" || Array.isArray(value)) return defaults;
    Object.keys(defaults).forEach((key) => {
        const item = value[key] || {};
        defaults[key] = {
            account: String(item.account ?? "").trim(),
            homepage_url: String(item.homepage_url ?? "").trim(),
        };
    });
    return defaults;
};

const getHotWordsByPersonaType = (data: Record<string, any>): string[] => {
    const personaType = (Number(data.persona_type) || currentPersonaType.value) as PersonTypeEnum;
    const formKey = PERSON_DETAIL_FORM_KEY[personaType];
    const typedDetail = formKey ? data[formKey] || {} : {};
    const hotWords = typedDetail.hot_words ?? data.hot_words;
    if (hotWords && typeof hotWords === "object" && !Array.isArray(hotWords)) {
        const directWords =
            hotWords.hot_words ?? hotWords.words ?? hotWords.keywords ?? hotWords.list ?? hotWords.lists;
        if (directWords !== undefined) return normalizeKeywordList(directWords);
        return normalizeKeywordList(hotWords[personaType] ?? hotWords[String(personaType)] ?? hotWords[formKey]);
    }
    return normalizeKeywordList(hotWords);
};

const applyTrackingWords = (words: any, personaType = props.personaType): void => {
    currentPersonaType.value = (Number(personaType) || currentPersonaType.value) as PersonTypeEnum | 0;
    // duration / publish_day / tracking_* 取 detail 最外层，不从子表单合并覆盖
    const outer = words && typeof words === "object" && !Array.isArray(words) ? words : {};
    trendingTrackingMode.value = normalizeTrendingTrackingMode(outer.tracking_mode);
    trendingDuration.value = normalizeTrendingDuration(outer.duration);
    trendingPublishDay.value = normalizeTrendingPublishDay(outer.publish_day);
    trendingAccountConfig.value = normalizeTrackingAccountConfig(outer.tracking_account_config);
    const keywords = Array.isArray(words)
        ? normalizeKeywordList(words)
        : getHotWordsByPersonaType({
              persona_type: currentPersonaType.value,
              ...outer,
          });
    trendingKeywords.value = [...keywords];
    trendingOriginKeywords.value = [...keywords];
};

const getTrackingWordsFromResponse = (data: any): string[] => {
    if (Array.isArray(data) || typeof data === "string") return normalizeKeywordList(data);
    if (!data || typeof data !== "object") return [];

    const directWords = data.hot_words ?? data.hot_words ?? data.words ?? data.keywords ?? data.list ?? data.lists;
    if (directWords !== undefined) {
        return getHotWordsByPersonaType({
            persona_type: data.persona_type || currentPersonaType.value,
            hot_words: directWords,
        });
    }
    return getHotWordsByPersonaType(data);
};

const getEmployeePreviewTags = (row: EmployeeRow): string[] => {
    if (row.key === EmployeeKey.TRENDING) return trendingKeywords.value.slice(0, 3);
    if (row.key !== EmployeeKey.EDITOR) return row.previewTags;
    return ruleSummary.value
        .split(" · ")
        .map((item) => item.trim())
        .filter(Boolean)
        .slice(0, 3);
};

const getEmployeePreviewMore = (row: EmployeeRow): number => {
    if (row.key === EmployeeKey.TRENDING) return Math.max(trendingKeywords.value.length - 3, 0);
    return row.previewMore || 0;
};

const resetTrackingWordsFromProps = (showSuccessToast = false): void => {
    if (!props.personId) {
        trendingKeywords.value = [];
        trendingOriginKeywords.value = [];
        return;
    }

    applyTrackingWords(props.trackingWords);
    if (showSuccessToast) uni.$u.toast(trendingKeywords.value.length ? "已恢复初始追踪词" : "暂无追踪词");
};

const queryMaterialEmpty = async (): Promise<void> => {
    if (!props.personId) {
        isMaterialEmpty.value = false;
        return;
    }

    try {
        const { lists = [], count = 0 } = await getMaterialLibraryList({
            persona_id: props.personId,
            page_no: 1,
            page_size: 1,
            material_type: "1,2",
        });
        isMaterialEmpty.value = Number(count || lists.length) === 0;
    } catch {
        isMaterialEmpty.value = false;
    }
};

const initRuleSetting = async (): Promise<void> => {
    if (!props.personId) return;
    await nextTick();
    await Promise.allSettled([queryMaterialEmpty(), ruleSettingPopRef.value?.getDetail()]);
    ruleSummary.value = ruleSettingPopRef.value?.buildSummary() ?? ruleSummary.value;
};

const ACTION_VALUE = {
    like: 1,
    follow: 2,
    comment: 3,
    dm: 4,
} as const;

const toNumber = (value: unknown, fallback = 0): number => {
    const numberValue = Number(value);
    return Number.isFinite(numberValue) ? numberValue : fallback;
};

// 接口可能返回字符串，滑块等整数字段回显前统一格式化为 int
const toInt = (value: unknown, fallback = 0): number => Math.round(toNumber(value, fallback));

const normalizePublishDay = (value: unknown): 0 | 1 | 7 | 180 => {
    const num = Number(value);
    if (num === 1) return 1;
    if (num >= 2 && num <= 7) return 7;
    if (num >= 8 && num <= 180) return 180;
    return 0;
};

const VISIBLE_KEYWORD_COUNT = 3;

// 关键词回显：接口数组按「可见前 3 个 + 展开更多」拆进两个 ref。
// 默认示例词只在整段为空时兜底，避免真实数据不足 3 个时把示例词塞进“更多”区造成显示错乱。
const applyKeywordList = (
    raw: unknown,
    visibleRef: Ref<string[]>,
    moreRef: Ref<string[]>,
    defaults: { visible: string[]; more: string[] },
): void => {
    const words = Array.isArray(raw) ? raw.map((item) => String(item)).filter(Boolean) : [];
    if (!words.length) {
        visibleRef.value = [...defaults.visible];
        moreRef.value = [...defaults.more];
        return;
    }
    visibleRef.value = words.slice(0, VISIBLE_KEYWORD_COUNT);
    moreRef.value = words.slice(VISIBLE_KEYWORD_COUNT);
};

const getAllWords = (...lists: Ref<string[]>[]): string[] => {
    return lists.flatMap((list) => list.value).filter(Boolean);
};

const genderValueToLabel = (value: unknown): Gender => {
    if (Number(value) === 1) return "男";
    if (Number(value) === 2) return "女";
    return "不限";
};

const genderLabelToValue = (value: Gender): number => {
    if (value === "男") return 1;
    if (value === "女") return 2;
    return 0;
};

const distanceValueToLabel = (value: unknown): string => {
    const distance = toNumber(value, 0);
    if (distance === 1) return "1km内";
    if (distance === 3) return "3km内";
    if (distance === 5) return "5km内";
    if (distance === 10) return "10km内";
    if (distance > 0) return "自定义";
    return "不限";
};

const distanceLabelToValue = (): number => {
    if (tcForm.distance === "1km内") return 1;
    if (tcForm.distance === "3km内") return 3;
    if (tcForm.distance === "5km内") return 5;
    if (tcForm.distance === "10km内") return 10;
    if (tcForm.distance === "自定义") return toNumber(tcForm.customDistance, 0);
    return 0;
};

const applyActionConfig = (value: unknown, target: { like: boolean; follow: boolean; dual: DualAction }): void => {
    const actions =
        Array.isArray(value) && value.length
            ? value.map((item) => Number(item))
            : [ACTION_VALUE.like, ACTION_VALUE.follow, ACTION_VALUE.comment];
    target.like = actions.includes(ACTION_VALUE.like);
    target.follow = actions.includes(ACTION_VALUE.follow);
    target.dual = actions.includes(ACTION_VALUE.dm) ? "dm" : actions.includes(ACTION_VALUE.comment) ? "comment" : "";
};

const buildCommentUserActionPayload = (source: { dual: DualAction }): number[] => {
    if (source.dual === "comment") return [ACTION_VALUE.comment];
    if (source.dual === "dm") return [ACTION_VALUE.dm];
    return [];
};

const applyTrafficConfig = (data: Record<string, any>): void => {
    applyKeywordList(data.clue_keywords, wxvKeywords, wxvMoreWords, {
        visible: ["创业者", "企业主", "工作室"],
        more: ["老板", "合伙人", "副业", "代理", "加盟", "招募"],
    });
    wxvLimit.value = toNumber(data.clue_max_number, 10);

    applyKeywordList(data.acquire_keywords, jlSearchWords, jlSearchMoreWords, {
        visible: ["2024AI使用教程", "AI落地应用场景", "普通人怎么用AI提效"],
        more: ["AI副业", "AI赚钱", "AI工具推荐", "AI变现"],
    });

    applyKeywordList(data.intercept_keywords, jlCommentWords, jlCommentMoreWords, {
        visible: ["怎么落地", "不会用", "求教程"],
        more: ["怎么学", "在哪学", "想入门", "求链接", "怎么买", "推荐课程"],
    });
    jlLimit.value = toNumber(data.intercept_max_number, 10);

    // 团购：后端通常只回 group_buy_method，其余字段缺省时与 wxv/jl 一致回退到默认示例
    const groupBuyConfig = data.group_buy_config || {};
    const tgComment = groupBuyConfig.comment_keywords;
    const tgExclude = groupBuyConfig.filter_nickname;
    tgForm.type = groupBuyConfig.group_buy_keyword || "双人套餐";
    tgForm.execNum = String(groupBuyConfig.exec_number ?? 15);
    tgForm.commentStart = toNumber(groupBuyConfig.group_num_comment, 1);
    tgForm.watchSeconds = toNumber(groupBuyConfig.view_video_time, 10);
    tgForm.touchInterval = toNumber(groupBuyConfig.touch_interval, 10);
    tgForm.gender = genderValueToLabel(groupBuyConfig.gender);
    tgForm.ip = groupBuyConfig.filter_ip || "";
    tgForm.region = groupBuyConfig.filter_address || "";
    tgKeywords.value = Array.isArray(tgComment) && tgComment.length ? tgComment : ["怎么买", "划算吗", "多少钱"];
    tgExcludeWords.value = Array.isArray(tgExclude) && tgExclude.length ? tgExclude : ["同行", "客服"];
    applyActionConfig(groupBuyConfig.interactive_action, tgActions);
    tgActions.like = false;
    tgActions.follow = false;

    const cityConfig = data.same_city_config || {};
    tcForm.watchSeconds = toNumber(cityConfig.view_video_time, 10);
    tcForm.touchInterval = toNumber(cityConfig.touch_interval, 10);
    tcForm.distance = distanceValueToLabel(cityConfig.range);
    tcForm.customDistance = tcForm.distance === "自定义" ? String(toNumber(cityConfig.range, 0)) : "";
    tcForm.gender = genderValueToLabel(cityConfig.gender);
    tcForm.ageMin = String(cityConfig.age_range?.min ?? 18);
    tcForm.ageMax = String(cityConfig.age_range?.max ?? 60);
    tcForm.likeMin = String(cityConfig.filter_video_thumb_num ?? 0);
    tcForm.commentMax = String(cityConfig.filter_video_comment_num ?? 0);
    tcForm.fansMin = String(cityConfig.filter_comment_fans?.min ?? 0);
    tcForm.fansMax = String(cityConfig.filter_comment_fans?.max ?? 0);
    tcForm.followMin = String(cityConfig.filter_comment_follow?.min ?? 0);
    tcForm.followMax = String(cityConfig.filter_comment_follow?.max ?? 0);
    tcExcludeWords.value = cityConfig.filter_nickname || [];
    applyActionConfig(cityConfig.interactive_action, tcActions);
    // 同城：点赞/关注内置固定，UI 不展示，始终开启
    tcActions.like = true;
    tcActions.follow = true;

    riskConfig.contentPublishTime = normalizePublishDay(data.content_publish_day);
    riskConfig.commentPublishTime = Number(data.comment_publish_day) === 0 ? -1 : toInt(data.comment_publish_day, -1);
    riskConfig.messageNumber = String(toInt(data.message_number, 15));
    riskConfig.cityCommentNumber = String(toInt(data.comment_number, 15));
    riskConfig.videoMessageNumber = String(toInt(data.video_cutoff_number, 15));
    riskConfig.cityMessageNumber = String(toInt(data.city_cutoff_number, 15));
    riskConfig.grouponMessageNumber = String(toInt(data.group_cutoff_number, 15));
    riskConfig.replyNumber = Number(data.reply_number) === 0 ? 2 : 1;
};

const queryTrafficConfig = async (): Promise<void> => {
    if (!props.personId) return;
    try {
        employeeConfigLoading.value = true;
        const data = await getTrafficConfig({ persona_id: props.personId });
        applyTrafficConfig(data || {});
    } catch {
        uni.$u.toast("获客配置获取失败");
    } finally {
        employeeConfigLoading.value = false;
    }
};

const buildTrafficPayload = () => {
    return {
        persona_id: props.personId,
        acquire_keywords: getAllWords(jlSearchWords, jlSearchMoreWords),
        intercept_keywords: getAllWords(jlCommentWords, jlCommentMoreWords),
        comment_scripts: [],
        dm_scripts: [],
        message_number: riskConfig.messageNumber,
        comment_number: riskConfig.cityCommentNumber,
        reply_number: riskConfig.replyNumber === 2 ? 0 : 1,
        content_publish_day: normalizePublishDay(riskConfig.contentPublishTime),
        comment_publish_day: riskConfig.commentPublishTime === -1 ? 0 : riskConfig.commentPublishTime,
        intercept_max_number: jlLimit.value,
        intercept_keyword_used_type: 1,
        clue_keywords: getAllWords(wxvKeywords, wxvMoreWords),
        clue_max_number: wxvLimit.value,
        clue_keyword_used_type: 1,
        video_cutoff_number: riskConfig.videoMessageNumber,
        city_cutoff_number: riskConfig.cityMessageNumber,
        group_cutoff_number: riskConfig.grouponMessageNumber,
        group_buy_config: {
            group_buy_method: 2,
            group_buy_keyword: tgForm.type,
            range: 0,
            exec_number: toNumber(tgForm.execNum, 15),
            comment_keywords: tgKeywords.value,
            group_publish_day: 1,
            group_num_comment: toNumber(tgForm.commentStart, 1),
            interactive_action: buildCommentUserActionPayload(tgActions),
            group_thumb_method: 1,
            view_video_time: toNumber(tgForm.watchSeconds, 10),
            touch_interval: toNumber(tgForm.touchInterval, 10),
            gender: genderLabelToValue(tgForm.gender),
            filter_ip: tgForm.ip,
            filter_address: tgForm.region,
            filter_nickname: tgExcludeWords.value,
        },
        same_city_config: {
            // 点赞/关注内置固定，仅评论/私信由用户二选一
            interactive_action: [ACTION_VALUE.like, ACTION_VALUE.follow, ...buildCommentUserActionPayload(tcActions)],
            view_video_time: toNumber(tcForm.watchSeconds, 10),
            touch_interval: toNumber(tcForm.touchInterval, 10),
            range: distanceLabelToValue(),
            gender: genderLabelToValue(tcForm.gender),
            age_range: {
                min: toNumber(tcForm.ageMin, 0),
                max: toNumber(tcForm.ageMax, 0),
            },
            filter_video_thumb_num: toNumber(tcForm.likeMin, 0),
            filter_video_comment_num: toNumber(tcForm.commentMax, 0),
            filter_comment_fans: {
                min: toNumber(tcForm.fansMin, 0),
                max: toNumber(tcForm.fansMax, 0),
            },
            filter_comment_follow: {
                min: toNumber(tcForm.followMin, 0),
                max: toNumber(tcForm.followMax, 0),
            },
            filter_nickname: tcExcludeWords.value,
        },
    };
};

const validateTrafficConfig = (): boolean => {
    if (leadFeatureStatus.tg && !tgActions.dual) {
        if (!openLeadFeature.value.includes("tg")) openLeadFeature.value.push("tg");
        uni.$u.toast("请至少选择一个执行动作");
        return false;
    }
    if (leadFeatureStatus.tc && !tcActions.dual) {
        if (!openLeadFeature.value.includes("tc")) openLeadFeature.value.push("tc");
        uni.$u.toast("请至少选择一个执行动作");
        return false;
    }
    if (leadFeatureStatus.tc && tcForm.distance === "自定义" && !tcForm.customDistance) {
        if (!openLeadFeature.value.includes("tc")) openLeadFeature.value.push("tc");
        uni.$u.toast("请输入自定义距离");
        return false;
    }
    if (toNumber(tcForm.ageMin, 0) > toNumber(tcForm.ageMax, 0)) {
        if (!openLeadFeature.value.includes("tc")) openLeadFeature.value.push("tc");
        uni.$u.toast("年龄最小值不能大于最大值");
        return false;
    }
    return true;
};

const handleSaveTrafficConfig = async (): Promise<void> => {
    if (!props.personId || employeeConfigSaving.value || !validateTrafficConfig()) return;
    try {
        employeeConfigSaving.value = true;
        uni.showLoading({ title: "保存中...", mask: true });
        await updateTrafficConfig(buildTrafficPayload());
        uni.hideLoading();
        uni.$u.toast("自动获客配置已保存");
        closeEmployeeSheet();
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(error || "保存失败，请重试");
    } finally {
        employeeConfigSaving.value = false;
    }
};

// 单个平台配置回显：以默认值兜底，逐字段规范化；legacy（version 1）无 platform_configs 时用顶层字段迁移
const buildPlatformConfig = (source: Record<string, any>, platform: PublishPlatformEnum): PlatformPublishConfig => {
    const def = getPublishPlatformDefault(platform);
    const src = source && typeof source === "object" ? source : {};

    const generateMode = toNumber(src.generate_mode, 0);
    const copySourceRaw = toNumber(src.publish_copywriting_source, 0);
    const copySource =
        copySourceRaw === PublishCopySourceEnum.LIBRARY || copySourceRaw === PublishCopySourceEnum.AUTO
            ? copySourceRaw
            : generateMode === PublishGenerateModeEnum.LIBRARY
            ? PublishCopySourceEnum.LIBRARY
            : PublishCopySourceEnum.AUTO;

    const mediaTypeRaw = toNumber(src.publish_media_type, def.publish_media_type);
    return {
        // 仅小红书支持图文，其他平台强制视频
        publish_media_type:
            platform === PublishPlatformEnum.XIAOHONGSHU && mediaTypeRaw === PublishMediaTypeEnum.IMAGE
                ? PublishMediaTypeEnum.IMAGE
                : PublishMediaTypeEnum.VIDEO,
        publish_copywriting_source: copySource,
        generate_basis: toNumber(src.generate_basis, def.generate_basis) === 2 ? 2 : 1,
        custom_direction: String(src.custom_direction ?? def.custom_direction),
        library_use_mode: toNumber(src.library_use_mode, def.library_use_mode) === 2 ? 2 : 1,
        library_reuse_mode: toNumber(src.library_reuse_mode, def.library_reuse_mode) === 2 ? 2 : 1,
        is_content_location:
            src.is_content_location == null ? def.is_content_location : Number(src.is_content_location) === 1 ? 1 : 0,
        content_location: String(src.content_location ?? ""),
    };
};

const applyPublishConfig = (data: Record<string, any>): void => {
    const cfg = (data && data.content_publish_config) || {};
    publishConfigBase.value = cfg;

    const platformConfigs = cfg.platform_configs || {};
    const hasPlatformConfigs = platformConfigs && Object.keys(platformConfigs).length > 0;
    PUBLISH_PLATFORM_LIST.forEach(({ platform }) => {
        const raw = platformConfigs[platform] ?? platformConfigs[String(platform)];
        // 无 platform_configs（老配置）时用 content_publish_config 顶层字段迁移
        const source = raw ?? (hasPlatformConfigs ? {} : cfg);
        publishPlatformConfigs[platform] = buildPlatformConfig(source, platform);
    });

    // 挂载购物车 / 商家定位（抖音专属，存于顶层）
    publishCartEnabled.value = Number(data?.is_shopping_cart) === 1;
    publishGoodsName.value = String(data?.goods_name ?? "");
    publishStoreLocationEnabled.value = Number(data?.is_store_position) === 1;
    publishStoreLocation.value = String(data?.store_position ?? "");
};

const queryPublishConfig = async (): Promise<void> => {
    if (!props.personId) return;
    try {
        publishConfigLoading.value = true;
        const data = await getPublishConfigDetail({ id: props.personId });
        applyPublishConfig(data || {});
    } catch {
        uni.$u.toast("内容发布配置获取失败");
    } finally {
        publishConfigLoading.value = false;
    }
};

const buildPlatformConfigPayload = (platform: PublishPlatformEnum): Record<string, any> => {
    const config = publishPlatformConfigs[platform];
    const isLibrary = config.publish_copywriting_source === PublishCopySourceEnum.LIBRARY;
    const isCustomBasis = config.generate_basis === PublishBasisEnum.CUSTOM;
    return {
        platform,
        publish_media_type:
            platform === PublishPlatformEnum.XIAOHONGSHU ? config.publish_media_type : PublishMediaTypeEnum.VIDEO,
        generate_mode: isLibrary ? PublishGenerateModeEnum.LIBRARY : PublishGenerateModeEnum.AUTO,
        publish_copywriting_source: isLibrary ? PublishCopySourceEnum.LIBRARY : PublishCopySourceEnum.AUTO,
        library_use_mode: config.library_use_mode,
        library_reuse_mode: config.library_reuse_mode,
        generate_basis: config.generate_basis,
        custom_direction: !isLibrary && isCustomBasis ? config.custom_direction.trim() : "",
        is_content_location: config.is_content_location,
        content_location: config.is_content_location === 1 ? config.content_location.trim() : "",
        custom_copywriting: {
            title: "",
            content: "",
            topic_tags: [],
        },
    };
};

const buildPublishConfigPayload = () => {
    const base = publishConfigBase.value || {};
    const baseCustomCopy = base.custom_copywriting || {};
    const platform_configs = PUBLISH_PLATFORM_LIST.reduce<Record<number, Record<string, any>>>((acc, { platform }) => {
        acc[platform] = buildPlatformConfigPayload(platform);
        return acc;
    }, {});

    return {
        id: props.personId,
        content_publish_config: {
            version: 2,
            generate_mode: toNumber(base.generate_mode, 1),
            generate_basis: toNumber(base.generate_basis, 1),
            custom_direction: String(base.custom_direction ?? ""),
            is_content_location: toNumber(base.is_content_location, 0),
            content_location: String(base.content_location ?? ""),
            custom_copywriting: {
                title: String(baseCustomCopy.title ?? ""),
                content: String(baseCustomCopy.content ?? ""),
                topic_tags: Array.isArray(baseCustomCopy.topic_tags) ? baseCustomCopy.topic_tags : [],
            },
            platform_configs,
        },
        is_content_location: 0,
        content_location: "",
        is_shopping_cart: publishCartEnabled.value ? 1 : 0,
        goods_name: publishCartEnabled.value ? publishGoodsName.value : "",
        is_store_position: publishStoreLocationEnabled.value ? 1 : 0,
        store_position: publishStoreLocationEnabled.value ? publishStoreLocation.value.trim() : "",
    };
};

const validatePublishConfig = (): boolean => {
    for (const { platform, label } of PUBLISH_PLATFORM_LIST) {
        const config = publishPlatformConfigs[platform];
        const isAuto = config.publish_copywriting_source === PublishCopySourceEnum.AUTO;
        if (isAuto && config.generate_basis === PublishBasisEnum.CUSTOM && !config.custom_direction.trim()) {
            publishActivePlatform.value = platform;
            uni.$u.toast(`请填写「${label}」的自定义方向`);
            return false;
        }
        if (config.is_content_location === 1 && !config.content_location.trim()) {
            publishActivePlatform.value = platform;
            uni.$u.toast(`请填写「${label}」的内容定位地址`);
            return false;
        }
    }
    if (publishStoreLocationEnabled.value && !publishStoreLocation.value.trim()) {
        publishActivePlatform.value = PublishPlatformEnum.DOUYIN;
        uni.$u.toast("请填写商家定位地址");
        return false;
    }
    return true;
};

const handleSavePublishConfig = async (): Promise<void> => {
    if (!props.personId || publishConfigSaving.value || !validatePublishConfig()) return;
    try {
        publishConfigSaving.value = true;
        uni.showLoading({ title: "保存中...", mask: true });
        await updatePublishConfig(buildPublishConfigPayload());
        uni.hideLoading();
        uni.$u.toast("内容发布配置已保存");
        closeEmployeeSheet();
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(error || "保存失败，请重试");
    } finally {
        publishConfigSaving.value = false;
    }
};

const applyInteractionConfig = (data: Record<string, any>): void => {
    privateClueCount.value = toNumber(data.clue_count, 0);
    friendApplyText.value =
        typeof data.add_friend_script === "string"
            ? data.add_friend_script
            : "您好我是AI导师，曾带百人团队做AI落地，可帮您解决AI应用相关疑问";
    saleWechatList.value = Array.isArray(data.sales_wechat)
        ? data.sales_wechat.map((item) => String(item)).filter(Boolean)
        : [];
    groupNameTemplate.value = data.group_name_template || "{客户名}的专属VIP服务群";
    groupWelcomeEnabled.value = Number(data.is_greeting ?? 1) === 1;
    groupWelcomeText.value =
        data.greeting_text || "哈喽{客户名}，欢迎！我是您的专属销售顾问，以后有任何问题都可以直接在这个群里找我哦~";
    carryHistoryEnabled.value = Number(data.is_share_chats ?? 0) === 1;
    groupTriggerMode.value = Number(data.group_trigger_mode) === 2 ? 2 : 1;
    groupKeywords.value = Array.isArray(data.group_trigger_keywords)
        ? data.group_trigger_keywords.map((item) => String(item)).filter(Boolean)
        : ["加群", "进群"];
    circleCount.value = toNumber(data.number, 15);
    interactionPreservedConfig.isLike = toNumber(data.is_like, 1);
    interactionPreservedConfig.isComment = toNumber(data.is_comment, 1);
    interactionPreservedConfig.commentMethod = toNumber(data.comment_method, 1);
    interactionPreservedConfig.commentSpeech = Array.isArray(data.comment_speech)
        ? data.comment_speech.map((item) => String(item))
        : [];
    interactionPreservedConfig.commentRobotPrompt = data.comment_robot_prompt || "";
};

const queryInteractionConfig = async (): Promise<void> => {
    if (!props.personId) return;
    try {
        interactionConfigLoading.value = true;
        const data = await getInteractionConfig({ persona_id: props.personId });
        applyInteractionConfig(data || {});
    } catch {
        uni.$u.toast("私域运营配置获取失败");
    } finally {
        interactionConfigLoading.value = false;
    }
};

const buildInteractionPayload = () => {
    return {
        persona_id: props.personId,
        clue_count: privateClueCount.value,
        add_friend_script: friendApplyText.value,
        is_like: interactionPreservedConfig.isLike,
        is_comment: interactionPreservedConfig.isComment,
        comment_method: interactionPreservedConfig.commentMethod,
        comment_speech: interactionPreservedConfig.commentSpeech,
        number: circleCount.value,
        comment_robot_prompt: interactionPreservedConfig.commentRobotPrompt,
        // 与面板开关一致：关闭自动加群时写入 0
        is_auto_group: privatePanelStatus.group ? 1 : 0,
        sales_wechat: saleWechatList.value,
        group_name_template: groupNameTemplate.value,
        is_greeting: groupWelcomeEnabled.value ? 1 : 0,
        greeting_text: groupWelcomeText.value,
        is_share_chats: carryHistoryEnabled.value ? 1 : 0,
        group_trigger_mode: groupTriggerMode.value,
        group_trigger_keywords: groupKeywords.value,
    };
};

const validateInteractionConfig = (): boolean => {
    // 板块开关关闭时跳过该板块校验
    if (privatePanelStatus.add && !friendApplyText.value.trim()) {
        openPrivatePanel.value = "add";
        uni.$u.toast("请输入好友验证申请话术");
        return false;
    }
    if (privatePanelStatus.group) {
        if (saleWechatList.value.length === 0) {
            openPrivatePanel.value = "group";
            uni.$u.toast("请添加至少一个销售微信");
            return false;
        }
        if (!groupNameTemplate.value.trim()) {
            openPrivatePanel.value = "group";
            uni.$u.toast("请输入群名称模板");
            return false;
        }
        if (groupWelcomeEnabled.value && !groupWelcomeText.value.trim()) {
            openPrivatePanel.value = "group";
            uni.$u.toast("请输入建群欢迎语内容");
            return false;
        }
        if (groupTriggerMode.value === 2 && groupKeywords.value.length === 0) {
            openPrivatePanel.value = "group";
            uni.$u.toast("请添加至少一个加群触发词");
            return false;
        }
    }
    return true;
};

const handleSaveInteractionConfig = async (): Promise<void> => {
    if (!props.personId || interactionConfigSaving.value || !validateInteractionConfig()) return;
    try {
        interactionConfigSaving.value = true;
        uni.showLoading({ title: "保存中...", mask: true });
        await updateInteractionConfig(buildInteractionPayload());
        uni.hideLoading();
        uni.$u.toast("私域运营配置已保存");
        closeEmployeeSheet();
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(error || "保存失败，请重试");
    } finally {
        interactionConfigSaving.value = false;
    }
};

watch(
    () => props.personId,
    () => {
        initRuleSetting();
        queryTrafficConfig();
        queryInteractionConfig();
        queryPublishConfig();
        loadCsConfig();
    },
    { immediate: true },
);

watch(
    () => [props.personaType, props.trackingWords],
    () => {
        resetTrackingWordsFromProps();
    },
    { immediate: true, deep: true },
);

// 由父级主动关闭当前弹窗：调用其 close() 并清空当前打开标记
const closeEmployeeSheet = () => {
    employeeSheetVisible.value = false;
    openEmployee.value = "";
};

const handleOpenEmployee = (key: EmployeeKey) => {
    if (key === EmployeeKey.EDITOR) {
        handleOpenRuleSetting();
        return;
    }
    openEmployee.value = key;
    employeeSheetVisible.value = true;
};

// 弹窗自身关闭（蒙层/下拉/关闭按钮）后同步标记，无需再次调用 close()
const handleCloseEmployeeSheet = (key?: EmployeeKey) => {
    if (!key || openEmployee.value === key) {
        employeeSheetVisible.value = false;
        openEmployee.value = "";
    }
};

const handleOpenRuleSetting = async (): Promise<void> => {
    if (!props.personId) {
        uni.$u.toast("请先完成人设信息保存");
        return;
    }
    await queryMaterialEmpty();
    closeEmployeeSheet();
    await nextTick();
    showRuleSettingPop.value = true;
};

const handleRulesConfirm = (state: RulesState, summary: string): void => {
    currentRulesState.value = state;
    ruleSummary.value = summary;
    queryMaterialEmpty();
};

const handleRecommendTrendingKeywords = async () => {
    if (!props.personId || trendingKeywordLoading.value) return;

    try {
        trendingKeywordLoading.value = true;
        uni.showLoading({ title: "推荐中...", mask: true });
        const data = await getPersonTrackingWords({ id: props.personId });
        const keywords = getTrackingWordsFromResponse(data);
        if (keywords.length) {
            trendingKeywords.value = [...keywords];
        }
        uni.hideLoading();
        uni.$u.toast(keywords.length ? "AI 已推荐追踪词" : "暂无推荐词");
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(error || "AI 推荐失败，请重试");
    } finally {
        trendingKeywordLoading.value = false;
    }
};

const handleSaveTrendingKeywords = async (): Promise<void> => {
    if (!props.personId || trendingSaving.value) return;
    // 跟踪账号发布已隐藏，保存时固定为自动找爆款
    trendingTrackingMode.value = 1;
    const trackingAccountConfig = buildTrackingAccountConfigPayload();
    if (trendingKeywords.value.length === 0) {
        uni.$u.toast("请至少添加一个爆款追踪词");
        return;
    }
    try {
        trendingSaving.value = true;
        uni.showLoading({ title: "保存中...", mask: true });
        await updatePersonTrackingWords(buildTrendingPayload(trackingAccountConfig));
        trendingOriginKeywords.value = [...trendingKeywords.value];
        uni.hideLoading();
        uni.$u.toast("热点追踪配置已保存");
        closeEmployeeSheet();
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(error || "保存失败，请重试");
    } finally {
        trendingSaving.value = false;
    }
};

const buildTrackingAccountConfigPayload = (): TrackingAccountConfig => {
    const config = normalizeTrackingAccountConfig(trendingAccountConfig.value);
    return Object.keys(config).reduce<TrackingAccountConfig>((payload, key) => {
        const account = config[key]?.account?.trim() || "";
        const homepageUrl = config[key]?.homepage_url?.trim() || "";
        if (account || homepageUrl) {
            payload[key] = {
                account,
                homepage_url: homepageUrl,
            };
        }
        return payload;
    }, {});
};

const buildTrendingPayload = (trackingAccountConfig: TrackingAccountConfig): Record<string, any> => {
    if (trendingTrackingMode.value === 2) {
        return {
            id: props.personId,
            tracking_mode: 2,
            tracking_account_config: trackingAccountConfig,
        };
    }
    return {
        id: props.personId,
        tracking_mode: 1,
        hot_words: trendingKeywords.value,
        duration: trendingDuration.value,
        publish_day: trendingPublishDay.value,
    };
};

const openKeywordEditPopup = (list: Ref<string[]> | string[], index: number, options: KeywordEditOptions) => {
    keywordEditTarget = list;
    editingKeywordIndex.value = index;
    keywordEditTitle.value = options.title;
    keywordEditLabel.value = options.label;
    keywordEditPlaceholder.value = options.placeholder;
    keywordEditTip.value = options.tip;
    keywordEditMaxlength.value = options.maxlength || 20;
    keywordEditMaxItems.value = options.maxItems || 0;
    keywordEditAllowMultiple.value = Boolean(options.allowMultiple);
    const arr = Array.isArray(list) ? list : list.value;
    keywordEditValue.value = index >= 0 ? arr[index] || "" : "";
    showKeywordEditPopup.value = true;
};

const handleSaveKeywordItem = (value: string) => {
    const keywords = normalizeKeywordItems(value);
    const arr = getKeywordEditList();
    if (!keywords.length || !arr) {
        uni.$u.toast("请输入内容");
        return;
    }

    const editingIndex = editingKeywordIndex.value;
    const reservedKeywords = editingIndex >= 0 ? arr.filter((_, index) => index !== editingIndex) : arr;
    const saveKeywords = keywords.filter((keyword) => !reservedKeywords.includes(keyword));
    if (!saveKeywords.length) {
        uni.$u.toast("内容已存在");
        return;
    }

    const remainingCount =
        keywordEditMaxItems.value > 0 ? keywordEditMaxItems.value - reservedKeywords.length : saveKeywords.length;

    if (keywordEditMaxItems.value > 0 && saveKeywords.length > remainingCount) {
        uni.$u.toast(`最多添加 ${keywordEditMaxItems.value} 个`);
        return;
    }

    if (editingIndex >= 0) {
        arr.splice(editingIndex, 1, ...saveKeywords);
    } else {
        arr.push(...saveKeywords);
    }

    showKeywordEditPopup.value = false;
};

const handleOpenTrendingKeywordPopup = (index = -1) =>
    openKeywordEditPopup(trendingKeywords, index, {
        title: index >= 0 ? "编辑爆款追踪词" : "添加爆款追踪词",
        label: "追踪关键词",
        placeholder: "如：敏感肌、换季护肤",
        tip: "保存后，AI 会按这个关键词追踪全平台爆款内容。",
    });

// handleOpenWxv/Jl/Tg/Tc 系列已迁至 hooks/useLeadFeatures.ts（通过 destructure alias 重新暴露）

// addInputKeyword / handleAddTg* / handleAddTc* / handleRemoveKeyword /
// handleToggleLeadFeature / handleChangeWxv/JlLimit / handleSelectDistance /
// handleCustomDistanceInput 已迁至 hooks/useLeadFeatures.ts
// handleOpenSaleWechatPopup / handleOpenGroupKeywordPopup / handleTogglePrivatePanel /
// handleAddSaleWechat / handleInsertTemplate / handleChangeCircleCount /
// handleOpenCircleSetting 已迁至 hooks/usePrivateChannels.ts

const handleChooseAgentConfirm = (agent: any) => {
    csApplyAgent(agent);
};

const handleSaveEmployee = async (row: EmployeeRow) => {
    if (row.key === EmployeeKey.TRENDING) {
        await handleSaveTrendingKeywords();
        return;
    }
    if (row.key === EmployeeKey.LEADS) {
        await handleSaveTrafficConfig();
        return;
    }
    if (row.key === EmployeeKey.PRIVATE) {
        await handleSaveInteractionConfig();
        return;
    }
    if (row.key === EmployeeKey.OPERATOR) {
        await handleSavePublishConfig();
        return;
    }
    if (row.key === EmployeeKey.CS) {
        const ok = await saveCsConfig();
        if (ok) closeEmployeeSheet();
        return;
    }
    uni.$u.toast(`${row.name}配置已保存`);
    closeEmployeeSheet();
};
</script>

<style lang="scss" scoped>
.employee-overview {
    @apply flex items-center justify-between gap-[20rpx] py-[12rpx] px-[12rpx];
}

.skeleton-overview {
    @apply bg-white rounded-[36rpx] py-[30rpx] px-[28rpx] border-[2rpx] border-solid border-[#eef2f8];
}

.overview-icon {
    @apply w-[60rpx] h-[60rpx] rounded-[22rpx] bg-primary flex items-center justify-center shrink-0;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.overview-counts {
    @apply flex items-center gap-[10rpx] text-[22rpx] text-[#86909c] shrink-0;
}

.overview-count-item {
    @apply inline-flex items-center gap-[8rpx] whitespace-nowrap;
}

.status-dot {
    @apply w-[12rpx] h-[12rpx] rounded-full bg-[#00b578] shrink-0;

    &.off {
        @apply bg-[#d1d5db];
    }
}

.sec-label {
    @apply block text-[22rpx] text-[#9ca3af] mb-[12rpx] font-medium;
}

.field-label {
    @apply block text-[20rpx] text-[#9ca3af] mb-[8rpx];
}

.small-pill {
    @apply h-[44rpx] px-[20rpx] rounded-full text-[20rpx] flex items-center justify-center;
}

.emp-list {
    @apply flex flex-col gap-[26rpx];
}

.emp-group-title {
    @apply flex items-center gap-[12rpx] px-[4rpx] pt-[10rpx] pb-[2rpx] text-[30rpx] font-extrabold text-[#1d2129];
}

.emp-group-bar {
    @apply w-[6rpx] h-[32rpx] rounded-full bg-primary shrink-0;
}

.emp-group-count {
    @apply ml-auto text-[24rpx] font-bold text-[#9ca3af] bg-[#eef1f6] rounded-full py-[6rpx] px-[22rpx] whitespace-nowrap;
}

.emp-row {
    @apply bg-white rounded-[36rpx] overflow-hidden border-[2rpx] border-solid border-[#eef2f8];
    box-shadow: 0 8rpx 32rpx rgba(47, 115, 246, 0.09);

    &.paused {
        box-shadow: 0 4rpx 20rpx rgba(17, 24, 39, 0.04);

        .emp-row-icon image {
            opacity: 0.46;
        }
    }
}

.emp-row-hd {
    @apply flex items-center gap-[24rpx] py-[34rpx] px-[32rpx];
}

.emp-row-icon {
    @apply w-[88rpx] h-[88rpx] rounded-[26rpx] flex items-center justify-center shrink-0 relative;
}

.emp-status-dot {
    @apply absolute top-[-6rpx] right-[-6rpx] w-[20rpx] h-[20rpx] rounded-full bg-[#22c55e] border-[4rpx] border-solid border-white;
    animation: pulse-dot 2.2s ease-in-out infinite;

    &.off {
        @apply bg-[#d1d5db];
        animation: none;
    }
}

@keyframes pulse-dot {
    0%,
    100% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.45);
    }
    60% {
        box-shadow: 0 0 0 8rpx rgba(34, 197, 94, 0);
    }
}

.emp-row-info {
    @apply flex-1 min-w-0;
}

.emp-row-name {
    @apply text-[30rpx] font-bold text-[#1d2129];
}

.emp-row-desc {
    @apply text-[24rpx] text-[#9ca3af] mt-[8rpx] line-clamp-1;
}

.emp-badge {
    @apply text-[18rpx] font-semibold py-[4rpx] px-[14rpx] rounded-[12rpx] shrink-0 inline-flex items-center gap-[6rpx];

    &::before {
        @apply w-[8rpx] h-[8rpx] rounded-full;
        content: "";
        background: currentColor;
    }

    &.running {
        @apply text-[#00b578] bg-[#e6f9f0];
    }

    &.paused {
        @apply text-[#9ca3af] bg-[#f3f4f6];
    }
}

.emp-row-foot {
    @apply flex items-center gap-[14rpx] py-[22rpx] px-[32rpx] bg-[#f8fafc] border-0 border-t-[2rpx] border-solid border-[#f0f3f8];
}

.skeleton-row-foot {
    @apply flex items-center justify-between py-[22rpx] px-[30rpx] bg-[#f8fafc] border-0 border-t-[2rpx] border-solid border-[#f0f3f8];
}

.emp-foot-tags {
    @apply flex flex-wrap items-center gap-[12rpx] flex-1 min-w-0;
}

.emp-foot-tag {
    @apply text-[22rpx] text-[#4e5969] bg-white border-[2rpx] border-solid border-[#e5e9f0] rounded-[16rpx] py-[8rpx] px-[18rpx] whitespace-nowrap;
}

.emp-foot-more {
    @apply text-[22rpx] text-[#9ca3af] bg-[#eef1f6] rounded-[16rpx] py-[8rpx] px-[18rpx] whitespace-nowrap;
}

.emp-foot-cfg {
    @apply flex items-center gap-[4rpx] text-[24rpx] font-semibold text-[#2f73f6] shrink-0;
}

.seg-bar {
    @apply flex bg-[#f3f4f6] rounded-[32rpx] p-[8rpx] gap-[4rpx];

    &.mini {
        @apply w-[300rpx] rounded-[24rpx] p-[4rpx];
    }

    &.basis {
        @apply w-[330rpx];
    }

    &.white {
        @apply bg-white border-[2rpx] border-solid border-[#e5e7eb];
    }
}

.seg-btn {
    @apply flex-1 min-h-[64rpx] rounded-[24rpx] text-[22rpx] font-semibold text-[#9ca3af] flex items-center justify-center text-center;

    &.active {
        @apply bg-primary text-white;
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
    }
}

// .op-* 已搬至 ai-employee-step/panels/operator-panel.vue

.cfg-inp {
    @apply w-full min-h-[88rpx] bg-[#F9FAFB] border-[3rpx] border-solid border-[transparent] rounded-[24rpx] px-[28rpx] text-[24rpx] text-[#1d2129] box-border;
}

.cfg-textarea {
    @apply min-h-[180rpx] pt-[24rpx] leading-[1.6];
}

.customer-textarea {
    @apply w-full min-h-[280rpx] bg-white rounded-[28rpx] py-[20rpx] px-[24rpx] text-xs text-[#1d2129] leading-[1.7] box-border border border-solid border-[#e5e7eb];
}

.setting-block {
    @apply flex items-center justify-between gap-[24rpx];
}

.setting-title {
    @apply block text-[26rpx] font-bold text-[#1d2129];
}

.setting-desc {
    @apply block text-[22rpx] text-[#9ca3af] mt-[4rpx];
}
// .private-* 已搬至 ai-employee-step/panels/private-panel.vue

.cs-platform {
    @apply min-h-[124rpx] rounded-[24rpx] bg-[#f3f4f6] text-[#9ca3af] flex flex-col items-center justify-center;

    &.active {
        @apply text-white;
    }
}

.reply-box {
    @apply bg-[#F9FAFB] rounded-[32rpx] p-[28rpx];
}

.picker-field {
    @apply bg-white border-[#e5e7eb];
}

/* ===== 智能客服分块 ===== */
// .cs-* 已搬至 ai-employee-step/panels/cs-panel.vue

// .feat-card / .risk-* / .lfc-* / .step-* 已搬至 ai-employee-step/panels/leads-panel.vue
// .prv-* / .private-* / .lead-badge / .mini-icon / .warning-box / .circle-* / .cfg-inp-row / .cfg-add-btn
// 以及 .detail-kw-* 已搬至 ai-employee-step/panels/private-panel.vue 和 leads-panel.vue
// .target-region-grid / .region-input / .cfg-exec / .cfg-num-* / .gender-* / .dist-* /
// .custom-distance-* / .range-* / .city-btn 已搬至 ai-employee-step/panels/leads-panel.vue
</style>
