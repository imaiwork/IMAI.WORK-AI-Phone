<template>
    <view class="h-full space-y-3">
        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex justify-between items-center mb-4 pb-4 border-[0] border-b border-solid border-[#F9FAFB]">
                <view>
                    <view class="text-[20rpx] font-bold text-[#9CA3AF] uppercase tracking-wider mb-1">
                        全网扫描对象
                    </view>
                    <view
                        class="text-lg font-black"
                        style="
                            background: linear-gradient(90deg, #f97316 0%, #dc2626 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        "
                        >{{ reportData.target }}</view
                    >
                </view>
                <view
                    class="shrink-0 w-10 h-10 rounded-full bg-[#FFF7ED] flex items-center justify-center border border-solid border-[#FED7AA]">
                    <image src="@/ai_modules/person/static/icons/line3.svg" class="w-5 h-5" />
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
                    class="text-xs font-bold text-[#EA580C] bg-[#FFF7ED]/80 p-3 rounded-xl border border-solid border-[#FED7AA] leading-relaxed flex items-start">
                    <text class="mr-2 text-base">👉</text>{{ reportData.diagnosisConclusion }}
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center mb-4">
                <view class="w-7 h-7 rounded-lg bg-[#FFF7ED] flex items-center justify-center mr-3">
                    <text class="text-[#EA580C]">🎯</text>
                </view>
                <text class="text-sm font-bold text-[#1F2937]">{{ reportData.audienceTitle }}</text>
            </view>
            <view class="flex mb-4 bg-[#F9FAFB] p-3 rounded-xl border border-solid border-[#F3F4F6]">
                <text class="text-[#9CA3AF] mr-2">📍</text>
                <text class="text-xs font-bold text-[#374151] shrink-0">截流范围：</text>
                <text class="ml-2 text-xs font-black text-[#EA580C]">{{ reportData.audienceRadius }}</text>
            </view>
            <view class="flex flex-wrap gap-2 mb-4">
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#FED7AA] text-[#C2410C] border border-solid border-[#FED7AA]"
                    ># {{ reportData.audienceTag1 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#FED7AA] text-[#C2410C] border border-solid border-[#FED7AA]"
                    ># {{ reportData.audienceTag2 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#FED7AA] text-[#C2410C] border border-solid border-[#FED7AA]"
                    ># {{ reportData.audienceTag3 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#FED7AA] text-[#C2410C] border border-solid border-[#FED7AA]"
                    ># {{ reportData.audienceTag4 }}</text
                >
            </view>
            <view
                class="text-[22rpx] text-[#6B7280] bg-[#F9FAFB] p-3 rounded-lg border-[0] border-l-2 border-solid border-[#F97316]">
                {{ reportData.audienceStrategy }}
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center justify-between mb-5">
                <view class="flex items-center">
                    <view class="w-7 h-7 rounded-lg bg-[#FFF7ED] flex items-center justify-center mr-3">
                        <text class="text-[#EA580C]">🔄</text>
                    </view>
                    <text class="text-sm font-bold text-[#1F2937]">全域获客漏斗模型</text>
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
                            <text class="text-sm font-black text-[#EA580C]">{{ reportData.funnelMetric1 }}</text>
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
                            <text class="text-sm font-black text-[#EA580C]">{{ reportData.funnelMetric2 }}</text>
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
                            <text class="text-sm font-black text-[#EA580C]">{{ reportData.funnelMetric3 }}</text>
                        </view>
                        <text class="text-[20rpx] text-[#6B7280] mt-1">{{ reportData.funnelDesc3 }}</text>
                    </view>
                </view>

                <view class="relative z-10 flex items-start pb-4">
                    <view
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#FED7AA] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <text class="text-[#EA580C]">📈</text>
                    </view>
                    <view class="pt-1">
                        <view class="flex items-baseline">
                            <text class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep4 }}</text>
                            <text class="text-sm font-black text-[#EA580C]">{{ reportData.funnelMetric4 }}</text>
                        </view>
                        <text class="text-[20rpx] text-[#6B7280] mt-1">{{ reportData.funnelDesc4 }}</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center justify-between mb-4">
                <view class="flex items-center">
                    <view class="w-7 h-7 rounded-lg bg-[#FFF7ED] flex items-center justify-center mr-3">
                        <text class="text-[#EA580C]">⏰</text>
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
                            ? 'background: linear-gradient(90deg, #f97316 0%, #dc2626 100%)'
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
                        style="background: linear-gradient(to right, #f97316 0%, #dc2626 100%)">
                        <view class="text-white font-bold text-xs">{{ task.time }}</view>
                        <view class="bg-[#ffffff]/20 text-white text-[20rpx] font-bold px-2 py-0.5 rounded">{{
                            task.platform
                        }}</view>
                    </view>
                    <view class="p-4">
                        <view class="flex items-center mb-3">
                            <view class="w-6 h-6 rounded-full bg-white flex items-center justify-center mr-2 shadow-sm">
                                <text class="text-[#EA580C]">{{ getTaskIcon(task.title) }}</text>
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

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <text class="text-sm font-bold text-[#1F2937] mb-4 text-center block">预期商业回报对比</text>
            <view class="grid grid-cols-2 gap-3">
                <view class="bg-[#F9FAFB] rounded-xl p-3 border border-solid border-[#F3F4F6] text-center">
                    <view class="text-[20rpx] text-[#9CA3AF] font-bold mb-1">传统人工运营</view>
                    <view class="text-sm font-bold text-[#374151] mb-2">{{ reportData.roiTraditionalCost }}</view>
                    <view class="text-[20rpx] text-[#6B7280]">{{ reportData.roiTraditionalEffect }}</view>
                </view>
                <view
                    class="bg-[#FFF7ED] rounded-xl p-3 border border-solid border-[#FED7AA] text-center relative overflow-hidden">
                    <view
                        class="absolute top-0 right-0 w-10 h-10 opacity-10 rounded-bl-full"
                        style="background: linear-gradient(to bottom right, #f97316 0%, #dc2626 100%)"></view>
                    <view class="text-[20rpx] text-[#EA580C] font-bold mb-1">部署AI系统</view>
                    <view
                        class="text-sm font-black mb-2"
                        style="
                            background: linear-gradient(90deg, #f97316 0%, #dc2626 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        "
                        >{{ reportData.roiAiCost }}</view
                    >
                    <view class="text-[20rpx] text-[#EA580C] font-medium">{{ reportData.roiAiEffect }}</view>
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6] text-center">
            <text class="text-xs font-bold text-[#9CA3AF] mb-4 uppercase tracking-widest block">系统每日执行量</text>
            <view class="grid grid-cols-3 gap-2 mb-4">
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadVideo }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">发视频</view>
                </view>
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadComment }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">神评论</view>
                </view>
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadDm }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">发私信</view>
                </view>
            </view>
            <view class="border-[0] border-t border-solid border-[#F3F4F6] pt-4">
                <view
                    class="text-2xl font-black mb-1.5"
                    style="
                        background: linear-gradient(90deg, #f97316 0%, #dc2626 100%);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    "
                    >500+ 动作 / 天</view
                >
                <view
                    class="text-[22rpx] font-bold text-[#4B5563] bg-[#F9FAFB] inline-block px-3 py-1 rounded-full border border-solid border-[#F3F4F6]"
                    >1个AI ≈ 10个不知疲倦的员工</view
                >
            </view>
        </view>

        <view
            class="rounded-2xl p-6 shadow-lg text-white relative overflow-hidden"
            style="background: linear-gradient(90deg, #f97316 0%, #dc2626 100%)">
            <view class="absolute top-0 right-0 w-32 h-32 bg-[#ffffff]/10 rounded-full blur-2xl -mr-10 -mt-10"></view>
            <view
                class="flex justify-between items-center mb-6 relative z-10 bg-[#000000]/20 p-3 rounded-xl backdrop-blur-sm">
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第7天</text>
                    <text class="text-xs font-bold">有人加你</text>
                </view>
                <text class="text-[#ffffff]/30">→</text>
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第15天</text>
                    <text class="text-xs font-bold">稳定咨询</text>
                </view>
                <text class="text-[#ffffff]/30">→</text>
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第30天</text>
                    <text class="text-xs font-black text-[#FEF08A]">自动进客</text>
                </view>
            </view>
            <view class="relative z-10 text-center space-y-3">
                <text class="text-xs font-medium text-[#ffffff]/90 block">老板，你缺的不是视频，也不是技巧</text>
                <text class="text-lg font-black leading-snug tracking-wide block"
                    >你缺的是一个<br /><text class="text-[#FEF08A]">每天稳定给你搞人的系统</text></text
                >
                <view class="w-10 h-px bg-[#ffffff]/30 mx-auto my-3"></view>
                <text class="text-xs font-bold text-[#ffffff]/90 leading-relaxed block"
                    >这不是爆款玩法，是流水线搞人玩法。<br />你只需要做一件事：<text
                        class="text-[#FEF08A] text-sm font-black"
                        >把人变成钱。</text
                    ></text
                >
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
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

// 任务类型
const taskTypes = [
    {
        name: "早间地域标记",
        key: TaskType.MORNING,
    },
    {
        name: "午市引流高峰",
        key: TaskType.MORNING_GROWTH,
    },
    {
        name: "下午引流准备",
        key: TaskType.AFTERNOON_PEAK,
    },
    {
        name: "晚市营销高峰",
        key: TaskType.AFTERNOON_REST,
    },
    {
        name: "深夜养号收尾",
        key: TaskType.EVENING_PEAK,
    },
];

// 完整的33个任务执行时间表
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
