<template>
    <div class="space-y-4">
        <!-- tab -->
        <div class="bg-white rounded-xl border border-br px-5">
            <GeoSubTabs :model-value="tab" :tabs="topicTabs" class="!border-0" @update:model-value="tab = $event" />
        </div>

        <!-- ===== 场景问题 ===== -->
        <template v-if="tab === 'question'">
            <div class="bg-white rounded-xl border border-br overflow-hidden min-h-[280px]" v-spin="{ show: qLoading, text: '加载中...' }">
                <div class="px-5 py-3.5 border-b border-[#F1F5F9] flex items-center gap-2 flex-wrap">
                    <ElButton type="primary" class="!h-9 !rounded-lg" @click="openAdd()">添加场景问题</ElButton>
                    <ElButton class="!h-9 !rounded-lg" :disabled="!selected.length" @click="batch('disable')">批量停用</ElButton>
                    <ElButton class="!h-9 !rounded-lg" :disabled="!selected.length" @click="batch('enable')">批量启用</ElButton>
                    <ElButton type="danger" class="!h-9 !rounded-lg" :disabled="!selected.length" @click="batch('delete')">批量删除</ElButton>
                    <div class="flex items-center gap-2 ml-auto">
                        <ElSelect v-model="qFilter.topic_id" placeholder="全部话题" clearable style="width:160px" @change="reloadQuestions">
                            <ElOption v-for="t in topicList" :key="t.id" :label="t.name" :value="t.id" />
                        </ElSelect>
                        <ElSelect v-model="qFilter.status" placeholder="全部状态" clearable style="width:120px" @change="reloadQuestions">
                            <ElOption label="启用中" :value="1" /><ElOption label="已停用" :value="0" />
                        </ElSelect>
                    </div>
                </div>
                <div v-if="realTerms.length" class="px-5 py-2.5 border-b border-[#F1F5F9] text-xs text-slate-600">
                    <div class="flex items-center justify-between gap-3">
                        <span>已参考真实搜索需求</span>
                        <button type="button" class="text-slate-400 hover:text-slate-600" @click="realTerms = []">关闭</button>
                    </div>
                    <div class="flex flex-wrap gap-1 mt-1.5">
                        <span v-for="(t, i) in realTerms" :key="i" class="topic-chip">{{ t.term }}<span class="text-slate-400 ml-1">{{ t.platform }}</span></span>
                    </div>
                </div>
                <ElTable ref="qTableRef" :data="questions" class="geo-plain-table topic-table" @selection-change="(v: any[]) => selected = v">
                    <ElTableColumn type="selection" width="46" />
                    <ElTableColumn label="场景问题" min-width="320">
                        <template #default="{ row }">
                            <button type="button" class="text-left text-sm font-medium text-slate-800 hover:text-primary" @click="openAdd(row)">{{ row.value }}</button>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="话题" width="160">
                        <template #default="{ row }">
                            <span class="text-sm text-slate-600">{{ row.topic_name || '—' }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="创建时间" width="168">
                        <template #default="{ row }">
                            <span class="text-sm text-slate-500 tabular-nums">{{ fmtTime(row.create_time) }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="状态" width="148">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2">
                                <span class="topic-chip" :class="row.status ? 'topic-chip--ok' : 'topic-chip--mute'">{{ row.status ? '启用中' : '已停用' }}</span>
                                <ElSwitch :model-value="!!row.status" @change="toggleQuestion(row)" />
                            </div>
                        </template>
                    </ElTableColumn>
                    <template #empty><GeoEmpty description="暂无场景问题" /></template>
                </ElTable>
                <div v-if="qPage.total > qPage.limit" class="flex justify-end px-5 py-3 border-t border-[#F1F5F9]">
                    <ElPagination
                        v-model:current-page="qPage.page"
                        v-model:page-size="qPage.limit"
                        :total="qPage.total"
                        :page-sizes="[20, 50, 100]"
                        layout="total, sizes, prev, pager, next"
                        background
                        @current-change="loadQuestions"
                        @size-change="() => { qPage.page = 1; loadQuestions() }" />
                </div>
            </div>
        </template>

        <!-- ===== 话题 ===== -->
        <template v-if="tab === 'topic'">
            <div class="bg-white rounded-xl border border-br overflow-hidden min-h-[280px]" v-spin="{ show: tLoading, text: '加载中...' }">
                <div class="px-5 py-3.5 border-b border-[#F1F5F9] flex items-center gap-3">
                    <ElButton type="primary" class="!h-9 !rounded-lg" :disabled="!remaining" @click="openTopic()">添加话题</ElButton>
                    <span class="text-sm text-slate-500">
                        还可添加
                        <b :class="remaining ? 'text-primary' : 'text-rose-600'">{{ remaining }}</b>
                        / {{ maxTopics }}
                    </span>
                </div>
                <ElTable :data="topicList" class="geo-plain-table topic-table">
                    <ElTableColumn label="话题" min-width="200">
                        <template #default="{ row }">
                            <button type="button" class="text-left text-sm font-semibold text-slate-800 hover:text-primary" @click="openTopic(row)">{{ row.name }}</button>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="场景问题" width="100">
                        <template #default="{ row }">
                            <span class="text-sm text-slate-600 tabular-nums">{{ row.question_count ?? 0 }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="创建时间" width="168">
                        <template #default="{ row }">
                            <span class="text-sm text-slate-500 tabular-nums">{{ fmtTime(row.create_time) }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="状态" width="148">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2">
                                <span class="topic-chip" :class="row.status ? 'topic-chip--ok' : 'topic-chip--mute'">{{ row.status ? '启用中' : '已停用' }}</span>
                                <ElSwitch :model-value="!!row.status" @change="toggleTopic(row)" />
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="操作" width="228" fixed="right" align="right">
                        <template #default="{ row }">
                            <div class="topic-btns">
                                <button type="button" class="topic-btn" @click="openTopic(row)">编辑</button>
                                <button type="button" class="topic-btn" :disabled="genning === row.id" @click="genQuestions(row)">
                                    {{ genning === row.id ? '生成中' : 'AI补问题' }}
                                </button>
                                <button type="button" class="topic-btn topic-btn--danger" @click="delTopic(row)">删除</button>
                            </div>
                        </template>
                    </ElTableColumn>
                    <template #empty><GeoEmpty description="暂无话题" /></template>
                </ElTable>
            </div>
        </template>

        <GeoDialog
            v-model="showAdd"
            :title="qForm.id ? '编辑场景问题' : '添加场景问题'"
            desc="这些是向 AI 平台提问的原话，可改得更接近真实用户问法"
            width="520px"
            confirm-text="保存"
            @confirm="saveQuestion">
            <ElForm label-position="top">
                <ElFormItem label="所属话题" required>
                    <ElSelect v-model="qForm.topic_id" class="w-full" placeholder="选择话题">
                        <ElOption v-for="t in topicList" :key="t.id" :label="t.name" :value="t.id" />
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="场景问题" required><ElInput v-model="qForm.value" type="textarea" :rows="3" placeholder="如:预算有限,适合中小商家的全流程私域运营方案有啥?" /></ElFormItem>
            </ElForm>
        </GeoDialog>

        <GeoDialog
            v-model="showTopic"
            :title="tForm.id ? '编辑话题' : '添加话题'"
            desc="话题决定监测覆盖的场景"
            width="480px"
            confirm-text="保存"
            @confirm="saveTopic">
            <ElForm label-position="top">
                <ElFormItem label="话题名称" required><ElInput v-model="tForm.name" placeholder="如 全流程私域运营" /></ElFormItem>
                <ElFormItem label="目标场景问题数"><ElInputNumber v-model="tForm.question_target" :min="3" :max="20" /></ElFormItem>
            </ElForm>
        </GeoDialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoTopics, geoTopicSave, geoTopicToggle, geoTopicDelete, geoQuestions, geoQuestionSave, geoQuestionBatch, geoAiQuestions, geoChargeConfig } from '@/api/geo'
import { useGeoLoading } from '../_composables/use-geo-loading'
import GeoSubTabs from './geo-sub-tabs.vue'
import GeoEmpty from './geo-empty.vue'
import GeoDialog from './geo-dialog.vue'
import { geoConfirm } from '../_composables/geo-confirm'
import { fmtGeoTime as fmtTime } from '../_composables/geo-time'

const props = defineProps<{ pid: number; info: any }>()
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

const tab = ref('question')
const topicTabs = [
    { key: 'question', label: '场景问题' },
    { key: 'topic', label: '话题' }
]
const topicList = ref<any[]>([])
const maxTopics = ref(3)
const remaining = ref(0)
const questions = ref<any[]>([])
const selected = ref<any[]>([])
const qTableRef = ref()
// AI 补问题时参考到的真实搜索联想词;未接 TikHub 时后端返回空数组,该提示块不出现
const realTerms = ref<Array<{ term: string; platform: string }>>([])
const qFilter = reactive<any>({ topic_id: '', status: '' })
const qPage = reactive({ page: 1, limit: 20, total: 0 })
// 筛选条件变化时回到第一页再加载
const reloadQuestions = () => { qPage.page = 1; loadQuestions() }
const { contentLoading: qLoading, beginLoad: beginQ, isLatest: latestQ, endLoad: endQ } = useGeoLoading()
const { contentLoading: tLoading, beginLoad: beginT, isLatest: latestT, endLoad: endT } = useGeoLoading()

const loadTopics = async () => {
    const seq = beginT()
    try {
        const res: any = await geoTopics(props.pid)
        if (!latestT(seq)) return
        topicList.value = res?.list || []
        maxTopics.value = res?.max || 3
        remaining.value = res?.remaining || 0
    } catch (e) {
        if (latestT(seq)) ElMessage.error(errText(e))
    } finally {
        endT(seq)
    }
}
const loadQuestions = async () => {
    const seq = beginQ()
    try {
        const res: any = (await geoQuestions({ project_id: props.pid, ...qFilter, page: qPage.page, limit: qPage.limit })) || {}
        if (latestQ(seq)) {
            questions.value = Array.isArray(res) ? res : res.list || []
            qPage.total = Array.isArray(res) ? res.length : Number(res.total || 0)
        }
    } catch (e) {
        if (latestQ(seq)) ElMessage.error(errText(e))
    } finally {
        endQ(seq)
    }
}

// 场景问题
const showAdd = ref(false)
const qForm = reactive<any>({ id: 0, topic_id: null, value: '' })
const openAdd = (row?: any) => {
    Object.assign(qForm, row ? { id: row.id, topic_id: row.topic_id, value: row.value } : { id: 0, topic_id: topicList.value[0]?.id ?? null, value: '' })
    showAdd.value = true
}
const saveQuestion = async () => {
    if (!qForm.topic_id) return ElMessage.warning('请选择话题')
    if (!qForm.value.trim()) return ElMessage.warning('请填写场景问题')
    try {
        await geoQuestionSave({ project_id: props.pid, ...qForm })
        ElMessage.success('已保存'); showAdd.value = false
        loadQuestions(); loadTopics()
    } catch (e) { ElMessage.error(errText(e)) }
}
const toggleQuestion = async (row: any) => {
    try {
        await geoQuestionBatch(props.pid, [row.id], row.status ? 'disable' : 'enable')
        row.status = row.status ? 0 : 1
    } catch (e) { ElMessage.error(errText(e)) }
}
const batch = async (action: string) => {
    if (action === 'delete') {
        try {
            await geoConfirm({
                title: '删除场景问题',
                message: `确定删除选中的 ${selected.value.length} 条场景问题？`,
                confirmText: '删除',
                tone: 'danger'
            })
        } catch { return }
    }
    try {
        const ids = selected.value.map((s) => s.id)
        await geoQuestionBatch(props.pid, ids, action)
        if (action === 'delete') {
            const drop = new Set(ids)
            questions.value = questions.value.filter((q) => !drop.has(q.id))
        } else {
            const next = action === 'enable' ? 1 : 0
            for (const q of questions.value) {
                if (ids.includes(q.id)) q.status = next
            }
        }
        qTableRef.value?.clearSelection()
        selected.value = []
        ElMessage.success(action === 'delete' ? '已删除' : action === 'enable' ? '已启用' : '已停用')
        await Promise.all([loadQuestions(), loadTopics()])
    } catch (e) { ElMessage.error(errText(e)) }
}

// 话题
const showTopic = ref(false)
const tForm = reactive<any>({ id: 0, name: '', question_target: 10 })
const openTopic = (row?: any) => {
    Object.assign(tForm, row ? { id: row.id, name: row.name, question_target: row.question_target } : { id: 0, name: '', question_target: 10 })
    showTopic.value = true
}
const saveTopic = async () => {
    if (!tForm.name.trim()) return ElMessage.warning('请填写话题名称')
    try {
        await geoTopicSave({ project_id: props.pid, ...tForm })
        ElMessage.success('已保存'); showTopic.value = false; loadTopics()
    } catch (e) { ElMessage.error(errText(e)) }
}
const toggleTopic = async (row: any) => {
    try {
        await geoTopicToggle(props.pid, row.id)
        row.status = row.status ? 0 : 1
        // 后端会级联启停该话题下的场景问题,同步刷新问题列表避免切回去看到旧状态
        loadQuestions()
    } catch (e) { ElMessage.error(errText(e)) }
}
const delTopic = async (row: any) => {
    try {
        await geoConfirm({
            title: '删除话题',
            message: `确定删除「${row.name}」？该话题下的场景问题将一并删除。`,
            confirmText: '删除',
            tone: 'danger'
        })
    } catch { return }
    try {
        await geoTopicDelete(props.pid, row.id)
        ElMessage.success('已删除'); loadTopics(); loadQuestions()
    } catch (e) { ElMessage.error(errText(e)) }
}
const genning = ref(0)
const genQuestions = async (row: any) => {
    // 询价失败与用户取消分开处理:混在一个 try 里网络抖动会让按钮"点了没反应"
    let chargeOn = false
    try {
        const cfg: any = (await geoChargeConfig()) || {}
        // 模型计费口径:是否弹确认看 enabled,score 已恒为 0
        chargeOn = !!cfg.enabled
    } catch (e) {
        ElMessage.error('获取计费配置失败,请稍后重试')
        return
    }
    if (chargeOn) {
        try {
            await geoConfirm({
                title: 'AI 补问题',
                message: `将为「${row.name}」补足场景问题，与已有问题重复的自动跳过。`,
                confirmText: '开始生成',
                tone: 'info',
                facts: [
                    { label: '目标条数', value: `${row.question_target || 10} 个` },
                    { label: '计费方式', value: '按模型用量计费', emphasize: true }
                ],
                note: '生成失败不扣费'
            })
        } catch { return } // 用户取消
    }
    genning.value = row.id
    try {
        const res: any = await geoAiQuestions(props.pid, row.id, row.question_target || 10)
        realTerms.value = res?.real_terms || []
        ElMessage.success(
            `已生成 ${res?.created ?? 0} 个场景问题` + (realTerms.value.length ? '(已参考真实搜索需求)' : '')
        )
        loadQuestions(); loadTopics()
    } catch (e) { ElMessage.error(errText(e)) } finally { genning.value = 0 }
}

onMounted(() => { loadTopics(); loadQuestions() })
</script>

<style lang="scss" scoped>
.topic-table {
    :deep(.el-table__cell) {
        padding-top: 14px;
        padding-bottom: 14px;
        vertical-align: middle;
    }
}

.topic-chip {
    @apply inline-flex items-center h-6 px-2 rounded-md text-xs text-slate-600 bg-[#F1F5F9];
}
.topic-chip--ok { @apply text-emerald-700 bg-emerald-50; }
.topic-chip--mute { @apply text-slate-500 bg-[#F1F5F9]; }

.topic-btns {
    @apply inline-flex items-center overflow-hidden;
    height: 32px;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
}

.topic-btn {
    @apply h-8 px-3 text-xs text-slate-600 bg-white;
    border-right: 1px solid #E2E8F0;
    transition: background-color 150ms ease, color 150ms ease;
    &:last-child { border-right: none; }
    &:hover:not(:disabled) { background: #F8FAFC; color: var(--el-color-primary); }
    &:disabled { color: #94a3b8; cursor: not-allowed; }
    &--danger:hover:not(:disabled) { color: var(--el-color-danger); background: #FEF2F2; }
}

@media (prefers-reduced-motion: reduce) {
    .topic-btn { transition: none; }
}
</style>

