<template>
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-br px-5 py-3">
            <GeoFilterBar
                mode="range"
                v-model:range="range"
                v-model:engine="filter.engine"
                v-model:topic-id="filter.topic_id"
                :engines="engines"
                :topics="topics"
                @change="load" />
        </div>
        <ElAlert
            type="info"
            :closable="true"
            class="!rounded-xl"
            title="默认监测口径为模型直答（不联网），引用数据可能偏少；配置联网检索后，快照页带「联网」标记的引用会更丰富。" />

        <div class="min-h-[360px]" v-spin="{ show: contentLoading, text: '加载中...' }">
            <section class="bg-white rounded-xl border border-br overflow-hidden">
                <div class="grid grid-cols-3 divide-x divide-[#F1F5F9]">
                    <div v-for="c in kpiCards" :key="c.label" class="px-5 py-4">
                        <div class="text-xs text-slate-500">{{ c.label }}</div>
                        <div class="tabular-nums mt-1.5 leading-none text-2xl font-semibold text-slate-900">{{ c.val }}</div>
                        <div class="text-xs text-slate-400 mt-2">{{ c.hint }}</div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 mt-4">
                <section class="bg-white rounded-xl border border-br overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-[#F1F5F9]">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">TOP 引用站点</div>
                            <div class="text-xs text-slate-400 mt-0.5">按引用次数排序，最多 15 条</div>
                        </div>
                        <ElButton class="!h-9 !rounded-lg" :disabled="!sites.length" @click="exportCsv('sites')">导出</ElButton>
                    </div>
                    <ElTable :data="sites" class="geo-plain-table">
                        <ElTableColumn label="站点" prop="site" min-width="220" />
                        <ElTableColumn label="引用次数" prop="cite_count" width="120" align="center" sortable />
                        <ElTableColumn label="引用文章数" prop="article_count" width="120" align="center" />
                        <template #empty><GeoEmpty description="暂无引用站点，可更换筛选条件" /></template>
                    </ElTable>
                </section>

                <section class="bg-white rounded-xl border border-br overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-[#F1F5F9]">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">热门引用文章</div>
                            <div class="text-xs text-slate-400 mt-0.5">按引用次数排序</div>
                        </div>
                        <ElButton class="!h-9 !rounded-lg" :disabled="!articles.length" @click="exportCsv('articles')">导出</ElButton>
                    </div>
                    <ElTable :data="articles" class="geo-plain-table">
                        <ElTableColumn label="文章标题" min-width="320">
                            <template #default="{ row }">
                                <a :href="row.url" target="_blank" class="text-slate-700 hover:text-primary">{{ row.title }}</a>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="发布站点" prop="site" width="200" />
                        <ElTableColumn label="引用次数" prop="cite_count" width="110" align="center" sortable />
                        <template #empty><GeoEmpty description="暂无引用文章，可更换筛选条件" /></template>
                    </ElTable>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoInsightQuotes, geoTopics, geoMonitorEngines } from '@/api/geo'
import { useGeoLoading } from '../_composables/use-geo-loading'
import GeoFilterBar from './geo-filter-bar.vue'
import GeoEmpty from './geo-empty.vue'

const props = defineProps<{ pid: number; info: any }>()
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')
const { contentLoading, beginLoad, isLatest, endLoad } = useGeoLoading()

const range = ref<any>(null)
const filter = reactive<any>({ engine: '', topic_id: '' })
const engines = ref<any[]>([])
const topics = ref<any[]>([])
const sites = ref<any[]>([])
const articles = ref<any[]>([])

const kpiCards = computed(() => {
    const citeSum = sites.value.reduce((n, s) => n + (Number(s.cite_count) || 0), 0)
    return [
        { label: '引用站点', val: String(sites.value.length), hint: '本期被 AI 引用的站点' },
        { label: '引用文章', val: String(articles.value.length), hint: '本期被引用的文章' },
        { label: '站点引用次数', val: String(citeSum), hint: 'TOP 站点引用合计' }
    ]
})

const load = async () => {
    const seq = beginLoad()
    try {
        const res: any = await geoInsightQuotes({
            project_id: props.pid,
            ...filter,
            date_from: range.value?.[0] || '',
            date_to: range.value?.[1] || ''
        })
        if (!isLatest(seq)) return
        sites.value = res?.top_sites || []
        articles.value = res?.top_articles || []
    } catch (e) {
        if (isLatest(seq)) ElMessage.error(errText(e))
    } finally {
        endLoad(seq)
    }
}

const exportCsv = (kind: string) => {
    const rows = kind === 'sites'
        ? [['站点', '引用次数', '引用文章数'], ...sites.value.map((s) => [s.site, s.cite_count, s.article_count])]
        : [['文章标题', '发布站点', '引用次数', '链接'], ...articles.value.map((a) => [a.title, a.site, a.cite_count, a.url])]
    const csv = '\ufeff' + rows.map((r) => r.map((c: any) => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n')
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }))
    const a = document.createElement('a')
    a.href = url
    a.download = `引用分析-${kind === 'sites' ? '站点' : '文章'}.csv`
    a.click()
    URL.revokeObjectURL(url)
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
