<template>
    <popup-bottom
        v-model="show"
        custom-class="bg-white"
        :is-disabled-touch="true"
        :clearable="false"
        :mask-close-able="true"
        height="70%">
        <template #header>
            <view class="px-[40rpx] pt-3 pb-[24rpx] border-b border-solid border-[#F3F4F6]">
                <view class="w-[66rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-3"></view>
                <view class="flex items-center justify-between">
                    <view>
                        <view class="text-[32rpx] font-bold text-[#1F2937]">选择模型</view>
                        <view class="text-xs text-[#9CA3AF] mt-[4rpx]">不同模型的画面风格与出图效果各异</view>
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
                        <view class="px-[32rpx] py-[24rpx] flex flex-col gap-y-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]">
                            <view
                                v-for="m in models"
                                :key="m.id"
                                class="model-opt"
                                :class="{ 'model-opt--on': String(m.id) === String(selectedId) }"
                                hover-class="opacity-70"
                                :hover-stay-time="80"
                                @click="onSelect(m)">
                                <image
                                    v-if="m.logo"
                                    :src="m.logo"
                                    class="w-[64rpx] h-[64rpx] rounded-full flex-shrink-0"
                                    mode="aspectFill" />
                                <view
                                    v-else
                                    class="w-[64rpx] h-[64rpx] rounded-full bg-[#111827] flex items-center justify-center flex-shrink-0">
                                    <text class="text-white text-[22rpx] font-bold">
                                        {{ (m.name || "M").charAt(0) }}
                                    </text>
                                </view>
                                <view class="flex-1 min-w-0">
                                    <view class="text-[28rpx] font-bold text-[#1F2937] line-clamp-1">
                                        {{ m.name || m.alias || "未命名" }}
                                    </view>
                                    <view class="text-[22rpx] text-[#9CA3AF] mt-[4rpx] line-clamp-1">
                                        {{ modelPriceText(m) }}
                                    </view>
                                </view>
                                <u-icon
                                    v-if="String(m.id) === String(selectedId)"
                                    name="checkmark"
                                    color="#2563EB"
                                    :size="28"></u-icon>
                            </view>
                            <view
                                v-if="!models.length"
                                class="text-center text-[#9CA3AF] text-[26rpx] py-[80rpx]">
                                暂无可用图像模型
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
    models: any[];
    selectedId?: string | number;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "select", id: string): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const onSelect = (m: any) => {
    emit("select", String(m.id));
    emit("update:modelValue", false);
};

/** 单价 + 单位：如 30算力/张（勿用 /\.?0+$/，会把 30 误裁成 3） */
const modelPriceText = (m: any) => {
    const unit = String(m?.price_unit_label || m?.unit || "").trim();
    const price = Number(m?.unit_price);
    if (Number.isFinite(price) && price > 0 && unit) {
        // Number→String：去掉小数末尾 0（2.0→2），保留整数尾 0（30→30）
        const formatted = String(Number(price));
        return `${formatted}${unit}`;
    }
    return unit || m?.remarks || m?.desc || "图像生成模型";
};
</script>

<style lang="scss" scoped>
.model-opt {
    @apply flex items-center gap-x-[20rpx] px-[24rpx] py-[22rpx] rounded-[24rpx] bg-[#F7F9FC] border border-solid border-[transparent];
}
.model-opt--on {
    @apply bg-[#EFF6FF] border-primary;
}
</style>
