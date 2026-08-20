<template>
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-br px-5 py-3 flex items-center gap-4 flex-wrap">
            <GeoFilterBar
                v-model:date="filter.date"
                v-model:engine="filter.engine"
                v-model:topic-id="filter.topic_id"
                :engines="engines"
                :topics="topics"
                @change="load" />
            <div class="ml-auto flex items-center gap-3">
                <span class="text-xs text-slate-400 tabular-nums">{{ snapshots.length }} 条</span>
                <ElRadioGroup v-model="viewMode">
                    <ElRadioButton value="list">列表</ElRadioButton>
                    <ElRadioButton value="compare">对照</ElRadioButton>
                </ElRadioGroup>
            </div>
        </div>

        <div class="min-h-[360px]" v-spin="{ show: contentLoading, text: '加载中...' }">
            <section v-if="snapshots.length" class="bg-white rounded-xl border border-br overflow-hidden">
                <!-- 列表：一行一条，原文默认收起 -->
                <template v-if="viewMode === 'list'">
                    <article
                        v-for="s in snapshots"
                        :key="s.id"
                        :id="`snap-${s.id}`"
                        class="snap-row border-b border-[#F1F5F9] last:border-b-0">
                        <div class="flex items-start gap-4 px-5 py-3.5">
                            <button type="button" class="min-w-0 flex-1 text-left" @click="toggleOpen(s.id)">
                                <div class="text-sm font-semibold text-slate-900 leading-snug line-clamp-2">
                                    {{ s.question }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                    <span class="text-xs text-slate-500">{{ s.engine_label }}</span>
                                    <span v-if="s.search_mode === 'web'" class="snap-chip snap-chip--ok">联网</span>
                                    <span v-if="s.topic_name" class="snap-chip">{{ s.topic_name }}</span>
                                    <span class="snap-chip" :class="s.online ? 'snap-chip--ok' : 'snap-chip--mute'">
                                        {{ s.online ? `在线 · 第${s.brand_rank}位` : '离线' }}
                                    </span>
                                    <span class="text-xs text-slate-400">{{ s.time }}</span>
                                </div>
                                <p v-if="openId !== s.id" class="text-xs text-slate-500 mt-2 line-clamp-1">
                                    {{ excerpt(s.raw_answer, 72) }}
                                </p>
                            </button>
                            <div class="flex items-center gap-1 shrink-0 pt-0.5">
                                <ElButton
                                    v-if="s.citations?.length"
                                    class="!h-8 !px-2.5 !rounded-lg"
                                    @click="openCites(s)">
                                    引用 {{ s.citations.length }}
                                </ElButton>
                                <ElTooltip
                                    v-else
                                    :content="
                                        s.search_mode === 'model'
                                            ? '该次为模型直答，不产生引用信源'
                                            : '该引擎本次未透出可溯源的引用信源'
                                    "
                                    placement="top">
                                    <span class="h-8 px-2 inline-flex items-center text-xs text-slate-400">无引用</span>
                                </ElTooltip>
                                <ElButton class="!h-8 !px-2.5 !rounded-lg" @click="openTrend(s)">趋势</ElButton>
                                <ElButton class="!h-8 !px-2.5 !rounded-lg" @click="toggleOpen(s.id)">
                                    {{ openId === s.id ? '收起' : '原文' }}
                                </ElButton>
                            </div>
                        </div>
                        <div v-if="openId === s.id" class="px-5 pb-4">
                            <div class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap max-w-[72ch]">
                                {{
                                    fullId === s.id || (s.raw_answer || '').length <= 480
                                        ? s.raw_answer
                                        : excerpt(s.raw_answer, 480)
                                }}
                            </div>
                            <button
                                v-if="(s.raw_answer || '').length > 480"
                                type="button"
                                class="mt-2 text-xs text-primary hover:underline"
                                @click="fullId = fullId === s.id ? 0 : s.id">
                                {{ fullId === s.id ? '收起长文' : '展开全文' }}
                            </button>
                        </div>
                    </article>
                </template>

                <!-- 对照：同一问题，各引擎并排看在线与摘要 -->
                <template v-else>
                    <article
                        v-for="g in compareGroups"
                        :key="g.key"
                        class="px-5 py-4 border-b border-[#F1F5F9] last:border-b-0">
                        <div class="text-sm font-semibold text-slate-900 leading-snug">{{ g.question }}</div>
                        <div class="flex items-center gap-2 mt-1.5 text-xs text-slate-400">
                            <span v-if="g.topic">{{ g.topic }}</span>
                            <span>{{ g.time }}</span>
                            <span>{{ g.items.length }} 个引擎</span>
                            <span :class="g.onlineCount ? 'text-emerald-600' : 'text-slate-400'">
                                {{ g.onlineCount }} 在线
                            </span>
                        </div>
                        <div class="snap-compare mt-3">
                            <button
                                v-for="s in g.items"
                                :key="s.id"
                                :id="`snap-${s.id}`"
                                type="button"
                                class="snap-cell"
                                :class="{ 'is-open': openId === s.id }"
                                @click="toggleOpen(s.id)">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-slate-800 truncate">{{ s.engine_label }}</span>
                                    <span class="snap-chip shrink-0" :class="s.online ? 'snap-chip--ok' : 'snap-chip--mute'">
                                        {{ s.online ? `第${s.brand_rank}位` : '离线' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed line-clamp-3 text-left">
                                    {{ excerpt(s.raw_answer, 90) || '暂无回答' }}
                                </p>
                                <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                                    <span>{{ s.citations?.length ? `${s.citations.length} 条引用` : '无引用' }}</span>
                                    <span>{{ openId === s.id ? '收起原文' : '看原文' }}</span>
                                </div>
                            </button>
                        </div>
                        <div v-if="g.items.some((s) => s.id === openId)" class="mt-3 pt-3 border-t border-[#F1F5F9]">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <span class="text-xs text-slate-500">{{ openSnap?.engine_label }} · {{ openSnap?.time }}</span>
                                <div class="flex items-center gap-1">
                                    <ElButton
                                        v-if="openSnap?.citations?.length"
                                        class="!h-8 !px-2.5 !rounded-lg"
                                        @click="openCites(openSnap)">
                                        引用 {{ openSnap.citations.length }}
                                    </ElButton>
                                    <ElButton class="!h-8 !px-2.5 !rounded-lg" @click="openTrend(openSnap)">趋势</ElButton>
                                </div>
                            </div>
                            <div class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap max-w-[72ch]">
                                {{
                                    fullId === openId || (openSnap?.raw_answer || '').length <= 480
                                        ? openSnap?.raw_answer
                                        : excerpt(openSnap?.raw_answer, 480)
                                }}
                            </div>
                            <button
                                v-if="(openSnap?.raw_answer || '').length > 480"
                                type="button"
                                class="mt-2 text-xs text-primary hover:underline"
                                @click="fullId = fullId === openId ? 0 : openId">
                                {{ fullId === openId ? '收起长文' : '展开全文' }}
                            </button>
                        </div>
                    </article>
                </template>
            </section>

            <div v-else-if="!contentLoading" class="bg-white rounded-xl border border-br">
                <GeoEmpty description="暂无对话快照，可更换筛选条件或先跑一轮诊断" />
            </div>
        </div>

        <ElDrawer v-model="citeDrawer" title="查看引用文章" size="560px">
            <ElTable :data="curCites" class="geo-plain-table">
                <ElTableColumn label="文章标题" min-width="260">
                    <template #default="{ row }">
                        <a :href="row.url" target="_blank" class="text-slate-700 hover:text-primary">{{ row.title }} ↗</a>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="发布站点" prop="site" width="160" />
            </ElTable>
        </ElDrawer>

        <ElDrawer v-model="trendDrawer" title="在线趋势" size="520px">
            <div v-spin="{ show: trendLoading, text: '加载中...' }" class="min-h-[160px]">
                <template v-if="trend.length">
                    <div class="text-slate-400 text-xs mb-1">可见度分走势(每个点 = 一次监测,悬停看明细)</div>
                    <TrendChart :series="trendChartSeries" :height="160" class="mb-4" />
                    <div class="space-y-2">
                        <div v-for="t in trend" :key="t.id" class="flex items-center gap-3 text-sm">
                            <span class="text-slate-400 text-xs w-24">{{ t.date }}</span>
                            <ElTag
                                :type="t.brand_appear ? 'success' : 'info'"
                                size="small"
                                effect="light"
                                class="w-14 justify-center">
                                {{ t.brand_appear ? '在线' : '离线' }}
                            </ElTag>
                            <div class="flex-1 h-1.5 rounded-full bg-[#eef2f7] overflow-hidden">
                                <div class="h-full bg-primary rounded-full" :style="{ width: t.brand_visibility + '%' }"></div>
                            </div>
                            <span class="text-slate-500 text-xs w-8 text-right">{{ t.brand_visibility }}</span>
                        </div>
                    </div>
                </template>
                <ElEmpty v-else-if="!trendLoading" description="暂无历史监测数据" />
            </div>
        </ElDrawer>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoInsightSnapshots, geoInsightTrend, geoTopics, geoMonitorEngines } from '@/api/geo'
import { useGeoLoading } from '../_composables/use-geo-loading'
import TrendChart from './trend-chart.vue'
import GeoFilterBar from './geo-filter-bar.vue'
import GeoEmpty from './geo-empty.vue'

const props = defineProps<{ pid: number; info: any }>()
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')
const { contentLoading, beginLoad, isLatest, endLoad } = useGeoLoading()

const filter = reactive<any>({ date: '', engine: '', topic_id: '' })
const engines = ref<any[]>([])
const topics = ref<any[]>([])
const snapshots = ref<any[]>([])
const viewMode = ref<'list' | 'compare'>('list')
const openId = ref(0)
const fullId = ref(0)

const excerpt = (text: string, n = 80) => {
    const plain = String(text || '')
        .replace(/[#*`>_\-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
    return plain.length > n ? plain.slice(0, n) + '…' : plain
}

const toggleOpen = (id: number) => {
    openId.value = openId.value === id ? 0 : id
    if (openId.value !== fullId.value) fullId.value = 0
}

const openSnap = computed(() => snapshots.value.find((s) => s.id === openId.value) || null)

const compareGroups = computed(() => {
    const map = new Map<string, { key: string; question: string; topic: string; time: string; items: any[] }>()
    for (const s of snapshots.value) {
        const key = String(s.keyword_id || s.question)
        if (!map.has(key)) {
            map.set(key, { key, question: s.question, topic: s.topic_name || '', time: s.time, items: [] })
        }
        map.get(key)!.items.push(s)
    }
    const order = engines.value.map((e: any) => e.key)
    return [...map.values()].map((g) => {
        g.items.sort((a, b) => {
            const ia = order.indexOf(a.engine)
            const ib = order.indexOf(b.engine)
            return (ia < 0 ? 99 : ia) - (ib < 0 ? 99 : ib)
        })
        return { ...g, onlineCount: g.items.filter((s) => s.online).length }
    })
})

const load = async () => {
    const seq = beginLoad()
    openId.value = 0
    fullId.value = 0
    try {
        const res = (await geoInsightSnapshots({ project_id: props.pid, ...filter })) || []
        if (isLatest(seq)) snapshots.value = res
    } catch (e) {
        if (isLatest(seq)) ElMessage.error(errText(e))
    } finally {
        endLoad(seq)
    }
}

const focus = async (row: any) => {
    filter.date = row.time || ''
    filter.engine = row.engine || ''
    filter.topic_id = ''
    viewMode.value = 'list'
    await load()
    nextTick(() => {
        const el = document.getElementById(`snap-${row.monitor_id}`)
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' })
            return
        }
        const alt = snapshots.value.find((s: any) => s.keyword_id === row.keyword_id && s.engine === row.engine)
        if (alt) {
            openId.value = alt.id
            document.getElementById(`snap-${alt.id}`)?.scrollIntoView({ behavior: 'smooth' })
        }
    })
}
defineExpose({ focus })

const citeDrawer = ref(false)
const curCites = ref<any[]>([])
const openCites = (s: any) => {
    curCites.value = s.citations || []
    citeDrawer.value = true
}

const trendDrawer = ref(false)
const trendLoading = ref(false)
const trend = ref<any[]>([])
const trendChartSeries = computed(() => [{
    name: '可见度分',
    color: '#0065fb',
    unit: '',
    data: trend.value.map((t: any) => ({
        label: t.date,
        value: Number(t.brand_visibility ?? 0),
        extra: t.brand_appear ? '在线' : '离线'
    }))
}])
const openTrend = async (s: any) => {
    if (!s) return
    trendDrawer.value = true
    trendLoading.value = true
    try {
        trend.value = (await geoInsightTrend({ project_id: props.pid, keyword_id: s.keyword_id || 0, engine: s.engine })) || []
    } catch (e) {
        trend.value = []
    } finally {
        trendLoading.value = false
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
.snap-row {
    transition: background-color 150ms ease;
    &:hover { background: #FAFBFC; }
}

.snap-chip {
    @apply inline-flex items-center h-6 px-2 rounded-md text-xs text-slate-600 bg-[#F1F5F9];
}
.snap-chip--ok { @apply text-emerald-700 bg-emerald-50; }
.snap-chip--mute { @apply text-slate-500 bg-[#F1F5F9]; }

.snap-compare {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 10px;
}

.snap-cell {
    @apply text-left rounded-lg border border-[#E8EEF5] bg-[#FAFBFC] p-3 transition-colors duration-150;
    &:hover { background: #fff; border-color: #d6e0ec; }
    &.is-open { background: #fff; border-color: var(--el-color-primary); }
}

@media (prefers-reduced-motion: reduce) {
    .snap-row,
    .snap-cell { transition: none; }
}
</style>
