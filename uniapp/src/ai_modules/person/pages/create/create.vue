<template>
    <view class="min-h-screen bg-[#F3F7FC] pt-[118rpx] pb-[180rpx]">
        <view
            class="fixed top-0 left-0 right-0 z-40 bg-white px-[44rpx] pt-[28rpx] pb-[22rpx] border-0 border-b border-solid border-[#E8EEF7]">
            <view class="flex items-center">
                <view class="flex items-center">
                    <view
                        class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center text-[24rpx] font-bold"
                        :class="activeStep === 'info' ? 'bg-primary text-white' : 'bg-[#22C55E] text-white'"
                        @click="switchStep(CreateStepEnum.INFO)">
                        <u-icon v-if="activeStep === 'biz'" name="checkmark" color="#ffffff" size="26"></u-icon>
                        <text v-else>1</text>
                    </view>
                    <text
                        class="ml-[16rpx] text-[30rpx] font-black whitespace-nowrap"
                        :class="activeStep === 'info' ? 'text-[#111827]' : 'text-[#22C55E]'"
                        @click="switchStep(CreateStepEnum.INFO)">
                        基础信息
                    </text>
                </view>
                <view
                    class="h-[4rpx] flex-1 mx-[32rpx] rounded-full"
                    :class="activeStep === CreateStepEnum.BIZ ? 'bg-[#22C55E]' : 'bg-[#DFE7F5]'">
                </view>
                <view class="flex items-center" @click="switchStep(CreateStepEnum.BIZ)">
                    <view
                        class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center text-[24rpx] font-bold"
                        :class="activeStep === 'biz' ? 'bg-primary text-white' : 'bg-[#DFE7F5] text-[#9CA3AF]'">
                        2
                    </view>
                    <text
                        class="ml-[16rpx] text-[30rpx] font-black whitespace-nowrap"
                        :class="activeStep === 'biz' ? 'text-[#111827]' : 'text-[#9CA3AF]'">
                        业务描述
                    </text>
                </view>
            </view>
        </view>

        <view v-if="pageLoading" class="px-[36rpx] pt-[28rpx] animate-pulse">
            <view class="bg-white rounded-[32rpx] p-[40rpx] mb-[28rpx]">
                <view class="h-[180rpx] bg-[#EEF3FA] rounded-[28rpx]"></view>
            </view>
            <view class="bg-white rounded-[32rpx] p-[40rpx]">
                <view v-for="n in 4" :key="n" class="h-[150rpx] bg-[#EEF3FA] rounded-[28rpx] mb-[34rpx]"></view>
            </view>
        </view>

        <view v-else class="px-[36rpx] pt-[28rpx]">
            <view v-show="activeStep === 'info'">
                <basic-info-step
                    ref="basicInfoStepRef"
                    :basic-form="basicForm"
                    :avatar-url="formData.avatar_url"
                    @update:avatar="handleAvatarUpdate"
                    @popup-visible-change="basicPopupVisible = $event" />
            </view>

            <view v-show="activeStep === 'biz'">
                <business-desc-step
                    :is-add-mode="isAddMode"
                    :analysis-token-score="getAnalysisTokenScore"
                    :current-sub-form="currentSubForm"
                    @open-voice="openVoicePop" />
            </view>
        </view>

        <view
            v-show="!footerHidden"
            class="fixed bottom-0 left-0 right-0 bg-white px-[36rpx] pt-[26rpx] shadow-[0_-4rpx_20rpx_rgba(0,0,0,0.05)] z-50"
            style="padding-bottom: calc(24rpx + env(safe-area-inset-bottom))">
            <view v-if="pageLoading" class="animate-pulse">
                <view class="h-[100rpx] rounded-[28rpx] bg-[#E5E7EB]"></view>
            </view>
            <u-button
                v-else
                type="primary"
                shape="square"
                :loading="submitting"
                :ripple="true"
                :custom-style="{
                    height: '100rpx',
                    borderRadius: '28rpx',
                    fontSize: '30rpx',
                    fontWeight: '900',
                    border: 'none',
                    background: 'linear-gradient(135deg, #3D82F7, #2563EB)',
                    boxShadow: '0 12rpx 40rpx rgba(47, 115, 246, 0.28)',
                }"
                @click="handleFooterClick">
                <text class="ml-[12rpx]">{{ footerButtonText }}</text>
                <view v-if="activeStep === 'biz' && isAddMode && getTokenScore > 0" class="ml-[12rpx]">
                    <text class="bg-[#ffffff]/20 py-[6rpx] px-[16rpx] rounded-full text-[22rpx] text-white font-medium">
                        消耗 {{ getTokenScore }} 算力
                    </text>
                </view>
            </u-button>
        </view>

        <dragon-button :x-edge="-20" :y-edge="100" v-if="isAddMode && isReleaseVersion()">
            <view
                class="w-[100rpx] h-[100rpx] rounded-full flex items-center justify-center"
                style="background: linear-gradient(180deg, rgba(77, 163, 255, 1) 0%, rgba(0, 122, 255, 1) 100%)"
                @click="openVoicePop">
                <u-icon name="mic" color="#ffffff" size="48"></u-icon>
            </view>
        </dragon-button>
    </view>

    <recorder-control
        v-model="showRecorder"
        ref="recorderRef"
        @close="showRecorder = false"
        @success="recorderSuccess" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import {
    getPersonDetail,
    createPerson,
    editPerson,
    createPersonAnalysis,
    generatePersonAnalysisReport,
    getPersonClueWords,
    getPersonInteractionWords,
    getPersonTrackingWords,
} from "@/api/person";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { PersonTypeEnum } from "@/ai_modules/person/enums";
import { isReleaseVersion } from "@/utils/env";
import RecorderControl from "@/ai_modules/person/components/recorder-control/recorder-control.vue";
import BasicInfoStep from "./components/basic-info-step.vue";
import BusinessDescStep from "./components/business-desc-step.vue";
import { setFormData } from "@/utils/util";
import {
    OPTIONS_CONFIG,
    ACCOUNT_STYLE_OPTION_NAMES,
    ACTION_GOAL_OPTION_NAMES,
    INDUSTRY_DIRECTION_OPTION_NAMES,
    PERSON_TYPE_NAME_MAP,
    REPORT_MODEL_MAP,
    buildPersonPayload,
    buildReportContents,
    createDefaultBasicForm,
    createDefaultPersonFormData,
    createReportSnapshot,
    hasReportFieldsChanged,
    normalizeStringArray,
    syncBasicInfoToPayloadFields as syncBasicInfoToPersonFields,
    type PersonFormData,
} from "@/ai_modules/person/hooks/usePersonFormCore";

enum CreateStepEnum {
    INFO = "info",
    BIZ = "biz",
}

// ─── 字段配置类型 ─────────────────────────────────────────────────
type CreateMode = "add" | "edit";
type CreateStep = (typeof CreateStepEnum)[keyof typeof CreateStepEnum];

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const source = ref<string>("");
const personId = ref<string>("");
const showRecorder = ref(false);
const basicInfoStepRef = shallowRef<{ closePopups?: () => void }>();
const basicPopupVisible = ref(false);
const footerHidden = computed(() => basicPopupVisible.value || showRecorder.value);
const submitting = ref(false);
const createMode = ref<CreateMode>("add");
const activeStep = ref<CreateStep>(CreateStepEnum.INFO);

watch(activeStep, () => {
    basicInfoStepRef.value?.closePopups?.();
    basicPopupVisible.value = false;
});

const pageLoading = ref(false);

const basicForm = reactive(createDefaultBasicForm());
const formData = reactive(createDefaultPersonFormData());
const originSnapshot = ref<Partial<PersonFormData> | null>(null);

const hasFormChanged = (): boolean => {
    if (createMode.value === "add") return true;
    return hasReportFieldsChanged(formData, originSnapshot.value);
};

const recorderRef = shallowRef<InstanceType<typeof RecorderControl>>();
const rechargePopupRef = shallowRef();

const currentSubForm = computed<Record<string, any>>(() => formData as unknown as Record<string, any>);
const isAddMode = computed(() => createMode.value === "add");

const getTokenScore = computed(() => {
    const tokenInfo = userStore.getTokenByScene(TokensSceneEnum.AI_PERSONA_REPORT);
    return Number(tokenInfo?.score || 0);
});

const getAnalysisTokenScore = computed(() => {
    const tokenInfo = userStore.getTokenByScene(TokensSceneEnum.AI_PERSONA_ANALYSIS);
    return Number(tokenInfo?.score || 0);
});

const footerButtonText = computed(() => {
    if (activeStep.value === "info") return "下一步：业务描述";
    return createMode.value === "add" ? "完成，立即上岗！" : "保存人设";
});

const syncBasicInfoToPayloadFields = (): void => {
    syncBasicInfoToPersonFields(basicForm, formData);
};

const scrollToTop = () => {
    uni.pageScrollTo({ scrollTop: 0, duration: 180 });
};

const switchStep = (step: CreateStep): void => {
    if (step === CreateStepEnum.BIZ) {
        if (!validateBaseInfo()) return;
        syncBasicInfoToPayloadFields();
    }
    activeStep.value = step;
    scrollToTop();
};

const validateBaseInfo = (): boolean => {
    if (!(basicForm.industry_direction || []).length) {
        uni.showToast({ title: "请选择行业方向", icon: "none" });
        return false;
    }
    if (!basicForm.account_style) {
        uni.showToast({ title: "请选择账号风格", icon: "none" });
        return false;
    }
    if (!basicForm.action_goal) {
        uni.showToast({ title: "请选择行动目标", icon: "none" });
        return false;
    }
    return true;
};

const handleFooterClick = async (): Promise<void> => {
    if (activeStep.value === CreateStepEnum.INFO) {
        if (!validateBaseInfo()) return;
        syncBasicInfoToPayloadFields();
        switchStep(CreateStepEnum.BIZ);
        return;
    }
    await handleSubmit();
};

const openVoicePop = async () => {
    await recorderRef.value?.authorize(recorderRef.value.proxy);
    showRecorder.value = true;
};

const recorderSuccess = async (res: any) => {
    showRecorder.value = false;
    const { message } = res;

    syncBasicInfoToPayloadFields();

    if (userTokens.value <= getAnalysisTokenScore.value) {
        rechargePopupRef.value?.open();
        return;
    }

    uni.showLoading({ title: "AI分析中...", mask: true });

    try {
        const analysisResult = await createPersonAnalysis({
            contents: message,
            model: formData.persona_type,
        });

        // 统一字段匹配：支持 string / string[]，模糊匹配到选择器可选项
        const parseAndMatchOptions = (
            value: string | string[] | undefined,
            availableOptions: readonly string[],
        ): string[] => {
            if (!value) return [];
            const items = (Array.isArray(value) ? value : String(value).split(/[,，;；、\s]+/))
                .map((item) => String(item).trim())
                .filter((item) => item.length > 0);
            const matched: string[] = [];
            items.forEach((item) => {
                const exactMatch = availableOptions.find(
                    (option) => option === item || option.includes(item) || item.includes(option),
                );
                if (exactMatch && !matched.includes(exactMatch)) matched.push(exactMatch);
            });
            return matched;
        };

        // 新版语音分析接口返回统一字段（不再按人设类型区分 key）
        const {
            ip_name,
            ip_intro,
            what_you_do,
            main_share,
            target_viewers,
            tone,
            location_city,
            desired_action,
            what_you_sell,
            target_buyers,
            advantage,
        } = analysisResult;

        // 步骤1 基础信息（绑定 basicForm，直接回显）
        if (ip_name) basicForm.ai_name = ip_name;
        if (ip_intro) basicForm.intro = ip_intro;
        if (main_share) basicForm.content_direction = main_share;
        if (target_viewers) basicForm.target_audience = target_viewers;
        if (location_city) basicForm.city_position = location_city;

        // 步骤1 选择器（匹配到可选项才回填，匹配不到保留原值）
        const matchedIndustry = parseAndMatchOptions(what_you_do, INDUSTRY_DIRECTION_OPTION_NAMES);
        if (matchedIndustry.length) basicForm.industry_direction = matchedIndustry;
        const matchedStyle = parseAndMatchOptions(tone, ACCOUNT_STYLE_OPTION_NAMES);
        if (matchedStyle.length) basicForm.account_style = matchedStyle[0];
        const matchedGoal = parseAndMatchOptions(desired_action, ACTION_GOAL_OPTION_NAMES);
        if (matchedGoal.length) basicForm.action_goal = matchedGoal[0];

        // 步骤2 业务描述（绑定 formData，直接回显）
        if (what_you_sell) formData.main_business = what_you_sell;
        if (target_buyers) formData.target_pain_points = target_buyers;
        if (advantage) formData.conversion_hook = advantage;

        uni.hideLoading();
        uni.showToast({ title: "AI分析完成", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "语音分析失败，请检查网络后重试",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleAvatarUpdate = (imageUrl: string): void => {
    formData.avatar_url = imageUrl;
};

const validate = (): boolean => {
    if (!validateBaseInfo()) {
        activeStep.value = CreateStepEnum.INFO;
        return false;
    }
    syncBasicInfoToPayloadFields();

    const requiredFields: { key: keyof PersonFormData; label: string }[] = [
        { key: "main_business", label: "你在做什么 / 卖什么" },
        { key: "target_pain_points", label: "你想卖给谁 / 给谁看" },
        { key: "conversion_hook", label: "你比别人好在哪" },
    ];

    for (const field of requiredFields) {
        if (!String(formData[field.key] || "").trim()) {
            uni.showToast({ title: `请完善「${field.label}」`, icon: "none" });
            activeStep.value = CreateStepEnum.BIZ;
            return false;
        }
    }
    return true;
};

const checkContentLength = (): Promise<boolean> => {
    const MIN_TOTAL_LENGTH = 300;
    const totalLength = [
        formData.persona_name,
        formData.persona_desc,
        formData.content_focus,
        (basicForm.industry_direction || []).join(""),
        basicForm.content_direction,
        basicForm.target_audience,
        basicForm.account_style,
        basicForm.city_position,
        basicForm.action_goal,
        formData.main_business,
        formData.target_pain_points,
        formData.conversion_hook,
        formData.account_style,
    ].reduce((sum, value) => sum + String(value || "").trim().length, 0);

    if (totalLength >= MIN_TOTAL_LENGTH) return Promise.resolve(true);

    return new Promise((resolve) => {
        uni.showModal({
            title: "内容过少",
            content: "当前内容过少，如果生成报告准确性将不确定，是否继续生成？",
            confirmText: "确定生成",
            cancelText: "继续修改",
            success: ({ confirm }) => resolve(confirm),
        });
    });
};

const handleSubmit = async (): Promise<void> => {
    if (!validate()) return;

    const needGenerateReport = hasFormChanged();

    if (needGenerateReport) {
        const shouldContinue = await checkContentLength();
        if (!shouldContinue) return;
    }

    if (needGenerateReport && userTokens.value <= getTokenScore.value) {
        rechargePopupRef.value?.open();
        return;
    }

    uni.showLoading({ title: "保存中...", mask: true });
    try {
        submitting.value = true;
        const payload = buildPersonPayload(basicForm, formData, { includeStoreAndShoppingFields: true });
        const res = personId.value
            ? await editPerson({
                  ...payload,
                  id: personId.value,
                  is_create_report: needGenerateReport ? 1 : 0,
              })
            : await createPerson(payload);
        uni.hideLoading();

        if (needGenerateReport) {
            generatePersonAnalysisReport({
                persona_id: res.persona_id,
                model: REPORT_MODEL_MAP[formData.persona_type],
                contents: buildReportContents(formData),
            });
            getPersonClueWords({ id: res.persona_id });
            getPersonInteractionWords({ id: res.persona_id });
            // 触发爆款追踪词生成，供「今日爆款速递」关键词 tab 使用
            getPersonTrackingWords({ id: res.persona_id });

            uni.showModal({
                title: "提示",
                content: "IP运营报告生成中,您可以继续等待，或可先配置其他内容",
                cancelText: "稍后查看",
                confirmText: "立即前往",
                success: ({ confirm }) => {
                    if (confirm) {
                        uni.redirectTo({
                            url: `/ai_modules/person/pages/analysis/analysis?id=${res.persona_id}&mode=${
                                needGenerateReport ? "add" : createMode.value
                            }&type=${formData.persona_type}`,
                        });
                    } else {
                        if (source.value === "back") {
                            uni.navigateBack();
                            return;
                        }
                        uni.redirectTo({
                            url: `/ai_modules/person/pages/detail/detail?id=${res.persona_id}&mode=${createMode.value}&type=${formData.persona_type}`,
                        });
                    }
                },
            });
        } else {
            uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
            setTimeout(() => uni.navigateBack(), 1000);
        }
    } catch (e: any) {
        uni.hideLoading();
        uni.showToast({ title: e || "保存失败，请重试", icon: "none" });
    } finally {
        submitting.value = false;
    }
};

const back = (): void => {
    if (activeStep.value === CreateStepEnum.BIZ) {
        switchStep(CreateStepEnum.INFO);
        return;
    }
    uni.showModal({
        title: "提示",
        content: "退出后，您填写的信息将不会被保存",
        success: (res) => {
            if (res.confirm) uni.navigateBack();
        },
    });
};

onLoad((options: any) => {
    if (options.type) {
        formData.persona_type = Number(options.type) as PersonTypeEnum;
        basicForm.persona_type = formData.persona_type;
    }
    if (options.mode) createMode.value = options.mode as CreateMode;
    if (options.id) {
        personId.value = options.id;
    }
    if (options.source) source.value = options.source;
});
</script>
