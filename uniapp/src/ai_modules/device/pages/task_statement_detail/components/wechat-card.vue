<template>
    <view class="bg-white rounded-[20rpx] px-[32rpx] py-[32rpx]">
        <view
            class="flex items-center gap-[24rpx] bg-[#F0FFF4] border-[1rpx] border-[#C8E6C9] rounded-[16rpx] px-[24rpx] py-[20rpx] mb-[20rpx]">
            <image src="/static/images/common/wechat_s.png" class="w-[72rpx] h-[72rpx]"></image>
            <view class="flex flex-col gap-[6rpx]">
                <text class="text-[22rpx] text-[#888888] font-medium">微信号</text>
                <text class="text-[30rpx] font-bold text-[#1A1A1A] tracking-wide">{{ item.reg_wechat }}</text>
            </view>
        </view>

        <view class="bg-[#FAFAFA] rounded-[12rpx] px-[24rpx] py-[20rpx] mb-[20rpx]">
            <text class="text-[22rpx] font-medium text-[#AAAAAA] block mb-[8rpx]">添加备注</text>
            <text class="text-[#444444] leading-relaxed">
                {{ item.remark || "-" }}
            </text>
        </view>

        <view class="h-[1rpx] bg-[#F0F0F0] mb-[20rpx]"></view>

        <view class="flex items-center gap-[10rpx]">
            <view class="w-[10rpx] h-[10rpx] rounded-full bg-[#D0D0D0] shrink-0"></view>
            <text class="text-[22rpx] text-[#AAAAAA]"
                >执行：{{ item.execute_name || "-" }}（{{ item.execute_account || "-" }}）</text
            >
        </view>
        <view class="flex items-center gap-[10rpx] mt-2">
            <view class="w-[10rpx] h-[10rpx] rounded-full bg-[#D0D0D0] shrink-0"></view>
            <view class="text-[22rpx] text-[#AAAAAA]"> 时间：{{ item.create_time }} </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    item: any;
}>();
</script>

<style scoped></style>
