<template>
    <view class="bg-[#F8FAFF] rounded-[28rpx] px-[24rpx] py-[24rpx] flex items-center justify-between gap-[20rpx]">
        <view class="flex items-center gap-[20rpx] flex-1 min-w-0">
            <view class="w-[80rpx] h-[80rpx] rounded-[24rpx] bg-[#EEF2FF] flex items-center justify-center shrink-0">
                <image :src="icon" class="w-[36rpx] h-[36rpx]"></image>
            </view>
            <view class="flex-1 min-w-0">
                <text class="text-[26rpx] font-semibold text-[#111827] line-clamp-1">{{ label }}</text>
                <text class="text-[20rpx] text-[#9CA3AF] mt-[6rpx] block line-clamp-1">{{ desc }}</text>
            </view>
        </view>
        <view class="items-end flex flex-col gap-[10rpx] shrink-0">
            <view class="flex items-baseline">
                <text class="text-[36rpx] font-bold text-[#111827] leading-none">{{ value }}</text>
                <text class="text-[20rpx] text-[#9CA3AF] ml-[4rpx]">{{ unit }}</text>
            </view>
            <text class="text-[20rpx] font-semibold px-[16rpx] py-[4rpx] rounded-full" :class="getTagClass(tagType)">
                {{ tag }}
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
type TagType = "primary" | "success" | "warning" | "danger";

withDefaults(
    defineProps<{
        icon: string;
        label: string;
        desc: string;
        value: number;
        unit: string;
        tag: string;
        tagType: TagType;
    }>(),
    {
        tagType: "primary",
    },
);

const getTagClass = (type?: TagType) => {
    if (type === "success") return "text-[#16A34A] bg-[#DCFCE7]";
    if (type === "warning") return "text-[#D97706] bg-[#FFF3DC]";
    if (type === "danger") return "text-[#DC2626] bg-[#FEE2E2]";
    return "text-[#3D5CF5] bg-[#EEF2FF]";
};
</script>

<style scoped></style>
