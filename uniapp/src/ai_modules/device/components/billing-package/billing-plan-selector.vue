<template>
    <view v-if="plans.length" class="grid grid-cols-2 gap-[20rpx]">
        <view
            v-for="plan in plans"
            :key="plan.key"
            class="billing-plan-card"
            :class="{ 'billing-plan-card--active': modelValue === plan.key }"
            @click="handleSelectPlan(plan)">
            <view
                v-if="plan.recommend"
                class="absolute top-0 right-0 bg-[#FF8C00] text-white text-[16rpx] font-bold px-[16rpx] py-[4rpx] rounded-bl-[18rpx] rounded-tr-[18rpx]">
                推荐
            </view>
            <view
                v-if="modelValue === plan.key"
                class="absolute top-[14rpx] right-[14rpx] w-[36rpx] h-[36rpx] rounded-full bg-primary flex items-center justify-center">
                <u-icon name="checkmark" color="#ffffff" size="22" />
            </view>
            <text class="block text-[22rpx] font-semibold text-[#888888] mb-[8rpx]">
                {{ getPlanTitle(plan) }}
            </text>
            <text class="block text-[44rpx] leading-[56rpx] font-bold text-[#1A1A1A]"> ¥{{ plan.price }} </text>
            <text class="block text-[20rpx] text-[#888888] mt-[4rpx]">
                {{ getPlanDesc(plan) }}
            </text>
            <text v-if="showCompute" class="block text-[20rpx] text-primary mt-[10rpx]">
                或 {{ plan.computeLabel }} 算力
            </text>
        </view>
    </view>

    <view v-else class="py-[80rpx] flex flex-col items-center">
        <image
            src="@/ai_modules/device/static/icons/statement.svg"
            mode="aspectFit"
            class="w-[110rpx] h-[110rpx] opacity-60" />
        <text class="text-[28rpx] font-semibold text-[#1A1A1A] mt-[24rpx]">暂无可用套餐</text>
        <text class="text-[22rpx] text-[#888888] mt-[8rpx]">请稍后再试或联系客服</text>
    </view>
</template>

<script setup lang="ts">
import { type PlanItem, type PlanKey } from "./billing-plans";

type PlanScene = "renew" | "activation-code";

const props = withDefaults(
    defineProps<{
        modelValue: PlanKey;
        plans?: PlanItem[];
        scene?: PlanScene;
        titleSuffix?: string;
        showCompute?: boolean;
    }>(),
    {
        plans: () => [],
        scene: "renew",
        titleSuffix: "",
        showCompute: true,
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: PlanKey): void;
    (event: "change", value: PlanItem): void;
}>();

const getPlanTitle = (plan: PlanItem) => `${plan.name}${props.titleSuffix}`;

const getPlanDesc = (plan: PlanItem) => {
    if (props.scene === "activation-code") {
        return `激活后 ${plan.activationText}`;
    }
    return plan.validText;
};

const handleSelectPlan = (plan: PlanItem) => {
    emit("update:modelValue", plan.key);
    emit("change", plan);
};
</script>

<style scoped lang="scss">
.billing-plan-card {
    @apply relative rounded-[28rpx] border-2 border-solid border-[#F0F0F0] px-[24rpx] py-[22rpx] bg-white overflow-hidden;
}

.billing-plan-card--active {
    @apply border-primary bg-[#F0F5FF];
}
</style>
