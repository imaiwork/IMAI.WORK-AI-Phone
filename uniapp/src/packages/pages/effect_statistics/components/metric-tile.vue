<template>
    <view class="bg-[#F8FAFF] rounded-[28rpx] px-[12rpx] py-[28rpx] flex flex-col items-center text-center">
        <view class="w-[88rpx] h-[88rpx] rounded-[28rpx] bg-[#EEF2FF] flex items-center justify-center mb-[22rpx]">
            <image :src="icon" class="w-[40rpx] h-[40rpx]"></image>
        </view>
        <text class="text-[20rpx] text-[#9CA3AF] leading-tight mb-[16rpx]">{{ label }}</text>
        <view class="flex items-baseline justify-center">
            <text
                :class="size === 'large' ? 'text-[52rpx]' : 'text-[40rpx]'"
                class="font-bold text-[#111827] leading-none">
                {{ value }}
            </text>
            <text class="text-[20rpx] font-normal text-[#9CA3AF] ml-[4rpx]">{{ unit }}</text>
        </view>
        <text
            class="mt-[18rpx] text-[20rpx] font-semibold px-[18rpx] py-[6rpx] rounded-full"
            :class="getTagClass(tagType)">
            {{ tag }}
        </text>
    </view>
</template>

<script setup lang="ts">
type TagType = "primary" | "success" | "warning" | "danger";

withDefaults(
    defineProps<{
        icon: string;
        label: string;
        value: number;
        unit: string;
        tag: string;
        tagType?: TagType;
        size?: "normal" | "large";
    }>(),
    {
        tagType: "primary",
        size: "normal",
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
