<template>
    <ElDialog
        v-model="show"
        width="760px"
        append-to-body
        :show-close="false"
        style="border-radius: 16px; overflow: hidden; padding: 0"
        @close="handleClose">
        <div class="flex flex-col max-h-[80vh]">
            <div class="px-7 pt-6 pb-4 flex items-start justify-between border-b border-br">
                <div>
                    <div class="text-[18px] font-[900] text-[#1E293B]">购买CDK</div>
                    <div class="text-[13px] text-[#94A3B8] mt-1">CDK购买后长期有效，激活后才计算有效期。</div>
                </div>
                <div class="w-8 h-8" @click="show = false">
                    <close-btn />
                </div>
            </div>

            <div class="grow min-h-0 overflow-y-auto px-7 py-5">
                <!-- 套餐选择 -->
                <div v-if="plans.length" class="grid grid-cols-3 gap-3">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="plan-card"
                        :class="{
                            'plan-card--active': selectedPlanId === plan.id,
                        }"
                        @click="selectedPlanId = plan.id">
                        <div v-if="plan.is_recommend" class="plan-recommend">推荐</div>
                        <div
                            v-if="selectedPlanId === plan.id"
                            class="absolute top-2 right-2 w-5 h-5 rounded-full bg-primary flex items-center justify-center">
                            <Icon name="el-icon-Check" color="#fff" :size="12" />
                        </div>
                        <div class="text-[13px] font-bold text-[#64748B]">
                            {{ plan.name }}
                        </div>
                        <div class="text-[24px] font-[900] text-[#1E293B] leading-tight mt-1">￥{{ plan.price }}</div>
                        <div class="text-[12px] text-[#94A3B8] mt-0.5">激活后{{ getPlanDurationText(plan) }}</div>
                        <div class="text-[12px] font-bold text-primary mt-1">
                            或 {{ formatTokens(plan.tokens_price) }} 算力
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center py-16">
                    <Icon name="el-icon-Box" color="#CBD5E1" :size="48" />
                    <div class="text-[15px] font-semibold text-[#1E293B] mt-3">暂无可购买套餐</div>
                    <div class="text-[13px] text-[#94A3B8] mt-1">请稍后再试或联系客服</div>
                </div>

                <!-- 购买数量 -->
                <div
                    v-if="plans.length"
                    class="mt-5 flex items-center justify-between bg-[#F8FAFC] rounded-2xl px-5 py-3">
                    <span class="text-[14px] font-medium text-[#1E293B]">购买数量</span>
                    <div class="flex items-center gap-4">
                        <button class="qty-btn" @click="changeCount(-1)">−</button>
                        <input
                            :value="purchaseCount"
                            type="text"
                            inputmode="numeric"
                            class="qty-input"
                            @input="handleCountInput"
                            @blur="normalizeCount" />
                        <button class="qty-btn" @click="changeCount(1)">+</button>
                    </div>
                </div>

                <!-- 支付方式 -->
                <div v-if="plans.length" class="mt-5">
                    <div class="text-[13px] text-[#94A3B8] mb-2">支付方式</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div
                            v-for="way in payWayList"
                            :key="way.id"
                            class="pay-card"
                            :class="{
                                'pay-card--active': payMethod === 'cash' && payWay === way.id,
                            }"
                            @click="selectCash(way.id)">
                            <img v-if="way.icon" :src="way.icon" class="w-6 h-6" />
                            <Icon v-else name="el-icon-Money" color="#10B981" :size="20" />
                            <span class="text-[14px] font-medium text-[#1E293B]">{{ way.name }}</span>
                        </div>
                        <div
                            class="pay-card"
                            :class="{
                                'pay-card--active': payMethod === 'compute',
                            }"
                            @click="payMethod = 'compute'">
                            <Icon name="el-icon-Coin" color="#FF8C00" :size="20" />
                            <span class="text-[14px] font-medium text-[#1E293B]">算力支付</span>
                        </div>
                    </div>
                </div>

                <!-- 扫码区 -->
                <div v-if="showQr" class="mt-5 flex flex-col items-center">
                    <div class="rounded-2xl border border-token-primary p-2" v-loading="payLoading">
                        <vue-qr v-if="perCode" :text="perCode" :size="180" class="rounded-[10px]" :margin="10" />
                        <div v-else class="w-[180px] h-[180px] flex items-center justify-center text-[#94A3B8] text-sm">
                            二维码生成失败
                        </div>
                    </div>
                    <div class="text-[14px] text-[#1E293B] mt-3">
                        请使用微信扫码支付
                        <span class="text-primary font-bold">￥{{ totalPrice }}</span>
                    </div>
                </div>
            </div>

            <div class="px-7 py-4 border-t border-br">
                <ElButton
                    v-if="!showQr"
                    type="primary"
                    class="w-full !h-11 !rounded-xl !font-bold"
                    :disabled="!selectedPlan"
                    @click="handleConfirm">
                    {{ confirmText }}
                </ElButton>
                <ElButton v-else class="w-full !h-11 !rounded-xl" @click="backToForm"> 返回修改 </ElButton>
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import VueQr from "vue-qr/src/packages/vue-qr.vue";
import { getDeviceAuthPlanList, purchaseDeviceAuthCode } from "@/api/device_auth";
import { DeviceAuthPayType, getPlanDurationText, formatTokens, type DeviceAuthPlan } from "../_enums/deviceAuthEnums";
import { useDeviceAuthScanPay } from "../_hooks/useDeviceAuthScanPay";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const show = ref(true);

const plans = ref<DeviceAuthPlan[]>([]);
const selectedPlanId = ref<number | string>("");
const purchaseCount = ref(1);
const payMethod = ref<"cash" | "compute">("cash");
const showQr = ref(false);

const { payWayList, payWay, perCode, payLoading, getPayWayList, createAndPay, runComputePayment, onPaid, reset } =
    useDeviceAuthScanPay();

const selectedPlan = computed(() => plans.value.find((item) => item.id === selectedPlanId.value));

const totalPrice = computed(() => {
    const price = Number(selectedPlan.value?.price ?? 0);
    return (price * purchaseCount.value).toFixed(2).replace(/\.00$/, "");
});

const totalTokens = computed(() => formatTokens(Number(selectedPlan.value?.tokens_price ?? 0) * purchaseCount.value));

const confirmText = computed(() =>
    payMethod.value === "compute" ? `算力支付 · ${totalTokens.value} 算力` : `立即购买 · ￥${totalPrice.value}`,
);

const selectCash = (id: number) => {
    payMethod.value = "cash";
    payWay.value = id;
};

const MAX_PURCHASE_COUNT = 99;

const changeCount = (step: number) => {
    purchaseCount.value = Math.min(MAX_PURCHASE_COUNT, Math.max(1, purchaseCount.value + step));
};

// 仅允许正整数；编辑中允许暂时为空，失焦时兜底为 1
const handleCountInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const digits = input.value.replace(/\D/g, "").replace(/^0+/, "");
    const next = digits ? Math.min(MAX_PURCHASE_COUNT, Number(digits)) : 0;
    purchaseCount.value = next;
    input.value = next ? String(next) : "";
};

const normalizeCount = () => {
    if (purchaseCount.value < 1) purchaseCount.value = 1;
};

const getPlanList = async () => {
    try {
        const res = await getDeviceAuthPlanList();
        const list: DeviceAuthPlan[] = (res?.lists || res || [])
            .filter((item: any) => Number(item.status) !== 0)
            .sort((a: any, b: any) => Number(a.sort) - Number(b.sort));
        plans.value = list;
        const recommended = list.find((item) => item.is_recommend) || list[0];
        selectedPlanId.value = recommended?.id ?? "";
    } catch {
        plans.value = [];
    }
};

const handleConfirm = async () => {
    if (!selectedPlan.value) {
        feedback.msgError("请选择套餐");
        return;
    }
    const isCompute = payMethod.value === "compute";
    const submit = () =>
        purchaseDeviceAuthCode({
            plan_id: selectedPlan.value!.id,
            quantity: purchaseCount.value,
            pay_type: isCompute ? DeviceAuthPayType.COMPUTE : DeviceAuthPayType.CASH,
        });
    if (isCompute) {
        // 算力支付：接口成功即扣减算力完成，无需扫码
        const ok = await runComputePayment(submit);
        if (ok) {
            feedback.msgSuccess("购买成功");
            emit("success");
            show.value = false;
        }
        return;
    }
    const ok = await createAndPay(submit);
    if (ok) showQr.value = true;
};

const backToForm = () => {
    showQr.value = false;
    reset();
};

const handleClose = () => {
    reset();
    emit("close");
};

onPaid(() => {
    feedback.msgSuccess("购买成功");
    emit("success");
    show.value = false;
});

onMounted(async () => {
    await getPayWayList();
    await getPlanList();
});
</script>

<style scoped lang="scss">
.plan-card {
    @apply relative rounded-2xl border-2 border-[#F0F0F0] bg-white px-4 py-3 cursor-pointer transition-all overflow-hidden;
    &--active {
        @apply border-primary bg-[#F0F5FF];
    }
}
.plan-recommend {
    @apply absolute top-0 right-0 bg-[#FF8C00] text-white text-[10px] font-bold px-2 py-0.5;
    border-bottom-left-radius: 12px;
}
.pay-card {
    @apply rounded-xl border-2 border-[#F0F0F0] bg-white px-4 py-3 flex items-center gap-2 cursor-pointer transition-all;
    &--active {
        @apply border-primary bg-[#F0F5FF];
    }
}
.qty-btn {
    @apply w-8 h-8 rounded-full bg-white border border-[#E2E8F0] text-[18px] font-bold text-[#64748B] flex items-center justify-center hover:border-primary;
}
.qty-input {
    @apply w-12 h-8 text-center text-[16px] font-bold text-[#1E293B] bg-white border border-[#E2E8F0] rounded-lg outline-none focus:border-primary;
}
</style>
