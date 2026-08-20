<template>
    <div v-if="task" class="image-task w-full">
        <div class="task-header">
            <template v-if="hasError && allDone">
                {{ firstErrorMsg }} · {{ task.ratio === "smart" ? "智能比例" : task.ratio }} ·
                {{ task.resolution }}
            </template>
            <template v-else-if="allDone">
                已生成
                <b>{{ successCount }} 张</b>
                {{ task.ratio === "smart" ? "智能比例" : task.ratio }} · {{ task.resolution }} 图片
            </template>
            <template v-else>
                已收到提示词，正在为你生成
                <b>{{ task.count }} 张</b>
                {{ task.ratio === "smart" ? "智能比例" : task.ratio }} · {{ task.resolution }} 图片…
            </template>
        </div>
        <div
            class="image-grid"
            :style="{ gridTemplateColumns: `repeat(${gridCols(task.count)}, 1fr)` }">
            <div
                v-for="(img, idx) in task.images"
                :key="img.id"
                class="image-cell"
                :class="{ loading: !img.done, error: img.error }"
                :style="cellStyle">
                <template v-if="!img.done">
                    <div class="shimmer" />
                    <div class="cell-hint">生成中 {{ idx + 1 }}/{{ task.count }}</div>
                </template>
                <template v-else-if="img.error">
                    <div class="cell-error">
                        <div class="err-icon-wrap">
                            <Icon name="el-icon-Picture" :size="22" />
                        </div>
                        <div class="err-title">{{ img.errorMsg || "生成失败" }}</div>
                        <div class="err-hint">可点击下方重新生成</div>
                    </div>
                </template>
                <template v-else>
                    <img
                        v-if="img.dataUrl"
                        :src="img.dataUrl"
                        :alt="img.title"
                        class="cell-img"
                        @click="openPreview(idx)" />
                    <div v-else class="cell-art" :style="{ background: img.bg }">
                        <div class="art-title">{{ img.title }}</div>
                    </div>
                    <button
                        v-if="img.dataUrl"
                        type="button"
                        class="zoom-btn"
                        title="放大预览"
                        @click.stop="openPreview(idx)">
                        <Icon name="el-icon-ZoomIn" :size="16" />
                    </button>
                </template>
            </div>
        </div>

        <!-- 气泡底部操作条:重新生成 / 下载(全部生成完再出) -->
        <div v-if="allDone" class="bubble-actions">
            <div class="action-btn" @click="$emit('regenerate', task)">
                <Icon name="el-icon-RefreshRight" :size="16" />
                <span>重新生成</span>
            </div>
            <div
                v-if="!hasError"
                class="action-btn"
                :class="{ disabled: downloading }"
                @click="downloadAll">
                <Icon name="el-icon-Download" :size="16" />
                <span>{{ downloading ? "下载中…" : "下载" }}</span>
            </div>
        </div>

        <ElImageViewer
            v-if="previewVisible"
            :url-list="previewUrls"
            :initial-index="previewIndex"
            hide-on-click-modal
            @close="previewVisible = false" />
    </div>
</template>

<script setup lang="ts">
import { ElImageViewer } from "element-plus";
import { downloadFile } from "@/utils/util";
import feedback from "@/utils/feedback";

export interface ImageItem {
    id: number;
    done: boolean;
    bg: string;
    title: string;
    dataUrl: string;
    error?: boolean;
    errorMsg?: string;
}
export interface ImageTask {
    id: number;
    prompt: string;
    ratio: string;
    resolution: string;
    width: number;
    height: number;
    count: number;
    optimized: boolean;
    images: ImageItem[];
}

const props = defineProps<{ task: ImageTask | null }>();
defineEmits<{
    (e: "regenerate", task: ImageTask): void;
}>();

const previewVisible = ref(false);
const previewIndex = ref(0);
const downloading = ref(false);
const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));

function gridCols(n: number) {
    if (n <= 1) return 1;
    if (n <= 4) return 2;
    return 3;
}

const cellStyle = computed(() => {
    if (!props.task) return {};
    const { width, height, ratio } = props.task;
    if (width > 0 && height > 0) return { aspectRatio: `${width} / ${height}` };
    if (ratio && ratio.includes(":")) {
        const [w, h] = ratio.split(":").map(Number);
        if (w > 0 && h > 0) return { aspectRatio: `${w} / ${h}` };
    }
    return { aspectRatio: "1 / 1" };
});

const allDone = computed(
    () => !!props.task && props.task.images.length > 0 && props.task.images.every((i) => i.done),
);

const hasError = computed(() => !!props.task && props.task.images.some((i) => i.error));

const firstErrorMsg = computed(() => {
    const hit = props.task?.images.find((i) => i.error && i.errorMsg);
    return hit?.errorMsg || "生成失败";
});

const successCount = computed(
    () => props.task?.images.filter((i) => i.done && !i.error && i.dataUrl).length ?? 0,
);

const previewUrls = computed(
    () => props.task?.images.filter((i) => i.dataUrl && !i.error).map((i) => i.dataUrl) ?? [],
);

function openPreview(idx: number) {
    const img = props.task?.images[idx];
    if (!img?.dataUrl || img.error) return;
    const urls = previewUrls.value;
    const at = urls.indexOf(img.dataUrl);
    previewIndex.value = at >= 0 ? at : 0;
    previewVisible.value = true;
}

/** 跨域图片 URL 不能依赖 a[download]，需 fetch blob 后再触发本地下载 */
async function downloadAll() {
    if (!props.task || downloading.value) return;
    const list = props.task.images.filter((img) => img.dataUrl && !img.error);
    if (!list.length) {
        feedback.msgWarning("暂无可下载图片");
        return;
    }
    downloading.value = true;
    try {
        for (let i = 0; i < list.length; i++) {
            const img = list[i];
            const name = `${img.title || `image-${i + 1}`}.png`.replace(/[\\/:*?"<>|]+/g, "_");
            await downloadFile(img.dataUrl, name);
            if (i < list.length - 1) await sleep(300);
        }
        feedback.msgSuccess(list.length > 1 ? `已开始下载 ${list.length} 张图片` : "已开始下载");
    } catch {
        feedback.msgError("下载失败，请稍后重试");
    } finally {
        downloading.value = false;
    }
}
</script>

<style lang="scss" scoped>
.image-task {
    background: transparent;
}
.task-header {
    font-size: 13px;
    color: #4b5563;
    margin-bottom: 12px;
    line-height: 1.5;
    b {
        color: #1f2937;
        font-weight: 600;
    }
}
.image-grid {
    display: grid;
    width: 100%;
    gap: 10px;
}
.image-cell {
    position: relative;
    width: 100%;
    min-height: 220px;
    border-radius: 12px;
    overflow: hidden;
    background: #f3f4f6;
    max-height: 380px;
    &.error {
        background: #f8fafc;
        border: 1px dashed #e2e8f0;
        min-height: 220px;
    }
    &:hover .zoom-btn {
        background: rgba(15, 23, 42, 0.82);
    }
    .shimmer {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        &::before {
            content: "";
            position: absolute;
            inset: -50% -50%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.55), transparent);
            animation: shimmer 1.4s infinite;
        }
    }
    .cell-hint {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 12px;
    }
    .cell-error {
        position: absolute;
        inset: 0;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 16px;
        min-height: 220px;
        .err-icon-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #94a3b8;
        }
        .err-title {
            max-width: 90%;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            text-align: center;
            word-break: break-word;
        }
        .err-hint {
            font-size: 12px;
            color: #94a3b8;
        }
    }
    .cell-art {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: flex-end;
        padding: 12px;
        color: #fff;
        font-weight: 600;
    }
    .cell-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        cursor: zoom-in;
    }
    .art-title {
        background: rgba(0, 0, 0, 0.25);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        backdrop-filter: blur(2px);
    }
    .zoom-btn {
        position: absolute;
        right: 10px;
        bottom: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.62);
        color: #fff;
        cursor: pointer;
        transition: background 0.15s;
        &:hover {
            background: rgba(15, 23, 42, 0.82);
        }
    }
}
.bubble-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
}
.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 13px;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s;
    user-select: none;
    &:hover {
        background: #f3f5f9;
        color: #2563eb;
    }
    &.disabled {
        opacity: 0.55;
        pointer-events: none;
    }
}
@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}
</style>
