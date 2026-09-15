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
            <view class="cs-handle"></view>
            <view class="cs-hd">
                <view class="flex-1 min-w-0 flex flex-col gap-[10rpx]">
                    <text class="text-[34rpx] font-bold text-main">换个文案来源就能用</text>
                    <text class="text-xs leading-[36rpx] text-content">
                        无手机模式没有手机去帮你抓爆款，「找爆款仿写」用不了。选一个替代，我把文案来源和运行模式一起改掉。
                    </text>
                </view>
                <view class="cs-close" @click="emit('update:modelValue', false)">
                    <u-icon name="close" color="#999999" size="24"></u-icon>
                </view>
            </view>
        </template>

        <template #content>
            <view class="cs-body">
                <view
                    v-for="option in options"
                    :key="option.value"
                    class="cs-option"
                    :class="[
                        option.disabled ? 'cs-option--off' : 'active:opacity-80',
                        picked === option.value ? 'cs-option--on' : '',
                    ]"
                    @click="handlePick(option)">
                    <view class="cs-radio" :class="picked === option.value ? 'cs-radio--on' : ''">
                        <u-icon
                            v-if="picked === option.value"
                            name="checkmark"
                            color="#FFFFFF"
                            size="20"></u-icon>
                    </view>
                    <view class="flex-1 min-w-0 flex flex-col gap-[8rpx]">
                        <view class="flex items-center gap-[12rpx]">
                            <text class="text-base font-semibold" :class="option.disabled ? 'text-muted' : 'text-main'">
                                {{ option.title }}
                            </text>
                            <text v-if="option.recommended" class="cs-tip">推荐</text>
                        </view>
                        <text class="text-[22rpx] leading-[34rpx] text-content">{{ option.desc }}</text>
                    </view>
                </view>

                <view class="cs-foot">
                    <view class="cs-cancel active:opacity-70" @click="emit('update:modelValue', false)">
                        <text class="text-lg font-semibold text-content">还用手机模式</text>
                    </view>
                    <view class="cs-primary active:opacity-85" @click="emit('confirm', picked)">
                        <text class="text-lg font-semibold text-white">换成它并切换</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { CopywritingSourceEnum } from "@/ai_modules/person/enums";

const props = defineProps<{
    modelValue: boolean;
    /** 文案库现有条数：为 0 时那条不可选，免得选完过去发现是空的 */
    copywritingCount: number;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm", source: CopywritingSourceEnum): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const picked = ref<CopywritingSourceEnum>(CopywritingSourceEnum.AI);

const options = computed(() => [
    {
        value: CopywritingSourceEnum.AI,
        title: "AI 直接生成",
        desc: "按人设和追踪词现写，不依赖任何素材来源。",
        recommended: true,
        disabled: false,
    },
    {
        value: CopywritingSourceEnum.LIBRARY,
        title: "用文案库",
        desc: props.copywritingCount
            ? `从你已存的 ${props.copywritingCount} 条文案里挑着发。`
            : "文案库现在还是空的，先去攒几条再用这个。",
        recommended: false,
        disabled: props.copywritingCount === 0,
    },
]);

const handlePick = (option: { value: CopywritingSourceEnum; disabled: boolean }): void => {
    if (option.disabled) return;
    picked.value = option.value;
};

// 每次打开都回到推荐项，避免上次的选择残留
watch(
    () => props.modelValue,
    (visible) => {
        if (visible) picked.value = CopywritingSourceEnum.AI;
    },
);
</script>

<style lang="scss" scoped>
.cs-handle {
    @apply w-[72rpx] h-[8rpx] rounded-[4rpx] bg-light mx-auto mt-[20rpx];
}

.cs-hd {
    @apply flex items-start gap-[24rpx] px-[40rpx] pt-[28rpx] pb-[8rpx];
}

.cs-close {
    @apply relative w-[64rpx] h-[64rpx] rounded-full shrink-0 flex items-center justify-center bg-[#F2F3F5];

    &::after {
        content: "";
        @apply absolute inset-[-12rpx];
    }
}

.cs-body {
    @apply w-full flex flex-col gap-[20rpx] px-[40rpx] pt-[16rpx];
}

.cs-option {
    @apply flex gap-[20rpx] rounded-[26rpx] px-[26rpx] py-[24rpx] border-[2rpx] border-solid border-light;
}

.cs-option--on {
    @apply border-primary bg-primary-light-9;
}

.cs-option--off {
    @apply bg-[#F2F3F5];
}

.cs-radio {
    @apply w-[36rpx] h-[36rpx] rounded-full shrink-0 mt-[4rpx] flex items-center justify-center
        border-[3rpx] border-solid border-disabled;
}

.cs-radio--on {
    @apply bg-primary border-primary;
}

.cs-tip {
    @apply shrink-0 text-[20rpx] font-semibold text-primary bg-white rounded-[8rpx] px-[12rpx] py-[2rpx];
}

.cs-foot {
    @apply flex gap-[20rpx] pt-[12rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))];
}

.cs-cancel {
    @apply flex-1 h-[92rpx] rounded-[24rpx] bg-[#F2F3F5] flex items-center justify-center;
}

.cs-primary {
    @apply flex-1 h-[92rpx] rounded-[24rpx] bg-primary flex items-center justify-center;
}
</style>
