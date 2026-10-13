<template>
    <popup-bottom
        v-model="show"
        custom-class="bg-white"
        :is-disabled-touch="true"
        :clearable="false"
        :mask-close-able="true"
        height="68%">
        <template #header>
            <view class="px-[40rpx] pt-3 pb-[24rpx] border-b border-solid border-[#F3F4F6]">
                <view class="w-[66rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-3"></view>
                <view class="flex items-center justify-between">
                    <view class="text-[32rpx] font-bold text-[#1F2937]">视频规格</view>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                        hover-class="opacity-70"
                        :hover-stay-time="80"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#666666" :size="20"></u-icon>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[32rpx] py-[24rpx]">
                            <view class="text-[26rpx] font-semibold text-[#6B7280] mb-[20rpx]">选择比例</view>
                            <scroll-view scroll-x class="w-full">
                                <view class="flex items-end gap-x-[28rpx] pb-[8rpx]">
                                    <view
                                        v-for="r in VIDEO_RATIO_OPTIONS"
                                        :key="r.key"
                                        class="flex flex-col items-center gap-y-[12rpx] flex-shrink-0"
                                        @click="draftRatio = r.key">
                                        <view
                                            class="rounded-[8rpx] border-[2rpx] border-solid"
                                            :class="
                                                draftRatio === r.key
                                                    ? 'border-primary bg-[#EFF6FF]'
                                                    : 'border-[#D1D5DB] bg-white'
                                            "
                                            :style="{ width: r.w * 2 + 'rpx', height: r.h * 2 + 'rpx' }"></view>
                                        <text
                                            class="text-[22rpx]"
                                            :class="
                                                draftRatio === r.key
                                                    ? 'text-primary font-semibold'
                                                    : 'text-[#9CA3AF]'
                                            ">
                                            {{ r.label }}
                                        </text>
                                    </view>
                                </view>
                            </scroll-view>

                            <view class="text-[26rpx] font-semibold text-[#6B7280] mt-[36rpx] mb-[20rpx]">
                                选择分辨率
                            </view>
                            <view class="flex gap-x-[20rpx]">
                                <view
                                    v-for="opt in VIDEO_RESOLUTION_OPTIONS"
                                    :key="opt.key"
                                    class="res-btn"
                                    :class="{ 'res-btn--on': draftResolution === opt.key }"
                                    hover-class="opacity-70"
                                    :hover-stay-time="80"
                                    @click="draftResolution = opt.key">
                                    {{ opt.label }}
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="px-[32rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]">
                    <view
                        class="h-[88rpx] rounded-full bg-primary text-white text-[30rpx] font-semibold flex items-center justify-center"
                        hover-class="opacity-80"
                        :hover-stay-time="80"
                        @click="onConfirm">
                        完成
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { VIDEO_RATIO_OPTIONS, VIDEO_RESOLUTION_OPTIONS } from "../../enums/workbench";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        ratio?: string;
        resolution?: string;
    }>(),
    {
        ratio: "16:9",
        resolution: "720p",
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "confirm", payload: { ratio: string; resolution: string }): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const draftRatio = ref(props.ratio);
const draftResolution = ref(props.resolution);

watch(
    () => props.modelValue,
    (v) => {
        if (!v) return;
        draftRatio.value = props.ratio;
        draftResolution.value = props.resolution;
    },
);

const onConfirm = () => {
    emit("confirm", {
        ratio: draftRatio.value,
        resolution: draftResolution.value,
    });
    emit("update:modelValue", false);
};
</script>

<style lang="scss" scoped>
.res-btn {
    @apply flex-1 py-[26rpx] rounded-[28rpx] text-center text-[28rpx] font-bold text-[#6B7280] bg-[#F3F4F6] border border-solid border-[transparent];
}
.res-btn--on {
    @apply bg-white text-[#1F2937] border-primary;
    box-shadow: 0 1px 6px rgba(37, 99, 235, 0.12);
}
</style>
