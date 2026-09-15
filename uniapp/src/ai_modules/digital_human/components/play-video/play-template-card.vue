<template>
    <view class="bg-white rounded-[24rpx] p-[24rpx] mt-[24rpx] flex items-center gap-[20rpx]">
        <slot name="cover">
            <view
                class="w-[120rpx] h-[160rpx] rounded-[16rpx] overflow-hidden flex-shrink-0 cover-ph"
            >
                <image
                    v-if="template?.cover_url"
                    :src="template.cover_url"
                    class="w-full h-full"
                    mode="aspectFill"
                />
            </view>
        </slot>
        <view class="flex-1 min-w-0">
            <text v-if="label" class="text-[20rpx] text-[#9CA3AF] block">{{ label }}</text>
            <text class="text-[26rpx] font-bold text-[#1D2129] block line-clamp-1">{{
                template?.name || '模板'
            }}</text>
            <text class="text-[20rpx] text-[#9CA3AF] block mt-[6rpx] line-clamp-1">{{
                subtitle
            }}</text>
        </view>
        <view
            class="px-[20rpx] py-[10rpx] rounded-full bg-[#F4F6FA] active:opacity-70 flex-shrink-0"
            @click="emit('change')"
        >
            <text class="text-[21rpx] font-semibold text-[#4B5563]">换一个</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import type { PlayTemplate } from '../../utils/play_video'

defineProps<{ template: PlayTemplate | null; subtitle: string; label?: string }>()
const emit = defineEmits<{ (e: 'change'): void }>()
</script>

<style lang="scss" scoped>
.cover-ph {
    background: linear-gradient(160deg, #2b3a5c, #151c2e);
}
</style>
