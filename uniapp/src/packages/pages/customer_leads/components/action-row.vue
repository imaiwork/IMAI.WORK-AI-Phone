<template>
    <view class="act-row" @click.stop="emit('click')">
        <view class="act-icon">
            <image :src="icon" class="w-[28rpx] h-[28rpx]"></image>
        </view>
        <view class="flex-1 min-w-0">
            <text class="text-xs font-semibold text-[#374151]">{{ title }}</text>
            <text v-if="desc" class="text-[22rpx] text-[#9CA3AF] ml-[8rpx]">{{ desc }}</text>
        </view>
        <u-icon v-if="showArrow" name="arrow-right" color="#D1D5DB" size="24"></u-icon>
    </view>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        icon: string;
        title: string;
        desc?: string;
        showArrow?: boolean;
    }>(),
    {
        desc: "",
        showArrow: true,
    },
);

const emit = defineEmits<{
    (event: "click"): void;
}>();
</script>

<style scoped lang="scss">
.act-row {
    @apply relative flex items-center px-[32rpx] py-[20rpx] gap-[24rpx];
}

.act-row::after {
    @apply absolute right-[32rpx] bottom-0 left-[32rpx] h-[2rpx] bg-[#eef1f6];

    content: "";
}

.act-icon {
    @apply flex items-center justify-center w-[64rpx] h-[64rpx] rounded-[20rpx] bg-[#ebf2ff] shrink-0;
}
</style>
