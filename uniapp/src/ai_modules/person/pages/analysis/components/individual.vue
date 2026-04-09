<template>
    <view class="h-full space-y-3">
        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex justify-between items-center mb-4 pb-4 border-[0] border-b border-solid border-[#F9FAFB]">
                <view>
                    <view class="text-[20rpx] font-bold text-[#9CA3AF] uppercase tracking-wider mb-1"
                        >全网扫描对象</view
                    >
                    <view
                        class="text-lg font-black"
                        style="
                            background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        "
                        >{{ reportData.target }}</view
                    >
                </view>
                <view
                    class="shrink-0 w-10 h-10 rounded-full bg-[#EFF6FF] flex items-center justify-center border border-solid border-[#DBEAFE]">
                    <image src="@/ai_modules/person/static/icons/line.svg" class="w-5 h-5" />
                </view>
            </view>
            <view>
                <view class="flex items-center mb-3">
                    <text class="text-xs font-bold text-[#6B7280] mr-2">当前账号状态：</text>
                    <text
                        class="bg-[#FEF2F2] text-[#DC2626] text-xs font-black px-2 py-1 rounded border border-solid border-[#FECACA]"
                        >{{ reportData.diagnosisStatus }}</text
                    >
                </view>
                <view class="flex flex-wrap gap-2 mb-4">
                    <view
                        class="text-[20rpx] text-[#4B5563] bg-[#F9FAFB] px-2 py-1 rounded-md border border-solid border-[#F3F4F6] flex items-center">
                        <text class="text-[#F87171] mr-1 font-bold">×</text>{{ reportData.diagnosisPoint1 }}
                    </view>
                    <view
                        class="text-[20rpx] text-[#4B5563] bg-[#F9FAFB] px-2 py-1 rounded-md border border-solid border-[#F3F4F6] flex items-center">
                        <text class="text-[#F87171] mr-1 font-bold">×</text>{{ reportData.diagnosisPoint2 }}
                    </view>
                    <view
                        class="text-[20rpx] text-[#4B5563] bg-[#F9FAFB] px-2 py-1 rounded-md border border-solid border-[#F3F4F6] flex items-center">
                        <text class="text-[#F87171] mr-1 font-bold">×</text>{{ reportData.diagnosisPoint3 }}
                    </view>
                </view>
                <view
                    class="text-xs font-bold text-[#3B82F6] bg-[#EFF6FF]/80 p-3 rounded-xl border border-solid border-[#DBEAFE] leading-relaxed flex items-start">
                    <text class="mr-2 text-base">👉</text>{{ reportData.diagnosisConclusion }}
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center mb-4">
                <view class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center mr-3">
                    <text class="text-[#3B82F6]">🎯</text>
                </view>
                <text class="text-sm font-bold text-[#1F2937]">{{ reportData.audienceTitle }}</text>
            </view>
            <view class="flex mb-4 bg-[#F9FAFB] p-3 rounded-xl border border-solid border-[#F3F4F6]">
                <text class="text-[#9CA3AF] mr-2">📍</text>
                <text class="text-xs font-bold text-[#374151] shrink-0">目标范围：</text>
                <text class="ml-2 text-xs font-black text-[#3B82F6]">{{ reportData.audienceRadius }}</text>
            </view>
            <view class="flex flex-wrap gap-2 mb-4">
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] border border-solid border-[#DBEAFE]"
                    ># {{ reportData.audienceTag1 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] border border-solid border-[#DBEAFE]"
                    ># {{ reportData.audienceTag2 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] border border-solid border-[#DBEAFE]"
                    ># {{ reportData.audienceTag3 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#DBEAFE] text-[#1E40AF] border border-solid border-[#DBEAFE]"
                    ># {{ reportData.audienceTag4 }}</text
                >
            </view>
            <view
                class="text-[22rpx] text-[#6B7280] bg-[#F9FAFB] p-3 rounded-lg border-[0] border-l-2 border-solid border-[#3B82F6]">
                {{ reportData.audienceStrategy }}
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center justify-between mb-5">
                <view class="flex items-center">
                    <view class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center mr-3">
                        <text class="text-[#3B82F6]">🔄</text>
                    </view>
                    <text class="text-sm font-bold text-[#1F2937]">全域增长漏斗模型</text>
                </view>
            </view>
            <view class="space-y-0 relative">
                <view class="absolute left-6 top-4 bottom-4 w-0.5 bg-[#F3F4F6] z-0"></view>

                <view class="relative z-10 flex items-start pb-4">
                    <view
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#F3F4F6] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <text class="text-[#9CA3AF]">👥</text>
                    </view>
                    <view class="pt-1">
                        <view class="flex items-baseline">
                            <text class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep1 }}</text>
                            <text class="text-sm font-black text-[#3B82F6]">{{ reportData.funnelMetric1 }}</text>
                        </view>
                        <text class="text-[20rpx] text-[#6B7280] mt-1">{{ reportData.funnelDesc1 }}</text>
                    </view>
                </view>

                <view class="relative z-10 flex items-start pb-4">
                    <view
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#F3F4F6] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <text class="text-[#9CA3AF]">⚡</text>
                    </view>
                    <view class="pt-1">
                        <view class="flex items-baseline">
                            <text class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep2 }}</text>
                            <text class="text-sm font-black text-[#3B82F6]">{{ reportData.funnelMetric2 }}</text>
                        </view>
                        <text class="text-[20rpx] text-[#6B7280] mt-1">{{ reportData.funnelDesc2 }}</text>
                    </view>
                </view>

                <view class="relative z-10 flex items-start pb-4">
                    <view
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#F3F4F6] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <text class="text-[#9CA3AF]">💬</text>
                    </view>
                    <view class="pt-1">
                        <view class="flex items-baseline">
                            <text class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep3 }}</text>
                            <text class="text-sm font-black text-[#3B82F6]">{{ reportData.funnelMetric3 }}</text>
                        </view>
                        <text class="text-[20rpx] text-[#6B7280] mt-1">{{ reportData.funnelDesc3 }}</text>
                    </view>
                </view>

                <view class="relative z-10 flex items-start pb-4">
                    <view
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#DBEAFE] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <text class="text-[#3B82F6]">📈</text>
                    </view>
                    <view class="pt-1">
                        <view class="flex items-baseline">
                            <text class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep4 }}</text>
                            <text class="text-sm font-black text-[#3B82F6]">{{ reportData.funnelMetric4 }}</text>
                        </view>
                        <text class="text-[20rpx] text-[#6B7280] mt-1">{{ reportData.funnelDesc4 }}</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center justify-between mb-4">
                <view class="flex items-center">
                    <view class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center mr-3">
                        <text class="text-[#3B82F6]">⏰</text>
                    </view>
                    <text class="text-sm font-bold text-[#1F2937]">AI 24小时任务日历</text>
                </view>
                <view
                    class="bg-[#FEF2F2] text-[#DC2626] text-[20rpx] font-bold px-2 py-1 rounded border border-solid border-[#FECACA] animate-pulse"
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
                    v-for="(taskType, index) in taskTypes"
                    :key="taskType.key"
                    @click="switchTaskType(taskType.key)"
                    :class="[
                        'shrink-0 px-3 py-2 rounded-xl text-[22rpx] font-bold transition-all cursor-pointer',
                        currentTaskType === taskType.key
                            ? 'text-white shadow-md'
                            : 'bg-[#F9FAFB] text-[#6B7280] border border-solid border-[#F3F4F6]',
                    ]"
                    :style="
                        currentTaskType === taskType.key
                            ? 'background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%)'
                            : ''
                    "
                    >{{ taskType.name }}</view
                >
            </view>

            <view class="space-y-4">
                <view
                    v-for="(task, index) in currentTasks"
                    :key="index"
                    class="bg-[#F9FAFB] rounded-xl border border-solid border-[#F3F4F6] overflow-hidden">
                    <view
                        class="px-4 py-2.5 flex justify-between items-center"
                        style="background: linear-gradient(to right, #3b82f6 0%, #7c3aed 100%)">
                        <view class="text-white font-bold text-xs">{{ task.time }}</view>
                        <view class="bg-[#ffffff]/20 text-white text-[20rpx] font-bold px-2 py-0.5 rounded">{{
                            task.platform
                        }}</view>
                    </view>
                    <view class="p-4">
                        <view class="flex items-center mb-3">
                            <view class="w-6 h-6 rounded-full bg-white flex items-center justify-center mr-2 shadow-sm">
                                <text class="text-[#3B82F6]">{{ getTaskIcon(task.title) }}</text>
                            </view>
                            <view class="text-sm font-bold text-[#1F2937]">{{ task.title }}</view>
                        </view>
                        <view class="space-y-2.5">
                            <view class="flex items-start">
                                <view class="w-14 shrink-0 pt-0.5">
                                    <text
                                        class="text-[20rpx] font-bold text-[#3B82F6] bg-[#EFF6FF] px-1.5 py-0.5 rounded"
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

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <text class="text-sm font-bold text-[#1F2937] mb-4 text-center block">预期商业回报对比</text>
            <view class="grid grid-cols-2 gap-3">
                <view class="bg-[#F9FAFB] rounded-xl p-3 border border-solid border-[#F3F4F6] text-center">
                    <view class="text-[20rpx] text-[#9CA3AF] font-bold mb-1">传统人工运营</view>
                    <view class="text-sm font-bold text-[#374151] mb-2">{{ reportData.roiTraditionalCost }}</view>
                    <view class="text-[20rpx] text-[#6B7280]">{{ reportData.roiTraditionalEffect }}</view>
                </view>
                <view class="bg-[#EFF6FF] rounded-xl p-3 border border-[#DBEAFE] text-center relative overflow-hidden">
                    <view
                        class="absolute top-0 right-0 w-10 h-10 opacity-10 rounded-bl-full"
                        style="background: linear-gradient(to bottom right, #3b82f6 0%, #7c3aed 100%)"></view>
                    <view class="text-[20rpx] text-[#3B82F6] font-bold mb-1">部署AI系统</view>
                    <view
                        class="text-sm font-black mb-2"
                        style="
                            background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        "
                        >{{ reportData.roiAiCost }}</view
                    >
                    <view class="text-[20rpx] text-[#3B82F6] font-medium">{{ reportData.roiAiEffect }}</view>
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-[#F3F4F6] text-center">
            <text class="text-xs font-bold text-[#9CA3AF] mb-4 uppercase tracking-widest block">系统每日执行量</text>
            <view class="grid grid-cols-3 gap-2 mb-4">
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadVideo || "4-6条" }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">发视频</view>
                </view>
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadComment || "150+次" }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">神评论</view>
                </view>
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadDm || "200+人" }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">发私信</view>
                </view>
            </view>
            <view class="border-t border-[#F3F4F6] pt-4">
                <view
                    class="text-2xl font-black mb-1.5"
                    style="
                        background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    "
                    >800+ 动作 / 天</view
                >
                <view
                    class="text-[22rpx] font-bold text-[#4B5563] bg-[#F9FAFB] inline-block px-3 py-1 rounded-full border border-[#F3F4F6]"
                    >1个AI ≈ 10个不知疲倦的员工</view
                >
            </view>
        </view>

        <view
            class="rounded-2xl p-6 shadow-lg text-white relative overflow-hidden"
            style="background: linear-gradient(90deg, #3b82f6 0%, #7c3aed 100%)">
            <view class="absolute top-0 right-0 w-32 h-32 bg-[#ffffff]/10 rounded-full blur-2xl -mr-10 -mt-10"></view>
            <view class="flex justify-between items-center mb-6 relative z-10 bg-[#000000]/20 p-3 rounded-xl">
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第7天</text>
                    <text class="text-xs font-bold">粉丝破万</text>
                </view>
                <text class="text-[#ffffff]/30">→</text>
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第15天</text>
                    <text class="text-xs font-bold">私域破千</text>
                </view>
                <text class="text-[#ffffff]/30">→</text>
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第30天</text>
                    <text class="text-xs font-black text-[#FEF08A]">稳定变现</text>
                </view>
            </view>
            <view class="relative z-10 text-center space-y-3">
                <text class="text-xs font-medium text-[#ffffff]/90 block">你缺的不是内容，也不是专业度</text>
                <text class="text-lg font-black leading-snug tracking-wide block"
                    >你缺的是一个<br /><text class="text-[#FEF08A]">全天候自动化增长系统</text></text
                >
                <view class="w-10 h-px bg-[#ffffff]/30 mx-auto my-3"></view>
                <text class="text-xs font-bold text-[#ffffff]/90 leading-relaxed block"
                    >这不是单点爆发，是持续增长引擎。<br />你只需要做一件事：<text
                        class="text-[#FEF08A] text-sm font-black"
                        >专注创作核心内容。</text
                    ></text
                >
            </view>
        </view>
    </view>
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
    NIGHT_REST = "night_rest",
}

// 任务类型
const taskTypes = [
    {
        name: "清晨启动期",
        key: TaskType.MORNING,
    },
    {
        name: "上午增长期",
        key: TaskType.MORNING_GROWTH,
    },
    {
        name: "午间高峰期",
        key: TaskType.AFTERNOON_PEAK,
    },
    {
        name: "下午养号期",
        key: TaskType.AFTERNOON_REST,
    },
    {
        name: "晚高峰引爆期",
        key: TaskType.EVENING_PEAK,
    },
    {
        name: "深夜养号期",
        key: TaskType.NIGHT_REST,
    },
];

// 任务执行时间表
const taskExecutionTimeTable = {
    [TaskType.MORNING]: [
        {
            time: "6:00 - 6:15",
            platform: "小红书",
            title: "私信接管",
            why: "抓住早起用户",
            do: "回复夜间积累的咨询",
            effect: "提高响应速度",
        },
        {
            time: "6:15 - 6:30",
            platform: "抖音",
            title: "私信接管",
            why: "清理夜间咨询",
            do: "回复夜间积累的咨询",
            effect: "不错过任何线索",
        },
        {
            time: "6:30 - 6:45",
            platform: "快手",
            title: "私信接管",
            why: "全平台覆盖",
            do: "回复夜间积累的咨询",
            effect: "全网无死角",
        },
        {
            time: "6:45 - 7:00",
            platform: "微信",
            title: "私信接管",
            why: "私域维护",
            do: "回复私域用户咨询",
            effect: "提高私域活跃度",
        },
        {
            time: "7:00 - 7:15",
            platform: "微信",
            title: "自动加好友",
            why: "扩充私域",
            do: "发送好友请求，附带话术（如：你好，抖音/小红书来的）",
            effect: "持续扩大私域池",
        },
        {
            time: "7:15 - 7:30",
            platform: "微信",
            title: "私信接管",
            why: "实时跟进",
            do: "及时跟进早间活跃用户的消息",
            effect: "提高转化率",
        },
        {
            time: "7:30 - 8:00",
            platform: "微信",
            title: "朋友圈发布",
            why: "早间种草",
            do: "发布视频，展示个人IP日常或专业干货",
            effect: "激活私域用户",
        },
    ],
    [TaskType.MORNING_GROWTH]: [
        {
            time: "8:00 - 8:30",
            platform: "抖/红/快",
            title: "视频发布",
            why: "矩阵分发",
            do: "各发布1条视频，抓住早通勤流量",
            effect: "多平台覆盖",
        },
        {
            time: "8:30 - 9:00",
            platform: "小红书",
            title: "留痕获客",
            why: "扩大影响圈",
            do: "搜索关键词找同行笔记，点赞评论+关注+点赞其作品",
            effect: "吸引同行粉丝",
        },
        {
            time: "9:00 - 9:30",
            platform: "微信",
            title: "私信接管",
            why: "承接流量",
            do: "承接早间视频发布和留痕带来的咨询",
            effect: "快速响应",
        },
        {
            time: "9:30 - 9:45",
            platform: "微信",
            title: "自动加好友",
            why: "持续扩充",
            do: "少量多次添加，打散频率防封控",
            effect: "稳定增长私域",
        },
        {
            time: "9:45 - 10:30",
            platform: "抖音",
            title: "截流获客",
            why: "精准获客",
            do: "搜索关键词找同行视频，在评论区找符合预设匹配词的用户发私信",
            effect: "低成本高转化",
        },
        {
            time: "10:30 - 11:00",
            platform: "微信",
            title: "私信接管",
            why: "及时跟进",
            do: "集中处理微信端的好友通过及初步沟通",
            effect: "提高转化率",
        },
        {
            time: "11:00 - 11:30",
            platform: "快手",
            title: "自动养号",
            why: "提升权重",
            do: "浏览行业相关图文/视频，模拟正常用户",
            effect: "增加账号活跃度",
        },
        {
            time: "11:30 - 12:00",
            platform: "小红书",
            title: "自动养号",
            why: "提升权重",
            do: "浏览行业相关图文/视频，模拟正常用户",
            effect: "增加账号活跃度",
        },
    ],
    [TaskType.AFTERNOON_PEAK]: [
        {
            time: "12:00 - 12:30",
            platform: "抖音",
            title: "自动养号",
            why: "午休流量期",
            do: "浏览行业相关图文/视频，模拟正常用户",
            effect: "提升推荐概率",
        },
        {
            time: "12:30 - 12:45",
            platform: "微信",
            title: "自动加好友",
            why: "午休高峰",
            do: "午休时间用户看手机频率高，通过率高",
            effect: "提高加友效率",
        },
        {
            time: "12:45 - 13:15",
            platform: "小红书",
            title: "截流获客",
            why: "午休获客",
            do: "刷同城页面，在同城热门笔记评论区寻找目标用户发私信",
            effect: "精准触达",
        },
        {
            time: "13:15 - 13:30",
            platform: "全平台",
            title: "私信接管",
            why: "统一处理",
            do: "紧跟截流动作，极速响应回复，防止线索流失",
            effect: "提高转化率",
        },
        {
            time: "13:30 - 14:00",
            platform: "抖音",
            title: "留痕获客",
            why: "扩大影响力",
            do: "刷同城视频，对符合画像的作者执行：关注+点赞+评论",
            effect: "增加曝光",
        },
        {
            time: "14:00 - 14:30",
            platform: "微信",
            title: "私信接管",
            why: "集中处理",
            do: "处理午间积累的微信消息",
            effect: "提高客户满意度",
        },
        {
            time: "14:30 - 14:45",
            platform: "微信",
            title: "自动加好友",
            why: "持续引流",
            do: "持续消化线索库",
            effect: "扩大私域基础",
        },
    ],
    [TaskType.AFTERNOON_REST]: [
        {
            time: "14:45 - 16:00",
            platform: "抖音",
            title: "自动养号",
            why: "下午低谷期",
            do: "下午长时段挂机养号，提升账号权重",
            effect: "提高推荐率",
        },
        {
            time: "16:00 - 17:00",
            platform: "小红书",
            title: "自动养号",
            why: "持续养号",
            do: "下午长时段挂机养号，提升账号权重",
            effect: "提高推荐率",
        },
    ],
    [TaskType.EVENING_PEAK]: [
        {
            time: "17:00 - 17:30",
            platform: "视/朋友圈",
            title: "视频发布",
            why: "晚高峰预热",
            do: "晚高峰前夕发布，触达微信私域及视频号公域",
            effect: "双渠道覆盖",
        },
        {
            time: "17:30 - 17:45",
            platform: "抖音",
            title: "私信接管",
            why: "晚高峰准备",
            do: "晚高峰红点清理",
            effect: "为高峰期做准备",
        },
        {
            time: "17:45 - 18:00",
            platform: "小红书",
            title: "私信接管",
            why: "晚高峰准备",
            do: "晚高峰红点清理",
            effect: "为高峰期做准备",
        },
        {
            time: "18:00 - 18:15",
            platform: "快手",
            title: "私信接管",
            why: "晚高峰准备",
            do: "晚高峰红点清理",
            effect: "为高峰期做准备",
        },
        {
            time: "18:15 - 18:30",
            platform: "微信",
            title: "自动加好友",
            why: "下班高峰",
            do: "下班通勤时间添加",
            effect: "提高通过率",
        },
        {
            time: "18:30 - 19:15",
            platform: "抖音",
            title: "截流获客",
            why: "晚间黄金期",
            do: "搜索关键词找同行视频，在评论区找符合预设匹配词的用户发私信",
            effect: "高效获客",
        },
        {
            time: "19:15 - 19:30",
            platform: "全平台",
            title: "私信接管",
            why: "及时跟进",
            do: "及时承接晚间截流反馈",
            effect: "提高转化率",
        },
        {
            time: "19:30 - 20:00",
            platform: "小红书",
            title: "留痕获客",
            why: "晚间高活跃",
            do: "搜索精准词，点赞评论+关注+收藏其作品",
            effect: "扩大影响圈",
        },
        {
            time: "20:00 - 20:15",
            platform: "全平台",
            title: "私信接管",
            why: "持续响应",
            do: "晚间高频互动期，保持响应",
            effect: "提高用户体验",
        },
        {
            time: "20:15 - 20:30",
            platform: "微信",
            title: "自动加好友",
            why: "晚间休息期",
            do: "晚间休息期添加",
            effect: "持续扩充私域",
        },
    ],
    [TaskType.NIGHT_REST]: [
        {
            time: "20:30 - 20:45",
            platform: "抖音",
            title: "自动养号",
            why: "晚间养号",
            do: "晚间黄金档轻度养号",
            effect: "提升账号活跃度",
        },
        {
            time: "20:45 - 21:00",
            platform: "全平台",
            title: "私信接管",
            why: "轮询处理",
            do: "轮询回复各平台消息",
            effect: "保持高响应率",
        },
        {
            time: "21:00 - 21:15",
            platform: "小红书",
            title: "自动养号",
            why: "晚间活跃期",
            do: "晚间活跃期养号",
            effect: "提升账号权重",
        },
        {
            time: "21:15 - 21:30",
            platform: "全平台",
            title: "私信接管",
            why: "轮询处理",
            do: "轮询回复各平台消息",
            effect: "保持高响应率",
        },
        {
            time: "21:30 - 21:45",
            platform: "快手",
            title: "自动养号",
            why: "晚间活跃期",
            do: "晚间活跃期养号",
            effect: "提升账号权重",
        },
        {
            time: "21:45 - 22:00",
            platform: "全平台",
            title: "私信接管",
            why: "轮询处理",
            do: "轮询回复各平台消息",
            effect: "保持高响应率",
        },
        {
            time: "22:00 - 22:30",
            platform: "微信",
            title: "朋友圈互动",
            why: "私域激活",
            do: "自动给微信好友朋友圈点赞/评论，做私域激活与留痕",
            effect: "增强粘性",
        },
        {
            time: "22:30 - 22:45",
            platform: "微信",
            title: "自动加好友",
            why: "最后收网",
            do: "睡前最后一次添加",
            effect: "完成全天引流",
        },
        {
            time: "22:45 - 23:30",
            platform: "全平台",
            title: "私信接管",
            why: "全面清理",
            do: "睡前最后一次全盘清理未读消息，随后进入静默",
            effect: "零遗漏服务",
        },
    ],
};

// 当前选中的任务类型
const currentTaskType = ref<TaskType>(TaskType.MORNING);

// 当前任务列表
const currentTasks = computed(() => {
    return taskExecutionTimeTable[currentTaskType.value] || [];
});

// 总任务数量
const totalTaskCount = computed(() => {
    return Object.values(taskExecutionTimeTable).reduce((total, tasks) => total + tasks.length, 0);
});

// 切换任务类型
const switchTaskType = (taskType: TaskType) => {
    currentTaskType.value = taskType;
};

// 获取任务图标
const getTaskIcon = (title: string) => {
    const iconMap: Record<string, string> = {
        私信接管: "🔥",
        视频发布: "📹",
        留痕获客: "👀",
        截流获客: "🎯",
        自动养号: "🌱",
        自动加好友: "➕",
        朋友圈发布: "📱",
        朋友圈互动: "❤️",
    };
    return iconMap[title] || "🔥";
};
</script>

<style scoped></style>
