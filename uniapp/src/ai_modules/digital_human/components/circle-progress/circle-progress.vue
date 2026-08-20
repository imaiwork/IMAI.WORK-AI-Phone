<template>
    <div
        class="circle-progress"
        :style="{
            '--progress-deg': progressDeg + 'deg',
            '--progress-color': progressColor,
            '--not-progress-color': notProgressColor,
            '--border-width': borderWidth,
            '--bg-color': bgColor,
            '--width': width,
            '--color': color,
            '--percent': percent,
        }">
        <div class="progress-text">
            <text v-if="!$slots.text">{{ percent }}%</text>
            <slot v-else name="text"></slot>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    width?: string;
    borderWidth?: string;
    bgColor?: string;
    notProgressColor?: string;
    progressColor?: string;
    color?: string;
    percent?: number;
}

const props = withDefaults(defineProps<Props>(), {
    width: "100rpx",
    borderWidth: "20rpx",
    bgColor: "#fff",
    notProgressColor: "#ddd",
    progressColor: "#07c160",
    color: "#000000",
    percent: 0,
});

const progressDeg = computed(() => {
    return (props.percent / 100) * 360;
});
</script>

<style lang="scss" scoped>
.circle-progress {
    position: relative;
    width: var(--width);
    height: var(--width);
    border-radius: 50%;
    background: conic-gradient(
        from 0deg,
        var(--progress-color) 0deg,
        var(--progress-color) var(--progress-deg, 0deg),
        var(--not-progress-color) var(--progress-deg, 0deg),
        var(--not-progress-color) 360deg
    );
    transition: all 0.5s ease-in-out;
    &::before {
        content: "";
        position: absolute;
        top: var(--border-width);
        left: var(--border-width);
        width: calc(var(--width) - var(--border-width) * 2);
        height: calc(var(--width) - var(--border-width) * 2);
        background: var(--bg-color);
        border-radius: 50%;
    }
}
.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
    font-weight: bold;
    color: #1f2937;
}
</style>
