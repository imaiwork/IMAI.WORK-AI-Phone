<template>
    <view class="px-[32rpx] pt-[8rpx] pb-[80rpx]">
        <view v-for="item in count" :key="item" class="skeleton-card">
            <view class="px-[32rpx] pt-[32rpx] pb-[24rpx] flex items-start justify-between gap-[20rpx]">
                <view class="flex items-center gap-[24rpx] flex-1 min-w-0">
                    <view class="skeleton-block w-[96rpx] h-[96rpx] rounded-[28rpx] shrink-0"></view>
                    <view class="flex-1 min-w-0">
                        <view class="skeleton-block w-[200rpx] h-[30rpx] mb-[16rpx]"></view>
                        <view class="skeleton-block w-[260rpx] h-[22rpx] mb-[12rpx]"></view>
                        <view class="skeleton-block w-[180rpx] h-[22rpx]"></view>
                    </view>
                </view>
                <view class="skeleton-block w-[112rpx] h-[36rpx] rounded-full shrink-0"></view>
            </view>

            <view class="flex items-stretch px-[32rpx] py-[26rpx] gap-[20rpx] row-divider-b">
                <view v-for="col in 2" :key="col" class="flex-1 flex flex-col items-center gap-[14rpx]">
                    <view class="skeleton-block w-[100rpx] h-[38rpx]"></view>
                    <view class="skeleton-block w-[120rpx] h-[22rpx]"></view>
                </view>
            </view>

            <view class="flex items-center px-[32rpx] py-[24rpx] gap-[24rpx] row-divider-b">
                <view class="skeleton-block w-[64rpx] h-[64rpx] rounded-[20rpx] shrink-0"></view>
                <view class="skeleton-block flex-1 h-[26rpx]"></view>
                <view class="skeleton-block w-[40rpx] h-[26rpx]"></view>
            </view>

            <view class="px-[32rpx] py-[26rpx]">
                <view class="skeleton-block w-full h-[88rpx] rounded-[24rpx]"></view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ count?: number }>(), {
    count: 3,
});
</script>

<style scoped lang="scss">
.skeleton-card {
    @apply mb-[24rpx] overflow-hidden rounded-[40rpx] bg-white;

    box-shadow: 0 4rpx 28rpx rgba(47, 115, 246, 0.07);
}

.skeleton-block {
    border-radius: 12rpx;
    background: linear-gradient(90deg, #edf1f7 25%, #f6f8fb 50%, #edf1f7 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.row-divider-b {
    @apply relative;
}

.row-divider-b::after {
    @apply absolute right-[32rpx] bottom-0 left-[32rpx] h-[2rpx] bg-[#eef1f6];

    content: "";
}

@keyframes skeleton-loading {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}
</style>
