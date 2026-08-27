<template>
    <view
        class="flex flex-col min-h-screen pb-[300rpx]"
        style="background: linear-gradient(180deg, #c9def8 0%, #ddeafa 300rpx, #f3f5fa 600rpx)">
        <u-navbar title="创作详情" :border-bottom="false" :background="{ background: 'transparent' }"></u-navbar>

        <view v-if="!task && loaded" class="flex flex-col items-center py-[160rpx]">
            <empty :text="loadError ? '加载失败，请检查网络' : '任务不存在或已被删除'" />
            <view
                v-if="loadError"
                class="mt-[24rpx] px-[48rpx] py-[16rpx] rounded-full bg-primary active:opacity-80"
                @click="retryLoad">
                <text class="text-sm text-white font-semibold">重新加载</text>
            </view>
        </view>

        <template v-else-if="task">
            <!-- 任务卡 -->
            <view class="mx-4 mt-2">
                <view class="bg-white rounded-[24rpx] p-[28rpx]" style="box-shadow: 0 6px 22px rgba(99, 120, 200, 0.1)">
                    <view class="flex items-start gap-[20rpx]">
                        <view
                            class="w-[96rpx] h-[96rpx] rounded-[24rpx] flex items-center justify-center flex-shrink-0"
                            style="
                                background: linear-gradient(135deg, #5b9bf8 0%, #3b82f6 100%);
                                box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
                            ">
                            <image
                                src="@/ai_modules/hotspot/static/icons/flame_white.svg"
                                mode="aspectFit"
                                class="w-[48rpx] h-[48rpx]" />
                        </view>
                        <view class="flex-1 min-w-0">
                            <view class="flex items-center gap-[12rpx] flex-wrap">
                                <view
                                    class="inline-flex items-center gap-[6rpx] px-[12rpx] py-[4rpx] rounded-[8rpx]"
                                    :style="`background:${platformActiveBg(task.platform)}`">
                                    <image
                                        :src="platformWhiteIcon(task.platform)"
                                        mode="aspectFit"
                                        class="w-[20rpx] h-[20rpx]" />
                                    <text class="text-[20rpx] font-semibold text-white">
                                        {{ platformLabel(task.platform) }}
                                    </text>
                                </view>
                                <text
                                    class="px-[14rpx] py-[4rpx] rounded-[8rpx] text-[20rpx] font-medium"
                                    :class="HOTSPOT_STATUS_CLASS[task.status] || 'bg-[#F3F4F6] text-[#6B7280]'">
                                    {{ HOTSPOT_STATUS_TEXT[task.status] || task.status }}
                                </text>
                            </view>
                            <text class="block text-[30rpx] font-bold text-[#111827] leading-snug mt-[12rpx]">
                                {{ task.title || task.topic }}
                            </text>
                            <text class="block text-[22rpx] text-[#9CA3AF] mt-[8rpx] line-clamp-1">
                                来自热点 · {{ task.topic }}
                            </text>
                        </view>
                    </view>

                    <view class="h-[14rpx] bg-[#F3F4F6] rounded-full mt-[24rpx] overflow-hidden">
                        <view
                            class="h-full rounded-full"
                            :style="`width:${progressPercent}%;background:linear-gradient(90deg,#2563EB,#60A5FA)`"></view>
                    </view>
                    <text class="block text-[22rpx] text-[#9CA3AF] mt-[10rpx]">
                        {{ doneCount }}/{{ HOTSPOT_TASK_STEPS.length }} 步已完成 · {{ task.created_at || "" }}
                    </text>

                    <view class="flex items-center gap-[10rpx] mt-[18rpx] flex-wrap">
                        <text
                            v-if="goalText"
                            class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#EFF6FF] text-primary text-[20rpx] font-semibold">
                            {{ goalText }}
                        </text>
                        <text
                            v-if="videoTypeText"
                            class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F5F3FF] text-[#7C3AED] text-[20rpx]">
                            {{ videoTypeText }}
                        </text>
                        <text
                            v-if="task.options?.materials?.length"
                            class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                            素材 {{ task.options.materials.length }}
                        </text>
                        <text
                            v-if="task.options?.direction"
                            class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                            {{ task.options.direction }}
                        </text>
                        <text
                            v-if="materialModeText"
                            class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                            {{ materialModeText }}
                        </text>
                        <text
                            v-if="task.options?.duration_sec"
                            class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                            {{ task.options.duration_sec }} 秒
                        </text>
                        <text
                            v-if="task.options?.product"
                            class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#FFFBEB] text-[#D97706] text-[20rpx]">
                            推 {{ task.options.product }}
                        </text>
                    </view>
                </view>
            </view>

            <!-- 步骤时间线 -->
            <view class="mx-4 mt-[32rpx] pb-[200rpx]">
                <view v-for="(step, index) in HOTSPOT_TASK_STEPS" :key="step.key" class="flex gap-[20rpx]">
                    <view class="flex flex-col items-center flex-shrink-0" style="width: 56rpx">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center flex-shrink-0"
                            :class="nodeClass(stepStatus(step.key))">
                            <u-loading
                                v-if="stepStatus(step.key) === 'running'"
                                mode="circle"
                                size="26"
                                color="#2563EB"></u-loading>
                            <u-icon
                                v-else
                                :name="nodeIcon(stepStatus(step.key))"
                                :color="nodeIconColor(stepStatus(step.key))"
                                :size="26"></u-icon>
                        </view>
                        <view
                            v-if="index < HOTSPOT_TASK_STEPS.length - 1"
                            class="w-[4rpx] flex-1 rounded-full mt-[4rpx]"
                            style="min-height: 24rpx"
                            :class="lineClass(stepStatus(step.key))"></view>
                    </view>
                    <view class="flex-1 min-w-0 pb-[28rpx]">
                        <text
                            class="block text-[28rpx] font-bold pt-[8rpx]"
                            :class="stepStatus(step.key) === 'pending' ? 'text-[#D1D5DB]' : 'text-[#111827]'">
                            {{ step.label }}
                        </text>

                        <!-- 失败原因 -->
                        <view v-if="stepStatus(step.key) === 'fail'" class="err-box mt-[14rpx]">
                            <view class="flex-shrink-0 mt-[4rpx]">
                                <u-icon name="error-circle" color="#EF4444" :size="26"></u-icon>
                            </view>
                            <text class="flex-1 text-xs text-[#EF4444] leading-relaxed break-all">
                                {{ task.error || "该步骤失败" }}
                            </text>
                        </view>

                        <!-- 等待中 -->
                        <view v-else-if="stepStatus(step.key) === 'pending'" class="detail-card mt-[14rpx]">
                            <text class="text-[24rpx] text-[#D1D5DB]">等待中</text>
                        </view>

                        <!-- 执行中 -->
                        <view
                            v-else-if="stepStatus(step.key) === 'running'"
                            class="detail-card mt-[14rpx] flex items-center gap-[18rpx]">
                            <u-loading mode="circle" size="36" color="#0065fb"></u-loading>
                            <view>
                                <text class="block text-[25rpx] font-semibold text-primary">
                                    {{ runningText(step.key).title }}
                                </text>
                                <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">
                                    {{ runningText(step.key).sub }}
                                </text>
                            </view>
                        </view>

                        <!-- 已完成内容 -->
                        <template v-else>
                            <!-- 选定热点 -->
                            <view v-if="step.key === 'select'" class="detail-card mt-[14rpx]">
                                <text class="text-[24rpx] text-[#6B7280]">热点话题「{{ task.topic }}」</text>
                            </view>

                            <!-- 联网搜索 -->
                            <view v-else-if="step.key === 'search'" class="detail-card mt-[14rpx]">
                                <template v-if="task.core_points?.length">
                                    <view
                                        v-for="(point, pi) in task.core_points"
                                        :key="pi"
                                        class="flex gap-[16rpx] py-[10rpx]"
                                        :class="pi ? 'border-[0] border-t-[2rpx] border-solid border-[#F9FAFB]' : ''">
                                        <view
                                            class="w-[30rpx] h-[30rpx] rounded-[6rpx] bg-[#EFF6FF] flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                                            <text class="text-[18rpx] font-bold text-primary">{{ pi + 1 }}</text>
                                        </view>
                                        <view class="flex-1 min-w-0">
                                            <text class="block text-[25rpx] font-semibold text-[#111827]">
                                                {{ point.label }}
                                            </text>
                                            <text
                                                class="block text-[23rpx] text-[#6B7280] leading-relaxed mt-[4rpx] break-all">
                                                {{ point.detail }}
                                            </text>
                                        </view>
                                    </view>
                                </template>
                                <text v-else class="text-[24rpx] text-[#6B7280]">已完成联网搜索</text>
                                <template v-if="task.citations?.length">
                                    <text class="block text-[20rpx] font-bold text-[#9CA3AF] mt-[18rpx] mb-[4rpx]">
                                        信息来源 · {{ task.citations.length }}
                                    </text>
                                    <view
                                        v-for="(cite, ci) in task.citations.slice(0, 5)"
                                        :key="ci"
                                        class="flex items-center gap-[12rpx] py-[8rpx]">
                                        <view
                                            class="w-[24rpx] h-[24rpx] rounded-[4rpx] bg-[#F3F4F6] flex-shrink-0"></view>
                                        <text class="flex-1 min-w-0 text-[22rpx] text-[#6B7280] line-clamp-1">
                                            {{ cite.title || cite.site_name || cite.url }}
                                        </text>
                                        <text
                                            v-if="cite.publish_time"
                                            class="text-[20rpx] text-[#D1D5DB] flex-shrink-0">
                                            {{ String(cite.publish_time).slice(0, 10) }}
                                        </text>
                                    </view>
                                </template>
                            </view>

                            <!-- 结合分析 -->
                            <view v-else-if="step.key === 'analyze'" class="detail-card mt-[14rpx]">
                                <view
                                    v-if="task.persona?.name"
                                    class="flex items-center gap-[16rpx] pb-[18rpx] mb-[18rpx] border-[0] border-b-[2rpx] border-solid border-[#F9FAFB]">
                                    <image
                                        :src="task.persona.avatar || ''"
                                        mode="aspectFill"
                                        class="w-[64rpx] h-[64rpx] rounded-[14rpx] bg-[#F3F4F6] flex-shrink-0" />
                                    <view class="flex-1 min-w-0">
                                        <text class="block text-[25rpx] font-bold text-[#111827]">
                                            {{ task.persona.name }}
                                        </text>
                                        <text class="block text-[20rpx] text-[#9CA3AF] mt-[2rpx]">
                                            {{ task.persona.tag || "" }}
                                        </text>
                                    </view>
                                </view>
                                <template v-if="hasAnalysis">
                                    <view class="flex items-center justify-between">
                                        <text class="text-[24rpx] font-bold text-[#374151]">契合度</text>
                                        <text class="text-[23rpx] font-bold" :class="fitTextClass">
                                            {{ fitLevelText }} {{ fitScore }}
                                        </text>
                                    </view>
                                    <view class="h-[10rpx] bg-[#F3F4F6] rounded-full mt-[12rpx] overflow-hidden">
                                        <view
                                            class="h-full rounded-full"
                                            :class="fitBarClass"
                                            :style="`width:${fitScore}%`"></view>
                                    </view>
                                    <text
                                        v-if="task.analysis.fit_reason"
                                        class="block text-[23rpx] text-[#6B7280] leading-relaxed mt-[16rpx] break-all">
                                        {{ task.analysis.fit_reason }}
                                    </text>
                                    <template v-if="task.analysis.hooks?.length">
                                        <text class="block text-[20rpx] font-bold text-[#9CA3AF] mt-[18rpx] mb-[10rpx]">
                                            切入方式
                                        </text>
                                        <view
                                            v-for="(hook, hi) in task.analysis.hooks"
                                            :key="hi"
                                            class="bg-[#F9FAFB] rounded-[14rpx] p-[16rpx] mb-[10rpx]">
                                            <text class="block text-[24rpx] font-semibold text-primary">
                                                {{ hook.label }}
                                            </text>
                                            <text
                                                class="block text-[23rpx] text-[#6B7280] leading-relaxed mt-[4rpx] break-all">
                                                {{ hook.detail }}
                                            </text>
                                        </view>
                                    </template>
                                    <view
                                        v-if="task.analysis.risks?.length"
                                        class="flex items-start gap-[10rpx] px-[18rpx] py-[14rpx] rounded-[14rpx] mt-[10rpx] bg-[#FFFBEB] border-[2rpx] border-solid border-[#FDE68A]">
                                        <u-icon
                                            name="warning"
                                            color="#F59E0B"
                                            :size="24"
                                            class="flex-shrink-0"></u-icon>
                                        <view class="flex-1 min-w-0">
                                            <text
                                                v-for="(risk, ri) in task.analysis.risks"
                                                :key="ri"
                                                class="block text-xs text-[#D97706] leading-relaxed break-all">
                                                {{ risk }}
                                            </text>
                                        </view>
                                    </view>
                                </template>
                                <text v-else class="text-[24rpx] text-[#D1D5DB]">没有分析结果</text>
                            </view>

                            <!-- 口播文案 -->
                            <view v-else-if="step.key === 'script'" class="detail-card mt-[14rpx]">
                                <text
                                    class="block text-[26rpx] text-[#374151] leading-relaxed break-all"
                                    style="white-space: pre-wrap">
                                    {{ task.script || "（无文案）" }}
                                </text>
                                <text
                                    class="block text-[20rpx] text-[#D1D5DB] mt-[16rpx] pt-[16rpx] border-[0] border-t-[2rpx] border-solid border-[#F9FAFB]">
                                    {{ scriptWordCount }} 字 · 约 {{ scriptEstSec }} 秒
                                </text>
                            </view>

                            <!-- 视频合成 -->
                            <template v-else-if="step.key === 'video'">
                                <view
                                    v-if="task.video_url"
                                    class="mt-[14rpx] rounded-[24rpx] overflow-hidden bg-[#111827]"
                                    style="aspect-ratio: 9/16; min-height: 480rpx; max-height: 640rpx">
                                    <video
                                        :id="INLINE_VIDEO_ID"
                                        :src="task.video_url"
                                        class="w-full h-full"
                                        controls
                                        object-fit="contain"></video>
                                </view>
                                <view v-else class="detail-card mt-[14rpx]">
                                    <text class="text-[24rpx] text-[#6B7280]">视频已生成</text>
                                </view>
                            </template>
                        </template>
                    </view>
                </view>
            </view>

            <!-- 底部操作栏 -->
            <view
                class="fixed bottom-0 left-0 right-0 px-4 pt-[20rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))]"
                style="background: linear-gradient(180deg, rgba(243, 245, 250, 0) 0%, #f3f5fa 42%)">
                <view class="flex items-center gap-[20rpx]">
                    <button
                        class="plain-btn flex-1 py-[24rpx] rounded-full bg-white text-sm text-[#4B5563] font-semibold"
                        style="border: 2rpx solid #e5e7eb"
                        @click="backToList">
                        返回列表
                    </button>
                    <button
                        class="plain-btn py-[24rpx] rounded-full text-[29rpx] font-semibold"
                        :style="{ flex: canPublish ? 1 : 1.4 }"
                        :class="[
                            mainBtn.disabled ? 'bg-[#C3D4EE] text-[#5C7299]' : '',
                            !mainBtn.disabled && canPublish ? 'bg-[#EFF6FF] text-primary' : '',
                            !mainBtn.disabled && !canPublish ? 'cta-gradient text-white' : '',
                        ]"
                        :disabled="mainBtn.disabled"
                        @click="handleMainAction">
                        {{ mainBtn.text }}
                    </button>
                    <button
                        v-if="canPublish"
                        class="plain-btn py-[24rpx] rounded-full text-[29rpx] font-semibold text-white cta-gradient"
                        style="flex: 1.4"
                        @click="onPublish">
                        一键发布
                    </button>
                </view>
            </view>
        </template>

        <view v-else class="flex items-center justify-center py-[160rpx] gap-[12rpx]">
            <u-loading mode="circle" size="32" color="#0065fb"></u-loading>
            <text class="text-xs text-[#9CA3AF]">加载中...</text>
        </view>
    </view>

    <video-preview-v2 v-model:show="showVideoPreview" :video-url="task?.video_url || ''" />
</template>

<script setup lang="ts">
import { getHotspotTaskDetail, retryHotspotTask } from "@/api/hotspot";
import usePolling from "@/hooks/usePolling";
import {
    HOTSPOT_STATUS_CLASS,
    HOTSPOT_STATUS_TEXT,
    HOTSPOT_TASK_STEPS,
    HOTSPOT_DEFAULT_OPTIONS,
    HotspotTaskStatus,
    platformActiveBg,
    platformLabel,
    platformWhiteIcon,
} from "@/ai_modules/hotspot/enums";

const CHARS_PER_SEC = 4.2;

const taskId = ref("");
const task = ref<Record<string, any> | null>(null);
const loaded = ref(false);
const loadError = ref(false);
const retrying = ref(false);
const showVideoPreview = ref(false);

/** 步骤里内嵌的视频；打开全屏预览前要先暂停它，否则两个播放器会同时出声 */
const INLINE_VIDEO_ID = "hotspot-step-video";
const { proxy }: any = getCurrentInstance();

const pauseInlineVideo = () => {
    try {
        uni.createVideoContext(INLINE_VIDEO_ID, proxy)?.pause?.();
    } catch {
        /* ignore */
    }
};

const stepStatus = (key: string): string => task.value?.step_status?.[key] || "pending";

/** 后端空 analysis 序列化为 []（truthy），需按内容判断，否则会渲染出「关联较弱 0」 */
const hasAnalysis = computed(() => {
    const analysis = task.value?.analysis;
    return !!analysis && !Array.isArray(analysis) && analysis.fit_score !== undefined;
});

const doneCount = computed(() => HOTSPOT_TASK_STEPS.filter((step) => stepStatus(step.key) === "done").length);
const progressPercent = computed(() => Math.round((doneCount.value / HOTSPOT_TASK_STEPS.length) * 100));

const goalText = computed(() => {
    const goal = task.value?.options?.goal;
    if (!goal) return "";
    return HOTSPOT_DEFAULT_OPTIONS.goals.find((g) => g.key === goal)?.label || goal;
});
const videoTypeText = computed(() => {
    const vt = task.value?.options?.video_type;
    if (!vt) return "";
    const label = HOTSPOT_DEFAULT_OPTIONS.video_types.find((v) => v.key === vt)?.label || vt;
    const avatar = task.value?.options?.avatar;
    return vt === "digital" && avatar ? `${label} · ${avatar}` : label;
});
const materialModeText = computed(() => {
    const mode = task.value?.options?.material_mode;
    if (!mode) return "";
    return HOTSPOT_DEFAULT_OPTIONS.materials.find((m) => m.key === mode)?.label || mode;
});

const fitScore = computed(() => Number(task.value?.analysis?.fit_score || 0));
const fitLevelText = computed(() =>
    fitScore.value >= 70 ? "契合度高" : fitScore.value >= 40 ? "有一定关联" : "关联较弱",
);
const fitTextClass = computed(() =>
    fitScore.value >= 70 ? "text-[#16A34A]" : fitScore.value >= 40 ? "text-[#D97706]" : "text-[#EF4444]",
);
const fitBarClass = computed(() =>
    fitScore.value >= 70 ? "bg-[#22C55E]" : fitScore.value >= 40 ? "bg-[#FBBF24]" : "bg-[#F87171]",
);

const scriptWordCount = computed(() => String(task.value?.script || "").replace(/\s/g, "").length);
const scriptEstSec = computed(() => Math.max(1, Math.round(scriptWordCount.value / CHARS_PER_SEC)));

const nodeClass = (status: string) => {
    if (status === "done") return "bg-[#DCFCE7]";
    if (status === "running") return "bg-[#DBEAFE] node-running";
    if (status === "fail") return "bg-[#FEE2E2]";
    return "bg-[#F1F3F8]";
};
const nodeIcon = (status: string) => {
    if (status === "done") return "checkmark";
    if (status === "fail") return "close";
    return "clock";
};
const nodeIconColor = (status: string) => {
    if (status === "done") return "#16A34A";
    if (status === "fail") return "#DC2626";
    return "#B0B6C8";
};
const lineClass = (status: string) => {
    if (status === "done") return "bg-[#22C55E]";
    if (status === "fail") return "bg-[#EF4444]";
    return "bg-[#E5E9F2]";
};

const RUNNING_TEXT: Record<string, { title: string; sub: string }> = {
    search: { title: "正在联网搜索…", sub: "抓取最新报道与讨论" },
    analyze: { title: "正在分析结合点…", sub: "判断契合度、切入方式和风险" },
    script: { title: "正在生成口播文案…", sub: "按目的与人设口吻改写" },
    video: { title: "正在合成视频…", sub: "数字人配音 + 画面拼接，大约 3-8 分钟" },
};
const runningText = (key: string) => RUNNING_TEXT[key] || { title: "执行中…", sub: "" };

const mainBtn = computed(() => {
    if (!task.value) return { text: "—", disabled: true };
    if (task.value.status === HotspotTaskStatus.DONE) {
        return { text: task.value.video_url ? "预览视频" : "已完成", disabled: !task.value.video_url };
    }
    if (task.value.status === HotspotTaskStatus.FAIL) {
        return { text: retrying.value ? "提交中…" : "重新生成", disabled: retrying.value };
    }
    return { text: "合成中…", disabled: true };
});

/** 已完成且有成片才可一键发布 */
const canPublish = computed(
    () => !!task.value && task.value.status === HotspotTaskStatus.DONE && !!task.value.video_url,
);

/** 一键发布：带成片与文案跳矩阵发布任务创建页（source=hotspot 分支回填素材与文案），与列表卡片一致 */
const onPublish = () => {
    if (!canPublish.value) {
        uni.$u.toast("视频还未生成");
        return;
    }
    uni.$u.route({
        url: "/ai_modules/device/pages/create_task/create_task",
        params: {
            type: 1,
            source: "hotspot",
            data: JSON.stringify({ id: task.value!.id }),
        },
    });
};

const handleMainAction = () => {
    if (!task.value) return;
    if (task.value.status === HotspotTaskStatus.DONE && task.value.video_url) {
        pauseInlineVideo();
        showVideoPreview.value = true;
        return;
    }
    if (task.value.status === HotspotTaskStatus.FAIL) {
        handleRetry();
    }
};

const handleRetry = async () => {
    if (retrying.value || !taskId.value) return;
    retrying.value = true;
    try {
        const res = await retryHotspotTask({ id: taskId.value });
        if (res) task.value = res;
        uni.showToast({ title: "已重新开始合成", icon: "none", duration: 2500 });
        pollFailCount = 0;
        checkAndStartPolling();
    } catch (error: any) {
        uni.showToast({ title: error?.message || error || "重试失败", icon: "none", duration: 3000 });
    } finally {
        retrying.value = false;
    }
};

const backToList = () => {
    const pages = getCurrentPages();
    if (pages.length > 1) {
        uni.navigateBack();
    } else {
        uni.redirectTo({ url: "/ai_modules/hotspot/pages/index/index" });
    }
};

// 返回是否加载成功（网络失败与任务不存在区分展示）
const loadDetail = async (): Promise<boolean> => {
    if (!taskId.value) {
        loaded.value = true;
        return false;
    }
    try {
        const res = await getHotspotTaskDetail({ id: taskId.value });
        if (res) task.value = res;
        loadError.value = false;
        return true;
    } catch (error: any) {
        if (!task.value) {
            loadError.value = true;
            uni.showToast({ title: error?.message || error || "任务加载失败", icon: "none", duration: 3000 });
        }
        return false;
    } finally {
        loaded.value = true;
    }
};

const retryLoad = async () => {
    loaded.value = false;
    await loadDetail();
    checkAndStartPolling();
};

const isTaskRunning = () => {
    const status = task.value?.status;
    return status === HotspotTaskStatus.RUNNING || status === HotspotTaskStatus.WAIT;
};

// start 只由页面加载 / 重试触发；轮询回调内只做停止判断，避免双重调度
const checkAndStartPolling = () => {
    if (isTaskRunning()) start();
    else end();
};

let pollFailCount = 0;
const pollDetail = async () => {
    const ok = await loadDetail();
    // 连续失败止损（任务被删/网络长期异常时不再每 15 秒空转）
    pollFailCount = ok ? 0 : pollFailCount + 1;
    if (pollFailCount >= 3 || !isTaskRunning()) end();
};

const { start, end } = usePolling(pollDetail, { time: 15000 });

onLoad(async (options: any) => {
    taskId.value = String(options?.id || "");
    await loadDetail();
    checkAndStartPolling();
});

onShow(async () => {
    // 切后台/跳走会触发 onHide 停轮询，回到页面刷新一次并按需重启
    if (task.value) {
        pollFailCount = 0;
        await loadDetail();
        checkAndStartPolling();
    }
});

onUnmounted(() => end());
onHide(() => end());
onUnload(() => end());
</script>

<style lang="scss" scoped>
.detail-card {
    @apply bg-white rounded-[20rpx] p-[22rpx];
    box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08);
}

.err-box {
    @apply flex items-start gap-[10rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#FEF2F2];
    border: 2rpx solid #fecaca;
}

.node-running {
    border: 3rpx solid #2563eb;
    box-shadow: 0 0 0 6rpx rgba(37, 99, 235, 0.12);
}

.cta-gradient {
    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 60%, #4f8cf7 100%);
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
}

.plain-btn {
    line-height: 1.4;

    &::after {
        border: none;
    }
}
</style>
