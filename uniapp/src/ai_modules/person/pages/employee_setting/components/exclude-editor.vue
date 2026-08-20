<template>
    <view>
        <text class="lfc-sec-lbl">对方昵称不包含（防误触）</text>
        <view class="excl-box">
            <text v-if="!words.length" class="excl-empty">暂无排除词</text>
            <view v-else class="excl-tags">
                <view
                    v-for="(word, index) in words"
                    :key="word"
                    class="excl-tag"
                    @click="$emit('edit', { word, index })">
                    <text>{{ word }}</text>
                    <text class="rm" @click.stop="$emit('remove', word)">×</text>
                </view>
            </view>
        </view>
        <view class="cfg-inp-row mt-[16rpx]">
            <input
                :value="input"
                class="cfg-inp"
                :placeholder="placeholder"
                @input="$emit('update:input', ($event.detail as any).value)" />
            <button class="plain-btn cfg-add-btn" @click="$emit('add')">添加</button>
        </view>
    </view>
</template>

<script setup lang="ts">
defineProps<{
    input: string;
    words: string[];
    placeholder: string;
}>();

defineEmits<{
    (event: "update:input", value: string): void;
    (event: "add"): void;
    (event: "remove", value: string): void;
    (event: "edit", payload: { word: string; index: number }): void;
}>();
</script>

<style lang="scss" scoped>
.plain-btn {
    @apply m-0 p-0 leading-none border-none bg-[transparent];

    &::after {
        border: none;
    }
}

.lfc-sec-lbl {
    @apply block text-[22rpx] font-semibold text-[#9ca3af] mb-[16rpx];
}

.excl-box {
    @apply bg-[#f3f7fc] rounded-[24rpx] py-[24rpx] px-[28rpx] min-h-[92rpx];
}

.excl-empty {
    @apply block text-[24rpx] text-[#c0c8d8] text-center py-[8rpx];
}

.excl-tags {
    @apply flex flex-wrap gap-[12rpx];
}

.excl-tag {
    @apply inline-flex items-center gap-[8rpx] text-[24rpx] bg-white border-[3rpx] border-solid border-[#e5e7eb] text-[#1d2129] py-[8rpx] px-[20rpx] rounded-full;
}

.rm {
    @apply text-[#c0c8d8] text-[28rpx] leading-none;
}

.cfg-inp-row {
    @apply flex gap-[16rpx] items-stretch;
}

.cfg-inp {
    @apply flex-1 w-full min-h-[88rpx] bg-[#f3f7fc] border-[3rpx] border-solid border-[transparent] rounded-[24rpx] px-[28rpx] text-[24rpx] text-[#1d2129] box-border;
}

.cfg-add-btn {
    @apply bg-[#2f73f6] text-white text-[26rpx] font-bold rounded-[24rpx] px-[36rpx] min-h-[88rpx] flex items-center justify-center shrink-0;
}
</style>
