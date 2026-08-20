<template>
    <popup-bottom
        v-model="visible"
        height="80%"
        border-radius="44"
        custom-class="bg-white"
        :z-index="5001"
        :clearable="false"
        :mask-close-able="true"
        :custom-style="{ overflow: 'hidden' }"
        @close="emit('close')">
        <template #header>
            <view class="emp-sheet-handle"></view>
            <view class="emp-sheet-hd">
                <view class="emp-sheet-ic" :style="{ background: iconBg }">
                    <image :src="icon" mode="aspectFit" class="w-[36rpx] h-[36rpx]" />
                </view>
                <view class="emp-sheet-info">
                    <text class="block emp-sheet-title">{{ title }}</text>
                    <text class="block emp-sheet-sub">{{ subtitle }}</text>
                </view>
                <button class="plain-btn emp-sheet-close" @click.stop="handleClose">
                    <u-icon name="close" color="#86909C" size="22"></u-icon>
                </button>
            </view>
        </template>
        <template #content>
            <view class="h-full flex flex-col">
                <scroll-view scroll-y class="grow min-h-0">
                    <view class="emp-row-body-inner">
                        <slot />
                    </view>
                </scroll-view>
                <view v-if="!footerHidden" class="emp-sheet-foot">
                    <button class="plain-btn emp-sheet-done" @click.stop="emit('save')">完成配置</button>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        modelValue?: boolean;
        title: string;
        subtitle: string;
        icon: string;
        iconBg: string;
        footerHidden?: boolean;
    }>(),
    {
        modelValue: false,
        footerHidden: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "close"): void;
    (e: "save"): void;
}>();

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const handleClose = () => {
    visible.value = false;
    emit("close");
};

defineExpose({
    open: () => {
        visible.value = true;
    },
    close: () => {
        visible.value = false;
    },
});
</script>

<style scoped lang="scss">
.plain-btn {
    @apply m-0 p-0 leading-none border-none bg-[transparent];

    &::after {
        border: none;
    }
}

.emp-sheet-handle {
    @apply w-[76rpx] h-[8rpx] rounded-full bg-[#e5e9f0] mt-[18rpx] mx-auto shrink-0;
}

.emp-sheet-hd {
    @apply flex items-center gap-[24rpx] py-[22rpx] px-[36rpx] border-0 border-b-[2rpx] border-solid border-[#f2f5fa];
}

.emp-sheet-ic {
    @apply w-[80rpx] h-[80rpx] rounded-[24rpx] flex items-center justify-center shrink-0;
}

.emp-sheet-info {
    @apply flex-1 min-w-0;
}

.emp-sheet-title {
    @apply text-[30rpx] font-bold text-[#1d2129] line-clamp-1;
}

.emp-sheet-sub {
    @apply text-[22rpx] text-[#9ca3af] mt-[6rpx] line-clamp-1;
}

.emp-sheet-close {
    @apply w-[60rpx] h-[60rpx] rounded-full bg-[#f3f5f9] flex items-center justify-center shrink-0;
}

.emp-row-body-inner {
    @apply pt-[32rpx] px-[32rpx] pb-[40rpx];
}

.emp-sheet-foot {
    @apply shrink-0 py-[24rpx] px-[32rpx] border-0 border-t-[2rpx] border-solid border-[#f2f5fa] bg-white;
    padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
}

.emp-sheet-done {
    @apply w-full h-[96rpx] rounded-[28rpx] bg-primary text-white text-[30rpx] font-bold flex items-center justify-center;
    box-shadow: 0 8rpx 28rpx rgba(47, 115, 246, 0.22);
}
</style>
