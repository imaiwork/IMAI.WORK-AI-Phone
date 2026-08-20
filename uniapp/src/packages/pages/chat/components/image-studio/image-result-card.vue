<template>
    <view class="img-card">
        <!-- 生成中骨架（对齐 HTML doGenerate） -->
        <template v-if="status === 'generating'">
            <view class="img-card__status img-card__status--muted">
                <view class="img-spin"></view>
                <text>正在生成 {{ count }} 张图片 · {{ modelName }} · {{ ratio }}</text>
            </view>
            <view class="img-grid" :class="count > 1 ? 'img-grid--2' : 'img-grid--1'">
                <view
                    v-for="i in count"
                    :key="i"
                    class="img-skel"
                    :style="{ aspectRatio: String(aspectRatio) }"></view>
            </view>
        </template>

        <!-- 失败 -->
        <template v-else-if="status === 'error'">
            <view class="img-card__status img-card__status--error">
                <u-icon name="close-circle" :size="28" color="#DC2626"></u-icon>
                <text>{{ error || "生成失败，请重试" }}</text>
            </view>
            <view
                class="img-card__regen"
                hover-class="opacity-70"
                :hover-stay-time="80"
                @click="emit('regenerate')">
                <u-icon name="reload" :size="26" color="#4B5563"></u-icon>
                <text>重新生成</text>
            </view>
        </template>

        <!-- 成功结果 -->
        <template v-else>
            <view class="img-card__status img-card__status--ok">
                <u-icon name="checkmark-circle" :size="28" color="#16A34A"></u-icon>
                <text>{{ title || `已生成 ${urls.length || count} 张 · ${sizeLabel}` }}</text>
            </view>
            <view class="img-grid" :class="(urls.length || count) > 1 ? 'img-grid--2' : 'img-grid--1'">
                <view
                    v-for="(url, idx) in urls"
                    :key="idx"
                    class="img-item"
                    :style="{ aspectRatio: String(aspectRatio) }">
                    <image
                        :src="url"
                        mode="aspectFill"
                        class="img-item__pic"
                        lazy-load
                        @click="preview(idx)" />
                    <view
                        class="img-item__save"
                        hover-class="opacity-80"
                        :hover-stay-time="80"
                        @click.stop="emit('save', url)">
                        <u-icon name="download" :size="24" color="#FFFFFF"></u-icon>
                    </view>
                </view>
            </view>
            <view
                class="img-card__regen"
                hover-class="opacity-70"
                :hover-stay-time="80"
                @click="emit('regenerate')">
                <u-icon name="reload" :size="26" color="#4B5563"></u-icon>
                <text>重新生成</text>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        status?: "generating" | "done" | "error";
        urls?: string[];
        title?: string;
        error?: string;
        count?: number;
        modelName?: string;
        ratio?: string;
        sizeLabel?: string;
        aspectRatio?: number;
    }>(),
    {
        status: "done",
        urls: () => [],
        title: "",
        error: "",
        count: 1,
        modelName: "",
        ratio: "9:16",
        sizeLabel: "9:16 · 高清2K",
        aspectRatio: 9 / 16,
    },
);

const emit = defineEmits<{
    (e: "regenerate"): void;
    (e: "save", url: string): void;
}>();

const preview = (index: number) => {
    if (!props.urls.length) return;
    uni.previewImage({ urls: props.urls, current: props.urls[index] });
};
</script>

<style lang="scss" scoped>
.img-card {
    @apply bg-white rounded-[28rpx] px-[26rpx] py-[22rpx];
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}
.img-card__status {
    @apply flex items-center gap-x-[12rpx] text-[24rpx] mb-[18rpx];
}
.img-card__status--muted {
    @apply text-[#6B7280];
}
.img-card__status--ok {
    @apply text-[#16A34A];
}
.img-card__status--error {
    @apply text-[#DC2626];
}
.img-grid {
    @apply w-full;
    display: grid;
    gap: 16rpx;
}
.img-grid--1 {
    grid-template-columns: 1fr;
}
.img-grid--2 {
    grid-template-columns: 1fr 1fr;
}
.img-skel {
    @apply w-full rounded-[24rpx];
    background: linear-gradient(110deg, #eef2f7 30%, #e2e8f0 50%, #eef2f7 70%);
    background-size: 200% 100%;
    animation: imgShimmer 1.2s ease-in-out infinite;
}
.img-item {
    @apply relative w-full rounded-[24rpx] overflow-hidden bg-[#EEF2F7];
}
.img-item__pic {
    @apply w-full h-full block;
}
.img-item__save {
    @apply absolute right-[12rpx] bottom-[12rpx] w-[52rpx] h-[52rpx] rounded-full flex items-center justify-center;
    background: rgba(0, 0, 0, 0.5);
}
.img-card__regen {
    @apply mt-[18rpx] flex items-center justify-center gap-x-[10rpx] text-[26rpx] text-[#4B5563] bg-[#F3F4F6] rounded-[20rpx] py-[16rpx];
}
.img-spin {
    @apply w-[26rpx] h-[26rpx] rounded-full flex-shrink-0;
    border: 4rpx solid #dbeafe;
    border-top-color: #2563eb;
    animation: imgSpin 0.8s linear infinite;
}
@keyframes imgSpin {
    to {
        transform: rotate(360deg);
    }
}
@keyframes imgShimmer {
    0% {
        background-position: 100% 0;
    }
    100% {
        background-position: -100% 0;
    }
}
</style>
