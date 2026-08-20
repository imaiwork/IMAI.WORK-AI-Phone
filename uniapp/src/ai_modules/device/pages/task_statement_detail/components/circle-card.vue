<template>
    <view class="bg-white rounded-[20rpx] px-[32rpx] py-[36rpx] flex gap-[28rpx]">
        <view class="shrink-0 flex flex-col items-center">
            <image
                src="/static/images/common/wechat_s.png"
                lazy-load
                class="w-[88rpx] h-[88rpx] rounded-full border-[4rpx] border-[#FFE0B2]"></image>
        </view>

        <view class="flex-1 min-w-0">
            <view class="flex items-center justify-between">
                <text class="text-[30rpx] font-semibold text-[#1A1A1A]">{{ item.nickname }}</text>
            </view>

            <view
                class="mt-[20rpx] bg-[#FFF8F2] border-[1rpx] border-[#FFE5CC] border-l-[6rpx] border-l-[#FF8C42] rounded-[16rpx] px-[24rpx] py-[20rpx]">
                <view class="flex gap-[16rpx] items-start">
                    <image
                        :src="item.execute_avatar"
                        lazy-load
                        class="w-[56rpx] h-[56rpx] rounded-full shrink-0"></image>
                    <view class="flex-1 min-w-0">
                        <text class="text-[22rpx] font-semibold text-[#BA6F0D]">{{ item.execute_name }}</text>
                        <view class="text-[22rpx] text-[#555555] mt-[6rpx] leading-relaxed">
                            {{ item.comment }}
                        </view>
                    </view>
                </view>

                <view class="h-[1rpx] bg-[#FFE5CC] my-[16rpx]"></view>

                <view class="flex items-center gap-[10rpx]" v-if="[2, 3].includes(item.type)">
                    <view
                        class="w-[32rpx] h-[32rpx] rounded-full bg-gradient-to-br from-[#FF8C42] to-[#FF5C5C] flex items-center justify-center">
                        <text class="text-[20rpx] text-white">✓</text>
                    </view>
                    <text class="text-xs font-medium text-[#FF8C42]">已点赞了Ta</text>
                </view>
            </view>

            <view class="flex items-center gap-[10rpx] mt-[16rpx]">
                <view class="w-[10rpx] h-[10rpx] rounded-full bg-[#D0D0D0] shrink-0"></view>
                <text class="text-[22rpx] text-[#AAAAAA]"
                    >执行：{{ item.execute_name }}（{{ item.execute_account }}）</text
                >
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    item: any;
}>();
</script>

<style scoped></style>
