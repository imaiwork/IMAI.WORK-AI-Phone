<template>
    <div class="h-full space-y-3">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <div class="flex justify-between items-center mb-4 pb-4 border-0 border-b border-solid border-[#F9FAFB]">
                <div>
                    <div class="text-[12px] font-bold text-[#9CA3AF] uppercase tracking-wider mb-1">全网扫描对象</div>
                    <div
                        class="text-lg font-black"
                        style="
                            background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        ">
                        {{ reportData.target }}
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center mb-3">
                    <span class="text-xs font-bold text-[#6B7280] mr-2">当前账号状态：</span>
                    <span
                        class="bg-[#FEF2F2] text-[#DC2626] text-xs font-black px-2 py-1 rounded border border-solid border-[#FECACA]">
                        {{ reportData.diagnosisStatus }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 mb-4">
                    <div
                        class="text-[12px] text-[#4B5563] bg-[#F9FAFB] px-2 py-1 rounded-md border border-solid border-[#F3F4F6] flex items-center">
                        <span class="text-[#F87171] mr-1 font-bold">×</span>{{ reportData.diagnosisPoint1 }}
                    </div>
                    <div
                        class="text-[12px] text-[#4B5563] bg-[#F9FAFB] px-2 py-1 rounded-md border border-solid border-[#F3F4F6] flex items-center">
                        <span class="text-[#F87171] mr-1 font-bold">×</span>{{ reportData.diagnosisPoint2 }}
                    </div>
                    <div
                        class="text-[12px] text-[#4B5563] bg-[#F9FAFB] px-2 py-1 rounded-md border border-solid border-[#F3F4F6] flex items-center">
                        <span class="text-[#F87171] mr-1 font-bold">×</span>{{ reportData.diagnosisPoint3 }}
                    </div>
                </div>
                <div
                    class="text-xs font-bold text-[#3B82F6] bg-[#EFF6FF]/80 p-3 rounded-xl border border-solid border-[#DBEAFE] leading-relaxed flex items-start">
                    <span class="mr-2 text-base">👉</span>{{ reportData.diagnosisConclusion }}
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <div class="flex items-center mb-4">
                <div class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center mr-3">
                    <span class="text-[#3B82F6]">🎯</span>
                </div>
                <span class="text-sm font-bold text-[#1F2937]">{{ reportData.audienceTitle }}</span>
            </div>
            <div class="flex mb-4 bg-[#F9FAFB] p-3 rounded-xl border border-solid border-[#F3F4F6]">
                <span class="text-[#9CA3AF] mr-2">📍</span>
                <span class="text-xs font-bold text-[#374151] shrink-0">截流范围：</span>
                <span class="ml-2 text-xs font-black text-[#3B82F6]">{{ reportData.audienceRadius }}</span>
            </div>
            <div class="flex flex-wrap gap-2 mb-4">
                <span
                    class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] border border-solid border-[#DBEAFE]"
                    ># {{ reportData.audienceTag1 }}</span
                >
                <span
                    class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] border border-solid border-[#DBEAFE]"
                    ># {{ reportData.audienceTag2 }}</span
                >
                <span
                    class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] border border-solid border-[#DBEAFE]"
                    ># {{ reportData.audienceTag3 }}</span
                >
                <span
                    class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] border border-solid border-[#DBEAFE]"
                    ># {{ reportData.audienceTag4 }}</span
                >
            </div>
            <div
                class="text-[13px] text-[#6B7280] bg-[#F9FAFB] p-3 rounded-lg border-0 border-l-2 border-solid border-[#3B82F6]">
                {{ reportData.audienceStrategy }}
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center">
                    <div class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center mr-3">
                        <span class="text-[#3B82F6]">🔄</span>
                    </div>
                    <span class="text-sm font-bold text-[#1F2937]">全域获客漏斗模型</span>
                </div>
            </div>
            <div class="space-y-0 relative">
                <div class="absolute left-6 top-4 bottom-4 w-0.5 bg-[#F3F4F6] z-0"></div>

                <div class="relative z-10 flex items-start pb-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#F3F4F6] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <span class="text-[#9CA3AF]">👥</span>
                    </div>
                    <div class="pt-1">
                        <div class="flex items-baseline">
                            <span class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep1 }}</span>
                            <span class="text-sm font-black text-[#3B82F6]">{{ reportData.funnelMetric1 }}</span>
                        </div>
                        <span class="text-[12px] text-[#6B7280] mt-1 block">{{ reportData.funnelDesc1 }}</span>
                    </div>
                </div>

                <div class="relative z-10 flex items-start pb-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#F3F4F6] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <span class="text-[#9CA3AF]">⚡</span>
                    </div>
                    <div class="pt-1">
                        <div class="flex items-baseline">
                            <span class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep2 }}</span>
                            <span class="text-sm font-black text-[#3B82F6]">{{ reportData.funnelMetric2 }}</span>
                        </div>
                        <span class="text-[12px] text-[#6B7280] mt-1 block">{{ reportData.funnelDesc2 }}</span>
                    </div>
                </div>

                <div class="relative z-10 flex items-start pb-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#F3F4F6] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <span class="text-[#9CA3AF]">💬</span>
                    </div>
                    <div class="pt-1">
                        <div class="flex items-baseline">
                            <span class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep3 }}</span>
                            <span class="text-sm font-black text-[#3B82F6]">{{ reportData.funnelMetric3 }}</span>
                        </div>
                        <span class="text-[12px] text-[#6B7280] mt-1 block">{{ reportData.funnelDesc3 }}</span>
                    </div>
                </div>

                <div class="relative z-10 flex items-start pb-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#DBEAFE] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <span class="text-[#3B82F6]">📈</span>
                    </div>
                    <div class="pt-1">
                        <div class="flex items-baseline">
                            <span class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep4 }}</span>
                            <span class="text-sm font-black text-[#3B82F6]">{{ reportData.funnelMetric4 }}</span>
                        </div>
                        <span class="text-[12px] text-[#6B7280] mt-1 block">{{ reportData.funnelDesc4 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center mr-3">
                        <span class="text-[#3B82F6]">⏰</span>
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

            <div class="flex overflow-x-auto pb-2 mb-4 gap-2">
                <div
                    v-for="taskType in taskTypes"
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
                            ? 'background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%)'
                            : ''
                    ">
                    {{ taskType.name }}
                </div>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(task, index) in currentTasks"
                    :key="index"
                    class="bg-[#F9FAFB] rounded-xl border border-solid border-[#F3F4F6] overflow-hidden">
                    <div
                        class="px-4 py-2.5 flex justify-between items-center"
                        style="background: linear-gradient(to right, #3b82f6 0%, #7c3aed 100%)">
                        <span class="text-white font-bold text-xs">{{ task.time }}</span>
                        <span class="bg-white/20 text-white text-[12px] font-bold px-2 py-0.5 rounded">{{
                            task.platform
                        }}</span>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center mb-3">
                            <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center mr-2 shadow-sm">
                                <span class="text-[#3B82F6]">{{ getTaskIcon(task.title) }}</span>
                            </div>
                            <span class="text-sm font-bold text-[#1F2937]">{{ task.title }}</span>
                        </div>
                        <div class="space-y-2.5">
                            <div class="flex items-start">
                                <div class="w-14 shrink-0 pt-0.5">
                                    <span
                                        class="text-[12px] font-bold text-[#2563EB] bg-[#EFF6FF] px-1.5 py-0.5 rounded"
                                        >为什么</span
                                    >
                                </div>
                                <span class="text-[13px] text-[#4B5563] leading-tight">{{ task.why }}</span>
                            </div>
                            <div class="flex items-start">
                                <div class="w-14 shrink-0 pt-0.5">
                                    <span
                                        class="text-[12px] font-bold text-[#059669] bg-[#ECFDF5] px-1.5 py-0.5 rounded"
                                        >怎么做</span
                                    >
                                </div>
                                <span class="text-[13px] text-[#4B5563] leading-tight">{{ task.do }}</span>
                            </div>
                            <div class="flex items-start">
                                <div class="w-14 shrink-0 pt-0.5">
                                    <span
                                        class="text-[12px] font-bold text-[#7C3AED] bg-[#F3E8FF] px-1.5 py-0.5 rounded"
                                        >效果</span
                                    >
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

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <span class="text-sm font-bold text-[#1F2937] mb-4 text-center block">预期商业回报对比</span>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-[#F9FAFB] rounded-xl p-3 border border-solid border-[#F3F4F6] text-center">
                    <div class="text-[12px] text-[#9CA3AF] font-bold mb-1">传统人工运营</div>
                    <div class="text-sm font-bold text-[#374151] mb-2">{{ reportData.roiTraditionalCost }}</div>
                    <div class="text-[12px] text-[#6B7280]">{{ reportData.roiTraditionalEffect }}</div>
                </div>
                <div
                    class="bg-[#EFF6FF] rounded-xl p-3 border border-solid border-[#DBEAFE] text-center relative overflow-hidden">
                    <div
                        class="absolute top-0 right-0 w-10 h-10 opacity-10 rounded-bl-full"
                        style="background: linear-gradient(to bottom right, #3b82f6 0%, #7c3aed 100%)"></div>
                    <div class="text-[12px] text-[#3B82F6] font-bold mb-1">部署AI系统</div>
                    <div
                        class="text-sm font-black mb-2"
                        style="
                            background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        ">
                        {{ reportData.roiAiCost }}
                    </div>
                    <div class="text-[12px] text-[#3B82F6] font-medium">{{ reportData.roiAiEffect }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6] text-center">
            <span class="text-xs font-bold text-[#9CA3AF] mb-4 uppercase tracking-widest block">系统每日执行量</span>
            <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <div class="text-sm font-black text-[#1F2937]">{{ reportData.workloadVideo }}</div>
                    <div class="text-[12px] text-[#6B7280] mt-0.5">发视频</div>
                </div>
                <div class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <div class="text-sm font-black text-[#1F2937]">{{ reportData.workloadComment }}</div>
                    <div class="text-[12px] text-[#6B7280] mt-0.5">神评论</div>
                </div>
                <div class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <div class="text-sm font-black text-[#1F2937]">{{ reportData.workloadDm }}</div>
                    <div class="text-[12px] text-[#6B7280] mt-0.5">发私信</div>
                </div>
            </div>
            <div class="border-0 border-t border-solid border-[#F3F4F6] pt-4">
                <div
                    class="text-2xl font-black mb-1.5"
                    style="
                        background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    ">
                    500+ 动作 / 天
                </div>
                <div
                    class="text-[13px] font-bold text-[#4B5563] bg-[#F9FAFB] inline-block px-3 py-1 rounded-full border border-solid border-[#F3F4F6]">
                    1个AI ≈ 10个不知疲倦的员工
                </div>
            </div>
        </div>

        <div
            class="rounded-2xl p-6 shadow-lg text-white relative overflow-hidden"
            style="background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%)">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
            <div
                class="flex justify-between items-center mb-6 relative z-10 bg-black/20 p-3 rounded-xl backdrop-blur-sm">
                <div class="text-center px-1">
                    <span class="text-[12px] text-white/70 mb-0.5 block">第7天</span>
                    <span class="text-xs font-bold">有人加你</span>
                </div>
                <span class="text-white/30">→</span>
                <div class="text-center px-1">
                    <span class="text-[12px] text-white/70 mb-0.5 block">第15天</span>
                    <span class="text-xs font-bold">稳定咨询</span>
                </div>
                <span class="text-white/30">→</span>
                <div class="text-center px-1">
                    <span class="text-[12px] text-white/70 mb-0.5 block">第30天</span>
                    <span class="text-xs font-black text-[#FEF08A]">自动进客</span>
                </div>
            </div>
            <div class="relative z-10 text-center space-y-3">
                <span class="text-xs font-medium text-white/90 block">老板，你缺的不是视频，也不是技巧</span>
                <span class="text-lg font-black leading-snug tracking-wide block">
                    你缺的是一个<br />
                    <span class="text-[#FEF08A]">每天稳定给你搞人的系统</span>
                </span>
                <div class="w-10 h-px bg-white/30 mx-auto my-3"></div>
                <span class="text-xs font-bold text-white/90 leading-relaxed block">
                    这不是爆款玩法，是流水线搞人玩法。<br />
                    你只需要做一件事：<span class="text-[#FEF08A] text-sm font-black">把人变成钱。</span>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";

const props = defineProps<{
    reportData: any;
}>();

enum TaskType {
    MORNING = "morning",
    MORNING_GROWTH = "morning_growth",
    AFTERNOON_PEAK = "afternoon_peak",
    AFTERNOON_REST = "afternoon_rest",
    EVENING_PEAK = "evening_peak",
}

const taskTypes = [
    { name: "早间地域标记", key: TaskType.MORNING },
    { name: "午市引流高峰", key: TaskType.MORNING_GROWTH },
    { name: "下午引流准备", key: TaskType.AFTERNOON_PEAK },
    { name: "晚市营销高峰", key: TaskType.AFTERNOON_REST },
    { name: "深夜养号收尾", key: TaskType.EVENING_PEAK },
];

const taskExecutionTimeTable = {
    [TaskType.MORNING]: [
        {
            time: "8:00 - 9:00",
            platform: "抖音",
            title: "自动养号",
            why: "给账号打上强烈的本地地域标签",
            do: "只刷同城页面，随机点赞完播，系统模拟本地用户行为",
            effect: "提高本地推荐率",
        },
        {
            time: "9:00 - 9:30",
            platform: "抖/红",
            title: "视频发布",
            why: "占领早间本地推荐流量",
            do: "各发布1条视频",
            effect: "吸引早间活跃用户",
        },
        {
            time: "9:30 - 10:00",
            platform: "微信",
            title: "私信接管",
            why: "处理夜间遗留消息",
            do: "处理老客户微信预订及夜间遗留消息",
            effect: "锁定老客户复购",
        },
    ],
    [TaskType.MORNING_GROWTH]: [
        {
            time: "10:00 - 11:00",
            platform: "抖音",
            title: "同城获客",
            why: "增加同城曝光",
            do: '刷同城视频流，关注作者+点赞+评论"欢迎有空来店坐坐"',
            effect: "引流午市客源",
        },
        {
            time: "11:00 - 11:15",
            platform: "抖音",
            title: "私信接管",
            why: "午市前夕极速响应",
            do: "极速响应抖音同城带来的咨询，发送团购链接或门店定位",
            effect: "促进即时转化",
        },
        {
            time: "11:15 - 11:30",
            platform: "微信",
            title: "自动加好友",
            why: "沉淀私域池",
            do: "将刚咨询的高意向客户加微",
            effect: "方便发送定位和后续触达",
        },
        {
            time: "11:30 - 12:00",
            platform: "小红书",
            title: "同城获客",
            why: "增加本地圈子曝光",
            do: "刷同城页面，关注作者+点赞+评论",
            effect: "扩大影响力",
        },
        {
            time: "12:00 - 12:15",
            platform: "小红书",
            title: "私信接管",
            why: "极速响应咨询",
            do: "极速响应小红书午市咨询",
            effect: "促进午市到店",
        },
        {
            time: "12:15 - 12:30",
            platform: "微信",
            title: "朋友圈互动",
            why: "午市高峰期刷存在感",
            do: "AI手机在后台给朋友圈本地客户点赞/评论",
            effect: "促使老客复购",
        },
        {
            time: "12:30 - 13:15",
            platform: "微信",
            title: "私信接管",
            why: "处理午市咨询",
            do: "自动回复客户咨询",
            effect: "提高客户满意度",
        },
        {
            time: "13:15 - 13:30",
            platform: "微信",
            title: "朋友圈发布",
            why: "展示门店人气",
            do: "发布1条朋友圈",
            effect: "刺激晚市预订",
        },
    ],
    [TaskType.AFTERNOON_PEAK]: [
        {
            time: "13:30 - 13:45",
            platform: "抖音",
            title: "私信接管",
            why: "查漏补缺",
            do: "统一回复遗漏信息",
            effect: "不错过任何线索",
        },
        {
            time: "13:45 - 14:00",
            platform: "小红书",
            title: "私信接管",
            why: "查漏补缺",
            do: "统一回复小红书遗漏信息",
            effect: "不错过任何线索",
        },
        {
            time: "14:00 - 14:30",
            platform: "微信",
            title: "私信接管",
            why: "集中处理咨询",
            do: "集中处理微信端客户咨询与售后",
            effect: "提升服务体验",
        },
        {
            time: "14:30 - 14:45",
            platform: "微信",
            title: "自动加好友",
            why: "扩充私域",
            do: "零星添加午间积累的线索",
            effect: "储备晚市客源",
        },
        {
            time: "14:45 - 15:30",
            platform: "抖音",
            title: "截流获客",
            why: "截流高意向用户",
            do: '搜索"本地地名+探店/美食"，在本地大V评论区找求推荐/问价的用户发送私信',
            effect: "精准低成本获客",
        },
        {
            time: "15:30 - 15:45",
            platform: "抖音",
            title: "私信接管",
            why: "承接截流流量",
            do: "紧跟截流动作，极速承接私信回复",
            effect: "提高转化率",
        },
        {
            time: "15:45 - 16:45",
            platform: "小红书",
            title: "留痕获客",
            why: "竞品截流",
            do: "搜索本地竞品词，点赞其评论+关注该人+点赞其个人中心第一条作品",
            effect: "挖墙脚获客",
        },
        {
            time: "16:45 - 17:00",
            platform: "抖/红",
            title: "视频发布",
            why: "卡位晚市流量",
            do: "各发布1条晚餐推荐或夜间活动预告",
            effect: "引流晚市",
        },
    ],
    [TaskType.AFTERNOON_REST]: [
        {
            time: "17:00 - 17:15",
            platform: "抖音",
            title: "私信接管",
            why: "晚市前夕清理",
            do: "晚市前夕红点清理",
            effect: "促进晚市转化",
        },
        {
            time: "17:15 - 17:30",
            platform: "小红书",
            title: "私信接管",
            why: "晚市前夕清理",
            do: "晚市前夕红点清理",
            effect: "促进晚市转化",
        },
        {
            time: "17:30 - 17:45",
            platform: "微信",
            title: "私信接管",
            why: "处理预订",
            do: "晚市前夕红点清理，处理客户预订",
            effect: "锁定晚市客源",
        },
        {
            time: "17:45 - 18:00",
            platform: "微信",
            title: "自动加好友",
            why: "晚市预订高峰",
            do: "加微发定位",
            effect: "方便客户到店",
        },
        {
            time: "18:00 - 18:30",
            platform: "抖音",
            title: "同城获客",
            why: "晚间流量最大",
            do: "高频执行同城关注+点赞当前作品+评论当前作品",
            effect: "强力曝光",
        },
        {
            time: "18:30 - 18:45",
            platform: "抖音",
            title: "私信接管",
            why: "极速响应",
            do: "极速响应晚间咨询",
            effect: "提高转化",
        },
        {
            time: "18:45 - 18:50",
            platform: "微信",
            title: "自动加好友",
            why: "沉淀私域",
            do: "零星添加",
            effect: "扩充私域池",
        },
        {
            time: "18:50 - 19:30",
            platform: "小红书",
            title: "同城获客",
            why: "晚间同城曝光",
            do: "执行关注+点赞当前笔记+评论当前笔记",
            effect: "扩大影响力",
        },
        {
            time: "19:30 - 20:00",
            platform: "小红书",
            title: "私信接管",
            why: "响应咨询",
            do: "响应小红书晚间咨询",
            effect: "提高转化",
        },
    ],
    [TaskType.EVENING_PEAK]: [
        {
            time: "20:00 - 21:00",
            platform: "抖音",
            title: "自动养号",
            why: "提升账号权重",
            do: "晚间挂机看本地生活类直播或同城视频",
            effect: "保持账号活跃",
        },
        {
            time: "21:00 - 21:30",
            platform: "抖音",
            title: "私信接管",
            why: "深度服务",
            do: "深度回复，做售后关怀",
            effect: "提升客户满意度",
        },
        {
            time: "21:30 - 22:00",
            platform: "小红书",
            title: "私信接管",
            why: "睡前清理",
            do: "睡前红点清理",
            effect: "保持高回复率",
        },
        {
            time: "22:00 - 22:30",
            platform: "快手",
            title: "私信接管",
            why: "睡前清理",
            do: "睡前红点清理",
            effect: "保持高回复率",
        },
        {
            time: "22:30 - 22:45",
            platform: "微信",
            title: "自动加好友",
            why: "最后收网",
            do: "睡前最后一次添加，随后进入夜间静默",
            effect: "结束全天任务",
        },
    ],
};

const currentTaskType = ref<TaskType>(TaskType.MORNING);

const currentTasks = computed(() => taskExecutionTimeTable[currentTaskType.value] || []);

const totalTaskCount = computed(() =>
    Object.values(taskExecutionTimeTable).reduce((total, tasks) => total + tasks.length, 0)
);

const switchTaskType = (taskType: TaskType) => {
    currentTaskType.value = taskType;
};

const getTaskIcon = (title: string): string => {
    const iconMap: Record<string, string> = {
        自动养号: "🌱",
        视频发布: "📹",
        私信接管: "💬",
        同城获客: "🎯",
        自动加好友: "➕",
        朋友圈互动: "❤️",
        朋友圈发布: "📱",
        截流获客: "🎯",
        留痕获客: "👀",
    };
    return iconMap[title] || "🔥";
};
</script>

<style scoped></style>
