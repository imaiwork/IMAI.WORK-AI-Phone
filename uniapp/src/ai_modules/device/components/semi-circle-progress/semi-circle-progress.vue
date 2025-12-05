<template>
    <view class="progress-container" :style="{ width: size + 'px', height: size / 2 + 'px' }">
        <canvas
            v-if="!imagePath"
            :canvas-id="canvasId"
            :id="canvasId"
            :style="{ width: size + 'px', height: size / 2 + 'px' }"
            :width="canvasWidth"
            :height="canvasHeight"></canvas>
        <image v-else :src="imagePath" :style="{ width: size + 'px', height: size / 2 + 'px' }"></image>
        <view class="content">
            <slot></slot>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps({
    progress: {
        type: Number,
        default: 0,
    },
    size: {
        type: Number,
        default: 180,
    },
    strokeWidth: {
        type: Number,
        default: 12,
    },
    color: {
        type: String,
        default: "#0065FB",
    },
    bgColor: {
        type: String,
        default: "rgba(0, 0, 0, 0.05)",
    },
    animationDuration: {
        type: Number,
        default: 800,
    },
    animation: {
        type: Boolean,
        default: true,
    },
});

const instance = getCurrentInstance();
const canvasId = "progressCanvas" + new Date().getTime();

const pixelRatio = ref(1);
const canvasWidth = ref(props.size);
const canvasHeight = ref(props.size / 2);

const imagePath = ref("");

const { proxy } = getCurrentInstance() as { proxy: any };

const currentProgress = ref(0);
let animationTimer: any = null;

const drawCanvas = (generateImage = false) => {
    const ctx = uni.createCanvasContext(canvasId, instance);

    ctx.setTransform(1, 0, 0, 1, 0, 0);

    const centerX = props.size / 2;
    const centerY = props.size / 2;
    const radius = (props.size - props.strokeWidth) / 2;

    ctx.clearRect(0, 0, props.size, props.size / 2);

    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, Math.PI, 2 * Math.PI);
    ctx.setStrokeStyle(props.bgColor);
    ctx.setLineWidth(props.strokeWidth);
    ctx.setLineCap("round");
    ctx.stroke();

    if (currentProgress.value > 0) {
        const endAngle = Math.PI + (currentProgress.value / 100) * Math.PI;
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, Math.PI, endAngle);
        ctx.setStrokeStyle(props.color);
        ctx.setLineWidth(props.strokeWidth);
        ctx.setLineCap("round");
        ctx.stroke();
    }

    ctx.draw(false, () => {
        if (!generateImage) return;
        uni.canvasToTempFilePath(
            {
                y: 0,
                x: 0,
                width: props.size,
                height: props.size / 2,
                canvasId,
                success: (res) => {
                    if (res.tempFilePath) {
                        imagePath.value = res.tempFilePath;
                    }
                },
            },
            proxy
        );
    });
};

const animateProgress = (start: number, end: number) => {
    if (animationTimer) {
        clearTimeout(animationTimer);
        animationTimer = null;
    }

    imagePath.value = "";

    const duration = props.animationDuration;
    const startTime = Date.now();

    const step = () => {
        const elapsed = Date.now() - startTime;
        const progressRatio = props.animation ? Math.min(elapsed / duration, 1) : 1;
        currentProgress.value = start + (end - start) * progressRatio;

        const isFinished = progressRatio === 1;
        drawCanvas(isFinished);

        if (!isFinished) {
            animationTimer = setTimeout(step, 16);
        } else {
            clearTimeout(animationTimer);
            animationTimer = null;
        }
    };
    step();
};

onMounted(() => {
    uni.getSystemInfo({
        success: (res) => {
            pixelRatio.value = res.pixelRatio;
            canvasWidth.value = props.size * pixelRatio.value;
            canvasHeight.value = (props.size / 2) * pixelRatio.value;
            animateProgress(0, props.progress);
        },
    });
});

watch(
    () => props.progress,
    (newProgress, oldProgress) => {
        animateProgress(oldProgress || 0, newProgress);
    }
);
</script>

<style scoped lang="scss">
.progress-container {
    @apply relative;
    @apply mx-auto;
}
canvas {
    @apply absolute top-0 left-0;
}
.content {
    @apply absolute top-0 left-0 w-full h-full flex flex-col items-center justify-end;
}
</style>
