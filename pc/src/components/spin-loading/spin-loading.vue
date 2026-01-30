<template>
    <Teleport :to="teleportTarget" :disabled="!teleportTarget">
        <Transition
            name="spin-fade"
            enter-active-class="transition-all duration-300 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="opacity-0 backdrop-blur-0"
            leave-to-class="opacity-0 backdrop-blur-0">
            <div
                v-show="currentVisible"
                :class="['spin-overlay', isFullscreen ? 'spin-fullscreen' : 'spin-relative']"
                :style="{ zIndex: currentZIndex }">
                <div class="spin-content">
                    <div class="loader-container">
                        <div class="outer-circle">
                            <div class="outer-arc"></div>
                        </div>

                        <div class="middle-circle">
                            <div class="middle-arc"></div>
                        </div>

                        <div class="inner-core">
                            <div class="core-pulse"></div>
                            <div class="core-icon">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M12 2L15.09 8.26L22 9L15.09 9.74L12 16L8.91 9.74L2 9L8.91 8.26L12 2Z"
                                        fill="currentColor" />
                                </svg>
                            </div>
                        </div>

                        <div class="glow-effect"></div>
                    </div>

                    <div class="info-section">
                        <div class="loading-text">{{ currentText }}</div>
                        <div class="sub-text">请耐心等待...</div>
                        <div class="progress-dots">
                            <span class="dot" v-for="i in 4" :key="i"></span>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
interface SpinProps {
    visible?: boolean | (() => boolean);
    text?: string | (() => string);
    zIndex?: number | (() => number);
    target?: HTMLElement | string;
}

const props = withDefaults(defineProps<SpinProps>(), {
    visible: false,
    text: "正在加载...",
    zIndex: 1000,
    target: undefined,
});

const currentVisible = computed(() => {
    return typeof props.visible === "function" ? props.visible() : props.visible;
});

const currentText = computed(() => {
    return typeof props.text === "function" ? props.text() : props.text;
});

const currentZIndex = computed(() => {
    return typeof props.zIndex === "function" ? props.zIndex() : props.zIndex;
});

const teleportTarget = computed(() => {
    if (typeof props.target === "string") {
        return props.target;
    }
    return props.target ? undefined : "body";
});

const isFullscreen = computed(() => {
    return !props.target || props.target === "body";
});
</script>

<style scoped>
/* 背景样式 - 采用第三个版本的明亮背景 */
.spin-overlay {
    @apply absolute inset-0 flex items-center justify-center;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px) saturate(180%);
    -webkit-backdrop-filter: blur(12px) saturate(180%);
}

.spin-fullscreen {
    @apply fixed inset-0;
    background: rgba(255, 255, 255, 0.98);
}

.spin-content {
    @apply flex flex-col items-center;
    position: relative;
}

.loader-container {
    position: relative;
    width: 80px;
    height: 80px;
    margin-bottom: 1.5rem;
}

/* 外环 - 调整为适合明亮背景的颜色 */
.outer-circle {
    position: absolute;
    inset: 0;
    border-radius: 50%;
}

.outer-arc {
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-top: 3px solid #3b82f6;
    border-right: 3px solid #1d4ed8;
    border-radius: 50%;
    animation: spin 1.5s linear infinite;
    filter: drop-shadow(0 0 8px rgba(59, 130, 246, 0.4));
}

/* 中环 - 调整颜色 */
.middle-circle {
    position: absolute;
    inset: 12px;
    border-radius: 50%;
}

.middle-arc {
    width: 100%;
    height: 100%;
    border: 2px solid transparent;
    border-bottom: 2px solid #8b5cf6;
    border-left: 2px solid #7c3aed;
    border-radius: 50%;
    animation: spin 2s linear infinite reverse;
    filter: drop-shadow(0 0 6px rgba(139, 92, 246, 0.4));
}

/* 内核 - 调整为明亮背景适合的样式 */
.inner-core {
    position: absolute;
    inset: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 2px solid rgba(59, 130, 246, 0.2);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.core-pulse {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
    animation: pulse 2s ease-in-out infinite;
    border: 1px solid rgba(59, 130, 246, 0.1);
}

.core-icon {
    width: 16px;
    height: 16px;
    color: #3b82f6;
    animation: iconRotate 3s ease-in-out infinite;
    filter: drop-shadow(0 0 4px rgba(59, 130, 246, 0.3));
}

/* 光晕效果 - 调整为适合明亮背景 */
.glow-effect {
    position: absolute;
    inset: -20px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 70%);
    animation: glow 2s ease-in-out infinite alternate;
}

/* 文本区域 - 调整为深色文字 */
.info-section {
    text-align: center;
    max-width: 220px;
}

.loading-text {
    color: #1e293b;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    letter-spacing: -0.025em;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.sub-text {
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 400;
    margin-bottom: 1rem;
    opacity: 0.8;
}

.progress-dots {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.dot {
    width: 5px;
    height: 5px;
    background: linear-gradient(45deg, #3b82f6, #8b5cf6);
    border-radius: 50%;
    animation: dotPulse 1.4s ease-in-out infinite;
    box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
}

.dot:nth-child(1) {
    animation-delay: 0s;
}
.dot:nth-child(2) {
    animation-delay: 0.2s;
}
.dot:nth-child(3) {
    animation-delay: 0.4s;
}
.dot:nth-child(4) {
    animation-delay: 0.6s;
}

/* 动画定义 */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes pulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.4;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.7;
    }
}

@keyframes iconRotate {
    0%,
    100% {
        transform: rotate(0deg) scale(1);
        opacity: 0.8;
    }
    50% {
        transform: rotate(180deg) scale(1.1);
        opacity: 1;
    }
}

@keyframes glow {
    0% {
        opacity: 0.3;
        transform: scale(0.95);
    }
    100% {
        opacity: 0.6;
        transform: scale(1.05);
    }
}

@keyframes dotPulse {
    0%,
    80%,
    100% {
        transform: scale(0.8);
        opacity: 0.4;
    }
    40% {
        transform: scale(1.3);
        opacity: 1;
    }
}

/* 暗色主题支持 */
@media (prefers-color-scheme: dark) {
    .spin-overlay {
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(12px) saturate(180%);
    }

    .spin-fullscreen {
        background: rgba(15, 23, 42, 0.98);
    }

    .outer-arc {
        border-top: 3px solid #60a5fa;
        border-right: 3px solid #3b82f6;
        filter: drop-shadow(0 0 8px rgba(96, 165, 250, 0.5));
    }

    .middle-arc {
        border-bottom: 2px solid #a78bfa;
        border-left: 2px solid #8b5cf6;
        filter: drop-shadow(0 0 6px rgba(167, 139, 250, 0.5));
    }

    .inner-core {
        background: linear-gradient(135deg, #1e293b, #334155);
        border: 2px solid rgba(96, 165, 250, 0.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    .core-pulse {
        background: linear-gradient(135deg, rgba(96, 165, 250, 0.2), rgba(167, 139, 250, 0.2));
        border: 1px solid rgba(96, 165, 250, 0.2);
    }

    .core-icon {
        color: #60a5fa;
        filter: drop-shadow(0 0 4px rgba(96, 165, 250, 0.4));
    }

    .glow-effect {
        background: radial-gradient(circle, rgba(96, 165, 250, 0.1) 0%, transparent 70%);
    }

    .loading-text {
        color: #f1f5f9;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .sub-text {
        color: #94a3b8;
    }

    .dot {
        background: linear-gradient(45deg, #60a5fa, #a78bfa);
        box-shadow: 0 0 8px rgba(96, 165, 250, 0.4);
    }
}

/* 响应式调整 */
@media (max-width: 640px) {
    .loader-container {
        width: 70px;
        height: 70px;
    }

    .loading-text {
        font-size: 0.9rem;
    }

    .sub-text {
        font-size: 0.7rem;
    }

    .dot {
        width: 4px;
        height: 4px;
    }
}

/* 高性能优化 */
.loader-container,
.outer-circle,
.middle-circle,
.inner-core {
    will-change: transform;
}

.outer-arc,
.middle-arc,
.core-pulse,
.core-icon,
.dot {
    will-change: transform, opacity;
}

/* 减少动画在低性能设备上的复杂度 */
@media (prefers-reduced-motion: reduce) {
    .outer-arc,
    .middle-arc {
        animation-duration: 3s;
    }

    .core-pulse,
    .glow-effect {
        animation: none;
    }

    .dotPulse {
        animation-duration: 2s;
    }
}
</style>
