<template>
    <ElDialog
        v-model="show"
        width="720px"
        append-to-body
        :show-close="false"
        style="border-radius: 16px; overflow: hidden; padding: 0"
        @close="handleClose">
        <div class="flex flex-col max-h-[80vh]">
            <div class="px-7 pt-6 pb-4 flex items-start justify-between border-b border-br">
                <div>
                    <div class="text-[18px] font-[900] text-[#1E293B]">
                        {{ title }}
                    </div>
                    <div class="text-[13px] text-[#94A3B8] mt-1">
                        {{ device.device_name || "未命名设备" }} ·
                        {{ device.device_code }}
                    </div>
                </div>
                <div class="w-8 h-8" @click="show = false">
                    <close-btn />
                </div>
            </div>

            <!-- 内部切换 -->
            <div class="px-7 pt-4">
                <div class="inline-flex bg-[#F1F5F9] rounded-xl p-1">
                    <button
                        class="tab-btn"
                        :class="{ 'tab-btn--active': activeTab === 'plan' }"
                        @click="switchTab('plan')">
                        套餐{{ isRenew ? "续费" : "激活" }}
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ 'tab-btn--active': activeTab === 'code' }"
                        @click="switchTab('code')">
                        CDK激活
                    </button>
                </div>
            </div>

            <div class="grow min-h-0 overflow-y-auto px-7 py-5">
                <!-- 套餐续费/激活 -->
                <template v-if="activeTab === 'plan'">
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
                            <div class="text-[24px] font-[900] text-[#1E293B] leading-tight mt-1">
                                ￥{{ plan.price }}
                            </div>
                            <div class="text-[12px] text-[#94A3B8] mt-0.5">
                                {{ getPlanDurationText(plan) }}
                            </div>
                            <div class="text-[12px] font-bold text-primary mt-1">
                                或 {{ formatTokens(plan.tokens_price) }} 算力
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center py-16">
                        <Icon name="el-icon-Box" color="#CBD5E1" :size="48" />
                        <div class="text-[15px] font-semibold text-[#1E293B] mt-3">暂无可用套餐</div>
                        <div class="text-[13px] text-[#94A3B8] mt-1">请稍后再试或使用CDK激活</div>
                    </div>

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

                    <div v-if="showQr" class="mt-5 flex flex-col items-center">
                        <div class="rounded-2xl border border-token-primary p-2" v-loading="payLoading">
                            <vue-qr v-if="perCode" :text="perCode" :size="180" class="rounded-[10px]" :margin="10" />
                            <div
                                v-else
                                class="w-[180px] h-[180px] flex items-center justify-center text-[#94A3B8] text-sm">
                                二维码生成失败
                            </div>
                        </div>
                        <div class="text-[14px] text-[#1E293B] mt-3">
                            请使用微信扫码支付
                            <span class="text-primary font-bold">￥{{ selectedPlan?.price }}</span>
                        </div>
                    </div>
                </template>

                <!-- CDK激活 -->
                <template v-else>
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-[14px] text-[#64748B]">输入未使用的CDK，激活后立即生效。</div>
                        <ElButton link type="primary" class="!font-bold" @click="handleOpenCodePicker">
                            选择已有
                        </ElButton>
                    </div>
                    <ElInput v-model="redeemCode" class="!h-[46px]" placeholder="请输入CDK，如 CARD-12345678-ABCD" clearable />
                    <ElButton
                        type="primary"
                        class="w-full !h-11 !rounded-xl mt-4 !font-bold"
                        :loading="activating"
                        @click="handleActivate">
                        立即激活
                    </ElButton>
                </template>
            </div>

            <div class="px-7 py-4 border-t border-br" v-if="activeTab === 'plan'">
                <ElButton
                    v-if="!showQr"
                    type="primary"
                    class="w-full !h-11 !rounded-xl !font-bold"
                    :disabled="!selectedPlan"
                    @click="handleConfirm">
                    {{ confirmText }}
                </ElButton>
                <ElButton v-else class="w-full !h-11 !rounded-xl" @click="backToForm">返回修改</ElButton>
            </div>
        </div>
    </ElDialog>

    <ElDialog
        v-model="showCodePicker"
        width="680px"
        append-to-body
        :show-close="false"
        style="border-radius: 16px; overflow: hidden; padding: 0">
        <div class="flex flex-col max-h-[70vh]">
            <div class="px-7 pt-6 pb-4 flex items-start justify-between border-b border-br">
                <div>
                    <div class="text-[18px] font-[900] text-[#1E293B]">选择已有CDK</div>
                    <div class="text-[13px] text-[#94A3B8] mt-1">未使用 {{ unusedCodes.length }} 张</div>
                </div>
                <div class="w-8 h-8" @click="showCodePicker = false">
                    <close-btn />
                </div>
            </div>
            <div class="grow min-h-0 px-7 py-4">
                <ElTable v-loading="codesLoading" :data="unusedCodes" height="360px" empty-text="暂无未使用CDK">
                    <ElTableColumn label="CDK" min-width="220">
                        <template #default="{ row }">
                            <span class="font-mono text-[13px] font-bold text-[#1E293B]">{{ getCode(row) }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="套餐" min-width="120">
                        <template #default="{ row }">{{ getCodePlanName(row) }}</template>
                    </ElTableColumn>
                    <ElTableColumn label="购买时间" min-width="160">
                        <template #default="{ row }">{{ row.purchase_time || "-" }}</template>
                    </ElTableColumn>
                    <ElTableColumn label="操作" width="90" align="right">
                        <template #default="{ row }">
                            <ElButton link type="primary" class="!font-bold" @click="handleSelectCode(row)">
                                选择
                            </ElButton>
                        </template>
                    </ElTableColumn>
                </ElTable>
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import VueQr from "vue-qr/src/packages/vue-qr.vue";
import { redeemDeviceCode } from "@/api/device";
import { getDeviceAuthMyCodes, getDeviceAuthPlanList, renewDeviceAuth } from "@/api/device_auth";
import {
    DeviceAuthPayType,
    isDeviceActivated,
    isDeviceExpired,
    getPlanDurationText,
    formatTokens,
    getPlanTypeLabel,
    type DeviceAuthPlan,
} from "../_enums/deviceAuthEnums";
import { useDeviceAuthScanPay } from "../_hooks/useDeviceAuthScanPay";

const props = defineProps<{
    device: Record<string, any>;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const show = ref(true);
const activeTab = ref<"plan" | "code">("plan");

const plans = ref<DeviceAuthPlan[]>([]);
const selectedPlanId = ref<number | string>("");
const payMethod = ref<"cash" | "compute">("cash");
const showQr = ref(false);
const redeemCode = ref("");
const activating = ref(false);
const showCodePicker = ref(false);
const codesLoading = ref(false);
const codeList = ref<Record<string, any>[]>([]);

const { payWayList, payWay, perCode, payLoading, getPayWayList, createAndPay, runComputePayment, onPaid, reset } =
    useDeviceAuthScanPay();

// 已激活或已过期都属于"续费"场景；仅未激活才是"激活"
const isRenew = computed(() => isDeviceActivated(props.device) || isDeviceExpired(props.device));
const title = computed(() => (isRenew.value ? "设备续费" : "设备激活"));

const selectedPlan = computed(() => plans.value.find((item) => item.id === selectedPlanId.value));
const unusedCodes = computed(() => codeList.value.filter((item) => !isCodeUsed(item) && getCode(item) !== "-"));

const confirmText = computed(() =>
    payMethod.value === "compute"
        ? `算力支付 · ${formatTokens(Number(selectedPlan.value?.tokens_price ?? 0))} 算力`
        : `立即${isRenew.value ? "续费" : "激活"} · ￥${selectedPlan.value?.price ?? 0}`,
);

const selectCash = (id: number) => {
    payMethod.value = "cash";
    payWay.value = id;
};

const switchTab = (tab: "plan" | "code") => {
    if (tab === activeTab.value) return;
    activeTab.value = tab;
    if (tab === "code") {
        showQr.value = false;
        reset();
    }
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

const getCode = (row: Record<string, any>) => row.code || row.card_no || row.card_code || row.sn || "-";

const isCodeUsed = (row: Record<string, any>) => {
    const value = row.status ?? row.use_status ?? row.used_status ?? row.activate_status;
    if (typeof value === "number") return value === 1;
    if (typeof value === "boolean") return value;
    if (typeof value === "string") return ["1", "used", "activated", "已使用", "已激活"].includes(value);
    return !!row.use_time;
};

const getCodePlanName = (row: Record<string, any>) =>
    row.type_desc || row.auth_type_name || row.plan_name || row.name || getPlanTypeLabel(row.type ?? row.auth_type) || "-";

const getCodeList = async () => {
    codesLoading.value = true;
    try {
        const res = await getDeviceAuthMyCodes();
        codeList.value = res?.lists || res || [];
    } catch {
        codeList.value = [];
    } finally {
        codesLoading.value = false;
    }
};

const handleOpenCodePicker = async () => {
    showCodePicker.value = true;
    if (!codeList.value.length) await getCodeList();
};

const handleSelectCode = (row: Record<string, any>) => {
    redeemCode.value = getCode(row);
    showCodePicker.value = false;
};

const handleConfirm = async () => {
    if (!selectedPlan.value) {
        feedback.msgError("请选择套餐");
        return;
    }
    const isCompute = payMethod.value === "compute";
    const submit = () =>
        renewDeviceAuth({
            plan_id: selectedPlan.value!.id,
            pay_type: isCompute ? DeviceAuthPayType.COMPUTE : DeviceAuthPayType.CASH,
            device_id: props.device.id ?? "",
            device_code: props.device.device_code ?? "",
        });
    if (isCompute) {
        // 算力支付：接口成功即扣减算力完成，无需扫码
        const ok = await runComputePayment(submit);
        if (ok) {
            feedback.msgSuccess(isRenew.value ? "续费成功" : "激活成功");
            emit("success");
            show.value = false;
        }
        return;
    }
    const ok = await createAndPay(submit);
    if (ok) showQr.value = true;
};

const handleActivate = async () => {
    if (!redeemCode.value.trim()) {
        feedback.msgError("请输入CDK");
        return;
    }
    activating.value = true;
    try {
        await redeemDeviceCode({
            cdk_code: redeemCode.value.trim(),
            device_code: props.device.device_code ?? "",
        });
        feedback.msgSuccess("激活成功");
        emit("success");
        show.value = false;
    } catch (error: any) {
        feedback.msgError(error || "激活失败");
    } finally {
        activating.value = false;
    }
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
    feedback.msgSuccess(isRenew.value ? "续费成功" : "激活成功");
    emit("success");
    show.value = false;
});

onMounted(async () => {
    await getPayWayList();
    await getPlanList();
    getCodeList();
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
.tab-btn {
    @apply px-5 py-1.5 rounded-lg text-[14px] font-medium text-[#64748B] transition-all;
    &--active {
        @apply bg-white text-primary font-bold;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }
}
</style>
