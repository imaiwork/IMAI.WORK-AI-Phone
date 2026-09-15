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
        @close="handleClose">
        <template #header>
            <view class="mi-handle"></view>
            <view class="mi-hd">
                <view class="flex-1 min-w-0">
                    <text class="mi-title">手动入库</text>
                    <text class="mi-sub">抖音 / 小红书 / 快手 / 视频号</text>
                </view>
                <view class="mi-close" @click="handleClose">
                    <u-icon name="close" color="#5B6B80" size="24"></u-icon>
                </view>
            </view>
        </template>

        <template #content>
            <view class="mi-body">
                <text class="mi-label">作品分享链接</text>
                <textarea
                    v-model="shareContent"
                    class="mi-textarea"
                    :maxlength="-1"
                    :auto-height="false"
                    placeholder="粘贴包含链接的完整分享内容"
                    placeholder-class="mi-placeholder"
                    @input="handleDetect" />
                <text class="mi-hint">自动识别来源平台，确认后加入当前人设的爆款库。</text>

                <view v-if="detectedPlatform" class="mi-result">
                    <view class="mi-plat-logo" :style="{ background: detectedPlatform.color }">
                        <text class="mi-plat-chip">{{ detectedPlatform.short }}</text>
                    </view>
                    <view class="mi-result-info">
                        <text class="mi-plat-name">{{ detectedPlatform.label }}</text>
                        <text class="mi-result-ok">已识别来源平台，可确认入库</text>
                    </view>
                    <u-icon name="checkmark-circle" color="#16835E" size="36"></u-icon>
                </view>

                <view v-else-if="hasInput" class="mi-unknown">
                    <u-icon name="info-circle" color="#F86E21" size="28"></u-icon>
                    <text class="mi-unknown-text">暂无法识别平台，请粘贴支持平台的完整分享内容。</text>
                </view>
            </view>
            <view class="mi-actions">
                <view class="mi-btn mi-btn-cancel" @click="handleClose">
                    <text>取消</text>
                </view>
                <view
                    class="mi-btn mi-btn-confirm"
                    :class="{ disabled: !canConfirm || submitting }"
                    @click="handleConfirm">
                    <text>{{ submitting ? "入库中..." : "确认入库" }}</text>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { manualImportDeviceViralRecord } from "@/api/person";

type DetectedPlatform = {
    key: "douyin" | "xiaohongshu" | "kuaishou" | "shipinhao";
    label: string;
    short: string;
    color: string;
};

const PLATFORM_META: Record<DetectedPlatform["key"], DetectedPlatform> = {
    douyin: { key: "douyin", label: "抖音", short: "抖", color: "#111827" },
    xiaohongshu: { key: "xiaohongshu", label: "小红书", short: "红", color: "#F56C6C" },
    kuaishou: { key: "kuaishou", label: "快手", short: "快", color: "#FF6800" },
    shipinhao: { key: "shipinhao", label: "视频号", short: "视", color: "#FA9D3B" },
};

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "success"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const shareContent = ref("");
const detectedPlatform = ref<DetectedPlatform | null>(null);
const submitting = ref(false);

const hasInput = computed(() => shareContent.value.trim().length > 0);
const canConfirm = computed(() => !!detectedPlatform.value && hasInput.value);

const detectPlatform = (raw: string): DetectedPlatform | null => {
    const text = raw.trim().toLowerCase();
    if (!text) return null;
    if (/xiaohongshu\.com|xhslink\.com|xhs\.link|小红书/.test(text)) {
        return PLATFORM_META.xiaohongshu;
    }
    if (/douyin\.com|iesdouyin\.com|抖音|dou音/.test(text)) {
        return PLATFORM_META.douyin;
    }
    if (/kuaishou\.com|gifshow\.com|v\.kwai|快手/.test(text)) {
        return PLATFORM_META.kuaishou;
    }
    if (/channels\.weixin|weixin\.qq\.com|wxvideo|视频号/.test(text)) {
        return PLATFORM_META.shipinhao;
    }
    return null;
};

const handleDetect = (): void => {
    detectedPlatform.value = detectPlatform(shareContent.value);
};

const resetState = (): void => {
    shareContent.value = "";
    detectedPlatform.value = null;
    submitting.value = false;
};

const handleClose = (): void => {
    emit("update:modelValue", false);
};

const handleConfirm = async (): Promise<void> => {
    if (!canConfirm.value || submitting.value) return;
    const content = shareContent.value.trim();
    if (!content) {
        uni.$u.toast("请粘贴分享链接");
        return;
    }
    if (!props.personaId) {
        uni.$u.toast("人设信息异常");
        return;
    }

    submitting.value = true;
    try {
        await manualImportDeviceViralRecord({
            persona_id: props.personaId,
            share_content: content,
        });
        uni.$u.toast(detectedPlatform.value ? `已入库到「${detectedPlatform.value.label}」爆款库` : "已入库");
        show.value = false;
        emit("success");
    } catch (error: any) {
        uni.$u.toast(error || "入库失败");
    } finally {
        submitting.value = false;
    }
};

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) resetState();
    },
);
</script>

<style lang="scss" scoped>
.mi-handle {
    @apply w-[64rpx] h-[8rpx] rounded-[8rpx] bg-[#DCE3EC] mx-auto mt-[20rpx] mb-[4rpx];
}

.mi-hd {
    @apply flex items-center justify-between gap-[24rpx] px-[36rpx] pt-[18rpx] pb-[26rpx]
        border-[0] border-b-[2rpx] border-solid border-[#EEF1F5];
}

.mi-title {
    @apply block text-[34rpx] font-semibold text-[#202937];
}

.mi-close {
    @apply relative w-[56rpx] h-[56rpx] rounded-full bg-[#F1F4F8] flex items-center justify-center shrink-0 active:opacity-70;

    &::after {
        content: "";
        @apply absolute inset-[-16rpx];
    }
}

.mi-sub {
    @apply block mt-[8rpx] text-[24rpx] text-[#647184] leading-[36rpx];
}

.mi-body {
    @apply p-[36rpx];
}

.mi-label {
    @apply block mb-[16rpx] text-[26rpx] font-semibold text-[#475971];
}

.mi-hint {
    @apply block mt-[16rpx] text-[24rpx] leading-[38rpx] text-[#647184];
}

.mi-textarea {
    @apply w-full h-[240rpx] box-border rounded-[22rpx] px-[24rpx] py-[22rpx]
        text-[30rpx] leading-[1.6] text-[#26364E] bg-[#F9FBFD]
        border-[2rpx] border-solid border-[#DBE3EE];
}

.mi-placeholder {
    @apply text-[30rpx] leading-[1.6] text-[#647184];
}

.mi-result {
    @apply mt-[24rpx] flex items-center gap-[20rpx] rounded-[22rpx] px-[24rpx] py-[22rpx]
        bg-[#EDF8F3] border-[2rpx] border-solid border-[#D5EBDD];
}

.mi-plat-logo {
    @apply w-[64rpx] h-[64rpx] rounded-[16rpx] flex items-center justify-center shrink-0;
}

.mi-plat-chip {
    @apply text-[26rpx] font-semibold text-white;
}

.mi-result-info {
    @apply flex-1 min-w-0;
}

.mi-plat-name {
    @apply block text-[28rpx] font-semibold text-[#202937];
}

.mi-result-ok {
    @apply block mt-[4rpx] text-[24rpx] leading-[36rpx] text-[#16835E];
}

.mi-unknown {
    @apply mt-[24rpx] flex items-center gap-[20rpx] rounded-[22rpx] px-[24rpx] py-[22rpx]
        bg-[#FFF7E8] border-[2rpx] border-solid border-[#FFE1B3];
}

.mi-unknown-text {
    @apply flex-1 text-[24rpx] text-[#9B5C08] leading-[36rpx];
}

.mi-actions {
    @apply flex gap-[20rpx] px-[36rpx] pt-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]
        border-[0] border-t-[2rpx] border-solid border-[#E7ECF2];
}

.mi-btn {
    @apply flex-1 min-h-[94rpx] rounded-[24rpx] flex items-center justify-center text-[28rpx] font-semibold;
}

.mi-btn-cancel {
    @apply bg-white text-[#36516F] border-[2rpx] border-solid border-[#D8E2ED];
}

.mi-btn-confirm {
    @apply bg-primary text-white;
}

.mi-btn-confirm.disabled {
    @apply opacity-40 pointer-events-none;
}

.mi-btn:active {
    @apply opacity-85;
}
</style>
