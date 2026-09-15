<template>
    <view class="flex flex-col items-center px-[32rpx] pt-[72rpx] pb-[48rpx] text-center">
        <view class="text-primary mb-[24rpx]">
            <u-icon name="file-text" size="52" color="currentColor" />
        </view>
        <text class="text-lg font-semibold text-[#1E293B]">{{ text }}</text>
        <text v-if="description" class="text-xs leading-relaxed text-[#617087] mt-[16rpx]">
            {{ description }}
        </text>
    </view>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        text: string
        description?: string
    }>(),
    { description: '' }
)
</script>
