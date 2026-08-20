<template>
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-br px-5 py-3">
            <GeoFilterBar
                v-model:date="filter.date"
                v-model:engine="filter.engine"
                v-model:topic-id="filter.topic_id"
                :engines="engines"
                :topics="topics"
                @change="load">
                <ElSelect v-model="filter.online" placeholder="全部状态" clearable style="width:130px" @change="load">
                    <ElOption label="在线" :value="1" />
                    <ElOption label="离线" :value="0" />
                </ElSelect>
                <ElButton class="ml-auto !h-9 !rounded-lg" :disabled="!rows.length" @click="exportCsv">导出</ElButton>
            </GeoFilterBar>
        </div>

        <div class="min-h-[360px]" v-spin="{ show: contentLoading, text: '加载中...' }">
            <section class="bg-white rounded-xl border border-br overflow-hidden">
                <ElTable :data="pagedRows" class="geo-plain-table">
                    <ElTableColumn label="时间" prop="time" width="110" />
                    <ElTableColumn label="场景问题" prop="question" min-width="300" show-overflow-tooltip />
                    <ElTableColumn label="话题" prop="topic_name" width="160" />
                    <ElTableColumn label="AI平台" width="120">
                        <template #default="{ row }">{{ row.engine_label }}</template>
                    </ElTableColumn>
                    <ElTableColumn label="在线状态" width="100" align="center">
                        <template #default="{ row }">
                            <ElTag :type="row.online ? 'success' : 'info'" size="small" effect="light">
                                {{ row.online ? '在线' : '离线' }}
                            </ElTag>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="操作" width="90">
                        <template #default="{ row }">
                            <ElButton link type="primary" size="small" @click="$emit('view-snapshot', row)">查看</ElButton>
                        </template>
                    </ElTableColumn>
                    <template #empty><GeoEmpty description="暂无场景问题分析，可更换筛选条件" /></template>
                </ElTable>
                <div v-if="rows.length" class="flex items-center justify-between px-5 py-3 border-t border-[#F1F5F9]">
                    <span class="text-slate-400 text-sm">共 {{ rows.length }} 条</span>
                    <ElPagination
                        v-model:current-page="pageNo"
                        :page-size="pageSize"
                        :total="rows.length"
                        layout="prev, pager, next"
                        background
                        small />
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoInsightScene, geoTopics, geoMonitorEngines } from '@/api/geo'
import { useGeoLoading } from '../_composables/use-geo-loading'
import GeoFilterBar from './geo-filter-bar.vue'
import GeoEmpty from './geo-empty.vue'

const props = defineProps<{ pid: number; info: any }>()
defineEmits(['view-snapshot'])
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')
const { contentLoading, beginLoad, isLatest, endLoad } = useGeoLoading()

const filter = reactive<any>({ date: '', online: '', engine: '', topic_id: '' })
const engines = ref<any[]>([])
const topics = ref<any[]>([])
const rows = ref<any[]>([])
const pageNo = ref(1)
const pageSize = 10
const pagedRows = computed(() => rows.value.slice((pageNo.value - 1) * pageSize, pageNo.value * pageSize))

const load = async () => {
    const seq = beginLoad()
    try {
        const res = (await geoInsightScene({ project_id: props.pid, ...filter })) || []
        if (!isLatest(seq)) return
        rows.value = res
        pageNo.value = 1
    } catch (e) {
        if (isLatest(seq)) ElMessage.error(errText(e))
    } finally {
        endLoad(seq)
    }
}

const exportCsv = () => {
    const data = [['时间', '场景问题', '话题', 'AI平台', '在线状态'], ...rows.value.map((r) => [r.time, r.question, r.topic_name, r.engine_label, r.online ? '在线' : '离线'])]
    const csv = '\ufeff' + data.map((r) => r.map((c: any) => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n')
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }))
    const a = document.createElement('a')
    a.href = url
    a.download = '场景问题分析.csv'
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
