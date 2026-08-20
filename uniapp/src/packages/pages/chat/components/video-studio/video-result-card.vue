<template>
    <view class="vid-card">
        <template v-if="status === 'generating'">
            <view class="vid-card__status vid-card__status--muted">
                <view class="vid-spin"></view>
                <text>正在生成视频 · {{ modelName }} · {{ sizeLabel }}</text>
            </view>
            <view class="vid-skel" :style="{ aspectRatio: String(aspectRatio) }"></view>
        </template>

        <template v-else-if="status === 'error'">
            <view class="vid-card__status vid-card__status--error">
                <u-icon name="close-circle" :size="28" color="#DC2626"></u-icon>
                <text>{{ error || "生成失败，请重试" }}</text>
            </view>
            <view
                class="vid-card__regen"
                hover-class="opacity-70"
                :hover-stay-time="80"
                @click="emit('regenerate')">
                <u-icon name="reload" :size="26" color="#4B5563"></u-icon>
                <text>重新生成</text>
            </view>
        </template>

        <template v-else>
            <view class="vid-card__status vid-card__status--ok">
                <u-icon name="checkmark-circle" :size="28" color="#16A34A"></u-icon>
                <text>{{ title || `视频已生成 · ${sizeLabel}` }}</text>
            </view>
            <view v-for="(url, idx) in urls" :key="videoDomId(idx)" class="vid-item">
                <video
                    :id="videoDomId(idx)"
                    :src="url"
                    class="vid-item__player"
                    :style="{ aspectRatio: String(aspectRatio) }"
                    controls
                    object-fit="contain"
                    :show-center-play-btn="true"
                    @play="onVideoPlay(idx)" />
            </view>
            <view v-if="!urls.length" class="vid-empty">
                <text class="text-[24rpx] text-[#9CA3AF]">未返回视频地址</text>
            </view>
            <view class="vid-card__actions">
                <view
                    class="vid-card__action"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('regenerate')">
                    <u-icon name="reload" :size="26" color="#4B5563"></u-icon>
                    <text>重新生成</text>
                </view>
                <view
                    v-if="urls.length"
                    class="vid-card__action"
                    :class="{ 'vid-card__action--disabled': downloading }"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="onDownload">
                    <u-icon name="download" :size="26" color="#4B5563"></u-icon>
                    <text>{{ downloading ? "下载中…" : "下载" }}</text>
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { saveVideoToPhotosAlbum } from "@/utils/file";
import {
    exclusiveVideoPlay,
    releaseExclusiveVideo,
} from "../../composables/useExclusiveVideoPlay";

const props = withDefaults(
    defineProps<{
        status?: "generating" | "done" | "error";
        urls?: string[];
        title?: string;
        error?: string;
        modelName?: string;
        sizeLabel?: string;
        aspectRatio?: number;
        /** 消息下标，用于生成全局唯一 video id */
        videoKey?: string | number;
    }>(),
    {
        status: "done",
        urls: () => [],
        title: "",
        error: "",
        modelName: "",
        sizeLabel: "16:9 · 720p",
        aspectRatio: 16 / 9,
        videoKey: "0",
    },
);

const emit = defineEmits<{
    (e: "regenerate"): void;
}>();

const { proxy }: any = getCurrentInstance();
const downloading = ref(false);

const videoDomId = (idx: number) => `wb-vid-${props.videoKey}-${idx}`;

const onVideoPlay = (idx: number) => {
    const id = videoDomId(idx);
    exclusiveVideoPlay(id, () => {
        try {
            uni.createVideoContext(id, proxy)?.pause?.();
        } catch {
            /* ignore */
        }
    });
};

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));

const onDownload = async () => {
    if (downloading.value) return;
    const list = (props.urls || []).map((u) => String(u || "").trim()).filter(Boolean);
    if (!list.length) {
        uni.$u.toast("暂无可下载视频");
        return;
    }
    downloading.value = true;
    try {
        for (let i = 0; i < list.length; i++) {
            await saveVideoToPhotosAlbum(list[i]);
            if (i < list.length - 1) await sleep(300);
        }
    } finally {
        downloading.value = false;
    }
};

onUnmounted(() => {
    (props.urls || []).forEach((_, idx) => releaseExclusiveVideo(videoDomId(idx)));
});
</script>

<style lang="scss" scoped>
.vid-card {
    @apply bg-white rounded-[28rpx] px-[28rpx] py-[28rpx];
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}
.vid-card__status {
    @apply flex items-center gap-x-[12rpx] text-[24rpx] mb-[20rpx];
}
.vid-card__status--muted {
    @apply text-[#6B7280];
}
.vid-card__status--ok {
    @apply text-[#16A34A] font-medium;
}
.vid-card__status--error {
    @apply text-[#DC2626];
}
.vid-spin {
    @apply w-[28rpx] h-[28rpx] rounded-full border-[4rpx] border-solid border-[#DBEAFE] flex-shrink-0;
    border-top-color: #2563eb;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
.vid-skel {
    @apply w-full rounded-[20rpx] bg-[#EEF2F7];
    min-height: 280rpx;
    animation: pulse 1.2s ease-in-out infinite;
}
@keyframes pulse {
    0%,
    100% {
        opacity: 0.55;
    }
    50% {
        opacity: 1;
    }
}
.vid-item {
    @apply w-full rounded-[20rpx] overflow-hidden bg-[#111111] mb-[16rpx];
}
.vid-item__player {
    @apply w-full;
    min-height: 280rpx;
}
.vid-empty {
    @apply py-[40rpx] flex justify-center;
}
.vid-card__regen {
    @apply mt-[8rpx] h-[72rpx] rounded-[20rpx] bg-[#F3F4F6] text-[#4B5563] text-[26rpx] font-semibold flex items-center justify-center gap-x-[10rpx];
}
.vid-card__actions {
    @apply mt-[8rpx] flex items-center gap-x-[16rpx];
}
.vid-card__action {
    @apply flex-1 h-[72rpx] rounded-[20rpx] bg-[#F3F4F6] text-[#4B5563] text-[26rpx] font-semibold flex items-center justify-center gap-x-[10rpx];
}
.vid-card__action--disabled {
    @apply opacity-50;
}
</style>
