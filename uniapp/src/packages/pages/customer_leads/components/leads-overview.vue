<template>
    <view class="leads-overview">
        <view class="overview-primary">
            <view class="flex items-center gap-[12rpx]">
                <u-icon name="grid" size="28" color="#FFFFFF" />
                <text class="text-[28rpx] font-semibold leading-[40rpx]">客资概览</text>
            </view>
            <view class="overview-summary">
                <view v-if="loading" class="skeleton-block h-[28rpx] w-[260rpx]"></view>
                <text v-else class="min-w-0 break-all">{{ statistics.summaryText }}</text>
            </view>
            <view class="main-metrics">
                <view
                    v-for="item in mainMetrics"
                    :key="item.label"
                    class="main-metric"
                    :class="{ 'main-metric-highlight': item.highlight }"
                >
                    <text class="metric-label">{{ item.label }}</text>
                    <view v-if="loading" class="metric-value flex items-center">
                        <view class="skeleton-block h-[62rpx] w-[120rpx]"></view>
                    </view>
                    <text
                        v-else
                        class="metric-value"
                        :class="{
                            'metric-value-compact': String(item.displayValue).length > 6
                        }"
                    >
                        {{ item.displayValue }}
                    </text>
                </view>
            </view>
        </view>
        <view class="secondary-metrics" :class="{ 'secondary-metrics-stacked': secondaryStacked }">
            <view v-for="item in secondaryMetrics" :key="item.label" class="secondary-metric">
                <view class="metric-label flex items-center gap-[10rpx]">
                    <u-icon :name="item.icon" size="28" color="var(--color-primary)" />
                    <text>{{ item.label }}</text>
                </view>
                <view v-if="loading" class="skeleton-block w-[48rpx] h-[44rpx]"></view>
                <text v-else class="secondary-value">{{ item.displayValue }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatNumberToWanOrYi } from '@/utils/util'
import type { StatisticsData } from './types'

const props = defineProps<{
    statistics: StatisticsData
    loading: boolean
}>()

const mainMetrics = computed(() => [
    {
        label: '总触达客户',
        displayValue: formatNumberToWanOrYi(props.statistics.totalTouchCustomerCount),
        highlight: false
    },
    {
        label: '线索数',
        displayValue: formatNumberToWanOrYi(props.statistics.clueCount),
        highlight: true
    }
])

const secondaryMetrics = computed(() => [
    {
        label: '添加好友',
        icon: 'man-add',
        displayValue: formatNumberToWanOrYi(props.statistics.addFriendCount)
    },
    {
        label: '拉群数',
        icon: 'account',
        displayValue: formatNumberToWanOrYi(props.statistics.createGroupCount)
    }
])

const secondaryStacked = computed(() =>
    secondaryMetrics.value.some((item) => String(item.displayValue).length > 5)
)
</script>

<style lang="scss" scoped>
.leads-overview {
    @apply mx-[28rpx] mt-[20rpx] overflow-hidden rounded-[30rpx] bg-white text-[#1E293B];
}

.overview-primary {
    @apply bg-primary px-[32rpx] pb-[28rpx] pt-[28rpx] text-white;
}

.overview-summary {
    @apply mt-[8rpx] flex min-h-[32rpx] items-center text-[24rpx] leading-[32rpx];
}

.main-metrics {
    @apply mt-[28rpx] grid grid-cols-2;
}

.main-metric {
    @apply min-w-0 pr-[24rpx];
}

.main-metric-highlight {
    @apply border-[0] border-l-[1rpx] border-solid border-primary-light-4 pl-[32rpx] pr-0;
}

.metric-label {
    @apply text-[24rpx] leading-[34rpx];
}

.metric-value {
    @apply mt-[8rpx] block min-h-[80rpx] break-all text-[68rpx] font-semibold leading-[80rpx];
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.03em;
}

.metric-value-compact {
    @apply text-[48rpx];
}

.secondary-metrics {
    @apply grid grid-cols-2 gap-x-[32rpx] px-[32rpx] py-[24rpx];
}

.secondary-metric {
    @apply flex min-w-0 flex-wrap items-center justify-between gap-[8rpx] text-[#52647D];
}

.secondary-metrics-stacked .secondary-metric {
    @apply flex-col items-start;
}

.secondary-value {
    @apply min-w-0 break-all text-[36rpx] font-semibold leading-[44rpx] text-[#1E293B];
    font-variant-numeric: tabular-nums;
}

.skeleton-block {
    @apply max-w-full rounded-[8rpx] bg-[#EDF1F6];
}

.overview-primary .skeleton-block {
    @apply bg-white opacity-30;
}
</style>
