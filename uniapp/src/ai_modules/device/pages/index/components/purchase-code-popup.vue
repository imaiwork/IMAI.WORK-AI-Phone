<template>
    <popup-bottom v-model="showPopup" height="82%" custom-class="bg-white" :clearable="false" :mask-close-able="true">
        <template #header>
            <view class="px-[32rpx] pt-[24rpx]">
                <view class="w-[72rpx] h-[8rpx] bg-[#E5EAF3] rounded-full mx-auto mb-[28rpx]"></view>
                <view class="flex items-center mb-[12rpx]">
                    <text class="flex-1 text-[32rpx] font-bold text-[#1A1A1A]">购买CDK</text>
                    <view
                        class="w-[56rpx] h-[56rpx] bg-[#F4F6FA] rounded-full flex items-center justify-center active:opacity-80"
                        @click="showPopup = false">
                        <u-icon name="close" color="#888888" size="24" />
                    </view>
                </view>
                <text class="block text-[22rpx] text-[#888888]"> CDK购买后长期有效，激活后才计算有效期。 </text>
            </view>
        </template>

        <template #content>
            <view class="h-full flex flex-col">
                <scroll-view scroll-y class="flex-1 min-h-0">
                    <view class="px-[32rpx] pt-[16rpx] pb-[24rpx]">
                        <billing-plan-selector
                            v-model="selectedPlanKey"
                            :plans="displayPlans"
                            scene="activation-code" />
                    </view>
                </scroll-view>

                <view
                    class="shrink-0 px-[32rpx] pt-[24rpx] pb-[calc(32rpx+env(safe-area-inset-bottom))] bg-white border-[0] border-t border-solid border-[#F0F0F0] shadow-[0_-8rpx_32rpx_rgba(15,23,42,0.06)]">
                    <view class="bg-[#F8FAFC] rounded-[24rpx] px-[24rpx] py-[20rpx] flex items-center">
                        <text class="text-[26rpx] text-[#1A1A1A] flex-1">购买数量</text>
                        <view class="flex items-center gap-x-[18rpx]">
                            <view
                                class="w-[60rpx] h-[60rpx] rounded-full bg-white border border-solid border-[#E8ECF0] flex items-center justify-center active:opacity-80"
                                @click="handleChangeCount(-1)">
                                <u-icon name="minus" color="#888888" size="24" />
                            </view>
                            <view
                                class="w-[100rpx] h-[60rpx] rounded-[16rpx] bg-white border border-solid border-[#E8ECF0] px-[8rpx]">
                                <input
                                    v-model="purchaseCountText"
                                    type="number"
                                    maxlength="2"
                                    class="w-full h-full text-center text-[30rpx] font-bold text-[#1A1A1A]"
                                    @input="handleCountInput"
                                    @blur="normalizePurchaseCount" />
                            </view>
                            <view
                                class="w-[60rpx] h-[60rpx] rounded-full bg-primary flex items-center justify-center active:opacity-80"
                                @click="handleChangeCount(1)">
                                <u-icon name="plus" color="#ffffff" size="24" />
                            </view>
                        </view>
                    </view>

                    <view class="mt-[24rpx]">
                        <text class="block text-[22rpx] text-[#888888] mb-[14rpx]">支付方式</text>
                        <view class="grid grid-cols-2 gap-[20rpx]">
                            <view
                                class="pay-card"
                                :class="{ 'pay-card--active': selectedPayType === 'cash' }"
                                @click="selectedPayType = 'cash'">
                                <view v-if="selectedPayType === 'cash'" class="pay-card__check">
                                    <u-icon name="checkmark" color="#ffffff" size="18" />
                                </view>
                                <view class="flex items-center gap-x-[10rpx]">
                                    <image
                                        src="@/ai_modules/device/static/icons/credit_card_primary.svg"
                                        mode="aspectFit"
                                        class="w-[32rpx] h-[32rpx]" />
                                    <text class="text-[26rpx] font-semibold text-[#1A1A1A]">在线支付</text>
                                </view>
                                <text class="block text-[20rpx] text-[#888888] mt-[8rpx]">微信支付</text>
                            </view>
                            <view
                                class="pay-card"
                                :class="{ 'pay-card--active': selectedPayType === 'compute' }"
                                @click="selectedPayType = 'compute'">
                                <view v-if="selectedPayType === 'compute'" class="pay-card__check">
                                    <u-icon name="checkmark" color="#ffffff" size="18" />
                                </view>
                                <view class="flex items-center gap-x-[10rpx]">
                                    <image
                                        src="@/ai_modules/device/static/icons/zap_primary.svg"
                                        mode="aspectFit"
                                        class="w-[32rpx] h-[32rpx]" />
                                    <text class="text-[26rpx] font-semibold text-[#1A1A1A]">算力支付</text>
                                </view>
                                <text class="block text-[20rpx] text-[#888888] mt-[8rpx] line-clamp-1">
                                    当前算力余额 {{ computeBalanceLabel }}
                                </text>
                            </view>
                        </view>
                    </view>

                    <view
                        class="h-[96rpx] rounded-[24rpx] flex items-center justify-center mt-[32rpx] shadow-[0_8rpx_32rpx_rgba(43,110,255,0.28)] active:opacity-85 bg-primary"
                        @click="handleConfirmPurchase">
                        <text class="text-white text-[30rpx] font-semibold">
                            {{ confirmButtonText }}
                        </text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import BillingPlanSelector from "@/ai_modules/device/components/billing-package/billing-plan-selector.vue";
import {
    formatBillingNumber,
    resolveDefaultPlanKey,
    type BillingPayType,
    type PlanItem,
    type PlanKey,
} from "@/ai_modules/device/components/billing-package/billing-plans";
import { purchaseDeviceAuthCode } from "@/api/device_auth";
import { DeviceAuthBizType, DeviceAuthPayType } from "@/ai_modules/device/enums";
import { useDeviceAuthPay } from "@/ai_modules/device/hooks/useDeviceAuthPay";
import { useUserStore } from "@/stores/user";
import { PayStatusEnum } from "@/enums/appEnums";

const MAX_PURCHASE_COUNT = 99;

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        plans?: PlanItem[];
    }>(),
    {
        modelValue: false,
        plans: () => [],
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
    (event: "success"): void;
}>();

const showPopup = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const { isPaying, runOnlinePayment, runComputePayment } = useDeviceAuthPay();
const userStore = useUserStore();

// 接口套餐为空时用静态套餐兜底空态
const displayPlans = computed(() => (props.plans.length ? props.plans : []));

const selectedPlanKey = ref<PlanKey>("");
const selectedPayType = ref<BillingPayType>("cash");
const purchaseCountText = ref("1");

const selectedPlan = computed(
    () => displayPlans.value.find((item) => item.key === selectedPlanKey.value) ?? displayPlans.value[0],
);

const purchaseCount = computed(() => {
    const count = Number.parseInt(purchaseCountText.value, 10);
    if (Number.isNaN(count) || count < 1) return 1;
    return Math.min(count, MAX_PURCHASE_COUNT);
});

const purchaseTotal = computed(() => (selectedPlan.value?.price ?? 0) * purchaseCount.value);

const purchaseComputeTotalLabel = computed(() =>
    formatBillingNumber((selectedPlan.value?.compute ?? 0) * purchaseCount.value),
);

const computeBalanceLabel = computed(() =>
    formatBillingNumber(userStore.userInfo?.userToken ?? userStore.userInfo?.tokens ?? 0),
);

const confirmButtonText = computed(() => {
    if (selectedPayType.value === "compute") {
        return `算力支付 · ${purchaseComputeTotalLabel.value} 算力`;
    }
    return `立即购买 · ¥${purchaseTotal.value}`;
});

const normalizeCountText = (value: string) => value.replace(/\D/g, "").replace(/^0+/, "");

const handleCountInput = (event: any) => {
    const value = String(event?.detail?.value ?? purchaseCountText.value ?? "");
    purchaseCountText.value = normalizeCountText(value);
};

const normalizePurchaseCount = () => {
    purchaseCountText.value = String(purchaseCount.value);
};

const handleChangeCount = (step: number) => {
    const nextCount = Math.min(MAX_PURCHASE_COUNT, Math.max(1, purchaseCount.value + step));
    purchaseCountText.value = String(nextCount);
};

const handleConfirmPurchase = async () => {
    normalizePurchaseCount();
    if (isPaying.value) return;
    if (!selectedPlan.value?.planId) {
        uni.showToast({ title: "请选择套餐", icon: "none" });
        return;
    }
    const isCompute = selectedPayType.value === "compute";
    try {
        const submit = () =>
            purchaseDeviceAuthCode({
                plan_id: selectedPlan.value.planId,
                quantity: purchaseCount.value,
                pay_type: isCompute ? DeviceAuthPayType.COMPUTE : DeviceAuthPayType.CASH,
            });
        const { status } = isCompute
            ? await runComputePayment(submit)
            : await runOnlinePayment({
                  createOrder: submit,
                  virtualPayload: {
                      biz_type: DeviceAuthBizType.PURCHASE,
                      plan_id: selectedPlan.value.planId,
                      quantity: purchaseCount.value,
                      product_id: selectedPlan.value.productId,
                  },
              });
        if (status === PayStatusEnum.SUCCESS) {
            uni.$u.toast("购买成功");
            emit("success");
            showPopup.value = false;
        }
    } catch (error: any) {
        uni.showToast({ title: error || "支付失败", icon: "none" });
    }
};

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) {
            selectedPlanKey.value = resolveDefaultPlanKey(displayPlans.value);
            selectedPayType.value = "cash";
            purchaseCountText.value = "1";
        }
    },
);

// 套餐随弹窗触发异步加载，回来时若当前选中项已失效则重选默认套餐
watch(displayPlans, (plans) => {
    if (!props.modelValue) return;
    if (!plans.some((item) => item.key === selectedPlanKey.value)) {
        selectedPlanKey.value = resolveDefaultPlanKey(plans);
    }
});
</script>

<style scoped lang="scss">
.pay-card {
    @apply relative rounded-[20rpx] border-2 border-solid border-[#F0F0F0] bg-white px-[24rpx] py-[20rpx];
}

.pay-card--active {
    @apply border-primary bg-[#F0F5FF];
}

.pay-card__check {
    @apply absolute top-[12rpx] right-[12rpx] w-[36rpx] h-[36rpx] rounded-full bg-primary flex items-center justify-center;
}
</style>
