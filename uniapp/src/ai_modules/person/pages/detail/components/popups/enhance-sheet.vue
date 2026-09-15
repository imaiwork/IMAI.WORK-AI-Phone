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
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="eh-handle"></view>
            <view class="eh-hd">
                <view class="eh-ring" :style="{ background: ringBackground }">
                    <view class="eh-ring-inner">
                        <text class="text-[30rpx] font-bold text-main">{{ percent }}</text>
                    </view>
                </view>
                <view class="flex-1 min-w-0 flex flex-col gap-[8rpx]">
                    <text class="text-[34rpx] font-bold text-main">人设完善度</text>
                    <text class="text-xs leading-[34rpx] text-content">{{ headerTip }}</text>
                </view>
                <view class="eh-close" @click="emit('update:modelValue', false)">
                    <u-icon name="close" color="#999999" size="24"></u-icon>
                </view>
            </view>
        </template>

        <template #content>
            <view class="eh-body">
                <scroll-view scroll-y class="eh-scroll">
                    <view class="px-[40rpx] pb-[24rpx] flex flex-col gap-[28rpx]">
                        <view class="eh-required">
                            <view class="eh-required-badge">
                                <u-icon name="checkmark" color="#FFFFFF" size="24"></u-icon>
                            </view>
                            <text class="flex-1 min-w-0 text-sm font-semibold text-main">
                                必须项 {{ requiredDone }} / {{ requiredTotal }} · 已经能自动产内容
                            </text>
                        </view>

                        <view class="flex flex-col gap-[22rpx]">
                            <view class="flex items-baseline gap-[14rpx]">
                                <text class="text-sm font-bold text-main">让内容更好</text>
                                <text class="text-[22rpx] text-muted">
                                    还有 {{ remainCount }} 项 · 按影响排序
                                </text>
                            </view>

                            <view class="flex flex-col">
                                <view
                                    v-for="(item, index) in undoneItems"
                                    :key="item.key"
                                    class="eh-row"
                                    :class="{ 'eh-row--divided': index > 0 }">
                                    <view class="eh-index">{{ index + 1 }}</view>

                                    <view class="flex-1 min-w-0 flex flex-col gap-[6rpx]">
                                        <view class="flex items-baseline gap-[12rpx]">
                                            <text class="text-sm font-semibold text-main line-clamp-1">
                                                {{ item.title }}
                                            </text>
                                            <text class="shrink-0 text-[22rpx] text-disabled">
                                                {{ item.current }}
                                            </text>
                                        </view>
                                        <text class="text-[22rpx] leading-[32rpx] text-muted">
                                            完成后：{{ item.benefit }}
                                        </text>
                                    </view>

                                    <view class="eh-action active:opacity-70" @click="emit('item', item.key)">
                                        <text class="text-xs font-semibold text-content">
                                            {{ item.actionText }}
                                        </text>
                                    </view>
                                </view>

                                <view v-if="doneItems.length" class="eh-row eh-row--divided">
                                    <view class="eh-index eh-index--done">
                                        <u-icon name="checkmark" color="#19BE6B" size="24"></u-icon>
                                    </view>
                                    <view class="flex-1 min-w-0 flex flex-col gap-[6rpx]">
                                        <text class="text-sm font-semibold text-muted">
                                            已完成 {{ doneItems.length }} 项
                                        </text>
                                        <text class="text-[22rpx] leading-[32rpx] text-disabled line-clamp-1">
                                            {{ doneTitles }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>

                <view v-if="topItem" class="eh-foot">
                    <view class="eh-primary active:opacity-85" @click="emit('item', topItem.key)">
                        <text class="text-lg font-semibold text-white">
                            先补最有用的：{{ topItem.title }}
                        </text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import type { EnhanceItemEnum } from "@/ai_modules/person/enums";
import type { EnhanceItem } from "../../hooks/useEnhanceList";

const props = defineProps<{
    modelValue: boolean;
    percent: number;
    items: EnhanceItem[];
    remainCount: number;
    requiredDone: number;
    requiredTotal: number;
    topItem?: EnhanceItem;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "item", key: EnhanceItemEnum): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const ringBackground = computed(() => {
    const turn = Math.min(1, Math.max(0, props.percent / 100));
    return `conic-gradient(#0065FB 0turn ${turn}turn, #E2E2E2 ${turn}turn 1turn)`;
});

const undoneItems = computed(() => props.items.filter((item) => !item.done));
const doneItems = computed(() => props.items.filter((item) => item.done));
const doneTitles = computed(() => doneItems.value.map((item) => item.title).join(" · "));

const headerTip = computed(() =>
    props.remainCount
        ? "必须项已经齐了，下面这些不做也能跑，做了内容会更好。"
        : "该配的都配齐了，保持住就行。",
);
</script>

<style lang="scss" scoped>
.eh-handle {
    @apply w-[72rpx] h-[8rpx] rounded-[4rpx] bg-light mx-auto mt-[20rpx];
}

.eh-hd {
    @apply flex items-center gap-[28rpx] px-[40rpx] pt-[28rpx] pb-[8rpx];
}

.eh-ring {
    @apply w-[104rpx] h-[104rpx] rounded-full shrink-0 flex items-center justify-center;
}

.eh-ring-inner {
    @apply w-[80rpx] h-[80rpx] rounded-full bg-white flex items-center justify-center;
}

.eh-close {
    @apply relative w-[64rpx] h-[64rpx] rounded-full shrink-0 flex items-center justify-center bg-[#F2F3F5];

    &::after {
        content: "";
        @apply absolute inset-[-12rpx];
    }
}

// 滚动区 + 固定底部。popup-bottom 是 height="auto"，父级没有确定高度，
// 所以不用 grow min-h-0 那套（撑不出来会让底部按钮压住内容），
// 直接给 scroll-view 一个高度上限，底部操作区走正常流跟在后面
.eh-body {
    @apply w-full flex flex-col;
}

.eh-scroll {
    max-height: 52vh;
}

.eh-required {
    @apply flex items-center gap-[20rpx] rounded-[24rpx] px-[26rpx] py-[24rpx] bg-success-light-9;
}

.eh-required-badge {
    @apply w-[44rpx] h-[44rpx] rounded-full shrink-0 flex items-center justify-center bg-success;
}

.eh-row {
    @apply flex items-center gap-[22rpx] py-[24rpx];
}

// 单边边框先重置四边，否则小程序端会四条边都画出来
.eh-row--divided {
    @apply border-[0] border-t-[2rpx] border-solid border-[#F2F3F5];
}

.eh-index {
    @apply w-[48rpx] h-[48rpx] rounded-full shrink-0 flex items-center justify-center
        text-xs font-bold text-content bg-[#F2F3F5];
}

.eh-index--done {
    @apply bg-success-light-9;
}

.eh-action {
    @apply h-[64rpx] shrink-0 flex items-center px-[26rpx] rounded-[18rpx] bg-[#F2F3F5];
}

.eh-foot {
    @apply px-[40rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))];
}

.eh-primary {
    @apply h-[92rpx] rounded-[24rpx] bg-primary flex items-center justify-center px-[24rpx];
}
</style>
