<template>
    <view class="px-[32rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
        <view class="bg-white rounded-[36rpx] px-[40rpx] py-[32rpx] effect-card-shadow">
            <view class="flex items-center justify-between">
                <view class="flex-1 min-w-0">
                    <view class="flex items-center gap-[16rpx] mb-[20rpx]">
                        <view class="skeleton-block w-[120rpx] h-[44rpx] rounded-[18rpx]"></view>
                        <view class="skeleton-block w-[160rpx] h-[24rpx]"></view>
                    </view>
                    <view class="flex items-center gap-[12rpx]">
                        <view class="skeleton-circle w-[56rpx] h-[56rpx]"></view>
                        <view class="skeleton-block w-[180rpx] h-[52rpx]"></view>
                        <view class="skeleton-circle w-[56rpx] h-[56rpx]"></view>
                    </view>
                </view>
                <view class="skeleton-block w-[96rpx] h-[96rpx] rounded-[28rpx] shrink-0"></view>
            </view>
        </view>

        <view class="mt-[24rpx] flex flex-col gap-[24rpx]">
            <view class="bg-white rounded-[32rpx] p-[32rpx] effect-card-shadow">
                <view class="flex items-center gap-[14rpx] mb-[28rpx]">
                    <view class="skeleton-block w-[6rpx] h-[34rpx] rounded-full"></view>
                    <view class="skeleton-block w-[168rpx] h-[32rpx]"></view>
                </view>
                <view class="grid grid-cols-3 gap-[16rpx]">
                    <view
                        v-for="item in 3"
                        :key="item"
                        class="bg-[#F8FAFF] rounded-[28rpx] px-[12rpx] py-[28rpx] flex flex-col items-center">
                        <view class="skeleton-block w-[88rpx] h-[88rpx] rounded-[28rpx] mb-[22rpx]"></view>
                        <view class="skeleton-block w-[96rpx] h-[24rpx] mb-[18rpx]"></view>
                        <view class="skeleton-block w-[72rpx] h-[42rpx]"></view>
                        <view class="skeleton-block w-[108rpx] h-[32rpx] rounded-full mt-[18rpx]"></view>
                    </view>
                </view>
            </view>

            <view class="bg-white rounded-[32rpx] p-[32rpx] effect-card-shadow">
                <view class="flex items-center justify-between mb-[28rpx]">
                    <view class="flex items-center gap-[14rpx]">
                        <view class="skeleton-block w-[6rpx] h-[34rpx] rounded-full"></view>
                        <view class="skeleton-block w-[128rpx] h-[32rpx]"></view>
                    </view>
                    <view class="skeleton-block w-[128rpx] h-[38rpx] rounded-full"></view>
                </view>
                <view class="grid grid-cols-2 gap-[20rpx]">
                    <view v-for="item in 2" :key="item" class="bg-[#F8FAFF] rounded-[28rpx] p-[28rpx]">
                        <view class="skeleton-block w-[88rpx] h-[88rpx] rounded-[28rpx] mb-[22rpx]"></view>
                        <view class="skeleton-block w-[128rpx] h-[26rpx] mb-[20rpx]"></view>
                        <view class="skeleton-block w-[110rpx] h-[58rpx]"></view>
                        <view class="skeleton-block w-[148rpx] h-[24rpx] mt-[18rpx]"></view>
                    </view>
                </view>
            </view>

            <view class="bg-white rounded-[32rpx] p-[32rpx] effect-card-shadow">
                <view class="flex items-center gap-[14rpx] mb-[28rpx]">
                    <view class="skeleton-block w-[6rpx] h-[34rpx] rounded-full"></view>
                    <view class="skeleton-block w-[220rpx] h-[32rpx]"></view>
                </view>
                <view class="flex flex-col gap-[12rpx]">
                    <view
                        v-for="item in 4"
                        :key="item"
                        class="bg-[#F8FAFF] rounded-[28rpx] px-[24rpx] py-[24rpx] flex items-center justify-between gap-[20rpx]">
                        <view class="flex items-center gap-[20rpx] flex-1 min-w-0">
                            <view class="skeleton-block w-[80rpx] h-[80rpx] rounded-[24rpx] shrink-0"></view>
                            <view class="flex-1 min-w-0">
                                <view class="skeleton-block w-[180rpx] h-[28rpx]"></view>
                                <view class="skeleton-block w-[240rpx] h-[22rpx] mt-[12rpx]"></view>
                            </view>
                        </view>
                        <view class="items-end flex flex-col gap-[12rpx] shrink-0">
                            <view class="skeleton-block w-[72rpx] h-[36rpx]"></view>
                            <view class="skeleton-block w-[128rpx] h-[30rpx] rounded-full"></view>
                        </view>
                    </view>
                </view>
            </view>

            <view class="bg-white rounded-[32rpx] p-[32rpx] effect-card-shadow">
                <view class="flex items-center gap-[14rpx] mb-[28rpx]">
                    <view class="skeleton-block w-[6rpx] h-[34rpx] rounded-full"></view>
                    <view class="skeleton-block w-[200rpx] h-[32rpx]"></view>
                </view>
                <view class="grid grid-cols-2 gap-[20rpx]">
                    <view
                        v-for="item in 2"
                        :key="item"
                        class="bg-[#F8FAFF] rounded-[28rpx] px-[12rpx] py-[28rpx] flex flex-col items-center">
                        <view class="skeleton-block w-[88rpx] h-[88rpx] rounded-[28rpx] mb-[22rpx]"></view>
                        <view class="skeleton-block w-[108rpx] h-[24rpx] mb-[18rpx]"></view>
                        <view class="skeleton-block w-[84rpx] h-[52rpx]"></view>
                        <view class="skeleton-block w-[112rpx] h-[32rpx] rounded-full mt-[18rpx]"></view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<style scoped lang="scss">
.effect-card-shadow {
    box-shadow: 0 8rpx 40rpx rgba(24, 50, 238, 0.1);
}

.skeleton-block,
.skeleton-circle {
    background: linear-gradient(90deg, #edf1f7 25%, #f6f8fb 50%, #edf1f7 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-block {
    border-radius: 12rpx;
}

.skeleton-circle {
    border-radius: 999rpx;
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
