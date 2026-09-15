<template>
    <view class="setup-card">
        <view class="setup-heading" @click="emit('update:expanded', !expanded)">
            <text class="text-[32rpx] font-semibold text-[#202937]">配置清单</text>
            <text class="setup-count">
                {{ state.doneCount }} / {{ state.totalStep }} 已完成
            </text>
            <view class="ml-auto flex items-center gap-[8rpx] text-[24rpx] text-[#67768A]">
                <text>{{ expanded ? "收起" : "展开" }}</text>
                <u-icon :name="expanded ? 'arrow-up' : 'arrow-down'" color="#667085" size="22"></u-icon>
            </view>
        </view>
        <view class="h-[8rpx] rounded-full bg-[#EDF1F5] overflow-hidden">
                <view
                    class="h-full rounded-full bg-primary"
                    :style="{ width: `${state.progressPercent}%` }"></view>
        </view>
        <view v-if="!expanded" class="setup-summary" @click="emit('update:expanded', true)">
            <text>当前待办 · {{ currentStep?.title }}</text>
            <text v-if="currentStep?.progressText" class="text-[#936118]">{{ currentStep.progressText }}</text>
        </view>
        <view v-else class="flex flex-col mt-[14rpx]">
            <template v-for="(step, index) in state.steps" :key="step.key">
                <!-- 当前这一步：整个清单里只展开这一个 -->
                <view
                    v-if="step.key === state.currentStepKey"
                    class="flex gap-[20rpx] py-[24rpx]"
                    :class="{ 'setup-item--divided': index > 0 }">
                    <view class="setup-index setup-index--current">{{ index + 1 }}</view>
                    <view class="flex-1 min-w-0 flex flex-col gap-[10rpx]">
                        <view class="flex flex-wrap items-center justify-between gap-[8rpx]">
                            <text class="text-[30rpx] font-semibold text-[#202937]">{{ step.title }}</text>
                            <text v-if="step.progressText" class="text-[24rpx] font-semibold text-[#9B5C08]">
                                {{ step.progressText }}
                            </text>
                        </view>
                        <text class="text-[24rpx] leading-[40rpx] text-[#647184]">{{ step.detail }}</text>

                        <view v-if="step.samples?.length" class="flex flex-wrap items-center gap-[12rpx]">
                            <text class="text-[24rpx] text-content">例如：</text>
                            <text v-for="sample in step.samples" :key="sample" class="setup-sample">
                                {{ sample }}
                            </text>
                        </view>

                        <template v-if="step.options?.length">
                            <view
                                v-for="option in step.options"
                                :key="option.value"
                                class="setup-option active:opacity-80"
                                @click="emit('option', step.key, option.value)">
                                <view
                                    class="setup-radio"
                                    :class="step.currentValue === option.value ? 'setup-radio--on' : ''"></view>
                                <view class="flex-1 min-w-0 flex flex-col gap-[8rpx]">
                                    <view class="flex items-center gap-[12rpx]">
                                        <text class="text-base font-semibold text-main">{{ option.title }}</text>
                                        <text v-if="option.recommended" class="setup-recommend">推荐</text>
                                    </view>
                                    <text class="text-xs leading-[34rpx] text-content">{{ option.desc }}</text>
                                </view>
                            </view>
                        </template>

                        <view v-else class="flex flex-wrap items-center gap-x-[24rpx]">
                            <view class="setup-action" @click="emit('step', step.key)">
                                <text>{{ step.actionText }}</text>
                                <u-icon name="arrow-right" color="#0065FB" size="22"></u-icon>
                            </view>
                            <view
                                v-if="step.key === SetupStepEnum.MATERIAL"
                                class="setup-action setup-action--muted"
                                @click="emit('manage-material')">
                                打开素材库
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 已完成：收成一行，带出实证，让用户看见自己的进展 -->
                <view
                    v-else-if="step.done"
                    class="setup-row"
                    :class="{ 'setup-item--divided': index > 0 }"
                    @click="emit('edit', step.key)">
                    <view class="setup-index setup-index--done">
                        <u-icon name="checkmark" color="#16835E" size="28"></u-icon>
                    </view>
                    <view class="flex-1 min-w-0 flex flex-col gap-[6rpx]">
                        <text class="text-[26rpx] font-medium text-[#202937]">{{ step.title }}</text>
                        <text class="text-[24rpx] text-[#647184] leading-[36rpx] line-clamp-2">
                            {{ step.evidence }}
                        </text>
                    </view>
                    <view class="setup-edit">
                        <text class="text-[24rpx] text-[#527297]">修改</text>
                    </view>
                </view>

                <!-- 还没轮到：只留标题和一句「为什么要做」 -->
                <view
                    v-else
                    class="setup-row"
                    :class="{ 'setup-item--divided': index > 0 }"
                    @click="emit('step', step.key)">
                    <view class="setup-index setup-index--todo">{{ index + 1 }}</view>
                    <view class="flex-1 min-w-0 flex flex-col gap-[6rpx]">
                        <text class="text-sm font-semibold text-main">{{ step.title }}</text>
                        <text class="text-[24rpx] text-content leading-[34rpx]">{{ step.brief }}</text>
                    </view>
                    <u-icon name="arrow-right" color="#C8C9CC" size="26" class="shrink-0"></u-icon>
                </view>
            </template>
        </view>
    </view>
</template>

<script setup lang="ts">
import { SetupStepEnum } from "@/ai_modules/person/enums";
import type { SetupChecklistState } from "../hooks/useSetupChecklist";

const props = defineProps<{
    state: SetupChecklistState;
    expanded: boolean;
}>();

const currentStep = computed(() => props.state.steps.find((step) => step.key === props.state.currentStepKey));

const emit = defineEmits<{
    (e: "update:expanded", value: boolean): void;
    (e: "step", key: SetupStepEnum): void;
    (e: "edit", key: SetupStepEnum): void;
    (e: "option", key: SetupStepEnum, value: number): void;
    (e: "manage-material"): void;
}>();
</script>

<style lang="scss" scoped>
.setup-card {
    @apply bg-white rounded-[36rpx] px-[36rpx] pt-[20rpx] pb-[14rpx] overflow-hidden;
}

.setup-heading {
    @apply flex items-center gap-[16rpx] min-h-[76rpx] pb-[12rpx] active:opacity-70;
}

.setup-count {
    @apply shrink-0 text-[22rpx] leading-[32rpx] text-[#557087] bg-[#F0F4F8] rounded-[10rpx] px-[14rpx] py-[6rpx];
}

.setup-summary {
    @apply flex flex-wrap items-center justify-between gap-[12rpx] pt-[18rpx] pb-[18rpx] text-[24rpx] text-[#647184] active:opacity-70;
}

// 除首项外每项加一条上分隔线；单边边框必须先把四边重置掉，否则小程序端会画满四边
.setup-item--divided {
    @apply border-[0] border-t-[2rpx] border-solid border-[#EDF1F5];
}

.setup-row {
    @apply flex items-center gap-[20rpx] min-h-[108rpx] py-[14rpx] active:opacity-70;
}

.setup-index {
    @apply w-[46rpx] h-[46rpx] rounded-full shrink-0 flex items-center justify-center text-[24rpx] font-bold;
}

.setup-index--current {
    @apply bg-primary text-white;
}

.setup-index--done {
    @apply bg-[#EDF8F3];
}

.setup-index--todo {
    @apply bg-[transparent] text-disabled border-[3rpx] border-solid border-light;
}

// 发布方式这一步本身是道选择题，两张卡各写清自己的代价
.setup-option {
    @apply flex gap-[20rpx] rounded-[26rpx] px-[26rpx] py-[24rpx] border-[2rpx] border-solid border-light;
}

.setup-radio {
    @apply w-[36rpx] h-[36rpx] rounded-full shrink-0 mt-[4rpx] border-[3rpx] border-solid border-disabled;
}

.setup-radio--on {
    @apply border-[10rpx] border-primary;
}

.setup-recommend {
    @apply shrink-0 text-[20rpx] font-semibold text-primary bg-primary-light-9 rounded-[8rpx] px-[12rpx] py-[2rpx];
}

.setup-sample {
    @apply text-[22rpx] text-content bg-[#F2F3F5] rounded-full px-[20rpx] py-[8rpx];
}

.setup-action {
    @apply min-h-[80rpx] flex items-center gap-[8rpx] text-[26rpx] font-semibold text-primary active:opacity-70;
}

.setup-action--muted {
    @apply ml-auto text-[24rpx] font-normal text-[#66788E];
}

// 裸文字点不中，撑到 64rpx 再用负外边距把视觉位置拉回去
.setup-edit {
    @apply min-h-[88rpx] shrink-0 flex items-center px-[16rpx] mr-[-16rpx] active:opacity-60;
}
</style>
