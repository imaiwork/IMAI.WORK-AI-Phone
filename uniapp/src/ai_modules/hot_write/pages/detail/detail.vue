<template>
    <view class="flex flex-col h-screen" style="background: #f7f8fa">
        <u-navbar title="创作详情" :border-bottom="false" :background="{ background: 'transparent' }"></u-navbar>
        <template v-if="isLoading">
            <view
                class="mx-[24rpx] mt-[16rpx] mb-[28rpx] rounded-[36rpx] overflow-hidden shrink-0 skeleton-card"
                style="background: #fff; box-shadow: 0 8px 40px rgba(0, 101, 251, 0.08)">
                <view class="flex items-center gap-[24rpx] px-[32rpx] pt-[28rpx] pb-[20rpx]">
                    <view class="w-[96rpx] h-[96rpx] rounded-[22rpx] skeleton-block flex-shrink-0"></view>
                    <view class="flex-1 flex flex-col gap-[16rpx]">
                        <view class="h-[32rpx] rounded-[8rpx] skeleton-block" style="width: 75%"></view>
                        <view class="flex gap-[12rpx]">
                            <view class="h-[36rpx] w-[120rpx] rounded-full skeleton-block"></view>
                            <view class="h-[36rpx] w-[80rpx] rounded-full skeleton-block"></view>
                        </view>
                    </view>
                </view>
                <view class="px-[32rpx] pb-[28rpx]">
                    <view class="flex justify-between mb-[10rpx]">
                        <view class="h-[22rpx] w-[80rpx] rounded-[6rpx] skeleton-block"></view>
                        <view class="h-[22rpx] w-[60rpx] rounded-[6rpx] skeleton-block"></view>
                    </view>
                    <view class="h-[10rpx] rounded-full skeleton-block"></view>
                </view>
            </view>
            <view class="grow min-h-0 overflow-hidden">
                <view class="flex flex-col px-[24rpx]">
                    <view
                        v-for="i in 6"
                        :key="i"
                        class="flex skeleton-item"
                        :style="{ animationDelay: `${(i - 1) * 0.08}s` }">
                        <view class="flex flex-col items-center w-[60rpx] flex-shrink-0">
                            <view class="w-[48rpx] h-[48rpx] rounded-[16rpx] skeleton-block flex-shrink-0"></view>
                            <view v-if="i < 6" class="w-[2rpx] flex-1 min-h-[60rpx] mt-[6rpx] skeleton-line"></view>
                        </view>
                        <view class="flex-1 pl-[18rpx] pb-[4rpx]" style="padding-top: 10rpx">
                            <view class="flex items-center gap-[12rpx] mb-[14rpx]">
                                <view
                                    class="h-[30rpx] rounded-[8rpx] skeleton-block"
                                    :style="{ width: titleWidths[i - 1] }"></view>
                                <view v-if="i % 3 === 0" class="h-[28rpx] w-[56rpx] rounded-full skeleton-block"></view>
                            </view>
                            <view
                                class="rounded-[18rpx] px-[22rpx] py-[18rpx] mb-[20rpx] skeleton-block"
                                :style="{ height: contentHeights[i - 1] }"></view>
                        </view>
                    </view>
                </view>
            </view>
        </template>

        <template v-else>
            <view
                class="mx-[24rpx] mt-[16rpx] mb-[28rpx] rounded-[36rpx] overflow-hidden shrink-0 content-fade-in"
                style="background: #fff; box-shadow: 0 8px 40px rgba(0, 101, 251, 0.13)">
                <view class="flex items-center gap-[24rpx] px-[32rpx] pt-[28rpx] pb-[20rpx]">
                    <view class="relative w-[96rpx] h-[96rpx] flex-shrink-0">
                        <view
                            class="w-full h-full rounded-[22rpx] overflow-hidden"
                            style="box-shadow: 0 6px 20px rgba(0, 101, 251, 0.2)">
                            <image
                                src="@/ai_modules/hot_write/static/images/common/video_copy.png"
                                mode="aspectFill"
                                class="w-full h-full" />
                        </view>
                        <view
                            class="absolute flex items-center justify-center rounded-full"
                            style="
                                width: 32rpx;
                                height: 32rpx;
                                background: linear-gradient(135deg, #0065fb, #3d8bfc);
                                bottom: -6rpx;
                                right: -6rpx;
                                box-shadow: 0 2px 8px rgba(0, 101, 251, 0.4);
                            ">
                            <text class="text-white" style="font-size: 16rpx; font-weight: 800">AI</text>
                        </view>
                    </view>
                    <view class="flex-1">
                        <text class="text-[28rpx] font-bold text-[#111827] block mb-[14rpx]" style="line-height: 1.5">
                            {{ taskDetail.title }}
                        </text>
                        <view class="flex items-center gap-[10rpx] flex-wrap">
                            <view
                                v-if="taskStatus === 'running'"
                                class="flex items-center gap-[8rpx] px-[18rpx] py-[7rpx] rounded-full"
                                style="background: linear-gradient(135deg, #eff6ff, #dbeafe)">
                                <view
                                    class="w-[10rpx] h-[10rpx] rounded-full step-pulse"
                                    style="background: #0065fb"></view>
                                <text class="text-[21rpx] font-bold" style="color: #0065fb">执行中</text>
                            </view>
                            <view
                                v-else
                                class="flex items-center gap-[8rpx] px-[18rpx] py-[7rpx] rounded-full"
                                style="background: linear-gradient(135deg, #eff6ff, #dbeafe)">
                                <u-icon name="checkmark-circle-fill" color="#0065fb" size="20"></u-icon>
                                <text class="text-[21rpx] font-bold" style="color: #0065fb">已完成</text>
                            </view>
                            <text class="text-[21rpx]" style="color: #9ca3af">
                                {{ doneCount }}/{{ allSteps.length }} 步骤
                            </text>
                        </view>
                    </view>
                </view>
                <view class="px-[32rpx] pb-[28rpx]">
                    <view class="flex justify-between mb-[10rpx]">
                        <text class="text-[20rpx]" style="color: #9ca3af">任务进度</text>
                        <text class="text-[20rpx] font-bold" style="color: #0065fb">
                            {{ Math.round((doneCount / allSteps.length) * 100) }}%
                        </text>
                    </view>
                    <view class="h-[10rpx] rounded-full w-full" style="background: #f3f4f6">
                        <view
                            class="h-full rounded-full transition-all duration-700"
                            style="background: linear-gradient(90deg, #0065fb, #3d8bfc, #93c5fd)"
                            :style="{ width: progressWidth }"></view>
                    </view>
                </view>
            </view>

            <view class="grow min-h-0">
                <scroll-view scroll-y class="h-full" :scroll-top="scrollTop" :scroll-with-animation="true">
                    <view class="flex flex-col px-[24rpx] pb-[300rpx]">
                        <view
                            v-for="(step, index) in groupA"
                            :key="step.id"
                            class="flex content-fade-in"
                            :style="{ animationDelay: `${index * 0.06}s` }">
                            <view class="flex flex-col items-center w-[60rpx] flex-shrink-0">
                                <view
                                    class="w-[48rpx] h-[48rpx] rounded-[16rpx] flex items-center justify-center flex-shrink-0 z-10"
                                    :style="iconStyle(step.status)">
                                    <u-icon
                                        v-if="step.status === 'done'"
                                        name="checkmark"
                                        color="#fff"
                                        size="22"></u-icon>
                                    <view
                                        v-else-if="step.status === 'running'"
                                        class="w-[14rpx] h-[14rpx] rounded-full bg-white step-pulse"></view>
                                    <u-icon
                                        v-else-if="step.status === 'confirm'"
                                        name="edit-pen"
                                        color="#fff"
                                        size="22"></u-icon>
                                    <text v-else class="font-bold" style="color: #d1d5db; font-size: 22rpx">{{
                                        step.id
                                    }}</text>
                                </view>
                                <view
                                    v-if="index < groupA.length - 1 || showGroupB"
                                    class="w-[2rpx] flex-1 min-h-[24rpx] mt-[6rpx]"
                                    :style="lineStyle(step.status)"></view>
                            </view>
                            <view class="flex-1 pl-[18rpx] mb-[4rpx]" style="padding-top: 10rpx">
                                <view class="flex items-center gap-[10rpx] mb-[8rpx]">
                                    <text
                                        class="text-[27rpx] font-bold"
                                        :style="step.status === 'pending' ? 'color:#D1D5DB' : 'color:#111827'">
                                        {{ step.title }}
                                    </text>
                                    <view
                                        v-if="step.tag"
                                        class="px-[14rpx] py-[4rpx] rounded-full"
                                        :style="tagStyle(step.tag)">
                                        <text class="text-[18rpx] font-extrabold">{{ step.tag }}</text>
                                    </view>
                                    <view
                                        v-if="step.status === 'confirm'"
                                        class="px-[14rpx] py-[4rpx] rounded-full ml-auto"
                                        style="background: #fff7ed; border: 1rpx solid #fed7aa">
                                        <text class="text-[18rpx] font-bold" style="color: #f59e0b">待确认</text>
                                    </view>
                                </view>
                                <step-content
                                    :step="step"
                                    @confirm="onConfirmGroupA"
                                    @reject="onReject"
                                    @focus="onTextareaFocus" />
                            </view>
                        </view>

                        <view v-if="showGroupB" class="flex items-center gap-[16rpx] my-[24rpx] group-b-enter">
                            <view
                                class="flex-1 h-[1rpx]"
                                style="background: linear-gradient(90deg, transparent, #bfdbfe)"></view>
                            <view
                                class="flex items-center gap-[8rpx] px-[20rpx] py-[8rpx] rounded-full"
                                style="background: linear-gradient(135deg, #0065fb, #3d8bfc)">
                                <text class="text-[20rpx] font-bold text-white">视频合成阶段</text>
                            </view>
                            <view
                                class="flex-1 h-[1rpx]"
                                style="background: linear-gradient(90deg, #bfdbfe, transparent)"></view>
                        </view>

                        <template v-if="showGroupB">
                            <view v-for="(step, index) in groupB" :key="step.id" class="flex group-b-enter">
                                <view class="flex flex-col items-center w-[60rpx] flex-shrink-0">
                                    <view
                                        class="w-[48rpx] h-[48rpx] rounded-[16rpx] flex items-center justify-center flex-shrink-0 z-10"
                                        :style="iconStyle(step.status)">
                                        <u-icon
                                            v-if="step.status === 'done'"
                                            name="checkmark"
                                            color="#fff"
                                            size="22"></u-icon>
                                        <view
                                            v-else-if="step.status === 'running'"
                                            class="w-[14rpx] h-[14rpx] rounded-full bg-white step-pulse"></view>
                                        <u-icon
                                            v-else-if="step.status === 'confirm'"
                                            name="edit-pen"
                                            color="#fff"
                                            size="22"></u-icon>
                                        <text v-else class="font-bold" style="color: #d1d5db; font-size: 22rpx">{{
                                            step.id
                                        }}</text>
                                    </view>
                                    <view
                                        v-if="index < groupB.length - 1"
                                        class="w-[2rpx] flex-1 min-h-[24rpx] mt-[6rpx]"
                                        :style="lineStyle(step.status)"></view>
                                </view>
                                <view class="flex-1 pl-[18rpx] mb-[4rpx]" style="padding-top: 10rpx">
                                    <view class="flex items-center gap-[10rpx] mb-[8rpx]">
                                        <text
                                            class="text-[27rpx] font-bold"
                                            :style="step.status === 'pending' ? 'color:#D1D5DB' : 'color:#111827'">
                                            {{ step.title }}
                                        </text>
                                        <view
                                            v-if="step.tag"
                                            class="px-[14rpx] py-[4rpx] rounded-full"
                                            :style="tagStyle(step.tag)">
                                            <text class="text-[18rpx] font-extrabold">{{ step.tag }}</text>
                                        </view>
                                        <view
                                            v-if="step.status === 'confirm'"
                                            class="px-[14rpx] py-[4rpx] rounded-full ml-auto"
                                            style="background: #fff7ed; border: 1rpx solid #fed7aa">
                                            <text class="text-[18rpx] font-bold" style="color: #f59e0b">待确认</text>
                                        </view>
                                    </view>
                                    <step-content
                                        :step="step"
                                        @watch-video="onWatchVideo"
                                        @confirm="onConfirmGroupB"
                                        @reject="onReject"
                                        @focus="onTextareaFocus" />
                                </view>
                            </view>
                        </template>
                    </view>
                </scroll-view>
            </view>
        </template>
    </view>
    <video-preview-v2 v-model:show="showVideoPreview" :video-url="videoUrl" />
</template>

<script setup lang="ts">
import { getHotWriteDetail, generateVideo, confirmPublishText } from "@/api/hot_write";
import { generateMatrixPrompt } from "@/api/device";
import { getCopyWritingGenerate } from "@/api/agent";
import usePolling from "@/hooks/usePolling";
import { formatAudioTime } from "@/utils/util";
import StepContent from "./components/step-content.vue";

type StepStatus = "done" | "running" | "confirm" | "pending";
type DoneType = "text" | "tags" | "content" | "kv";

interface Step {
    id: number;
    title: string;
    desc: string;
    status: StepStatus;
    tag?: string;
    doneType?: DoneType;
    doneText?: string;
    meta?: string[];
    doneTags?: string[];
    hashtags?: string[];
    needConfirm?: boolean;
    confirmContent?: string;
    confirmLabel?: string;
    rejectLabel?: string;
    videoUrl?: string;
}

const enum TaskStatus {
    Pending = 1,
    VideoGenerating = 2,
    WaitingPublishConfirm = 3,
    Done = 4,
}

const showVideoPreview = ref(false);
const videoUrl = ref("");

const titleWidths = ["45%", "60%", "55%", "50%", "65%", "40%"];
const contentHeights = ["80rpx", "120rpx", "180rpx", "80rpx", "80rpx", "100rpx"];

const getAllSteps = () => {
    const { is_material } = taskDetail.value;
    if (is_material === 1) {
        return buildInitialSteps().filter((s) => s.id !== 8);
    } else {
        return buildInitialSteps();
    }
};

const buildInitialSteps = (): Step[] => [
    { id: 1, title: "创建任务", desc: "AI分配成功", status: "pending", doneType: "text", meta: [] },
    { id: 2, title: "提取文案", desc: "正在解析视频链接内容...", status: "pending", doneType: "text", doneText: "" },
    {
        id: 3,
        title: "原文案内容",
        desc: "正在清洗并提取原文案...",
        status: "pending",
        doneType: "content",
        doneText: "",
        confirmContent: "",
        confirmLabel: "确认文案",
        rejectLabel: "换一个",
    },
    { id: 4, title: "爆款分析", desc: "AI 分析中...", status: "pending", doneType: "tags", doneTags: [], tag: "AI" },
    { id: 5, title: "违禁词检查", desc: "全网合规性扫描中...", status: "pending", doneType: "text", doneText: "" },
    { id: 6, title: "人设分析", desc: "正在分析当前IP人设风格...", status: "pending", doneType: "kv", doneText: "" },
    {
        id: 7,
        title: "爆款仿写",
        desc: "AI 正在逐句重写爆款文案...",
        status: "pending",
        doneType: "content",
        doneText: "",
        needConfirm: true,
        confirmContent: "",
        confirmLabel: "确认文案",
        rejectLabel: "换一个",
        tag: "AI",
    },
    { id: 8, title: "匹配形象", desc: "正在检索合成形象...", status: "pending", doneType: "kv", doneText: "" },
    { id: 9, title: "匹配音色", desc: "正在检索合成音色...", status: "pending", doneType: "kv", doneText: "" },
    {
        id: 10,
        title: "合成视频",
        desc: "AI正在合成视频剪辑...",
        status: "pending",
        doneType: "kv",
        doneText: "",
        tag: "AI",
        videoUrl: "",
    },
    {
        id: 11,
        title: "云端剪辑包装",
        desc: "AI正在添加花字与BGM...",
        status: "pending",
        doneType: "tags",
        doneTags: ["背景音乐", "动态字幕", "特效转场"],
        tag: "AI",
    },
    {
        id: 12,
        title: "生成发布文案",
        desc: "AI 正在包装标题与标签...",
        status: "pending",
        doneType: "content",
        doneText: "",
        hashtags: [],
        needConfirm: true,
        confirmContent: "",
        confirmLabel: "确认文案",
        rejectLabel: "换一个",
        tag: "AI",
    },
    {
        id: 13,
        title: "视频完成",
        desc: "渲染成功，已保存至创作记录",
        status: "pending",
        doneType: "text",
        doneText: "",
    },
];

const allSteps = ref<Step[]>([]);
const taskId = ref<string>("");
const taskDetail = ref<any>({});
const taskStatus = ref<"running" | "done">("running");
const isLoading = ref(true);
const showGroupB = ref(false);
const scrollTop = ref(0);

const groupA = computed(() => allSteps.value.filter((s) => s.id <= 7));
const groupB = computed(() => allSteps.value.filter((s) => s.id > 7));

const doneCount = computed(() => allSteps.value.filter((s) => s.status === "done").length || 0);
const progressWidth = computed(() => `${Math.round((doneCount.value / allSteps.value.length) * 100)}%`);

const initStepsFromDetail = (res: any) => {
    const steps = allSteps.value;
    const find = (id: number) => steps.find((s) => s.id === id)!;

    find(1).meta = [`任务ID: ${res.id}`, "AI分配成功"];
    find(2).doneText = `成功解析目标链接，共提取 ${res.original_text?.length || 0} 字`;
    find(3).doneText = res.original_text;
    find(3).confirmContent = res.original_text;
    find(4).doneTags = res.analysis_tags;
    find(5).doneText = res.compliance_status;
    find(6).doneText = `匹配人设：${res.persona_tone}`;
    find(7).doneText = res.rewritten_text;
    find(7).confirmContent = res.rewritten_text;

    if (taskDetail.value.is_material === 0) {
        find(8).doneText = res.avatar_name ? `已选择形象：数字人${res.avatar_name} (绑定形象)` : "正在检索合成形象...";
    }
    find(9).doneText = res.voice_name ? `匹配音色：${res.voice_name}` : "正在检索合成音色...";
    find(10).doneText = res.video_url
        ? `数字人口播混剪视频；时长${formatAudioTime(res.duration || 0)}秒`
        : "正在合成视频...";
    find(10).videoUrl = res.video_url;
    find(12).doneText = res.publish_text ? res.publish_text : "正在包装标题与标签...";
    find(12).confirmContent = res.publish_text ? res.publish_text : "";
    find(12).hashtags = res.publish_topic ? JSON.parse(res.publish_topic) : [];

    if (res.status === TaskStatus.Pending) {
        steps.filter((s) => s.id >= 1 && s.id <= 6).forEach((s) => (s.status = "done"));
        find(7).status = "confirm";
    } else if (
        res.status === TaskStatus.VideoGenerating ||
        res.status === TaskStatus.WaitingPublishConfirm ||
        res.publish_confirm === 1
    ) {
        steps.filter((s) => s.id >= 1 && s.id <= 11).forEach((s) => (s.status = "done"));
        const isDone = res.publish_confirm === 1;
        if (res.publish_text == "") {
            find(12).status = "pending";
        } else {
            find(12).status = isDone ? "done" : "confirm";
        }
        find(13).status = isDone ? "done" : "pending";
        showGroupB.value = true;
        taskStatus.value = res.video_url && isDone ? "done" : "running";
    }
};

const getDetail = async () => {
    const res = await getHotWriteDetail({ id: taskId.value });
    taskDetail.value = res;
};

const scrollToBottom = () => {
    nextTick(() => {
        scrollTop.value = 0;
        nextTick(() => {
            scrollTop.value = 999999;
        });
    });
};

const onTextareaFocus = () => {
    setTimeout(() => scrollToBottom(), 300);
};

const onWatchVideo = (step: Step) => {
    videoUrl.value = step.videoUrl || "";
    showVideoPreview.value = true;
};

const onConfirmGroupA = async (step: Step) => {
    step.doneText = step.confirmContent;
    if (!step.confirmContent) {
        uni.showToast({ title: "请输入发布文案", icon: "none", duration: 3000 });
        return;
    }
    uni.showLoading({ title: "生成视频中...", mask: true });
    try {
        await generateVideo({ id: taskId.value, rewritten_text: step.confirmContent });
        await getDetail();
        initStepsFromDetail(taskDetail.value);
        step.status = "done";
        showGroupB.value = true;
        allSteps.value.filter((s) => s.id >= 8 && s.id <= 11).forEach((s) => (s.status = "done"));
        allSteps.value.find((s) => s.id === 12)!.status = "confirm";
        scrollToBottom();
    } catch {
        uni.showToast({ title: "生成视频失败", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

const onConfirmGroupB = async (step: Step) => {
    step.doneText = step.confirmContent;
    if (!step.confirmContent) {
        uni.showToast({ title: "请输入发布文案", icon: "none", duration: 3000 });
        return false;
    }
    uni.showLoading({ title: "确认发布文案中...", mask: true });
    try {
        await confirmPublishText({
            id: taskId.value,
            publish_text: step.confirmContent,
            publish_topic: JSON.stringify(step.hashtags),
        });
        step.status = "done";
        allSteps.value.find((s) => s.id === 13)!.status = "done";
        taskStatus.value = "done";
        scrollToBottom();
        uni.hideLoading();
    } catch (error) {
        uni.hideLoading();
        uni.showToast({ title: "确认发布文案失败", icon: "none", duration: 3000 });
    }
};

const onReject = async (step: Step) => {
    step.status = "running";
    if (!step.doneText) {
        uni.showToast({ title: "请先输入文案", icon: "none", duration: 3000 });
        return;
    }
    uni.showLoading({ title: "重新生成中...", mask: true });
    try {
        if (step.id === 7) {
            const res = await getCopyWritingGenerate({
                sn: 6,
                keywords: step.doneText,
                number: 1,
                length: step.doneText?.length || 300,
            });
            if (res.content?.length > 0) {
                step.doneText = res.content[0];
                step.confirmContent = res.content[0];
            }
        }
        if (step.id === 12) {
            const res = await generateMatrixPrompt({
                keywords: step.doneText,
                number: 1,
            });
            if (res.content?.length > 0) {
                const { content, topic, title } = res.content[0];
                step.doneText = content;
                step.confirmContent = content;
                step.hashtags = topic;
                step.title = title;
            }
        }
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "重新生成失败", icon: "none", duration: 3000 });
    } finally {
        step.status = "confirm";
        scrollToBottom();
    }
};

// ── 样式工具函数（done 状态全部改为蓝色主题）──
const iconStyle = (status: StepStatus) => {
    if (status === "done")
        return "background:linear-gradient(135deg,#0065fb,#3d8bfc);box-shadow:0 4px 14px rgba(0,101,251,0.35)";
    if (status === "running")
        return "background:linear-gradient(135deg,#0065fb,#3d8bfc);box-shadow:0 4px 14px rgba(0,101,251,0.4)";
    if (status === "confirm")
        return "background:linear-gradient(135deg,#0065fb,#3d8bfc);box-shadow:0 4px 14px rgba(0,101,251,0.4)";
    return "background:#F3F4F6;box-shadow:none";
};

const lineStyle = (status: StepStatus) => {
    if (status === "done") return "background:linear-gradient(180deg,#3d8bfc 0%,#dbeafe 100%)";
    if (status === "running") return "background:linear-gradient(180deg,#3d8bfc 0%,#dbeafe 100%)";
    if (status === "confirm") return "background:linear-gradient(180deg,#3d8bfc 0%,#dbeafe 100%)";
    return "background:#E5E7EB";
};

const tagStyle = (tag: string) => {
    if (tag === "AI") return "background:linear-gradient(135deg,#0065fb,#3d8bfc);color:#fff";
    return "background:linear-gradient(135deg,#06B6D4,#38BDF8);color:#fff";
};

const init = async () => {
    try {
        await getDetail();
        allSteps.value = getAllSteps();
        initStepsFromDetail(taskDetail.value);
    } finally {
        isLoading.value = false;
    }
};

onLoad((options: any) => {
    taskId.value = options.id as string;
    init();
});
</script>

<style scoped lang="scss">
.skeleton-block {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s ease-in-out infinite;
}
.skeleton-line {
    background: linear-gradient(180deg, #ebebeb 0%, #f5f5f5 100%);
}
@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
.skeleton-item {
    animation: skeletonFadeIn 0.3s ease both;
}
@keyframes skeletonFadeIn {
    from {
        opacity: 0;
        transform: translateY(12rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.content-fade-in {
    animation: contentFadeIn 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes contentFadeIn {
    from {
        opacity: 0;
        transform: translateY(16rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.group-b-enter {
    animation: groupSlideIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes groupSlideIn {
    from {
        opacity: 0;
        transform: translateY(32rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.typing-dot {
    animation: typingBounce 0.9s ease-in-out infinite;
}
@keyframes typingBounce {
    0%,
    80%,
    100% {
        transform: translateY(0);
        opacity: 0.3;
    }
    40% {
        transform: translateY(-5rpx);
        opacity: 1;
    }
}
.step-pulse {
    animation: stepPulse 1.6s ease-in-out infinite;
}
@keyframes stepPulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.8);
        opacity: 0.3;
    }
}
</style>
