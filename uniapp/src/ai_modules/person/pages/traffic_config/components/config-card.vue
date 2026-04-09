<template>
    <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)] mb-4">
        <view class="flex items-center gap-3 mb-4">
            <view
                class="w-[64rpx] h-[64rpx] rounded-[16rpx] flex items-center justify-center flex-shrink-0"
                :style="{ background: iconBg }">
                <u-icon :name="iconName" :color="iconColor" size="36"></u-icon>
            </view>
            <view class="flex flex-col">
                <text class="text-[30rpx] font-extrabold text-[#212121]">{{ title }}</text>
                <text class="text-[22rpx] text-[#676767] mt-0.5">{{ desc }}</text>
            </view>
        </view>
        <slot />
    </view>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    desc: string;
    iconName: string;
    iconColor: string;
    iconBg: string;
}>();
</script>
