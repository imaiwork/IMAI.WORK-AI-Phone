<template>
    <popup-bottom
        v-model="show"
        height="88%"
        border-radius="48"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        :z-index="5001">
        <template #header>
            <view class="px-[32rpx] pt-3 pb-[24rpx] border-[0] border-b border-solid border-[#F3F4F6]">
                <view class="w-[80rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-3"></view>
                <view class="flex items-center">
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center shrink-0"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="arrow-down" color="#6B7280" size="28"></u-icon>
                    </view>
                    <text class="flex-1 text-center text-sm font-semibold text-[#1F2937]">视频详情</text>
                    <view class="w-[64rpx] h-[64rpx] shrink-0"></view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view>
                            <view
                                class="relative w-full bg-[#111827] overflow-hidden"
                                style="aspect-ratio: 16 / 9"
                                @click="handlePlay">
                                <image v-if="coverUrl" :src="coverUrl" class="w-full h-full" mode="aspectFill"></image>
                                <view v-else class="w-full h-full flex items-center justify-center bg-[#EEF3FF]">
                                    <u-icon name="video-camera" color="#0065FB" size="48"></u-icon>
                                </view>
                                <view
                                    v-if="canPlay"
                                    class="absolute inset-0 flex items-center justify-center"
                                    style="background: rgba(0, 0, 0, 0.2)">
                                    <view
                                        class="w-[112rpx] h-[112rpx] rounded-full flex items-center justify-center"
                                        style="
                                            background: rgba(255, 255, 255, 0.2);
                                            border: 4rpx solid rgba(255, 255, 255, 0.5);
                                        ">
                                        <u-icon name="play-right-fill" color="#FFFFFF" size="40"></u-icon>
                                    </view>
                                </view>
                                <text
                                    v-if="durationText"
                                    class="absolute right-[24rpx] bottom-[20rpx] text-[20rpx] font-bold text-white px-[12rpx] py-[4rpx] rounded-[8rpx]"
                                    style="background: rgba(0, 0, 0, 0.6)">
                                    {{ durationText }}
                                </text>
                            </view>

                            <view class="px-[40rpx] pt-[32rpx] pb-[80rpx]">
                                <text class="text-[34rpx] font-bold text-[#111827] leading-snug">
                                    {{ titleText }}
                                </text>
                                <view class="flex items-center flex-wrap gap-[12rpx] mt-[16rpx]">
                                    <text
                                        v-for="tag in tags"
                                        :key="tag.label"
                                        class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-full"
                                        :style="`background:${tag.bg};color:${tag.color}`">
                                        {{ tag.label }}
                                    </text>
                                    <text v-if="timeText" class="text-[22rpx] text-[#9CA3AF]"> · {{ timeText }} </text>
                                </view>

                                <view v-if="copyText" class="mt-[32rpx] bg-[#F9FAFB] rounded-[28rpx] p-[28rpx]">
                                    <text class="block text-[20rpx] font-semibold text-[#9CA3AF] mb-[12rpx]">
                                        文案
                                    </text>
                                    <text class="text-sm text-[#374151] leading-relaxed whitespace-pre-wrap">
                                        {{ copyText }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { formatAudioTime } from "@/utils/util";

interface TagConfig {
    label: string;
    bg: string;
    color: string;
}

const props = defineProps<{
    modelValue: boolean;
    item: Record<string, any> | null;
    tags?: TagConfig[];
    canPlay?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "play"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const coverUrl = computed(() => String(props.item?.pic || "").trim());
const titleText = computed(() => String(props.item?.title || props.item?.name || "AI自动生成视频").trim());
const copyText = computed(() => String(props.item?.msg || props.item?.title || "").trim());
const timeText = computed(() => String(props.item?.create_time || props.item?.update_time || "").trim());
const tags = computed(() => props.tags || []);
const canPlay = computed(() => Boolean(props.canPlay));
const durationText = computed(() => {
    const duration = Number(props.item?.duration || 0);
    if (!duration || Number.isNaN(duration)) return "";
    return formatAudioTime(duration);
});

const handlePlay = () => {
    if (!canPlay.value) return;
    emit("play");
};
</script>
