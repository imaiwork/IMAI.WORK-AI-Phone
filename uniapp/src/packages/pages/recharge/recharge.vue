<template>
    <view class="h-screen flex flex-col relative bg-[#F4F6FA]">
        <u-navbar
            :border-bottom="true"
            :is-fixed="false"
            :background="{ background: '#FFFFFF' }"
            title="算力中心"
            title-bold>
        </u-navbar>

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-[32rpx] pt-[28rpx] pb-[180rpx]">
                    <view class="recharge-card overflow-hidden">
                        <view class="balance-panel px-[40rpx] pt-[38rpx] pb-[34rpx]">
                            <view class="text-[22rpx] font-semibold text-white opacity-80">当前算力余额</view>
                            <view class="mt-[20rpx] flex items-end justify-between gap-[24rpx]">
                                <view class="min-w-0">
                                    <view class="flex items-baseline gap-[10rpx]">
                                        <text class="text-[60rpx] font-bold leading-none text-white">
                                            {{ userTokens }}
                                        </text>
                                        <text class="text-[26rpx] font-semibold text-white opacity-80">算力</text>
                                    </view>
                                    <view class="mt-[14rpx] flex items-center gap-[8rpx]">
                                        <u-icon name="clock" color="rgba(255,255,255,.72)" size="24"></u-icon>
                                        <text class="text-[22rpx] text-white opacity-75"
                                            >购买后实时到账，余额全端共享</text
                                        >
                                    </view>
                                </view>
                                <view class="balance-icon">
                                    <image
                                        src="@/packages/static/icons/compute_power_white.svg"
                                        class="compute-icon-large"></image>
                                </view>
                            </view>
                        </view>

                        <view class="action-row" @click="handleOpenBalance">
                            <view class="action-icon">
                                <u-icon name="order" color="#2B6EFF" size="34"></u-icon>
                            </view>
                            <view class="min-w-0 flex-1">
                                <text class="block text-[26rpx] font-semibold text-[#1A1A1A]">账单明细</text>
                                <text class="mt-[4rpx] block text-[21rpx] text-[#8A94A6]">查看每次充值与到账记录</text>
                            </view>
                            <u-icon name="arrow-right" color="#B7C0CE" size="24"></u-icon>
                        </view>

                        <view class="action-row border-top" @click="showRules = true">
                            <view class="action-icon">
                                <u-icon name="question-circle" color="#2B6EFF" size="34"></u-icon>
                            </view>
                            <view class="min-w-0 flex-1">
                                <text class="block text-[26rpx] font-semibold text-[#1A1A1A]">算力规则</text>
                                <text class="mt-[4rpx] block text-[21rpx] text-[#8A94A6]"
                                    >了解使用限制、到账与退款说明</text
                                >
                            </view>
                            <u-icon name="arrow-right" color="#B7C0CE" size="24"></u-icon>
                        </view>
                    </view>

                    <!-- OEM 站点：不展示套餐购买，仅联系管理员二维码 + 兑换码 -->
                    <view v-if="isOemSite" class="mt-[24rpx]">
                        <view class="recharge-card px-[32rpx] py-[36rpx]">
                            <oem-recharge-panel />
                        </view>
                    </view>

                    <view v-else-if="rechargeClosed" class="mt-[24rpx]">
                        <view class="recharge-card px-[32rpx] py-[36rpx]">
                            <view class="flex items-center gap-[20rpx]">
                                <view class="service-icon">
                                    <u-icon name="server-fill" color="#2B6EFF" size="42"></u-icon>
                                </view>
                                <view class="min-w-0 flex-1">
                                    <text class="block text-[30rpx] font-bold text-[#1A1A1A]">
                                        {{ isIOS() ? "IOS" : "Android" }}端暂不支持在线充值
                                    </text>
                                    <text class="mt-[8rpx] block text-[24rpx] leading-[38rpx] text-[#7B8494]">
                                        请长按二维码添加客服，由客服协助完成充值。
                                    </text>
                                </view>
                            </view>
                            <view v-if="getServerConfig.qrcode" class="mt-[32rpx] flex justify-center">
                                <view class="service-qrcode-wrap">
                                    <image
                                        :src="getServerConfig.qrcode"
                                        show-menu-by-longpress
                                        class="service-qrcode"
                                        mode="aspectFill">
                                    </image>
                                </view>
                            </view>
                            <view class="mt-[34rpx]">
                                <view v-for="(item, index) in rechargeTips" :key="item" class="tip-row">
                                    <text class="tip-index">{{ index + 1 }}</text>
                                    <text class="text-[24rpx] text-[#7B8494]">{{ item }}</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <template v-else>
                        <view class="mt-[32rpx]">
                            <view class="mb-[20rpx] flex items-center justify-between px-[4rpx]">
                                <text class="text-[26rpx] font-bold text-[#6B7280]">选择套餐</text>
                                <text v-if="rechargeLoading" class="text-[22rpx] text-[#9CA3AF]">加载中</text>
                            </view>

                            <view class="grid grid-cols-2 gap-[20rpx]">
                                <view
                                    v-for="(item, index) in rechargeLists"
                                    :key="item.id"
                                    class="pkg-card"
                                    :class="{ selected: currRechargeId == item.id }"
                                    @click="handleRecharge(item.id)">
                                    <text
                                        class="block truncate text-[24rpx] font-semibold leading-[34rpx] text-[#1A1A1A]">
                                        {{ getPackageName(item) }}
                                    </text>
                                    <text class="mt-[14rpx] block text-[44rpx] font-bold leading-none text-[#1A1A1A]">
                                        {{ getPackageTokens(item) }}
                                    </text>
                                    <text class="mt-[8rpx] block text-[22rpx] text-[#8A94A6]">算力点数</text>
                                    <text class="mt-[22rpx] block text-[34rpx] font-bold text-primary">
                                        ￥{{ item.price }}
                                    </text>
                                    <view
                                        v-if="currRechargeId == item.id"
                                        class="absolute bottom-[22rpx] right-[22rpx] flex h-[34rpx] w-[34rpx] items-center justify-center rounded-full bg-primary">
                                        <u-icon name="checkbox-mark" color="#FFFFFF" size="22"></u-icon>
                                    </view>
                                </view>
                            </view>

                            <view v-if="!rechargeLoading && rechargeLists.length === 0" class="empty-card">
                                <text class="text-[26rpx] font-semibold text-[#1A1A1A]">暂无可购买套餐</text>
                                <text class="mt-[8rpx] text-[23rpx] text-[#8A94A6]">请稍后再试或联系客服处理</text>
                            </view>
                        </view>

                        <view class="notice-row">
                            <u-icon name="info-circle" color="#2B6EFF" size="26"></u-icon>
                            <text class="flex-1 text-[22rpx] leading-[34rpx] text-[#2B6EFF]">
                                充值获得的算力仅限本平台使用，虚拟商品一般不可退换。
                            </text>
                        </view>

                        <navigator
                            v-if="cardCodeConfig.is_open == 1"
                            url="/packages/pages/redeem/redeem"
                            hover-class="none"
                            class="recharge-card mt-[20rpx] block overflow-hidden active:opacity-80">
                            <view class="action-row">
                                <view class="action-icon">
                                    <u-icon name="coupon" color="#2B6EFF" size="34"></u-icon>
                                </view>
                                <view class="min-w-0 flex-1">
                                    <text class="block text-[26rpx] font-semibold text-[#1A1A1A]">卡密兑换</text>
                                    <text class="mt-[4rpx] block text-[21rpx] text-[#8A94A6]"
                                        >已有激活码？点击输入兑换</text
                                    >
                                </view>
                                <u-icon name="arrow-right" color="#B7C0CE" size="24"></u-icon>
                            </view>
                        </navigator>

                        <view class="mt-[24rpx] flex items-start">
                            <u-checkbox v-model="isAgreement" shape="circle" size="30"> </u-checkbox>
                            <view class="-ml-2 flex flex-wrap text-[22rpx] leading-[36rpx] text-[#7B8494]">
                                点击<text class="text-primary">兑换</text>或<text class="text-primary">充值</text>
                                即表示您已了解并接受
                                <navigator
                                    class="text-primary"
                                    hover-class="none"
                                    url="/packages/pages/agreement/agreement?type=service">
                                    《充值规则协议》
                                </navigator>
                            </view>
                        </view>
                    </template>
                </view>
            </scroll-view>
        </view>

        <!-- #ifdef MP-WEIXIN -->
        <view v-if="!isOemSite && !rechargeClosed && !showRules" class="pay-footer">
            <u-button
                type="primary"
                shape="circle"
                :loading="isLock"
                :disabled="!getRechargeData"
                :custom-style="{
                    height: '96rpx',
                    fontSize: '28rpx',
                    fontWeight: 700,
                    boxShadow: '0 8rpx 28rpx rgba(43,110,255,0.28)',
                }"
                @click="handlePay">
                {{ selectedPayText }}
            </u-button>
        </view>
        <!-- #endif -->
    </view>

    <popup-bottom v-model="showRules" title="算力规则" custom-class="bg-[#F9FAFB]" :mask-close-able="true">
        <template #content>
            <view class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-[32rpx] pb-[40rpx]">
                        <view class="rule-intro">
                            <view class="flex items-center gap-[12rpx]">
                                <image
                                    src="@/packages/static/icons/compute_power_primary.svg"
                                    class="compute-icon-small"></image>
                                <text class="text-[27rpx] font-bold text-[#2B6EFF]">{{ powerIntro.title }}</text>
                            </view>
                            <text class="mt-[14rpx] block text-[24rpx] leading-[40rpx] text-[#2B6EFF] opacity-80">
                                {{ powerIntro.desc }}
                            </text>
                        </view>

                        <view class="mt-[24rpx] rounded-[24rpx] bg-white px-[28rpx]">
                            <view v-for="item in powerRules" :key="item.title" class="rule-row">
                                <view class="rule-dot"></view>
                                <view class="min-w-0 flex-1">
                                    <text class="block text-[26rpx] font-semibold text-[#1A1A1A]">{{
                                        item.title
                                    }}</text>
                                    <text class="mt-[6rpx] block text-[24rpx] leading-[38rpx] text-[#7B8494]">
                                        {{ item.desc }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="mt-[24rpx] rounded-[24rpx] bg-white px-[28rpx]">
                            <view
                                v-for="(item, index) in powerFaqs"
                                :key="item.title"
                                class="faq-row"
                                :class="{ 'border-top': index > 0 }">
                                <text class="block text-[26rpx] font-semibold text-[#1A1A1A]">{{ item.title }}</text>
                                <text class="mt-[8rpx] block text-[24rpx] leading-[38rpx] text-[#7B8494]">
                                    {{ item.desc }}
                                </text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script lang="ts" setup>
import { getRechargeList, getPaymentList } from "@/api/recharge";
import { getAgentUserParentQrcode } from "@/api/user";
import { useLockFn } from "@/hooks/useLockFn";
import { useRechargePay } from "@/hooks/useRechargePay";
import { PayStatusEnum } from "@/enums/appEnums";
import { MnpPayType } from "@/enums/payEnums";
import { useUserStore } from "@/stores/user";
import { isAndroid, isIOS } from "@/utils/client";
import { useAppStore } from "@/stores/app";
import OemRechargePanel from "@/components/oem-recharge-panel/oem-recharge-panel.vue";

const appStore = useAppStore();

const userStore = useUserStore();
const { runPayment } = useRechargePay();

const userTokens = computed(() => userStore.userTokens);
const cardCodeConfig = computed(() => appStore.getCardCodeConfig);
const rechargeConfig = computed(() => appStore.getRechargeConfig);
const isOemSite = computed(() => appStore.isOemSite);
/** 小程序支付模式：1普通微信 2虚拟支付 */
const mnpPayType = computed(() => Number(rechargeConfig.value?.mnp_pay_type ?? MnpPayType.WECHAT));
const getServerConfig = computed(() => {
    const { customer_service } = appStore.getWebsiteConfig;
    return {
        qrcode: agentUserParentQrcode.value || customer_service?.wx_image,
    };
});

const getRechargeData = computed(() => {
    return rechargeLists.value.find((item: any) => item.id == currRechargeId.value);
});

const rechargeClosed = computed(() => {
    return (
        (isIOS() && rechargeConfig.value?.is_ios_open == 0) || (isAndroid() && rechargeConfig.value?.is_and_open == 0)
    );
});

const selectedPayText = computed(() => {
    if (!getRechargeData.value) return "请选择套餐";
    return `立即充值 · ￥${getRechargeData.value.price}`;
});

const rechargeTips = [
    "充值获得的算力只能在本平台使用",
    "若充值未到账，请联系客服",
    "充值获得的为虚拟算力，一般不可退换",
];

const powerIntro = {
    title: "什么是算力？",
    desc: "算力是驱动 AI 数字员工持续运作的能量单位。算力耗尽后 AI 员工将暂停工作，充值后立即恢复。",
};

const powerRules = [
    {
        title: "每台 AI 手机",
        desc: "每日消耗 1 算力，不论是否有任务运行",
    },
    {
        title: "任务执行加速",
        desc: "高频发帖、批量私信等操作额外消耗 0.5 算力/次",
    },
    {
        title: "算力不过期",
        desc: "购买的算力永久有效，账号删除时自动清零",
    },
    {
        title: "多设备共享",
        desc: "算力余额在名下所有 AI 手机之间自动共享，无需分配",
    },
];

const powerFaqs = [
    {
        title: "算力耗尽后会怎样？",
        desc: "AI 手机进入休眠状态，已有的聊天记录和人设不会丢失。充值后系统自动唤醒，无需重新配置。",
    },
    {
        title: "可以退款吗？",
        desc: "虚拟商品一经购买不支持退款，请按需购买。如遇充值异常请联系客服处理。",
    },
    {
        title: "卡密可以转赠吗？",
        desc: "卡密未兑换前可以分享给他人使用，一旦兑换立即绑定当前账号，不可再次使用。",
    },
];

const getPackageTokens = (item: any) => {
    return item?.package_info?.tokens ?? item?.tokens ?? 0;
};

const getPackageName = (item: any) => {
    const name = String(item?.name ?? "").trim();
    return name || "算力套餐";
};

const isAgreement = ref<boolean>(true);
const rechargeLoading = ref<boolean>(true);

const rechargeLists = ref<any[]>([]);
const getRechargeLists = async () => {
    try {
        const { lists } = await getRechargeList({ type: 1 });
        getPayWayListData();
        rechargeLists.value = lists || [];
        if (lists && lists.length) {
            currRechargeId.value = lists[0].id;
        }
    } finally {
        rechargeLoading.value = false;
    }
};

const currRechargeId = ref<number>(-1);

const handleRecharge = (id: number) => {
    currRechargeId.value = id;
};

const handleOpenBalance = () => {
    uni.$u.route({
        url: "/packages/pages/user_balance/user_balance",
    });
};

const payFrom = "tokens";
const payWay = ref(-1);
const payWayList = ref<any[]>([]);

const getPayWayListData = async () => {
    const { lists } = await getPaymentList({
        from: payFrom,
    });
    if (lists && lists.length) {
        payWayList.value = lists;
        // 优先用业务支付方式枚举 pay_way，兼容旧字段 id
        const first = payWayList.value[0];
        payWay.value = Number(first.pay_way ?? first.id);
    }
};

const { isLock, lockFn: handlePay } = useLockFn(async () => {
    if (!isAgreement.value) {
        uni.$u.toast("请先阅读并同意充值规则协议");
        return;
    }
    if (!getRechargeData.value) {
        uni.$u.toast("请选择充值套餐");
        return;
    }
    // 虚拟支付不依赖支付方式列表；普通支付需已选支付方式
    if (mnpPayType.value !== MnpPayType.VIRTUAL && payWay.value === -1) {
        uni.$u.toast("暂无可用支付方式");
        return;
    }
    try {
        const res: PayStatusEnum = await runPayment({
            pkg: getRechargeData.value,
            mnpPayType: mnpPayType.value,
            payWay: payWay.value,
        });
        handlePayResult(res);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "支付失败",
            icon: "none",
            duration: 3000,
        });
    }
});

const handlePayResult = (status: PayStatusEnum) => {
    switch (status) {
        case PayStatusEnum.SUCCESS:
            uni.$u.toast("购买成功");
            userStore.getUser();
            break;
        case PayStatusEnum.FAIL:
            break;
    }
};

const showRules = ref<boolean>(false);

const agentUserParentQrcode = ref("");
const getAgentParentQrcode = async () => {
    const res = await getAgentUserParentQrcode();
    agentUserParentQrcode.value = res.qr_code;
};

getAgentParentQrcode();
if (!isOemSite.value) {
    getRechargeLists();
} else {
    rechargeLoading.value = false;
}
onShow(async () => {
    userStore.getUser();
    // OEM 状态可能尚未拉到，补一次
    if (!Object.keys(appStore.oem || {}).length) {
        await appStore.getOem().catch(() => undefined);
    }
    if (!isOemSite.value) {
        // 刷新支付模式（普通微信 / 虚拟支付）
        appStore.getConfig().catch(() => undefined);
    }
});
</script>

<style lang="scss" scoped>
.recharge-card {
    border-radius: 32rpx;
    background: #ffffff;
    box-shadow: 0 8rpx 32rpx rgba(18, 33, 61, 0.07);
}

.balance-panel {
    background: linear-gradient(135deg, #2b6eff 0%, #1a50d9 100%);
}

.balance-icon {
    @apply flex h-[112rpx] w-[112rpx] flex-shrink-0 items-center justify-center rounded-full;
    background: rgba(255, 255, 255, 0.13);
}

.compute-icon-large {
    @apply h-[78rpx] w-[78rpx];
}

.compute-icon-small {
    @apply h-[38rpx] w-[38rpx] flex-shrink-0;
}

.action-row {
    @apply flex items-center gap-[22rpx] px-[40rpx] py-[28rpx];
}

.action-icon,
.service-icon {
    @apply flex flex-shrink-0 items-center justify-center;
    background: #ebf2ff;
}

.action-icon {
    @apply h-[64rpx] w-[64rpx] rounded-[20rpx];
}

.service-icon {
    @apply h-[76rpx] w-[76rpx] rounded-[24rpx];
}

.border-top {
    border-top: 2rpx solid #f0f2f6;
}

.pkg-card {
    @apply relative min-h-[248rpx] overflow-hidden rounded-[28rpx] bg-white px-[28rpx] py-[30rpx];
    border: 3rpx solid #e8ecf0;
    box-shadow: 0 6rpx 24rpx rgba(18, 33, 61, 0.06);
}

.pkg-card.selected {
    border-color: #2b6eff;
    box-shadow: 0 8rpx 32rpx rgba(43, 110, 255, 0.14);
}

.hot-tag {
    @apply absolute right-[18rpx] top-[18rpx] rounded-full px-[14rpx] py-[4rpx] text-[18rpx] font-bold text-white;
    background: linear-gradient(135deg, #ff6b35, #ff4d4f);
}

.notice-row {
    @apply mt-[22rpx] flex items-start gap-[12rpx] rounded-[22rpx] px-[24rpx] py-[18rpx];
    background: #ebf2ff;
    border: 2rpx solid #bad4ff;
}

.empty-card {
    @apply mt-[20rpx] flex flex-col items-center justify-center rounded-[28rpx] bg-white px-[32rpx] py-[56rpx];
    border: 2rpx dashed #d8dee8;
}

.pay-footer {
    @apply absolute bottom-0 left-0 right-0 z-20 px-[32rpx] pt-[22rpx];
    padding-bottom: calc(24rpx + constant(safe-area-inset-bottom));
    padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
    background: linear-gradient(180deg, rgba(244, 246, 250, 0), #f4f6fa 26%);
}

.tip-row {
    @apply flex items-center gap-[16rpx] py-[12rpx];
}

.tip-index {
    @apply flex h-[36rpx] w-[36rpx] flex-shrink-0 items-center justify-center rounded-full text-[22rpx] font-semibold text-primary;
    background: #ebf2ff;
}

.service-qrcode-wrap {
    @apply rounded-[28rpx] bg-white p-[14rpx];
    border: 2rpx solid #e8ecf0;
}

.service-qrcode {
    @apply h-[320rpx] w-[320rpx] rounded-[18rpx];
}

.rule-intro {
    @apply rounded-[24rpx] px-[28rpx] py-[24rpx];
    background: #ebf2ff;
    border: 2rpx solid #bad4ff;
}

.rule-row {
    @apply flex gap-[18rpx] py-[26rpx];
}

.rule-row + .rule-row,
.faq-row.border-top {
    border-top: 2rpx solid #f0f2f6;
}

.rule-dot {
    @apply mt-[14rpx] h-[12rpx] w-[12rpx] flex-shrink-0 rounded-full bg-primary;
}

.faq-row {
    @apply py-[26rpx];
}
</style>
