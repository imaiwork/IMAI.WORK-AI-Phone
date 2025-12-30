<template>
    <view class="range-slider-card">
        <view class="status-header">
            <view class="value-display">
                <text class="current-range">{{ modelStart }} — {{ modelEnd }}</text>
                <text class="unit-label">已选范围 (总计: {{ total }})</text>
            </view>
            <view class="badge" :class="{ 'is-limit': modelEnd - modelStart >= maxDiff }">
                <text class="badge-text">跨度: {{ modelEnd - modelStart }} / {{ maxDiff }}</text>
            </view>
        </view>

        <view class="slider-wrapper">
            <view class="track-container" id="sliderTrack" style="touch-action: none">
                <view class="track-base"> </view>

                <view
                    class="track-fill"
                    :style="{
                        left: (modelStart / total) * 100 + '%',
                        width: ((modelEnd - modelStart) / total) * 100 + '%',
                        marginLeft: handleSize / 2 + 'px',
                        marginRight: handleSize / 2 + 'px',
                    }"></view>

                <view
                    class="handle-item"
                    :class="{ 'is-active': dragType === 'left' }"
                    :style="{
                        left: (modelStart / total) * 100 + '%',
                        zIndex: dragType === 'left' ? 30 : 10,
                    }"
                    @touchstart.stop.prevent="onTouchStart('left', $event)"
                    @touchmove.stop.prevent="onTouchMove"
                    @touchend="onTouchEnd">
                    <view class="handle-inner"></view>
                    <view class="pop-tooltip">{{ modelStart }}</view>
                </view>

                <view
                    class="handle-item"
                    :class="{ 'is-active': dragType === 'right' }"
                    :style="{
                        left: (modelEnd / total) * 100 + '%',
                        zIndex: dragType === 'right' ? 30 : 10,
                    }"
                    @touchstart.stop.prevent="onTouchStart('right', $event)"
                    @touchmove.stop.prevent="onTouchMove"
                    @touchend="onTouchEnd">
                    <view class="handle-inner"></view>
                    <view class="pop-tooltip">{{ modelEnd }}</view>
                </view>
            </view>
        </view>

        <view class="footer-action">
            <view
                class="pan-control"
                :class="{ 'pan-active': dragType === 'body' }"
                @touchstart.stop.prevent="onTouchStart('body', $event)"
                @touchmove.stop.prevent="onTouchMove"
                @touchend="onTouchEnd">
                <view class="pan-content">
                    <text class="pan-icon">{{ dragType === "body" ? "⋯" : "⠿" }}</text>
                    <text>{{ dragType === "body" ? "正在平移中..." : "按住平移区间" }}</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from "vue";

interface Props {
    total?: number;
    start?: number;
    end?: number;
    maxDiff?: number;
}

const props = withDefaults(defineProps<Props>(), {
    total: 10,
    start: 0,
    end: 4,
    maxDiff: 10,
});

const emit = defineEmits(["change"]);

const { proxy } = getCurrentInstance() as any;

// 响应式状态
const modelStart = ref(props.start);
const modelEnd = ref(props.end);
const handleSize = 32;
const trackWidth = ref(0);
const trackLeft = ref(0);

// 内部计算变量（不触发渲染，提高性能）
let dragType = "";
let startX = 0;
let isMoving = false; // 是否触发了阈值
const MOVE_THRESHOLD = 5; // 物理移动超过 5px 才认为在拖动

// 缓存原始值用于增量平移
let cacheS = 0;
let cacheE = 0;

const updateRect = () => {
    uni.createSelectorQuery()
        .in(proxy)
        .select("#sliderTrack")
        .boundingClientRect((data: any) => {
            if (data) {
                trackWidth.value = data.width;
                trackLeft.value = data.left;
            }
        })
        .exec();
};

// 绝对坐标映射函数
const getValByClientX = (clientX: number) => {
    const usablePx = trackWidth.value - handleSize;
    const relativeX = clientX - trackLeft.value - handleSize / 2;
    const percent = relativeX / usablePx;
    const val = Math.round(percent * props.total);
    return Math.max(0, Math.min(props.total, val));
};

const onTouchStart = (type: string, e: TouchEvent) => {
    dragType = type;
    startX = e.touches[0].clientX;
    cacheS = modelStart.value;
    cacheE = modelEnd.value;
    isMoving = false; // 重置移动判定
    updateRect(); // 实时校准
};

const onTouchMove = (e: TouchEvent) => {
    if (!dragType || trackWidth.value === 0) return;
    const currentX = e.touches[0].clientX;

    // --- 阈值判定 ---
    const distance = Math.abs(currentX - startX);
    if (!isMoving && distance < MOVE_THRESHOLD) return;
    isMoving = true; // 超过阈值，开始处理逻辑

    const usablePx = trackWidth.value - handleSize;

    if (dragType === "left") {
        const val = getValByClientX(currentX);
        let target = Math.min(modelEnd.value, val);
        if (modelEnd.value - target > props.maxDiff) target = modelEnd.value - props.maxDiff;
        if (modelStart.value !== target) {
            modelStart.value = target;
            triggerEmit();
        }
    } else if (dragType === "right") {
        const val = getValByClientX(currentX);
        let target = Math.max(modelStart.value, val);
        if (target - modelStart.value > props.maxDiff) target = modelStart.value + props.maxDiff;
        target = Math.min(props.total, target);
        if (modelEnd.value !== target) {
            modelEnd.value = target;
            triggerEmit();
        }
    } else if (dragType === "body") {
        // 平移逻辑也加入平滑处理
        const diffVal = Math.round(((currentX - startX) / usablePx) * props.total);
        let s = cacheS + diffVal;
        let ev = cacheE + diffVal;
        const range = cacheE - cacheS;

        if (s < 0) {
            s = 0;
            ev = range;
        } else if (ev > props.total) {
            ev = props.total;
            s = props.total - range;
        }

        if (modelStart.value !== s || modelEnd.value !== ev) {
            modelStart.value = s;
            modelEnd.value = ev;
            triggerEmit();
        }
    }
};

const onTouchEnd = () => {
    dragType = "";
    isMoving = false;
};

const triggerEmit = () => {
    emit("change", { start: modelStart.value, end: modelEnd.value });
};

onMounted(() => nextTick(updateRect));
</script>

<style scoped>
.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}
.current-range {
    font-size: 32px;
    font-weight: 800;
    color: #1a1a1a;
    letter-spacing: -1px;
}
.unit-label {
    font-size: 11px;
    color: #bbb;
    text-transform: uppercase;
    margin-top: 2px;
}

.badge {
    background: #f0f7ff;
    padding: 4px 10px;
    border-radius: 8px;
    transition: all 0.3s;
}
.badge.is-limit {
    background: #fff1f0;
}
.badge-text {
    font-size: 11px;
    color: #007aff;
    font-weight: bold;
}
.is-limit .badge-text {
    color: #ff4d4f;
}

.track-container {
    position: relative;
    height: 32px;
    width: 100%;
    margin: 10px 0;
}
.track-base {
    position: absolute;
    top: 50%;
    left: 16px;
    right: 16px;
    height: 6px;
    background: #f2f2f2;
    border-radius: 10px;
    transform: translateY(-50%);
}
.tick {
    position: absolute;
    width: 4px;
    height: 4px;
    background: #e0e0e0;
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
}

.track-fill {
    position: absolute;
    top: 50%;
    height: 6px;
    background: linear-gradient(90deg, #007aff, #4facfe);
    border-radius: 10px;
    transform: translateY(-50%);
    z-index: 1;
}

.handle-item {
    position: absolute;
    top: 0;
    width: 32px;
    height: 32px;
    display: flex;
    justify-content: center;
    align-items: center;
}
.handle-inner {
    width: 24px;
    height: 24px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.25);
    border: 4px solid #007aff;
    transition: transform 0.1s;
}
.handle-item.is-active .handle-inner {
    transform: scale(1.1);
    border-width: 5px;
}

.pop-tooltip {
    position: absolute;
    top: -45px;
    background: #1a1a1a;
    color: #fff;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: bold;
    pointer-events: none;
}
.pop-tooltip::after {
    content: "";
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid #1a1a1a;
}

.footer-action {
    margin-top: 35px;
}
.pan-control {
    background: #fcfcfc;
    border: 2px dashed #eee;
    border-radius: 16px;
    padding: 14px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.pan-active {
    background: #1a1a1a;
    border-style: solid;
    border-color: #1a1a1a;
    transform: translateY(-2px);
}
.pan-content {
    display: flex;
    justify-content: center;
    align-items: center;
    color: #888;
    font-size: 13px;
    font-weight: 500;
}
.pan-active .pan-content {
    color: #fff;
}
.pan-icon {
    font-size: 18px;
    margin-right: 8px;
    font-weight: bold;
}
</style>
