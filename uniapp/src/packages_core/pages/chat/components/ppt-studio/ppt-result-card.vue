<template>
    <view class="ppt-card">
        <view class="ppt-head">
            <view v-if="isGenerating" class="ppt-spin ppt-spin--head"></view>
            <view class="ppt-head__body">
                <text class="ppt-title">{{ topic || "PPT" }}</text>
                <text class="ppt-meta">
                    {{ doneCount }}/{{ totalPages }} 页
                    <text v-if="statusText"> · {{ statusText }}</text>
                </text>
            </view>
        </view>

        <!-- 章节未出前：大骨架 + 生成动画 -->
        <view v-if="isGenerating && !slides.length" class="ppt-boot">
            <view class="ppt-stage ppt-stage--skel">
                <view class="ppt-shimmer"></view>
            </view>
            <view class="ppt-boot__row">
                <view class="ppt-spin"></view>
                <text class="ppt-boot__text">正在生成大纲与幻灯片…</text>
            </view>
        </view>

        <!-- 整卡失败（无页） -->
        <view v-else-if="error && !slides.length" class="ppt-fail" @click="showFullError(error)">
            <u-icon name="close-circle" :size="32" color="#DC2626"></u-icon>
            <text class="ppt-fail__text">{{ error }}</text>
            <text class="ppt-fail__hint">点击查看完整错误</text>
        </view>

        <view v-else class="ppt-list">
            <view v-for="(slide, idx) in slides" :key="slide.page || idx" class="ppt-slide">
                <view class="ppt-stage" @click="onPreview(slide, idx)">
                    <image v-if="slide.url" :src="slide.url" mode="aspectFit" class="ppt-stage__img" />
                    <view v-else-if="slide.loading || (isGenerating && !slide.error)" class="ppt-stage__state">
                        <view class="ppt-shimmer"></view>
                        <view class="ppt-stage__overlay">
                            <view class="ppt-spin"></view>
                            <text class="ppt-stage__tip">第 {{ slide.page || idx + 1 }} 页生成中</text>
                        </view>
                    </view>
                    <view
                        v-else
                        class="ppt-stage__state ppt-stage__state--err"
                        @click.stop="showFullError(slide.error || '生成失败')">
                        <u-icon name="info-circle" :size="30" color="#DC2626"></u-icon>
                        <text class="ppt-stage__err">{{ slide.error || "暂无图片" }}</text>
                        <text class="ppt-stage__hint">点击查看完整错误</text>
                    </view>
                </view>
                <view class="ppt-slide__meta">
                    <text class="ppt-slide__title"> {{ slide.page || idx + 1 }}. {{ slide.title || "未命名" }} </text>
                    <view
                        v-if="!busy && !slide.loading"
                        class="ppt-slide__regen"
                        hover-class="opacity-70"
                        :hover-stay-time="80"
                        @click.stop="emit('regenerate-slide', idx)">
                        重新生成
                    </view>
                </view>
            </view>
        </view>

        <view
            v-if="!busy && doneCount > 0"
            class="ppt-preview"
            hover-class="opacity-80"
            :hover-stay-time="80"
            @click="onPreviewAll">
            <u-icon name="eye" :size="26" color="#FFFFFF"></u-icon>
            <text class="text-white text-[26rpx] font-semibold">预览图片</text>
        </view>
        <view
            v-if="!busy && slides.length"
            class="ppt-regen"
            hover-class="opacity-80"
            :hover-stay-time="80"
            @click="emit('regenerate')">
            重新生成整套
        </view>
        <view
            v-else-if="!busy && error && !slides.length"
            class="ppt-regen"
            hover-class="opacity-80"
            :hover-stay-time="80"
            @click="emit('regenerate')">
            重新生成
        </view>
    </view>
</template>

<script setup lang="ts">
import type { PptSlideItem } from "../../composables/useWorkbenchPpt";

const props = withDefaults(
    defineProps<{
        topic?: string;
        slides?: PptSlideItem[];
        pageCount?: number;
        busy?: boolean;
        error?: string;
    }>(),
    {
        topic: "",
        slides: () => [],
        pageCount: 0,
        busy: false,
        error: "",
    },
);

const emit = defineEmits<{
    (e: "regenerate"): void;
    (e: "regenerate-slide", index: number): void;
}>();

const doneCount = computed(() => props.slides.filter((s) => !!s.url && !s.loading).length);

const totalPages = computed(() => props.slides.length || props.pageCount || 0);

const isGenerating = computed(() => props.busy || props.slides.some((s) => s.loading));

const statusText = computed(() => {
    if (isGenerating.value) return "生成中";
    if (props.error && !props.slides.length) return "生成失败";
    if (props.slides.some((s) => s.error && !s.url)) return "部分失败";
    if (doneCount.value > 0) return "已完成";
    return "";
});

const showFullError = (msg: string) => {
    const content = String(msg || "").trim();
    if (!content) return;
    uni.showModal({
        title: "错误详情",
        content,
        showCancel: false,
        confirmText: "知道了",
    });
};

const onPreview = (slide: PptSlideItem, _idx: number) => {
    if (!slide.url) {
        if (slide.error) showFullError(slide.error);
        return;
    }
    const urls = props.slides.map((s) => s.url).filter(Boolean) as string[];
    const current = urls.indexOf(slide.url);
    uni.previewImage({
        urls,
        current: current >= 0 ? current : 0,
    });
};

/** 直接预览全部幻灯片图片（小程序走 uni.previewImage，不另做预览页） */
const onPreviewAll = () => {
    const urls = props.slides.map((s) => s.url).filter(Boolean) as string[];
    if (!urls.length) return;
    uni.previewImage({ urls, current: 0 });
};
</script>

<style lang="scss" scoped>
.ppt-card {
    @apply bg-white rounded-[28rpx] px-[28rpx] py-[28rpx];
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}
.ppt-head {
    @apply flex items-start gap-x-[16rpx] mb-[20rpx];
}
.ppt-head__body {
    @apply flex-1 min-w-0;
}
.ppt-title {
    @apply block text-[28rpx] font-bold text-[#111827] mb-[6rpx];
}
.ppt-meta {
    @apply text-[22rpx] text-[#6B7280];
}
.ppt-spin {
    width: 32rpx;
    height: 32rpx;
    border-radius: 50%;
    border: 4rpx solid #dbeafe;
    border-top-color: #2563eb;
    flex-shrink: 0;
    animation: pptSpin 0.8s linear infinite;
}
.ppt-spin--head {
    margin-top: 6rpx;
}
.ppt-boot {
    @apply w-full;
}
.ppt-boot__row {
    @apply flex items-center justify-center gap-x-[12rpx] mt-[20rpx];
}
.ppt-boot__text {
    @apply text-[24rpx] text-[#6B7280];
}
.ppt-list {
    @apply flex flex-col gap-y-[24rpx];
}
.ppt-slide {
    @apply w-full;
}
.ppt-stage {
    @apply relative w-full rounded-[20rpx] overflow-hidden bg-[#EEF2F7];
    aspect-ratio: 16 / 9;
    min-height: 320rpx;
}
.ppt-stage--skel {
    min-height: 360rpx;
}
.ppt-stage__img {
    @apply w-full h-full block;
    background: #111827;
}
.ppt-stage__state {
    @apply absolute inset-0 flex flex-col items-center justify-center px-[28rpx];
}
.ppt-stage__state--err {
    @apply bg-[#FEF2F2] items-start justify-center gap-y-[12rpx];
}
.ppt-stage__overlay {
    @apply relative z-[1] flex flex-col items-center gap-y-[12rpx];
}
.ppt-stage__tip {
    @apply text-[24rpx] text-[#6B7280];
}
.ppt-stage__err {
    @apply text-[24rpx] text-[#DC2626] leading-relaxed;
    width: 100%;
    word-break: break-all;
    white-space: normal;
    max-height: 220rpx;
    overflow: hidden;
}
.ppt-stage__hint {
    @apply text-[22rpx] text-[#9CA3AF];
}
.ppt-shimmer {
    @apply absolute inset-0;
    background: linear-gradient(110deg, #eef2f7 30%, #e2e8f0 50%, #eef2f7 70%);
    background-size: 200% 100%;
    animation: pptShimmer 1.2s ease-in-out infinite;
}
.ppt-fail {
    @apply w-full rounded-[20rpx] bg-[#FEF2F2] px-[28rpx] py-[32rpx] flex flex-col items-start gap-y-[12rpx];
    min-height: 200rpx;
}
.ppt-fail__text {
    @apply text-[26rpx] text-[#DC2626] leading-relaxed;
    width: 100%;
    word-break: break-all;
    white-space: normal;
}
.ppt-fail__hint {
    @apply text-[22rpx] text-[#9CA3AF];
}
.ppt-slide__meta {
    @apply mt-[12rpx] flex items-start justify-between gap-x-[16rpx];
}
.ppt-slide__title {
    @apply flex-1 text-[26rpx] text-[#374151] leading-snug;
}
.ppt-slide__regen {
    @apply flex-shrink-0 text-[24rpx] text-primary pt-[2rpx];
}
.ppt-regen {
    @apply mt-[24rpx] h-[72rpx] rounded-[20rpx] bg-[#EFF6FF] text-primary text-[26rpx] font-semibold flex items-center justify-center;
}
.ppt-preview {
    @apply mt-[24rpx] h-[76rpx] rounded-[20rpx] text-white text-[26rpx] font-semibold flex items-center justify-center gap-x-[8rpx];
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
}
@keyframes pptSpin {
    to {
        transform: rotate(360deg);
    }
}
@keyframes pptShimmer {
    0% {
        background-position: 100% 0;
    }
    100% {
        background-position: -100% 0;
    }
}
</style>
