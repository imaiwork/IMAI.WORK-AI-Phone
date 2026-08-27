<template>
    <view
        class="bg-white rounded-[24rpx] p-[24rpx] active:opacity-90"
        style="box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08)"
        @click="emit('detail')">
        <view class="flex items-start gap-[16rpx]">
            <view
                class="inline-flex items-center gap-[6rpx] px-[12rpx] py-[4rpx] rounded-[8rpx] flex-shrink-0 mt-[4rpx]"
                :style="`background:${platformActiveBg(task.platform)}`">
                <image :src="platformWhiteIcon(task.platform)" mode="aspectFit" class="w-[20rpx] h-[20rpx]" />
                <text class="text-[20rpx] font-semibold text-white">{{ platformLabel(task.platform) }}</text>
            </view>
            <text class="flex-1 min-w-0 text-[30rpx] font-bold text-[#111827] leading-snug line-clamp-2">
                {{ task.title || task.topic }}
            </text>
        </view>
        <text class="block text-[22rpx] text-[#9CA3AF] mt-[10rpx] line-clamp-1">来自热点 · {{ task.topic }}</text>

        <view class="flex items-center gap-[10rpx] mt-[16rpx] flex-wrap">
            <text
                v-if="goalText"
                class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#EFF6FF] text-primary text-[20rpx] font-medium">
                {{ goalText }}
            </text>
            <text
                v-if="videoTypeText"
                class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#F5F3FF] text-[#7C3AED] text-[20rpx]">
                {{ videoTypeText }}
            </text>
            <text
                v-if="task.options?.duration_sec"
                class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                {{ task.options.duration_sec }}秒
            </text>
            <text
                v-if="fitScore !== null"
                class="px-[14rpx] py-[4rpx] rounded-[8rpx] text-[20rpx] font-medium"
                :class="fitScoreClass">
                契合 {{ fitScore }}
            </text>
        </view>

        <view class="flex items-center gap-[16rpx] mt-[18rpx]">
            <text
                class="px-[14rpx] py-[4rpx] rounded-[8rpx] text-[20rpx] font-medium"
                :class="HOTSPOT_STATUS_CLASS[task.status] || 'bg-[#F3F4F6] text-[#6B7280]'">
                {{ HOTSPOT_STATUS_TEXT[task.status] || task.status }}
            </text>
            <text class="text-[22rpx] text-[#9CA3AF]">{{ task.created_at || "" }}</text>
        </view>
        <text v-if="task.status === 'fail' && task.error" class="block text-[22rpx] text-[#EF4444] mt-[10rpx]">
            提示：{{ task.error }}
        </text>

        <view class="flex items-center gap-[16rpx] mt-[20rpx]">
            <view
                v-if="task.status === 'done'"
                class="flex-1 py-[18rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center active:opacity-80"
                @click.stop="emit('detail')">
                <text class="text-sm text-[#374151] font-medium">查看详情</text>
            </view>
            <view
                v-if="task.status === 'done'"
                class="flex-1 py-[18rpx] rounded-[16rpx] bg-primary flex items-center justify-center active:opacity-80"
                @click.stop="emit('publish')">
                <text class="text-sm text-white font-semibold">一键发布</text>
            </view>
            <view
                v-else-if="task.status === 'fail'"
                class="flex-1 py-[18rpx] rounded-[16rpx] bg-[#FEF2F2] flex items-center justify-center active:opacity-80"
                @click.stop="emit('detail')">
                <text class="text-sm text-[#EF4444] font-medium">查看并重试</text>
            </view>
            <view
                v-else-if="task.status !== 'done'"
                class="flex-1 py-[18rpx] rounded-[16rpx] bg-[#EFF6FF] flex items-center justify-center gap-[10rpx]"
                @click.stop="emit('detail')">
                <u-loading mode="circle" size="26" color="#0065fb"></u-loading>
                <text class="text-sm text-primary font-medium">生成中…</text>
            </view>
            <view
                class="w-[88rpx] py-[18rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center active:opacity-80"
                @click.stop="emit('remove')">
                <u-icon name="trash" color="#6B7280" size="30"></u-icon>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import {
    HOTSPOT_STATUS_CLASS,
    HOTSPOT_STATUS_TEXT,
    platformActiveBg,
    platformLabel,
    platformWhiteIcon,
} from "@/ai_modules/hotspot/enums";

const props = defineProps<{
    task: Record<string, any>;
    goalLabelMap: Record<string, string>;
    videoTypeLabelMap: Record<string, string>;
}>();

const emit = defineEmits<{
    (e: "detail"): void;
    (e: "publish"): void;
    (e: "remove"): void;
}>();

const goalText = computed(() => {
    const goal = props.task.options?.goal;
    return goal ? props.goalLabelMap[goal] || goal : "";
});

const videoTypeText = computed(() => {
    const vt = props.task.options?.video_type;
    if (!vt) return "";
    const label = props.videoTypeLabelMap[vt] || vt;
    const avatar = props.task.options?.avatar;
    return vt === "digital" && avatar ? `${label} · ${avatar}` : label;
});

const fitScore = computed(() => {
    const score = props.task.analysis?.fit_score;
    return typeof score === "number" ? score : null;
});

const fitScoreClass = computed(() => {
    const score = fitScore.value ?? 0;
    if (score >= 70) return "bg-[#F0FDF4] text-[#16A34A]";
    if (score >= 40) return "bg-[#FFFBEB] text-[#D97706]";
    return "bg-[#FEF2F2] text-[#EF4444]";
});
</script>
