<template>
    <view class="h-screen flex flex-col overflow-hidden bg-[#F3F5FA]">
        <u-navbar title="图文复刻详情" :border-bottom="false" :background="{ background: '#F3F5FA' }" />

        <!-- 失败原因：导航下固定，下方内容滚动时始终可见 -->
        <view
            v-if="isFailed && failReason"
            class="fail-sticky flex-shrink-0 z-30 px-[32rpx] py-[16rpx] bg-[#F3F5FA] border-0 border-b border-solid border-[#FECACA]">
            <view
                class="flex items-start gap-[12rpx] px-[24rpx] py-[20rpx] rounded-[16rpx] bg-[#FEF2F2]"
                style="box-shadow: 0 4px 12px rgba(239, 68, 68, 0.08)">
                <text class="mt-[4rpx]">
                    <u-icon name="close-circle" color="#EF4444" size="22"></u-icon>
                </text>
                <text class="flex-1 text-[24rpx] leading-relaxed text-[#EF4444] break-words">
                    失败原因：{{ failReason }}
                </text>
            </view>
        </view>

        <scroll-view scroll-y class="w-full grow min-h-0">
            <view class="px-[32rpx] pb-[240rpx]">
                <view class="pt-[8rpx] pb-[16rpx]">
                    <text class="text-[32rpx] font-bold text-[#111827] leading-snug block">
                        {{ detail.title || "图文复刻任务" }}
                    </text>
                    <text class="text-xs text-[#9CA3AF] mt-[8rpx] block">
                        {{ statusLabel }} · {{ detail.create_time }}
                    </text>
                </view>

                <!-- 1 解析链接 -->
                <view class="flex items-center gap-[12rpx] mt-[16rpx] mb-[16rpx]">
                    <view class="step-no" :class="stepClass(1)">1</view>
                    <text class="text-[28rpx] font-bold text-[#111827]">解析链接</text>
                </view>
                <view class="card">
                    <text class="text-[25rpx] text-[#6B7280] break-all">{{ detail.prompt || "-" }}</text>
                    <view v-if="hasExtracted" class="flex items-center gap-[8rpx] mt-[12rpx]">
                        <u-icon name="checkmark-circle" color="#16A34A" size="22"></u-icon>
                        <text class="text-[22rpx] text-[#16A34A]">链接有效，已定位到原笔记</text>
                    </view>
                    <view v-else-if="isFailed" class="flex items-start gap-[8rpx] mt-[12rpx]">
                        <u-icon name="close-circle" color="#EF4444" size="22"></u-icon>
                        <text class="text-[22rpx] text-[#EF4444] leading-relaxed">{{ failReason }}</text>
                    </view>
                    <view v-else class="flex items-center gap-[16rpx] mt-[12rpx]">
                        <u-loading mode="circle" size="28" color="#2563EB"></u-loading>
                        <text class="text-[26rpx] font-semibold text-[#2563EB]">正在解析链接…</text>
                    </view>
                </view>

                <!-- 2 提取笔记：已提取 或 进行中/失败到该步 -->
                <template v-if="hasExtracted || step >= 1">
                    <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                        <view class="step-no" :class="stepClass(2)">2</view>
                        <text class="text-[28rpx] font-bold text-[#111827]">提取笔记内容</text>
                    </view>
                    <view class="card">
                        <template v-if="hasExtracted">
                            <text class="text-[27rpx] font-bold text-[#111827] block">
                                {{ originalTitle }}
                            </text>
                            <text class="text-[25rpx] text-[#6B7280] leading-relaxed mt-[12rpx] block">
                                {{ detail.original_text || "暂无正文" }}
                            </text>
                            <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                                <view class="flex gap-[12rpx] mt-[20rpx]">
                                    <view
                                        v-for="(url, i) in originalImages"
                                        :key="'o-' + i"
                                        class="w-[112rpx] h-[140rpx] rounded-[12rpx] overflow-hidden bg-[#E5E7EB] flex-shrink-0"
                                        @click="previewImages(originalImages, i)">
                                        <image :src="url" mode="aspectFill" class="w-full h-full" />
                                    </view>
                                </view>
                            </scroll-view>
                            <text class="text-[22rpx] text-[#9CA3AF] mt-[12rpx] block">
                                共提取 {{ originalImages.length }} 张图片
                            </text>
                        </template>
                        <view v-else-if="isFailed" class="flex items-start gap-[8rpx]">
                            <u-icon name="close-circle" color="#EF4444" size="22"></u-icon>
                            <text class="text-[26rpx] font-semibold text-[#EF4444] leading-relaxed">
                                提取失败：{{ failReason }}
                            </text>
                        </view>
                        <view v-else class="flex items-center gap-[16rpx]">
                            <u-loading mode="circle" size="28" color="#2563EB"></u-loading>
                            <text class="text-[26rpx] font-semibold text-[#2563EB]">正在提取标题、正文与图片…</text>
                        </view>
                    </view>
                </template>

                <!-- 3 人设仿写 -->
                <template v-if="hasExtracted">
                    <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                        <view class="step-no" :class="stepClass(3)">3</view>
                        <text class="text-[28rpx] font-bold text-[#111827]">
                            {{ isWash ? "洗稿改写" : "人设内容仿写" }}
                        </text>
                    </view>
                    <view class="card">
                        <template v-if="hasRewrittenCopy">
                            <view class="flex items-center justify-between mb-[8rpx]">
                                <text class="text-[22rpx] font-bold text-[#9CA3AF]">仿写后标题</text>
                                <text v-if="isSelecting" class="text-[20rpx] text-[#9CA3AF]">
                                    {{ editTitle.length }}/60
                                </text>
                            </view>
                            <textarea
                                v-if="isSelecting"
                                v-model="editTitle"
                                class="edit-title w-full text-[27rpx] font-bold text-[#111827] bg-[#F9FAFB] rounded-[12rpx] px-[20rpx] py-[16rpx] box-border"
                                style="min-height: 80rpx; width: 100%; line-height: 1.5"
                                maxlength="60"
                                :show-confirm-bar="false"
                                :auto-height="true"
                                placeholder="请输入标题"
                                placeholder-style="color:#9CA3AF;font-size:26rpx" />
                            <text
                                v-else
                                class="text-[27rpx] font-bold text-[#111827] block break-words"
                                style="word-break: break-word; white-space: pre-wrap">
                                {{ detail.title }}
                            </text>

                            <view class="flex items-center justify-between mt-[20rpx] mb-[8rpx]">
                                <text class="text-[22rpx] font-bold text-[#9CA3AF]">仿写后正文</text>
                                <text v-if="isSelecting" class="text-[20rpx] text-[#9CA3AF]">
                                    {{ editContent.length }}/1000
                                </text>
                            </view>
                            <textarea
                                v-if="isSelecting"
                                v-model="editContent"
                                class="w-full text-[25rpx] text-[#4B5563] leading-relaxed bg-[#F9FAFB] rounded-[12rpx] px-[20rpx] py-[16rpx] box-border"
                                style="min-height: 220rpx; width: 100%"
                                maxlength="1000"
                                :show-confirm-bar="false"
                                :auto-height="true"
                                placeholder="请输入正文"
                                placeholder-style="color:#9CA3AF;font-size:24rpx" />
                            <text v-else class="text-[25rpx] text-[#4B5563] leading-relaxed block">
                                {{ detail.rewritten_text || detail.publish_text || "-" }}
                            </text>
                        </template>
                        <view v-else-if="isFailed" class="flex items-start gap-[8rpx]">
                            <u-icon name="close-circle" color="#EF4444" size="22"></u-icon>
                            <text class="text-[26rpx] font-semibold text-[#EF4444] leading-relaxed">
                                仿写失败：{{ failReason }}
                            </text>
                        </view>
                        <view v-else class="flex items-center gap-[16rpx]">
                            <u-loading mode="circle" size="28" color="#2563EB"></u-loading>
                            <text class="text-[26rpx] font-semibold text-[#2563EB]">
                                {{ isWash ? "正在洗稿改写标题与正文…" : "正在按人设口吻仿写标题与正文…" }}
                            </text>
                        </view>
                    </view>
                </template>

                <!-- 4 选图：须已提取成功，且进入选图/改写阶段后才展示（提取失败不展示后续） -->
                <template
                    v-if="
                        hasExtracted && (hasRewrittenCopy || isSelecting || isGenerating || isDone || rewriteReached)
                    ">
                    <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                        <view class="step-no" :class="stepClass(4)">4</view>
                        <text class="text-[28rpx] font-bold text-[#111827]">
                            {{ isSelecting ? "逐图分析 · 选择要生成的图" : "逐图分析" }}
                        </text>
                    </view>
                    <view class="grid grid-cols-2 gap-[16rpx]">
                        <view
                            v-for="(item, i) in selectableImages"
                            :key="'s-' + i"
                            class="bg-white rounded-[20rpx] p-[16rpx]"
                            style="box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08)">
                            <view
                                class="relative rounded-[12rpx] overflow-hidden bg-[#E5E7EB]"
                                style="aspect-ratio: 3 / 4"
                                @click="previewImages(originalImages, i)">
                                <image
                                    :src="item.url"
                                    mode="aspectFill"
                                    class="w-full h-full"
                                    :class="item.keep ? '' : 'opacity-50'"
                                    :style="item.keep ? '' : 'filter: grayscale(1)'" />
                                <view
                                    v-if="!item.keep"
                                    class="absolute top-[12rpx] right-[12rpx] px-[12rpx] py-[4rpx] rounded-[6rpx] bg-black/70">
                                    <text class="text-[20rpx] text-white">已移除</text>
                                </view>
                            </view>
                            <view
                                v-if="isSelecting"
                                class="w-full mt-[12rpx] py-[12rpx] rounded-[12rpx] flex items-center justify-center"
                                :class="item.keep ? 'bg-[#F3F4F6]' : 'bg-[#EFF6FF]'"
                                @click="toggleImage(i)">
                                <text
                                    class="text-[22rpx] font-semibold"
                                    :class="item.keep ? 'text-[#6B7280]' : 'text-[#2563EB]'">
                                    {{ item.keep ? "移除" : "恢复使用" }}
                                </text>
                            </view>
                        </view>
                    </view>
                    <text v-if="isSelecting" class="text-[23rpx] text-[#9CA3AF] mt-[16rpx] block">
                        已选 {{ selectedCount }}/{{ selectableImages.length }} 张 · 不想要的图点「移除」，至少保留 1 张
                    </text>
                    <text v-else-if="hasConfirmedSelection" class="text-[23rpx] text-[#9CA3AF] mt-[16rpx] block">
                        已选 {{ selectedImages.length }}/{{ originalImages.length }} 张参与生成 ·
                        灰色「已移除」为未生成的图
                    </text>
                </template>

                <!-- 5 生成图片 -->
                <template v-if="showGenerateSection">
                    <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                        <view class="step-no" :class="isDone ? 'step-done' : 'step-act'">5</view>
                        <text class="text-[28rpx] font-bold text-[#111827]">生成图片</text>
                    </view>
                    <view class="grid grid-cols-2 gap-[16rpx]">
                        <view
                            v-for="(url, i) in generateImages"
                            :key="'g-' + i"
                            class="bg-white rounded-[20rpx] p-[16rpx]"
                            style="box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08)"
                            @click="previewImages(generateImages, i)">
                            <view
                                class="relative rounded-[12rpx] overflow-hidden bg-[#E5E7EB]"
                                style="aspect-ratio: 3 / 4">
                                <image :src="url" mode="aspectFill" class="w-full h-full" lazy-load />
                                <view
                                    v-if="isGenerating"
                                    class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center gap-[8rpx]">
                                    <u-loading mode="circle" size="32" color="#ffffff"></u-loading>
                                    <text class="text-[20rpx] text-white">生成中</text>
                                </view>
                                <view
                                    v-else-if="isDone"
                                    class="absolute top-[12rpx] right-[12rpx] px-[12rpx] py-[4rpx] rounded-[6rpx] bg-[#22C55E]">
                                    <text class="text-[20rpx] text-white font-semibold">已生成</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <text v-if="isDone" class="text-[23rpx] text-[#9CA3AF] mt-[16rpx] block">
                        全部生成完成，可预览图文或一键发布
                    </text>
                </template>
            </view>
        </scroll-view>

        <view
            class="fixed left-0 right-0 bottom-0 px-[32rpx] pt-[24rpx] bg-white border-t border-[#F3F4F6]"
            style="padding-bottom: calc(28rpx + env(safe-area-inset-bottom)); z-index: 20">
            <view
                v-if="isSelecting"
                class="w-full py-[28rpx] rounded-full flex items-center justify-center gap-[16rpx]"
                style="
                    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 60%, #4f8cf7 100%);
                    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
                "
                :class="submitting ? 'opacity-60' : ''"
                @click="handleConfirm">
                <u-loading v-if="submitting" mode="circle" size="28" color="#ffffff"></u-loading>
                <text class="text-base font-semibold text-white">
                    {{ submitting ? "提交中..." : `确认无误，生成 ${selectedCount} 张图` }}
                </text>
                <view
                    v-if="!submitting && estimateTokens > 0"
                    class="rounded-full px-[20rpx] py-[4rpx]"
                    style="background: rgba(255, 255, 255, 0.2)">
                    <text class="text-xs text-white">消耗 {{ estimateTokens }} 算力</text>
                </view>
            </view>
            <view v-else-if="isDone" class="flex gap-[20rpx]">
                <view
                    class="flex-1 py-[28rpx] rounded-full bg-white border border-[#E5E7EB] flex items-center justify-center"
                    @click="handlePreview">
                    <text class="text-sm text-[#4B5563] font-semibold">预览图文</text>
                </view>
                <view
                    class="flex-[1.4] py-[28rpx] rounded-full flex items-center justify-center"
                    style="
                        background: linear-gradient(90deg, #2563eb 0%, #3b82f6 60%, #4f8cf7 100%);
                        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
                    "
                    @click="emit('publish')">
                    <text class="text-[30rpx] text-white font-semibold">一键发布</text>
                </view>
            </view>
            <view
                v-else-if="isFailed"
                class="w-full py-[28rpx] rounded-full flex items-center justify-center gap-[12rpx]"
                style="
                    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 60%, #4f8cf7 100%);
                    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
                "
                :class="retrying ? 'opacity-60' : ''"
                @click="handleRetry">
                <u-loading v-if="retrying" mode="circle" size="28" color="#ffffff"></u-loading>
                <text class="text-base font-semibold text-white">{{ retrying ? "重试中..." : "重新尝试" }}</text>
            </view>
            <view v-else class="w-full py-[28rpx] rounded-full bg-[#E5E7EB] flex items-center justify-center">
                <text class="text-sm text-[#9CA3AF] font-semibold">{{ footerDisabledText }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { confirmImageRewrite, createHotWriteImageText } from "@/api/hot_write";
import {
    HotWriteRewriteMode,
    HotWriteTaskStatus,
    ImageRewriteStatus,
    getTaskPreviewImages,
    isWashTask,
} from "@/ai_modules/hot_write/enums";

const props = defineProps<{
    detail: any;
}>();

const emit = defineEmits<{
    (e: "refresh"): void;
    (e: "publish"): void;
}>();

const submitting = ref(false);
const retrying = ref(false);
const keepMap = ref<Record<number, boolean>>({});
const editTitle = ref("");
const editContent = ref("");

const originalImages = computed(() =>
    Array.isArray(props.detail?.original_images) ? props.detail.original_images.filter(Boolean) : [],
);
const selectedImages = computed(() =>
    Array.isArray(props.detail?.selected_images) ? props.detail.selected_images.filter(Boolean) : [],
);
const rewrittenImages = computed(() =>
    Array.isArray(props.detail?.rewritten_images) ? props.detail.rewritten_images.filter(Boolean) : [],
);

const rewriteStatus = computed(() => Number(props.detail?.image_rewrite_status ?? 0));
const taskStatus = computed(() => Number(props.detail?.status ?? 0));

const isWash = computed(() => isWashTask(props.detail));
const isFailed = computed(() => taskStatus.value === HotWriteTaskStatus.FAIL);
const isSelecting = computed(
    () =>
        !isFailed.value &&
        taskStatus.value === HotWriteTaskStatus.WAIT_CONFIRM &&
        rewriteStatus.value === ImageRewriteStatus.SELECTING,
);
const isGenerating = computed(
    () => !isFailed.value && [ImageRewriteStatus.WAIT, ImageRewriteStatus.PROCESSING].includes(rewriteStatus.value),
);
const isDone = computed(() => taskStatus.value === HotWriteTaskStatus.SUCCESS);

const failReason = computed(() => String(props.detail?.remarks || "").trim() || "任务失败");
const hasExtracted = computed(
    () => !!(String(props.detail?.original_text || "").trim() || originalImages.value.length),
);
const hasRewrittenCopy = computed(
    () =>
        !!(
            String(props.detail?.rewritten_text || "").trim() ||
            (hasExtracted.value && String(props.detail?.title || "").trim())
        ),
);
/** 是否已进入选图/改写相关状态（避免早期失败误展示生成区） */
const rewriteReached = computed(() =>
    [
        ImageRewriteStatus.SELECTING,
        ImageRewriteStatus.WAIT,
        ImageRewriteStatus.PROCESSING,
        ImageRewriteStatus.SUCCESS,
        ImageRewriteStatus.FAIL,
    ].includes(rewriteStatus.value),
);

/** 详情进度：0 解析中；1 待提取；2 已提取待仿写；3 已仿写/选图阶段 */
const step = computed(() => {
    if (!isFailed.value && taskStatus.value === HotWriteTaskStatus.PARSING) return 0;
    if (!hasExtracted.value) return 1;
    if (!hasRewrittenCopy.value && !isSelecting.value && !isGenerating.value && !isDone.value) return 2;
    return 3;
});

const showGenerateSection = computed(() => {
    if (isGenerating.value || isDone.value) return true;
    // 改写失败且确有待展示图片时才显示第 5 步
    return (
        rewriteStatus.value === ImageRewriteStatus.FAIL &&
        (rewrittenImages.value.length > 0 || selectedImages.value.length > 0)
    );
});

const statusLabel = computed(() => {
    if (isFailed.value) return "失败";
    if (isDone.value) return "已完成";
    if (isSelecting.value) return "待选图";
    if (isGenerating.value) return "生成中";
    return "执行中";
});

const originalTitle = computed(() => {
    const text = String(props.detail?.original_text || "");
    const firstLine = text.split("\n").find((l) => l.trim()) || "";
    return firstLine.slice(0, 40) || "原笔记内容";
});

/** 对比原图/已选图 URL（兼容域名补全前后） */
const normalizeImageKey = (url: string) => {
    const raw = String(url || "")
        .trim()
        .split("?")[0]
        .split("#")[0];
    if (!raw) return "";
    try {
        const path = new URL(raw, "https://local.invalid").pathname;
        return decodeURIComponent(path).replace(/\/+/g, "/").toLowerCase();
    } catch {
        return raw.toLowerCase();
    }
};

const isSameImageUrl = (a: string, b: string) => {
    if (!a || !b) return false;
    if (a === b) return true;
    const ka = normalizeImageKey(a);
    const kb = normalizeImageKey(b);
    if (!ka || !kb) return false;
    return ka === kb || ka.endsWith(kb) || kb.endsWith(ka);
};

/** 确认选图后：用 selected_images 还原「已移除」标识 */
const hasConfirmedSelection = computed(() => !isSelecting.value && selectedImages.value.length > 0);

const selectableImages = computed(() =>
    originalImages.value.map((url, index) => {
        let keep = true;
        if (isSelecting.value) {
            keep = keepMap.value[index] !== false;
        } else if (hasConfirmedSelection.value) {
            keep = selectedImages.value.some((s) => isSameImageUrl(s, url));
        }
        return { url, keep };
    }),
);

const selectedCount = computed(() => selectableImages.value.filter((i) => i.keep).length);

const unitPrice = computed(() => Number(props.detail?.rewrite_unit_price || 0));
const estimateTokens = computed(() => {
    if (unitPrice.value > 0) {
        return Number((unitPrice.value * selectedCount.value).toFixed(2));
    }
    return Number(props.detail?.estimated_tokens || 0);
});

/** 生成区：改写结果 > 已确认选图 > 预览兜底（生成中勿回退到全部原图） */
const generateImages = computed(() => {
    if (rewrittenImages.value.length) return rewrittenImages.value;
    if (selectedImages.value.length) return selectedImages.value;
    return getTaskPreviewImages(props.detail);
});

const footerDisabledText = computed(() => {
    if (isGenerating.value) return "图片生成中，可稍后回来查看";
    if (isFailed.value) return props.detail?.remarks || "任务失败";
    return "流水线执行中…";
});

watch(
    () => originalImages.value.join("|"),
    () => {
        const next: Record<number, boolean> = {};
        originalImages.value.forEach((_, i) => {
            next[i] = keepMap.value[i] !== false;
        });
        keepMap.value = next;
    },
    { immediate: true },
);

/** 进入待选图时初始化可编辑标题/正文（避免轮询覆盖用户正在编辑的内容） */
watch(
    () => [props.detail?.id, isSelecting.value] as const,
    ([, selecting], prev) => {
        if (!selecting) return;
        const prevSelecting = prev?.[1];
        const idChanged = prev != null && prev[0] !== props.detail?.id;
        if (prevSelecting && !idChanged) return;
        editTitle.value = String(props.detail?.title || "");
        editContent.value = String(props.detail?.rewritten_text || props.detail?.publish_text || "");
    },
    { immediate: true },
);

const stepClass = (n: number) => {
    // n: 1解析 2提取 3仿写 4选图
    if (isFailed.value) {
        // 失败停在哪一步，该步标红
        if (n === 1 && !hasExtracted.value) return "step-fail";
        if (n === 2 && !hasExtracted.value) return "step-fail";
        if (n === 3 && hasExtracted.value && !hasRewrittenCopy.value) return "step-fail";
        if (n === 4 && hasRewrittenCopy.value && rewriteStatus.value === ImageRewriteStatus.FAIL) {
            return "step-fail";
        }
        if (n === 1 && hasExtracted.value) return "step-done";
        if (n === 2 && hasExtracted.value) return "step-done";
        if (n === 3 && hasRewrittenCopy.value) return "step-done";
    }
    if (n <= 3) {
        if (step.value > n - 1) return "step-done";
        if (step.value === n - 1) return "step-act";
        return "step-pending";
    }
    if (isDone.value || (showGenerateSection.value && !isSelecting.value && !isGenerating.value)) {
        return "step-done";
    }
    if (isSelecting.value || isGenerating.value || step.value >= 3) return "step-act";
    return "step-pending";
};

const toggleImage = (index: number) => {
    if (!isSelecting.value) return;
    const nextKeep = !(keepMap.value[index] !== false);
    if (!nextKeep && selectedCount.value <= 1) {
        uni.$u.toast("至少保留 1 张图");
        return;
    }
    keepMap.value = { ...keepMap.value, [index]: nextKeep };
};

const previewImages = (urls: string[], index: number) => {
    if (!urls.length) return;
    uni.previewImage({ urls, current: urls[index] });
};

const handlePreview = () => {
    const urls = getTaskPreviewImages(props.detail);
    if (!urls.length) {
        uni.$u.toast("暂无可预览图片");
        return;
    }
    uni.previewImage({ urls, current: urls[0] });
};

const handleConfirm = async () => {
    if (submitting.value || !isSelecting.value) return;
    const title = editTitle.value.trim();
    const rewrittenText = editContent.value.trim();
    if (!title) {
        uni.$u.toast("请填写标题");
        return;
    }
    if (!rewrittenText) {
        uni.$u.toast("请填写正文");
        return;
    }
    const indexes = selectableImages.value.map((item, index) => (item.keep ? index : -1)).filter((i) => i >= 0);
    if (!indexes.length) {
        uni.$u.toast("请至少选择 1 张图片");
        return;
    }
    submitting.value = true;
    uni.showLoading({ title: "确认选图...", mask: true });
    try {
        await confirmImageRewrite({
            id: props.detail.id,
            image_indexes: indexes,
            title,
            rewritten_text: rewrittenText,
        });
        uni.hideLoading();
        uni.showToast({ title: "已开始排队改写", icon: "none", duration: 2500 });
        emit("refresh");
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "确认失败", icon: "none", duration: 3000 });
    } finally {
        submitting.value = false;
    }
};

const handleRetry = async () => {
    if (retrying.value || !isFailed.value) return;
    const url = String(props.detail?.prompt || props.detail?.url || "").trim();
    const washTask = isWashTask(props.detail);
    const personaId = washTask ? 0 : props.detail?.persona_id;
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
        await createHotWriteImageText({
            id: props.detail.id,
            url,
            persona_id: personaId,
            rewrite_mode: Number(props.detail?.rewrite_mode) || HotWriteRewriteMode.PERSONA,
        });
        uni.hideLoading();
        uni.showToast({ title: "已重新提交", icon: "none", duration: 2500 });
        emit("refresh");
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "重试失败", icon: "none", duration: 3000 });
    } finally {
        retrying.value = false;
    }
};
</script>

<style scoped lang="scss">
/* 仅保留 sticky / box-shadow 等不宜堆在模板里的样式；布局色值优先 @apply / Tailwind */
.fail-sticky {
    position: sticky;
    top: 0;
}

.card {
    @apply bg-white rounded-[24rpx] px-[28rpx] py-[28rpx];
    box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08);
}

.edit-title {
    @apply break-words;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
}

.step-no {
    @apply w-[40rpx] h-[40rpx] rounded-[8rpx] text-white text-[22rpx] font-bold flex items-center justify-center flex-shrink-0;
}

.step-done {
    @apply bg-[#22C55E];
}

.step-act {
    @apply bg-[#2563EB];
}

.step-pending {
    @apply bg-[#D1D5DB];
}

.step-fail {
    @apply bg-[#EF4444];
}
</style>
