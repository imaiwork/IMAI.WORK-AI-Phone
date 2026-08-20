<template>
    <u-popup v-model="visible" mode="center" border-radius="24" :closeable="false" width="680rpx" @close="handleClose">
        <view class="conflict-dialog">
            <view class="conflict-dialog__stripe" :class="stripeClass"></view>

            <view class="px-[40rpx] pb-[40rpx]">
                <view class="conflict-dialog__header">
                    <view class="conflict-dialog__icon-wrap" :class="iconWrapClass">
                        <u-icon :name="headerIcon.name" size="36" :color="headerIcon.color"></u-icon>
                    </view>
                    <view>
                        <view class="conflict-dialog__title">{{ headerTitle }}</view>
                        <view class="conflict-dialog__subtitle">{{ headerSubtitle }}</view>
                    </view>
                </view>

                <view v-if="showTabs" class="conflict-dialog__tabs">
                    <view
                        class="conflict-dialog__tab"
                        :class="{ 'conflict-dialog__tab--active-warn': activeTab === 'messages' }"
                        @click="activeTab = 'messages'">
                        <u-icon
                            name="warning-fill"
                            size="22"
                            :color="activeTab === 'messages' ? '#F6882B' : '#999'"></u-icon>
                        <text>冲突警告</text>
                        <view class="conflict-dialog__tab-badge conflict-dialog__tab-badge--warn">{{
                            messages.length
                        }}</view>
                    </view>
                    <view
                        class="conflict-dialog__tab"
                        :class="{ 'conflict-dialog__tab--active-error': activeTab === 'errors' }"
                        @click="activeTab = 'errors'">
                        <u-icon
                            name="close-circle-fill"
                            size="22"
                            :color="activeTab === 'errors' ? '#F04E4E' : '#999'"></u-icon>
                        <text>错误信息</text>
                        <view class="conflict-dialog__tab-badge conflict-dialog__tab-badge--error">{{
                            errors.length
                        }}</view>
                    </view>
                </view>

                <scroll-view scroll-y class="conflict-dialog__list" :style="{ maxHeight: listMaxHeight }">
                    <view class="flex flex-col gap-y-[16rpx]">
                        <template v-if="activeTab === 'messages'">
                            <view
                                v-for="(msg, index) in messages"
                                :key="'msg-' + index"
                                class="conflict-dialog__item conflict-dialog__item--warn">
                                <view class="conflict-dialog__item-dot conflict-dialog__item-dot--warn"></view>
                                <text class="conflict-dialog__item-text conflict-dialog__item-text--warn">{{
                                    msg
                                }}</text>
                            </view>
                        </template>
                        <template v-if="activeTab === 'errors'">
                            <view
                                v-for="(err, index) in errors"
                                :key="'err-' + index"
                                class="conflict-dialog__item conflict-dialog__item--error">
                                <view class="conflict-dialog__item-dot conflict-dialog__item-dot--error"></view>
                                <text class="conflict-dialog__item-text conflict-dialog__item-text--error">{{
                                    err
                                }}</text>
                            </view>
                        </template>
                    </view>
                </scroll-view>

                <view class="conflict-dialog__tip">
                    <u-icon name="info-circle" size="24" color="#999"></u-icon>
                    <text class="conflict-dialog__tip-text">{{ tipText }}</text>
                </view>

                <view class="conflict-dialog__actions">
                    <view class="conflict-dialog__btn conflict-dialog__btn--cancel" @click="handleClose">
                        返回修改
                    </view>
                    <view
                        v-if="!hasErrors"
                        class="conflict-dialog__btn conflict-dialog__btn--confirm"
                        @click="handleConfirm">
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
    errors: string[];
}

interface Emits {
    (e: "update:modelValue", val: boolean): void;
    (e: "close"): void;
    (e: "confirm"): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    messages: () => [],
    errors: () => [],
});

const emit = defineEmits<Emits>();

const hasErrors = computed(() => props.errors.length > 0);

const showTabs = computed(() => props.messages.length > 0 && props.errors.length > 0);

const activeTab = ref<"messages" | "errors">(props.messages.length > 0 ? "messages" : "errors");

watch(
    () => [props.messages.length, props.errors.length],
    () => {
        activeTab.value = props.messages.length > 0 ? "messages" : "errors";
    },
);

const currentList = computed(() => (activeTab.value === "messages" ? props.messages : props.errors));
const listMaxHeight = computed(() => (currentList.value.length > 4 ? "360rpx" : "auto"));

const stripeClass = computed(() => {
    if (activeTab.value === "errors") return "conflict-dialog__stripe--error";
    return "conflict-dialog__stripe--warn";
});

const headerIcon = computed(() => {
    if (activeTab.value === "errors") {
        return { name: "close-circle-fill", color: "#F04E4E" };
    }
    return { name: "warning-fill", color: "#F6882B" };
});

const iconWrapClass = computed(() => {
    if (activeTab.value === "errors") return "conflict-dialog__icon-wrap--error";
    return "";
});

const headerTitle = computed(() => {
    return activeTab.value === "errors" ? "检测到任务错误" : "检测到任务时间冲突";
});

const headerSubtitle = computed(() => {
    if (activeTab.value === "errors") return "以下任务存在错误，请检查后重试";
    return "以下任务与当前设置存在时间冲突";
});

const tipText = computed(() => {
    if (hasErrors.value) return "存在错误的任务无法创建，请返回修改后重试";
    return "可忽略冲突继续创建，或返回修改时间配置";
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

    // 顶部装饰条
    &__stripe {
        @apply h-[8rpx] w-full;
        &--warn {
            background: linear-gradient(90deg, #f6882b 0%, #ffb347 100%);
        }
        &--error {
            background: linear-gradient(90deg, #f04e4e 0%, #ff7875 100%);
        }
    }

    // 标题区
    &__header {
        @apply flex items-center gap-x-[20rpx] py-[36rpx];
    }

    &__icon-wrap {
        @apply w-[80rpx] h-[80rpx] rounded-full flex items-center justify-center flex-shrink-0;
        background: #fff3e0;
        transition: background 0.2s;
        &--error {
            background: #fff1f0;
        }
    }

    &__title {
        @apply text-[30rpx] font-semibold text-[#1a1a1a];
    }

    &__subtitle {
        @apply text-[22rpx] text-[#999] mt-[6rpx];
    }

    // Tab 切换栏
    &__tabs {
        @apply flex items-center rounded-[16rpx] p-[6rpx] mb-[24rpx];
        background: #f5f5f5;
        gap: 8rpx;
    }

    &__tab {
        @apply flex-1 flex items-center justify-center gap-x-[10rpx] h-[72rpx] rounded-[12rpx] text-xs text-[#999];
        transition: all 0.2s;

        &--active-warn {
            @apply text-[#F6882B] font-medium bg-white;
            box-shadow: 0 2rpx 12rpx rgba(246, 136, 43, 0.15);
        }

        &--active-error {
            @apply text-[#F04E4E] font-medium bg-white;
            box-shadow: 0 2rpx 12rpx rgba(240, 78, 78, 0.15);
        }
    }

    &__tab-badge {
        @apply flex items-center justify-center rounded-full text-[20rpx] text-white;
        min-width: 36rpx;
        height: 36rpx;
        padding: 0 8rpx;
        &--warn {
            background: #f6882b;
        }
        &--error {
            background: #f04e4e;
        }
    }

    // 冲突列表
    &__list {
        @apply w-full;
    }

    // 单条冲突项
    &__item {
        @apply flex items-start gap-x-[16rpx] rounded-[16rpx] px-[24rpx] py-[20rpx];
        &--warn {
            background: #fff8f0;
            border: 1rpx solid #ffe0b2;
        }
        &--error {
            background: #fff2f0;
            border: 1rpx solid #ffccc7;
        }
    }

    &__item-dot {
        @apply w-[12rpx] h-[12rpx] rounded-full flex-shrink-0 mt-[8rpx];
        &--warn {
            background: #f6882b;
        }
        &--error {
            background: #f04e4e;
        }
    }

    &__item-text {
        @apply text-xs leading-relaxed;
        word-break: break-all;
        &--warn {
            color: #c85a00;
        }
        &--error {
            color: #cf1322;
        }
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
