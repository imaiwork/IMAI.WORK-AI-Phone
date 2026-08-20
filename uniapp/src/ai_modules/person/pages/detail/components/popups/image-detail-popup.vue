<template>
    <popup-bottom
        v-model="show"
        height="85%"
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
                    <text class="flex-1 text-center text-sm font-semibold text-[#1F2937]">图文详情</text>
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
                                class="relative w-full overflow-hidden bg-[#F3F4F6]"
                                style="aspect-ratio: 4 / 3"
                                @click="handlePreviewImage">
                                <image
                                    v-if="currentImage"
                                    :src="currentImage"
                                    class="w-full h-full"
                                    mode="aspectFill"></image>
                                <view v-else class="w-full h-full flex items-center justify-center bg-[#EEF3FF]">
                                    <u-icon name="photo" color="#0065FB" size="48"></u-icon>
                                </view>
                                <view
                                    v-if="images.length > 1 && activeIndex > 0"
                                    class="absolute left-[16rpx] top-1/2 -translate-y-1/2 w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center"
                                    style="background: rgba(0, 0, 0, 0.3)"
                                    @click.stop="handlePrev">
                                    <u-icon name="arrow-left" color="#FFFFFF" size="28"></u-icon>
                                </view>
                                <view
                                    v-if="images.length > 1 && activeIndex < images.length - 1"
                                    class="absolute right-[16rpx] top-1/2 -translate-y-1/2 w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center"
                                    style="background: rgba(0, 0, 0, 0.3)"
                                    @click.stop="handleNext">
                                    <u-icon name="arrow-right" color="#FFFFFF" size="28"></u-icon>
                                </view>
                            </view>

                            <view
                                v-if="images.length > 1"
                                class="flex justify-center items-center gap-[12rpx] pt-[24rpx] pb-[8rpx]">
                                <view
                                    v-for="(_, index) in images"
                                    :key="index"
                                    class="rounded-full"
                                    :style="dotStyle(index)"></view>
                            </view>

                            <view class="px-[40rpx] pt-[16rpx] pb-[80rpx]">
                                <text class="text-[34rpx] font-bold text-[#111827] leading-snug">
                                    {{ titleText }}
                                </text>
                                <text
                                    v-if="copyText"
                                    class="block text-sm text-[#4B5563] mt-[24rpx] leading-relaxed whitespace-pre-wrap">
                                    {{ copyText }}
                                </text>
                                <view v-if="tags.length" class="flex flex-wrap gap-[16rpx] mt-[32rpx]">
                                    <text v-for="tag in tags" :key="tag" class="text-sm font-semibold text-[#FF2D55]">
                                        {{ tag }}
                                    </text>
                                </view>
                                <view
                                    class="flex items-center gap-[16rpx] mt-[32rpx] pt-[24rpx] border-[0] border-t border-solid border-[#F3F4F6]">
                                    <text
                                        v-if="platformBadge"
                                        class="text-[22rpx] font-bold text-white px-[16rpx] py-[4rpx] rounded-[8rpx]"
                                        :style="`background:${platformBadge.bg}`">
                                        {{ platformBadge.label }}
                                    </text>
                                    <text v-if="timeText" class="text-[22rpx] text-[#9CA3AF]">
                                        生成于 {{ timeText }}
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
import { computed, ref, watch } from "vue";

interface PlatformBadge {
    label: string;
    bg: string;
}

const props = defineProps<{
    modelValue: boolean;
    item: Record<string, any> | null;
    images?: string[];
    tags?: string[];
    title?: string;
    platformBadge?: PlatformBadge | null;
    time?: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const activeIndex = ref(0);
const images = computed(() => (Array.isArray(props.images) ? props.images.filter(Boolean) : []));
const currentImage = computed(() => images.value[activeIndex.value] || "");
const tags = computed(() => props.tags || []);
const titleText = computed(() => String(props.title || props.item?.title || "AI自动生成图片").trim());
const copyText = computed(() =>
    String(props.item?.rewritten_text || props.item?.content || props.item?.copywriting?.rewritten_text || "").trim(),
);
const platformBadge = computed(() => props.platformBadge || null);
const timeText = computed(() => String(props.time || props.item?.create_time || props.item?.update_time || "").trim());

const dotStyle = (index: number) => {
    const active = index === activeIndex.value;
    return {
        width: active ? "36rpx" : "12rpx",
        height: "12rpx",
        background: active ? "#2F73F6" : "#D1D5DB",
    };
};

const handlePrev = () => {
    if (activeIndex.value <= 0) return;
    activeIndex.value -= 1;
};

const handleNext = () => {
    if (activeIndex.value >= images.value.length - 1) return;
    activeIndex.value += 1;
};

const handlePreviewImage = () => {
    if (!images.value.length) return;
    uni.previewImage({
        urls: images.value,
        current: currentImage.value || images.value[0],
    });
};

watch(
    () => props.modelValue,
    (visible) => {
        if (!visible) {
            activeIndex.value = 0;
            return;
        }
        activeIndex.value = 0;
    },
);
</script>
