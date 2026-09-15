<template>
    <view class="overflow-hidden rounded-[32rpx] bg-white">
        <view
            v-for="row in rows"
            :key="row"
            class="flex items-center gap-[24rpx] px-[32rpx] py-[28rpx]"
            :class="{ 'border-[0] border-t-[2rpx] border-solid border-[#F2F4F9]': row > 1 }">
            <view class="skeleton-block h-[88rpx] w-[88rpx] flex-shrink-0 rounded-[24rpx]" />
            <view class="min-w-0 flex-1">
                <view class="skeleton-block mb-[14rpx] h-[30rpx] w-[45%]" />
                <view class="skeleton-block h-[22rpx] w-[70%]" />
            </view>
            <view class="skeleton-block h-[32rpx] w-[96rpx] flex-shrink-0" />
        </view>
    </view>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        rows?: number
    }>(),
    {
        rows: 6
    }
)
</script>

<style lang="scss" scoped>
.skeleton-block {
    border-radius: 12rpx;
    background: linear-gradient(90deg, #edf1f7 25%, #f6f8fb 50%, #edf1f7 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
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
