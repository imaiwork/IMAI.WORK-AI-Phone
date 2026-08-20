<template>
    <Teleport to="body">
        <div v-if="visible" class="history-popup-portal" :style="positionStyle" @click.stop>
            <div class="history-popup-head">
                <div class="popup-title !mb-0">历史</div>
                <button v-if="showNew" type="button" class="history-new" @click="emit('new')">新会话</button>
            </div>
            <div v-if="loading" class="history-empty">加载中…</div>
            <div v-else-if="list.length === 0" class="history-empty">暂无历史会话</div>
            <div v-else class="history-list">
                <div
                    v-for="s in list"
                    :key="s.id"
                    class="history-item"
                    :class="{ active: String(activeId) === s.id }"
                    @click="emit('restore', s)">
                    <span class="history-title">{{ s.title || '未命名会话' }}</span>
                    <span class="history-time">{{ relativeTime(s.timestamp) }}</span>
                    <button
                        class="history-del"
                        title="删除"
                        :disabled="deletingId === s.id"
                        @click.stop="emit('delete', s.id)">
                        <Icon
                            v-if="deletingId === s.id"
                            name="el-icon-Loading"
                            :size="12"
                            class="animate-spin" />
                        <template v-else>×</template>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import type { DrawHistorySessionItem } from '../../_composables/useDrawConversation'

defineProps<{
    visible: boolean
    positionStyle: Record<string, string>
    list: DrawHistorySessionItem[]
    loading: boolean
    deletingId: string
    activeId: number | string
    showNew: boolean
}>()

const emit = defineEmits<{
    new: []
    restore: [item: DrawHistorySessionItem]
    delete: [id: string]
}>()

function relativeTime(ts: number) {
    if (!ts) return ''
    const diff = Date.now() - ts
    if (diff < 60_000) return '刚刚'
    if (diff < 3600_000) return `${Math.floor(diff / 60000)}分钟前`
    if (diff < 86400_000) return `${Math.floor(diff / 3600000)}小时前`
    if (diff < 7 * 86400_000) return `${Math.floor(diff / 86400000)}天前`
    const d = new Date(ts)
    return `${d.getMonth() + 1}/${d.getDate()}`
}
</script>

<style lang="scss" scoped>
.history-popup-portal {
    @apply z-[2000] box-border w-[280px] rounded-[14px] border border-[#ebedf0] bg-white p-3 shadow-[0_8px_32px_rgba(0,0,0,0.12)];

    .history-popup-head {
        @apply mb-2 flex items-center justify-between gap-2;
    }
    .popup-title {
        @apply mb-2 text-[13px] font-semibold text-[#1f2937];
    }
    .history-new {
        @apply cursor-pointer rounded-md border-0 bg-[#eff6ff] px-2 py-0.5 text-[11px] text-[#2563eb];
        &:hover {
            @apply bg-[#dbeafe];
        }
    }
    .history-empty {
        @apply px-2 py-6 text-center text-xs text-[#9ca3af];
    }
    .history-list {
        @apply max-h-[280px] overflow-y-auto pr-0.5;

        &::-webkit-scrollbar {
            width: 4px;
        }
        &::-webkit-scrollbar-thumb {
            @apply rounded-full bg-[#e5e7eb];
        }
    }
    .history-item {
        @apply relative flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] text-[#4b5563];

        &:hover {
            @apply bg-[#f3f5f9];

            .history-del {
                @apply opacity-100;
            }
        }
        &.active {
            @apply bg-[#eff6ff] text-[#2563eb];
        }
    }
    .history-title {
        @apply min-w-0 flex-1 truncate;
    }
    .history-time {
        @apply flex-shrink-0 whitespace-nowrap text-[11px] text-[#c4c8cf];
    }
    .history-del {
        @apply flex h-[18px] w-[18px] flex-shrink-0 cursor-pointer items-center justify-center rounded-full border-0 bg-black/40 text-xs leading-none text-white opacity-0 transition-opacity duration-150;

        &:hover {
            @apply bg-[#ef4444];
        }
        &:disabled {
            @apply cursor-wait opacity-100;
        }
    }
}
</style>
