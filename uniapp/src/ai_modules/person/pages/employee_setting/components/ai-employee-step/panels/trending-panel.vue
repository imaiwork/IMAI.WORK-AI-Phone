<template>
    <view class="tracking-panel">
        <view class="tracking-hd">
            <text class="sec-label tracking-title">爆款追踪词</text>
            <view class="tracking-actions">
                <button
                    class="plain-btn small-pill tracking-batch"
                    :disabled="loading"
                    @click.stop="emit('recommend')">
                    换一批
                </button>
                <button class="plain-btn small-pill tracking-ai" :disabled="loading" @click.stop="emit('recommend')">
                    AI 推荐
                </button>
            </view>
        </view>
        <view class="tracking-kw-wrap">
            <view v-if="loading" class="tracking-empty"> 正在获取人设追踪词... </view>
            <view v-else-if="!keywords.length" class="tracking-empty" @click.stop="emit('edit-keyword')">
                暂无追踪词，点击添加
            </view>
            <template v-else>
                <view
                    v-for="(word, index) in keywords"
                    :key="`${word}-${index}`"
                    class="tracking-kw-tag"
                    @click.stop="emit('edit-keyword', index)">
                    <text class="tracking-kw-text">{{ word }}</text>
                    <text class="tracking-kw-remove" @click.stop="emit('remove-keyword', word)"> × </text>
                </view>
            </template>
            <button class="plain-btn tracking-kw-add" @click.stop="emit('edit-keyword')">+ 添加</button>
        </view>
        <text class="tracking-tip"> AI 每天根据这些关键词追踪全平台爆款内容 </text>

        <view class="tracking-filter-section">
            <text class="tracking-section-title">视频时长</text>
            <view class="tracking-chip-wrap">
                <button
                    v-for="item in DURATION_OPTIONS"
                    :key="item.value"
                    class="plain-btn tracking-chip"
                    :class="{ active: Number(duration) === item.value }"
                    @click.stop="emit('update:duration', item.value)">
                    {{ item.label }}
                </button>
            </view>
        </view>

        <view class="tracking-filter-section">
            <text class="tracking-section-title">视频发布时间</text>
            <view class="tracking-chip-wrap">
                <button
                    v-for="item in PUBLISH_DAY_OPTIONS"
                    :key="item.value"
                    class="plain-btn tracking-chip"
                    :class="{ active: Number(publishDay) === item.value }"
                    @click.stop="emit('update:publishDay', item.value)">
                    {{ item.label }}
                </button>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const DURATION_OPTIONS = [
    { label: "1 分钟以下", value: 1 },
    { label: "1-5 分钟", value: 2 },
    { label: "5 分钟以上", value: 3 },
    { label: "不限", value: 0 },
];

const PUBLISH_DAY_OPTIONS = [
    { label: "一天内", value: 1 },
    { label: "一周内", value: 2 },
    { label: "半年内", value: 3 },
    { label: "不限", value: 0 },
];

defineProps<{
    keywords: string[];
    loading: boolean;
    trackingMode: number;
    duration: number;
    publishDay: number;
    trackingAccountConfig: Record<string, { account: string; homepage_url: string }>;
}>();

const emit = defineEmits<{
    (e: "recommend"): void;
    (e: "edit-keyword", index?: number): void;
    (e: "remove-keyword", word: string): void;
    (e: "update:trackingMode", value: number): void;
    (e: "update:duration", value: number): void;
    (e: "update:publishDay", value: number): void;
    (e: "update:trackingAccountConfig", value: Record<string, { account: string; homepage_url: string }>): void;
}>();
</script>

<style scoped lang="scss">
// 与主文件保持一致的基础工具类（scoped 不串）
.sec-label {
    @apply block text-[22rpx] text-[#9ca3af] mb-[12rpx] font-medium;
}

.plain-btn {
    @apply m-0 p-0 leading-none border-none bg-[transparent];

    &::after {
        border: none;
    }
}

.small-pill {
    @apply h-[44rpx] px-[20rpx] rounded-full text-[20rpx] flex items-center justify-center;
}

.tracking-panel {
    @apply w-full;
}

.tracking-section-title {
    @apply block text-[28rpx] text-[#1d2129] mb-[18rpx] font-bold;
}

.tracking-hd {
    @apply flex items-center justify-between gap-[16rpx] mb-[24rpx];
}

.tracking-title {
    @apply mb-0 shrink-0;
}

.tracking-actions {
    @apply flex items-center gap-[12rpx] shrink-0;
}

.tracking-batch {
    @apply bg-[#f3f4f6] text-[#9ca3af];
}

.tracking-ai {
    @apply bg-[#ebf2ff] text-[#2f73f6];
}

.tracking-kw-wrap {
    @apply flex flex-wrap items-center gap-[14rpx];
}

.tracking-kw-tag {
    @apply inline-flex items-center gap-[10rpx] min-h-[56rpx] max-w-full rounded-[18rpx] bg-white border-[2rpx] border-solid border-[#e5e7eb] py-[10rpx] pl-[22rpx] pr-[14rpx] text-[24rpx] font-medium text-[#1d2129] box-border;
}

.tracking-kw-text {
    @apply min-w-0 break-all;
}

.tracking-kw-remove {
    @apply w-[28rpx] h-[28rpx] rounded-full bg-[#eef2f8] text-[#9ca3af] text-[22rpx] leading-[28rpx] text-center shrink-0;
}

.tracking-empty {
    @apply w-full min-h-[88rpx] rounded-[24rpx] bg-white border-[2rpx] border-dashed border-[#dde6f5] text-[#9ca3af] text-[24rpx] flex items-center justify-center;
}

.tracking-kw-add {
    @apply inline-flex items-center min-h-[56rpx] rounded-[18rpx] bg-white border-[2rpx] border-dashed border-[#93bbfd] py-[10rpx] px-[24rpx] text-[24rpx] font-medium text-[#2f73f6] box-border;
}

.tracking-tip {
    @apply block text-[24rpx] text-[#9ca3af] mt-[20rpx];
}

.tracking-filter-section {
    @apply mt-[36rpx];
}

.tracking-chip-wrap {
    @apply flex flex-wrap gap-[18rpx];
}

.tracking-chip {
    @apply h-[64rpx] px-[28rpx] rounded-full border-[2rpx] border-solid border-[#e3eaf4] bg-white text-[#6b7280] text-[24rpx] font-semibold flex items-center justify-center;
}

.tracking-chip.active {
    @apply bg-[#ebf2ff] border-[#2f73f6] text-[#2f73f6] font-bold;
}
</style>
