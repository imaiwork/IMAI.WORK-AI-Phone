<template>
    <view class="flex flex-col gap-[28rpx] px-[32rpx] pt-[28rpx] pb-[120rpx]">
        <!-- 信息卡 -->
        <view class="overflow-hidden rounded-[40rpx] bg-white">
            <view class="px-[40rpx] py-[40rpx]" style="background: linear-gradient(135deg, #dbeafe, #eff6ff)">
                <view class="flex items-center gap-[28rpx]">
                    <view class="skeleton-block h-[104rpx] w-[104rpx] flex-shrink-0 rounded-[32rpx]" />
                    <view class="min-w-0 flex-1">
                        <view class="skeleton-block mb-[16rpx] h-[36rpx] w-[240rpx]" />
                        <view class="skeleton-block h-[22rpx] w-[320rpx]" />
                    </view>
                    <view class="skeleton-block h-[64rpx] w-[64rpx] flex-shrink-0 rounded-[20rpx]" />
                </view>
            </view>
            <view class="flex py-[28rpx]">
                <view v-for="col in 3" :key="col" class="flex flex-1 flex-col items-center gap-[12rpx]">
                    <view class="skeleton-block h-[36rpx] w-[80rpx]" />
                    <view class="skeleton-block h-[22rpx] w-[100rpx]" />
                </view>
            </view>
        </view>

        <!-- 事务入口 -->
        <view class="overflow-hidden rounded-[40rpx] bg-white">
            <view
                v-for="row in 2"
                :key="row"
                class="flex items-center gap-[28rpx] px-[36rpx] py-[32rpx]"
                :class="{ 'border-t border-[#F0F4FB]': row > 1 }">
                <view class="skeleton-block h-[90rpx] w-[90rpx] flex-shrink-0 rounded-[28rpx]" />
                <view class="min-w-0 flex-1">
                    <view class="skeleton-block mb-[12rpx] h-[30rpx] w-[160rpx]" />
                    <view class="skeleton-block h-[24rpx] w-[280rpx]" />
                </view>
                <view class="skeleton-block h-[28rpx] w-[28rpx] rounded-full" />
            </view>
        </view>

        <!-- 套餐与算力 -->
        <view class="overflow-hidden rounded-[40rpx] bg-white px-[36rpx] py-[32rpx]">
            <view class="mb-[24rpx] flex items-center gap-[16rpx]">
                <view class="skeleton-block h-[32rpx] w-[8rpx] rounded-full" />
                <view class="skeleton-block h-[30rpx] w-[160rpx]" />
                <view class="ml-auto skeleton-block h-[36rpx] w-[180rpx] rounded-full" />
            </view>
            <view class="mb-[24rpx] flex">
                <view v-for="col in 3" :key="col" class="flex flex-1 flex-col items-center gap-[12rpx] py-[16rpx]">
                    <view class="skeleton-block h-[34rpx] w-[120rpx]" />
                    <view class="skeleton-block h-[22rpx] w-[100rpx]" />
                </view>
            </view>
            <view class="skeleton-block h-[12rpx] w-full rounded-full" />
        </view>

        <!-- 授权功能 -->
        <view class="overflow-hidden rounded-[40rpx] bg-white px-[24rpx] py-[32rpx]">
            <view class="mb-[24rpx] flex items-center gap-[16rpx] px-[12rpx]">
                <view class="skeleton-block h-[32rpx] w-[8rpx] rounded-full" />
                <view class="skeleton-block h-[30rpx] w-[140rpx]" />
                <view class="ml-auto skeleton-block h-[36rpx] w-[160rpx] rounded-full" />
            </view>
            <view class="grid grid-cols-3 gap-[8rpx]">
                <view v-for="item in 9" :key="item" class="flex flex-col items-center gap-[14rpx] py-[24rpx]">
                    <view class="skeleton-block h-[88rpx] w-[88rpx] rounded-[28rpx]" />
                    <view class="skeleton-block h-[22rpx] w-[88rpx]" />
                </view>
            </view>
        </view>

        <!-- 危险操作 -->
        <view class="skeleton-block h-[96rpx] w-full rounded-[32rpx]" />
        <view class="mx-auto skeleton-block h-[22rpx] w-[360rpx]" />
    </view>
</template>

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
