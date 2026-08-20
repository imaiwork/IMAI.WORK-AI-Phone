<template>
    <popup-bottom
        v-model="show"
        custom-class="bg-white"
        :is-disabled-touch="true"
        :clearable="false"
        :mask-close-able="true"
        height="62%">
        <template #header>
            <view class="px-[40rpx] pt-3 pb-[24rpx] border-b border-solid border-[#F3F4F6]">
                <view class="w-[66rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-3"></view>
                <view class="flex items-center justify-between">
                    <view class="text-[32rpx] font-bold text-[#1F2937]">选择页数</view>
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
                            <view class="text-[26rpx] font-semibold text-[#6B7280] mb-[20rpx]">常用区间</view>
                            <view class="flex flex-wrap gap-[16rpx]">
                                <view
                                    v-for="p in PPT_PAGE_RANGE_OPTIONS"
                                    :key="p"
                                    class="range-chip"
                                    :class="{ 'range-chip--on': !useCustom && draftRange === p }"
                                    hover-class="opacity-70"
                                    :hover-stay-time="80"
                                    @click="pickPreset(p)">
                                    {{ p }}
                                </view>
                            </view>

                            <view class="text-[26rpx] font-semibold text-[#6B7280] mt-[36rpx] mb-[20rpx]">
                                自定义
                            </view>
                            <view
                                class="custom-box"
                                :class="{ 'custom-box--on': useCustom }">
                                <input
                                    class="custom-input"
                                    type="number"
                                    :value="customInput"
                                    :maxlength="2"
                                    placeholder="1-99"
                                    placeholder-style="color:#9CA3AF;"
                                    @input="onCustomInput"
                                    @focus="useCustom = true" />
                                <text class="custom-suffix">页</text>
                            </view>
                            <text v-if="useCustom && customInput && !isCustomValid" class="hint-err">
                                请输入 1-99 的整数
                            </text>
                        </view>
                    </scroll-view>
                </view>
                <view class="px-[32rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]">
                    <view
                        class="h-[88rpx] rounded-full bg-primary text-white text-[30rpx] font-semibold flex items-center justify-center"
                        :class="{ 'opacity-50': !canConfirm }"
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
import { PPT_PAGE_RANGE_OPTIONS } from "../../enums/workbench";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        pageRange?: string;
    }>(),
    {
        pageRange: "15-25页",
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "confirm", pageRange: string): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const draftRange = ref(props.pageRange);
const customInput = ref("");
const useCustom = ref(false);

const isCustomValid = computed(() => {
    const n = Number(customInput.value);
    return Number.isInteger(n) && n >= 1 && n <= 99;
});

const canConfirm = computed(() => (useCustom.value ? isCustomValid.value : !!draftRange.value));

const syncFromProp = () => {
    const label = String(props.pageRange || "").trim() || "15-25页";
    const isPreset = (PPT_PAGE_RANGE_OPTIONS as readonly string[]).includes(label);
    if (isPreset) {
        draftRange.value = label;
        useCustom.value = false;
        customInput.value = "";
        return;
    }
    const m = label.match(/^(\d+)\s*页$/);
    if (m) {
        useCustom.value = true;
        customInput.value = m[1];
        draftRange.value = "15-25页";
        return;
    }
    draftRange.value = label;
    useCustom.value = false;
    customInput.value = "";
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) syncFromProp();
    },
);

const pickPreset = (p: string) => {
    draftRange.value = p;
    useCustom.value = false;
    customInput.value = "";
};

const onCustomInput = (e: any) => {
    useCustom.value = true;
    customInput.value = String(e?.detail?.value ?? "").replace(/[^\d]/g, "").slice(0, 2);
};

const onConfirm = () => {
    if (!canConfirm.value) return;
    if (useCustom.value) {
        const n = Number(customInput.value);
        emit("confirm", `${n}页`);
    } else {
        emit("confirm", draftRange.value);
    }
    emit("update:modelValue", false);
};
</script>

<style lang="scss" scoped>
.range-chip {
    @apply h-[72rpx] px-[28rpx] rounded-full bg-[#F3F4F6] text-[#4B5563] text-[26rpx] font-medium flex items-center justify-center;
}
.range-chip--on {
    @apply bg-[#EFF6FF] text-primary;
}
.custom-box {
    @apply h-[88rpx] rounded-[20rpx] bg-[#F9FAFB] border-[2rpx] border-solid border-[#E5E7EB] px-[28rpx] flex items-center gap-x-[12rpx];
}
.custom-box--on {
    @apply border-primary bg-[#EFF6FF];
}
.custom-input {
    @apply flex-1 h-full text-[30rpx] text-[#111827];
}
.custom-suffix {
    @apply text-[28rpx] text-[#6B7280] flex-shrink-0;
}
.hint-err {
    @apply block mt-[12rpx] text-[22rpx] text-[#DC2626];
}
</style>
