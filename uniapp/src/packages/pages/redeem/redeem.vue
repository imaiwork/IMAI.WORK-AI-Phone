<template>
    <view class="h-screen flex flex-col bg-[#F9FAFB]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            title="充值中心"
            title-bold>
        </u-navbar>
        <view class="grow min-h-0 mt-[40rpx]">
            <scroll-view scroll-y class="h-full">
                <view class="px-[64rpx]">
                    <view class="flex items-center justify-between">
                        <view class="flex items-center justify-between gap-1 relative">
                            <image src="@/packages/static/images/common/jf.png" class="w-[48rpx] h-[48rpx]"></image>
                            <text class="text-[32rpx] font-bold">卡密兑换</text>
                            <image
                                src="@/packages/static/images/common/title_path.png"
                                class="h-[16rpx] w-[122rpx] absolute right-[-62rpx] bottom-0 z-[-1]"></image>
                        </view>
                    </view>
                    <view class="text-[#0000004d] text-[26rpx] mt-[20rpx]">
                        请确认卡密编号，兑换后立即生效，卡密不可再次使用
                    </view>
                    <view class="mt-4">
                        <view
                            class="form-ipt !border-[#EDEDED]"
                            :class="{
                                'is-focus': isFocus,
                                'is-error': isError,
                            }">
                            <u-input
                                v-model="codeValue"
                                placeholder="请输入有效卡密编号"
                                :placeholder-style="`color:${isError ? '#FB0000' : ' rgba(0,0,0,.2)'};font-size:26rpx;`"
                                @blur="isFocus = false"
                                @focus="isFocus = true"></u-input>
                        </view>
                        <view class="text-xs flex items-center text-[#B0B0B0] justify-center mt-4">
                            兑换代表接受<navigator
                                class="text-[#7397FC]"
                                hover-class="none"
                                url="/packages/pages/agreement/agreement?type=service"
                                >《充值规则协议》</navigator
                            >
                        </view>
                        <view class="mt-4">
                            <u-button
                                type="primary"
                                shape="circle"
                                :loading="isLockQueryRedeem"
                                :custom-style="{
                                    height: '90rpx',
                                    fontSize: '26rpx',
                                    boxShadow: '0px 6px 12px 0px rgba(0, 101, 251, 0.20)',
                                }"
                                @click="lockFnQueryRedeem"
                                >确认兑换</u-button
                            >
                        </view>
                    </view>
                    <view class="mt-[48rpx] flex flex-col items-center justify-center px-[48rpx]">
                        <view class="flex items-center gap-x-2 w-full">
                            <view class="flex-1"><u-line /></view>
                            <view class="text-[#0000004d] text-[26rpx] flex-shrink-0">温馨提示</view>
                            <view class="flex-1"><u-line /></view>
                        </view>
                        <view class="flex flex-col gap-y-4 mt-[52rpx]">
                            <view class="flex items-center gap-x-2 text-[#0000004d] text-[26rpx]">
                                <text
                                    class="flex items-center justify-center border border-solid border-[#EDEDEE] bg-[#EDEDEE] w-[36rpx] h-[36rpx] rounded-full"
                                    >1</text
                                >
                                <text>充值获得的算力只能在本平台使用</text>
                            </view>
                            <view class="flex items-center gap-x-2 text-[#0000004d] text-[26rpx]">
                                <text
                                    class="flex items-center justify-center border border-solid border-[#EDEDEE] bg-[#EDEDEE] w-[36rpx] h-[36rpx] rounded-full"
                                    >2</text
                                >
                                <text>若充值未到账，请联系客服</text>
                            </view>
                            <view class="flex items-center gap-x-2 text-[#0000004d] text-[26rpx]">
                                <text
                                    class="flex items-center justify-center border border-solid border-[#EDEDEE] bg-[#EDEDEE] w-[36rpx] h-[36rpx] rounded-full"
                                    >3</text
                                >
                                <text>充值获得的为虚拟算力，一般不可退换</text>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>
    <u-popup v-model="checkVisible" mode="center" border-radius="16" @close="checkVisible = false">
        <view class="flex flex-col p-4 gap-y-4">
            <view class="flex items-center">
                <text>卡密面额：</text>
                <text>{{ checkResult.content }}</text>
            </view>
            <view class="flex items-center">
                <text>兑换时间：</text>
                <text>{{ checkResult.failure_time }}</text>
            </view>
            <view class="flex items-center" v-if="checkResult.valid_time">
                <text>有效期至：</text>
                <text>{{ checkResult.valid_time }}</text>
            </view>
            <view class="flex-1 flex justify-center items-center bg-white pt-[20px]">
                <u-button type="primary" :custom-style="{ width: '100%' }" :loading="isLockRedeem" @click="lockFnRedeem"
                    >立即兑换</u-button
                >
            </view>
        </view>
    </u-popup>
</template>

<script lang="ts" setup>
import { checkRedeemCode, useRedeemCode } from "@/api/recharge";
import { useLockFn } from "@/hooks/useLockFn";
const codeValue = ref<string>("");
const checkVisible = ref<boolean>(false);
const checkResult = ref<any>({});

const isFocus = ref<boolean>(false);
const isError = ref<boolean>(false);

const { lockFn: lockFnQueryRedeem, isLock: isLockQueryRedeem } = useLockFn(async () => {
    if (!codeValue.value) {
        uni.$u.toast("卡密编号不能为空");
        isError.value = true;
        return;
    }
    isError.value = false;
    try {
        const data = await checkRedeemCode({ sn: codeValue.value });
        checkVisible.value = true;
        checkResult.value = data;
    } catch (error) {
        uni.$u.toast(error);
    }
});
const { lockFn: lockFnRedeem, isLock: isLockRedeem } = useLockFn(async () => {
    try {
        await useRedeemCode({ sn: codeValue.value });
        checkVisible.value = false;
        checkResult.value = {};
        codeValue.value = "";
        uni.$u.toast("兑换成功");
    } catch (error) {
        uni.$u.toast(error);
    }
});
</script>

<style lang="scss" scoped></style>
