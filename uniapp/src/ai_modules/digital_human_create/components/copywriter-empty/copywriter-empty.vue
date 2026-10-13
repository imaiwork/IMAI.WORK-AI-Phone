<template>
    <view class="flex flex-col items-center justify-center h-full px-8">
        <view class="relative mb-[48rpx]">
            <view class="w-[280rpx] h-[280rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                <view class="w-[200rpx] h-[200rpx] rounded-full bg-[#DBEAFE] flex items-center justify-center">
                    <view
                        class="w-[120rpx] h-[120rpx] rounded-[32rpx] flex items-center justify-center shadow-[0_8rpx_24rpx_rgba(0,101,251,0.25)]"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                        <u-icon name="edit-pen" color="#fff" size="44" />
                    </view>
                </view>
            </view>
            <view
                class="absolute top-[8rpx] right-[-12rpx] w-[56rpx] h-[56rpx] rounded-full shadow-[0_4rpx_12rpx_rgba(0,101,251,0.15)] flex items-center justify-center"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                <image src="/static/images/common/magic_white.png" class="w-[28rpx] h-[28rpx]" />
            </view>
            <view
                class="absolute bottom-[8rpx] left-[-12rpx] w-[56rpx] h-[56rpx] rounded-full bg-[#FEF9C3] shadow-[0_4rpx_12rpx_rgba(0,0,0,0.08)] flex items-center justify-center">
                <text class="font-extrabold text-[#D97706]">#</text>
            </view>
        </view>
        <text class="text-[34rpx] font-extrabold text-[#0D1117] mb-[16rpx]">{{ title }}</text>
        <text class="text-[#9CA3AF] text-center leading-relaxed mb-[64rpx]">
            {{ description }}
        </text>
    </view>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        description: string;
    }>(),
    {
        title: "还没有文案",
        description: "手动输入或使用 AI 一键生成您的发布文案",
    },
);
</script>

<style scoped></style>
