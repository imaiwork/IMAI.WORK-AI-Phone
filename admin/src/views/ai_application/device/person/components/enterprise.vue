<template>
    <div class="h-full space-y-3">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <div class="flex justify-between items-center mb-4 pb-4 border-0 border-b border-solid border-[#F9FAFB]">
                <div>
                    <div class="text-[12px] font-bold text-[#9CA3AF] uppercase tracking-wider mb-1">目标客户群体</div>
                    <div
                        class="text-lg font-black"
                        style="
                            background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        ">
                        {{ reportData.target }}
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center mb-3">
                    <span class="text-xs font-bold text-[#6B7280] mr-2">当前业务状态：</span>
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
                    class="text-xs font-bold text-[#059669] bg-[#ECFDF5]/80 p-3 rounded-xl border border-solid border-[#D1FAE5] leading-relaxed flex items-start">
                    <span class="mr-2 text-base">👉</span>{{ reportData.diagnosisConclusion }}
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <div class="flex items-center mb-4">
                <div class="w-7 h-7 rounded-lg bg-[#ECFDF5] flex items-center justify-center mr-3">
                    <span class="text-[#059669]">🎯</span>
                </div>
                <span class="text-sm font-bold text-[#1F2937]">{{ reportData.audienceTitle }}</span>
            </div>
            <div class="flex mb-4 bg-[#F9FAFB] p-3 rounded-xl border border-solid border-[#F3F4F6]">
                <span class="text-[#9CA3AF] mr-2">📍</span>
                <span class="text-xs font-bold text-[#374151] shrink-0">目标人群：</span>
                <span class="ml-2 text-xs font-black text-[#059669]">{{ reportData.audienceRadius }}</span>
            </div>
            <div class="flex flex-wrap gap-2 mb-4">
                <span
                    class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[#D1FAE5] text-[#047857] border border-solid border-[#D1FAE5]"
                    ># {{ reportData.audienceTag1 }}</span
                >
                <span
                    class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[#D1FAE5] text-[#047857] border border-solid border-[#D1FAE5]"
                    ># {{ reportData.audienceTag2 }}</span
                >
                <span
                    class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[#D1FAE5] text-[#047857] border border-solid border-[#D1FAE5]"
                    ># {{ reportData.audienceTag3 }}</span
                >
                <span
                    class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[#D1FAE5] text-[#047857] border border-solid border-[#D1FAE5]"
                    ># {{ reportData.audienceTag4 }}</span
                >
            </div>
            <div
                class="text-[13px] text-[#6B7280] bg-[#F9FAFB] p-3 rounded-lg border-0 border-l-2 border-solid border-[#10B981]">
                {{ reportData.audienceStrategy }}
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center">
                    <div class="w-7 h-7 rounded-lg bg-[#ECFDF5] flex items-center justify-center mr-3">
                        <span class="text-[#059669]">🔄</span>
                    </div>
                    <span class="text-sm font-bold text-[#1F2937]">B端企业获客漏斗模型</span>
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
                            <span class="text-sm font-black text-[#059669]">{{ reportData.funnelMetric1 }}</span>
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
                            <span class="text-sm font-black text-[#059669]">{{ reportData.funnelMetric2 }}</span>
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
                            <span class="text-sm font-black text-[#059669]">{{ reportData.funnelMetric3 }}</span>
                        </div>
                        <span class="text-[12px] text-[#6B7280] mt-1 block">{{ reportData.funnelDesc3 }}</span>
                    </div>
                </div>

                <div class="relative z-10 flex items-start pb-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-white border-2 border-solid border-[#D1FAE5] shadow-sm flex items-center justify-center shrink-0 mr-4">
                        <span class="text-[#059669]">📈</span>
                    </div>
                    <div class="pt-1">
                        <div class="flex items-baseline">
                            <span class="text-sm font-bold text-[#1F2937] mr-2">{{ reportData.funnelStep4 }}</span>
                            <span class="text-sm font-black text-[#059669]">{{ reportData.funnelMetric4 }}</span>
                        </div>
                        <span class="text-[12px] text-[#6B7280] mt-1 block">{{ reportData.funnelDesc4 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6]">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-7 h-7 rounded-lg bg-[#ECFDF5] flex items-center justify-center mr-3">
                        <span class="text-[#059669]">⏰</span>
                    </div>
                    <span class="text-sm font-bold text-[#1F2937]">AI 24小时任务日历</span>
                </div>
                <div
                    class="bg-[#FEF2F2] text-[#DC2626] text-[12px] font-bold px-2 py-1 rounded border border-[#FECACA] animate-pulse">
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
                            : 'bg-[#F9FAFB] text-[#6B7280] border border-[#F3F4F6]',
                    ]"
                    :style="
                        currentTaskType === taskType.key
                            ? 'background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%)'
                            : ''
                    ">
                    {{ taskType.name }}
                </div>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(task, index) in currentTasks"
                    :key="index"
                    class="bg-[#F9FAFB] rounded-xl border border-[#F3F4F6] overflow-hidden">
                    <div
                        class="px-4 py-2.5 flex justify-between items-center"
                        style="background: linear-gradient(to right, #10b981 0%, #3b82f6 100%)">
                        <span class="text-white font-bold text-xs">{{ task.time }}</span>
                        <span
                            class="bg-white/20 text-white text-[12px] font-bold px-2 py-0.5 rounded backdrop-blur-sm"
                            >{{ task.platform }}</span
                        >
                    </div>
                    <div class="p-4">
                        <div class="flex items-center mb-3">
                            <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center mr-2 shadow-sm">
                                <span class="text-[#059669]">{{ getTaskIcon(task.title) }}</span>
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
                    <div class="text-[12px] text-[#9CA3AF] font-bold mb-1">传统商务拓展</div>
                    <div class="text-sm font-bold text-[#374151] mb-2">{{ reportData.roiTraditionalCost }}</div>
                    <div class="text-[12px] text-[#6B7280]">{{ reportData.roiTraditionalEffect }}</div>
                </div>
                <div
                    class="bg-[#ECFDF5] rounded-xl p-3 border border-solid border-[#D1FAE5] text-center relative overflow-hidden">
                    <div
                        class="absolute top-0 right-0 w-10 h-10 opacity-10 rounded-bl-full"
                        style="background: linear-gradient(to bottom right, #10b981 0%, #3b82f6 100%)"></div>
                    <div class="text-[12px] text-[#059669] font-bold mb-1">部署AI系统</div>
                    <div
                        class="text-sm font-black"
                        style="
                            background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        ">
                        {{ reportData.roiAiCost }}
                    </div>
                    <div class="text-[12px] text-[#059669] font-medium">{{ reportData.roiAiEffect }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F3F4F6] text-center">
            <span class="text-xs font-bold text-[#9CA3AF] mb-4 uppercase tracking-widest block">系统每日执行量</span>
            <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <div class="text-sm font-black text-[#1F2937]">{{ reportData.workloadVideo || "2-4条" }}</div>
                    <div class="text-[12px] text-[#6B7280] mt-0.5">发视频</div>
                </div>
                <div class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <div class="text-sm font-black text-[#1F2937]">{{ reportData.workloadComment || "100+次" }}</div>
                    <div class="text-[12px] text-[#6B7280] mt-0.5">专业评论</div>
                </div>
                <div class="bg-[#F9FAFB] p-2.5 rounded-xl border border-solid border-[#F3F4F6]">
                    <div class="text-sm font-black text-[#1F2937]">{{ reportData.workloadDm || "60+人" }}</div>
                    <div class="text-[12px] text-[#6B7280] mt-0.5">商务私信</div>
                </div>
            </div>
            <div class="border-0 border-t border-solid border-[#F3F4F6] pt-4">
                <div
                    class="text-2xl font-black mb-1.5"
                    style="
                        background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    ">
                    600+ 动作 / 天
                </div>
                <div
                    class="text-[13px] font-bold text-[#4B5563] bg-[#F9FAFB] inline-block px-3 py-1 rounded-full border border-[#F3F4F6]">
                    1个AI ≈ 3个资深商务BD
                </div>
            </div>
        </div>

        <div
            class="rounded-2xl p-6 shadow-lg text-white relative overflow-hidden"
            style="background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%)">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
            <div
                class="flex justify-between items-center mb-6 relative z-10 bg-black/20 p-3 rounded-xl backdrop-blur-sm">
                <div class="text-center px-1">
                    <span class="text-[12px] text-white/70 mb-0.5 block">第7天</span>
                    <span class="text-xs font-bold">精准获客</span>
                </div>
                <span class="text-white/30">→</span>
                <div class="text-center px-1">
                    <span class="text-[12px] text-white/70 mb-0.5 block">第15天</span>
                    <span class="text-xs font-bold">稳定商机</span>
                </div>
                <span class="text-white/30">→</span>
                <div class="text-center px-1">
                    <span class="text-[12px] text-white/70 mb-0.5 block">第30天</span>
                    <span class="text-xs font-black text-[#FEF08A]">规模合作</span>
                </div>
            </div>
            <div class="relative z-10 text-center space-y-3">
                <span class="text-xs font-medium text-white/90 block">你缺的不是专业能力，也不是服务经验</span>
                <span class="text-lg font-black leading-snug tracking-wide block">
                    你缺的是一个<br />
                    <span class="text-[#FEF08A]">B端企业专属获客引擎</span>
                </span>
                <div class="w-10 h-px bg-white/30 mx-auto my-3"></div>
                <span class="text-xs font-bold text-white/90 leading-relaxed block">
                    这不是普通获客，是高端商机对接。<br />
                    你只需要做一件事：<span class="text-[#FEF08A] text-sm font-black">专注提供优质服务。</span>
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
    NIGHT_REST = "night_rest",
}

const taskTypes = [
    { name: "早间商务标记", key: TaskType.MORNING },
    { name: "上午需求对接", key: TaskType.MORNING_GROWTH },
    { name: "午间客资拓展", key: TaskType.AFTERNOON_PEAK },
    { name: "下午商务拓展", key: TaskType.AFTERNOON_REST },
    { name: "晚间关系维护", key: TaskType.EVENING_PEAK },
    { name: "深夜值守期", key: TaskType.NIGHT_REST },
];

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
