<template>
    <view v-if="visible" class="bg-white rounded-[28rpx] px-[26rpx] py-[22rpx] mb-[22rpx] slice-card-shadow">
        <view class="flex items-center justify-between mb-[16rpx]">
            <view class="flex items-center gap-[12rpx] min-w-0">
                <view class="slice-spinner shrink-0"></view>
                <text class="text-xs font-semibold text-[#1D2129] line-clamp-1">视频切割中…</text>
            </view>
            <text class="text-[22rpx] font-semibold text-primary shrink-0 ml-[16rpx]">
                {{ progressText }}
            </text>
        </view>

        <view class="h-[12rpx] rounded-full overflow-hidden bg-[#EEF1F6]">
            <view class="slice-bar h-full rounded-full bg-primary" :style="{ width: percent }"></view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { isSliceTaskRunning, type SliceStatistics } from "../hooks/useMaterialsTab";

const props = defineProps<{
    statistics: SliceStatistics | null;
}>();

// 兜底，避免模板里反复判空
const stat = computed<SliceStatistics>(
    () =>
        props.statistics ?? {
            total_count: 0,
            pending_count: 0,
            slicing_count: 0,
            queue_count: 0,
            finished_count: 0,
            success_count: 0,
            failed_count: 0,
            total_slice_count: 0,
            success_slice_count: 0,
            item_count: 0,
            sliced_count: 0,
        },
);

// 无有效任务数据时不展示（父级已判 hasSlicingTask，这里再挡一层空统计）
const visible = computed(() => {
    if (!props.statistics) return false;
    return isSliceTaskRunning(stat.value);
});

// 总进度优先用片段数 success_slice_count / total_slice_count
const progressText = computed(() => {
    const { success_slice_count, total_slice_count, finished_count, total_count } = stat.value;
    if (total_slice_count > 0) return `${success_slice_count}/${total_slice_count}`;
    if (total_count > 0) return `${finished_count}/${total_count}`;
    return "0/0";
});

const percent = computed(() => {
    const { success_slice_count, total_slice_count, finished_count, total_count } = stat.value;
    if (total_slice_count > 0) {
        return Math.min(100, Math.max(2, Math.round((success_slice_count / total_slice_count) * 100))) + "%";
    }
    if (total_count > 0) {
        return Math.min(100, Math.max(2, Math.round((finished_count / total_count) * 100))) + "%";
    }
    return "2%";
});
</script>

<style scoped lang="scss">
.slice-card-shadow {
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}

.slice-bar {
    transition: width 0.3s ease;
}

.slice-spinner {
    width: 28rpx;
    height: 28rpx;
    border: 5rpx solid #c9dbfb;
    border-top-color: var(--color-primary, #0065fb);
    border-radius: 50%;
    animation: sliceSpin 0.8s linear infinite;
}

@keyframes sliceSpin {
    to {
        transform: rotate(360deg);
    }
}
</style>
