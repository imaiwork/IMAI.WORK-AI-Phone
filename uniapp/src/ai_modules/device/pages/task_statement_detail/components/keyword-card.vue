<template>
    <view class="bg-white rounded-[20rpx] px-[32rpx] py-[32rpx]">
        <view class="flex items-center justify-between mb-[28rpx]">
            <view
                class="flex items-center gap-[4rpx] bg-[#FFF3E0] border-[1rpx] border-[#FFD180] rounded-[30rpx] px-[20rpx] py-[6rpx]">
                <text class="text-[26rpx] font-bold text-[#BA6F0D]">#</text>
                <text class="text-[24rpx] font-bold text-[#BA6F0D]">{{ item.exec_keyword }}</text>
            </view>
            <view class="flex items-center gap-[8rpx] bg-[#F5F5F5] rounded-[30rpx] px-[20rpx] py-[6rpx]">
                <view class="w-[14rpx] h-[14rpx] rounded-full bg-gradient-to-br from-[#FF8C42] to-[#FF5C5C]"></view>
                <text class="text-[22rpx] text-[#888888]">识别</text>
                <text class="text-[22rpx] font-bold text-[#FF8C42]">{{ item.number_of_recognitions || 0 }} 次</text>
            </view>
        </view>

        <view class="flex items-stretch bg-[#FAFAFA] rounded-[16rpx] overflow-hidden">
            <view class="flex-1 flex flex-col items-center justify-center py-[24rpx] gap-[8rpx]">
                <text class="text-[44rpx] font-black text-[#FF8C42] leading-none">{{ item.effective_clues || 0 }}</text>
                <text class="text-[22rpx] text-[#AAAAAA] font-medium">有效线索</text>
            </view>

            <view class="w-[1rpx] bg-[#EBEBEB] my-[20rpx]"></view>

            <view class="flex-1 flex flex-col items-center justify-center py-[24rpx] gap-[8rpx]">
                <text class="text-[44rpx] font-black text-[#4CAF50] leading-none">{{
                    item.number_of_valid_clues_included || 0
                }}</text>
                <text class="text-[22rpx] text-[#AAAAAA] font-medium">内含有效</text>
            </view>

            <view class="w-[1rpx] bg-[#EBEBEB] my-[20rpx]"></view>

            <view class="flex-1 flex flex-col items-center justify-center py-[24rpx] gap-[8rpx]">
                <text class="text-[44rpx] font-black text-[#BDBDBD] leading-none">{{
                    item.number_of_invalid_clues || 0
                }}</text>
                <text class="text-[22rpx] text-[#AAAAAA] font-medium">无效线索</text>
            </view>
        </view>

        <view class="h-[1rpx] bg-[#F0F0F0] my-[24rpx]"></view>

        <view class="flex items-center justify-between" @click="onViewDetail">
            <text class="text-[26rpx] font-semibold text-[#333333]">查看详情</text>
            <view class="w-[48rpx] h-[48rpx] rounded-full bg-[#FFF3E0] flex items-center justify-center">
                <u-icon name="arrow-right" color="#FF8C42" size="14"></u-icon>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    item: any;
}>();

const emit = defineEmits<{
    (e: "view-detail"): void;
}>();

const onViewDetail = () => {
    emit("view-detail");
};
</script>

<style scoped></style>
