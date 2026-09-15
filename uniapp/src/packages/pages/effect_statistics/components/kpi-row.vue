<template>
    <view class="flex items-stretch">
        <view
            v-for="(item, index) in cells"
            :key="item.label"
            class="flex-1 flex items-center min-w-0"
        >
            <view class="flex-1 min-w-0">
                <template v-if="loading">
                    <skeleton-block class="mx-auto" w="80rpx" h="34rpx" />
                    <skeleton-block class="mx-auto mt-[8rpx]" w="104rpx" h="68rpx" />
                </template>
                <template v-else>
                    <!-- 标签和数值各自锁定行高，数值字号不同也不会把标签顶歪 -->
                    <view class="h-[34rpx] flex items-center justify-center">
                        <text class="text-[23rpx] text-[#8A93A3] whitespace-nowrap">
                            {{ item.label }}
                        </text>
                    </view>
                    <view class="h-[68rpx] mt-[8rpx] flex items-center justify-center">
                        <text
                            class="font-bold whitespace-nowrap leading-none"
                            :class="
                                item.value === null
                                    ? 'text-[28rpx] text-[#C3CAD6]'
                                    : 'text-[52rpx] text-main'
                            "
                        >
                            {{ item.value === null ? item.naText : item.value }}
                        </text>
                    </view>
                </template>
            </view>
            <view
                v-if="index < cells.length - 1"
                class="w-[2rpx] h-[72rpx] bg-[#EEF1F6] shrink-0"
            ></view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatCount } from '../utils'
import { NA_PLAY_TEXT, NA_REACH_TEXT, REACH_LABEL } from '../enums'
import SkeletonBlock from './skeleton-block.vue'
import type { PlatformConfig, WindowStat } from '../enums'

const props = defineProps<{
    stat: WindowStat | null
    platform: PlatformConfig
    playAvailable: boolean
    loading: boolean
}>()

// value 为 null 表示这个指标当前拿不到，展示 naText 而不是 0
const cells = computed(() => {
    const stat = props.stat
    const canPlay = props.playAvailable && stat?.play !== null && stat?.play !== undefined
    return [
        {
            label: '已发布',
            value: stat ? String(stat.published ?? 0) : '—',
            naText: ''
        },
        {
            label: props.platform.viewLabel,
            value: stat && canPlay ? formatCount(stat.play) : null,
            naText: NA_PLAY_TEXT
        },
        {
            label: '互动',
            value: stat ? formatCount(stat.interaction) : '—',
            naText: ''
        },
        {
            label: REACH_LABEL,
            value: null,
            naText: NA_REACH_TEXT
        }
    ]
})
</script>
