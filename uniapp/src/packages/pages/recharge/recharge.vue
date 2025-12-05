<template>
    <view class="h-screen flex flex-col relative bg-[#060815]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            back-icon-color="#ffffff"
            title-color="#ffffff"
            title="充值中心"
            title-bold>
        </u-navbar>
        <view
            class="flex flex-col items-center justify-center bg-no-repeat bg-center bg-cover relative py-[60rpx]"
            :style="{ backgroundImage: `url(${config.baseUrl}static/images/recharge_img1.png)` }">
            <view class="text-[30rpx] font-bold text-white">当前算力</view>
            <text class="font-digital-number font-bold text-white text-[48rpx] mt-[60rpx]"> {{ userTokens }}</text>
        </view>
        <view class="relative grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-[32rpx]">
                    <view v-if="isIOS()">
                        <view class="flex items-center gap-x-2 justify-center">
                            <image src="@/packages/static/icons/title_line.svg" class="w-[50rpx] h-[10rpx]"></image>
                            <view class="text-center text-[#384166] text-[26rpx]"> 小程序暂不提供IOS端充值功能 </view>
                            <image
                                src="@/packages/static/icons/title_line.svg"
                                class="w-[50rpx] h-[10rpx]"
                                style="transform: rotate(180deg)"></image>
                        </view>
                        <view class="relative h-[450rpx] mt-2">
                            <view
                                class="h-[284rpx] bg-center bg-no-repeat bg-cover"
                                :style="{
                                    backgroundImage: `url(${config.baseUrl}static/images/recharge_qrcode_bg.png)`,
                                }">
                            </view>
                            <image :src="getServerConfig.qrcode" class="service-qrcode"></image>
                        </view>
                        <view class="text-[26rpx] text-white mt-[12rpx] text-center"> 请长按二维码扫描添加客服 </view>
                        <view class="text-[#384166] text-[26rpx] flex flex-col items-center justify-center mt-[24rpx]">
                            <view class="mb-[52rpx]">温馨提示</view>
                            <view class="flex flex-col gap-y-4">
                                <view class="flex items-center gap-x-2 text-[26rpx]">
                                    <text
                                        class="flex items-center justify-center border border-solid border-[#9eb4fd0d] bg-[#9eb4fd1a] w-[36rpx] h-[36rpx] text-white rounded-full"
                                        >1</text
                                    >
                                    <text>充值获得的算力只能在本平台使用</text>
                                </view>
                                <view class="flex items-center gap-x-2 text-[26rpx]">
                                    <text
                                        class="flex items-center justify-center border border-solid border-[#9eb4fd0d] bg-[#9eb4fd1a] w-[36rpx] h-[36rpx] text-white rounded-full"
                                        >2</text
                                    >
                                    <text>若充值未到账，请联系客服</text>
                                </view>
                                <view class="flex items-center gap-x-2 text-[26rpx]">
                                    <text
                                        class="flex items-center justify-center border border-solid border-[#9eb4fd0d] bg-[#9eb4fd1a] w-[36rpx] h-[36rpx] text-white rounded-full"
                                        >3</text
                                    >
                                    <text>充值获得的为虚拟算力，一般不可退换</text>
                                </view>
                            </view>
                        </view>
                        <view class="mt-5 pb-5">
                            <u-button
                                type="primary"
                                shape="circle"
                                :custom-style="{ height: '90rpx', fontSize: '26rpx' }"
                                @click="showRecord = true"
                                >订阅记录</u-button
                            >
                        </view>
                    </view>
                    <template v-else>
                        <view class="absolute left-0 -top-[42rpx] flex justify-center w-full">
                            <image
                                src="@/packages/static/images/common/dazzle_light.png"
                                class="w-[308rpx] h-[200rpx]"></image>
                        </view>
                        <view
                            class="h-[560rpx] w-full bg-no-repeat mt-[60rpx]"
                            :style="{
                                backgroundImage: `url(${config.baseUrl}static/images/recharge_box_bg.png)`,
                                backgroundSize: '100% 100%',
                            }">
                            <scroll-view class="h-full" scroll-y>
                                <view class="h-full flex flex-col p-[40rpx]">
                                    <view
                                        v-for="(item, index) in rechargeLists"
                                        :key="index"
                                        class="flex items-center h-[122rpx] px-[26rpx] bg-no-repeat"
                                        :style="{
                                            backgroundImage:
                                                currRechargeId == item.id
                                                    ? `url(${config.baseUrl}static/images/recharge_tokens_item_active_bg.png)`
                                                    : `url(${config.baseUrl}static/images/recharge_tokens_item_bg.png)`,
                                            backgroundSize: '100% 100%',
                                        }"
                                        @click="handleRecharge(item.id)">
                                        <view
                                            class="min-w-[200rpx] font-digital-number"
                                            :class="[currRechargeId == item.id ? 'text-[#FF9500]' : 'text-white']">
                                            ￥{{ item.price }}
                                        </view>
                                        <view class="flex justify-between flex-1">
                                            <view
                                                class="text-[26rpx]"
                                                :class="[
                                                    currRechargeId == item.id ? 'text-[#FF9500]' : 'text-[#808080]',
                                                ]">
                                                Tokens/算力
                                            </view>
                                            <view
                                                class="flex items-center h-[40rpx] rounded-full relative pr-3 pl-5 border border-solid"
                                                :class="[
                                                    currRechargeId == item.id
                                                        ? 'border-[#624E35] bg-[#ff95001a]'
                                                        : 'bg-[#16f49f1a] border-[#16f49f33]',
                                                ]">
                                                <image
                                                    v-if="currRechargeId == item.id"
                                                    src="@/packages/static/icons/tokens2.svg"
                                                    class="w-[32rpx] h-[32rpx] absolute left-[2rpx]"></image>
                                                <image
                                                    v-else
                                                    src="@/packages/static/icons/tokens.svg"
                                                    class="w-[32rpx] h-[32rpx] absolute left-[2rpx]"></image>
                                                <view
                                                    class="text-[26rpx] flex-1 text-center"
                                                    :class="[
                                                        currRechargeId == item.id ? 'text-[#FF9500]' : 'text-[#16F49F]',
                                                    ]"
                                                    >{{ item.package_info.tokens }}</view
                                                >
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </scroll-view>
                        </view>
                        <view class="flex items-center mt-[24rpx]">
                            <u-checkbox v-model="isAgreement" shape="circle" size="28"> </u-checkbox>
                            <view class="text-white text-xs flex -ml-2">
                                点击<text class="text-primary">兑换</text>或<text class="text-primary">充值</text
                                >即表示您已了解并接受<navigator
                                    class="text-primary"
                                    hover-class="none"
                                    url="/packages/pages/agreement/agreement?type=service"
                                    >《充值规则协议》</navigator
                                >
                            </view>
                        </view>
                        <view class="flex items-center justify-center mt-[48rpx] gap-x-2">
                            <navigator
                                v-if="cardCodeConfig.is_open == 1"
                                url="/packages/pages/redeem/redeem"
                                hover-class="none"
                                class="bg-[#121420] flex-1 h-[100rpx] rounded-full flex items-center justify-center text-[#888990] text-[26rpx] font-bold">
                                卡密兑换
                            </navigator>
                            <!-- #ifdef MP-WEIXIN -->
                            <view class="flex-1">
                                <u-button
                                    type="primary"
                                    shape="circle"
                                    :loading="isLock"
                                    :custom-style="{ height: '100rpx', fontSize: '26rpx' }"
                                    @click="handlePay">
                                    立即充值
                                </u-button>
                            </view>
                            <!-- #endif -->
                        </view>
                        <view class="text-white text-[26rpx] text-center mt-[48rpx] font-bold">
                            点击查看<text class="text-primary ml-1" @click="showRecord = true">账单明细</text>
                        </view>
                    </template>
                </view>
            </scroll-view>
        </view>
    </view>
    <popup-bottom v-model:show="showRecord" title="订阅记录" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 rounded-[24rpx] mt-4">
                    <z-paging
                        ref="pagingRef"
                        v-model="recordLists"
                        :fixed="false"
                        :default-page-size="20"
                        :safe-area-inset-bottom="true"
                        auto-show-back-to-top
                        @query="queryList">
                        <view class="px-[32rpx] flex flex-col gap-2">
                            <view
                                v-for="(item, index) in recordLists"
                                :key="index"
                                class="p-4 rounded-[16rpx] bg-white">
                                <view class="flex items-center justify-between">
                                    <view>
                                        <view class="text-[26rpx]">{{ item.remark }}</view>
                                        <view class="opacity-30 text-[22rpx] mt-1">订单编号：{{ item.sn }}</view>
                                    </view>
                                    <view class="text-right">
                                        <view class="text-[26rpx] opacity-80">
                                            {{ item.change_amount_desc }}
                                        </view>
                                        <view class="opacity-30 text-[22rpx] mt-1">
                                            {{ item.create_time }}
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script lang="ts" setup>
import { getRechargeList, getPaymentList, createRechargeOrder, prePay } from "@/api/recharge";
import { accountLog } from "@/api/user";
import { pay } from "@/utils/pay";
import { useLockFn } from "@/hooks/useLockFn";
import { PayStatusEnum } from "@/enums/appEnums";
import { useUserStore } from "@/stores/user";
import { series } from "@/utils/util";
import { isIOS } from "@/utils/client";
import { useAppStore } from "@/stores/app";
import config from "@/config";

const appStore = useAppStore();

const userStore = useUserStore();

const userTokens = computed(() => userStore.userTokens);
const cardCodeConfig = computed(() => appStore.getCardCodeConfig);
const getServerConfig = computed(() => {
    const { customer_service } = appStore.getWebsiteConfig;
    return {
        qrcode: customer_service?.wx_image,
    };
});

const getRechargeData = computed(() => {
    return rechargeLists.value.find((item: any) => item.id == currRechargeId.value);
});

const isAgreement = ref<boolean>(true);
const rechargeLoading = ref<boolean>(true);

const rechargeLists = ref<any[]>([]);
const getRechargeLists = async () => {
    const { lists } = await getRechargeList({ type: 1 });
    getPayWayListData();
    rechargeLists.value = lists;
    rechargeLoading.value = false;
    if (lists && lists.length) {
        currRechargeId.value = lists[0].id;
    }
};

const currRechargeId = ref<number>(-1);

const handleRecharge = (id: number) => {
    currRechargeId.value = id;
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
        payWay.value = payWayList.value[0].id;
    }
};

const payment = (() => {
    // 创建订单
    const createOrderTask = async () => {
        uni.showLoading({
            title: "创建订单中",
        });
        try {
            const result = await createRechargeOrder({
                type: 1,
                package_id: getRechargeData.value.id,
            });
            uni.hideLoading();
            return result;
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({
                icon: "none",
                title: error || "创建订单失败",
                duration: 3000,
            });
            return Promise.reject(error);
        }
    };
    // 调用预支付
    const prepayTask = async (data: any) => {
        uni.showLoading({
            title: "正在支付中",
        });
        try {
            const res = await prePay({
                order_id: data.order_id,
                from: payFrom,
                pay_way: payWay.value,
            });

            return res;
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({
                title: error || "支付失败",
                icon: "none",
                duration: 3000,
            });
            return Promise.reject(error);
        }
    };

    //拉起支付
    const payTask = async (data: any) => {
        try {
            const res = await pay.payment(data.pay_way, data.config);
            return res;
        } catch (error: any) {
            uni.showToast({
                title: error || "支付失败",
                icon: "none",
                duration: 5000,
            });
            return Promise.reject(error);
        }
    };
    return series(createOrderTask, prepayTask, payTask);
})();

const { isLock, lockFn: handlePay } = useLockFn(async () => {
    try {
        const res: PayStatusEnum = await payment();
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

const showRecord = ref<boolean>(false);
const recordLists = ref<any[]>([]);
const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await accountLog({
            page_no,
            page_size,
            type: "tokens",
            is_order: 1,
            action: 1,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

getRechargeLists();
</script>

<style lang="scss" scoped>
.service-qrcode {
    @apply w-[400rpx] h-[400rpx] absolute top-2 left-[50%] border-0 border-b-[4rpx] border-dashed border-white;
    transform: translateX(-50%);
    &::after {
        position: absolute;
        content: "";
        top: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(180deg, #000 0%, #232323 5.25%, rgba(35, 35, 35, 0) 26.25%);
    }
}
</style>
