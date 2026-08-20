<template>
    <view v-if="show" class="cut-mask" @click="handleCancel">
        <view class="cut-modal" @click.stop>
            <view class="cut-body">
                <text class="cut-title">选择切割模式</text>
                <text class="cut-sub">视频素材将按 5 秒/段 自动分割，便于 AI 混剪</text>

                <view class="cut-options">
                    <view
                        v-for="option in options"
                        :key="option.value"
                        class="cut-opt"
                        :class="{ active: selected === option.value }"
                        @click="selected = option.value">
                        <view class="cut-opt-icon" :style="{ background: option.iconBg }">
                            <u-icon :name="option.icon" :color="option.iconColor" size="34"></u-icon>
                        </view>
                        <view class="cut-opt-main">
                            <view class="cut-opt-title-row">
                                <text class="cut-opt-title">{{ option.title }}</text>
                                <text v-if="option.badge" class="cut-opt-badge">{{ option.badge }}</text>
                            </view>
                            <view class="cut-opt-desc">
                                <text class="cut-opt-desc-text">{{ option.desc }}</text>
                                <text
                                    v-if="option.descHighlight"
                                    class="cut-opt-desc-hl"
                                    :style="{ color: option.highlightColor }">
                                    {{ option.descHighlight }}
                                </text>
                            </view>
                        </view>
                        <u-icon
                            v-if="selected === option.value"
                            name="checkmark-circle-fill"
                            color="#2F73F6"
                            size="36"
                            class="shrink-0"></u-icon>
                    </view>
                </view>
            </view>

            <view class="cut-footer">
                <view class="cut-btn cut-btn-cancel" @click="handleCancel">
                    <text>取消</text>
                </view>
                <view class="cut-btn cut-btn-confirm" @click="handleConfirm">
                    <text>确认</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { TokensSceneEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";

export type CutMode = "cut" | "none";

type CutOption = {
    value: CutMode;
    title: string;
    desc: string;
    descHighlight?: string;
    highlightColor?: string;
    badge?: string;
    icon: string;
    iconBg: string;
    iconColor: string;
};

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm", mode: CutMode): void;
}>();

const appStore = useAppStore();
const userStore = useUserStore();

const show = computed({
    get: () => props.modelValue,
    set: (val: boolean) => emit("update:modelValue", val),
});

const selected = ref<CutMode>("cut");

// 与后台「媒体处理」一致：OSS 切割走 material_slice_oss，本地走 material_slice_local
const isOssTranscode = computed(() => !!appStore.config?.is_oss_transcode);
const cutToken = computed(() => {
    const scene = isOssTranscode.value
        ? TokensSceneEnum.MATERIAL_SLICE_OSS
        : TokensSceneEnum.MATERIAL_SLICE_LOCAL;
    return userStore.getTokenByScene(scene);
});
const cutFeeText = computed(() => {
    const score = cutToken.value?.score;
    if (score === undefined || score === null || score === "") return "便于 AI 混剪";
    const unit = cutToken.value?.unit || "算力/秒";
    return `消耗 ${score}${unit}`;
});

const options = computed<CutOption[]>(() => [
    {
        value: "cut",
        title: "切割",
        desc: "按 5 秒/段自动分割 · ",
        descHighlight: cutFeeText.value,
        highlightColor: "#F86E21",
        badge: "推荐",
        icon: "grid",
        iconBg: "#EEF6EE",
        iconColor: "#10B981",
    },
    {
        value: "none",
        title: "不切割",
        desc: "保留原素材 · ",
        descHighlight: "不产生费用",
        highlightColor: "#10B981",
        icon: "close-circle",
        iconBg: "#F3F4F6",
        iconColor: "#9CA3AF",
    },
]);

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) selected.value = "cut";
    },
);

const handleCancel = () => {
    show.value = false;
};

const handleConfirm = () => {
    emit("confirm", selected.value);
    show.value = false;
};
</script>

<style scoped lang="scss">
.cut-mask {
    @apply fixed inset-0 z-[70] flex items-center justify-center px-[64rpx];
    background: rgba(0, 0, 0, 0.45);
}

.cut-modal {
    @apply relative w-full bg-white overflow-hidden;
    max-width: 660rpx;
    border-radius: 32rpx;
}

.cut-body {
    @apply px-[40rpx] pt-[40rpx] pb-[32rpx];
}

.cut-title {
    @apply block text-[30rpx] font-bold text-[#1F2937] text-center;
}

.cut-sub {
    @apply block text-[22rpx] text-[#9CA3AF] text-center mt-[8rpx] mb-[32rpx];
}

.cut-options {
    @apply flex flex-col gap-[20rpx];
}

.cut-opt {
    @apply w-full flex items-center gap-[24rpx] rounded-[24rpx] px-[28rpx] py-[24rpx];
    border: 3rpx solid #f3f4f6;
    background: #f9fafb;
    transition: border-color 0.15s ease, background 0.15s ease;

    &.active {
        border-color: #2f73f6;
        background: #ebf3ff;
    }
}

.cut-opt-icon {
    @apply w-[72rpx] h-[72rpx] rounded-[24rpx] flex items-center justify-center shrink-0;
}

.cut-opt-main {
    @apply flex-1 min-w-0;
}

.cut-opt-title-row {
    @apply flex items-center gap-[12rpx];
}

.cut-opt-title {
    @apply text-[26rpx] font-bold text-[#1F2937];
}

.cut-opt-badge {
    @apply text-[18rpx] font-bold text-white px-[12rpx] py-[4rpx] rounded-full;
    background: #2f73f6;
}

.cut-opt-desc {
    @apply flex flex-wrap items-center mt-[6rpx];
}

.cut-opt-desc-text {
    @apply text-[22rpx] text-[#9CA3AF];
}

.cut-opt-desc-hl {
    @apply text-[22rpx] font-semibold;
}

.cut-footer {
    @apply flex;
    border-top: 2rpx solid #f3f4f6;
}

.cut-btn {
    @apply flex-1 py-[28rpx] flex items-center justify-center active:opacity-70;

    text {
        @apply text-[28rpx] font-semibold;
    }
}

.cut-btn-cancel text {
    color: #6b7280;
}

.cut-btn-confirm {
    border-left: 2rpx solid #f3f4f6;

    text {
        color: #2f73f6;
    }
}
</style>
