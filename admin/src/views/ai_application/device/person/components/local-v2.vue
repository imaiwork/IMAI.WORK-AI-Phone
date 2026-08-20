<template>
    <div class="bg-[#F8FAFC] pb-6">
        <div class="bg-white py-10 px-5 text-center relative overflow-hidden rounded-b-3xl shadow-sm">
            <div
                class="absolute top-0 left-0 w-full h-[6rpx]"
                style="background: linear-gradient(90deg, #22d3ee 0%, #3b82f6 100%)"></div>
            <div class="text-[22px] font-extrabold text-[#1E293B] mb-4 leading-tight">
                <span>专为 </span>
                <span class="text-[#2563EB]">{{ reportData.ipName }}</span>
                <span>{{ "\n" }}定制的同城获客系统</span>
            </div>
            <div class="bg-[#ECFEFF] inline-block px-4 py-3 rounded-2xl border border-solid border-[#CFFAFE] text-left">
                <span class="text-[#4B5563] leading-relaxed">
                    🏂 <span class="font-bold text-[#0E7490]">{{ reportData.heroTag }}：</span>{{ "\n"
                    }}{{ reportData.heroDescription }}
                </span>
            </div>
        </div>

        <div class="px-4 mt-6 space-y-6">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F1F5F9]">
                <div class="flex items-center gap-2 mb-5 pb-3 border-[0] border-b border-solid border-[#F1F5F9]">
                    <div class="w-7 h-7 rounded-lg bg-[#ECFEFF] flex items-center justify-center">
                        <span class="text-[#0E7490] text-[16px]">⚓</span>
                    </div>
                    <span class="text-[16px] font-bold text-[#1E293B]">{{ reportData.logicTitle }}</span>
                </div>

                <div class="space-y-6">
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 bg-[#ECFEFF] rounded-full flex items-center justify-center shrink-0 mt-1">
                            <span>🛡️</span>
                        </div>
                        <div class="flex-1">
                            <span class="text-[14px] font-bold text-[#1E293B] block mb-1">{{
                                reportData.strategy1Title
                            }}</span>
                            <span class="text-[#64748B] text-xs leading-relaxed">
                                {{ reportData.strategy1Desc }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 bg-[#EFF6FF] rounded-full flex items-center justify-center shrink-0 mt-1">
                            <span>📍</span>
                        </div>
                        <div class="flex-1">
                            <span class="text-[14px] font-bold text-[#1E293B] block mb-1">{{
                                reportData.strategy2Title
                            }}</span>
                            <span class="text-[#64748B] text-xs leading-relaxed">
                                {{ reportData.strategy2Desc }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-[#ECFEFF] rounded-2xl p-5 border border-solid border-[#CFFAFE]"
                style="background: rgba(236, 254, 255, 0.7)">
                <div class="flex items-center gap-2 mb-5">
                    <div class="w-7 h-7 rounded-lg bg-[#CFFAFE] flex items-center justify-center">
                        <span class="text-[#0E7490] text-[16px]">⚡</span>
                    </div>
                    <span class="text-[16px] font-bold text-[#164E63]">{{ reportData.targetAudienceTitle }}</span>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-sm border border-solid border-[#F1F5F9] mb-4">
                    <div class="space-y-4">
                        <div class="pb-4 border-[0] border-b border-solid border-[#F8FAFC]">
                            <div class="inline-block bg-[#F1F5F9] px-3 py-1 rounded mb-2">
                                <span class="text-[11px] font-bold text-[#334155]">{{ reportData.platform1Name }}</span>
                            </div>
                            <span class="text-[#64748B] text-xs leading-relaxed block">
                                {{ reportData.platform1Desc }}
                            </span>
                        </div>
                        <div class="pb-4 border-[0] border-b border-solid border-[#F8FAFC]">
                            <div class="inline-block bg-[#FEF2F2] px-3 py-1 rounded mb-2">
                                <span class="text-[11px] font-bold text-[#DC2626]">{{ reportData.platform2Name }}</span>
                            </div>
                            <span class="text-[#64748B] text-xs leading-relaxed block">
                                {{ reportData.platform2Desc }}
                            </span>
                        </div>
                        <div>
                            <div class="inline-block bg-[#F0FDF4] px-3 py-1 rounded mb-2">
                                <span class="text-[11px] font-bold text-[#16A34A]">{{ reportData.platform3Name }}</span>
                            </div>
                            <span class="text-[#64748B] text-xs leading-relaxed block">
                                {{ reportData.platform3Desc }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="p-4 rounded-xl shadow-sm"
                    style="background: linear-gradient(90deg, #06b6d4 0%, #3b82f6 100%)">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-white font-bold">🤖 {{ reportData.autoHookTitle }}</span>
                    </div>
                    <span class="text-[#ECFEFF] text-xs leading-relaxed">
                        {{ reportData.autoHookDesc }}
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F1F5F9]">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center">
                        <span class="text-[#2563EB]">📈</span>
                    </div>
                    <span class="text-[16px] font-bold text-[#1E293B]">{{ reportData.funnelTitle }}</span>
                </div>

                <div class="space-y-3 relative">
                    <div class="absolute left-[19px] top-[40px] bottom-[40px] w-[1px] bg-[#F1F5F9] z-0"></div>

                    <div
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <div class="w-10 h-10 bg-[#ECFEFF] rounded-full flex items-center justify-center shrink-0">
                            <span class="text-[#0E7490] font-bold text-[16px]">1</span>
                        </div>
                        <div class="flex-1">
                            <span class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel1Title }}</span>
                            <span class="text-[#94A3B8] text-xs">{{ reportData.funnel1Desc }}</span>
                        </div>
                    </div>

                    <div
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <div class="w-10 h-10 bg-[#EFF6FF] rounded-full flex items-center justify-center shrink-0">
                            <span class="text-[#2563EB] font-bold text-[16px]">2</span>
                        </div>
                        <div class="flex-1">
                            <span class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel2Title }}</span>
                            <span class="text-[#94A3B8] text-xs">{{ reportData.funnel2Desc }}</span>
                        </div>
                    </div>

                    <div
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <div class="w-10 h-10 bg-[#EEF2FF] rounded-full flex items-center justify-center shrink-0">
                            <span class="text-[#4F46E5] font-bold text-[16px]">3</span>
                        </div>
                        <div class="flex-1">
                            <span class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel3Title }}</span>
                            <span class="text-[#94A3B8] text-xs">{{ reportData.funnel3Desc }}</span>
                        </div>
                    </div>

                    <div
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <div class="w-10 h-10 bg-[#F0FDF4] rounded-full flex items-center justify-center shrink-0">
                            <span class="text-[#16A34A] font-bold text-[16px]">4</span>
                        </div>
                        <div class="flex-1">
                            <span class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel4Title }}</span>
                            <span class="text-[#94A3B8] text-xs">{{ reportData.funnel4Desc }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ AI 24小时任务日历（动态版） -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-7 h-7 rounded-lg bg-[#FFF7ED] flex items-center justify-center mr-3">
                            <span class="text-[#EA580C]">⏰</span>
                        </div>
                        <span class="text-sm font-bold text-[#1F2937]">AI 24小时任务日历</span>
                    </div>
                    <div
                        class="bg-[#FEF2F2] text-[#DC2626] text-[12px] font-bold px-2 py-1 rounded border border-solid border-[#FECACA] animate-pulse">
                        自动执行 {{ totalTaskCount }} 个任务
                    </div>
                </div>
                <div class="text-xs text-[#6B7280] mb-4">
                    下方为系统全自动执行的一整天任务安排，每个任务都有明确的
                    <span class="font-bold text-[#374151]">执行原理</span>和
                    <span class="font-bold text-[#374151]">效果预期</span>。
                </div>

                <!-- Tab 切换（只展示有数据的时段） -->
                <div class="flex overflow-x-auto pb-2 mb-4 gap-2">
                    <div
                        v-for="taskType in availableTaskTypes"
                        :key="taskType.key"
                        @click="switchTaskType(taskType.key)"
                        :class="[
                            'shrink-0 px-3 py-2 rounded-xl text-[13px] font-bold transition-all cursor-pointer',
                            currentTaskType === taskType.key
                                ? 'text-white shadow-md'
                                : 'bg-[#F9FAFB] text-[#6B7280] border border-solid border-[#F3F4F6]',
                        ]"
                        :style="
                            currentTaskType === taskType.key
                                ? 'background: linear-gradient(90deg, #f97316 0%, #dc2626 100%)'
                                : ''
                        ">
                        {{ taskType.name }}
                    </div>
                </div>

                <!-- 空状态 -->
                <div v-if="currentTasks.length === 0" class="py-10 text-center text-[#94A3B8] text-sm">
                    该时段暂无任务安排
                </div>

                <!-- 任务列表 -->
                <div class="space-y-4">
                    <div
                        v-for="(task, index) in currentTasks"
                        :key="index"
                        class="bg-[#F9FAFB] rounded-xl border border-solid border-[#F3F4F6] overflow-hidden">
                        <div
                            class="px-4 py-2.5 flex justify-between items-center"
                            style="background: linear-gradient(to right, #f97316 0%, #dc2626 100%)">
                            <span class="text-white font-bold text-xs">{{ task.time }}</span>
                            <span class="bg-white/20 text-white text-[12px] font-bold px-2 py-0.5 rounded">{{
                                task.platform
                            }}</span>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center mb-3">
                                <div
                                    class="w-6 h-6 rounded-full bg-white flex items-center justify-center mr-2 shadow-sm">
                                    <span class="text-[#EA580C]">{{ getTaskIcon(task.title) }}</span>
                                </div>
                                <span class="text-sm font-bold text-[#1F2937]">{{ task.title }}</span>
                            </div>
                            <div class="space-y-2.5">
                                <div class="flex items-start">
                                    <div class="w-14 shrink-0 pt-0.5">
                                        <span
                                            class="text-[12px] font-bold text-[#2563EB] bg-[#EFF6FF] px-1.5 py-0.5 rounded">
                                            为什么
                                        </span>
                                    </div>
                                    <span class="text-[13px] text-[#4B5563] leading-tight">{{ task.why }}</span>
                                </div>
                                <div class="flex items-start">
                                    <div class="w-14 shrink-0 pt-0.5">
                                        <span
                                            class="text-[12px] font-bold text-[#059669] bg-[#ECFDF5] px-1.5 py-0.5 rounded">
                                            怎么做
                                        </span>
                                    </div>
                                    <span class="text-[13px] text-[#4B5563] leading-tight">{{ task.do }}</span>
                                </div>
                                <div class="flex items-start">
                                    <div class="w-14 shrink-0 pt-0.5">
                                        <span
                                            class="text-[12px] font-bold text-[#7C3AED] bg-[#F3E8FF] px-1.5 py-0.5 rounded">
                                            效果
                                        </span>
                                    </div>
                                    <span class="text-[13px] text-[#374151] font-medium leading-tight">{{
                                        task.effect
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl p-6 text-white shadow-xl relative overflow-hidden"
                style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%)">
                <div
                    class="absolute top-[-40px] left-[-40px] w-[150px] h-[150px] rounded-full opacity-30"
                    style="background: radial-gradient(circle, #06b6d4 0%, transparent 70%)"></div>
                <div
                    class="absolute bottom-[-40px] right-[-40px] w-[150px] h-[150px] rounded-full opacity-30"
                    style="background: radial-gradient(circle, #3b82f6 0%, transparent 70%)"></div>

                <div class="relative z-10">
                    <span class="text-[18px] font-bold text-white block text-center mb-3">{{
                        reportData.visionTitle
                    }}</span>
                    <span class="text-[#CBD5E1] text-xs leading-relaxed block mb-6">
                        {{ reportData.visionIntro }}
                    </span>

                    <div class="space-y-3 mb-8">
                        <div
                            class="flex justify-between items-center px-4 py-3 rounded-xl border border-solid border-[#ffffff1a]"
                            style="background: rgba(255, 255, 255, 0.08)">
                            <span class="text-[11px] text-[#67E8F9]">阶段一</span>
                            <span class="font-bold text-white">{{ reportData.phase1 }}</span>
                        </div>
                        <div
                            class="flex justify-between items-center px-4 py-3 rounded-xl border border-solid border-[#ffffff1a]"
                            style="background: rgba(255, 255, 255, 0.08)">
                            <span class="text-[11px] text-[#67E8F9]">阶段二</span>
                            <span class="font-bold text-white">{{ reportData.phase2 }}</span>
                        </div>
                        <div
                            class="flex justify-between items-center px-4 py-3 rounded-xl border border-solid border-[#ffffff1a]"
                            style="background: rgba(255, 255, 255, 0.08)">
                            <span class="text-[11px] text-[#67E8F9]">阶段三</span>
                            <span class="font-bold text-[#FDE047]">{{ reportData.phase3 }}</span>
                        </div>
                    </div>

                    <div class="border-[0] border-t border-solid border-[#ffffff1a] pt-5">
                        <span class="text-[14px] font-medium text-white leading-relaxed block mb-3">
                            {{ reportData.conclusionHighlight }}
                        </span>
                        <span class="text-[#CBD5E1] text-xs leading-relaxed">
                            {{ reportData.conclusionAction }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useReportTask, TaskType, type TemplateDataItem } from "./useReportTask";

const props = defineProps<{
    reportData: any;
    templateData: TemplateDataItem[];
}>();

const { currentTaskType, availableTaskTypes, totalTaskCount, currentTasks, switchTaskType, getTaskIcon } =
    useReportTask(toRef(props, "templateData"), {
        // 同城版专属 Tab 名称
        taskTypeNames: {
            [TaskType.MORNING]: "早间地域标记",
            [TaskType.MORNING_GROWTH]: "午市引流高峰",
            [TaskType.AFTERNOON_PEAK]: "午间收尾",
            [TaskType.AFTERNOON_REST]: "下午引流准备",
            [TaskType.EVENING_PEAK]: "晚市营销高峰",
        },
        // 同城版专属图标
        extraIconMap: {
            同城曝光: "📡",
            同城触达: "📍",
            同城截流: "🎯",
            团购截流: "🛒",
        },
        // 同城版覆盖部分 scene 描述（如需差异化）
        extraSceneDescMap: {
            3: {
                why: "增加在同城精准人群中的曝光度",
                do: "对符合画像的本地作者执行：关注+点赞+评论",
                effect: "吸引同城潜在客户关注，建立初步连接",
            },
            10: {
                why: "提升账号活跃度与本地标签权重",
                do: "浏览同城相关图文/视频，模拟正常用户行为",
                effect: "给账号打上强烈的本地地域标签",
            },
        },
    });
</script>

<style scoped></style>
