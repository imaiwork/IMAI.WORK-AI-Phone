<template>
    <view class="bg-white rounded-[32rpx] p-[32rpx] effect-card-shadow">
        <view class="flex items-center justify-between mb-[28rpx]">
            <view class="flex items-center gap-[14rpx]">
                <view class="w-[6rpx] h-[34rpx] rounded-full bg-[#2845F5]"></view>
                <text class="text-sm font-bold text-[#111827]">{{ title }}</text>
            </view>
            <text
                v-if="badge"
                class="text-[20rpx] font-semibold px-[20rpx] py-[8rpx] rounded-full"
                :class="getTagClass(badgeType)">
                {{ badge }}
            </text>
        </view>
        <slot />
    </view>
</template>

<script setup lang="ts">
type TagType = "primary" | "success" | "warning" | "danger";

withDefaults(
    defineProps<{
        title: string;
        badge?: string;
        badgeType: TagType;
    }>(),
    {
        badge: "",
        badgeType: "primary",
    },
);

const getTagClass = (type?: TagType) => {
    if (type === "success") return "text-[#16A34A] bg-[#DCFCE7]";
    if (type === "warning") return "text-[#D97706] bg-[#FFF3DC]";
    if (type === "danger") return "text-[#DC2626] bg-[#FEE2E2]";
    return "text-[#3D5CF5] bg-[#EEF2FF]";
};
</script>

<style scoped lang="scss">
.effect-card-shadow {
    box-shadow: 0 8rpx 40rpx rgba(24, 50, 238, 0.1);
}
</style>
