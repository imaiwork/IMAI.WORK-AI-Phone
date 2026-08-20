<template>
    <view class="mt-[28rpx] bg-white rounded-[36rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.04)] overflow-hidden">
        <view
            class="flex items-center px-[32rpx] py-[28rpx] active:bg-[#F8FAFC] transition-colors"
            @click="$emit('toggle')">
            <view class="flex items-center gap-[12rpx] min-w-0 flex-1">
                <view class="w-[16rpx] h-[16rpx] rounded-full flex-shrink-0" :style="{ backgroundColor: dotColor }" />
                <text class="text-[28rpx] font-bold text-[#1F2937] flex-shrink-0">{{ title }}</text>
                <text class="text-[24rpx] text-[#94A3B8] font-normal truncate">{{ timeRange }}</text>
                <view v-if="tasks.length > 0" class="px-[16rpx] py-[4rpx] rounded-full bg-[#F1F5F9] flex-shrink-0">
                    <text class="text-[22rpx] text-[#94A3B8] font-semibold">{{ tasks.length }}</text>
                </view>
            </view>
            <view
                class="w-[40rpx] h-[40rpx] ml-[16rpx] flex items-center justify-center transition-transform flex-shrink-0"
                :style="collapsed ? 'transform:rotate(-90deg);' : 'transform:rotate(0deg);'">
                <u-icon name="arrow-down" color="#94A3B8" size="24" />
            </view>
        </view>

        <view v-show="!collapsed" class="px-[28rpx] pb-[24rpx]">
            <view
                v-if="tasks.length === 0"
                class="flex items-center justify-center py-[28rpx] bg-[#FAFAFA] rounded-[28rpx] border border-dashed border-[#E5E7EB]">
                <text class="text-[24rpx] text-[#CBD5E1]">该时段暂无任务</text>
            </view>

            <view v-else class="flex flex-col gap-[16rpx]">
                <view
                    v-for="task in tasks"
                    :key="task.id"
                    class="rounded-[28rpx] px-[28rpx] py-[24rpx] border border-solid border-[#F0F2F7] flex flex-col gap-[20rpx] transition-colors"
                    :class="[
                        isTaskMuted(task) ? 'bg-[#FBFCFE]' : 'bg-white',
                        canOperateTask(task) ? 'active:bg-[#FAFBFD]' : '',
                        task.status === 0 && !isDefaultLocked(task) ? 'opacity-60' : '',
                    ]"
                    @click="canOperateTask(task) ? handleEdit(task) : undefined">
                    <view class="flex items-start gap-[20rpx]">
                        <view class="flex flex-col items-start w-[86rpx] flex-shrink-0 pt-[4rpx]">
                            <text
                                class="text-[28rpx] font-bold leading-none mb-[8rpx]"
                                :class="isTaskMuted(task) ? 'text-[#B6BECC]' : 'text-[#2563EB]'">
                                {{ task.time.split("-")[0] }}
                            </text>
                            <text class="text-[22rpx] text-[#94A3B8] leading-none">
                                {{ task.time.split("-")[1] }}
                            </text>
                        </view>

                        <view class="w-[2rpx] self-stretch min-h-[76rpx] bg-[#EDF0F5] flex-shrink-0" />

                        <view class="flex flex-col gap-[10rpx] flex-1 min-w-0 pr-[8rpx]">
                            <view class="flex items-center gap-[8rpx] flex-wrap">
                                <text
                                    class="text-[28rpx] font-bold leading-tight"
                                    :class="isTaskMuted(task) ? 'text-[#B6BECC]' : 'text-[#1F2937]'">
                                    {{ task.title }}
                                </text>
                                <view
                                    v-if="isDefaultLocked(task)"
                                    class="px-[10rpx] py-[4rpx] rounded bg-[#F3F4F6] border border-[#E5E7EB] flex items-center gap-[4rpx]">
                                    <u-icon name="lock-fill" color="#9CA3AF" size="18" />
                                    <text class="text-[18rpx] text-[#9CA3AF]">不可编辑</text>
                                </view>
                                <view
                                    v-if="isCrossAfternoon(task)"
                                    class="px-[10rpx] py-[4rpx] rounded bg-[#EFF6FF] border border-[#BFDBFE]">
                                    <text class="text-[18rpx] text-[#3B82F6]">跨午后</text>
                                </view>
                                <view
                                    v-if="isCrossEvening(task)"
                                    class="px-[10rpx] py-[4rpx] rounded bg-[#FAF5FF] border border-[#E9D5FF]">
                                    <text class="text-[18rpx] text-[#9333EA]">跨晚间</text>
                                </view>
                            </view>
                            <view class="flex items-center gap-[10rpx] min-w-0">
                                <view
                                    class="w-[12rpx] h-[12rpx] rounded-full flex-shrink-0"
                                    :style="{ backgroundColor: getPlatformDotColor(task.platform) }" />
                                <text class="text-[22rpx] text-[#64748B] truncate">{{ task.platform }}</text>
                            </view>
                        </view>

                        <view @click.stop>
                            <u-switch
                                v-if="canOperateTask(task)"
                                v-model="task.status"
                                class="flex-shrink-0 mt-[2rpx]"
                                :active-value="1"
                                :inactive-value="0"
                                :size="40"
                                @change="handleToggleStatus(task)" />
                        </view>
                    </view>

                    <view class="pl-[108rpx] flex items-center gap-[14rpx]" @click.stop>
                        <view
                            v-if="isDefaultLocked(task) || task.status === 1"
                            class="h-[64rpx] flex-1 min-w-0 flex items-center justify-center gap-[8rpx] px-[24rpx] rounded-full bg-[#EFF4FF] border border-solid border-[#D5E2FF] active:opacity-70"
                            @click.stop="handleDemo(task)">
                            <u-icon name="play-right-fill" color="#2563EB" size="22" />
                            <text class="text-[24rpx] text-[#2563EB] font-bold whitespace-nowrap">立即演示</text>
                        </view>
                        <view
                            v-else
                            class="h-[64rpx] flex-1 min-w-0 flex items-center justify-center rounded-full bg-[#F4F6FB]">
                            <text class="text-[24rpx] text-[#94A3B8] whitespace-nowrap">已暂停</text>
                        </view>
                        <view
                            v-if="canOperateTask(task)"
                            class="w-[64rpx] h-[64rpx] flex items-center justify-center rounded-full bg-[#FFF1F2] border border-solid border-[#FFE4E6] active:opacity-70 flex-shrink-0"
                            @click.stop="handleDelete(task.id)">
                            <u-icon name="trash" color="#E11D48" size="30" />
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import type { TaskItem } from "../stores/taskStore";

const props = defineProps<{
    title: string;
    timeRange: string;
    dotColor: string;
    tasks: TaskItem[];
    isEditable: boolean;
    collapsed: boolean;
}>();

const emit = defineEmits<{
    (e: "delete", id: number): void;
    (e: "edit", task: TaskItem): void;
    (e: "demo", task: TaskItem): void;
    (e: "toggle-status", id: number, newStatus: number): void;
    (e: "toggle"): void;
}>();

const platformDotColors = [
    { keyword: "小红书", color: "#FF2442" },
    { keyword: "抖音", color: "#111827" },
    { keyword: "快手", color: "#FF6E2E" },
    { keyword: "视频号", color: "#07C160" },
    { keyword: "微信", color: "#07C160" },
];

const getPlatformDotColor = (platform: string): string => {
    return platformDotColors.find((item) => platform.includes(item.keyword))?.color ?? "#CBD5E1";
};

/** 后端默认任务节点：不可编辑 / 删除 / 启停，仍可立即演示 */
const isDefaultLocked = (task: TaskItem): boolean => Number(task.is_default) === 1;

const isTaskMuted = (task: TaskItem): boolean => task.status === 0 || isDefaultLocked(task);

const canOperateTask = (task: TaskItem): boolean => props.isEditable && !isDefaultLocked(task);

// ─── 操作处理 ─────────────────────────────────────────────────
const handleEdit = (task: TaskItem) => {
    if (!canOperateTask(task)) return;
    emit("edit", task);
};

const handleDelete = (id: number) => {
    const task = props.tasks.find((item) => item.id === id);
    if (task && isDefaultLocked(task)) return;
    emit("delete", id);
};

const handleDemo = (task: TaskItem) => {
    emit("demo", task);
};

const handleToggleStatus = (task: TaskItem) => {
    if (!canOperateTask(task)) return;
    const newStatus = task.status === 1 ? 0 : 1;
    emit("toggle-status", task.id, newStatus);
};

// ─── 跨时段标签 ───────────────────────────────────────────────
const timeToMins = (t: string): number => {
    const [h, m] = t.split(":");
    return parseInt(h) * 60 + parseInt(m);
};
const isCrossAfternoon = (task: TaskItem): boolean => {
    const [s, e] = task.time.split("-");
    return timeToMins(s) < 720 && timeToMins(e) > 720;
};
const isCrossEvening = (task: TaskItem): boolean => {
    const [s, e] = task.time.split("-");
    return timeToMins(s) < 1080 && timeToMins(e) > 1080;
};
</script>
