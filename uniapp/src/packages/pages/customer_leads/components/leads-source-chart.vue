<template>
    <view class="source-chart">
        <view class="flex items-center justify-between gap-[16rpx]">
            <text class="text-[30rpx] font-semibold text-[#1E293B]">线索来源</text>
            <text class="text-[22rpx] text-[#617087]">数量对比</text>
        </view>
        <view v-if="loading" class="chart-loading">
            <view v-for="item in skeletonRows" :key="item" class="source-row">
                <view class="skeleton-block h-[24rpx] w-[110rpx]"></view>
                <view class="skeleton-block h-[16rpx] w-full"></view>
                <view class="skeleton-block h-[28rpx] w-[48rpx] justify-self-end"></view>
            </view>
        </view>
        <view v-else-if="!groups.length" class="source-empty">
            <text class="block text-[26rpx] font-medium text-[#1E293B]">暂无来源数据</text>
            <text class="mt-[10rpx] block text-[24rpx] leading-[36rpx] text-[#617087]">
                有来源统计后将在这里展示
            </text>
        </view>
        <view v-else class="mt-[36rpx] min-h-[150rpx]">
            <view
                v-for="group in groups"
                :key="group.unit"
                class="source-group"
                :class="{ 'source-group-stacked': group.stacked }"
            >
                <text v-if="groups.length > 1" class="group-label">
                    {{ group.unit ? `单位：${group.unit}` : '未标注单位' }}
                </text>
                <view v-for="item in group.items" :key="item.key" class="source-row">
                    <text class="source-label">{{ item.label }}</text>
                    <view class="source-track">
                        <view class="source-bar" :style="item.barStyle"></view>
                    </view>
                    <view class="source-value">
                        <text class="source-number">{{ item.value }}</text>
                        <text v-if="item.unit" class="source-unit">{{ item.unit }}</text>
                    </view>
                </view>
                <view v-if="group.max > 0" class="chart-scale">
                    <text>0</text>
                    <text v-if="group.midpoint !== null">{{ group.midpoint }}</text>
                    <text>{{ group.max }}</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import type { SourceStat } from './types'

const props = defineProps<{
    sources: SourceStat[]
    loading: boolean
}>()

const SKELETON_ROW_COUNT = 2
const BAR_PERCENT_MAX = 100
const SCALE_DIVISOR = 2
const INLINE_VALUE_MAX_CHARACTERS = 3
const INLINE_UNIT_MAX_CHARACTERS = 1
const INLINE_LABEL_MAX_CHARACTERS = 8
const skeletonRows = computed(() => Math.max(props.sources.length, SKELETON_ROW_COUNT))

const groups = computed(() => {
    const grouped = new Map<string, SourceStat[]>()
    props.sources.forEach((source) => {
        const items = grouped.get(source.unit) || []
        items.push(source)
        grouped.set(source.unit, items)
    })

    return Array.from(grouped, ([unit, sources]) => {
        // 不同单位独立比较，只计算可视宽度，不改变来源数值或做业务汇总。
        const max = Math.max(0, ...sources.map((source) => source.value))
        return {
            unit,
            max,
            stacked: sources.some(
                (source) =>
                    String(source.value).length > INLINE_VALUE_MAX_CHARACTERS ||
                    source.unit.length > INLINE_UNIT_MAX_CHARACTERS ||
                    source.label.length > INLINE_LABEL_MAX_CHARACTERS
            ),
            midpoint: max > 0 && max % SCALE_DIVISOR === 0 ? max / SCALE_DIVISOR : null,
            items: sources.map((source) => ({
                ...source,
                barStyle: {
                    width: `${max > 0 ? Math.max(0, source.value / max) * BAR_PERCENT_MAX : 0}%`
                }
            }))
        }
    })
})
</script>

<style lang="scss" scoped>
.source-chart {
    @apply rounded-[30rpx] bg-white p-[32rpx];
}

.chart-loading {
    @apply mt-[36rpx] min-h-[150rpx] pb-[46rpx];
}

.source-empty {
    @apply mt-[36rpx] flex min-h-[150rpx] flex-col items-center justify-center text-center;
}

.source-group + .source-group {
    @apply mt-[32rpx] border-[0] border-t-[1rpx] border-solid border-[#E8EDF4] pt-[26rpx];
}

.group-label {
    @apply mb-[20rpx] block text-[22rpx] text-[#617087];
}

.source-row {
    @apply grid min-h-[36rpx] items-center gap-x-[20rpx];
    grid-template-columns: 128rpx minmax(0, 1fr) 72rpx;
}

.source-row + .source-row {
    @apply mt-[32rpx];
}

.source-label {
    @apply min-w-0 break-all text-[24rpx] leading-[34rpx] text-[#4E5F77];
}

.source-track {
    @apply h-[16rpx] min-w-0 overflow-hidden rounded-[6rpx] bg-[#F0F4FA];
}

.source-bar {
    @apply h-full rounded-[6rpx] bg-primary;
}

.source-row:nth-child(even) .source-bar {
    @apply opacity-60;
}

.source-value {
    @apply min-w-0 break-all text-right text-[28rpx] font-semibold leading-[34rpx] text-[#1E293B];
    font-variant-numeric: tabular-nums;
}

.source-unit {
    @apply ml-[4rpx] text-[22rpx] font-normal text-[#617087];
}

.source-group-stacked {
    .source-row {
        @apply items-start gap-y-[16rpx];
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .source-track {
        grid-column: 1 / -1;
        grid-row: 2;
    }

    .source-value {
        @apply flex max-w-[380rpx] flex-wrap items-baseline justify-end gap-x-[4rpx];
        grid-column: 2;
        grid-row: 1;
    }

    .source-number {
        @apply whitespace-nowrap;
    }

    .source-unit {
        @apply ml-0 min-w-0 max-w-full break-all;
    }

    .chart-scale {
        @apply mx-0;
    }
}

.chart-scale {
    @apply ml-[148rpx] mr-[92rpx] mt-[18rpx] flex items-start justify-between gap-[8rpx] text-[20rpx] leading-[28rpx] text-[#617087];
    font-variant-numeric: tabular-nums;

    text {
        @apply min-w-0 break-all;
    }
}

.skeleton-block {
    @apply max-w-full rounded-[6rpx] bg-[#EDF1F6];
}
</style>
