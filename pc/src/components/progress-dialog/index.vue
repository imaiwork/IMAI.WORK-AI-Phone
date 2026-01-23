<template>
    <popup
        ref="popupRef"
        width="460px"
        title="上传进度"
        cancel-button-text=""
        confirm-button-text=""
        destroy-on-close
        overflow
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :show-close="false"
        @close="close">
        <div class="dialog-body">
            <DefineTemplate v-slot="{ percentage }">
                <div class="progress-wrapper">
                    <div class="percentage-display">
                        <span class="number">{{ percentage }}</span>
                        <span class="unit">%</span>
                    </div>

                    <div class="premium-progress-track">
                        <div
                            class="premium-progress-fill"
                            :style="{
                                width: percentage + '%',
                                background: progressGradient(percentage),
                            }">
                            <div class="inner-stripe"></div>
                            <div class="progress-head-glow"></div>
                        </div>
                    </div>
                </div>
            </DefineTemplate>
            <UseTemplate v-for="percentage in getPercentage" :percentage="percentage" />
        </div>

        <Transition name="slide-up">
            <div v-if="isFinished && showConfirmButton" class="footer-action">
                <button class="confirm-action-btn" @click="close">完成</button>
            </div>
        </Transition>
    </popup>
</template>

<script setup lang="ts">
import { createReusableTemplate } from "@vueuse/core";

const props = withDefaults(
    defineProps<{
        percentage: number | number[];
        showConfirmButton?: boolean;
    }>(),
    {
        percentage: 0,
        showConfirmButton: true,
    }
);

const emit = defineEmits<{
    (e: "close"): void;
}>();

const popupRef = shallowRef();

const getPercentage = computed(() => {
    return Array.isArray(props.percentage) ? props.percentage : [props.percentage];
});

const currentPercentage = computed(() => {
    if (Array.isArray(props.percentage)) {
        return props.percentage.reduce((acc, curr) => acc + curr, 0) / props.percentage.length || 0;
    }
    return props.percentage;
});

const isFinished = computed(() => currentPercentage.value === 100);

// 动态渐变色计算
const progressGradient = (percentage: number) => {
    if (percentage === 100) return "linear-gradient(90deg, #10b981, #34d399)";
    return "linear-gradient(90deg, #6366f1 0%, #a855f7 50%, #ec4899 100%)";
};

const open = () => popupRef.value.open();
const close = () => {
    emit("close");
};

const [DefineTemplate, UseTemplate] = createReusableTemplate();

defineExpose({ open, close });
</script>

<style scoped>
/* --- 布局重塑 --- */
.dialog-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 10px 0;
}

.status-indicator {
    position: relative;
    width: 12px;
    height: 12px;
    margin-top: 6px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #6366f1;
    z-index: 2;
    position: relative;
    transition: background 0.5s;
}

.status-dot.is-complete {
    background: #10b981;
}

.pulse-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    background: #6366f1;
    border-radius: 50%;
    animation: pulse-ring 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
}

.premium-progress-track {
    height: 14px;
    background: #f1f5f9;
    border-radius: 10px;
    position: relative;
    overflow: hidden;
    /* 内阴影增加深度感 */
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}

.premium-progress-fill {
    height: 100%;
    border-radius: 10px;
    position: relative;
    transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.inner-stripe {
    position: absolute;
    inset: 0;
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%
    );
    background-size: 30px 30px;
    animation: move-stripe 1.5s linear infinite;
}

.progress-head-glow {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 20px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6));
    filter: blur(2px);
}

.percentage-display {
    display: flex;
    align-items: baseline;
    justify-content: flex-end;
    margin-bottom: 8px;
}

.percentage-display .number {
    font-size: 2.8rem;
    font-weight: 800;
    letter-spacing: -1px;
    background: linear-gradient(180deg, #1e293b, #64748b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.footer-action {
    margin-top: 24px;
}

.confirm-action-btn {
    width: 100%;
    padding: 14px;
    background: #1e293b;
    color: white;
    border: none;
    border-radius: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.confirm-action-btn:hover {
    background: #0f172a;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

/* --- 动画 --- */
@keyframes move-stripe {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 30px 0;
    }
}

@keyframes pulse-ring {
    0% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    100% {
        transform: scale(2.2);
        opacity: 0;
    }
}

.slide-up-enter-active {
    transition: all 0.4s ease-out;
}
.slide-up-enter-from {
    opacity: 0;
    transform: translateY(10px);
}
</style>
