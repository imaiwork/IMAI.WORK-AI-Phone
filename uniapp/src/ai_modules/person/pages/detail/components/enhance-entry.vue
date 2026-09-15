<template>
    <view class="enhance-entry active:opacity-70" @click="emit('open')">
        <view class="enhance-ring" :style="{ background: ringBackground }">
            <view class="enhance-ring-inner">
                <text class="text-[22rpx] font-bold text-main">{{ percent }}</text>
            </view>
        </view>
        <view class="flex-1 min-w-0 flex flex-col gap-[6rpx]">
            <text class="text-sm font-semibold text-main">人设完善度 {{ percent }}%</text>
            <text class="text-[22rpx] text-muted line-clamp-1">{{ subText }}</text>
        </view>
        <u-icon name="arrow-right" color="#C8C9CC" size="28" class="shrink-0"></u-icon>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    percent: number;
    /** 还有几项加分项没做 */
    remainCount: number;
}>();

const emit = defineEmits<{
    (e: "open"): void;
}>();

const ringBackground = computed(() => {
    const turn = Math.min(1, Math.max(0, props.percent / 100));
    return `conic-gradient(#0065FB 0turn ${turn}turn, #E2E2E2 ${turn}turn 1turn)`;
});

const subText = computed(() =>
    props.remainCount ? `还有 ${props.remainCount} 项能让内容更好` : "所有项都已完成",
);
</script>

<style lang="scss" scoped>
.enhance-entry {
    @apply bg-white rounded-[32rpx] px-[28rpx] py-[26rpx] flex items-center gap-[24rpx];
    box-shadow: 0 8rpx 36rpx rgba(16, 40, 90, 0.06);
}

.enhance-ring {
    @apply w-[76rpx] h-[76rpx] rounded-full shrink-0 flex items-center justify-center;
}

.enhance-ring-inner {
    @apply w-[58rpx] h-[58rpx] rounded-full bg-white flex items-center justify-center;
}
</style>
