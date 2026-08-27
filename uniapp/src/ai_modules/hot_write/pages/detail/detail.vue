<template>
    <image-text-detail
        v-if="!isLoading && isImageText"
        :detail="taskDetail"
        @refresh="refreshImageTextDetail"
        @publish="onPublishImageText" />

    <view v-else class="h-screen flex flex-col overflow-hidden bg-[#F7F8FA]">
        <u-navbar title="创作详情" :border-bottom="false" :background="{ background: '#F7F8FA' }"></u-navbar>

        <view
            v-if="isTaskFailed && failReason"
            class="fail-sticky flex-shrink-0 z-30 px-[32rpx] py-[16rpx] bg-[#F7F8FA] border-0 border-b border-solid border-[#FECACA]">
            <view
                class="flex items-start gap-[12rpx] px-[24rpx] py-[20rpx] rounded-[16rpx] bg-[#FEF2F2]"
                style="box-shadow: 0 4px 12px rgba(239, 68, 68, 0.08)">
                <view class="mt-[4rpx]">
                    <u-icon name="close-circle" color="#EF4444" size="22"></u-icon>
                </view>
                <text class="flex-1 text-[24rpx] leading-relaxed text-[#EF4444] break-words">
                    失败原因：{{ failReason }}
                </text>
            </view>
        </view>

        <scroll-view scroll-y class="w-full grow min-h-0">
            <view class="pb-[400rpx]">
                <template v-if="isLoading">
                    <view
                        class="mx-[24rpx] mt-[16rpx] mb-[28rpx] rounded-[36rpx] overflow-hidden shrink-0"
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
                            <view v-for="i in 6" :key="i" class="flex">
                                <view class="flex flex-col items-center w-[60rpx] flex-shrink-0">
                                    <view
                                        class="w-[48rpx] h-[48rpx] rounded-[16rpx] skeleton-block flex-shrink-0"></view>
                                    <view
                                        v-if="i < 6"
                                        class="w-[2rpx] flex-1 min-h-[60rpx] mt-[6rpx] skeleton-line"></view>
                                </view>
                                <view class="flex-1 min-w-0 pl-[18rpx] pb-[4rpx]" style="padding-top: 10rpx">
                                    <view class="flex items-center gap-[12rpx] mb-[14rpx]">
                                        <view
                                            class="h-[30rpx] rounded-[8rpx] skeleton-block"
                                            :style="{ width: titleWidths[i - 1] }"></view>
                                        <view
                                            v-if="i % 3 === 0"
                                            class="h-[28rpx] w-[56rpx] rounded-full skeleton-block"></view>
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
                        class="mx-[24rpx] mt-[16rpx] mb-[28rpx] rounded-[36rpx] overflow-hidden shrink-0"
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
                                <text
                                    class="text-[28rpx] font-bold text-[#111827] block mb-[14rpx]"
                                    style="line-height: 1.5">
                                    {{ taskDetail.title }}
                                </text>
                                <view class="flex items-center gap-[10rpx] flex-wrap">
                                    <view
                                        v-if="taskStatus === 'failed'"
                                        class="flex items-center gap-[8rpx] px-[18rpx] py-[7rpx] rounded-full bg-[#FEF2F2]">
                                        <view class="w-[10rpx] h-[10rpx] rounded-full bg-[#EF4444]"></view>
                                        <text class="text-[21rpx] font-bold text-[#EF4444]">失败</text>
                                    </view>
                                    <view
                                        v-else-if="taskStatus === 'running'"
                                        class="flex items-center gap-[8rpx] px-[18rpx] py-[7rpx] rounded-full"
                                        style="background: linear-gradient(135deg, #eff6ff, #dbeafe)">
                                        <view
                                            class="w-[10rpx] h-[10rpx] rounded-full"
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
                                    class="h-full rounded-full"
                                    style="background: linear-gradient(90deg, #0065fb, #3d8bfc, #93c5fd)"
                                    :style="{ width: progressWidth }"></view>
                            </view>
                        </view>
                    </view>

                    <view class="flex flex-col px-[24rpx]">
                        <view v-for="(step, index) in groupA" :key="step.id" class="flex">
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
                                        class="w-[14rpx] h-[14rpx] rounded-full bg-white"></view>
                                    <u-icon
                                        v-else-if="step.status === 'confirm'"
                                        name="edit-pen"
                                        color="#fff"
                                        size="22"></u-icon>
                                    <u-icon
                                        v-else-if="step.status === 'failed'"
                                        name="close"
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
                            <view class="flex-1 min-w-0 pl-[18rpx] mb-[4rpx]" style="padding-top: 10rpx">
                                <view class="flex items-center gap-[10rpx] mb-[8rpx]">
                                    <text
                                        class="text-[27rpx] font-bold"
                                        :style="
                                            step.status === 'pending'
                                                ? 'color:#D1D5DB'
                                                : step.status === 'failed'
                                                ? 'color:#EF4444'
                                                : 'color:#111827'
                                        ">
                                        {{ step.title }}
                                    </text>
                                    <view
                                        v-if="step.tag"
                                        class="px-[14rpx] py-[4rpx] rounded-full"
                                        :style="tagStyle(step.tag)">
                                        <text class="text-[18rpx] font-extrabold">{{ step.tag }}</text>
                                    </view>
                                    <view
                                        v-if="step.status === 'confirm' && !isTaskFailed"
                                        class="px-[14rpx] py-[4rpx] rounded-full ml-auto"
                                        style="background: #fff7ed; border: 1rpx solid #fed7aa">
                                        <text class="text-[18rpx] font-bold" style="color: #f59e0b">待确认</text>
                                    </view>
                                    <view
                                        v-else-if="step.status === 'failed'"
                                        class="px-[14rpx] py-[4rpx] rounded-full ml-auto bg-[#FEF2F2]">
                                        <text class="text-[18rpx] font-bold text-[#EF4444]">失败</text>
                                    </view>
                                </view>
                                <step-content
                                    :step="step"
                                    :fail-reason="failReason"
                                    @confirm="onConfirmGroupA"
                                    @reject="onReject"
                                    @focus="onTextareaFocus"
                                    @blur="onTextareaBlur" />
                            </view>
                        </view>

                        <view v-if="showGroupB" class="flex items-center gap-[16rpx] my-[24rpx]">
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
                            <view v-for="(step, index) in groupB" :key="step.id" class="flex">
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
                                            class="w-[14rpx] h-[14rpx] rounded-full bg-white"></view>
                                        <u-icon
                                            v-else-if="step.status === 'confirm'"
                                            name="edit-pen"
                                            color="#fff"
                                            size="22"></u-icon>
                                        <u-icon
                                            v-else-if="step.status === 'failed'"
                                            name="close"
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
                                <view class="flex-1 min-w-0 pl-[18rpx] mb-[4rpx]" style="padding-top: 10rpx">
                                    <view class="flex items-center gap-[10rpx] mb-[8rpx]">
                                        <text
                                            class="text-[27rpx] font-bold"
                                            :style="
                                                step.status === 'pending'
                                                    ? 'color:#D1D5DB'
                                                    : step.status === 'failed'
                                                    ? 'color:#EF4444'
                                                    : 'color:#111827'
                                            ">
                                            {{ step.title }}
                                        </text>
                                        <view
                                            v-if="step.tag"
                                            class="px-[14rpx] py-[4rpx] rounded-full"
                                            :style="tagStyle(step.tag)">
                                            <text class="text-[18rpx] font-extrabold">{{ step.tag }}</text>
                                        </view>
                                        <view
                                            v-if="step.status === 'confirm' && !isTaskFailed"
                                            class="px-[14rpx] py-[4rpx] rounded-full ml-auto"
                                            style="background: #fff7ed; border: 1rpx solid #fed7aa">
                                            <text class="text-[18rpx] font-bold" style="color: #f59e0b">待确认</text>
                                        </view>
                                        <view
                                            v-else-if="step.status === 'failed'"
                                            class="px-[14rpx] py-[4rpx] rounded-full ml-auto bg-[#FEF2F2]">
                                            <text class="text-[18rpx] font-bold text-[#EF4444]">失败</text>
                                        </view>
                                    </view>
                                    <wash-config
                                        v-if="step.washConfig && step.status === 'confirm'"
                                        :task-id="taskId"
                                        :submitting="washSubmitting"
                                        @confirm="onConfirmWashConfig" />
                                    <step-content
                                        v-else
                                        :step="step"
                                        :fail-reason="failReason"
                                        @watch-video="onWatchVideo"
                                        @confirm="onConfirmGroupB"
                                        @reject="onReject"
                                        @focus="onTextareaFocus"
                                        @blur="onTextareaBlur" />
                                </view>
                            </view>
                        </template>
                    </view>
                </template>
            </view>
        </scroll-view>

        <view
            v-if="isTaskFailed"
            class="flex-shrink-0 px-[32rpx] pt-[24rpx] bg-white border-0 border-t border-solid border-[#F3F4F6]"
            style="padding-bottom: calc(28rpx + env(safe-area-inset-bottom)); z-index: 20">
            <view
                class="w-full py-[28rpx] rounded-full flex items-center justify-center gap-[12rpx] bg-primary"
                style="box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32)"
                :class="retrying ? 'opacity-60' : ''"
                @click="handleRetryVideo">
                <u-loading v-if="retrying" mode="circle" size="28" color="#ffffff"></u-loading>
                <text class="text-base font-semibold text-white">{{ retrying ? "重试中..." : "重新尝试" }}</text>
            </view>
        </view>

        <video-preview-v2 v-model:show="showVideoPreview" :video-url="videoUrl" />
    </view>
</template>

<script setup lang="ts">
import {
    getHotWriteDetail,
    generateVideo,
    confirmPublishText,
    createHotWrite,
    confirmGenerationOptions,
    confirmRewrittenText,
} from "@/api/hot_write";
import { generateMatrixPrompt } from "@/api/device";
import { getCopyWritingGenerate } from "@/api/agent";
import usePolling from "@/hooks/usePolling";
import { formatAudioTime } from "@/utils/util";
import {
    HotWriteRewriteMode,
    HotWriteTaskStatus,
    WASH_GENERATION_TYPE_LABEL,
    isImageTextTask,
    isWashTask,
} from "@/ai_modules/hot_write/enums";
import StepContent from "./components/step-content.vue";
import ImageTextDetail from "./components/image-text-detail.vue";
import WashConfig from "./components/wash-config.vue";

type StepStatus = "done" | "running" | "confirm" | "pending" | "failed";
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
    /** 待确认态的补充提示（如 AI 未生成出文案） */
    confirmTip?: string;
    confirmLabel?: string;
    rejectLabel?: string;
    videoUrl?: string;
    /** 洗稿：视频类型/形象/音色选择步骤 */
    washConfig?: boolean;
}

/** 视频详情内部阶段（勿与接口 HotWriteTaskStatus.FAIL=4 混用） */
const enum TaskStatus {
    Pending = 1,
    VideoGenerating = 2,
    WaitingPublishConfirm = 3,
}

const showVideoPreview = ref(false);
const videoUrl = ref("");
const retrying = ref(false);

const titleWidths = ["45%", "60%", "55%", "50%", "65%", "40%"];
const contentHeights = ["80rpx", "120rpx", "180rpx", "80rpx", "80rpx", "100rpx"];

const getAllSteps = () => {
    if (isWashTask(taskDetail.value)) {
        return buildWashSteps();
    }
    const { is_material } = taskDetail.value;
    if (is_material === 1) {
        return buildInitialSteps().filter((s) => s.id !== 8);
    } else {
        return buildInitialSteps();
    }
};

/** 洗稿步骤流：去掉人设分析与自动匹配形象/音色，插入用户自选的「选择视频配置」 */
const buildWashSteps = (): Step[] => {
    const steps = buildInitialSteps().filter((s) => ![6, 8, 9].includes(s.id));
    const rewrite = steps.find((s) => s.id === 7)!;
    rewrite.title = "洗稿改写";
    rewrite.desc = "AI 正在同义改写原文案...";
    steps.splice(
        steps.findIndex((s) => s.id === 10),
        0,
        {
            id: 8,
            title: "选择视频配置",
            desc: "选择视频类型、数字人形象与音色",
            status: "pending",
            doneType: "kv",
            doneText: "",
            washConfig: true,
        },
    );
    return steps;
};

const buildInitialSteps = (): Step[] => [
    { id: 1, title: "创建任务", desc: "AI分配成功", status: "pending", doneType: "text", meta: [] },
    {
        id: 2,
        title: "提取文案",
        desc: "正在解析视频链接内容...",
        status: "pending",
        doneType: "text",
        doneText: "",
    },
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
    {
        id: 4,
        title: "爆款分析",
        desc: "AI 分析中...",
        status: "pending",
        doneType: "tags",
        doneTags: [],
        tag: "AI",
    },
    {
        id: 5,
        title: "违禁词检查",
        desc: "全网合规性扫描中...",
        status: "pending",
        doneType: "text",
        doneText: "",
    },
    {
        id: 6,
        title: "人设分析",
        desc: "正在分析当前IP人设风格...",
        status: "pending",
        doneType: "kv",
        doneText: "",
    },
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
    {
        id: 8,
        title: "匹配形象",
        desc: "正在检索合成形象...",
        status: "pending",
        doneType: "kv",
        doneText: "",
    },
    {
        id: 9,
        title: "匹配音色",
        desc: "正在检索合成音色...",
        status: "pending",
        doneType: "kv",
        doneText: "",
    },
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
const taskStatus = ref<"running" | "done" | "failed">("running");
const isLoading = ref(true);
const showGroupB = ref(false);
const scrollTop = ref(0);
const isImageText = computed(() => isImageTextTask(taskDetail.value));
const isTaskFailed = computed(() => Number(taskDetail.value?.status) === HotWriteTaskStatus.FAIL);
const failReason = computed(() => String(taskDetail.value?.remarks || "").trim() || "任务失败");

const groupA = computed(() => allSteps.value.filter((s) => s.id <= 7));
const groupB = computed(() => allSteps.value.filter((s) => s.id > 7));

const doneCount = computed(
    () => allSteps.value.filter((s) => s.status === "done" || s.status === "failed").length || 0,
);
const progressWidth = computed(() => `${Math.round((doneCount.value / allSteps.value.length) * 100)}%`);

/** 失败时按已有数据推断失败停在哪一步，已完成步骤只读展示（不用 confirm） */
const applyFailedSteps = (res: any) => {
    const steps = allSteps.value;
    const find = (id: number) => steps.find((s) => s.id === id)!;
    const markDone = (from: number, to: number) => {
        steps.filter((s) => s.id >= from && s.id <= to).forEach((s) => (s.status = "done"));
    };
    const failAt = (id: number) => {
        find(id).status = "failed";
        if (id > 7) showGroupB.value = true;
    };

    const hasOriginal = !!String(res.original_text || "").trim();
    const hasRewritten = !!String(res.rewritten_text || "").trim();
    const tags = Array.isArray(res.analysis_tags)
        ? res.analysis_tags
        : typeof res.analysis_tags === "string" && res.analysis_tags
        ? (() => {
              try {
                  return JSON.parse(res.analysis_tags);
              } catch {
                  return [];
              }
          })()
        : [];

    taskStatus.value = "failed";
    find(1).status = "done";

    // 洗稿任务：无人设分析步，配置未确认失败时允许直接补选配置续跑
    if (isWashTask(taskDetail.value)) {
        if (!hasOriginal) {
            failAt(2);
            return;
        }
        markDone(2, 3);
        if (!tags.length) {
            failAt(4);
            return;
        }
        find(4).status = "done";
        if (!res.compliance_status) {
            failAt(5);
            return;
        }
        find(5).status = "done";
        if (!hasRewritten) {
            failAt(7);
            return;
        }
        find(7).status = "done";
        showGroupB.value = true;
        if (Number(res.generation_config_confirmed) !== 1) {
            find(8).status = "confirm";
            return;
        }
        find(8).status = "done";
        if (!res.video_url) {
            failAt(10);
            return;
        }
        markDone(10, 11);
        if (!String(res.publish_text || "").trim()) {
            failAt(12);
            return;
        }
        find(12).status = "done";
        failAt(13);
        return;
    }

    // 已有仿写结果：A 组只读，失败点优先落在合成视频
    if (hasRewritten) {
        markDone(1, 7);
        showGroupB.value = true;
        if (taskDetail.value.is_material === 0) find(8).status = "done";
        find(9).status = "done";
        if (!res.video_url) {
            failAt(10);
            return;
        }
        markDone(10, 11);
        if (!String(res.publish_text || "").trim()) {
            failAt(12);
            return;
        }
        find(12).status = "done";
        failAt(13);
        return;
    }

    if (!hasOriginal) {
        failAt(2);
        return;
    }
    markDone(2, 3);

    if (!tags.length) {
        failAt(4);
        return;
    }
    find(4).status = "done";

    if (!res.compliance_status) {
        failAt(5);
        return;
    }
    find(5).status = "done";

    if (!res.persona_tone) {
        failAt(6);
        return;
    }
    find(6).status = "done";
    failAt(7);
};

const initStepsFromDetail = (res: any) => {
    const steps = allSteps.value;
    const find = (id: number) => steps.find((s) => s.id === id)!;
    const wash = isWashTask(taskDetail.value);
    const washTypeLabel = WASH_GENERATION_TYPE_LABEL[Number(res.generation_type)] || "";

    find(1).meta = [`任务ID: ${res.id}`, "AI分配成功"];
    find(2).doneText = `成功解析目标链接，共提取 ${res.original_text?.length || 0} 字`;
    find(3).doneText = res.original_text;
    find(3).confirmContent = res.original_text;
    find(4).doneTags = res.analysis_tags;
    find(5).doneText = res.compliance_status;
    if (!wash) {
        find(6).doneText = `匹配人设：${res.persona_tone}`;
    }
    find(7).doneText = res.rewritten_text;
    find(7).confirmContent = res.rewritten_text;

    if (wash) {
        const parts = [washTypeLabel];
        if (res.avatar_name) parts.push(`形象：${res.avatar_name}`);
        if (res.voice_name) parts.push(`音色：${res.voice_name}`);
        find(8).doneText =
            Number(res.generation_config_confirmed) === 1
                ? `已选择：${parts.filter(Boolean).join(" · ")}`
                : "等待选择视频类型、形象与音色";
    } else {
        if (taskDetail.value.is_material === 0) {
            find(8).doneText = res.avatar_name
                ? `已选择形象：数字人${res.avatar_name} (绑定形象)`
                : "正在检索合成形象...";
        }
        find(9).doneText = res.voice_name ? `匹配音色：${res.voice_name}` : "正在检索合成音色...";
    }
    find(10).doneText = res.video_url
        ? `${wash ? washTypeLabel || "洗稿" : "数字人口播混剪"}视频；时长${formatAudioTime(res.duration || 0)}秒`
        : "正在合成视频...";
    find(10).videoUrl = res.video_url;
    // 发布文案由后端在「获取素材 → 下发合成」成功后才写入，期间为 null，不能当成空文案让用户去确认
    const publishText = String(res.publish_text || "").trim();
    checkAndStartVideoPolling(res);
    find(12).doneText = publishText || "正在包装标题与标签...";
    find(12).confirmContent = publishText;
    try {
        find(12).hashtags = res.publish_topic ? JSON.parse(res.publish_topic) : [];
    } catch {
        find(12).hashtags = [];
    }

    if (Number(res.status) === HotWriteTaskStatus.FAIL) {
        applyFailedSteps(res);
        return;
    }

    if (res.status === TaskStatus.Pending) {
        if (wash) {
            // 洗稿：文案就绪后先确认文案，再进入视频配置自选；文案已确认过（后端落库）则直接恢复到配置步
            steps.filter((s) => s.id >= 1 && s.id <= 5).forEach((s) => (s.status = "done"));
            if (Number(res.rewritten_text_confirmed) === 1 && Number(res.generation_config_confirmed) !== 1) {
                find(7).status = "done";
                find(8).status = "confirm";
                showGroupB.value = true;
            } else {
                find(7).status = "confirm";
            }
            taskStatus.value = "running";
            return;
        }
        steps.filter((s) => s.id >= 1 && s.id <= 6).forEach((s) => (s.status = "done"));
        find(7).status = "confirm";
        taskStatus.value = "running";
    } else if (
        res.status === TaskStatus.VideoGenerating ||
        res.status === TaskStatus.WaitingPublishConfirm ||
        res.publish_confirm === 1
    ) {
        steps.filter((s) => s.id >= 1 && s.id <= 11).forEach((s) => (s.status = "done"));
        const isDone = res.publish_confirm === 1;
        if (isPublishTextPending(res)) {
            // 还没生成：显示等待提示并轮询，别给一个空的待确认输入框
            find(12).desc = "AI 正在获取素材并下发合成，发布文案稍后自动生成...";
            find(12).status = "running";
        } else {
            find(12).status = isDone ? "done" : "confirm";
            // 已跑过生成但文案为空：告诉用户可手动输入或重新生成，否则只看到一个空框
            find(12).confirmTip =
                !isDone && !publishText ? "AI 没能生成发布文案，可自行输入，或点「换一个」重新生成" : "";
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

const refreshImageTextDetail = async () => {
    await getDetail();
    checkAndStartImageTextPolling();
};

const onPublishImageText = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/create_task/create_task",
        params: {
            type: 2, // TaskType.IMAGE 发布图文
            source: "hot_write",
            data: JSON.stringify({ id: taskDetail.value.id }),
        },
    });
};

/** 视频任务：发布文案未就绪（后端还在找素材/下发合成）时轮询等待，避免用户看到空文案框 */
const isPublishTextPending = (res: any) =>
    Number(res?.status) === TaskStatus.VideoGenerating &&
    Number(res?.publish_confirm) !== 1 &&
    !String(res?.publish_text || "").trim() &&
    // publish_text / publish_topic 由后端同一次落库；话题都没有说明这步还没跑
    !String(res?.publish_topic || "").trim();

const videoPolling = ref(false);

const checkAndStartVideoPolling = (res: any) => {
    if (isPublishTextPending(res)) {
        if (videoPolling.value) return;
        videoPolling.value = true;
        startVideoPoll();
        return;
    }
    if (videoPolling.value) {
        videoPolling.value = false;
        endVideoPoll();
    }
};

const silentRefreshVideo = async () => {
    try {
        await getDetail();
        initStepsFromDetail(taskDetail.value);
    } catch {
        videoPolling.value = false;
        endVideoPoll();
    }
};

const checkAndStartImageTextPolling = () => {
    const status = Number(taskDetail.value?.status);
    const rewriteStatus = Number(taskDetail.value?.image_rewrite_status);
    const running = status === 0 || status === 2 || [1, 2].includes(rewriteStatus); // WAIT / PROCESSING
    if (running) startImageTextPoll();
    else endImageTextPoll();
};

const silentRefreshImageText = async () => {
    try {
        await getDetail();
        const status = Number(taskDetail.value?.status);
        if (status === 3 || status === 4) endImageTextPoll();
    } catch {
        endImageTextPoll();
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        scrollTop.value = scrollTop.value === 999999 ? 999998 : 999999;
    });
};

const onTextareaFocus = () => {};

const onTextareaBlur = () => {
    setTimeout(() => {
        scrollTop.value = scrollTop.value <= 0 ? 1 : scrollTop.value - 1;
        nextTick(() => {
            scrollTop.value = scrollTop.value + 1;
        });
    }, 300);
};

const onWatchVideo = (step: Step) => {
    videoUrl.value = step.videoUrl || "";
    showVideoPreview.value = true;
};

const handleRetryVideo = async () => {
    if (retrying.value || !isTaskFailed.value) return;
    const url = String(taskDetail.value?.prompt || taskDetail.value?.url || "").trim();
    const washTask = isWashTask(taskDetail.value);
    const personaId = washTask ? 0 : taskDetail.value?.persona_id;
    if (!url) {
        uni.$u.toast("缺少原链接，无法重试");
        return;
    }
    if (!washTask && !personaId) {
        uni.$u.toast("缺少人设信息，无法重试");
        return;
    }
    retrying.value = true;
    uni.showLoading({ title: "重试中...", mask: true });
    try {
        await createHotWrite({
            id: taskDetail.value.id,
            url,
            persona_id: personaId,
            visual_material_source: washTask ? 1 : taskDetail.value.visual_material_source ?? 3,
            rewrite_mode: Number(taskDetail.value.rewrite_mode) || HotWriteRewriteMode.PERSONA,
        });
        uni.hideLoading();
        uni.showToast({ title: "已重新提交", icon: "none", duration: 2500 });
        isLoading.value = true;
        showGroupB.value = false;
        await getDetail();
        allSteps.value = getAllSteps();
        initStepsFromDetail(taskDetail.value);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "重试失败", icon: "none", duration: 3000 });
    } finally {
        retrying.value = false;
        isLoading.value = false;
    }
};

const onConfirmGroupA = async (step: Step) => {
    if (isTaskFailed.value) return;
    step.doneText = step.confirmContent;
    if (!step.confirmContent) {
        uni.showToast({ title: "请输入仿写文案", icon: "none", duration: 3000 });
        return;
    }
    const contentLength = step.confirmContent.length;
    if (contentLength < 3 || contentLength > 1800) {
        uni.showToast({ title: "文案内容需在3-1800字之间", icon: "none", duration: 3000 });
        return;
    }
    // 洗稿：确认文案后不直接合成，先让用户自选视频类型/形象/音色
    if (isWashTask(taskDetail.value)) {
        // 确认状态需落到后端，否则返回重进会再次停在“确认文案”
        uni.showLoading({ title: "确认中...", mask: true });
        try {
            await confirmRewrittenText({ id: taskId.value, rewritten_text: step.confirmContent });
            if (taskDetail.value) {
                taskDetail.value.rewritten_text = step.confirmContent;
                taskDetail.value.rewritten_text_confirmed = 1;
            }
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({ title: error || "确认文案失败", icon: "none", duration: 3000 });
            return;
        }
        uni.hideLoading();
        step.status = "done";
        showGroupB.value = true;
        const cfgStep = allSteps.value.find((s) => s.washConfig);
        if (cfgStep && cfgStep.status !== "done") {
            cfgStep.status = "confirm";
        }
        scrollToBottom();
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
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "生成视频失败", icon: "none", duration: 3000 });
    }
};

/** 洗稿：确认视频类型/形象/音色并启动合成（一并提交编辑后的洗稿文案） */
const washSubmitting = ref(false);
const onConfirmWashConfig = async (payload: { generation_type: number; avatar_id: number; voice_id: number }) => {
    if (washSubmitting.value) return;
    const rewriteStep = allSteps.value.find((s) => s.id === 7);
    const rewrittenText = String(rewriteStep?.confirmContent || taskDetail.value?.rewritten_text || "").trim();
    if (!rewrittenText) {
        uni.$u.toast("洗稿文案尚未生成，暂不能提交");
        return;
    }
    if (rewrittenText.length < 3 || rewrittenText.length > 1800) {
        uni.$u.toast("文案内容需在3-1800字之间");
        return;
    }
    washSubmitting.value = true;
    uni.showLoading({ title: "提交合成中...", mask: true });
    try {
        await confirmGenerationOptions({
            id: taskId.value,
            generation_type: payload.generation_type,
            avatar_id: payload.avatar_id,
            voice_id: payload.voice_id,
            rewritten_text: rewrittenText,
        });
        uni.hideLoading();
        uni.showToast({ title: "配置已确认，视频合成中", icon: "none", duration: 2500 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "提交合成失败", icon: "none", duration: 3000 });
    } finally {
        washSubmitting.value = false;
        // 成败都刷新详情：即使请求超时，后端也可能已进入合成
        try {
            await getDetail();
            allSteps.value = getAllSteps();
            initStepsFromDetail(taskDetail.value);
            scrollToBottom();
        } catch {}
    }
};

const onConfirmGroupB = async (step: Step) => {
    if (isTaskFailed.value) return;
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
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "确认发布文案失败", icon: "none", duration: 3000 });
    }
};

const onReject = async (step: Step) => {
    if (isTaskFailed.value) return;
    step.status = "running";
    if (!step.doneText) {
        uni.showToast({ title: "请先输入文案", icon: "none", duration: 3000 });
        step.status = "confirm";
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
                // 发布文案由口播文案包装而来（与后端 getMatrixCopywriting 口径一致）
                keywords: String(taskDetail.value?.rewritten_text || "").trim() || step.doneText,
                number: 1,
            });
            if (res.length > 0) {
                const { content, topic, title } = res[0];
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
    }
};

const iconStyle = (status: StepStatus) => {
    if (status === "failed") return "background:#EF4444;box-shadow:0 4px 14px rgba(239,68,68,0.35)";
    if (status === "done")
        return "background:linear-gradient(135deg,#0065fb,#3d8bfc);box-shadow:0 4px 14px rgba(0,101,251,0.35)";
    if (status === "running")
        return "background:linear-gradient(135deg,#0065fb,#3d8bfc);box-shadow:0 4px 14px rgba(0,101,251,0.4)";
    if (status === "confirm")
        return "background:linear-gradient(135deg,#0065fb,#3d8bfc);box-shadow:0 4px 14px rgba(0,101,251,0.4)";
    return "background:#F3F4F6;box-shadow:none";
};

const lineStyle = (status: StepStatus) => {
    if (status === "failed") return "background:linear-gradient(180deg,#FCA5A5 0%,#FEE2E2 100%)";
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
        if (isImageText.value) {
            checkAndStartImageTextPolling();
            return;
        }
        allSteps.value = getAllSteps();
        initStepsFromDetail(taskDetail.value);
    } finally {
        isLoading.value = false;
    }
};

const { start: startImageTextPoll, end: endImageTextPoll } = usePolling(silentRefreshImageText, {
    time: 3000,
});

const { start: startVideoPoll, end: endVideoPoll } = usePolling(silentRefreshVideo, {
    time: 5000,
});

onLoad((options: any) => {
    taskId.value = options.id as string;
    init();
});

const endAllPolling = () => {
    endImageTextPoll();
    videoPolling.value = false;
    endVideoPoll();
};

onShow(() => {
    // 从其他页面返回时恢复轮询（onHide 已把轮询收口）
    if (isImageText.value) checkAndStartImageTextPolling();
    else if (taskDetail.value) checkAndStartVideoPolling(taskDetail.value);
});

onHide(() => endAllPolling());
onUnload(() => endAllPolling());
onUnmounted(() => endAllPolling());
</script>

<style scoped lang="scss">
.fail-sticky {
    position: sticky;
    top: 0;
}

.skeleton-block {
    background: #efefef;
}
.skeleton-line {
    background: #efefef;
}
</style>
