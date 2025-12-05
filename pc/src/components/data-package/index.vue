<template>
    <ElDialog
        v-model="showPop"
        width="740px"
        append-to-body
        confirm-button-text=""
        cancel-button-text=""
        class="recharge-popup-wrapper"
        :show-close="false"
        style="border-radius: 16px; overflow: hidden; padding: 0"
        @close="close">
        <div class="flex w-full h-[490px]">
            <div class="w-[370px] flex-shrink-0 recharge-cover">
                <div class="flex flex-col justify-end h-full relative">
                    <div class="absolute top-[180px] w-full flex justify-center">
                        <img src="@/assets/images/dazzle_light.svg" />
                    </div>
                    <div class="h-[200px]">
                        <ElScrollbar>
                            <div class="flex flex-col px-6">
                                <div
                                    v-for="(item, index) in rechargeLists"
                                    :key="index"
                                    class="tokens-item"
                                    :class="[index === packageIndex ? 'active' : '']"
                                    :style="{
                                        backgroundImage:
                                            index == packageIndex
                                                ? `url(${getApiUrl()}/static/images/recharge_tokens_item_active_bg.png)`
                                                : `url(${getApiUrl()}/static/images/recharge_tokens_item_bg.png)`,
                                    }"
                                    @click="handlePackage(index)">
                                    <div
                                        class="flex gap-x-[6px] min-w-[180px] justify-between"
                                        :class="[index === packageIndex ? 'text-[#FF9500]' : 'text-[#9eb4fd80]']">
                                        <div class="font-bold text-lg flex-shrink-0 font-digital-number">
                                            ￥{{ item.price }}
                                        </div>
                                        <div>Tokens/算力</div>
                                    </div>
                                    <div class="flex items-center justify-end flex-1 ml-5">
                                        <div
                                            class="flex items-center border rounded-full px-[2px] relative pr-3 pl-5"
                                            :class="[
                                                index === packageIndex
                                                    ? 'border-[#624E35] bg-[#ff95001a]'
                                                    : 'border-[#16F49F1a] bg-[#16F49F1a]',
                                            ]">
                                            <span class="absolute left-[1px]">
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 16 16"
                                                    fill="none">
                                                    <rect
                                                        width="16"
                                                        height="16"
                                                        rx="8"
                                                        :fill="index === packageIndex ? '#FF9500' : '#16F49F'" />
                                                    <path
                                                        d="M4 8.55556L8.57143 3V7.44444H12L7.42857 13V8.55556H4Z"
                                                        fill="white" />
                                                </svg>
                                            </span>
                                            <text
                                                class="font-bold"
                                                :class="[index === packageIndex ? 'text-[#FF9500]' : 'text-[#16F49F]']">
                                                {{ item.package_info?.tokens || 0 }}
                                            </text>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </ElScrollbar>
                    </div>
                    <div class="my-4 flex justify-center" v-if="getCardCodeConfig.is_open == 1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="178" height="20" viewBox="0 0 178 5" fill="none">
                            <g opacity="0.5">
                                <path opacity="0.05" d="M1 0V5" stroke="#9EB4FD" />
                                <path opacity="0.1" d="M7 0V5" stroke="#9EB4FD" />
                                <path opacity="0.3" d="M13 0V5" stroke="#9EB4FD" />
                                <path opacity="0.5" d="M19 0V5" stroke="#9EB4FD" />
                                <path opacity="0.7" d="M25 0V5" stroke="#9EB4FD" />
                                <path opacity="0.9" d="M31 0V5" stroke="#9EB4FD" />
                                <path d="M37 0V5" stroke="#9EB4FD" />
                                <path d="M43 0V5" stroke="#9EB4FD" />
                                <path d="M49 0V5" stroke="#9EB4FD" />
                            </g>
                            <text
                                x="50%"
                                y="50%"
                                dy="0.3em"
                                font-size="12"
                                fill="#9EB4FD"
                                text-anchor="middle"
                                class="cursor-pointer"
                                @click="handleTab(RechargeTypeEnum.REDEEM)">
                                卡密兑换
                            </text>
                            <g opacity="0.5">
                                <path opacity="0.05" d="M177 0V5" stroke="#9EB4FD" />
                                <path opacity="0.1" d="M171 0V5" stroke="#9EB4FD" />
                                <path opacity="0.3" d="M165 0V5" stroke="#9EB4FD" />
                                <path opacity="0.5" d="M159 0V5" stroke="#9EB4FD" />
                                <path opacity="0.7" d="M153 0V5" stroke="#9EB4FD" />
                                <path opacity="0.9" d="M147 0V5" stroke="#9EB4FD" />
                                <path d="M141 0V5" stroke="#9EB4FD" />
                                <path d="M135 0V5" stroke="#9EB4FD" />
                                <path d="M129 0V5" stroke="#9EB4FD" />
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="grow overflow-hidden relative">
                <div v-if="rechargeType == RechargeTypeEnum.RECHARGE" class="h-full flex flex-col">
                    <div class="grow min-h-0 flex flex-col items-center justify-center">
                        <div class="mt-4 w-[200px] h-[200px] flex items-center justify-center">
                            <template v-if="!payLoading">
                                <template v-if="perCode">
                                    <div class="rounded-2xl border border-token-primary p-1">
                                        <vue-qr :text="perCode" :size="180" class="rounded-[10px]" margin="12" />
                                    </div>
                                </template>
                                <template v-else>
                                    <div
                                        class="flex justify-center items-center gap-2 bg-[#fb0000e0] backdrop-blur-sm p-[10px] rounded-full">
                                        <span class="bg-white rounded-full leading-[0]">
                                            <Icon name="el-icon-CircleClose" color="#fb0000e0" :size="24"></Icon>
                                        </span>
                                        <span class="text-xl text-white">支付配置有误</span>
                                    </div>
                                </template>
                            </template>
                            <template v-else>
                                <div class="py-[32px]" v-loading="payLoading"></div>
                            </template>
                        </div>
                        <div
                            class="mt-5 grid gap-2"
                            :style="{
                                gridTemplateColumns: `repeat(${payWayList.length}, minmax(0, 1fr))`,
                            }">
                            <ElRadioGroup v-model="payWay" fill="#FFC8A3">
                                <div
                                    class="border border-[#00000014] rounded-full px-6 py-1 w-full"
                                    v-for="(item, index) in payWayList">
                                    <ElRadio label="" class="!m-0" :value="item.id">
                                        <div class="flex items-center gap-2">
                                            <img :src="item.icon" class="w-6 h-6" />
                                            <span class="text-black">{{ item.name }}</span>
                                        </div>
                                    </ElRadio>
                                </div>
                            </ElRadioGroup>
                        </div>
                    </div>
                    <div class="text-center my-4 text-xs w-full">
                        支付完成表示您已阅读并接受
                        <router-link
                            class="text-primary"
                            :to="getPolicyUrl(PolicyAgreementEnum.SERVICE)"
                            target="_blank"
                            >服务协议</router-link
                        >和
                        <router-link
                            class="text-primary"
                            :to="getPolicyUrl(PolicyAgreementEnum.PRIVACY)"
                            target="_blank"
                            >隐私协议</router-link
                        >
                    </div>
                </div>
                <div v-if="rechargeType == RechargeTypeEnum.REDEEM" class="redeem-box h-full">
                    <ElTooltip content="返回算力充值" placement="right">
                        <div
                            class="absolute top-[18px] right-[18px] cursor-pointer"
                            @click="
                                rechargeType = RechargeTypeEnum.RECHARGE;
                                start();
                            ">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="40"
                                height="40"
                                viewBox="0 0 40 40"
                                fill="none">
                                <path
                                    opacity="0.05"
                                    d="M0 20C0 8.95431 8.95431 0 20 0C31.0457 0 40 8.95431 40 20C40 31.0457 31.0457 40 20 40C8.95431 40 0 31.0457 0 20Z"
                                    fill="black" />
                                <path
                                    opacity="0.78"
                                    d="M19 15L13.9219 19.2318C13.4421 19.6316 13.4421 20.3684 13.9219 20.7682L19 25M14 20H27"
                                    stroke="black"
                                    stroke-width="1.2" />
                            </svg>
                        </div>
                    </ElTooltip>
                    <div class="h-full px-[30px] relative">
                        <div class="text-[20px] font-bold mt-[68px]">立即兑换</div>
                        <div class="text-[rgba(0,0,0,0.3)] text-xs mt-2">
                            请确认卡密编号，兑换后立即生效，卡密不可再次使用
                        </div>
                        <div class="mt-[20px]">
                            <ElInput
                                v-model="redeemForm.sn"
                                class="!h-[50px]"
                                input-style="font-size: 13px"
                                placeholder="请输入有效卡密编号"></ElInput>
                        </div>
                        <div class="px-2 mt-4">
                            <agreement ref="agreementRef"></agreement>
                        </div>
                        <div>
                            <ElButton
                                type="primary"
                                class="w-full !h-[50px] mt-4 !rounded-full shadow-[0_6px_12px_0_rgba(0,101,251,0.20)]"
                                :loading="isLockQueryRedeem"
                                @click="lockFnQueryRedeem">
                                查询
                            </ElButton>
                        </div>
                        <div class="flex justify-center mt-6">
                            <div class="text-[rgba(0,0,0,0.3)] text-xs leading-7">
                                <p class="num-col"><span>1</span>充值获得的积分只能在本平台使用。</p>
                                <p class="num-col"><span>2</span>若充值未到账，请联系客服。</p>
                                <p class="num-col"><span>3</span>充值获得的为虚拟积分，一般不可退换。</p>
                            </div>
                        </div>
                    </div>
                    <div class="redeem-code-check-pop" v-if="checkVisible">
                        <ElDialog v-model="checkVisible" width="400">
                            <template #header>
                                <div class="text-lg text-center font-medium">查询结果</div>
                            </template>
                            <div class="h-full">
                                <ElFormItem label="卡密面额：">
                                    {{ checkResult.content }}
                                </ElFormItem>
                                <ElFormItem label="兑换时间：">
                                    {{ checkResult.failure_time }}
                                </ElFormItem>
                                <ElFormItem label="有效期至：" v-if="checkResult.valid_time">
                                    {{ checkResult.valid_time }}
                                </ElFormItem>
                            </div>
                            <div class="flex-1 flex justify-center items-center bg-white pt-[20px]">
                                <ElButton
                                    class="w-full"
                                    type="primary"
                                    size="large"
                                    :loading="isUse"
                                    @click="onUseRedeemCode">
                                    立即兑换
                                </ElButton>
                            </div>
                        </ElDialog>
                    </div>
                </div>
            </div>
        </div>
        <ElDialog
            v-model="showPayResult"
            width="400px"
            confirm-button-text=""
            cancel-button-text=""
            style="border-radius: 16px; overflow: hidden; padding: 0; overflow: initial"
            @close="resultClose">
            >
            <div class="px-[15px] pb-[15px]">
                <div class="flex justify-center translate-y-[-14px]">
                    <img src="@/assets/images/recharge_top.png" class="h-[74px]" />
                </div>
                <div class="h-[118px] rounded-lg flex justify-center items-center result-box">
                    <span class="text-[32px] font-bold">算力+{{ getPackage.package_info?.tokens }}</span>
                </div>
                <div class="text-[#7C7C7C] mt-4 text-center">充值已到账，让先用AI的人富起来~</div>
                <div
                    class="mt-4 flex items-center justify-center bg-[#FFC8A3] h-[35px] w-[250px] mx-auto rounded-full cursor-pointer">
                    <div class="font-bold text-[#472716]" @click="resultClose()">立即使用({{ resultCountDown }}s)</div>
                </div>
            </div>
        </ElDialog>
    </ElDialog>
</template>

<script setup lang="ts">
import {
    getRechargeList,
    getPaymentList,
    createRechargeOrder,
    prePay,
    getPayResult,
    checkRedeemCode,
    useRedeemCode,
} from "@/api/recharge";
import { PolicyAgreementEnum } from "@/enums/appEnums";
import VueQr from "vue-qr/src/packages/vue-qr.vue";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { getApiUrl } from "@/utils/env";

const appStore = useAppStore();
const userStore = useUserStore();

const emit = defineEmits(["close"]);

enum RechargeTypeEnum {
    RECHARGE = "recharge",
    REDEEM = "redeem",
}

const getCardCodeConfig = computed(() => {
    return appStore.getCardCodeConfig;
});

const rechargeType = ref<RechargeTypeEnum>(RechargeTypeEnum.RECHARGE);

const handleTab = (key: RechargeTypeEnum) => {
    if (key == rechargeType.value) return;
    if (key == RechargeTypeEnum.REDEEM) {
        end();
    } else {
        start();
    }
    rechargeType.value = key;
};

const showPop = ref(false);
const showPayResult = ref(false);

const getPolicyUrl = (type: PolicyAgreementEnum) => {
    return `/policy/${type}`;
};

const rechargeLists = ref<any[]>([]);
const getRechargeListData = async () => {
    const res = await getRechargeList({ type: 1 });
    rechargeLists.value = res.lists;
};

const payWay = ref(-1);
const payWayList = ref<any[]>([]);

const getPayWayListData = async () => {
    const res = await getPaymentList({
        from: "tokens",
    });
    payWayList.value = res.lists;
    payWay.value = payWayList.value.length ? payWayList.value[0].id : -1;
};

const packageIndex = ref(0);
const getPackage = computed(() => {
    return rechargeLists.value.length ? rechargeLists.value[packageIndex.value] : {};
});
const handlePackage = (index: number) => {
    packageIndex.value = index;
    end();
    handlePay();
};

const perCode = ref<string>("");
const payLoading = ref(true);
const payOrderId = ref("");
const handlePay = async () => {
    if (!showPop.value) return;
    try {
        payLoading.value = true;
        const result = await createRechargeOrder({
            type: 1,
            package_id: rechargeLists.value?.[packageIndex.value]?.id,
        });
        payOrderId.value = result.order_id;
        const data = await prePay({
            order_id: result.order_id,
            from: "tokens",
            pay_way: payWay.value,
        });
        perCode.value = data.config;
        if (showPop.value) {
            start();
        }
    } catch (error) {
        feedback.msgError(error || "支付失败");
    } finally {
        payLoading.value = false;
    }
};

const resultCountDown = ref(10);
const resultTimer = ref<NodeJS.Timeout | null>(null);

const agreementRef = shallowRef();

// 查询支付结果
const check = async () => {
    if (!payOrderId.value) return;
    const { pay_status } = await getPayResult({
        order_id: payOrderId.value,
        from: "tokens",
    });
    if (pay_status == 1) {
        end();
        showPayResult.value = true;
        resultTimer.value = setInterval(() => {
            resultCountDown.value--;
            if (resultCountDown.value <= 0) {
                clearInterval(resultTimer.value);
                window.location.reload();
            }
        }, 1000);
    }
};
const endCallback = async () => {
    await feedback.alertWarning("支付超时！");
    start();
};

const cancelPay = () => {
    payWay.value = -1;
    payOrderId.value = "";
    clearInterval(resultTimer.value);
    end();
};

//轮询参数
const { start, end } = usePolling(check, {
    totalTime: 5 * 60 * 1000,
    callback: endCallback,
});

const redeemForm = reactive({
    sn: "",
});
// 显示查询结果
const checkVisible = ref<boolean>(false);
const checkResult = ref<any>({});
const { lockFn: lockFnQueryRedeem, isLock: isLockQueryRedeem } = useLockFn(async () => {
    if (!redeemForm.sn) {
        feedback.msgError("卡密编号不能为空");
        return;
    }
    if (!(await agreementRef.value?.checkAgreement())) {
        return;
    }
    try {
        const data = await checkRedeemCode({ sn: redeemForm.sn });
        checkVisible.value = true;
        checkResult.value = data;
    } catch (error) {
        feedback.msgError(error || "查询失败");
    }
});

const { lockFn: onUseRedeemCode, isLock: isUse } = useLockFn(async () => {
    try {
        await useRedeemCode({ sn: redeemForm.sn });
        feedback.msgSuccess("兑换成功");
        checkVisible.value = false;
        redeemForm.sn = "";
        showPop.value = false;
        await userStore.getUser();
    } catch (error) {
        feedback.msgError(error || "兑换失败");
    }
});

const loading = ref(false);

const close = () => {
    cancelPay();
    emit("close");
};

const resultClose = () => {
    showPayResult.value = false;
    window.location.reload();
};

const open = async () => {
    showPop.value = true;
    loading.value = true;
    try {
        await getPayWayListData();
        await getRechargeListData();
        handlePay();
    } finally {
        loading.value = false;
    }
};

defineExpose({
    open,
});
</script>

<style lang="scss" scoped>
.recharge-cover {
    background-image: url("@/assets/images/recharge_cover.png");
    background-color: #000000;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center -40px;
}

.tokens-item {
    @apply flex-shrink-0 w-full h-[61px] rounded-lg flex items-center cursor-pointer relative px-[13px];
    background-size: 100% 100%;
    background-repeat: no-repeat;
    background-position: center;
}

.result-box {
    background-image: url("@/assets/images/recharge_result_bg.png");
    background-size: contain;
    background-repeat: no-repeat;
}
.redeem-box {
    :deep(.el-input__wrapper) {
        background-color: #00000005;
        border: 1px solid transparent;
        border-radius: 9999px;
        box-shadow: none;
        padding-left: 18px;
        padding-right: 18px;
    }
    :deep(.el-input__inner) {
        &::placeholder {
            color: rgba(0, 0, 0, 0.2);
        }
    }
    :deep() {
        .el-input__wrapper.is-focus {
            box-shadow: 0px 0px 0px 2px rgba(0, 101, 251, 0.2);
            border-color: var(--color-primary);
            background: rgba(0, 101, 251, 0.03);
        }
    }
}

.num-col {
    @apply flex items-center gap-2;
    span {
        @apply flex items-center justify-center w-[18px] h-[18px] rounded-full bg-[rgba(0,0,0,0.05)];
    }
}
</style>
<style lang="scss">
.recharge-popup-wrapper {
    .el-dialog__header {
        padding: 0 !important;
    }
    .el-dialog__headerbtn {
        z-index: 88888;
    }
}
</style>
