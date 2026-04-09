<template>
    <view
        v-if="configured"
        class="flex items-center gap-[4rpx] bg-[#ECFDF5] border border-solid border-[#A7F3D0] px-[10rpx] py-[4rpx] rounded-full">
        <view class="w-[10rpx] h-[10rpx] rounded-full bg-[#10B981] flex-shrink-0"></view>
        <text class="text-[18rpx] text-[#059669] font-medium">已配置</text>
    </view>
    <view
        v-else
        class="flex items-center gap-[4rpx] bg-[#F3F4F6] border border-solid border-[#E5E7EB] px-[10rpx] py-[4rpx] rounded-full">
        <view class="w-[10rpx] h-[10rpx] rounded-full bg-[#D1D5DB] flex-shrink-0"></view>
        <text class="text-[18rpx] text-[#9CA3AF] font-medium">未配置</text>
    </view>
</template>

<script setup lang="ts">
defineProps<{
    configured: boolean;
}>();
</script>
