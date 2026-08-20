<template>
    <div class="geo-ws h-full bg-page">
        <GeoConfirm />
        <GeoPageSkeleton v-if="booting" />

        <InitWizard
            v-else-if="showInit"
            :pid="pid"
            :info="info"
            :auto-resume="autoResume"
            :resume-step="resumeStep"
            @done="onInitDone"
            @back="$router.push('/geo')" />

        <div v-else-if="loadError" class="h-full grid place-items-center">
            <div class="text-center">
                <div class="text-slate-500 mb-4">品牌信息加载失败：{{ loadError }}</div>
                <ElButton type="primary" @click="retryLoad">重新加载</ElButton>
                <ElButton @click="$router.push('/geo')">返回品牌列表</ElButton>
            </div>
        </div>

        <div v-else-if="info" class="h-full flex flex-col min-w-[1000px] px-4 pb-4 overflow-hidden">
            <header class="h-[88px] shrink-0 rounded-[20px] bg-white border border-br px-6 flex items-center gap-5">
                <ElButton link class="!px-1 !text-slate-500" @click="$router.push('/geo')">返回</ElButton>
                <ElDropdown trigger="click" @command="switchBrand">
                    <button type="button" class="flex items-center gap-3 min-w-0 max-w-[240px] text-left cursor-pointer">
                        <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary font-bold text-lg grid place-items-center shrink-0">
                            {{ brandInitial }}
                        </div>
                        <span class="min-w-0">
                            <span class="block font-semibold text-slate-800 truncate">{{ info.brand_name }}</span>
                            <span class="block text-xs text-slate-400 truncate mt-0.5">{{ info.industry || '未填行业' }}</span>
                        </span>
                        <Icon name="el-icon-ArrowDown" :size="12" class="text-slate-400 shrink-0" />
                    </button>
                    <template #dropdown>
                        <ElDropdownMenu>
                            <ElDropdownItem v-for="b in brands" :key="b.id" :command="b.id" :disabled="b.id === pid">
                                {{ b.brand_name }}
                            </ElDropdownItem>
                            <ElDropdownItem divided command="__list">全部项目</ElDropdownItem>
                        </ElDropdownMenu>
                    </template>
                </ElDropdown>

                <nav class="flex items-center gap-1 p-1 rounded-xl bg-slate-50" aria-label="GEO 分组">
                    <button
                        v-for="g in GEO_GROUPS"
                        :key="g.key"
                        type="button"
                        class="h-10 px-4 rounded-lg text-sm flex items-center gap-1.5 text-slate-500 hover:text-slate-800 transition-colors duration-200"
                        :class="{ 'bg-white text-primary font-semibold shadow-sm': groupKey === g.key }"
                        @click="setGroup(g.key)">
                        <Icon :name="g.icon" :size="16" />
                        {{ g.label }}
                    </button>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <ElSelect
                        v-model="genModel"
                        class="geo-hdr-select"
                        placeholder="系统默认"
                        filterable
                        clearable
                        :loading="savingModel"
                        @change="onModelChange">
                        <template #prefix>
                            <span class="text-slate-400 text-xs">模型</span>
                        </template>
                        <ElOption v-for="m in models" :key="m.value" :label="m.label" :value="m.value" />
                    </ElSelect>
                    <button
                        type="button"
                        class="h-11 px-3.5 rounded-xl text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 inline-flex items-center gap-2"
                        @click="taskDrawer = true">
                        任务日志
                        <span
                            v-if="runningCount"
                            class="min-w-5 h-5 px-1.5 rounded-full bg-primary text-white text-[11px] font-semibold tabular-nums grid place-items-center">
                            {{ runningCount }}
                        </span>
                    </button>
                </div>
            </header>

            <div class="grow min-h-0 flex flex-col bg-white rounded-[20px] border border-br overflow-hidden mt-4">
                <div class="px-6 bg-[#f8fafc]/50 border-b border-[#F1F5F9] shrink-0">
                    <ElTabs :model-value="page" class="custom-tabs" @tab-change="setPage">
                        <ElTabPane v-for="t in subTabs" :key="t.key" :label="t.label" :name="t.key" />
                    </ElTabs>
                </div>
                <main
                    class="flex-1 min-h-0 p-6 bg-[#f8fafc]"
                    :class="page === 'generate' ? 'overflow-hidden flex flex-col' : 'overflow-y-auto'">
                    <ElAlert
                        v-if="demoMode"
                        type="warning"
                        :closable="false"
                        class="!rounded-xl !mb-4"
                        title="【模拟数据】演示模式：尚未配置 AI 服务，所有 AI 功能返回模拟数据且全部免费，监测数据不可作为真实结果交付。" />
                    <MonitorVisibility v-if="page === 'visibility'" :pid="pid" :info="info" @go="setPage" />
                    <MonitorSentiment v-else-if="page === 'sentiment'" :pid="pid" :info="info" />
                    <MonitorQuotes v-else-if="page === 'quotes'" :pid="pid" :info="info" />
                    <MonitorScene v-else-if="page === 'scene'" :pid="pid" :info="info" @view-snapshot="viewSnapshot" />
                    <MonitorSnapshots v-else-if="page === 'snapshots'" ref="snapRef" :pid="pid" :info="info" />
                    <ContentGenerate v-else-if="page === 'generate'" :pid="pid" :info="info" @done="setPage('manage')" />
                    <ContentManage
                        v-else-if="page === 'manage' || page === 'publish'"
                        :pid="pid"
                        :info="info"
                        :section="page === 'publish' ? 'publish' : 'list'"
                        @go="setPage" />
                    <KbPanel v-else-if="page === 'kb'" :pid="pid" :info="info" />
                    <AgentsPanel v-else-if="page === 'agents'" :pid="pid" :info="info" @go="setPage" />
                    <SettingsTopics v-else-if="page === 'set_topic'" :pid="pid" :info="info" />
                    <SettingsBrand v-else-if="page === 'set_brand'" :pid="pid" :info="info" @saved="loadInfo" />
                    <SettingsAccounts v-else-if="page === 'set_account'" :pid="pid" :info="info" />
                </main>
            </div>
        </div>

        <TaskLogDrawer v-model="taskDrawer" :tasks="tasks" :loading="taskLoading" @refresh="loadTasks" />
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoProjectDetail, geoProjectUpdate, geoModels, geoTasks, geoInitState, geoChargeConfig, geoProjects } from '@/api/geo'
import { DEFAULT_GEO_TAB, GEO_GROUPS, groupOfTab, resolveGeoTab, tabsOfGroup, type GeoGroupKey, type GeoTabKey } from './_enums/nav'
import { geoConfirm } from './_composables/geo-confirm'
import InitWizard from './_components/init-wizard.vue'
import GeoPageSkeleton from './_components/geo-page-skeleton.vue'
import GeoConfirm from './_components/geo-confirm.vue'
import MonitorVisibility from './_components/monitor-visibility.vue'
import MonitorSentiment from './_components/monitor-sentiment.vue'
import MonitorQuotes from './_components/monitor-quotes.vue'
import MonitorScene from './_components/monitor-scene.vue'
import MonitorSnapshots from './_components/monitor-snapshots.vue'
import ContentGenerate from './_components/content-generate.vue'
import ContentManage from './_components/content-manage.vue'
import KbPanel from './_components/kb-panel.vue'
import AgentsPanel from './_components/agents-panel.vue'
import SettingsTopics from './_components/settings-topics.vue'
import SettingsBrand from './_components/settings-brand.vue'
import SettingsAccounts from './_components/settings-accounts.vue'
import TaskLogDrawer from './_components/task-log-drawer.vue'

definePageMeta({ key: (route) => String(route.params.id) })

const route = useRoute()
const router = useRouter()
const pid = computed(() => {
    const raw = route.params.id
    const n = Number(Array.isArray(raw) ? raw[0] : raw)
    return Number.isFinite(n) && n > 0 ? n : 0
})
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

const page = ref<GeoTabKey>(resolveGeoTab(String(route.query.tab || '')))
const groupKey = computed(() => groupOfTab(page.value))
const subTabs = computed(() => tabsOfGroup(groupKey.value))

const syncTabQuery = (tab: GeoTabKey) => {
    if (String(route.query.tab || '') === tab) return
    router.replace({ path: route.path, query: { ...route.query, tab } })
}

const setPage = (tab: string) => {
    const next = resolveGeoTab(tab)
    page.value = next
    syncTabQuery(next)
}

const setGroup = (key: GeoGroupKey) => {
    if (groupKey.value === key) return
    setPage(tabsOfGroup(key)[0].key)
}

watch(
    () => route.query.tab,
    (t) => {
        const next = resolveGeoTab(String(t || ''))
        if (next !== page.value) page.value = next
    }
)

const autoResume = computed(() => String(route.query.resume || '') === '1')
const resumeStep = ref(0)
const booting = ref(true)
const info = ref<any>(null)
const showInit = ref(false)
const loadError = ref('')
const demoMode = ref(false)
const models = ref<any[]>([])
const genModel = ref('')
const savingModel = ref(false)
const tasks = ref<any[]>([])
const taskLoading = ref(false)
const snapRef = ref()
const brands = ref<any[]>([])

const brandInitial = computed(() => (info.value?.brand_name || '?').trim().slice(0, 1))
const runningCount = computed(() => tasks.value.filter((t) => t.status === 'running' || t.status === 'pending').length)

const applyInfo = (detail: any) => {
    info.value = detail
    genModel.value = detail?.gen_model || ''
}
const loadInfo = async () => {
    if (!pid.value) return
    applyInfo(await geoProjectDetail(pid.value))
}
const loadModels = async () => {
    try {
        models.value = (await geoModels()) || []
    } catch (e) {
        /* 留空用默认 */
    }
}
const loadTasks = async () => {
    taskLoading.value = true
    try {
        if (!pid.value) return
        tasks.value = (await geoTasks(pid.value)) || []
    } catch (e) {
        /* 忽略 */
    } finally {
        taskLoading.value = false
    }
}
const loadBrands = async () => {
    try {
        brands.value = (await geoProjects()) || []
    } catch (e) {
        /* 忽略 */
    }
}

const switchBrand = (cmd: string | number) => {
    if (cmd === '__list') {
        router.push('/geo')
        return
    }
    const id = Number(cmd)
    if (!id || id === pid.value) return
    router.push({ path: `/geo/${id}`, query: { tab: page.value } })
}

const onModelChange = async (val: string) => {
    const prev = info.value?.gen_model || ''
    if ((val || '') === prev) return
    // 模型决定生成侧计费单价,切换前必须让用户知情确认;取消则回退选择
    const label = models.value.find((m: any) => m.value === val)?.label || val
    try {
        await geoConfirm({
            title: val ? '切换生成模型' : '恢复系统默认模型',
            message: val
                ? `切换后,AI 生成(文章 / 话题 / 场景问题 / 报告等)将按「${label}」的模型单价计费,是否继续?`
                : '恢复后,AI 生成将按系统默认模型的单价计费,是否继续?',
            confirmText: '确认切换',
            tone: 'info',
            impacts: ['监测(一键诊断 / 每日自动监测)按各引擎实际模型计费,不受此选择影响'],
        })
    } catch {
        genModel.value = prev
        return
    }
    savingModel.value = true
    try {
        await geoProjectUpdate({ id: pid.value, gen_model: val || '' })
        if (info.value) info.value.gen_model = val || ''
        ElMessage.success(val ? '已切换生成模型' : '已恢复系统默认模型')
    } catch (e) {
        ElMessage.error(errText(e))
        genModel.value = prev
    } finally {
        savingModel.value = false
    }
}

const onInitDone = async () => {
    showInit.value = false
    const { resume: _resume, ...rest } = route.query
    await router.replace({ path: route.path, query: { ...rest, tab: DEFAULT_GEO_TAB } })
    page.value = DEFAULT_GEO_TAB
    await loadInfo()
}

const viewSnapshot = (row: any) => {
    setPage('snapshots')
    nextTick(() => snapRef.value?.focus?.(row))
}

const taskDrawer = ref(false)

let bootSeq = 0
const boot = async () => {
    const id = pid.value
    if (!id) {
        booting.value = true
        return
    }
    const seq = ++bootSeq
    booting.value = true
    loadError.value = ''
    try {
        const [detailRes, stateRes] = await Promise.allSettled([geoProjectDetail(id), geoInitState(id)])
        if (seq !== bootSeq) return
        const detail = detailRes.status === 'fulfilled' ? detailRes.value : null
        if (!detail || typeof detail !== 'object' || !Number(detail.id)) {
            loadError.value = errText(detailRes.status === 'rejected' ? detailRes.reason : '无权访问该项目')
            return
        }
        applyInfo(detail)
        const state: any = stateRes.status === 'fulfilled' ? stateRes.value : { initialized: true, resume_step: 0 }
        resumeStep.value = Number(state?.resume_step ?? 0)
        // 诊断报告点「进入工作台」会带 tab；此时不要再强制向导，否则会弹出「继续未完成的创建」
        const openWorkbench = Boolean(route.query.tab) && String(route.query.resume || '') !== '1'
        showInit.value = !state?.initialized && !openWorkbench
        if (state?.initialized || openWorkbench) syncTabQuery(page.value)
    } catch (e) {
        if (seq !== bootSeq) return
        loadError.value = errText(e)
    } finally {
        if (seq === bootSeq) booting.value = false
    }
    if (seq !== bootSeq) return
    loadModels()
    loadTasks()
    loadBrands()
    try {
        const cfg: any = await geoChargeConfig()
        demoMode.value = !Array.isArray(cfg) && cfg?.enabled === false
    } catch (e) {
        /* 拿不到就不显示演示横幅 */
    }
}
const retryLoad = () => boot()
watch(pid, (id, prev) => {
    if (id && id !== prev) boot()
})
onMounted(boot)
</script>

<style lang="scss">
@import './_styles/table.scss';

.geo-ws .geo-hdr-select {
    width: 220px;
    .el-select__wrapper {
        min-height: 44px;
        border-radius: 12px;
        box-shadow: none !important;
        background: #f8fafc;
    }
    .el-select__wrapper.is-focused,
    .el-select__wrapper.is-hovering {
        box-shadow: 0 0 0 1px var(--el-color-primary) inset !important;
        background: #fff;
    }
}
</style>
