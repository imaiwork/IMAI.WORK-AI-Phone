<template>
    <popup-bottom
        v-model="show"
        mode="bottom"
        height="auto"
        border-radius="48"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        :z-index="5002"
        safe-area-inset-bottom
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="ls-handle"></view>
            <view class="ls-hd">
                <view class="flex-1 min-w-0 flex flex-col gap-[8rpx]">
                    <text class="text-[34rpx] font-semibold text-[#202937]">素材处理模式</text>
                    <text class="text-[24rpx] leading-[36rpx] text-[#647184]">人设设置 · 切换即生效</text>
                </view>
                <view class="ls-close" @click="emit('update:modelValue', false)">
                    <u-icon name="close" color="#5B6B80" size="24"></u-icon>
                </view>
            </view>
        </template>

        <template #content>
            <view class="ls-body">
                <scroll-view scroll-y class="ls-scroll">
                    <view class="p-[36rpx] flex flex-col gap-[24rpx]">
                        <view class="flex flex-col gap-[18rpx]">
                            <view class="flex flex-col gap-[16rpx]">
                                <view
                                    v-for="option in kindOptions"
                                    :key="option.value"
                                    class="ls-kind active:opacity-80"
                                    :class="materialKind === option.value ? 'ls-option--on' : ''"
                                    @click="emit('update:materialKind', option.value)">
                                    <view class="ls-icon">
                                        <u-icon
                                            :name="option.value === MaterialKindEnum.AUTO ? 'star' : 'photo'"
                                            color="#0065FB"
                                            size="36"></u-icon>
                                    </view>
                                    <view class="flex-1 min-w-0 flex flex-col gap-[8rpx]">
                                        <text class="text-[28rpx] font-semibold text-[#202937]">{{
                                            option.label
                                        }}</text>
                                        <text class="text-[24rpx] leading-[38rpx] text-[#647184]">
                                            {{ kindHintMap[option.value] }}
                                        </text>
                                    </view>
                                    <view class="ls-radio" :class="materialKind === option.value ? 'ls-radio--on' : ''">
                                        <u-icon
                                            v-if="materialKind === option.value"
                                            name="checkmark"
                                            color="#FFFFFF"
                                            size="20"></u-icon>
                                    </view>
                                </view>
                            </view>

                            <view class="ls-spec">
                                <u-icon
                                    name="info-circle"
                                    color="#3970AB"
                                    size="30"
                                    class="shrink-0 mt-[4rpx]"></u-icon>
                                <view class="flex-1 min-w-0 flex flex-col gap-[12rpx]">
                                    <text class="text-[26rpx] leading-[40rpx] font-semibold">{{ specText.title }}</text>
                                    <text
                                        v-for="line in specText.lines"
                                        :key="line"
                                        class="text-[24rpx] leading-[38rpx]">
                                        · {{ line }}
                                    </text>
                                    <text class="text-[24rpx] leading-[38rpx]">{{ specText.note }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>

                <view class="ls-foot">
                    <view class="ls-primary active:opacity-85" @click="emit('update:modelValue', false)">
                        <text class="text-[28rpx] font-semibold text-white">完成</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { MaterialKindEnum } from "@/ai_modules/person/enums";
import { materialKindHint, materialKindOptions, materialKindSpec } from "../../hooks/useMaterialsTab";

const props = defineProps<{
    modelValue: boolean;
    materialKind: MaterialKindEnum;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "update:materialKind", value: MaterialKindEnum): void;
}>();

// 静态配置就近取，不从父层灌 prop
const kindOptions = materialKindOptions;
const kindHintMap = materialKindHint;

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const specText = computed(() => materialKindSpec[props.materialKind] ?? materialKindSpec[MaterialKindEnum.AUTO]);
</script>

<style lang="scss" scoped>
.ls-handle {
    @apply w-[64rpx] h-[8rpx] rounded-[8rpx] bg-[#DCE3EC] mx-auto mt-[20rpx] mb-[4rpx];
}

.ls-hd {
    @apply flex items-center gap-[24rpx] px-[36rpx] pt-[18rpx] pb-[26rpx]
        border-[0] border-b-[2rpx] border-solid border-[#EEF1F5];
}

.ls-close {
    @apply relative w-[56rpx] h-[56rpx] rounded-full shrink-0 flex items-center justify-center bg-[#F1F4F8] active:opacity-70;

    &::after {
        content: "";
        @apply absolute inset-[-16rpx];
    }
}

.ls-body {
    @apply w-full flex flex-col;
}

.ls-scroll {
    max-height: 56vh;
}

.ls-kind {
    @apply min-w-0 flex items-center gap-[24rpx] rounded-[26rpx] p-[28rpx]
        border-[2rpx] border-solid border-[#DDE5EF];
}

.ls-option--on {
    @apply border-[#87B7FF] bg-[#F1F6FF];
}

.ls-icon {
    @apply w-[76rpx] h-[76rpx] rounded-[22rpx] bg-[#EDF4FF] flex items-center justify-center shrink-0;
}

.ls-radio {
    @apply w-[40rpx] h-[40rpx] border-[2rpx] border-solid border-[#C7D2E0] rounded-full flex items-center justify-center shrink-0;
}

.ls-radio--on {
    @apply border-primary bg-primary;
}

.ls-spec {
    @apply flex items-start gap-[20rpx] rounded-[24rpx] px-[26rpx] py-[24rpx] bg-[#F1F6FC] text-[#456181];
}

.ls-foot {
    @apply px-[36rpx] pt-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]
        border-[0] border-t-[2rpx] border-solid border-[#E7ECF2];
}

.ls-primary {
    @apply min-h-[94rpx] rounded-[24rpx] bg-primary flex items-center justify-center;
}
</style>
