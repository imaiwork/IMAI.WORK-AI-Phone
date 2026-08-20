<template>
    <div v-if="visible" class="ppt-panel">
        <div class="op-header">
            <div class="op-title-wrap">
                <div class="op-kicker">PPT 预览</div>
                <div class="op-title" :title="topic || 'PPT 大纲'">{{ topic || "PPT 大纲" }}</div>
            </div>
            <div class="op-actions">
                <button type="button" class="op-icon-btn" title="重新生成" @click="$emit('regenerate')">
                    <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true">
                        <path
                            fill="currentColor"
                            d="M13.5 8a5.5 5.5 0 0 1-9.3 4.05l.9-.9A4.25 4.25 0 1 0 4.1 6.3L5.5 7.7H2V4.2l1.35 1.35A5.5 5.5 0 0 1 13.5 8Z" />
                    </svg>
                    <span>重新生成</span>
                </button>
                <button type="button" class="op-icon-btn ghost" title="关闭" @click="$emit('close')">
                    <svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true">
                        <path
                            fill="currentColor"
                            d="M3.2 3.2a.75.75 0 0 1 1.06 0L8 6.94l3.74-3.74a.75.75 0 1 1 1.06 1.06L9.06 8l3.74 3.74a.75.75 0 1 1-1.06 1.06L8 9.06l-3.74 3.74a.75.75 0 0 1-1.06-1.06L6.94 8 3.2 4.26a.75.75 0 0 1 0-1.06Z" />
                    </svg>
                    <span>关闭</span>
                </button>
            </div>
        </div>

        <div class="op-list" ref="listRef">
            <div v-if="loading && slides.length === 0" class="slide-card loading">
                正在生成大纲，预计 {{ pages }} 页…
            </div>
            <div v-for="(s, idx) in slides" :key="s.id" class="slide-card stream-in">
                <!-- 顶部:页码 + 可编辑标题 -->
                <div class="slide-head">
                    <span class="dot" />
                    <span class="page-badge">P{{ s.page ?? idx + 1 }}</span>
                    <input
                        v-model="s.title"
                        class="sh-title-edit"
                        placeholder="章节标题"
                        @keydown.enter.prevent />
                </div>

                <!-- 缩略图区:真图 / 加载 / 错误 / mock -->
                <div class="slide-thumb" :class="{ 'has-img': !!s.imageUrl }" @click="openViewer(idx)">
                    <img v-if="s.imageUrl" :src="s.imageUrl" class="slide-img" alt="" />
                    <div v-else-if="s.imageLoading" class="slide-loading">
                        <span class="ld-spin" />
                        <span class="ld-text">AI 正在绘制 P{{ idx + 1 }} …</span>
                    </div>
                    <div v-else-if="s.imageError" class="slide-error">
                        <span>⚠️ {{ s.imageError }}</span>
                    </div>
                    <div v-else class="slide-placeholder">
                        <span class="ph-icon">🖼️</span>
                        <span>等待生成图像</span>
                    </div>
                    <span class="zoom-btn" @click.stop="openViewer(idx)">⛶</span>
                </div>

                <!-- 可编辑内容区 + 操作按钮 -->
                <div class="slide-body-edit">
                    <textarea
                        v-model="s.content"
                        class="sb-content-edit"
                        placeholder="章节内容(可编辑)"
                        rows="3" />
                    <div class="slide-actions">
                        <button
                            class="slide-btn"
                            :disabled="s.imageLoading"
                            @click="$emit('regenerate-slide', s.id)">
                            <span v-if="s.imageLoading" class="btn-spin" />
                            <span v-else>↻</span>
                            {{ s.imageLoading ? "生成中…" : "重新生成此页" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="op-footer">
            <button
                type="button"
                class="op-cta"
                :disabled="loading || !hasExportable"
                @click="$emit('export', slides)">
                <svg viewBox="0 0 16 16" width="15" height="15" aria-hidden="true">
                    <path
                        fill="currentColor"
                        d="M8 1.25a.75.75 0 0 1 .75.75v6.19l1.97-1.97a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 1.06-1.06l1.97 1.97V2A.75.75 0 0 1 8 1.25ZM2.75 11a.75.75 0 0 1 .75.75v1a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-1a.75.75 0 0 1 1.5 0v1A2 2 0 0 1 12 14.75H4A2 2 0 0 1 2 12.75v-1a.75.75 0 0 1 .75-.75Z" />
                </svg>
                <span>导出为 PPT</span>
                <span v-if="readyCount" class="op-cta-count">{{ readyCount }} 页</span>
            </button>
        </div>

        <Teleport to="body">
            <div v-if="viewerIdx >= 0" class="fs-viewer" @click="closeViewer">
                <div class="fs-close" @click.stop="closeViewer">✕</div>
                <div class="fs-counter">{{ viewerIdx + 1 }} / {{ slides.length }}</div>
                <div
                    class="fs-stage"
                    :class="{ 'fs-stage-img': !!slides[viewerIdx]?.imageUrl }"
                    @click.stop>
                    <div class="fs-page-no">P{{ slides[viewerIdx]?.page ?? viewerIdx + 1 }}</div>
                    <img
                        v-if="slides[viewerIdx]?.imageUrl"
                        :src="slides[viewerIdx]!.imageUrl"
                        class="fs-real-img"
                        alt="" />
                    <template v-else>
                        <div class="fs-title">{{ slides[viewerIdx]?.title }}</div>
                        <div class="fs-content">{{ slides[viewerIdx]?.content }}</div>
                    </template>
                </div>
                <div class="fs-hint">点击空白处或按 ESC 关闭 · ← → 切换</div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
export interface Slide {
    id: number;
    page?: number;
    title: string;
    content: string;
    imageUrl?: string;
    imageLoading?: boolean;
    imageError?: string;
}

const props = defineProps<{
    visible: boolean;
    topic: string;
    pages: number;
    slides: Slide[];
    loading: boolean;
}>();
defineEmits<{
    (e: "close"): void;
    (e: "regenerate"): void;
    (e: "regenerate-slide", slideId: number): void;
    (e: "export", slides: Slide[]): void;
}>();

const viewerIdx = ref(-1);
const listRef = ref<HTMLElement | null>(null);

const readyCount = computed(() => props.slides.filter((s) => !!s.imageUrl).length);
const hasExportable = computed(() => readyCount.value > 0);

function openViewer(idx: number) {
    viewerIdx.value = idx;
}
function closeViewer() {
    viewerIdx.value = -1;
}
function onKey(e: KeyboardEvent) {
    if (viewerIdx.value < 0) return;
    if (e.key === "Escape") closeViewer();
    if (e.key === "ArrowLeft") viewerIdx.value = Math.max(0, viewerIdx.value - 1);
    if (e.key === "ArrowRight")
        viewerIdx.value = Math.min(props.slides.length - 1, viewerIdx.value + 1);
}

// 新 slide 到来时自动滚到底
watch(
    () => props.slides.length,
    () => {
        nextTick(() => {
            if (listRef.value) listRef.value.scrollTop = listRef.value.scrollHeight;
        });
    },
);

onMounted(() => window.addEventListener("keydown", onKey));
onBeforeUnmount(() => window.removeEventListener("keydown", onKey));
</script>

<style lang="scss" scoped>
.ppt-panel {
    width: 480px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-left: 1px solid #eef0f3;
    height: 100%;
}
.op-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 14px 16px 12px;
    border-bottom: 1px solid #eef0f3;
    gap: 12px;
    flex-shrink: 0;
    background: #fafbfc;
}
.op-title-wrap {
    min-width: 0;
    flex: 1;
}
.op-kicker {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: #6b7280;
    margin-bottom: 2px;
}
.op-title {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.op-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.op-icon-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 30px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #4b5563;
    font-size: 12px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;

    &:hover {
        border-color: #bfdbfe;
        color: #2563eb;
        background: #f8fbff;
    }
    &:focus-visible {
        outline: 2px solid #93c5fd;
        outline-offset: 1px;
    }
    &.ghost {
        border-color: transparent;
        background: transparent;
        color: #6b7280;

        &:hover {
            background: #f3f4f6;
            color: #111827;
            border-color: transparent;
        }
    }
}
.op-list {
    flex: 1;
    overflow-y: auto;
    padding: 14px 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.op-footer {
    flex-shrink: 0;
    padding: 12px 16px 16px;
    border-top: 1px solid #eef0f3;
    background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
}
.op-cta {
    width: 100%;
    height: 42px;
    border: none;
    border-radius: 10px;
    background: #2563eb;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;

    &:hover:not(:disabled) {
        background: #1d4ed8;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.22);
    }
    &:active:not(:disabled) {
        transform: translateY(0.5px);
    }
    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        box-shadow: none;
    }
    .op-cta-count {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        line-height: 1.3;
    }
}
.slide-card {
    background: #1c1f2b;
    border: 1px solid #2a2e3a;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
}
.slide-card.loading {
    background: linear-gradient(135deg, #f9fafb, #fff);
    border-color: #e5e7eb;
    padding: 14px 16px;
    color: #6b7280;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
    &::before {
        content: "";
        width: 14px;
        height: 14px;
        border: 2px solid #dbeafe;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
}
.stream-in {
    animation: streamIn 0.35s ease;
}

/* ====== 头部:页码 + 可编辑标题 ====== */
.slide-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    border-bottom: 1px solid #2a2e3a;
    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        flex-shrink: 0;
    }
    .page-badge {
        background: #2563eb;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 999px;
        flex-shrink: 0;
    }
    .sh-title-edit {
        flex: 1;
        background: transparent;
        border: none;
        outline: none;
        font-size: 13px;
        color: #fff;
        font-weight: 500;
        padding: 4px 6px;
        border-radius: 6px;
        font-family: inherit;
        transition: background 0.15s;
        &:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        &:focus {
            background: rgba(255, 255, 255, 0.1);
        }
        &::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
    }
}

/* ====== 缩略图区 ====== */
.slide-thumb {
    position: relative;
    margin: 12px 14px 0;
    border-radius: 10px;
    overflow: hidden;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    aspect-ratio: 16 / 9;
    cursor: zoom-in;
    &.has-img {
        background: #0f1218;
    }
}
.slide-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.slide-loading,
.slide-error,
.slide-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #047857;
    font-size: 12px;
}
.slide-loading .ld-spin {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(16, 185, 129, 0.3);
    border-top-color: #10b981;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
.slide-error {
    color: #b91c1c;
    background: #fef2f2;
}
.slide-placeholder {
    color: #047857;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    .ph-icon {
        font-size: 22px;
    }
}
.zoom-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 24px;
    height: 24px;
    background: rgba(255, 255, 255, 0.85);
    border-radius: 6px;
    color: #065f46;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    z-index: 2;
    &:hover {
        background: #fff;
    }
}

/* ====== 可编辑内容 + 操作 ====== */
.slide-body-edit {
    padding: 12px 14px 14px;
    .sb-content-edit {
        width: 100%;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 12px;
        color: #e5e7eb;
        line-height: 1.55;
        outline: none;
        resize: vertical;
        font-family: inherit;
        min-height: 60px;
        max-height: 200px;
        transition: border-color 0.15s, background 0.15s;
        &:hover {
            background: rgba(255, 255, 255, 0.06);
        }
        &:focus {
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.08);
        }
        &::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }
    }
    .slide-actions {
        margin-top: 10px;
        display: flex;
        gap: 6px;
    }
    .slide-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.05);
        color: #d1d5db;
        font-size: 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s;
        &:hover:not(:disabled) {
            border-color: #60a5fa;
            color: #93c5fd;
            background: rgba(37, 99, 235, 0.12);
        }
        &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-spin {
            width: 11px;
            height: 11px;
            border: 1.5px solid rgba(147, 197, 253, 0.35);
            border-top-color: #93c5fd;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
    }
}

/* ====== 全屏 viewer ====== */
.fs-viewer {
    position: fixed;
    inset: 0;
    background: rgba(8, 10, 16, 0.96);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: zoom-out;
}
.fs-close {
    position: absolute;
    top: 24px;
    right: 28px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    &:hover {
        background: rgba(255, 255, 255, 0.18);
    }
}
.fs-counter {
    position: absolute;
    top: 28px;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255, 255, 255, 0.7);
    font-size: 14px;
}
.fs-hint {
    position: absolute;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255, 255, 255, 0.45);
    font-size: 12px;
}
.fs-stage {
    width: min(1100px, 92vw);
    max-height: 84vh;
    aspect-ratio: 16 / 9;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    padding: 60px 64px;
    position: relative;
    cursor: default;
    &.fs-stage-img {
        padding: 0;
        background: #0f1218;
    }
    .fs-real-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .fs-title {
        font-size: 44px;
        font-weight: 800;
        color: #065f46;
        margin-bottom: 24px;
        line-height: 1.15;
    }
    .fs-content {
        font-size: 18px;
        color: #047857;
        line-height: 1.6;
        white-space: pre-wrap;
    }
    .fs-page-no {
        position: absolute;
        top: 28px;
        left: 28px;
        background: #2563eb;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        padding: 4px 14px;
        border-radius: 999px;
        z-index: 2;
    }
}

@keyframes streamIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
