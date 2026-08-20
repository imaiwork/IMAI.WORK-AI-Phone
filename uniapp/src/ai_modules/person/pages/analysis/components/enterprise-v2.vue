<template>
    <view class="pb-6">
        <view class="bg-white py-10 px-5 text-center relative overflow-hidden rounded-b-3xl shadow-sm">
            <view
                class="absolute top-0 left-0 w-full h-[6rpx]"
                style="background: linear-gradient(90deg, #1d4ed8 0%, #1e293b 100%)"></view>
            <view class="text-[44rpx] font-extrabold text-[#1E293B] mb-4 leading-tight">
                <text>专为 </text>
                <text class="text-[#1D4ED8]">{{ reportData.ipName }}</text>
                <text>{{ "\n" }}定制的自动化获客系统</text>
            </view>
            <view
                class="bg-[#EFF6FF] inline-block px-4 py-3 rounded-2xl border border-solid border-[#BFDBFE] text-left">
                <text class="text-[#4B5563] leading-relaxed">
                    📈 <text class="font-bold text-[#1E40AF]">{{ reportData.heroTag }}：</text>{{ "\n"
                    }}{{ reportData.heroDescription }}
                </text>
            </view>
        </view>

        <view class="mt-6 space-y-6">
            <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F1F5F9]">
                <view class="flex items-center gap-2 mb-5 pb-3 border-[0] border-b border-solid border-[#F1F5F9]">
                    <view class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center">
                        <text class="text-[#1D4ED8]">⚙️</text>
                    </view>
                    <text class="text-[32rpx] font-bold text-[#1E293B]">{{ reportData.logicTitle }}</text>
                </view>

                <view class="space-y-6">
                    <view class="flex gap-4 items-start">
                        <view
                            class="w-10 h-10 bg-[#EFF6FF] rounded-full flex items-center justify-center shrink-0 mt-1">
                            <text>🔬</text>
                        </view>
                        <view class="flex-1">
                            <text class="text-[28rpx] font-bold text-[#1E293B] block mb-1">{{
                                reportData.strategy1Title
                            }}</text>
                            <text class="text-[#64748B] text-xs leading-relaxed">
                                {{ reportData.strategy1Desc }}
                            </text>
                        </view>
                    </view>
                    <view class="flex gap-4 items-start">
                        <view
                            class="w-10 h-10 bg-[#F1F5F9] rounded-full flex items-center justify-center shrink-0 mt-1">
                            <text>🔄</text>
                        </view>
                        <view class="flex-1">
                            <text class="text-[28rpx] font-bold text-[#1E293B] block mb-1">{{
                                reportData.strategy2Title
                            }}</text>
                            <text class="text-[#64748B] text-xs leading-relaxed">
                                {{ reportData.strategy2Desc }}
                            </text>
                        </view>
                    </view>
                </view>
            </view>

            <view
                class="rounded-2xl p-5 border border-solid border-[#BFDBFE]"
                style="background: rgba(239, 246, 255, 0.7)">
                <view class="flex items-center gap-2 mb-5">
                    <view class="w-7 h-7 rounded-lg bg-[#DBEAFE] flex items-center justify-center">
                        <text class="text-[#1D4ED8]">🎯</text>
                    </view>
                    <text class="text-[32rpx] font-bold text-[#1E3A8A]">{{ reportData.targetAudienceTitle }}</text>
                </view>

                <view class="bg-white p-4 rounded-xl shadow-sm border border-solid border-[#F1F5F9] mb-4">
                    <view class="space-y-4">
                        <view class="pb-4 border-[0] border-b border-solid border-[#F8FAFC]">
                            <view class="inline-block bg-[#F0FDF4] px-3 py-1 rounded mb-2">
                                <text class="text-[22rpx] font-bold text-[#166534]">{{
                                    reportData.platform1Name
                                }}</text>
                            </view>
                            <text class="text-[#64748B] text-xs leading-relaxed block">
                                {{ reportData.platform1Desc }}
                            </text>
                        </view>
                        <view class="pb-4 border-[0] border-b border-solid border-[#F8FAFC]">
                            <view class="inline-block bg-[#F1F5F9] px-3 py-1 rounded mb-2">
                                <text class="text-[22rpx] font-bold text-[#334155]">{{
                                    reportData.platform2Name
                                }}</text>
                            </view>
                            <text class="text-[#64748B] text-xs leading-relaxed block">
                                {{ reportData.platform2Desc }}
                            </text>
                        </view>
                        <view>
                            <view class="inline-block bg-[#EFF6FF] px-3 py-1 rounded mb-2">
                                <text class="text-[22rpx] font-bold text-[#1E40AF]">{{
                                    reportData.platform3Name
                                }}</text>
                            </view>
                            <text class="text-[#64748B] text-xs leading-relaxed block">
                                {{ reportData.platform3Desc }}
                            </text>
                        </view>
                    </view>
                </view>

                <view
                    class="p-4 rounded-xl shadow-sm"
                    style="background: linear-gradient(90deg, #1d4ed8 0%, #1e293b 100%)">
                    <view class="flex items-center gap-2 mb-2">
                        <text class="text-white font-bold">🤖 {{ reportData.autoHookTitle }}</text>
                    </view>
                    <text class="text-[#BFDBFE] text-xs leading-relaxed">
                        {{ reportData.autoHookDesc }}
                    </text>
                </view>
            </view>

            <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F1F5F9]">
                <view class="flex items-center gap-2 mb-6">
                    <view class="w-7 h-7 rounded-lg bg-[#F1F5F9] flex items-center justify-center">
                        <text class="text-[#475569]">📊</text>
                    </view>
                    <text class="text-[32rpx] font-bold text-[#1E293B]">{{ reportData.funnelTitle }}</text>
                </view>

                <view class="space-y-3 relative">
                    <view class="absolute left-[38rpx] top-[80rpx] bottom-[80rpx] w-[2rpx] bg-[#F1F5F9] z-0"></view>

                    <view
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <view class="w-10 h-10 bg-[#EFF6FF] rounded-full flex items-center justify-center shrink-0">
                            <text class="text-[#1D4ED8] font-bold text-[32rpx]">1</text>
                        </view>
                        <view class="flex-1">
                            <text class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel1Title }}</text>
                            <text class="text-[#94A3B8] text-[22rpx]">{{ reportData.funnel1Desc }}</text>
                        </view>
                    </view>

                    <view
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <view class="w-10 h-10 bg-[#F1F5F9] rounded-full flex items-center justify-center shrink-0">
                            <text class="text-[#475569] font-bold text-[32rpx]">2</text>
                        </view>
                        <view class="flex-1">
                            <text class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel2Title }}</text>
                            <text class="text-[#94A3B8] text-[22rpx]">{{ reportData.funnel2Desc }}</text>
                        </view>
                    </view>

                    <view
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <view class="w-10 h-10 bg-[#EEF2FF] rounded-full flex items-center justify-center shrink-0">
                            <text class="text-[#4338CA] font-bold text-[32rpx]">3</text>
                        </view>
                        <view class="flex-1">
                            <text class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel3Title }}</text>
                            <text class="text-[#94A3B8] text-[22rpx]">{{ reportData.funnel3Desc }}</text>
                        </view>
                    </view>

                    <view
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <view class="w-10 h-10 bg-[#FFFBEB] rounded-full flex items-center justify-center shrink-0">
                            <text class="text-[#D97706] font-bold text-[32rpx]">4</text>
                        </view>
                        <view class="flex-1">
                            <text class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel4Title }}</text>
                            <text class="text-[#94A3B8] text-[22rpx]">{{ reportData.funnel4Desc }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
                <view class="flex items-center justify-between mb-4">
                    <view class="flex items-center">
                        <view class="w-7 h-7 rounded-lg bg-[#ECFDF5] flex items-center justify-center mr-3">
                            <text class="text-[#059669]">⏰</text>
                        </view>
                        <text class="text-sm font-bold text-[#1F2937]">AI 24小时任务日历</text>
                    </view>
                    <view
                        class="bg-[#FEF2F2] text-[#DC2626] text-[20rpx] font-bold px-2 py-1 rounded border border-[#FECACA] animate-pulse"
                        >自动执行 {{ totalTaskCount }} 个任务</view
                    >
                </view>
                <view class="text-xs text-[#6B7280] mb-4"
                    >下方为系统全自动执行的一整天任务安排，每个任务都有明确的<text class="font-bold text-[#374151]"
                        >执行原理</text
                    >和<text class="font-bold text-[#374151]">效果预期</text>。</view
                >

                <view class="flex overflow-x-auto pb-2 mb-4 space-x-2">
                    <view
                        v-for="taskType in availableTaskTypes"
                        :key="taskType.key"
                        @click="switchTaskType(taskType.key)"
                        :class="[
                            'shrink-0 px-3 py-2 rounded-xl text-[22rpx] font-bold transition-all cursor-pointer',
                            currentTaskType === taskType.key
                                ? 'text-white shadow-md'
                                : 'bg-[#F9FAFB] text-[#6B7280] border border-[#F3F4F6]',
                        ]"
                        :style="
                            currentTaskType === taskType.key
                                ? 'background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%)'
                                : ''
                        "
                        >{{ taskType.name }}</view
                    >
                </view>

                <view v-if="currentTasks.length === 0" class="py-10 text-center text-[#94A3B8] text-sm">
                    该时段暂无任务安排
                </view>

                <view class="space-y-4">
                    <view
                        v-for="(task, index) in currentTasks"
                        :key="index"
                        class="bg-[#F9FAFB] rounded-xl border border-[#F3F4F6] overflow-hidden">
                        <view
                            class="px-4 py-2.5 flex justify-between items-center"
                            style="background: linear-gradient(to right, #10b981 0%, #3b82f6 100%)">
                            <view class="text-white font-bold text-xs">{{ task.time }}</view>
                            <view
                                class="bg-[#ffffff]/20 text-white text-[20rpx] font-bold px-2 py-0.5 rounded backdrop-blur-sm"
                                >{{ task.platform }}</view
                            >
                        </view>
                        <view class="p-4">
                            <view class="flex items-center mb-3">
                                <view
                                    class="w-6 h-6 rounded-full bg-white flex items-center justify-center mr-2 shadow-sm">
                                    <text class="text-[#059669]">{{ getTaskIcon(task.title) }}</text>
                                </view>
                                <view class="text-sm font-bold text-[#1F2937]">{{ task.title }}</view>
                            </view>
                            <view class="space-y-2.5">
                                <view class="flex items-start">
                                    <view class="w-14 shrink-0 pt-0.5">
                                        <text
                                            class="text-[20rpx] font-bold text-[#2563EB] bg-[#EFF6FF] px-1.5 py-0.5 rounded"
                                            >为什么</text
                                        >
                                    </view>
                                    <view class="text-[22rpx] text-[#4B5563] leading-tight">{{ task.why }}</view>
                                </view>
                                <view class="flex items-start">
                                    <view class="w-14 shrink-0 pt-0.5">
                                        <text
                                            class="text-[20rpx] font-bold text-[#059669] bg-[#ECFDF5] px-1.5 py-0.5 rounded"
                                            >怎么做</text
                                        >
                                    </view>
                                    <view class="text-[22rpx] text-[#4B5563] leading-tight">{{ task.do }}</view>
                                </view>
                                <view class="flex items-start">
                                    <view class="w-14 shrink-0 pt-0.5">
                                        <text
                                            class="text-[20rpx] font-bold text-[#7C3AED] bg-[#F3E8FF] px-1.5 py-0.5 rounded"
                                            >效果</text
                                        >
                                    </view>
                                    <view class="text-[22rpx] text-[#374151] font-medium leading-tight">{{
                                        task.effect
                                    }}</view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view
                class="rounded-2xl p-6 text-white shadow-xl relative overflow-hidden"
                style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%)">
                <view
                    class="absolute top-[-80rpx] left-[-80rpx] w-[300rpx] h-[300rpx] rounded-full opacity-40"
                    style="background: radial-gradient(circle, #1d4ed8 0%, transparent 70%)"></view>
                <view
                    class="absolute bottom-[-80rpx] right-[-80rpx] w-[300rpx] h-[300rpx] rounded-full opacity-30"
                    style="background: radial-gradient(circle, #475569 0%, transparent 70%)"></view>

                <view class="relative z-10">
                    <text class="text-[36rpx] font-bold text-white block text-center mb-3">{{
                        reportData.visionTitle
                    }}</text>
                    <text class="text-[#CBD5E1] text-xs leading-relaxed block mb-6">
                        {{ reportData.visionIntro }}
                    </text>

                    <view class="space-y-3 mb-8">
                        <view
                            class="flex justify-between items-center px-4 py-3 rounded-xl border border-solid border-[#ffffff1a]"
                            style="background: rgba(255, 255, 255, 0.08)">
                            <text class="text-[22rpx] text-[#93C5FD]">阶段一</text>
                            <text class="font-bold text-white">{{ reportData.phase1 }}</text>
                        </view>
                        <view
                            class="flex justify-between items-center px-4 py-3 rounded-xl border border-solid border-[#ffffff1a]"
                            style="background: rgba(255, 255, 255, 0.08)">
                            <text class="text-[22rpx] text-[#93C5FD]">阶段二</text>
                            <text class="font-bold text-white">{{ reportData.phase2 }}</text>
                        </view>
                        <view
                            class="flex justify-between items-center px-4 py-3 rounded-xl border border-solid border-[#ffffff1a]"
                            style="background: rgba(255, 255, 255, 0.08)">
                            <text class="text-[22rpx] text-[#93C5FD]">阶段三</text>
                            <text class="font-bold text-[#FCD34D]">{{ reportData.phase3 }}</text>
                        </view>
                    </view>

                    <view class="border-[0] border-t border-solid border-[#ffffff1a] pt-5">
                        <text class="text-[28rpx] font-medium text-white leading-relaxed block mb-3">
                            {{ reportData.conclusionHighlight }}
                        </text>
                        <text class="text-[#CBD5E1] text-xs leading-relaxed">
                            {{ reportData.conclusionAction }}
                        </text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useReportTask, TaskType, type TemplateDataItem } from "./useReportTask";

const props = defineProps<{
    reportData: any;
    templateData: TemplateDataItem[];
}>();

const { currentTaskType, availableTaskTypes, totalTaskCount, currentTasks, switchTaskType, getTaskIcon } =
    useReportTask(toRef(props, "templateData"), {
        taskTypeNames: {
            [TaskType.MORNING]: "早间时段",
            [TaskType.MORNING_GROWTH]: "上午时段",
            [TaskType.AFTERNOON_PEAK]: "午间时段",
            [TaskType.AFTERNOON_REST]: "下午时段",
            [TaskType.EVENING_PEAK]: "晚间时段",
        },
        extraIconMap: {
            私信接管: "🔥",
            "私信接管（拉群）": "🔥",
            评论接管: "👍",
            视频号自动获客: "🎥",
            视频号获客: "🎥",
            同城触达: "📍",
        },
        extraSceneDescMap: {
            4: {
                why: "借助视频号公域流量获取客资",
                do: "搜索行业关键词进入目标主页，通过OCR提取微信号存入线索库",
                effect: "打通公私域，建立专属企业客资库",
            },
        },
    });
</script>

<style scoped></style>
