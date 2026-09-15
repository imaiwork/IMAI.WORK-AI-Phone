<template>
    <view class="run-card">
        <view class="run-header">
            <view class="run-status">
                <view class="run-dot" :class="statusDotClass"></view>
                <text class="run-title">{{ statusTitle }}</text>
            </view>
            <text v-if="!isProductDirect" class="run-mode-chip">{{ modeLabel }}</text>
            <view v-if="remainSeconds > 0" class="run-next run-countdown">
                <text class="run-countdown-value">{{ remainText }}</text>
                <text class="text-[20rpx] leading-[1.5] text-[#647184]">距开始</text>
            </view>
            <text v-else-if="nextRunLabel" class="run-meta run-next">
                {{ isWaitingFirstRun ? '首次' : '下一轮' }} {{ nextRunLabel }}
            </text>
        </view>

        <!-- 成品直发不进合成链路，三个「生成」数永远是 0，摆在这里只会误导；换成这条路上真实存在的数 -->
        <view v-if="isProductDirect" class="grid grid-cols-2 gap-[16rpx]">
            <view class="flex flex-col gap-[4rpx]">
                <text class="run-metric">{{ directMaterialCount }}</text>
                <text class="run-meta">成品库待发</text>
            </view>
            <view class="flex flex-col gap-[4rpx]">
                <text class="run-metric text-primary">{{ slotMarks.length }}</text>
                <text class="run-meta">每日发布次数</text>
            </view>
        </view>
        <view v-else class="grid grid-cols-3 gap-[16rpx]">
            <view class="flex flex-col gap-[4rpx]">
                <text class="run-metric">{{ todaySuccess }}</text>
                <text class="run-meta">今日已生成</text>
            </view>
            <view class="flex flex-col gap-[4rpx]">
                <text class="run-metric" :class="generating ? 'text-primary' : ''">{{ generating }}</text>
                <text class="run-meta">生成中</text>
            </view>
            <view class="flex flex-col gap-[4rpx]">
                <text class="run-metric" :class="todayFailed ? 'text-error' : ''">{{ todayFailed }}</text>
                <text class="run-meta">今日失败</text>
            </view>
        </view>

        <view class="flex flex-col gap-[14rpx]">
            <view class="run-track">
                <view v-if="!isProductDirect" class="run-seg" style="left: 0%; width: 12.5%; background: #B3D0FE"></view>
                <view v-if="!isProductDirect" class="run-seg" style="left: 12.5%; width: 12.5%; background: #66A2FD"></view>
                <view
                    v-for="mark in slotMarks"
                    :key="mark"
                    class="run-seg run-seg--mark"
                    :style="{ left: `${mark}%` }"></view>
            </view>

            <view class="run-legend">
                <view v-if="!isProductDirect" class="run-legend-item">
                    <view class="run-legend-dot" style="background: #b3d0fe"></view>
                    <text class="run-meta">采集 00–03</text>
                </view>
                <view v-if="!isProductDirect" class="run-legend-item">
                    <view class="run-legend-dot" style="background: #66a2fd"></view>
                    <text class="run-meta">合成 03–06</text>
                </view>
                <view v-if="slotMarks.length" class="run-legend-item">
                    <view class="run-legend-dot bg-primary"></view>
                    <text class="run-meta">发布 {{ slotMarks.length }} 次</text>
                </view>
                <view class="run-flow active:opacity-70" @click="emit('flow')">
                    <text class="text-[22rpx] font-semibold text-primary">任务流</text>
                    <u-icon name="arrow-right" color="#0065FB" size="22"></u-icon>
                </view>
            </view>
            <text v-if="runNote" class="run-meta">{{ runNote }}</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { isCloudRunMode } from "@/ai_modules/person/enums";
import { useFirstRunCountdown } from "../hooks/useFirstRunCountdown";
import { formatNextRun } from "../hooks/useSetupChecklist";

const props = withDefaults(
    defineProps<{
        runMode: number;
        todaySuccess: number;
        generating: number;
        todayFailed: number;
        nextRunAt: number;
        /** 发布时段的 HH:MM 列表，用来在节奏条上画真实发布点 */
        publishSlots: string[];
        /** 无手机模式每天做几条 */
        dailyCount: number;
        /** 工作方式是成品库直发（人设 publish_mode=2）：不经 AI 合成，运行模式对它没有意义 */
        isProductDirect: boolean;
        /** 成品直发库里待发的条数，成品直发时替代「今日已生成」 */
        directMaterialCount?: number;
        hasIssue: boolean;
        isFirstRunPending?: boolean;
    }>(),
    { isFirstRunPending: false, directMaterialCount: 0 },
);

const emit = defineEmits<{
    (e: "flow"): void;
}>();

const MINUTES_PER_DAY = 24 * 60;

const isCloudMode = computed(() => isCloudRunMode(props.runMode));

const modeLabel = computed(() => (isCloudMode.value ? "无手机模式" : "手机模式"));

const runNote = computed(() => {
    if (props.isProductDirect) return "从成品直发库按规则取用已上传的成品，跳过 AI 合成。";
    if (isCloudMode.value) return `每日 ${props.dailyCount || 1} 条 · 完成后下载，手动发布`;
    return "";
});

const isWaitingFirstRun = computed(
    () => props.isFirstRunPending && !props.generating && !props.todaySuccess && !props.todayFailed,
);

const { remainSeconds, remainText } = useFirstRunCountdown(
    () => props.nextRunAt,
    () => isWaitingFirstRun.value && !isCloudMode.value,
);

const statusTitle = computed(() => {
    if (props.generating > 0) return props.hasIssue ? "运行中 · 有异常" : "运行中";
    if (props.hasIssue) return "有异常待处理";
    return isWaitingFirstRun.value ? "等待首次运行" : "等待下一轮";
});

const statusDotClass = computed(() => {
    if (props.hasIssue) return "bg-warning";
    return props.generating > 0 ? "bg-success" : "bg-[#94A3B8]";
});

const nextRunLabel = computed(() => formatNextRun(props.nextRunAt));

// HH:MM 换算成一天里的百分比位置
const slotMarks = computed(() =>
    (props.publishSlots ?? [])
        .map((slot) => {
            const matched = /^(\d{1,2}):(\d{2})$/.exec(String(slot));
            if (!matched) return -1;
            return ((Number(matched[1]) * 60 + Number(matched[2])) / MINUTES_PER_DAY) * 100;
        })
        .filter((percent) => percent >= 0),
);
</script>

<style lang="scss" scoped>
.run-card {
    @apply bg-white rounded-[32rpx] p-[28rpx] flex flex-col gap-[26rpx] text-[#202937];
    box-shadow: 0 8rpx 36rpx rgba(16, 40, 90, 0.06);
}

.run-header {
    @apply flex flex-wrap items-center gap-x-[16rpx] gap-y-[8rpx];
}

.run-status {
    @apply flex items-center gap-[16rpx] min-w-0;
}

.run-title {
    @apply text-base font-semibold leading-[1.5] text-[#202937];
}

.run-dot {
    @apply w-[14rpx] h-[14rpx] rounded-full shrink-0;
}

.run-mode-chip {
    @apply shrink-0 text-[22rpx] leading-[1.5] text-[#647184] bg-[#F1F4F8] rounded-[10rpx] px-[14rpx] py-[4rpx];
}

.run-meta {
    @apply text-[22rpx] leading-[1.5] text-[#647184];
}

.run-next {
    @apply ml-auto text-right;
}

.run-countdown {
    @apply shrink-0 flex flex-col items-end gap-[4rpx];
}

.run-countdown-value {
    @apply text-[30rpx] font-bold leading-[1.2];
    font-variant-numeric: tabular-nums;
}

.run-metric {
    @apply text-[44rpx] font-bold leading-[52rpx];
}

.run-track {
    @apply relative h-[16rpx] rounded-[8rpx] bg-[#F2F3F5] overflow-hidden;
}

.run-seg {
    @apply absolute top-0 bottom-0;
}

.run-seg--mark {
    @apply bg-primary;
    width: 2.4%;
}

.run-legend-dot {
    @apply w-[12rpx] h-[12rpx] rounded-full shrink-0;
}

.run-legend {
    @apply flex flex-wrap items-center gap-x-[24rpx] gap-y-[16rpx];
}

.run-legend-item {
    @apply flex items-center gap-[8rpx] shrink-0;
}

.run-flow {
    @apply relative shrink-0 ml-auto flex items-center gap-[4rpx] h-[48rpx] px-[12rpx] mr-[-12rpx];
}

// 保留轻量文字入口，透明热区在原有间距内扩展到 88rpx 高。
.run-flow::before {
    content: "";
    @apply absolute inset-x-[0] top-[-20rpx] bottom-[-20rpx];
}
</style>
