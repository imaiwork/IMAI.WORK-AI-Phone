<template>
    <div v-if="task" class="video-task w-full">
        <div class="task-header">
            <template v-if="hasError && allDone">
                {{ firstErrorMsg }} · {{ task.ratio }} · {{ task.resolution }}
            </template>
            <template v-else-if="allDone">
                已生成
                <b>{{ successCount }} 条</b>
                {{ task.ratio }} · {{ task.resolution }} 视频（{{ task.modelName }} · {{ task.typeName }}）
            </template>
            <template v-else>
                正在生成
                <b>{{ task.count }} 条</b>
                {{ task.ratio }} · {{ task.resolution }} 视频（{{ task.modelName }} · {{ task.typeName }}）…
            </template>
        </div>

        <div class="video-grid" :style="{ gridTemplateColumns: `repeat(${gridCols(task.count)}, 1fr)` }">
            <div
                v-for="(v, idx) in task.videos"
                :key="v.id"
                class="video-cell"
                :class="{ loading: v.loading, error: v.error }"
                :style="cellStyle">
                <template v-if="v.error">
                    <div class="cell-error">
                        <div class="err-icon-wrap">
                            <Icon name="el-icon-VideoCamera" :size="22" />
                        </div>
                        <div class="err-title">{{ v.msg || "生成失败" }}</div>
                        <div class="err-hint">可点击下方重新生成</div>
                    </div>
                </template>
                <template v-else-if="v.url">
                    <video
                        :src="v.url"
                        controls
                        playsinline
                        preload="metadata"
                        class="cell-video" />
                </template>
                <template v-else>
                    <div class="shimmer" />
                    <div class="cell-hint">
                        <div class="ld-spin" />
                        生成中 {{ idx + 1 }}/{{ task.count }}
                        <span v-if="v.progress > 0">· {{ v.progress }}%</span>
                    </div>
                </template>
            </div>
        </div>

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
    </div>
</template>

<script setup lang="ts">
import { downloadFile } from "@/utils/util";
import feedback from "@/utils/feedback";

export interface VideoItem {
    id: number;
    url: string;
    loading: boolean;
    progress: number;
    status: number;
    error: boolean;
    msg?: string;
}
export interface VideoTask {
    id: number;
    prompt: string;
    ratio: string;
    resolution: string;
    /** TXT2VIDEO=0 / IMG2VIDEO=1 */
    type: number;
    typeName: string;
    /** GENERAL=2(即梦) / SEEDANCE=4(豆包) */
    model: number;
    modelName: string;
    count: number;
    videos: VideoItem[];
}

const props = defineProps<{ task: VideoTask | null }>();
defineEmits<{
    (e: "regenerate", task: VideoTask): void;
}>();

const downloading = ref(false);

function gridCols(n: number) {
    if (n <= 1) return 1;
    if (n <= 4) return 2;
    return 3;
}

const cellStyle = computed(() => {
    if (!props.task) return {};
    const [w, h] = props.task.ratio.split(":").map(Number);
    if (!w || !h) return { aspectRatio: "16 / 9" };
    return { aspectRatio: `${w} / ${h}` };
});

const allDone = computed(
    () =>
        !!props.task &&
        props.task.videos.length > 0 &&
        props.task.videos.every((v) => !v.loading),
);

const hasError = computed(() => !!props.task && props.task.videos.some((v) => v.error));

const firstErrorMsg = computed(() => {
    const hit = props.task?.videos.find((v) => v.error && v.msg);
    return hit?.msg || "生成失败";
});

const successCount = computed(
    () => props.task?.videos.filter((v) => !v.loading && !v.error && v.url).length ?? 0,
);

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));

/** 跨域视频 URL 不能依赖 a[download]，需 fetch blob 后再触发本地下载 */
async function downloadAll() {
    if (!props.task || downloading.value) return;
    const list = props.task.videos.filter((v) => v.url && !v.error);
    if (!list.length) {
        feedback.msgWarning("暂无可下载视频");
        return;
    }
    downloading.value = true;
    try {
        for (let i = 0; i < list.length; i++) {
            await downloadFile(list[i].url, `video-${i + 1}.mp4`);
            if (i < list.length - 1) await sleep(300);
        }
    } catch {
        feedback.msgError("下载失败，请稍后重试");
    } finally {
        downloading.value = false;
    }
}
</script>

<style lang="scss" scoped>
.video-task {
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
.video-grid {
    display: grid;
    width: 100%;
    gap: 10px;
}
.video-cell {
    position: relative;
    width: 100%;
    min-height: 220px;
    border-radius: 12px;
    overflow: hidden;
    background: #1f1f1f;
    max-height: 480px;
    &.error {
        background: #f8fafc;
        border: 1px dashed #e2e8f0;
        min-height: 220px;
    }
    .shimmer {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, #1f2937, #374151);
        &::before {
            content: "";
            position: absolute;
            inset: -50% -50%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18), transparent);
            animation: shimmer 1.4s infinite;
        }
    }
    .cell-hint {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #d1d5db;
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
    .cell-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        background: #000;
    }
    .ld-spin {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255, 255, 255, 0.18);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
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
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
