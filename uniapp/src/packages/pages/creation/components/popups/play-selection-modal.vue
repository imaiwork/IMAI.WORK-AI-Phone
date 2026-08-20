<template>
    <view v-if="modelValue" class="modal-container" :class="{ show: modelValue }" @touchmove.stop.prevent>
        <view class="modal-mask" @tap="emit('update:modelValue', false)"></view>
        <view class="modal-content">
            <view class="light-bar"></view>
            <view class="close-btn" @tap="emit('update:modelValue', false)">
                <text class="close-icon">×</text>
            </view>
            <view class="modal-body">
                <view class="header-section">
                    <text class="modal-title">选择播放版本</text>
                    <text class="modal-subtitle">检测到该作品包含 AI 剪辑版本</text>
                </view>
                <view class="action-group">
                    <button
                        class="select-btn primary-btn"
                        hover-class="btn-hover"
                        @tap="emit('play', operateItem.clip_result_url)">
                        <view class="btn-left">
                            <view class="icon-box blue-icon">
                                <text class="icon-text">AI</text>
                            </view>
                            <view class="text-col">
                                <text class="btn-title text-white">播放剪辑视频</text>
                                <text class="btn-desc text-blue-light">AI 智能处理版本</text>
                            </view>
                        </view>
                        <view class="arrow-icon"></view>
                        <view class="shine-effect"></view>
                    </button>
                    <button
                        class="select-btn secondary-btn"
                        hover-class="btn-hover-dark"
                        @click="emit('play', operateItem.video_result_url)">
                        <view class="btn-left">
                            <view class="icon-box gray-icon">
                                <text class="icon-text">原</text>
                            </view>
                            <view class="text-col">
                                <text class="btn-title text-gray">播放数字人视频</text>
                                <text class="btn-desc text-gray-dark">原始生成版本</text>
                            </view>
                        </view>
                        <view class="arrow-icon gray-arrow"></view>
                    </button>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: boolean;
    operateItem: any;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "play", url: string): void;
}>();
</script>

<style scoped lang="scss">
// 从原 creation.vue 整段搬入，scoped 不串
.modal-container {
    position: fixed;
    inset: 0;
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    visibility: hidden;

    &.show {
        visibility: visible;

        .modal-mask {
            opacity: 1;
        }
        .modal-content {
            transform: scale(1);
            opacity: 1;
        }
    }
}

.modal-mask {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(3px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal-content {
    position: relative;
    width: 600rpx;
    background: #0f172a;
    border: 1px solid rgba(51, 65, 85, 0.5);
    border-radius: 32rpx;
    overflow: hidden;
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 20rpx 50rpx -12rpx rgba(0, 0, 0, 0.5);
}

.light-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(0, 101, 251, 0.6), transparent);
}

.close-btn {
    position: absolute;
    top: 20rpx;
    right: 20rpx;
    width: 60rpx;
    height: 60rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;

    .close-icon {
        font-size: 40rpx;
        color: #64748b;
        line-height: 1;
    }
}

.modal-body {
    padding: 48rpx 40rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.header-section {
    text-align: center;
    margin-bottom: 40rpx;
    display: flex;
    flex-direction: column;
    gap: 10rpx;

    .modal-title {
        font-size: 36rpx;
        font-weight: bold;
        color: #ffffff;
        letter-spacing: 1px;
    }

    .modal-subtitle {
        font-size: 24rpx;
        color: #94a3b8;
    }
}

.action-group {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.select-btn {
    position: relative;
    width: 100%;
    height: 120rpx;
    padding: 0 30rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0;
    line-height: normal;
    overflow: hidden;

    &::after {
        border: none;
    }

    .btn-left {
        display: flex;
        align-items: center;
        gap: 24rpx;
        z-index: 2;
    }

    .text-col {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 4rpx;
    }

    .btn-title {
        font-size: 28rpx;
        font-weight: bold;
    }

    .btn-desc {
        font-size: 20rpx;
    }

    .icon-box {
        width: 64rpx;
        height: 64rpx;
        border-radius: 16rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        .icon-text {
            font-size: 24rpx;
            font-weight: 900;
        }
    }
}

.primary-btn {
    background-color: #0065fb;

    .blue-icon {
        background-color: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .text-white {
        color: #ffffff;
    }
    .text-blue-light {
        color: rgba(255, 255, 255, 0.7);
    }

    .arrow-icon {
        width: 16rpx;
        height: 16rpx;
        border-top: 4rpx solid rgba(255, 255, 255, 0.8);
        border-right: 4rpx solid rgba(255, 255, 255, 0.8);
        transform: rotate(45deg);
    }

    .shine-effect {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transform: translateX(-100%);
        animation: shine 3s infinite;
    }
}

@keyframes shine {
    0% {
        transform: translateX(-100%);
    }
    50%,
    100% {
        transform: translateX(100%);
    }
}
</style>
