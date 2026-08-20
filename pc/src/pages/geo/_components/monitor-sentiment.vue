<template>
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-br px-5 py-3">
            <GeoFilterBar
                v-model:date="filter.date"
                v-model:engine="filter.engine"
                v-model:topic-id="filter.topic_id"
                :engines="engines"
                :topics="topics"
                @change="load" />
        </div>

        <div class="min-h-[360px]" v-spin="{ show: contentLoading, text: '加载中...' }">
            <section class="bg-white rounded-xl border border-br overflow-hidden">
                <div class="grid grid-cols-4 divide-x divide-[#F1F5F9]">
                    <div v-for="c in kpiCards" :key="c.label" class="px-5 py-4">
                        <div class="text-xs text-slate-500">{{ c.label }}</div>
                        <div class="tabular-nums mt-1.5 leading-none text-2xl font-semibold" :class="c.valClass">
                            {{ c.val }}
                        </div>
                        <div class="text-xs text-slate-400 mt-2">{{ c.hint }}</div>
                    </div>
                </div>
                <div class="border-t border-[#F1F5F9] px-5 pt-4 pb-5">
                    <div class="flex items-center justify-between gap-4 mb-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">情绪趋势</div>
                            <div class="text-xs text-slate-400 mt-0.5">按天看 AI 回答里正面 / 中立 / 负面的占比</div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 text-xs text-slate-500">
                            <span><i class="dot" style="background: #10b981"></i>正面</span>
                            <span><i class="dot" style="background: #cbd5e1"></i>中立</span>
                            <span><i class="dot" style="background: #ef4444"></i>负面</span>
                        </div>
                    </div>
                    <div v-if="stats.trend?.length" class="space-y-2.5">
                        <div v-for="d in stats.trend" :key="d.date" class="flex items-center gap-3">
                            <span class="text-slate-400 text-xs w-12 shrink-0 tabular-nums">{{ d.date }}</span>
                            <div class="flex-1 flex h-3.5 rounded-full overflow-hidden bg-[#f1f5f9]">
                                <div
                                    v-if="d.pos"
                                    class="h-full"
                                    style="background: #10b981"
                                    :style="{ width: barW(d, 'pos') }"
                                    :title="`正面 ${d.pos}`"></div>
                                <div
                                    v-if="d.neu"
                                    class="h-full"
                                    style="background: #cbd5e1"
                                    :style="{ width: barW(d, 'neu') }"
                                    :title="`中立 ${d.neu}`"></div>
                                <div
                                    v-if="d.neg"
                                    class="h-full"
                                    style="background: #ef4444"
                                    :style="{ width: barW(d, 'neg') }"
                                    :title="`负面 ${d.neg}`"></div>
                            </div>
                            <span class="text-slate-500 text-xs w-14 text-right shrink-0 tabular-nums">{{ d.pos + d.neu + d.neg }} 条</span>
                        </div>
                    </div>
                    <GeoEmpty v-else description="暂无趋势数据：品牌尚未在 AI 回答中出现，先提升可见度" />
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoInsightSentiment, geoTopics, geoMonitorEngines } from '@/api/geo'
import GeoFilterBar from './geo-filter-bar.vue'
import GeoEmpty from './geo-empty.vue'

const props = defineProps<{ pid: number; info: any }>()
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

const filter = reactive<any>({ date: '', engine: '', topic_id: '' })
const engines = ref<any[]>([])
const topics = ref<any[]>([])
const stats = ref<any>({ total: 0, positive: {}, neutral: {}, negative: {}, trend: [] })
const contentLoading = ref(false)

const fmtPct = (v: any) => (v == null || v === '' ? '0%' : `${v}%`)
const fmtCount = (v: any) => `${Number(v) || 0} 条观点`

const kpiCards = computed(() => [
    { label: '提及总量', val: String(Number(stats.value.total) || 0), hint: 'AI 回答中提及本品牌', valClass: 'text-slate-900' },
    { label: '正面', val: fmtPct(stats.value.positive?.rate), hint: fmtCount(stats.value.positive?.count), valClass: 'text-emerald-600' },
    { label: '中立', val: fmtPct(stats.value.neutral?.rate), hint: fmtCount(stats.value.neutral?.count), valClass: 'text-slate-600' },
    { label: '负面', val: fmtPct(stats.value.negative?.rate), hint: fmtCount(stats.value.negative?.count), valClass: 'text-rose-500' }
])

const barW = (d: any, k: string) => {
    const total = d.pos + d.neu + d.neg
    return total ? `${(d[k] / total) * 100}%` : '0%'
}

let loadSeq = 0
const load = async () => {
    const seq = ++loadSeq
    contentLoading.value = true
    try {
        const res: any = await geoInsightSentiment({ project_id: props.pid, ...filter })
        if (seq !== loadSeq) return
        stats.value = res || stats.value
    } catch (e) {
        if (seq === loadSeq) ElMessage.error(errText(e))
    } finally {
        if (seq === loadSeq) contentLoading.value = false
    }
}

onMounted(async () => {
    try {
        const [eRes, tRes]: any = await Promise.all([geoMonitorEngines(), geoTopics(props.pid)])
        engines.value = eRes || []
        topics.value = tRes?.list || []
    } catch (e) { /* 忽略 */ }
    load()
})
</script>

<style lang="scss" scoped>
.dot {
    @apply inline-block w-2.5 h-2.5 rounded-full mr-1 align-middle;
}
</style>
