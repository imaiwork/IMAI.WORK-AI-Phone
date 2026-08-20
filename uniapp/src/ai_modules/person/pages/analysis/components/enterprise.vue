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
                            background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        "
                        >{{ reportData.target }}</view
                    >
                </view>
                <view
                    class="shrink-0 w-10 h-10 rounded-full bg-[#ECFDF5] flex items-center justify-center border border-[#D1FAE5]">
                    <image src="@/ai_modules/person/static/icons/line2.svg" class="w-5 h-5" />
                </view>
            </view>
            <view>
                <view class="flex items-center mb-3">
                    <text class="text-xs font-bold text-[#6B7280] mr-2">当前业务状态：</text>
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
                    class="text-xs font-bold text-[#059669] bg-[#ECFDF5]/80 p-3 rounded-xl border border-solid border-[#D1FAE5] leading-relaxed flex items-start">
                    <text class="mr-2 text-base">👉</text>{{ reportData.diagnosisConclusion }}
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center mb-4">
                <view class="w-7 h-7 rounded-lg bg-[#ECFDF5] flex items-center justify-center mr-3">
                    <text class="text-[#059669]">🎯</text>
                </view>
                <text class="text-sm font-bold text-[#1F2937]">{{ reportData.audienceTitle }}</text>
            </view>
            <view class="flex mb-4 bg-[#F9FAFB] p-3 rounded-xl border border-solid border-[#F3F4F6]">
                <text class="text-[#9CA3AF] mr-2">📍</text>
                <text class="text-xs font-bold text-[#374151] shrink-0">目标人群：</text>
                <text class="ml-2 text-xs font-black text-[#059669]">{{ reportData.audienceRadius }}</text>
            </view>
            <view class="flex flex-wrap gap-2 mb-4">
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#D1FAE5] text-[#047857] border border-solid border-[#D1FAE5]"
                    ># {{ reportData.audienceTag1 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#D1FAE5] text-[#047857] border border-solid border-[#D1FAE5]"
                    ># {{ reportData.audienceTag2 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#D1FAE5] text-[#047857] border border-solid border-[#D1FAE5]"
                    ># {{ reportData.audienceTag3 }}</text
                >
                <text
                    class="text-[20rpx] font-bold px-2.5 py-1 rounded-full bg-[#D1FAE5] text-[#047857] border border-solid border-[#D1FAE5]"
                    ># {{ reportData.audienceTag4 }}</text
                >
            </view>
            <view
                class="text-[22rpx] text-[#6B7280] bg-[#F9FAFB] p-3 rounded-lg border-[0] border-l-2 border-solid border-[#10B981]">
                {{ reportData.audienceStrategy }}
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <view class="flex items-center justify-between mb-5">
                <view class="flex items-center">
                    <view class="w-7 h-7 rounded-lg bg-[#ECFDF5] flex items-center justify-center mr-3">
                        <text class="text-[#059669]">🔄</text>
                    </view>
                    <text class="text-sm font-bold text-[#1F2937]">B端企业获客漏斗模型</text>
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
                            <text class="text-sm font-black text-[#059669]">{{ reportData.funnelMetric1 }}</text>
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
                            <text class="text-sm font-black text-[#059669]">{{ reportData.funnelMetric2 }}</text>
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
                            <text class="text-sm font-black text-[#059669]">{{ reportData.funnelMetric3 }}</text>
                        </view>
                        <text class="text-[20rpx] text-[#6B7280] mt-1">{{ reportData.funnelDesc3 }}</text>
                    </view>
                </view>

                <view class="relative z-10 flex items-start pb-4">
                    <view
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#D1FAE5] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <text class="text-[#059669]">📈</text>
                    </view>
                    <view class="pt-1">
                        <view class="flex items-baseline">
                            <text class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep4 }}</text>
                            <text class="text-sm font-black text-[#059669]">{{ reportData.funnelMetric4 }}</text>
                        </view>
                        <text class="text-[20rpx] text-[#6B7280] mt-1">{{ reportData.funnelDesc4 }}</text>
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
                    v-for="(taskType, index) in taskTypes"
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
                            <view class="w-6 h-6 rounded-full bg-white flex items-center justify-center mr-2 shadow-sm">
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

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <text class="text-sm font-bold text-[#1F2937] mb-4 text-center block">预期商业回报对比</text>
            <view class="grid grid-cols-2 gap-3">
                <view class="bg-[#F9FAFB] rounded-xl p-3 border border-solid border-[#F3F4F6] text-center">
                    <view class="text-[20rpx] text-[#9CA3AF] font-bold mb-1">传统商务拓展</view>
                    <view class="text-sm font-bold text-[#374151] mb-2">{{ reportData.roiTraditionalCost }}</view>
                    <view class="text-[20rpx] text-[#6B7280]">{{ reportData.roiTraditionalEffect }}</view>
                </view>
                <view
                    class="bg-[#ECFDF5] rounded-xl p-3 border border-solid border-[#D1FAE5] text-center relative overflow-hidden">
                    <view
                        class="absolute top-0 right-0 w-10 h-10 opacity-10 rounded-bl-full"
                        style="background: linear-gradient(to bottom right, #10b981 0%, #3b82f6 100%)"></view>
                    <view class="text-[20rpx] text-[#059669] font-bold mb-1">部署AI系统</view>
                    <view
                        class="text-sm font-black"
                        style="
                            background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        "
                        >{{ reportData.roiAiCost }}</view
                    >
                    <view class="text-[20rpx] text-[#059669] font-medium">{{ reportData.roiAiEffect }}</view>
                </view>
            </view>
        </view>

        <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6] text-center">
            <text class="text-xs font-bold text-[#9CA3AF] mb-4 uppercase tracking-widest block">系统每日执行量</text>
            <view class="grid grid-cols-3 gap-2 mb-4">
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadVideo || "2-4条" }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">发视频</view>
                </view>
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadComment || "100+次" }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">专业评论</view>
                </view>
                <view class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <view class="text-sm font-black text-[#1F2937]">{{ reportData.workloadDm || "60+人" }}</view>
                    <view class="text-[20rpx] text-[#6B7280] mt-0.5">商务私信</view>
                </view>
            </view>
            <view class="border-[0] border-t border-solid border-[#F3F4F6] pt-4">
                <view
                    class="text-2xl font-black mb-1.5"
                    style="
                        background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    "
                    >600+ 动作 / 天</view
                >
                <view
                    class="text-[22rpx] font-bold text-[#4B5563] bg-[#F9FAFB] inline-block px-3 py-1 rounded-full border border-[#F3F4F6]"
                    >1个AI ≈ 3个资深商务BD</view
                >
            </view>
        </view>

        <view
            class="rounded-2xl p-6 shadow-lg text-white relative overflow-hidden"
            style="background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%)">
            <view class="absolute top-0 right-0 w-32 h-32 bg-[#ffffff]/10 rounded-full blur-2xl -mr-10 -mt-10"></view>
            <view
                class="flex justify-between items-center mb-6 relative z-10 bg-[#000000]/20 p-3 rounded-xl backdrop-blur-sm">
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第7天</text>
                    <text class="text-xs font-bold">精准获客</text>
                </view>
                <text class="text-[#ffffff]/30">→</text>
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第15天</text>
                    <text class="text-xs font-bold">稳定商机</text>
                </view>
                <text class="text-[#ffffff]/30">→</text>
                <view class="text-center px-1">
                    <text class="text-[20rpx] text-[#ffffff]/70 mb-0.5 block">第30天</text>
                    <text class="text-xs font-black text-[#FEF08A]">规模合作</text>
                </view>
            </view>
            <view class="relative z-10 text-center space-y-3">
                <text class="text-xs font-medium text-[#ffffff]/90 block">你缺的不是专业能力，也不是服务经验</text>
                <text class="text-lg font-black leading-snug tracking-wide block"
                    >你缺的是一个<br /><text class="text-[#FEF08A]">B端企业专属获客引擎</text></text
                >
                <view class="w-10 h-px bg-[#ffffff]/30 mx-auto my-3"></view>
                <text class="text-xs font-bold text-[#ffffff]/90 leading-relaxed block"
                    >这不是普通获客，是高端商机对接。<br />你只需要做一件事：<text
                        class="text-[#FEF08A] text-sm font-black"
                        >专注提供优质服务。</text
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
    NIGHT_REST = "night_rest",
}

// 任务类型
const taskTypes = [
    {
        name: "早间商务标记",
        key: TaskType.MORNING,
    },
    {
        name: "上午需求对接",
        key: TaskType.MORNING_GROWTH,
    },
    {
        name: "午间客资拓展",
        key: TaskType.AFTERNOON_PEAK,
    },
    {
        name: "下午商务拓展",
        key: TaskType.AFTERNOON_REST,
    },
    {
        name: "晚间关系维护",
        key: TaskType.EVENING_PEAK,
    },
    {
        name: "深夜值守期",
        key: TaskType.NIGHT_REST,
    },
];

// 任务执行时间表
const taskExecutionTimeTable = {
    [TaskType.MORNING]: [
        {
            time: "8:00 - 8:30",
            platform: "小红书",
            title: "自动养号",
            why: "打造商务形象",
            do: "浏览行业垂直内容（如财经、管理、制造），打牢商务标签",
            effect: "提高账号权重",
        },
        {
            time: "8:30 - 9:00",
            platform: "抖音",
            title: "自动养号",
            why: "提升账号活跃度",
            do: "搜索并浏览B端行业资讯，随机点赞",
            effect: "增加推荐概率",
        },
        {
            time: "9:00 - 9:30",
            platform: "多平台",
            title: "视频发布",
            why: "展示专业能力",
            do: "发布专业干货、工厂实拍或客户案例视频",
            effect: "吸引目标客户",
        },
        {
            time: "9:30 - 9:45",
            platform: "微信",
            title: "私信接管",
            why: "及时跟进咨询",
            do: "处理早间客户微信留言，进行初步商务对接",
            effect: "提高响应速度",
        },
    ],
    [TaskType.MORNING_GROWTH]: [
        {
            time: "9:45 - 10:45",
            platform: "视频号",
            title: "视频号自动获客",
            why: "获取精准线索",
            do: "搜索行业关键词，进入目标账号主页，通过OCR识别提取微信号并存入线索库",
            effect: "高效获取联系方式",
        },
        {
            time: "10:45 - 11:00",
            platform: "微信",
            title: "自动加好友",
            why: "转化线索",
            do: "提取上午OCR识别的线索，发送带公司名头的专业验证语（如：您好，我是XX公司的业务经理）",
            effect: "建立初步联系",
        },
        {
            time: "11:00 - 11:45",
            platform: "抖音",
            title: "截流获客",
            why: "精准获取商机",
            do: "在抖音头部商业博主或同行视频评论区，寻找问价格、求合作的企业主，直接发送私信截流",
            effect: "低成本高质量获客",
        },
        {
            time: "11:45 - 12:00",
            platform: "抖音",
            title: "私信接管",
            why: "及时跟进",
            do: "极速承接抖音截流带来的私信回复",
            effect: "提高转化率",
        },
    ],
    [TaskType.AFTERNOON_PEAK]: [
        {
            time: "12:00 - 12:30",
            platform: "微信",
            title: "朋友圈互动",
            why: "维系客户关系",
            do: "模拟真人刷朋友圈，给已有B端客户点赞评论，维系商务人脉关系",
            effect: "增强客户粘性",
        },
        {
            time: "12:30 - 13:00",
            platform: "小红书",
            title: "留痕获客",
            why: "扩大影响圈",
            do: "搜索ToB业务词（如财税/代账/设计/源头厂家），对评论区意向用户执行：点赞评论+关注+点赞其个人作品",
            effect: "增加曝光",
        },
        {
            time: "13:00 - 13:15",
            platform: "小红书",
            title: "私信接管",
            why: "承接咨询",
            do: "承接小红书留痕动作带来的咨询",
            effect: "提高转化率",
        },
        {
            time: "13:15 - 13:30",
            platform: "微信",
            title: "自动加好友",
            why: "午休高效期",
            do: "午休时间零星添加客户微信",
            effect: "提高通过率",
        },
    ],
    [TaskType.AFTERNOON_REST]: [
        {
            time: "13:30 - 14:30",
            platform: "视频号",
            title: "视频号自动获客",
            why: "持续拓展",
            do: "下午场继续深挖视频号同行，OCR提取主页微信号，持续扩充客资库",
            effect: "积累更多线索",
        },
        {
            time: "14:30 - 14:45",
            platform: "微信",
            title: "私信接管",
            why: "及时跟进",
            do: "随时跟进微信端可能通过的好友，发送公司介绍资料",
            effect: "提升转化率",
        },
        {
            time: "14:45 - 15:30",
            platform: "抖音",
            title: "截流获客",
            why: "持续获客",
            do: "下午继续在抖音同行视频评论区找精准客户发私信",
            effect: "保持获客量",
        },
        {
            time: "15:30 - 15:45",
            platform: "抖音",
            title: "私信接管",
            why: "及时响应",
            do: "及时回复抖音私信，引导添加微信",
            effect: "提高转化率",
        },
        {
            time: "15:45 - 16:30",
            platform: "视频号",
            title: "视频号获客",
            why: "增加互动",
            do: "给潜在B端客户的视频号点赞/评论，吸引对方老板查看自己的主页",
            effect: "提高关注度",
        },
        {
            time: "16:30 - 16:45",
            platform: "微信",
            title: "私信接管",
            why: "集中处理",
            do: "处理私信",
            effect: "保持高响应",
        },
        {
            time: "16:45 - 17:00",
            platform: "微信",
            title: "自动加好友",
            why: "消化线索",
            do: "消耗下午OCR提取的线索，分批添加",
            effect: "持续扩充客户库",
        },
        {
            time: "17:00 - 17:30",
            platform: "小红书",
            title: "自动养号",
            why: "提升权重",
            do: "浏览B端商业笔记，提升小红书账号权重",
            effect: "增加推荐概率",
        },
        {
            time: "17:30 - 17:45",
            platform: "小红书",
            title: "私信接管",
            why: "下班前收尾",
            do: "下班前检查小红书私信",
            effect: "不错过任何线索",
        },
        {
            time: "17:45 - 18:00",
            platform: "微信",
            title: "私信接管",
            why: "日常收尾",
            do: "下班前集中处理所有商务咨询，做今日收尾",
            effect: "提高客户满意度",
        },
    ],
    [TaskType.EVENING_PEAK]: [
        {
            time: "18:00 - 18:15",
            platform: "微信",
            title: "朋友圈发布",
            why: "晚间触达",
            do: "发布公司日常、发货视频或行业见解，触达私域客户",
            effect: "维护客户关系",
        },
        {
            time: "18:15 - 18:30",
            platform: "微信",
            title: "朋友圈互动",
            why: "增强互动",
            do: "与客户朋友圈互动",
            effect: "提高活跃度",
        },
        {
            time: "18:30 - 19:30",
            platform: "视频号",
            title: "视频号获客",
            why: "晚间高效期",
            do: "晚间挂机看行业直播，提升账号权重",
            effect: "增加曝光机会",
        },
        {
            time: "19:30 - 20:00",
            platform: "抖音",
            title: "自动养号",
            why: "提升权重",
            do: "晚间浏览商业类长视频",
            effect: "增加账号活跃度",
        },
        {
            time: "20:00 - 20:15",
            platform: "抖音",
            title: "私信接管",
            why: "晚间收尾",
            do: "晚间红点清理",
            effect: "保持高响应",
        },
        {
            time: "20:15 - 20:30",
            platform: "小红书",
            title: "私信接管",
            why: "晚间收尾",
            do: "晚间红点清理",
            effect: "保持高响应",
        },
    ],
    [TaskType.NIGHT_REST]: [
        {
            time: "20:30 - 20:45",
            platform: "微信",
            title: "私信接管",
            why: "轻度维护",
            do: "晚间微信端轻度维护",
            effect: "保持客户体验",
        },
        {
            time: "20:45 - 21:00",
            platform: "微信",
            title: "自动加好友",
            why: "最后添加",
            do: "全天最后一次添加，消化今日剩余线索",
            effect: "完成日常获客",
        },
        {
            time: "21:00 - 23:00",
            platform: "微信",
            title: "私信接管",
            why: "夜间值守",
            do: "处理晚间可能的商务咨询",
            effect: "24小时响应",
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
        专业内容发布: "📹",
        私信接管: "🔥",
        视频号自动获客: "🎯",
        自动加好友: "➕",
        截流获客: "🎯",
        留痕获客: "👀",
        朋友圈互动: "❤️",
        朋友圈发布: "📱",
        视频号获客: "🎯",
        高端获客: "💼",
        法务标记: "⚖️",
    };
    return iconMap[title] || "🔥";
};
</script>

<style scoped></style>
