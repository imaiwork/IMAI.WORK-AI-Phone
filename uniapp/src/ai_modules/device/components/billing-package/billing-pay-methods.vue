<template>
    <view class="flex flex-col gap-y-[18rpx]">
        <view
            v-for="pay in payOptions"
            :key="pay.key"
            class="billing-pay-card"
            :class="{ 'billing-pay-card--active': modelValue === pay.key }"
            @click="handleSelectPay(pay.key)">
            <view class="billing-pay-radio">
                <view v-if="modelValue === pay.key" class="billing-pay-radio__dot"></view>
            </view>
            <view
                class="w-[72rpx] h-[72rpx] rounded-[20rpx] flex items-center justify-center"
                :style="{ background: pay.iconBg }">
                <image :src="pay.icon" mode="aspectFit" class="w-[34rpx] h-[34rpx]" />
            </view>
            <view class="flex-1 min-w-0">
                <text class="block text-[28rpx] font-semibold text-[#1A1A1A]">
                    {{ pay.title }}
                </text>
                <text class="block text-[22rpx] text-[#888888] mt-[4rpx] line-clamp-1">
                    {{ getPayDesc(pay.key) }}
                </text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatBillingNumber, type BillingPayType, type PlanItem } from "./billing-plans";
import { useUserStore } from "@/stores/user";
import ZapIcon from "@/ai_modules/device/static/icons/zap.svg";
import CreditCardIcon from "@/ai_modules/device/static/icons/credit_card.svg";

const props = withDefaults(
    defineProps<{
        modelValue: BillingPayType;
        selectedPlan: PlanItem;
        count?: number;
        computeBalanceText?: string;
        cashPayText?: string;
    }>(),
    {
        count: 1,
        computeBalanceText: "",
        cashPayText: "微信",
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: BillingPayType): void;
    (event: "change", value: BillingPayType): void;
}>();

const payOptions = [
    {
        key: "compute" as BillingPayType,
        title: "算力支付",
        icon: ZapIcon,
        iconBg: "#FFF3E0",
    },
    {
        key: "cash" as BillingPayType,
        title: "付费支付",
        icon: CreditCardIcon,
        iconBg: "#F0FBE8",
    },
];

const userStore = useUserStore();

const totalPrice = computed(() => props.selectedPlan.price * props.count);

const totalCompute = computed(() => props.selectedPlan.compute * props.count);

const computeBalanceText = computed(() => {
    const info = userStore.userInfo;
    const balance =
        props.computeBalanceText ||
        (info && info.userToken != null
            ? info.userToken
            : info && info.tokens != null
              ? info.tokens
              : 0);
    return formatBillingNumber(balance);
});

const getPayDesc = (type: BillingPayType) => {
    if (type === "compute") {
        return `余额 ${computeBalanceText.value} 点 · 扣除 ${formatBillingNumber(totalCompute.value)} 点`;
    }
    return `${props.cashPayText} · 应付 ¥${totalPrice.value}`;
};

const handleSelectPay = (type: BillingPayType) => {
    emit("update:modelValue", type);
    emit("change", type);
};
</script>

<style scoped lang="scss">
.billing-pay-card {
    @apply rounded-[24rpx] border-2 border-solid border-[#F0F0F0] px-[24rpx] py-[22rpx] flex items-center gap-x-[20rpx] bg-white;
}

.billing-pay-card--active {
    @apply border-primary bg-[#F0F5FF];
}

.billing-pay-radio {
    @apply w-[36rpx] h-[36rpx] rounded-full border-2 border-solid border-[#D0D0D0] flex items-center justify-center shrink-0;
}

.billing-pay-card--active .billing-pay-radio {
    @apply border-primary bg-primary;
}

.billing-pay-radio__dot {
    @apply w-[16rpx] h-[16rpx] rounded-full bg-white;
}
</style>
