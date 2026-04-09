<template>
    <u-popup v-model="visible" mode="center" border-radius="24" :closeable="false" width="680rpx" @close="handleClose">
        <view class="conflict-dialog">
            <view class="conflict-dialog__stripe"></view>

            <view class="px-[40rpx] pb-[40rpx]">
                <view class="conflict-dialog__header">
                    <view class="conflict-dialog__icon-wrap">
                        <u-icon name="warning-fill" size="36" color="#F6882B"></u-icon>
                    </view>
                    <view>
                        <view class="conflict-dialog__title">检测到任务时间冲突</view>
                        <view class="conflict-dialog__subtitle">以下任务与当前设置存在时间冲突</view>
                    </view>
                </view>

                <scroll-view scroll-y class="conflict-dialog__list" :style="{ maxHeight: listMaxHeight }">
                    <view class="flex flex-col gap-y-[16rpx]">
                        <view v-for="(msg, index) in messages" :key="index" class="conflict-dialog__item">
                            <view class="conflict-dialog__item-dot"></view>
                            <text class="conflict-dialog__item-text">{{ msg }}</text>
                        </view>
                    </view>
                </scroll-view>

                <view class="conflict-dialog__tip">
                    <u-icon name="info-circle" size="24" color="#999"></u-icon>
                    <text class="conflict-dialog__tip-text">可忽略冲突继续创建，或返回修改时间配置</text>
                </view>

                <view class="conflict-dialog__actions">
                    <view class="conflict-dialog__btn conflict-dialog__btn--cancel" @click="handleClose">
                        返回修改
                    </view>
                    <view class="conflict-dialog__btn conflict-dialog__btn--confirm" @click="handleConfirm">
                        继续创建
                    </view>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
interface Props {
    modelValue: boolean;
    messages: string[];
}

interface Emits {
    (e: "update:modelValue", val: boolean): void;
    (e: "close"): void;
    (e: "confirm"): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    messages: () => [],
});

const emit = defineEmits<Emits>();

// 根据条数动态限制列表高度，超过 4 条出现滚动
const listMaxHeight = computed(() => {
    return props.messages.length > 4 ? "360rpx" : "auto";
});

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

const handleClose = () => {
    emit("update:modelValue", false);
    emit("close");
};

const handleConfirm = () => {
    emit("update:modelValue", false);
    emit("confirm");
};
</script>

<style scoped lang="scss">
.conflict-dialog {
    @apply overflow-hidden rounded-[24rpx] bg-white;

    // 顶部橙色装饰条
    &__stripe {
        @apply h-[8rpx] w-full;
        background: linear-gradient(90deg, #f6882b 0%, #ffb347 100%);
    }

    // 标题区
    &__header {
        @apply flex items-center gap-x-[20rpx] py-[36rpx];
    }

    &__icon-wrap {
        @apply w-[80rpx] h-[80rpx] rounded-full flex items-center justify-center flex-shrink-0;
        background: #fff3e0;
    }

    &__title {
        @apply text-[30rpx] font-semibold text-[#1a1a1a];
    }

    &__subtitle {
        @apply text-[22rpx] text-[#999] mt-[6rpx];
    }

    // 冲突列表
    &__list {
        @apply w-full;
    }

    // 单条冲突项
    &__item {
        @apply flex items-start gap-x-[16rpx] rounded-[16rpx] px-[24rpx] py-[20rpx];
        background: #fff8f0;
        border: 1rpx solid #ffe0b2;
    }

    &__item-dot {
        @apply w-[12rpx] h-[12rpx] rounded-full flex-shrink-0 mt-[8rpx];
        background: #f6882b;
    }

    &__item-text {
        @apply text-[24rpx] leading-relaxed;
        color: #c85a00;
        word-break: break-all;
    }

    // 底部提示
    &__tip {
        @apply flex items-center gap-x-[10rpx] rounded-[12rpx] px-[24rpx] py-[18rpx] mt-[24rpx];
        background: #f5f5f5;
    }

    &__tip-text {
        @apply text-[22rpx] text-[#999] leading-relaxed;
    }

    // 按钮组
    &__actions {
        @apply flex items-center gap-x-[20rpx] mt-[36rpx];
    }

    &__btn {
        @apply flex-1 h-[88rpx] rounded-[16rpx] flex items-center justify-center text-[28rpx] font-medium;

        &--cancel {
            @apply text-[#666];
            background: #f5f5f5;
        }

        &--confirm {
            @apply text-white;
            background: linear-gradient(135deg, #f6882b 0%, #ffb347 100%);
            box-shadow: 0 8rpx 20rpx rgba(246, 136, 43, 0.35);
        }
    }
}
</style>
