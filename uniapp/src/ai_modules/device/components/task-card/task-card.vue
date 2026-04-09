<template>
    <view
        class="relative flex items-stretch bg-white rounded-[20rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)] mb-[20rpx]"
        @click="handleClick">
        <view class="w-[8rpx] flex-shrink-0" :class="cardStyle.bar" />

        <view class="flex items-center justify-center px-[24rpx] py-[28rpx]">
            <view class="w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center" :class="cardStyle.iconBg">
                <u-icon
                    :name="cardStyle.icon"
                    :color="cardStyle.iconColor"
                    size="44"
                    :class="item.status === 1 ? 'animate-spin' : ''" />
            </view>
        </view>

        <view class="flex-1 min-w-0 py-[24rpx] pr-[24rpx]">
            <view class="flex items-center justify-between mb-[8rpx]">
                <text class="text-[24rpx] text-[#9CA3AF]">{{ item.start_time }} - {{ item.end_time }}</text>
                <view class="rounded-full px-[18rpx] py-[6rpx]" :class="cardStyle.tagBg">
                    <text class="text-xs font-semibold" :class="cardStyle.tagText">{{ statusText }}</text>
                </view>
            </view>

            <view class="flex items-center gap-[10rpx] mb-[8rpx]">
                <text class="text-[30rpx] font-bold text-[#111827] truncate flex-1">{{ item.name }}</text>
                <view
                    class="flex-shrink-0 w-[44rpx] h-[44rpx] flex items-center justify-center"
                    @click.stop="handleEditName(item)">
                    <u-icon name="edit-pen" color="#9CA3AF" size="28" />
                </view>
            </view>

            <text class="text-[24rpx] text-[#9CA3AF]">{{ item.task_category }}</text>

            <view v-if="[3, 4].includes(item.status)" class="flex items-center gap-[8rpx] mt-[12rpx]">
                <u-icon name="info-circle" :color="cardStyle.iconColor" size="24" />
                <text class="text-xs break-all" :class="cardStyle.tagText">
                    失败原因：{{ item.remark || "任务执行超时" }}
                </text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{ item: any }>();
const emit = defineEmits<{
    (e: "click"): void;
    (e: "edit-name", item: any): void;
}>();

const statusText = computed(() => {
    const map: Record<number, string> = {
        0: "待开始",
        1: "执行中",
        2: "已完成",
        3: "执行失败",
        4: "已中断",
    };
    return map[props.item.status] ?? "-";
});

const cardStyle = computed(() => {
    const s = props.item.status;

    // 执行中
    if (s === 1)
        return {
            bar: "bg-primary",
            iconBg: "bg-[#EBF2FF]",
            icon: "reload",
            iconColor: "#0065fb",
            tagBg: "bg-[#EBF2FF]",
            tagText: "text-primary",
        };

    // 已完成
    if (s === 2)
        return {
            bar: "bg-[#00C48C]",
            iconBg: "bg-[#E6FAF5]",
            icon: "checkmark-circle-fill",
            iconColor: "#00A376",
            tagBg: "bg-[#E6FAF5]",
            tagText: "text-[#00A376]",
        };

    // 失败 / 中断
    if (s === 3 || s === 4)
        return {
            bar: "bg-[#E8002D]",
            iconBg: "bg-[#FFF0F2]",
            icon: "warning",
            iconColor: "#E8002D",
            tagBg: "bg-[#FFF0F2]",
            tagText: "text-[#E8002D]",
        };

    // 待开始（默认）
    return {
        bar: "bg-[#D1D5DB]",
        iconBg: "bg-[#F3F4F6]",
        icon: "clock",
        iconColor: "#9CA3AF",
        tagBg: "bg-[#F3F4F6]",
        tagText: "text-[#9CA3AF]",
    };
});

const handleEditName = (item: any) => emit("edit-name", item);
const handleClick = () => emit("click");
</script>
