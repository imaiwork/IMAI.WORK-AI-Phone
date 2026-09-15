<template>
    <view v-if="show" class="cut-mask" @click="handleCancel">
        <view class="cut-modal" @click.stop>
            <view class="cut-handle"></view>
            <view class="cut-header">
                <view class="flex-1 min-w-0">
                    <text class="cut-title">视频处理方式</text>
                    <text class="cut-sub">剪辑原片库 · 自动适配</text>
                </view>
                <view class="cut-close" @click="handleCancel">
                    <u-icon name="close" color="#647184" size="24"></u-icon>
                </view>
            </view>

            <view class="cut-body">
                <view class="cut-options">
                    <view
                        v-for="option in options"
                        :key="option.value"
                        class="cut-opt"
                        :class="{ active: selected === option.value }"
                        @click="selected = option.value">
                        <view class="cut-opt-icon" :style="{ background: option.iconBg }">
                            <u-icon :name="option.icon" :color="option.iconColor" size="36"></u-icon>
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
                            color="#0065FB"
                            size="36"
                            class="shrink-0"></u-icon>
                    </view>
                </view>
                <view class="cut-note">
                    <u-icon name="info-circle" color="#536A87" size="24"></u-icon>
                    <text class="flex-1 min-w-0">不切割会保留整段视频，仍会自动转码适配，不等于原图模式。</text>
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
    const scene = isOssTranscode.value ? TokensSceneEnum.MATERIAL_SLICE_OSS : TokensSceneEnum.MATERIAL_SLICE_LOCAL;
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
        iconBg: "#EDF3FF",
        iconColor: "#1764CD",
    },
    {
        value: "none",
        title: "不切割",
        desc: "保留原素材 · ",
        descHighlight: "不产生费用",
        highlightColor: "#10B981",
        icon: "close-circle",
        iconBg: "#EDF3FF",
        iconColor: "#1764CD",
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
    @apply fixed inset-0 z-[70] flex items-end justify-center;
    background: rgba(20, 33, 54, 0.4);
}

.cut-modal {
    @apply relative w-full flex flex-col min-h-[440rpx] bg-white overflow-hidden rounded-t-[48rpx];
    max-height: 88vh;
    box-shadow: 0 -20rpx 72rpx rgba(23, 42, 64, 0.07);
}

.cut-handle {
    @apply w-[64rpx] h-[8rpx] shrink-0 rounded-[8rpx] bg-[#DCE3EC] mx-auto mt-[20rpx] mb-[4rpx];
}

.cut-header {
    @apply flex items-center justify-between gap-[24rpx] shrink-0 px-[36rpx] pt-[18rpx] pb-[26rpx] border-[0] border-b-[2rpx] border-solid border-[#EEF1F5];
}

.cut-body {
    @apply p-[36rpx] min-h-0 overflow-y-auto;
}

.cut-title {
    @apply block text-[34rpx] font-semibold text-[#202937] leading-[1.4];
}

.cut-sub {
    @apply block text-[24rpx] text-[#6C7C91] mt-[8rpx] leading-[1.5];
}

.cut-close {
    @apply relative w-[56rpx] h-[56rpx] shrink-0 flex items-center justify-center rounded-full bg-[#F1F4F8] active:opacity-70;

    &::after {
        content: "";
        @apply absolute inset-[-16rpx];
    }
}

.cut-options {
    @apply flex flex-col gap-[20rpx];
}

.cut-opt {
    @apply w-full flex items-center gap-[20rpx] min-h-[120rpx] rounded-[22rpx] px-[24rpx] py-[22rpx] border-[2rpx] border-solid border-[#DFE6F0] bg-white;
    transition: border-color 0.15s ease, background 0.15s ease;

    &.active {
        @apply border-[#A7C9FB] bg-[#EFF5FF];
    }
}

.cut-opt-icon {
    @apply w-[72rpx] h-[72rpx] rounded-[20rpx] flex items-center justify-center shrink-0;
}

.cut-opt-main {
    @apply flex-1 min-w-0;
}

.cut-opt-title-row {
    @apply flex items-center gap-[12rpx];
}

.cut-opt-title {
    @apply text-[28rpx] font-semibold text-[#2D405A] leading-[1.5];
}

.cut-opt-badge {
    @apply text-[20rpx] font-medium text-[#075ECF] bg-[#EDF4FF] px-[12rpx] py-[4rpx] rounded-full;
}

.cut-opt-desc {
    @apply flex flex-wrap items-center mt-[8rpx] leading-[1.6];
}

.cut-opt-desc-text {
    @apply text-[24rpx] text-[#60758F];
}

.cut-opt-desc-hl {
    @apply text-[24rpx] font-medium;
}

.cut-note {
    @apply flex items-start gap-[16rpx] mt-[24rpx] p-[24rpx] text-[24rpx] text-[#536A87] bg-[#EDF3FC] border-[2rpx] border-solid border-[#E0E9F7] rounded-[24rpx] leading-[1.7];
}

.cut-footer {
    @apply flex shrink-0 gap-[20rpx] px-[36rpx] pt-[24rpx] border-[0] border-t-[2rpx] border-solid border-[#E7ECF2] bg-white;
    padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
}

.cut-btn {
    @apply flex-1 min-h-[88rpx] px-[32rpx] py-[20rpx] rounded-[24rpx] border-[2rpx] border-solid flex items-center justify-center active:opacity-70;

    text {
        @apply text-[28rpx] font-semibold;
    }
}

.cut-btn-cancel {
    @apply border-[#D8E2ED] bg-white text-[#36516F];
}

.cut-btn-confirm {
    @apply border-primary bg-primary text-white;
}
</style>
