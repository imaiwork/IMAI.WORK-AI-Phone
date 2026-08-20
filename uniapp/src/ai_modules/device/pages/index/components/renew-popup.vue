<template>
    <popup-bottom
        v-model="showPopup"
        height="78%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @close="handleClosePopup">
        <template #header>
            <view class="px-[32rpx] pt-[24rpx]">
                <view class="w-[72rpx] h-[8rpx] bg-[#E5EAF3] rounded-full mx-auto mb-[28rpx]"></view>
                <view class="flex items-center mb-[28rpx]">
                    <view
                        v-if="renewStep === 2"
                        class="w-[64rpx] h-[64rpx] -ml-[8rpx] rounded-[18rpx] flex items-center justify-center active:bg-[#F4F6FA]"
                        @click="renewStep = 1">
                        <u-icon name="arrow-left" color="#1A1A1A" size="28" />
                    </view>
                    <text class="flex-1 text-[32rpx] font-bold text-[#1A1A1A]">
                        {{ renewStep === 1 ? popupTitle : "选择支付方式" }}
                    </text>
                    <view
                        class="w-[56rpx] h-[56rpx] bg-[#F4F6FA] rounded-full flex items-center justify-center active:opacity-80"
                        @click="showPopup = false">
                        <u-icon name="close" color="#888888" size="24" />
                    </view>
                </view>
            </view>
        </template>

        <template #content>
            <view class="h-full flex flex-col">
                <scroll-view scroll-y class="flex-1 min-h-0">
                    <view class="px-[32rpx] pt-[8rpx] pb-[24rpx]">
                        <template v-if="renewStep === 1">
                            <billing-device-selector
                                v-model="selectedDeviceCode"
                                :devices="props.devices"
                                :fallback-device="props.device"
                                :mode="deviceSelectMode"
                                :title="deviceSelectorTitle"
                                :show-add-button="props.devices.length > 0"
                                @add-device="handleAddDevice" />
                            <view class="mt-[28rpx]">
                                <billing-plan-selector v-model="selectedPlanKey" :plans="displayPlans" />
                            </view>
                        </template>

                        <template v-else>
                            <view
                                class="bg-[#F8FAFC] rounded-[28rpx] px-[24rpx] py-[22rpx] flex items-center gap-x-[20rpx]">
                                <view
                                    class="w-[72rpx] h-[72rpx] rounded-[20rpx] bg-[#EBF2FF] flex items-center justify-center">
                                    <image
                                        src="@/ai_modules/device/static/icons/calendar_check.svg"
                                        mode="aspectFit"
                                        class="w-[34rpx] h-[34rpx]" />
                                </view>
                                <view class="flex-1">
                                    <text class="block text-[26rpx] font-semibold text-[#1A1A1A]">
                                        {{ selectedPlan.name }} · {{ selectedPlan.daysText }}
                                    </text>
                                    <text class="block text-[22rpx] text-[#888888] mt-[4rpx]">
                                        ¥{{ selectedPlan.price }} / 或 {{ selectedPlan.computeLabel }} 算力
                                    </text>
                                    <text class="block text-[22rpx] text-[#888888] mt-[4rpx] line-clamp-1">
                                        设备：{{ selectedDeviceName }}
                                    </text>
                                </view>
                                <text class="text-[22rpx] font-semibold text-primary" @click="renewStep = 1">更换</text>
                            </view>

                            <view class="mt-[24rpx]">
                                <billing-pay-methods v-model="selectedPayType" :selected-plan="selectedPlan" />
                            </view>
                        </template>
                    </view>
                </scroll-view>

                <view
                    class="shrink-0 px-[32rpx] pt-[24rpx] pb-[calc(32rpx+env(safe-area-inset-bottom))] bg-white border-[0] border-t border-solid border-[#F0F0F0] shadow-[0_-8rpx_32rpx_rgba(15,23,42,0.06)]">
                    <template v-if="renewStep === 1">
                        <view class="flex items-center justify-between mb-[14rpx]">
                            <text class="block text-[22rpx] font-semibold text-[#888888]">兑换码</text>
                            <view
                                class="h-[48rpx] px-[18rpx] rounded-full bg-[#EBF2FF] flex items-center gap-x-[6rpx] active:opacity-80"
                                @click="showCodePickerPopup = true">
                                <image
                                    src="@/ai_modules/device/static/icons/ticket.svg"
                                    mode="aspectFit"
                                    class="w-[24rpx] h-[24rpx]" />
                                <text class="text-[22rpx] font-semibold text-primary">选择已有</text>
                            </view>
                        </view>
                        <view class="flex gap-x-[14rpx]">
                            <view class="flex-1 bg-[#F8FAFC] border border-[#E8ECF0] rounded-[20rpx] px-[20rpx]">
                                <u-input
                                    v-model="redeemCode"
                                    placeholder="请输入兑换码"
                                    maxlength="32"
                                    :border="false"
                                    height="76"
                                    placeholder-style="color:#BBBBBB;font-size:24rpx;" />
                            </view>
                            <view
                                class="w-[150rpx] rounded-[20rpx] bg-primary flex items-center justify-center active:opacity-80"
                                @click="handleRedeemCode">
                                <text class="text-white text-[24rpx] font-semibold">立即兑换</text>
                            </view>
                        </view>

                        <view class="flex justify-center mt-[24rpx] mb-[24rpx]">
                            <view
                                class="flex items-center gap-x-[8rpx] active:opacity-70"
                                @click="handleShowCustomerService">
                                <image
                                    src="@/ai_modules/device/static/icons/headphones.svg"
                                    mode="aspectFit"
                                    class="w-[26rpx] h-[26rpx]" />
                                <text class="text-[24rpx] text-[#888888]">联系客服</text>
                            </view>
                        </view>

                        <view
                            class="h-[96rpx] rounded-[28rpx] flex items-center justify-center"
                            :class="
                                hasPlans
                                    ? 'bg-primary shadow-[0_8rpx_32rpx_rgba(43,110,255,0.28)] active:opacity-85'
                                    : 'bg-[#C8D2E0]'
                            "
                            @click="handleNextStep">
                            <text class="text-white text-[30rpx] font-semibold">下一步</text>
                        </view>
                    </template>

                    <view
                        v-else
                        class="h-[96rpx] bg-primary rounded-[28rpx] flex items-center justify-center shadow-[0_8rpx_32rpx_rgba(43,110,255,0.28)] active:opacity-85"
                        @click="handleConfirmPay">
                        <text class="text-white text-[30rpx] font-semibold"> 确认支付 · {{ confirmPayText }} </text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <u-popup v-model="showCustomerServicePopup" mode="center" width="82%" border-radius="32">
        <view class="bg-white rounded-[32rpx] p-[40rpx] text-center">
            <text class="block text-[30rpx] font-bold text-[#1A1A1A]">联系客服</text>
            <text class="block text-[24rpx] text-[#888888] mt-[8rpx]">扫码添加客服微信</text>
            <view
                class="w-[320rpx] h-[320rpx] bg-[#F8FAFC] rounded-[28rpx] border border-[#F0F0F0] flex items-center justify-center mx-auto my-[32rpx]">
                <image
                    v-if="serviceQrcode"
                    :src="serviceQrcode"
                    show-menu-by-longpress
                    mode="aspectFill"
                    class="w-[280rpx] h-[280rpx] rounded-[20rpx]" />
                <image
                    v-else
                    src="@/ai_modules/device/static/icons/qr_code.svg"
                    mode="aspectFit"
                    class="w-[120rpx] h-[120rpx] opacity-60" />
            </view>
            <view
                class="h-[88rpx] rounded-[24rpx] bg-[#F4F6FA] flex items-center justify-center active:opacity-80"
                @click="showCustomerServicePopup = false">
                <text class="text-[26rpx] font-semibold text-[#1A1A1A]">关闭</text>
            </view>
        </view>
    </u-popup>

    <u-popup v-model="showCodePickerPopup" mode="bottom" border-radius="32" height="62%">
        <view class="h-full bg-white rounded-t-[32rpx] flex flex-col">
            <view class="px-[32rpx] pt-[28rpx] pb-[22rpx] border-b border-solid border-[#F0F0F0]">
                <view class="w-[72rpx] h-[8rpx] bg-[#E5EAF3] rounded-full mx-auto mb-[28rpx]"></view>
                <view class="flex items-center justify-between">
                    <view>
                        <text class="block text-[30rpx] font-bold text-[#1A1A1A]">选择已有CDK</text>
                        <text class="block text-[22rpx] text-[#888888] mt-[6rpx]">
                            未使用 {{ unusedActivationCodes.length }} 张
                        </text>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] bg-[#F4F6FA] rounded-full flex items-center justify-center active:opacity-80"
                        @click="showCodePickerPopup = false">
                        <u-icon name="close" color="#888888" size="24" />
                    </view>
                </view>
            </view>

            <scroll-view scroll-y class="flex-1 min-h-0">
                <view class="px-[24rpx] py-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                    <view v-if="unusedActivationCodes.length > 0" class="flex flex-col gap-y-[18rpx]">
                        <view
                            v-for="code in unusedActivationCodes"
                            :key="code.code"
                            class="rounded-[24rpx] border border-solid border-[#E8ECF0] bg-white overflow-hidden active:bg-[#F7F9FF]"
                            @click="handleSelectActivationCode(code)">
                            <view
                                class="px-[24rpx] py-[18rpx] bg-[#F7F9FF] border-[0] border-b border-solid border-[#F0F0F0] flex items-center justify-between gap-x-[16rpx]">
                                <view class="px-[18rpx] py-[4rpx] rounded-full bg-[#EBF2FF]">
                                    <text class="text-[22rpx] font-bold text-primary">
                                        {{ code.planName }} · {{ code.daysLabel }}
                                    </text>
                                </view>
                                <view class="px-[16rpx] py-[4rpx] rounded-full bg-[#F0FBE8]">
                                    <text class="text-[20rpx] font-bold text-[#52C41A]">● 未使用</text>
                                </view>
                            </view>
                            <view class="px-[24rpx] py-[20rpx] flex items-center gap-x-[18rpx]">
                                <image
                                    src="@/ai_modules/device/static/icons/ticket.svg"
                                    mode="aspectFit"
                                    class="w-[44rpx] h-[44rpx] shrink-0" />
                                <view class="flex-1 min-w-0">
                                    <text class="block text-[26rpx] font-semibold text-[#1A1A1A] line-clamp-1">
                                        {{ code.code }}
                                    </text>
                                    <text class="block text-[20rpx] text-[#BBBBBB] mt-[6rpx]">
                                        购买于 {{ code.buyTime || "-" }}
                                    </text>
                                </view>
                                <u-icon name="arrow-right" color="#BBBBBB" size="22" />
                            </view>
                        </view>
                    </view>

                    <view v-else class="pt-[100rpx] flex flex-col items-center">
                        <image
                            src="@/ai_modules/device/static/icons/ticket.svg"
                            mode="aspectFit"
                            class="w-[110rpx] h-[110rpx] opacity-60" />
                        <text class="text-[28rpx] font-semibold text-[#1A1A1A] mt-[24rpx]"> 暂无未使用CDK </text>
                        <text class="text-[22rpx] text-[#888888] mt-[8rpx]"> 购买后可以在这里选择并兑换 </text>
                        <view
                            class="mt-[28rpx] h-[76rpx] px-[36rpx] rounded-full bg-primary flex items-center justify-center"
                            @click="handlePurchaseActivationCode">
                            <text class="text-white text-[24rpx] font-semibold">去购买</text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import BillingDeviceSelector from "@/ai_modules/device/components/billing-package/billing-device-selector.vue";
import BillingPlanSelector from "@/ai_modules/device/components/billing-package/billing-plan-selector.vue";
import BillingPayMethods from "@/ai_modules/device/components/billing-package/billing-pay-methods.vue";
import {
    isDeviceActivated,
    resolveDefaultPlanKey,
    type ActivationCodeItem,
    type BillingPayType,
    type PlanItem,
    type PlanKey,
} from "@/ai_modules/device/components/billing-package/billing-plans";
import { redeemDeviceCode } from "@/api/device";
import { renewDeviceAuth } from "@/api/device_auth";
import { getAgentUserParentQrcode } from "@/api/user";
import { DeviceAuthBizType, DeviceAuthPayType } from "@/ai_modules/device/enums";
import { useDeviceAuthPay } from "@/ai_modules/device/hooks/useDeviceAuthPay";
import { PayStatusEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";

interface BillingDeviceItem {
    id?: number | string;
    device_code?: string;
    device_name?: string;
    status?: number;
    is_active?: boolean | number | string;
    is_activated?: boolean | number | string;
    activate_status?: boolean | number | string;
    activation_status?: boolean | number | string;
    active_status?: boolean | number | string;
    is_permanent?: boolean | number;
    plan_type?: string | number;
    card_type?: string | number;
    package_type?: string | number;
    package_name?: string;
    [key: string]: any;
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        device?: BillingDeviceItem;
        devices?: BillingDeviceItem[];
        plans?: PlanItem[];
        activationCodes?: ActivationCodeItem[];
    }>(),
    {
        modelValue: false,
        device: () => ({}),
        devices: () => [],
        plans: () => [],
        activationCodes: () => [],
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
    (event: "purchase-code"): void;
    (event: "add-device"): void;
    (event: "success"): void;
}>();

const showPopup = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const { isPaying, runOnlinePayment, runComputePayment } = useDeviceAuthPay();

// 接口套餐为空时用静态套餐兜底空态
const displayPlans = computed(() => (props.plans.length ? props.plans : []));

// 套餐为空时禁用「下一步」，仅保留兑换码激活通道
const hasPlans = computed(() => displayPlans.value.length > 0);

const renewStep = ref(1);
const selectedPlanKey = ref<PlanKey>("");
const selectedPayType = ref<BillingPayType>("cash");
const selectedDeviceCode = ref("");
const redeemCode = ref("");
const isActivating = ref(false);
const showCustomerServicePopup = ref(false);
const showCodePickerPopup = ref(false);

const appStore = useAppStore();
// 上级代理二维码优先，其次取网站配置的客服微信二维码，与 user 页面同源
const agentParentQrcode = ref("");
const serviceQrcode = computed(
    () => agentParentQrcode.value || appStore.getWebsiteConfig?.customer_service?.wx_image || "",
);

const getAgentParentQrcode = async () => {
    try {
        const res = await getAgentUserParentQrcode();
        agentParentQrcode.value = res?.qr_code || "";
    } catch {
        agentParentQrcode.value = "";
    }
};

const selectedPlan = computed(
    () => displayPlans.value.find((item) => item.key === selectedPlanKey.value) ?? displayPlans.value[0],
);

const confirmPayText = computed(() => {
    if (selectedPayType.value === "compute") {
        return `${selectedPlan.value.computeLabel} 算力`;
    }
    return `¥${selectedPlan.value.price}`;
});

const selectedDevice = computed(() => {
    const fallbackCode = props.device?.device_code || "";
    const deviceCode = selectedDeviceCode.value || fallbackCode;
    return (
        props.devices.find((item) => item.device_code === deviceCode) ||
        (fallbackCode === deviceCode ? props.device : undefined)
    );
});

const hasSelectedDevice = computed(() => !!(selectedDeviceCode.value || props.device?.device_code));

const deviceSelectMode = computed(() =>
    hasSelectedDevice.value && isDeviceActivated(selectedDevice.value ?? {}) ? "renew" : "activation",
);

const deviceSelectorTitle = computed(() =>
    deviceSelectMode.value === "renew" ? "选择 / 确认续费设备" : "选择 / 确认激活设备",
);

const selectedDeviceName = computed(
    () => selectedDevice.value?.device_name || (hasSelectedDevice.value ? "未命名设备" : "请选择设备"),
);

const popupTitle = computed(() => (deviceSelectMode.value === "renew" ? "选择续费套餐" : "选择激活套餐"));

const isUnusedActivationCode = (item: ActivationCodeItem) => {
    const value = item.status ?? item.use_status ?? item.used_status ?? item.activate_status;
    if (typeof value === "number") return value === 0;
    if (typeof value === "boolean") return !value;
    return ["unused", "not_used", "unactivated", "0", "未使用", "未激活"].includes(`${value}`);
};

const unusedActivationCodes = computed(() => props.activationCodes?.filter(isUnusedActivationCode));

const handleClosePopup = () => {
    renewStep.value = 1;
    showCodePickerPopup.value = false;
};

const ensureSelectedDevice = () => {
    if (!hasSelectedDevice.value) {
        uni.showToast({ title: "请先选择设备", icon: "none" });
        return false;
    }
    return true;
};

const handleNextStep = () => {
    if (!hasPlans.value) return;
    if (!ensureSelectedDevice()) return;
    renewStep.value = 2;
};

const handleRedeemCode = async () => {
    if (!ensureSelectedDevice()) return;
    if (!redeemCode.value.trim()) {
        uni.showToast({ title: "请输入兑换码", icon: "none" });
        return;
    }
    if (isActivating.value) return;
    isActivating.value = true;
    uni.showLoading({ title: "激活中" });
    try {
        await redeemDeviceCode({
            cdk_code: redeemCode.value.trim(),
            device_code: selectedDeviceCode.value || props.device?.device_code || "",
        });
        uni.hideLoading();
        uni.$u.toast("激活成功");
        emit("success");
        showPopup.value = false;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "激活失败", icon: "none" });
    } finally {
        isActivating.value = false;
    }
};

const handleConfirmPay = async () => {
    if (!ensureSelectedDevice()) return;
    if (isPaying.value) return;
    if (!selectedPlan.value?.planId) {
        uni.showToast({ title: "请选择套餐", icon: "none" });
        return;
    }
    const isCompute = selectedPayType.value === "compute";
    try {
        const submit = () =>
            renewDeviceAuth({
                plan_id: selectedPlan.value.planId,
                pay_type: isCompute ? DeviceAuthPayType.COMPUTE : DeviceAuthPayType.CASH,
                device_id: selectedDevice.value?.id ?? "",
                device_code: selectedDeviceCode.value || props.device?.device_code || "",
            });
        const { status } = isCompute
            ? await runComputePayment(submit)
            : await runOnlinePayment({
                  createOrder: submit,
                  virtualPayload: {
                      biz_type: DeviceAuthBizType.RENEW,
                      plan_id: selectedPlan.value.planId,
                      quantity: 1,
                      device_id: selectedDevice.value?.id ?? "",
                      device_code: selectedDeviceCode.value || props.device?.device_code || "",
                      product_id: selectedPlan.value.productId,
                  },
              });
        if (status === PayStatusEnum.SUCCESS) {
            uni.$u.toast("支付成功");
            emit("success");
            showPopup.value = false;
        }
    } catch (error: any) {
        uni.showToast({ title: error || "支付失败", icon: "none" });
    }
};

const handleShowCustomerService = () => {
    showPopup.value = false;
    showCustomerServicePopup.value = true;
    if (!agentParentQrcode.value) {
        getAgentParentQrcode();
    }
};

const handleSelectActivationCode = (code: ActivationCodeItem) => {
    redeemCode.value = code.code;
    showCodePickerPopup.value = false;
    uni.showToast({ title: "已选择CDK", icon: "none" });
};

const handlePurchaseActivationCode = () => {
    showCodePickerPopup.value = false;
    showPopup.value = false;
    emit("purchase-code");
};

const handleAddDevice = () => {
    showPopup.value = false;
    emit("add-device");
};

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) {
            renewStep.value = 1;
            selectedPlanKey.value = resolveDefaultPlanKey(displayPlans.value);
            selectedPayType.value = "cash";
            selectedDeviceCode.value = props.device?.device_code || "";
            redeemCode.value = "";
            showCodePickerPopup.value = false;
        } else {
            showCodePickerPopup.value = false;
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
