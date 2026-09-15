<template>
    <view class="clue-card">
        <view class="mt-1">
            <image
                src="@/ai_modules/device/static/icons/upload.svg"
                class="w-[52rpx] h-[52rpx]"
                v-if="type === 1"></image>
            <image
                src="@/ai_modules/device/static/icons/task_list.svg"
                class="w-[52rpx] h-[52rpx]"
                v-if="type === 2"></image>
        </view>
        <view class="">
            <view class="font-medium text-[30rpx] break-all"> {{ data.name }} </view>
            <view class="text-xs text-[#0000004d] flex items-center gap-x-1 mt-1">
                <view>{{ type === 1 ? formatFileSize(data.size || 0) : data.time }}</view>
                <view class="w-[2rpx] h-[22rpx] bg-[#CCCCCC] mx-[10rpx]"></view>
                <view>{{ sourceLabel }}</view>
            </view>
            <view class="mt-[28rpx] grid grid-cols-3 gap-x-2" v-if="type == 2">
                <view class="text-xs flex flex-col items-center justify-center">
                    <text>{{ data.total }}</text>
                    <text class="text-[#0000004d]">线索总数</text>
                </view>
                <view class="text-xs flex flex-col items-center justify-center">
                    <text>{{ data.added }}</text>
                    <text class="text-[#0000004d]">已添加</text>
                </view>
                <view class="text-xs flex flex-col items-center justify-center">
                    <text>{{ data.total - data.added }}</text>
                    <text class="text-[#0000004d]">剩余</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatFileSize } from "@/utils/util";
import { CLUE_SOURCE_TEXT, ClueSourceEnum } from "@/ai_modules/device/enums";
const props = withDefaults(
    defineProps<{
        data: {
            name: string;
            size?: number;
            time?: string;
            total: number;
            added: number;
        };
        type: 1 | 2;
        // 线索来源，用于区分视频号获客线索与抖音获客线索；不传按 type 兜底
        source?: ClueSourceEnum;
    }>(),
    {
        data: () => ({
            name: "",
            size: 0,
            time: "",
            total: 0,
            added: 0,
        }),
        type: 1,
    },
);

// 来源没传时按 type 兜底，保持老调用方（只传 type）的显示不变
const sourceLabel = computed(() => {
    const source = props.source ?? (props.type === 1 ? ClueSourceEnum.FILE : ClueSourceEnum.AUTO_CLUE);
    return CLUE_SOURCE_TEXT[source] ?? CLUE_SOURCE_TEXT[ClueSourceEnum.AUTO_CLUE];
});
</script>

<style scoped lang="scss">
.clue-card {
    @apply rounded-[20rpx] bg-white px-5 py-[36rpx] relative flex gap-x-3;
}
</style>
