<template>
    <div class="space-y-4">
        <section class="bg-white rounded-xl border border-br px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="flex p-1 rounded-lg bg-slate-50 shrink-0">
                    <button
                        v-for="t in importTabs"
                        :key="t.key"
                        type="button"
                        class="h-8 px-3 rounded-md text-sm cursor-pointer transition-colors duration-200"
                        :class="importTab === t.key ? 'bg-white text-primary font-semibold shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        @click="setImportTab(t.key)">
                        {{ t.label }}
                    </button>
                </div>
                <div v-if="importTab !== 'text'" class="flex-1 min-w-0">
                    <button
                        v-if="importTab === 'file'"
                        type="button"
                        class="w-full h-8 px-3 rounded-lg border border-dashed border-br text-left text-sm cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors duration-200"
                        :class="pickedFile ? 'text-slate-800' : 'text-slate-400'"
                        @click="pickFile">
                        {{ pickedFile ? pickedFile.name : '选择 PDF / Word 文档' }}
                    </button>
                    <ElInput
                        v-else
                        v-model="kbUrl"
                        placeholder="https://www.yoursite.com/about"
                        @keyup.enter="onImport" />
                </div>
                <span v-else class="flex-1"></span>
                <span class="text-xs text-slate-400 shrink-0">按模型用量计费，失败不扣</span>
                <ElButton type="primary" class="!h-11 !rounded-xl !px-5 shrink-0" :loading="importing" @click="onImport">解析并入库</ElButton>
            </div>
            <ElInput
                v-if="importTab === 'text'"
                v-model="kbText"
                type="textarea"
                :rows="2"
                class="mt-3"
                placeholder="粘贴手册、官网介绍或 FAQ" />
            <input ref="fileInput" type="file" accept=".pdf,.doc,.docx" class="hidden" @change="onNativeFile" />
        </section>

        <section class="bg-white rounded-xl border border-br overflow-hidden" v-spin="{ show: contentLoading, text: '加载中...' }">
            <div class="px-5 py-4 flex items-center justify-between gap-4 border-b border-[#F1F5F9]">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">已入库语料</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ knowledge.length }} 条 · {{ typeCount }} 个类型，内容创作会引用这些实体</p>
                </div>
                <div class="flex items-center gap-1 flex-wrap justify-end">
                    <button
                        v-for="t in typeTabs"
                        :key="t.key"
                        type="button"
                        class="h-8 px-3 rounded-lg text-sm cursor-pointer transition-colors duration-200"
                        :class="typeTab === t.key ? 'bg-primary/10 text-primary font-semibold' : 'text-slate-500 hover:bg-slate-50'"
                        @click="typeTab = t.key">
                        {{ t.label }}
                        <span class="tabular-nums ml-1" :class="typeTab === t.key ? 'text-primary' : 'text-slate-400'">{{ t.count }}</span>
                    </button>
                </div>
            </div>

            <template v-if="groups.length">
                <div v-for="g in groups" :key="g.type">
                    <div class="px-5 py-2.5 text-xs font-semibold tracking-wide text-slate-500 bg-[#F8FAFC] border-b border-[#F1F5F9]">
                        {{ g.type }}
                        <span class="tabular-nums text-slate-400 font-normal ml-1">{{ g.items.length }}</span>
                    </div>
                    <article
                        v-for="row in g.items"
                        :key="row.id"
                        class="px-5 py-4 flex items-start gap-4 border-b border-[#F1F5F9] last:border-b-0 hover:bg-[#FAFBFC] transition-colors duration-200">
                        <ElTag size="small" effect="plain" class="shrink-0 mt-0.5">{{ row.entity_type }}</ElTag>
                        <div class="flex-1 min-w-0">
                            <p
                                :ref="(el: any) => setContentEl(row.id, el)"
                                class="text-[15px] text-slate-900 leading-relaxed"
                                :class="opened.has(row.id) ? '' : 'line-clamp-3'">
                                {{ row.content }}
                            </p>
                            <button
                                v-if="overflowing.has(row.id) || opened.has(row.id)"
                                type="button"
                                class="mt-1.5 text-sm text-primary cursor-pointer"
                                @click="toggleOpen(row.id)">
                                {{ opened.has(row.id) ? '收起' : '展开全部' }}
                            </button>
                        </div>
                        <div class="shrink-0 w-36 flex flex-col items-end gap-2 pt-0.5">
                            <span class="w-full text-right text-xs text-slate-400 truncate" :title="row.source">{{ sourceLabel(row.source) }}</span>
                            <div class="flex items-center">
                                <ElButton link type="primary" @click="openEdit(row)">编辑</ElButton>
                                <ElButton link type="danger" @click="delRow(row)">删除</ElButton>
                            </div>
                        </div>
                    </article>
                </div>
            </template>
            <GeoEmpty v-else-if="!contentLoading" description="还没有语料。把品牌介绍导进来，创作时就能引用。" />
        </section>

        <GeoDialog
            v-model="showEdit"
            layout="panel"
            title="编辑知识"
            desc="改完立即用于内容创作引用，不重新计费"
            width="560px"
            confirm-text="保存"
            :confirm-loading="saving"
            @confirm="saveEdit">
            <ElForm label-position="top">
                <ElFormItem label="类型" required>
                    <ElSelect v-model="editForm.entity_type" class="w-full">
                        <ElOption v-for="t in ENTITY_TYPES" :key="t" :label="t" :value="t" />
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="内容" required>
                    <ElInput v-model="editForm.content" type="textarea" :autosize="{ minRows: 4, maxRows: 12 }" />
                </ElFormItem>
            </ElForm>
        </GeoDialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoKnowledge, geoKnowledgeImport, geoKnowledgeImportFile, geoKnowledgeImportUrl, geoKnowledgeSave, geoKnowledgeDelete, geoChargeConfig } from '@/api/geo'
import { uploadFile } from '@/api/app'
import { useGeoLoading } from '../_composables/use-geo-loading'
import { geoConfirm } from '../_composables/geo-confirm'
import GeoDialog from './geo-dialog.vue'
import GeoEmpty from './geo-empty.vue'

const props = defineProps<{ pid: number; info: any }>()
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

const knowledge = ref<any[]>([])
const kbText = ref('')
const importing = ref(false)
const { contentLoading, beginLoad, isLatest, endLoad } = useGeoLoading()
const typeTab = ref('')
const importPrice = ref(0)
const fileInput = ref<HTMLInputElement | null>(null)
const opened = ref<Set<number>>(new Set())
const loadPrice = async () => {
    try {
        const cfg: any = (await geoChargeConfig()) || {}
        const charges: any[] = Array.isArray(cfg) ? cfg : cfg.list || []
        importPrice.value = Number(charges.find((c: any) => c.scene === 'geo_knowledge')?.score || 0)
    } catch (e) { /* 取不到价则不展示 */ }
}

const types = computed(() => [...new Set(knowledge.value.map((k) => k.entity_type).filter(Boolean))] as string[])
const typeCount = computed(() => types.value.length)
const typeTabs = computed(() => [
    { key: '', label: '全部', count: knowledge.value.length },
    ...types.value.map((t) => ({
        key: t,
        label: t,
        count: knowledge.value.filter((k) => k.entity_type === t).length
    }))
])
const groups = computed(() => {
    const keys = typeTab.value ? [typeTab.value] : types.value
    return keys
        .map((type) => ({
            type,
            items: knowledge.value.filter((k) => k.entity_type === type)
        }))
        .filter((g) => g.items.length)
})
const importTabs = [
    { key: 'text', label: '粘贴文本' },
    { key: 'file', label: '上传文档' },
    { key: 'url', label: '抓取网址' }
]
const ENTITY_TYPES = ['品牌介绍', '产品介绍', '能力标签', '行业标签', '产品特点', '用户画像', '业务流程', '术语']
const showEdit = ref(false)
const saving = ref(false)
const editForm = reactive({ id: 0, entity_type: '术语', content: '' })
const openEdit = (row: any) => {
    Object.assign(editForm, { id: row.id, entity_type: row.entity_type || '术语', content: row.content || '' })
    showEdit.value = true
}
const saveEdit = async () => {
    if (!editForm.content.trim()) return ElMessage.warning('请填写知识内容')
    saving.value = true
    try {
        await geoKnowledgeSave({ id: editForm.id, entity_type: editForm.entity_type, content: editForm.content })
        ElMessage.success('已保存')
        showEdit.value = false
        load()
    } catch (e) { ElMessage.error(errText(e)) } finally { saving.value = false }
}
const delRow = async (row: any) => {
    try {
        await geoConfirm({
            title: '删除知识',
            message: '删除后内容创作不再引用这条。',
            confirmText: '删除',
            tone: 'danger',
            facts: [{ label: '类型', value: row.entity_type || '—' }]
        })
    } catch { return }
    try {
        await geoKnowledgeDelete(row.id)
        ElMessage.success('已删除')
        load()
    } catch (e) { ElMessage.error(errText(e)) }
}

const SOURCE_NAME: Record<string, string> = {
    手动导入: '粘贴文本',
    文档导入: '上传文档',
    项目信息: '项目信息',
    品牌分析: '品牌分析'
}
const sourceLabel = (raw: any) => {
    const s = String(raw || '').trim()
    if (!s) return '—'
    if (SOURCE_NAME[s]) return SOURCE_NAME[s]
    if (/^https?:\/\//i.test(s)) {
        try {
            return new URL(s).hostname.replace(/^www\./, '')
        } catch (e) { /* 用原文 */ }
    }
    const file = s.split(/[/\\]/).pop() || s
    const named = file.match(/[^/\\]+\.(docx?|pdf|txt|md)$/i)
    const label = named ? named[0] : file
    return label.length > 22 ? `${label.slice(0, 10)}…${label.slice(-8)}` : label
}
// 「展开全部」只在正文真被 line-clamp-3 截断时出现:字符数阈值在宽容器下会误判,
// 两行内容也弹按钮,点击后毫无变化。按钮显隐以实测溢出为准,窗口变化时重测
const contentEls = new Map<number, HTMLElement>()
const overflowing = ref(new Set<number>())
let measureTimer: any = null
const measureOverflow = () => {
    const next = new Set<number>()
    contentEls.forEach((el, id) => {
        // 展开中的行不量(clamp 已解除,量不到溢出),按钮由 opened 分支保住「收起」入口
        if (!opened.value.has(id) && el.scrollHeight > el.clientHeight + 1) next.add(id)
    })
    overflowing.value = next
}
const scheduleMeasure = () => {
    if (measureTimer) return
    measureTimer = setTimeout(() => { measureTimer = null; measureOverflow() }, 50)
}
const setContentEl = (id: number, el: any) => {
    if (el) contentEls.set(id, el)
    else contentEls.delete(id)
    scheduleMeasure()
}
const toggleOpen = (id: number) => {
    const next = new Set(opened.value)
    if (next.has(id)) next.delete(id)
    else next.add(id)
    opened.value = next
    nextTick(measureOverflow)
}
onMounted(() => window.addEventListener('resize', scheduleMeasure))
onUnmounted(() => {
    window.removeEventListener('resize', scheduleMeasure)
    if (measureTimer) clearTimeout(measureTimer)
})

const load = async () => {
    const seq = beginLoad()
    try {
        const res = (await geoKnowledge(props.pid)) || []
        if (isLatest(seq)) knowledge.value = res
    } catch (e) {
        if (isLatest(seq)) ElMessage.error(errText(e))
    } finally {
        endLoad(seq)
    }
}

const importTab = ref<'text' | 'file' | 'url'>('text')
const setImportTab = (key: string) => {
    if (key !== 'text' && key !== 'file' && key !== 'url') return
    if (importTab.value === 'url' && key !== 'url') kbUrl.value = ''
    importTab.value = key
}
const kbUrl = ref('')
const pickedFile = ref<any>(null)
const pickFile = () => fileInput.value?.click()
const onNativeFile = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (!['pdf', 'doc', 'docx'].includes(ext || '')) return ElMessage.warning('仅支持 PDF / Word 文档')
    pickedFile.value = file
    if (fileInput.value) fileInput.value.value = ''
}

const onImport = async () => {
    importing.value = true
    try {
        let res: any = null
        if (importTab.value === 'text') {
            if (!kbText.value.trim()) return ElMessage.warning('请粘贴内容')
            res = await geoKnowledgeImport({ project_id: props.pid, source: '手动导入', source_type: 'txt', text: kbText.value })
        } else if (importTab.value === 'file') {
            if (!pickedFile.value) return ElMessage.warning('请先选择文档')
            const { uri }: any = await uploadFile({ file: pickedFile.value })
            res = await geoKnowledgeImportFile({
                project_id: props.pid, file_url: uri,
                name: pickedFile.value.name, ext: pickedFile.value.name.split('.').pop()?.toLowerCase(),
            })
        } else {
            if (!kbUrl.value.trim()) return ElMessage.warning('请填写网址')
            res = await geoKnowledgeImportUrl({ project_id: props.pid, url: kbUrl.value.trim() })
        }
        if (res?.status === 'failed') {
            const logs: any[] = res?.logs || []
            ElMessage.error(String(logs[logs.length - 1]?.message || '解析失败,请稍后重试'))
        } else {
            if (importTab.value === 'text') kbText.value = ''
            else if (importTab.value === 'file') pickedFile.value = null
            else kbUrl.value = ''
            ElMessage.success('已解析入库')
        }
        load()
    } catch (e) { ElMessage.error(errText(e)) } finally { importing.value = false }
}

onMounted(() => { load(); loadPrice() })
</script>
