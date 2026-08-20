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
                <text class="mi-title">手动入库</text>
                <view class="mi-close" @click="handleClose">
                    <u-icon name="close" color="#9CA3AF" size="20"></u-icon>
                </view>
            </view>
            <text class="mi-sub">粘贴爆款链接，自动识别来源平台并加入爆款库</text>
        </template>

        <template #content>
            <view class="mi-body">
                <textarea
                    v-model="shareContent"
                    class="mi-textarea"
                    :maxlength="-1"
                    :auto-height="false"
                    placeholder="粘贴抖音 / 小红书 的分享链接"
                    placeholder-class="mi-placeholder"
                    @input="handleDetect" />

                <view v-if="detectedPlatform" class="mi-result">
                    <view class="mi-plat-logo" :style="{ background: detectedPlatform.color }">
                        <text class="mi-plat-chip">{{ detectedPlatform.short }}</text>
                    </view>
                    <view class="mi-result-info">
                        <text class="mi-plat-name">{{ detectedPlatform.label }}</text>
                        <text class="mi-result-ok">已识别来源平台，可确认入库</text>
                    </view>
                    <u-icon name="checkmark-circle" color="#52C41A" size="36"></u-icon>
                </view>

                <view v-else-if="hasInput" class="mi-unknown">
                    <u-icon name="info-circle" color="#F86E21" size="28"></u-icon>
                    <text class="mi-unknown-text"> 暂无法识别平台，请粘贴抖音 / 小红书链接 </text>
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
    xiaohongshu: { key: "xiaohongshu", label: "小红书", short: "红", color: "#EF4444" },
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
    show.value = false;
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
    width: 80rpx;
    height: 8rpx;
    border-radius: 999rpx;
    background: #e5e7eb;
    margin: 24rpx auto 0;
}

.mi-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28rpx 40rpx 0;
}

.mi-title {
    font-size: 32rpx;
    font-weight: 800;
    color: #1f2937;
}

.mi-close {
    width: 56rpx;
    height: 56rpx;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mi-sub {
    display: block;
    padding: 8rpx 40rpx 0;
    font-size: 24rpx;
    color: #9ca3af;
    line-height: 1.5;
}

.mi-body {
    padding: 32rpx 40rpx calc(24rpx + env(safe-area-inset-bottom));
}

.mi-textarea {
    width: 100%;
    min-height: 180rpx;
    border-radius: 28rpx;
    padding: 24rpx 32rpx;
    font-size: 26rpx;
    line-height: 1.6;
    color: #1d2129;
    background: #f7f9fc;
    border: 3rpx solid #eef1f6;
    box-sizing: border-box;
}

.mi-placeholder {
    color: #c0c8d8;
    font-size: 26rpx;
    line-height: 1.6;
}

.mi-result {
    margin-top: 24rpx;
    display: flex;
    align-items: center;
    gap: 24rpx;
    border-radius: 28rpx;
    padding: 28rpx 32rpx;
    background: #f0fbea;
    border: 2rpx solid #d3f1bf;
}

.mi-plat-logo {
    width: 72rpx;
    height: 72rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.mi-plat-chip {
    font-size: 26rpx;
    font-weight: 800;
    color: #ffffff;
}

.mi-result-info {
    flex: 1;
    min-width: 0;
}

.mi-plat-name {
    display: block;
    font-size: 26rpx;
    font-weight: 800;
    color: #1f2937;
}

.mi-result-ok {
    display: block;
    margin-top: 4rpx;
    font-size: 22rpx;
    color: #52c41a;
}

.mi-unknown {
    margin-top: 24rpx;
    display: flex;
    align-items: center;
    gap: 20rpx;
    border-radius: 28rpx;
    padding: 24rpx 32rpx;
    background: #fff7e8;
    border: 2rpx solid #ffe1b3;
}

.mi-unknown-text {
    flex: 1;
    font-size: 24rpx;
    color: #b26205;
    line-height: 1.5;
}

.mi-actions {
    display: flex;
    gap: 24rpx;
    margin-top: 40rpx;
}

.mi-btn {
    flex: 1;
    height: 88rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;
    font-weight: 700;
}

.mi-btn-cancel {
    background: #f3f7fc;
    color: #6b7280;
}

.mi-btn-confirm {
    background: #2f73f6;
    color: #ffffff;
}

.mi-btn-confirm.disabled {
    opacity: 0.4;
    pointer-events: none;
}

.mi-btn:active {
    opacity: 0.85;
}
</style>
