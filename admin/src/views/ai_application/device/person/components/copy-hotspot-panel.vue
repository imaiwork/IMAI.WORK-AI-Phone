<template>
    <div class="mt-3">
        <div class="flex items-center gap-1 flex-wrap">
            <svg
                class="w-3.5 h-3.5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
            >
                <polyline
                    points="15 10 20 15 15 20"
                    stroke="#9CA3AF"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    d="M4 4v7a4 4 0 0 0 4 4h12"
                    stroke="#9CA3AF"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
            <span class="text-xs font-semibold text-[#1D2129]">热点库</span>
            <span class="text-[11px] text-[#9CA3AF]">·</span>
            <span
                class="text-[11px]"
                :class="pendingCount ? 'text-primary font-semibold' : 'text-[#9CA3AF]'"
            >
                {{ pendingCount }}{{ PENDING_COUNT_SUFFIX }}
            </span>
            <span class="text-[11px] text-[#9CA3AF]">·</span>
            <span class="text-[11px] text-[#9CA3AF]">
                {{ usedCount }}{{ USED_COUNT_SUFFIX }}
            </span>
        </div>
        <p class="mt-1 text-[11px] leading-5 text-[#4B5563]">
            在「热点追踪」里分析热点后点「入库」，这里就能取用
        </p>

        <div v-if="loading || items.length" v-loading="loading" class="mt-2 min-h-[48px]">
            <ul v-if="items.length" class="max-h-60 overflow-y-auto rounded-xl bg-[#F7F9FC]">
                <li
                    v-for="(item, index) in items"
                    :key="item.id || index"
                    class="flex items-center gap-1 px-3 py-2.5"
                    :class="[
                        index > 0 ? 'border-t border-[#EEF2F8]' : '',
                        item.useStatus === HotspotStockUseStatus.Used ? 'opacity-60' : ''
                    ]"
                >
                    <svg
                        class="w-3.5 h-3.5 shrink-0"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                    >
                        <path
                            d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"
                            stroke="#F86E21"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span
                        class="grow min-w-0 text-xs font-semibold text-[#1D2129] line-clamp-1 break-all"
                    >
                        {{ item.title }}
                    </span>
                    <div class="shrink-0 flex items-center gap-0.5">
                        <span
                            class="text-[10px] leading-4 px-1 py-px rounded"
                            :class="useStatusClass(item.useStatus)"
                        >
                            {{ USE_STATUS_LABEL[item.useStatus] }}
                        </span>
                        <span
                            class="text-[10px] leading-4 px-1 py-px rounded"
                            :class="kindClass(item.kind)"
                        >
                            {{ KIND_LABEL[item.kind] }}
                        </span>
                        <span class="text-[10px] text-[#9CA3AF]">
                            {{ item.platform }}{{ item.date ? ` ${item.date}` : '' }}
                        </span>
                    </div>
                    <button
                        v-if="item.id"
                        type="button"
                        class="shrink-0 w-6 h-6 flex items-center justify-center text-[#C0C8D8] hover:opacity-70 disabled:opacity-40"
                        :disabled="isLock"
                        aria-label="移出热点库"
                        @click="handleAskDelete(item)"
                    >
                        <el-icon :size="14"><Delete /></el-icon>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Delete } from '@element-plus/icons-vue'
import { deleteHotspotStock, getHotspotStock } from '@/api/ai_application/hotspot'
import { useLockFn } from '@/hooks/useLockFn'
import feedback from '@/utils/feedback'

enum HotspotLibItemKind {
    TitleOnly = 'title_only',
    WithScript = 'with_script'
}

enum HotspotStockHasScript {
    TitleOnly = 0,
    WithScript = 1
}

enum HotspotStockUseStatus {
    Pending = 0,
    Used = 1
}

interface HotspotLibItem {
    id: number
    title: string
    platform: string
    date: string
    kind: HotspotLibItemKind
    useStatus: HotspotStockUseStatus
}

interface HotspotStockRow {
    id?: number
    topic?: string
    title?: string
    platform?: string
    has_script?: number
    use_status?: number
    use_count?: number
    create_time?: number
}

const UNIX_SEC_MIN = 1e9

const HOTSPOT_PLATFORM_LABELS: Record<string, string> = {
    douyin: '抖音',
    kuaishou: '快手',
    xiaohongshu: '小红书',
    weibo: '微博'
}

const KIND_LABEL: Record<HotspotLibItemKind, string> = {
    [HotspotLibItemKind.TitleOnly]: '仅标题',
    [HotspotLibItemKind.WithScript]: '已带文案'
}

const USE_STATUS_LABEL: Record<HotspotStockUseStatus, string> = {
    [HotspotStockUseStatus.Pending]: '待用',
    [HotspotStockUseStatus.Used]: '已使用'
}

const PENDING_COUNT_SUFFIX = '条待用'
const USED_COUNT_SUFFIX = '条已使用'

const HOTSPOT_STOCK_DELETE_CONFIRM = '确定将这条热点移出热点库吗？'

const props = defineProps<{
    personaId: string | number
}>()

const items = ref<HotspotLibItem[]>([])
const pendingCount = ref(0)
const usedCount = ref(0)
const loading = ref(false)
let requestToken = 0

const personaIdText = computed(() => String(props.personaId ?? '').trim())

const useStatusClass = (status: HotspotStockUseStatus): string =>
    status === HotspotStockUseStatus.Pending ? 'bg-[#EEF4FF] text-primary' : 'bg-[#F3F4F6] text-[#9CA3AF]'

const kindClass = (kind: HotspotLibItemKind): string =>
    kind === HotspotLibItemKind.WithScript ? 'bg-[#ECFDF5] text-[#10B981]' : 'bg-[#F3F4F6] text-[#9CA3AF]'

const formatStockDate = (value: unknown): string => {
    const stamp = Number(value)
    if (!Number.isFinite(stamp) || stamp < UNIX_SEC_MIN) return ''
    const date = new Date(stamp * 1000)
    const pad = (num: number) => String(num).padStart(2, '0')
    return `${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

const toCount = (value: unknown, fallback: number): number => {
    const num = Number(value)
    return Number.isFinite(num) && num >= 0 ? num : fallback
}

const normalizeUseStatus = (row: HotspotStockRow): HotspotStockUseStatus => {
    const status = Number(row.use_status)
    if (status === HotspotStockUseStatus.Used) return HotspotStockUseStatus.Used
    if (status === HotspotStockUseStatus.Pending) return HotspotStockUseStatus.Pending
    return Number(row.use_count) > 0 ? HotspotStockUseStatus.Used : HotspotStockUseStatus.Pending
}

const normalizeStockItem = (row: HotspotStockRow): HotspotLibItem => {
    const hasScript = Number(row.has_script) === HotspotStockHasScript.WithScript
    return {
        id: Number(row.id) || 0,
        title: String(row.topic || row.title || '').trim(),
        platform: HOTSPOT_PLATFORM_LABELS[String(row.platform || '')] || String(row.platform || ''),
        date: formatStockDate(row.create_time),
        kind: hasScript ? HotspotLibItemKind.WithScript : HotspotLibItemKind.TitleOnly,
        useStatus: normalizeUseStatus(row)
    }
}

const countByStatus = (rows: HotspotLibItem[], status: HotspotStockUseStatus): number =>
    rows.filter((row) => row.useStatus === status).length

const resetStockState = (): void => {
    items.value = []
    pendingCount.value = 0
    usedCount.value = 0
}

const applyStockResult = (res: {
    lists?: HotspotStockRow[]
    pending_count?: number
    used_count?: number
}): void => {
    const lists = Array.isArray(res?.lists) ? res.lists : []
    const nextItems = lists.map((row) => normalizeStockItem(row))
    items.value = nextItems
    pendingCount.value = toCount(
        res?.pending_count,
        countByStatus(nextItems, HotspotStockUseStatus.Pending)
    )
    usedCount.value = toCount(res?.used_count, countByStatus(nextItems, HotspotStockUseStatus.Used))
}

const queryHotspotStock = async (personaId: string, token: number): Promise<void> => {
    if (!personaId) {
        if (token === requestToken) {
            resetStockState()
            loading.value = false
        }
        return
    }
    loading.value = true
    try {
        const res = await getHotspotStock({ persona_id: personaId })
        if (token !== requestToken) return
        applyStockResult(res || {})
    } catch {
        if (token !== requestToken) return
        resetStockState()
    } finally {
        if (token === requestToken) loading.value = false
    }
}

watch(
    personaIdText,
    (personaId) => {
        requestToken += 1
        queryHotspotStock(personaId, requestToken)
    },
    { immediate: true }
)

const { isLock, lockFn: submitDelete } = useLockFn(async (item: HotspotLibItem) => {
    await deleteHotspotStock({ id: item.id, persona_id: personaIdText.value })
    items.value = items.value.filter((row) => row.id !== item.id)
    if (item.useStatus === HotspotStockUseStatus.Used) {
        usedCount.value = Math.max(0, usedCount.value - 1)
    } else {
        pendingCount.value = Math.max(0, pendingCount.value - 1)
    }
})

const handleAskDelete = async (item: HotspotLibItem): Promise<void> => {
    if (!item.id) return
    try {
        await feedback.confirm(HOTSPOT_STOCK_DELETE_CONFIRM)
    } catch {
        return
    }
    try {
        await submitDelete(item)
    } catch {
        // 失败提示由请求封装处理
    }
}
</script>
