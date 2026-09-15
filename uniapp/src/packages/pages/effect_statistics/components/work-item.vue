<template>
    <view
        class="bg-white rounded-[32rpx] shadow-[0_4rpx_24rpx_rgba(99,120,200,0.07)] flex gap-[22rpx] p-[24rpx]"
    >
        <view
            class="wd-cover w-[124rpx] h-[164rpx] rounded-[20rpx] shrink-0 relative overflow-hidden"
        >
            <image v-if="item.cover" :src="item.cover" mode="aspectFill" class="w-full h-full" />
            <text
                v-if="badge"
                class="absolute left-[8rpx] bottom-[8rpx] px-[10rpx] py-[2rpx] rounded-[8rpx] bg-[rgba(0,0,0,0.6)] text-white text-[19rpx] font-bold"
            >
                {{ badge }}
            </text>
        </view>

        <view class="flex-1 min-w-0">
            <text class="block text-[26rpx] font-semibold text-main leading-snug line-clamp-2">
                {{ item.title }}
            </text>
            <text v-if="publishText" class="block text-[22rpx] text-[#9AA3B2] mt-[8rpx]">
                {{ publishText }} 发布
            </text>

            <view
                class="grid grid-cols-5 gap-x-[4rpx] gap-y-[4rpx] mt-[18rpx] pt-[18rpx] border-[0] border-t-[2rpx] border-solid border-[#F2F4F8]"
            >
                <text
                    v-for="cell in metrics"
                    :key="cell.label"
                    class="text-[21rpx] text-[#9AA3B2] text-center"
                >
                    {{ cell.label }}
                </text>
                <text
                    v-for="cell in metrics"
                    :key="`${cell.label}-value`"
                    class="text-[28rpx] text-center"
                    :class="
                        cell.value === null ? 'text-[#C9D0DA] font-semibold' : 'text-main font-bold'
                    "
                >
                    {{ cell.value === null ? '—' : formatCount(cell.value) }}
                </text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatCount, formatDuration, formatPublishTime } from '../utils'
import { PlatformEnum, type PlatformConfig, type WorkItem } from '../enums'

const NOTE_TYPE_VIDEO = 'video'

const props = defineProps<{
    item: WorkItem
    platform: PlatformConfig
}>()

const publishText = computed(() => formatPublishTime(props.item.publishedAt))

// 有时长按时长显示；小红书图文没有时长，用类型兜住封面角标
const badge = computed(() => {
    if (Number(props.item.durationSec) > 0) return formatDuration(props.item.durationSec)
    if (props.item.noteType === NOTE_TYPE_VIDEO) return '视频'
    return props.platform.key === PlatformEnum.XHS ? '图文' : ''
})

const metrics = computed(() => [
    { label: props.platform.viewLabel, value: props.item.play },
    { label: '点赞', value: props.item.digg },
    { label: '评论', value: props.item.comment },
    { label: '分享', value: props.item.share },
    { label: '收藏', value: props.item.collect }
])
</script>

<style lang="scss" scoped>
.wd-cover {
    background: linear-gradient(160deg, #3a4558, #1b2330);
}
</style>
