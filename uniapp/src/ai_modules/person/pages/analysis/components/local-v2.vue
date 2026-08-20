<template>
    <view class="pb-6">
        <view class="bg-white py-10 px-5 text-center relative overflow-hidden rounded-b-3xl shadow-sm">
            <view
                class="absolute top-0 left-0 w-full h-[6rpx]"
                style="background: linear-gradient(90deg, #22d3ee 0%, #3b82f6 100%)"></view>
            <view class="text-[44rpx] font-extrabold text-[#1E293B] mb-4 leading-tight">
                <text>专为 </text>
                <text class="text-[#2563EB]">{{ reportData.ipName }}</text>
                <text>{{ "\n" }}定制的同城获客系统</text>
            </view>
            <view
                class="bg-[#ECFEFF] inline-block px-4 py-3 rounded-2xl border border-solid border-[#CFFAFE] text-left">
                <text class="text-[#4B5563] leading-relaxed">
                    🏂 <text class="font-bold text-[#0E7490]">{{ reportData.heroTag }}：</text>{{ "\n"
                    }}{{ reportData.heroDescription }}
                </text>
            </view>
        </view>

        <view class="mt-6 space-y-6">
            <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F1F5F9]">
                <view class="flex items-center gap-2 mb-5 pb-3 border-[0] border-b border-solid border-[#F1F5F9]">
                    <view class="w-7 h-7 rounded-lg bg-[#ECFEFF] flex items-center justify-center">
                        <text class="text-[#0E7490]">⚓</text>
                    </view>
                    <text class="text-[32rpx] font-bold text-[#1E293B]">{{ reportData.logicTitle }}</text>
                </view>

                <view class="space-y-6">
                    <view class="flex gap-4 items-start">
                        <view
                            class="w-10 h-10 bg-[#ECFEFF] rounded-full flex items-center justify-center shrink-0 mt-1">
                            <text>🛡️</text>
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
                            class="w-10 h-10 bg-[#EFF6FF] rounded-full flex items-center justify-center shrink-0 mt-1">
                            <text>📍</text>
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
                class="bg-[#ECFEFF] rounded-2xl p-5 border border-solid border-[#CFFAFE]"
                style="background: rgba(236, 254, 255, 0.7)">
                <view class="flex items-center gap-2 mb-5">
                    <view class="w-7 h-7 rounded-lg bg-[#CFFAFE] flex items-center justify-center">
                        <text class="text-[#0E7490]">⚡</text>
                    </view>
                    <text class="text-[32rpx] font-bold text-[#164E63]">{{ reportData.targetAudienceTitle }}</text>
                </view>

                <view class="bg-white p-4 rounded-xl shadow-sm border border-solid border-[#F1F5F9] mb-4">
                    <view class="space-y-4">
                        <view class="pb-4 border-[0] border-b border-solid border-[#F8FAFC]">
                            <view class="inline-block bg-[#F1F5F9] px-3 py-1 rounded mb-2">
                                <text class="text-[22rpx] font-bold text-[#334155]">{{
                                    reportData.platform1Name
                                }}</text>
                            </view>
                            <text class="text-[#64748B] text-xs leading-relaxed block">
                                {{ reportData.platform1Desc }}
                            </text>
                        </view>
                        <view class="pb-4 border-[0] border-b border-solid border-[#F8FAFC]">
                            <view class="inline-block bg-[#FEF2F2] px-3 py-1 rounded mb-2">
                                <text class="text-[22rpx] font-bold text-[#DC2626]">{{
                                    reportData.platform2Name
                                }}</text>
                            </view>
                            <text class="text-[#64748B] text-xs leading-relaxed block">
                                {{ reportData.platform2Desc }}
                            </text>
                        </view>
                        <view>
                            <view class="inline-block bg-[#F0FDF4] px-3 py-1 rounded mb-2">
                                <text class="text-[22rpx] font-bold text-[#16A34A]">{{
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
                    style="background: linear-gradient(90deg, #06b6d4 0%, #3b82f6 100%)">
                    <view class="flex items-center gap-2 mb-2">
                        <text class="text-white font-bold">🤖 {{ reportData.autoHookTitle }}</text>
                    </view>
                    <text class="text-[#ECFEFF] text-xs leading-relaxed">
                        {{ reportData.autoHookDesc }}
                    </text>
                </view>
            </view>

            <view class="bg-white rounded-2xl p-5 shadow-sm border border-solid border-[#F1F5F9]">
                <view class="flex items-center gap-2 mb-6">
                    <view class="w-7 h-7 rounded-lg bg-[#EFF6FF] flex items-center justify-center">
                        <text class="text-[#2563EB]">📈</text>
                    </view>
                    <text class="text-[32rpx] font-bold text-[#1E293B]">{{ reportData.funnelTitle }}</text>
                </view>

                <view class="space-y-3 relative">
                    <view class="absolute left-[38rpx] top-[80rpx] bottom-[80rpx] w-[2rpx] bg-[#F1F5F9] z-0"></view>

                    <view
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <view class="w-10 h-10 bg-[#ECFEFF] rounded-full flex items-center justify-center shrink-0">
                            <text class="text-[#0E7490] font-bold text-[32rpx]">1</text>
                        </view>
                        <view class="flex-1">
                            <text class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel1Title }}</text>
                            <text class="text-[#94A3B8] text-[22rpx]">{{ reportData.funnel1Desc }}</text>
                        </view>
                    </view>

                    <view
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <view class="w-10 h-10 bg-[#EFF6FF] rounded-full flex items-center justify-center shrink-0">
                            <text class="text-[#2563EB] font-bold text-[32rpx]">2</text>
                        </view>
                        <view class="flex-1">
                            <text class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel2Title }}</text>
                            <text class="text-[#94A3B8] text-[22rpx]">{{ reportData.funnel2Desc }}</text>
                        </view>
                    </view>

                    <view
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <view class="w-10 h-10 bg-[#EEF2FF] rounded-full flex items-center justify-center shrink-0">
                            <text class="text-[#4F46E5] font-bold text-[32rpx]">3</text>
                        </view>
                        <view class="flex-1">
                            <text class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel3Title }}</text>
                            <text class="text-[#94A3B8] text-[22rpx]">{{ reportData.funnel3Desc }}</text>
                        </view>
                    </view>

                    <view
                        class="relative z-10 flex gap-3 items-center bg-[#F8FAFC] p-4 rounded-xl border border-solid border-[#F1F5F9]">
                        <view class="w-10 h-10 bg-[#F0FDF4] rounded-full flex items-center justify-center shrink-0">
                            <text class="text-[#16A34A] font-bold text-[32rpx]">4</text>
                        </view>
                        <view class="flex-1">
                            <text class="font-bold text-[#1E293B] block mb-1">{{ reportData.funnel4Title }}</text>
                            <text class="text-[#94A3B8] text-[22rpx]">{{ reportData.funnel4Desc }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- ✅ AI 24小时任务日历（动态版） -->
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

                <!-- Tab 切换（只展示有数据的时段） -->
                <view class="flex overflow-x-auto pb-2 mb-4 space-x-2">
                    <view
                        v-for="taskType in availableTaskTypes"
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

                <!-- 空状态 -->
                <view v-if="currentTasks.length === 0" class="py-10 text-center text-[#94A3B8] text-sm">
                    该时段暂无任务安排
                </view>

                <!-- 任务列表 -->
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
                                <view
                                    class="w-6 h-6 rounded-full bg-white flex items-center justify-center mr-2 shadow-sm">
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

            <view
                class="rounded-2xl p-6 text-white shadow-xl relative overflow-hidden"
                style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%)">
                <view
                    class="absolute top-[-80rpx] left-[-80rpx] w-[300rpx] h-[300rpx] rounded-full opacity-30"
                    style="background: radial-gradient(circle, #06b6d4 0%, transparent 70%)"></view>
                <view
                    class="absolute bottom-[-80rpx] right-[-80rpx] w-[300rpx] h-[300rpx] rounded-full opacity-30"
                    style="background: radial-gradient(circle, #3b82f6 0%, transparent 70%)"></view>

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
                            <text class="text-[22rpx] text-[#67E8F9]">阶段一</text>
                            <text class="font-bold text-white">{{ reportData.phase1 }}</text>
                        </view>
                        <view
                            class="flex justify-between items-center px-4 py-3 rounded-xl border border-solid border-[#ffffff1a]"
                            style="background: rgba(255, 255, 255, 0.08)">
                            <text class="text-[22rpx] text-[#67E8F9]">阶段二</text>
                            <text class="font-bold text-white">{{ reportData.phase2 }}</text>
                        </view>
                        <view
                            class="flex justify-between items-center px-4 py-3 rounded-xl border border-solid border-[#ffffff1a]"
                            style="background: rgba(255, 255, 255, 0.08)">
                            <text class="text-[22rpx] text-[#67E8F9]">阶段三</text>
                            <text class="font-bold text-[#FDE047]">{{ reportData.phase3 }}</text>
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
            [TaskType.MORNING]: "早间地域标记",
            [TaskType.MORNING_GROWTH]: "午市引流高峰",
            [TaskType.AFTERNOON_PEAK]: "午间收尾",
            [TaskType.AFTERNOON_REST]: "下午引流准备",
            [TaskType.EVENING_PEAK]: "晚市营销高峰",
        },
        extraIconMap: {
            私信接管: "💬",
            "私信接管（拉群）": "💬",
            评论接管: "🗨️",
            同城曝光: "📡",
            同城触达: "📍",
            同城截流: "🎯",
            团购截流: "🛒",
        },
        extraSceneDescMap: {
            3: {
                why: "增加在同城精准人群中的曝光度",
                do: "对符合画像的本地作者执行：关注+点赞+评论",
                effect: "吸引同城潜在客户关注，建立初步连接",
            },
            4: {
                why: "借助视频号公域流量获取本地客资",
                do: "在视频号发布内容并与微信好友互动",
                effect: "打通公私域，扩大本地获客渠道",
            },
            7: {
                why: "打造门店IP人设，增强信任感",
                do: "发布门店日常或菜品视频",
                effect: "增强私域客户的信任感",
            },
            8: {
                why: "唤醒沉睡老客户，刺激复购消费",
                do: "自动给微信好友的朋友圈点赞/评论",
                effect: "刷存在感，促使周边老客临时决定复购",
            },
            9: {
                why: "将公域高意向食客沉淀到私域",
                do: "发送好友请求，附带门店定位或专属话术",
                effect: "方便发送定位，建立长期复购关系",
            },
            10: {
                why: "提升账号活跃度与本地标签权重",
                do: "浏览同城相关图文/视频，模拟正常用户行为",
                effect: "给账号打上强烈的本地地域标签",
            },
            12: {
                why: "提升同城用户曝光，强化本地生活属性",
                do: "自动浏览同城视频流并随机点赞完播",
                effect: "提升账号在本地同城圈的自然流量",
            },
            13: {
                why: "精准获取有消费意向的本地客户",
                do: "寻找本地目标用户并进行互动截流",
                effect: "抢夺竞品客流，获取高转化率精准订单",
            },
            14: {
                why: "强化本地生活属性，提升团购推荐权重",
                do: "高频执行同城关注、点赞、评论当前作品",
                effect: "强力曝光门店，刺激周边用户即时消费",
            },
        },
    });
</script>

<style scoped></style>
