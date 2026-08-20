<template>
    <ElDrawer
        :model-value="modelValue"
        title="任务日志"
        size="520px"
        append-to-body
        @update:model-value="emit('update:modelValue', $event)">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center p-1 rounded-xl bg-slate-50" role="tablist" aria-label="按状态筛选">
                <button
                    v-for="f in FILTERS"
                    :key="f.key"
                    type="button"
                    class="h-8 px-2.5 rounded-lg text-xs text-slate-500"
                    :class="{ 'bg-white text-primary font-semibold shadow-sm': filter === f.key }"
                    @click="filter = f.key">
                    {{ f.label }}
                    <span v-if="counts[f.key]" class="tabular-nums ml-0.5">{{ counts[f.key] }}</span>
                </button>
            </div>
            <button type="button" class="text-xs text-slate-400 hover:text-slate-700 shrink-0" :disabled="loading" @click="emit('refresh')">
                刷新
            </button>
        </div>

        <div class="min-h-[240px]" v-spin="{ show: loading, text: '加载中...' }">
            <div v-if="shown.length" class="border border-br rounded-xl overflow-hidden divide-y divide-[#F1F5F9]">
                <div v-for="t in shown" :key="t.id">
                    <button
                        type="button"
                        class="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-slate-50"
                        :aria-expanded="openId === t.id"
                        @click="toggle(t.id)">
                        <span class="mt-1.5 w-2 h-2 rounded-full shrink-0" :class="dotClass(t.status)"></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-3">
                                <span class="text-sm font-medium text-slate-900 truncate">{{ typeLabel(t.task_type) }}</span>
                                <span class="text-xs text-slate-400 shrink-0 tabular-nums">{{ fmtWhen(t.create_time) }}</span>
                            </span>
                            <span class="block text-xs mt-0.5" :class="statusClass(t.status)">
                                {{ STATUS_MAP[t.status] || t.status }}
                                <span v-if="fmtDur(t)" class="text-slate-400"> · {{ fmtDur(t) }}</span>
                            </span>
                        </span>
                    </button>
                    <div v-if="openId === t.id" class="px-4 pb-3 pl-9">
                        <div v-if="detailLoading && detail?.id !== t.id" class="text-xs text-slate-400 py-2">加载步骤…</div>
                        <ol v-else-if="detailLogs.length" class="space-y-2.5">
                            <li v-for="(l, i) in detailLogs" :key="i" class="text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-400 tabular-nums shrink-0">{{ logTime(l.ts) }}</span>
                                    <span class="font-semibold text-slate-700">{{ l.step }}</span>
                                </div>
                                <div v-if="l.message" class="text-slate-500 mt-0.5 leading-relaxed break-all">{{ l.message }}</div>
                            </li>
                        </ol>
                        <div v-else class="text-xs text-slate-400 py-2">这条任务还没有步骤记录</div>
                    </div>
                </div>
            </div>
            <GeoEmpty v-else-if="!loading" :description="filter === 'all' ? '还没有任务记录' : '这个状态下没有任务'" />
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
import { geoTask } from '@/api/geo'
import GeoEmpty from './geo-empty.vue'

type FilterKey = 'all' | 'active' | 'failed' | 'success'

const FILTERS: { key: FilterKey; label: string }[] = [
    { key: 'all', label: '全部' },
    { key: 'active', label: '进行中' },
    { key: 'failed', label: '失败' },
    { key: 'success', label: '已完成' }
]

const TYPE_MAP: Record<string, string> = {
    build_context: '建立品牌上下文',
    parse_knowledge: '解析知识',
    analyze_brand: '品牌分析',
    gen_keyword: '生成关键词',
    gen_content: '生成内容',
    monitor: 'AI搜索监测',
    monitor_batch: '批量监测',
    gen_suggestion: '生成建议'
}
const STATUS_MAP: Record<string, string> = {
    pending: '排队中',
    running: '进行中',
    success: '已完成',
    failed: '失败'
}

const props = defineProps<{
    modelValue: boolean
    tasks: any[]
    loading?: boolean
}>()
const emit = defineEmits(['update:modelValue', 'refresh'])

const filter = ref<FilterKey>('all')
const openId = ref(0)
const detail = ref<any>(null)
const detailLoading = ref(false)

const matchFilter = (t: any, key: FilterKey) => {
    if (key === 'all') return true
    if (key === 'active') return t.status === 'running' || t.status === 'pending'
    return t.status === key
}
const counts = computed(() => {
    const list = props.tasks || []
    return {
        all: list.length,
        active: list.filter((t) => matchFilter(t, 'active')).length,
        failed: list.filter((t) => t.status === 'failed').length,
        success: list.filter((t) => t.status === 'success').length
    }
})
const shown = computed(() => (props.tasks || []).filter((t) => matchFilter(t, filter.value)))
const detailLogs = computed(() => (Array.isArray(detail.value?.logs) ? detail.value.logs : []))

const typeLabel = (type: string) => TYPE_MAP[type] || type
const statusClass = (s: string) =>
    ({ success: 'text-emerald-600', failed: 'text-rose-500', running: 'text-amber-600', pending: 'text-slate-400' } as Record<string, string>)[s] || 'text-slate-500'
const dotClass = (s: string) =>
    ({ success: 'bg-emerald-500', failed: 'bg-rose-500', running: 'bg-amber-500', pending: 'bg-slate-300' } as Record<string, string>)[s] || 'bg-slate-300'

const toTs = (t: any) => {
    if (t == null || t === '') return 0
    if (typeof t === 'number') return t < 1e12 ? t : Math.floor(t / 1000)
    const s = String(t)
    if (/^\d+$/.test(s)) return Number(s)
    const n = Date.parse(s.replace(/-/g, '/'))
    return Number.isNaN(n) ? 0 : Math.floor(n / 1000)
}
const pad = (n: number) => String(n).padStart(2, '0')
const fmtWhen = (t: any) => {
    const ts = toTs(t)
    if (!ts) return '–'
    const d = new Date(ts * 1000)
    const now = new Date()
    const hm = `${pad(d.getHours())}:${pad(d.getMinutes())}`
    if (d.toDateString() === now.toDateString()) return `今天 ${hm}`
    const y = new Date(now)
    y.setDate(now.getDate() - 1)
    if (d.toDateString() === y.toDateString()) return `昨天 ${hm}`
    return `${d.getMonth() + 1}/${d.getDate()} ${hm}`
}
const fmtDur = (row: any) => {
    const a = toTs(row.create_time)
    const b = row.status === 'running' || row.status === 'pending' ? Math.floor(Date.now() / 1000) : toTs(row.update_time)
    if (!a || !b || b < a) return ''
    const s = b - a
    if (s < 60) return `${s} 秒`
    if (s < 3600) return `${Math.max(1, Math.round(s / 60))} 分钟`
    return `${Math.max(1, Math.round(s / 3600))} 小时`
}
const logTime = (ts: string) => {
    if (!ts) return ''
    const s = String(ts)
    return s.length >= 19 ? s.slice(11, 19) : s
}

const toggle = async (id: number) => {
    if (openId.value === id) {
        openId.value = 0
        return
    }
    openId.value = id
    if (detail.value?.id === id) return
    detailLoading.value = true
    try {
        detail.value = (await geoTask(id)) || null
    } catch {
        detail.value = null
    } finally {
        detailLoading.value = false
    }
}

watch(
    () => props.modelValue,
    (v) => {
        if (v) emit('refresh')
        else {
            openId.value = 0
            filter.value = 'all'
        }
    }
)
</script>
