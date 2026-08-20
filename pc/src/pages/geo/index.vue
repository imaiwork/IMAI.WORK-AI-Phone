<template>
    <GeoConfirm />
    <InitWizard v-if="showCreate" :pid="0" @done="onWizardDone" @back="onWizardBack" />

    <div v-else class="h-full flex flex-col min-w-[1000px] px-4 pb-4">
        <div class="h-[120px] shrink-0 rounded-[20px] bg-white border border-br px-10 flex items-center justify-between">
            <div class="flex items-center gap-6 min-w-0">
                <div class="w-20 h-20 rounded-[20px] bg-primary/10 text-primary grid place-items-center shrink-0">
                    <Icon name="el-icon-DataLine" :size="36" />
                </div>
                <div class="min-w-0">
                    <h1 class="text-[20px] font-bold text-slate-800">GEO 中心</h1>
                    <p class="text-base text-slate-500 mt-1">监测品牌在 AI 回答中的可见度，并据此生成可发布内容</p>
                </div>
            </div>
            <ElButton type="primary" class="!h-12 !px-5 !rounded-2xl" @click="showCreate = true">
                <Icon name="el-icon-Plus" :size="16" class="mr-1" />
                创建项目
            </ElButton>
        </div>

        <div class="grow min-h-0 bg-white rounded-[20px] border border-br overflow-hidden mt-4">
            <ElScrollbar>
                <div v-loading="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 p-8">
                    <button type="button" class="geo-create-card" @click="showCreate = true">
                        <div class="w-14 h-14 rounded-full bg-slate-100 grid place-items-center">
                            <Icon name="el-icon-Plus" :size="24" />
                        </div>
                        <div class="text-center">
                            <div class="text-base font-bold text-slate-800">新建 GEO 项目</div>
                            <div class="text-xs text-slate-400 mt-1">填写品牌信息，开始监测与内容生产</div>
                        </div>
                    </button>

                    <article
                        v-for="p in projects"
                        :key="p.id"
                        class="geo-project-card"
                        @click="goCard(p)">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-xl bg-[#F5F7FF] text-primary font-bold text-lg grid place-items-center shrink-0 overflow-hidden">
                                    <img v-if="p.logo" :src="p.logo" :alt="p.brand_name" class="w-full h-full object-cover" />
                                    <span v-else>{{ brandInitial(p.brand_name) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-800 truncate">{{ p.brand_name }}</div>
                                    <div class="text-xs text-slate-400 truncate mt-0.5">{{ p.industry || '未填行业' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0" @click.stop>
                                <ElTag v-if="p.initialized != null" :type="statusOf(p).tag" size="small" effect="light">
                                    {{ statusOf(p).label }}
                                </ElTag>
                                <ElButton link type="danger" size="small" @click="onDelete(p)">删除</ElButton>
                            </div>
                        </div>

                        <div v-if="p.initialized === false" class="mt-3 flex items-center justify-between gap-3">
                            <div class="text-xs text-slate-500 truncate">{{ statusOf(p).hint || '未完成初始化，继续后回到中断步骤' }}</div>
                            <ElButton type="primary" size="small" class="!rounded-lg" @click.stop="goResume(p)">继续配置</ElButton>
                        </div>

                        <div v-else class="mt-4">
                            <div class="flex items-end justify-between gap-3">
                                <ElTooltip content="平均可见度：品牌在 AI 回答里的综合得分，越高越容易被提到" placement="top">
                                    <div class="min-w-0">
                                        <div class="text-xs text-slate-500">平均可见度</div>
                                        <div class="mt-0.5 flex items-baseline gap-1">
                                            <span class="text-2xl font-semibold tabular-nums text-slate-900">{{ visOf(p) }}</span>
                                            <span v-if="p.avg_visibility != null" class="text-xs text-slate-400">/ 100</span>
                                        </div>
                                    </div>
                                </ElTooltip>
                                <ElTooltip content="在线率：监测记录里品牌被提到的比例" placement="top">
                                    <div class="text-right shrink-0">
                                        <div class="text-xs text-slate-500">在线率</div>
                                        <div class="mt-0.5 text-sm font-medium tabular-nums text-slate-700">{{ rateOf(p) }}</div>
                                    </div>
                                </ElTooltip>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mt-2">
                                <div class="h-full rounded-full bg-primary" :style="{ width: visWidth(p) }"></div>
                            </div>
                            <div class="text-xs text-slate-400 mt-1.5">
                                {{ p.monitor_count ? `已采集 ${p.monitor_count} 次${lastMonitor(p) ? ' · ' + lastMonitor(p) : ''}` : '还没有监测，进工作台跑一轮诊断即可看到走势' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mt-4">
                            <ElTooltip v-for="s in statLinks" :key="s.key" :content="s.hint" placement="top">
                                <button type="button" class="stat-cell" @click.stop="goModule(p, s.tab)">
                                    <div class="text-base font-semibold tabular-nums text-slate-800">{{ p[s.countKey] || 0 }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ s.label }}</div>
                                </button>
                            </ElTooltip>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-3 pt-3 border-t border-br" @click.stop>
                            <button
                                v-for="l in extraLinks"
                                :key="l.tab"
                                type="button"
                                class="text-xs text-slate-500 hover:text-primary"
                                @click="goModule(p, l.tab)">
                                {{ l.label }}
                            </button>
                        </div>
                    </article>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoProjects, geoProjectDelete, geoInitState } from '@/api/geo'
import { DEFAULT_GEO_TAB, geoListStatus, type GeoTabKey } from './_enums/nav'
import InitWizard from './_components/init-wizard.vue'
import GeoConfirm from './_components/geo-confirm.vue'
import { geoConfirm } from './_composables/geo-confirm'
import { toGeoTs } from './_composables/geo-time'

definePageMeta({ key: 'geo' })

const router = useRouter()
const projects = ref<any[]>([])
const showCreate = ref(false)
const loading = ref(false)
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')
const brandInitial = (name: string) => (name || '?').trim().slice(0, 1)
const statusOf = (p: any) => geoListStatus(p)

const statLinks: Array<{ key: string; label: string; countKey: string; tab: GeoTabKey; hint: string }> = [
    { key: 'keyword', label: '关键词', countKey: 'keyword_count', tab: 'set_topic', hint: '已配置的场景问题数，用来向各 AI 引擎提问' },
    { key: 'content', label: '内容', countKey: 'content_count', tab: 'manage', hint: '已生成的文章数，可去发布或继续改稿' },
    { key: 'monitor', label: '监测', countKey: 'monitor_count', tab: 'visibility', hint: '已采集的问答次数，一次 = 一个问题 × 一个引擎' }
]
const visOf = (p: any) => (p.avg_visibility == null ? '—' : p.avg_visibility)
const rateOf = (p: any) => (p.online_rate == null ? '—' : p.online_rate + '%')
const visWidth = (p: any) => (p.avg_visibility == null ? '0%' : Math.min(100, Number(p.avg_visibility) || 0) + '%')
const lastMonitor = (p: any) => {
    const ts = toGeoTs(p.last_monitor_at)
    if (!ts) return ''
    const d = new Date(ts * 1000)
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${pad(d.getMonth() + 1)}-${pad(d.getDate())} 更新`
}
const extraLinks: Array<{ label: string; tab: GeoTabKey }> = [
    { label: '生成文章', tab: 'generate' },
    { label: '发布', tab: 'publish' },
    { label: '助手', tab: 'agents' },
    { label: '品牌画像', tab: 'set_brand' }
]

const attachInitState = async (list: any[]) => {
    const states = await Promise.all(
        list.map(async (p) => {
            try {
                return await geoInitState(p.id)
            } catch {
                return null
            }
        })
    )
    return list.map((p, i) => ({ ...p, ...(states[i] || {}) }))
}

const load = async () => {
    loading.value = true
    try {
        const list = (await geoProjects()) || []
        projects.value = await attachInitState(list)
    } catch (e) {
        ElMessage.error(errText(e))
    } finally {
        loading.value = false
    }
}

const goResume = (p: any) => router.push({ path: `/geo/${p.id}`, query: { resume: '1' } })

const goWorkspace = (p: any, tab: GeoTabKey) => router.push({ path: `/geo/${p.id}`, query: { tab } })

const goCard = (p: any) => {
    if (p.initialized === false) goResume(p)
    else goWorkspace(p, 'visibility')
}

const goModule = (p: any, tab: GeoTabKey) => {
    if (p.initialized === false) goResume(p)
    else goWorkspace(p, tab)
}

const onWizardDone = (id: number) => {
    showCreate.value = false
    if (id) router.push({ path: `/geo/${id}`, query: { tab: DEFAULT_GEO_TAB } })
    else load()
}

const onWizardBack = () => {
    showCreate.value = false
    load()
}

const onDelete = async (p: any) => {
    try {
        await geoConfirm({
            title: '删除项目',
            message: `确定删除「${p.brand_name}」？监测数据与已生成内容将一并清除。`,
            confirmText: '删除',
            tone: 'danger'
        })
        await geoProjectDelete(p.id)
        ElMessage.success('已删除')
        load()
    } catch (e) {
        if (e !== 'cancel') ElMessage.error(errText(e))
    }
}

onMounted(() => {
    load()
})
</script>

<style scoped lang="scss">
.geo-create-card {
    @apply min-h-[220px] rounded-[20px] border-2 border-dashed border-slate-300 bg-white cursor-pointer flex flex-col items-center justify-center gap-4 transition-all duration-200;
    &:hover {
        @apply border-primary bg-[#F5F7FF];
    }
}

.geo-project-card {
    @apply min-h-[220px] rounded-[20px] bg-white border border-br p-5 cursor-pointer flex flex-col transition-all duration-200;
    &:hover {
        @apply border-primary;
        box-shadow: 0 12px 24px -8px rgba(0, 101, 251, 0.08);
        transform: translateY(-2px);
    }
}

.stat-cell {
    @apply rounded-lg bg-slate-50 px-2 py-2 text-center cursor-pointer transition-colors duration-200;
    &:hover {
        @apply bg-[#F5F7FF] text-primary;
    }
}
</style>
