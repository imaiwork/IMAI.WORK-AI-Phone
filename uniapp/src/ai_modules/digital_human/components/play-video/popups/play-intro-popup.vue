<template>
    <popup-bottom v-model="show" height="auto" :clearable="true">
        <template #header>
            <view class="px-[36rpx] pt-[36rpx] flex items-center gap-[10rpx]">
                <image :src="PLAY_ICONS.lightbulb" class="w-[32rpx] h-[32rpx]" />
                <text class="text-[30rpx] font-bold text-[#0D1117]">{{ playName }} · 玩法原理</text>
            </view>
        </template>
        <template #content>
            <view class="px-[36rpx] pb-[56rpx]">
                <text class="text-[21rpx] text-[#9CA3AF] block mt-[10rpx] mb-[28rpx]">{{
                    intro.note
                }}</text>
                <view class="flex items-start">
                    <template v-for="(s, i) in intro.steps" :key="i">
                        <text v-if="i" class="text-[#B9C6DB] text-[24rpx] mt-[24rpx] mx-[4rpx]"
                            >›</text
                        >
                        <view class="flex-1 text-center">
                            <view
                                class="w-[68rpx] h-[68rpx] rounded-[18rpx] mx-auto mb-[10rpx] flex items-center justify-center"
                                :style="{ background: s.bg }"
                            >
                                <image :src="s.icon" class="w-[36rpx] h-[36rpx]" />
                            </view>
                            <text class="text-[21rpx] font-bold text-[#1F2937] block">{{
                                s.t
                            }}</text>
                            <text
                                class="text-[18rpx] text-[#9CA3AF] block mt-[4rpx] leading-snug whitespace-pre-line"
                                >{{ s.d }}</text
                            >
                        </view>
                    </template>
                </view>
                <view class="rounded-[24rpx] p-[26rpx] mt-[30rpx] bg-[#F5F8FF]">
                    <text class="text-[24rpx] font-bold text-[#1F2937] block mb-[12rpx]"
                        >怎么做</text
                    >
                    <view v-for="(h, i) in intro.how" :key="i" class="flex gap-[10rpx] mb-[8rpx]">
                        <text class="text-[21rpx] text-[#2680F7] font-bold">{{ i + 1 }}.</text>
                        <text class="text-[21rpx] text-[#4B5563] leading-relaxed flex-1">{{
                            h
                        }}</text>
                    </view>
                </view>
                <text class="text-[20rpx] text-[#9CA3AF] leading-relaxed block mt-[24rpx]">{{
                    intro.tip
                }}</text>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { PLAYS, PLAY_INTRO, PLAY_ICONS, type PlayKeyEnum } from '../../../enums/play_video'

const props = defineProps<{ modelValue: boolean; play: PlayKeyEnum }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})
const intro = computed(() => PLAY_INTRO[props.play])
const playName = computed(() => PLAYS.find((p) => p.key === props.play)?.name || '')
</script>
