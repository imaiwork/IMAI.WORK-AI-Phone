<template>
    <view class="min-h-screen overflow-hidden bg-[#F3F7FC]">
        <view class="shrink-0 bg-[#F3F7FC] px-[30rpx] pt-[24rpx] pb-[10rpx]">
            <view v-if="pageLoading" class="h-[196rpx] rounded-[36rpx] bg-[#E8F0FF] animate-pulse"></view>
            <view v-else class="employee-hero">
                <view class="relative z-[1] flex items-center gap-[20rpx] min-w-0">
                    <view class="employee-hero-icon">
                        <image :src="CpuIcon" mode="aspectFit" class="w-[36rpx] h-[36rpx]" />
                    </view>
                    <view class="min-w-0">
                        <text class="block text-[32rpx] font-extrabold text-white leading-[1.2]">AI 员工配置台</text>
                        <text class="block text-xs font-medium text-[#ffffff]/80 mt-[8rpx] line-clamp-1">
                            当前人设 · {{ personaName || "未命名人设" }}
                        </text>
                    </view>
                </view>
                <text class="relative z-[1] block text-[21rpx] leading-[1.6] text-[#ffffff]/85 mt-[22rpx]">
                    共 <text class="font-extrabold text-white">{{ EMPLOYEE_TOTAL_COUNT }}</text> 个 AI 员工，<text
                        class="font-extrabold text-white"
                        >{{ enabledEmployeeCount }}</text
                    >
                    项已启用 · 在这里配置每个 AI 员工的工作方式
                </text>
            </view>
        </view>

        <view class="px-[32rpx] pt-[18rpx] pb-[80rpx]">
            <ai-employee-step
                :person-id="personId"
                :config-status="configStatus"
                :loading="pageLoading"
                :persona-type="personaType"
                :tracking-words="trackingWords"
                :global-option="globalOption"
                hide-overview
                grouped />
        </view>
    </view>
</template>

<script setup lang="ts">
import { getPersonConfigStatus, getPersonDetail } from "@/api/person";
import { PersonTypeEnum } from "@/ai_modules/person/enums";
import { setFormData } from "@/utils/util";
import CpuIcon from "@/ai_modules/person/static/icons/employee/cpu-white.svg";
import AiEmployeeStep from "../employee_setting/components/ai-employee-step.vue";

type PersonDetailFormKey = "individual" | "enterprise" | "local";

const PERSON_DETAIL_FORM_KEY: Record<PersonTypeEnum, PersonDetailFormKey> = {
    [PersonTypeEnum.PERSONAL_IP]: "individual",
    [PersonTypeEnum.BUSINESS_SERVICE]: "enterprise",
    [PersonTypeEnum.LOCAL_BUSINESS]: "local",
};

const personId = ref("");
const pageLoading = ref(true);
const initialized = ref(false);
const personaName = ref("");
const personaType = ref<PersonTypeEnum | number>(PersonTypeEnum.PERSONAL_IP);
const trackingWords = ref<any>([]);
const globalOption = ref<Record<string, any> | null>(null);
const configStatus = ref<Record<string, any>>({
    digital_config: 0,
    material_config: 0,
    persona_agent_config: 0,
    traffic_config: 0,
    wechat_interaction_config: 0,
});

const EMPLOYEE_TOTAL_COUNT = 6;

const toSwitchBool = (val: any, fallback = false): boolean => {
    if (val === 1 || val === "1" || val === true) return true;
    if (val === 0 || val === "0" || val === false) return false;
    return fallback;
};

const enabledEmployeeCount = computed(() => {
    const option = globalOption.value || {};
    return [
        toSwitchBool(option.hot_words, true),
        toSwitchBool(option.video_clip, true),
        toSwitchBool(option.content_publish, true),
        toSwitchBool(option.customer_service, true),
        toSwitchBool(option.auto_clues?.status, true),
        toSwitchBool(option.private_operation?.status, false),
    ].filter(Boolean).length;
});

const syncPersonDetail = (data: Record<string, any>): void => {
    const nextPersonaType = Number(data.persona_type) as PersonTypeEnum;
    personaName.value = data.persona_name || "";
    personaType.value = nextPersonaType || PersonTypeEnum.PERSONAL_IP;

    const formKey = PERSON_DETAIL_FORM_KEY[personaType.value as PersonTypeEnum];
    const subFormData = formKey ? data[formKey] || {} : {};
    // duration / publish_day / tracking_* 取 detail 最外层；hot_words 仍读子表单
    trackingWords.value = {
        duration: data.duration,
        publish_day: data.publish_day,
        tracking_mode: data.tracking_mode,
        tracking_account_config: data.tracking_account_config,
        hot_words: subFormData.hot_words ?? data.hot_words ?? [],
    };
    globalOption.value = subFormData.global_option ?? data.global_option ?? null;
};

const getConfigStatus = async (): Promise<void> => {
    if (!personId.value) return;
    const res = await getPersonConfigStatus({ id: personId.value });
    setFormData(res, configStatus.value);
};

const init = async (): Promise<void> => {
    if (!personId.value) {
        pageLoading.value = false;
        initialized.value = true;
        return;
    }

    try {
        pageLoading.value = true;
        const [detailResult] = await Promise.allSettled([getPersonDetail({ id: personId.value }), getConfigStatus()]);
        if (detailResult.status === "fulfilled") {
            syncPersonDetail(detailResult.value || {});
        }
    } finally {
        pageLoading.value = false;
        initialized.value = true;
    }
};

onLoad((options: any) => {
    personId.value = options.id ?? "";
    init();
});

onShow(() => {
    if (!initialized.value || !personId.value) return;
    getConfigStatus();
});
</script>

<style lang="scss" scoped>
.employee-hero {
    position: relative;
    overflow: hidden;
    border-radius: 36rpx;
    padding: 30rpx 32rpx;
    background: linear-gradient(135deg, #2e64e8, #4c86f5);
    box-shadow: 0 16rpx 44rpx rgba(37, 99, 235, 0.28);

    &::after {
        content: "";
        position: absolute;
        top: -60rpx;
        right: -40rpx;
        width: 220rpx;
        height: 220rpx;
        border-radius: 999rpx;
        background: rgba(255, 255, 255, 0.1);
    }
}

.employee-hero-icon {
    @apply w-[76rpx] h-[76rpx] rounded-[24rpx] bg-[#ffffff]/20 flex items-center justify-center shrink-0;
}
</style>
