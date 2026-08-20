<template>
    <view class="bg-[#F8FAFF] rounded-[28rpx] p-[28rpx]">
        <view class="w-[88rpx] h-[88rpx] rounded-[28rpx] bg-[#EEF2FF] flex items-center justify-center mb-[22rpx]">
            <image :src="icon" class="w-[40rpx] h-[40rpx]"></image>
        </view>
        <text class="text-[22rpx] text-[#9CA3AF] mb-[16rpx] block">{{ label }}</text>
        <view class="flex items-baseline">
            <text class="text-[60rpx] font-bold text-[#3D5CF5] leading-none">{{ value }}</text>
            <text class="text-[26rpx] font-semibold text-[#3D5CF5] ml-[6rpx]">{{ unit }}</text>
        </view>
        <text class="text-[20rpx] text-[#9CA3AF] mt-[16rpx] block">{{ desc }}</text>
    </view>
</template>

<script setup lang="ts">
defineProps<{
    icon: string;
    label: string;
    value: number;
    unit: string;
    desc: string;
}>();
</script>

<style scoped></style>
