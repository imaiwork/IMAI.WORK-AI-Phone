<template>
    <view class="pb-[80rpx]">
        <view class="overflow-hidden rounded-[30rpx] bg-white">
            <view v-for="item in count" :key="item" class="skeleton-record">
                <view class="flex min-h-[88rpx] items-center gap-[20rpx]">
                    <view
                        class="skeleton-block h-[76rpx] w-[76rpx] shrink-0 rounded-[24rpx]"
                    ></view>
                    <view class="min-w-0 flex-1">
                        <view class="skeleton-block mb-[12rpx] h-[28rpx] w-[180rpx]"></view>
                        <view class="skeleton-block h-[22rpx] w-[240rpx]"></view>
                    </view>
                </view>
                <view class="mt-[30rpx] mb-[26rpx]">
                    <view class="skeleton-block mb-[14rpx] h-[24rpx] w-full"></view>
                    <view class="skeleton-block h-[24rpx] w-4/5"></view>
                </view>
                <view class="skeleton-block my-[22rpx] h-[26rpx] w-[320rpx]"></view>
                <view class="flex gap-[24rpx]">
                    <view class="skeleton-block h-[24rpx] w-[140rpx]"></view>
                    <view class="skeleton-block h-[24rpx] w-[240rpx]"></view>
                </view>
                <view class="skeleton-block mt-[12rpx] h-[22rpx] w-[240rpx]"></view>
                <view class="skeleton-actions">
                    <view v-for="action in 3" :key="action" class="flex flex-1 justify-center">
                        <view class="skeleton-block h-[24rpx] w-[130rpx]"></view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ count?: number }>(), {
    count: 3
})
</script>

<style scoped lang="scss">
.skeleton-record {
    @apply px-[32rpx] pt-[34rpx];
}

.skeleton-record + .skeleton-record {
    @apply border-[0] border-t-[12rpx] border-solid border-[#F4F7FA];
}

.skeleton-block {
    @apply rounded-[12rpx];
    background: linear-gradient(90deg, #edf1f7 25%, #f6f8fb 50%, #edf1f7 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-actions {
    @apply mt-[24rpx] flex min-h-[88rpx] items-center gap-[12rpx] border-[0] border-t-[2rpx] border-solid border-[#E8EDF3];
}

@media (prefers-reduced-motion: reduce) {
    .skeleton-block {
        animation: none;
    }
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
