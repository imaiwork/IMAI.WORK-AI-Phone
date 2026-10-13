<template>
    <popup-bottom
        v-model="show"
        custom-class="bg-white"
        :is-disabled-touch="true"
        :clearable="false"
        :mask-close-able="true"
        height="60%">
        <template #header>
            <view class="px-[40rpx] pt-3 pb-[24rpx] border-b border-solid border-[#F3F4F6]">
                <view class="w-[66rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-3"></view>
                <view class="flex items-center justify-between">
                    <view>
                        <view class="text-[32rpx] font-bold text-[#1F2937]">选择场景</view>
                        <view class="text-xs text-[#9CA3AF] mt-[4rpx]">选择最贴合的 PPT 应用场景</view>
                    </view>
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
                        <view
                            class="px-[32rpx] py-[24rpx] flex flex-col gap-y-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]">
                            <view
                                v-for="s in PPT_SCENES"
                                :key="s"
                                class="scene-opt"
                                :class="{ 'scene-opt--on': s === scene }"
                                hover-class="opacity-70"
                                :hover-stay-time="80"
                                @click="onSelect(s)">
                                <text
                                    class="text-[28rpx]"
                                    :class="s === scene ? 'text-primary font-bold' : 'text-[#1F2937]'">
                                    {{ s }}
                                </text>
                                <u-icon v-if="s === scene" name="checkmark" color="#2563EB" :size="28"></u-icon>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { PPT_SCENES } from "../../enums/workbench";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        scene?: string;
    }>(),
    {
        scene: "通用",
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "confirm", scene: string): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const onSelect = (v: string) => {
    emit("confirm", v);
    emit("update:modelValue", false);
};
</script>

<style lang="scss" scoped>
.scene-opt {
    @apply flex items-center justify-between h-[88rpx] px-[24rpx] rounded-[20rpx] bg-[#F7F8FA];
}
.scene-opt--on {
    @apply bg-[#EFF6FF];
}
</style>
