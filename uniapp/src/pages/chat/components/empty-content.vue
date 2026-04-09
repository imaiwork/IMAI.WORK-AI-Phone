<template>
    <view class="w-full h-full">
        <view class="welcome-card" v-if="!currAgent && !currModel">
            <view class="welcome-body">
                <view class="welcome-title">
                    Hi，我是
                    <text class="title-gradient">AI 大管家</text>
                </view>
                <view class="welcome-sub">选择一个模型，开启你的智能对话 ✦</view>

                <view class="model-grid">
                    <view
                        v-for="(item, index) in getAIModels"
                        :key="index"
                        class="model-chip"
                        @click="emit('selectModel', item)">
                        <image :src="item.logo" class="chip-logo" mode="aspectFill"></image>
                        <text class="chip-name">{{ item.name }}</text>
                        <view class="chip-badge" v-if="item.id == 10">推荐</view>
                    </view>
                </view>
            </view>
        </view>

        <view class="agent-selected" v-else-if="currAgent">
            <view class="selected-glow agent-glow-color"></view>
            <view class="selected-avatar-ring">
                <image :src="currAgent.avatar || currAgent.image" class="selected-avatar" mode="aspectFill"></image>
            </view>
            <view class="selected-name">{{ currAgent.name }}</view>
            <view class="selected-intro">{{ currAgent.intro || currAgent.introduced }}</view>
        </view>

        <view class="agent-selected" v-else-if="currModel">
            <view class="selected-glow model-glow-color"></view>
            <view class="selected-avatar-ring model-ring">
                <image :src="currModel.logo" class="selected-avatar" mode="aspectFill"></image>
            </view>
            <view class="selected-name">{{ currModel.name }}</view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";

const props = defineProps<{
    currAgent?: any;
    currModel?: any;
}>();

const emit = defineEmits<{
    (event: "selectModel", value: any): void;
}>();

const appStore = useAppStore();

const getAIModels = computed(() => {
    return (appStore.getAiModelConfig?.channel || []).filter((item: any) => item.status == "1");
});
</script>

<style scoped lang="scss">
.agent-selected-wrap {
    @apply w-full h-full flex flex-col items-center pt-[160rpx] relative overflow-hidden;
}

.navbar-center {
    @apply w-full flex items-center justify-center px-4;
}

.glass-tabs {
    @apply flex items-center rounded-full p-[6rpx];
    background: rgba(255, 255, 255, 0.6);
    border: 1.5px solid rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(16px);
    box-shadow: 0 4rpx 20rpx rgba(37, 99, 235, 0.08);
}

.glass-tab-item {
    @apply px-[36rpx] py-[12rpx] rounded-full transition-all;
}

.glass-tab-active {
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    box-shadow: 0 4rpx 16rpx rgba(37, 99, 235, 0.35);
}

.glass-tab-text {
    @apply text-[26rpx] font-medium text-[#64748B];
}

.glass-tab-text-active {
    @apply text-white font-semibold;
}

.page-title {
    @apply flex items-center;
}

.title-text {
    @apply text-[34rpx] font-bold text-[#1E3A5F];
}

.welcome-card {
    @apply w-[92%] mx-auto mt-6 rounded-[40rpx] relative overflow-hidden;
}

.welcome-body {
    @apply pt-[72rpx] pb-[48rpx] px-[48rpx];
}

.welcome-title {
    @apply text-[40rpx] font-bold text-[#1E3A5F] leading-snug;
}

.title-gradient {
    background: linear-gradient(90deg, #2563eb 0%, #60a5fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.welcome-sub {
    @apply text-[24rpx] text-[#64748B] mt-[14rpx] leading-relaxed;
}

.model-grid {
    @apply mt-[36rpx] flex flex-wrap gap-[16rpx];
}

.model-chip {
    @apply flex items-center gap-x-[12rpx] rounded-[100rpx] px-[24rpx] py-[18rpx];
    background: rgba(255, 255, 255, 0.85);
    border: 1.5px solid rgba(255, 255, 255, 1);
    backdrop-filter: blur(8px);
    box-shadow: 0 4rpx 16rpx rgba(37, 99, 235, 0.08);
    transition: all 0.2s ease;
    &:active {
        transform: scale(0.96);
        box-shadow: 0 2rpx 8rpx rgba(37, 99, 235, 0.15);
    }
}

.chip-logo {
    @apply w-[40rpx] h-[40rpx] rounded-full;
}

.chip-name {
    @apply text-[26rpx] font-medium text-[#1E3A5F];
}

.chip-badge {
    @apply text-white text-[20rpx] font-medium px-[12rpx] py-[4rpx] rounded-full;
    background: linear-gradient(90deg, #f59e0b 0%, #ef4444 100%);
}

.agent-selected {
    @apply w-full h-full flex flex-col items-center pt-[28vh] relative;
}

.selected-glow {
    @apply absolute rounded-full pointer-events-none;
    width: 500rpx;
    height: 500rpx;
    top: 10vh;
    left: 50%;
    transform: translateX(-50%);
}

.selected-avatar-ring {
    @apply relative flex items-center justify-center;
    width: 160rpx;
    height: 160rpx;
    border-radius: 50%;
}

.selected-avatar {
    @apply w-full h-full rounded-full;
    border: 4rpx solid white;
}

.selected-name {
    @apply text-[34rpx] font-bold text-[#1E3A5F] mt-[24rpx];
}

.selected-intro {
    @apply text-[24rpx] text-[#64748B] mt-[12rpx] px-[60rpx] text-center leading-relaxed;
}

.selected-badge {
    @apply flex items-center gap-x-[10rpx] mt-[24rpx] px-[28rpx] py-[12rpx] rounded-full;
    background: rgba(37, 99, 235, 0.08);
    border: 1px solid rgba(37, 99, 235, 0.2);
    color: #2563eb;
    font-size: 24rpx;
    font-weight: 500;
}

.model-badge-tag {
    background: rgba(99, 102, 241, 0.08);
    border-color: rgba(99, 102, 241, 0.2);
    color: #6366f1;
}

.badge-dot {
    @apply w-[14rpx] h-[14rpx] rounded-full bg-[#2563EB];
    box-shadow: 0 0 8rpx rgba(37, 99, 235, 0.6);
    animation: dot-pulse 2s infinite;
}

.model-dot {
    @apply bg-[#6366F1];
    box-shadow: 0 0 8rpx rgba(99, 102, 241, 0.6);
}

@keyframes dot-pulse {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(0.75);
    }
}
</style>
