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

                <view v-if="phoneControlActive" class="phone-control-tip">
                    <text class="phone-control-title">一句话操控手机模式</text>
                    <text class="phone-control-desc"
                        >当前模式使用 autoglm-phone 执行手机任务，暂不支持切换大模型。</text
                    >
                </view>

                <view v-else class="model-scroll-wrap">
                    <scroll-view scroll-y class="model-scroll" @scroll="handleModelScroll">
                        <view class="model-grid">
                            <view
                                v-for="(item, index) in getAIModels"
                                :key="index"
                                class="model-chip"
                                @click="handleSelectModel(item)">
                                <image
                                    v-if="hasLogo(item.logo)"
                                    :src="item.logo"
                                    class="chip-logo"
                                    mode="aspectFill"
                                    @error="markLogoBroken(item.logo)" />
                                <text class="chip-name">{{ item.name }}</text>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-if="showModelScrollHint" class="model-scroll-more">
                        <view class="scroll-more-pill">
                            <text>向下滑动查看更多模型</text>
                            <u-icon name="arrow-down" color="#2563EB" size="22" />
                        </view>
                    </view>
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
import { useAppStore } from "@/stores/app";

defineProps<{
    agent?: any;
    model?: any;
    phoneControlActive?: boolean;
}>();

const emit = defineEmits<{
    (event: "selectModel", value: any): void;
}>();

const appStore = useAppStore();
const modelScrollTop = ref(0);
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

const getAIModels = computed(() => appStore.getAllowedChatModel);

onMounted(() => {
    appStore.ensureMemberQuota();
});

const showModelScrollHint = computed(() => getAIModels.value.length > 10 && modelScrollTop.value < 24);

const handleModelScroll = (event: any) => {
    modelScrollTop.value = event?.detail?.scrollTop || 0;
};

const handleSelectModel = (model: any) => {
    emit("selectModel", model);
};

watch(
    () => getAIModels.value.length,
    () => {
        modelScrollTop.value = 0;
    },
);
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

.model-scroll-wrap {
    @apply mt-[36rpx];
    position: relative;
}

.phone-control-tip {
    @apply mt-[36rpx] rounded-[28rpx] px-[28rpx] py-[24rpx] flex flex-col gap-y-[10rpx];
    background: rgba(239, 246, 255, 0.9);
    border: 1rpx solid rgba(191, 219, 254, 1);
}

.phone-control-title {
    @apply text-[28rpx] font-semibold text-[#2563EB];
}

.phone-control-desc {
    @apply text-[24rpx] text-[#64748B] leading-relaxed;
}

.model-scroll {
    @apply w-full;
    height: 640rpx;
}

.model-grid {
    @apply flex flex-wrap gap-[16rpx] pb-[86rpx];
}

.model-chip {
    @apply flex items-center gap-x-[12rpx] rounded-[100rpx] px-[22rpx] py-[18rpx];
    background: rgba(255, 255, 255, 0.85);
    border: 1rpx solid rgba(255, 255, 255, 1);
    box-shadow: 0 4rpx 16rpx rgba(37, 99, 235, 0.08);
    transition: all 0.2s ease;
    &:active {
        transform: scale(0.96);
        box-shadow: 0 2rpx 8rpx rgba(37, 99, 235, 0.15);
    }
}

.model-scroll-more {
    pointer-events: none;
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 132rpx;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 12rpx;
    background: linear-gradient(180deg, rgba(238, 240, 246, 0) 0%, rgba(238, 240, 246, 0.92) 62%, #eef0f6 100%);
}

.scroll-more-pill {
    @apply flex items-center gap-[8rpx] rounded-full px-[22rpx] py-[10rpx] text-[22rpx] font-semibold text-primary;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(191, 219, 254, 1);
    box-shadow: 0 8rpx 24rpx rgba(37, 99, 235, 0.12);
}

.chip-logo {
    @apply w-[40rpx] h-[40rpx] rounded-full;
}

.chip-name {
    @apply font-medium text-[#1E3A5F] text-xs line-clamp-1;
}

.chip-badge {
    @apply text-white text-[20rpx] font-medium px-[12rpx] py-[4rpx] rounded-full;
    background: linear-gradient(90deg, #f59e0b 0%, #ef4444 100%);
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
