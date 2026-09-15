<template>
    <view class="w-full h-full">
        <view class="welcome-card" v-if="!agent && !model">
            <view class="welcome-body">
                <view class="welcome-title">
                    Hi，我是
                    <text class="title-gradient">AI 大管家</text>
                </view>
                <view class="welcome-sub">
                    {{ phoneControlActive ? "已挂载设备，直接输入手机任务" : "选择一个模型，开启你的智能对话 ✦" }}
                </view>

                <view v-if="phoneControlActive" class="welcome-tip">
                    <text class="welcome-tip-title">一句话操控手机模式</text>
                    <text class="welcome-tip-desc"
                        >当前模式使用 autoglm-phone 执行手机任务，暂不支持切换大模型。</text
                    >
                </view>

                <!-- 首页不再平铺可滚动模型列表：模型多时 iOS 下会导致输入框聚焦后键盘被系统收回，模型切换走底部工具栏 -->
                <view v-else class="welcome-tip">
                    <text class="welcome-tip-title">直接输入，开始对话</text>
                    <text class="welcome-tip-desc">默认使用底部栏已选的大模型，点击底部模型名称可随时切换。</text>
                </view>
            </view>
        </view>

        <view class="agent-selected" v-else-if="agent">
            <view v-if="hasLogo(agent.logo)" class="selected-avatar-ring">
                <image
                    :src="agent.logo"
                    class="selected-avatar"
                    mode="aspectFill"
                    @error="markLogoBroken(agent.logo)" />
            </view>
            <view class="selected-name">{{ agent.name }}</view>
            <view class="selected-intro">{{ agent.intro }}</view>
        </view>

        <view class="agent-selected" v-else-if="model">
            <view v-if="hasLogo(model.logo)" class="selected-avatar-ring model-ring">
                <image
                    :src="model.logo"
                    class="selected-avatar"
                    mode="aspectFill"
                    @error="markLogoBroken(model.logo)" />
            </view>
            <view class="selected-name">{{ model.name }}</view>
        </view>
    </view>
</template>

<script setup lang="ts">
defineProps<{
    agent?: any;
    model?: any;
    phoneControlActive?: boolean;
}>();

const brokenLogos = ref(new Set<string>());

const hasLogo = (logo?: string) => {
    const url = String(logo || "").trim();
    return !!url && !brokenLogos.value.has(url);
};
const markLogoBroken = (logo?: string) => {
    const url = String(logo || "").trim();
    if (!url) return;
    brokenLogos.value = new Set([...brokenLogos.value, url]);
};
</script>

<style scoped lang="scss">
.agent-selected-wrap {
    @apply w-full h-full flex flex-col items-center pt-[160rpx] relative overflow-hidden;
}

.navbar-center {
    @apply w-full flex items-center justify-center px-2;
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
    @apply font-medium text-[#64748B];
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
    @apply pb-[48rpx] px-[48rpx];
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
    @apply text-xs text-[#64748B] mt-[14rpx] leading-relaxed;
}

.welcome-tip {
    @apply mt-[36rpx] rounded-[28rpx] px-[28rpx] py-[24rpx] flex flex-col gap-y-[10rpx];
    background: rgba(239, 246, 255, 0.9);
    border: 1rpx solid rgba(191, 219, 254, 1);
}

.welcome-tip-title {
    @apply text-[28rpx] font-semibold text-[#2563EB];
}

.welcome-tip-desc {
    @apply text-[24rpx] text-[#64748B] leading-relaxed;
}

.agent-selected {
    @apply w-full h-full flex flex-col items-center pt-[14vh] relative;
}

.selected-avatar-ring {
    @apply relative flex items-center justify-center;
    width: 160rpx;
    height: 160rpx;
    border-radius: 50%;
}

.selected-avatar {
    @apply w-full h-full rounded-full;
}

.selected-name {
    @apply text-[34rpx] font-bold text-[#1E3A5F] mt-[24rpx];
}

.selected-intro {
    @apply text-xs text-[#64748B] mt-[12rpx] px-[60rpx] text-center leading-relaxed;
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
