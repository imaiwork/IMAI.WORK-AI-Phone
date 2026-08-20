<template>
    <div class="flex-1 min-h-0 w-full flex flex-col gap-4" v-spin="{ show: contentLoading, text: '加载中...' }">
        <section class="shrink-0 bg-white rounded-xl border border-br px-5 py-4">
            <div class="flex items-center justify-between gap-4 mb-4">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">生成品牌内容</div>
                    <div class="text-xs text-slate-400 mt-0.5">{{ STEP_HINTS[step] }}</div>
                </div>
                <div class="text-xs text-slate-400 shrink-0">第 {{ Math.min(step, 3) + 1 }} / {{ STEPS.length }} 步</div>
            </div>
            <nav class="flex items-center" aria-label="生成进度">
                <template v-for="(s, i) in STEPS" :key="s">
                    <button
                        type="button"
                        class="flex items-center gap-2 min-w-0 text-left cursor-pointer"
                        :class="stepTone(i) === 'current' ? 'text-primary' : stepTone(i) === 'done' ? 'text-slate-700' : 'text-slate-400'"
                        :aria-current="i === step ? 'step' : undefined"
                        @click="goStep(i)">
                        <span
                            class="w-8 h-8 rounded-full grid place-items-center text-sm font-semibold shrink-0"
                            :class="stepTone(i) === 'current' ? 'bg-primary text-white' : stepTone(i) === 'done' ? 'bg-[#F5F7FF] text-primary' : 'bg-slate-100 text-slate-400'">
                            <Icon v-if="stepTone(i) === 'done'" name="el-icon-Check" :size="16" />
                            <span v-else>{{ i + 1 }}</span>
                        </span>
                        <span class="text-sm truncate" :class="i === step ? 'font-semibold' : ''">{{ s }}</span>
                    </button>
                    <div
                        v-if="i < STEPS.length - 1"
                        class="flex-1 h-px mx-3"
                        :class="i < farthestStep ? 'bg-primary' : 'bg-slate-200'"></div>
                </template>
            </nav>
        </section>

        <section v-if="step < 3" class="flex-1 min-h-0 bg-white rounded-xl border border-br flex flex-col overflow-hidden">
            <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain p-5 space-y-4">
                    <template v-if="step === 0">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">选择话题</div>
                            <div class="text-xs text-slate-400 mt-0.5">先定主题，再勾选要覆盖的场景问题</div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <button
                                v-for="t in topics"
                                :key="t.id"
                                type="button"
                                class="rounded-xl border border-br p-3 text-left cursor-pointer hover:border-primary transition-colors duration-200"
                                :class="{ '!border-primary bg-primary/5': form.topic_id === t.id }"
                                @click="pickTopic(t)">
                                <div class="font-semibold text-slate-800 text-sm truncate">{{ t.name }}</div>
                                <div class="text-xs mt-1 text-slate-400">
                                    可见度 <span class="tabular-nums text-slate-700">{{ t.visibility ?? '–' }}%</span>
                                </div>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-semibold text-slate-900">低在线率场景问题</div>
                            <span class="text-slate-400 text-xs">已选 {{ form.keyword_ids.length }}/10</span>
                        </div>
                        <div class="space-y-2">
                            <label
                                v-for="q in topicQuestions"
                                :key="q.id"
                                class="flex items-center gap-3 rounded-lg border border-br px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                                <ElCheckbox :model-value="form.keyword_ids.includes(q.id)" @change="toggleQuestion(q.id)" />
                                <span class="flex-1 text-sm text-slate-700">{{ q.value }}</span>
                                <span class="text-xs text-slate-400 shrink-0">
                                    在线率
                                    <b :class="q.online_rate == null ? 'text-slate-400' : q.online_rate <= 30 ? 'text-rose-500' : 'text-amber-500'">
                                        {{ q.online_rate == null ? '未监测' : q.online_rate + '%' }}
                                    </b>
                                </span>
                            </label>
                            <GeoEmpty v-if="!topicQuestions.length" description="该话题暂无场景问题，去「设置 - 话题」添加" />
                        </div>
                    </template>

                    <template v-if="step === 1">
                        <div class="text-xs text-slate-500">已选「{{ topicName }}」· {{ form.keyword_ids.length }} 个场景问题</div>
                        <div class="text-sm font-semibold text-slate-900">选择创作方式</div>
                        <div class="grid grid-cols-3 gap-3">
                            <button
                                v-for="t in templates"
                                :key="t.key"
                                type="button"
                                class="rounded-xl border border-br p-3 text-left cursor-pointer hover:border-primary transition-colors duration-200"
                                :class="{ '!border-primary bg-primary/5': form.template === t.key }"
                                @click="form.template = t.key; form.style = ''">
                                <div class="font-semibold text-slate-800 text-sm">{{ t.label }}</div>
                                <div class="text-xs text-slate-400 mt-1 line-clamp-2">{{ t.desc }}</div>
                            </button>
                        </div>
                        <ElCheckbox v-model="customStyle" @change="form.template = customStyle ? '' : form.template">直接输入想要的风格</ElCheckbox>
                        <ElInput v-if="customStyle" v-model="form.style" type="textarea" :rows="2" placeholder="如：轻松幽默的知乎风格，多用短句和真实案例" />
                    </template>

                    <template v-if="step === 2">
                        <div class="text-xs text-slate-500">创作方式：{{ templateLabel }}</div>
                        <div class="text-sm font-semibold text-slate-900">参考来源</div>
                        <div class="flex items-center justify-between rounded-xl border border-br px-4 py-3">
                            <span class="text-sm text-slate-700">品牌语料库 <span class="text-slate-400 text-xs">({{ kbCount }} 条)</span></span>
                            <ElSwitch v-model="useKb" />
                        </div>
                        <div class="text-sm font-semibold text-slate-900">补充诉求（可选）</div>
                        <ElInput v-model="form.extra" type="textarea" :rows="3" maxlength="500" show-word-limit placeholder="例如：希望重点强调产品在中小企业场景下的实用性" />
                    </template>

            </div>

            <div class="shrink-0 px-5 py-3 border-t border-[#F1F5F9] flex items-center justify-between">
                <ElButton v-if="step > 0" class="!h-11 !rounded-xl" @click="step -= 1">上一步</ElButton>
                <span v-else></span>
                <div class="flex items-center gap-3">
                    <span v-if="step === 2" class="text-slate-400 text-xs">按模型用量计费，失败不扣</span>
                    <ElButton
                        type="primary"
                        class="!h-11 !rounded-xl"
                        :disabled="step === 0 ? !form.topic_id : step === 1 && !form.template && !form.style.trim()"
                        @click="step === 2 ? startGenerate() : (step += 1)">
                        {{ step === 2 ? '生成内容' : '下一步' }}
                    </ElButton>
                </div>
            </div>
        </section>

        <section
            v-else
            class="bg-white flex flex-col overflow-hidden"
            :class="fullView ? 'fixed inset-0 z-[2000] rounded-none' : 'flex-1 min-h-0 rounded-xl border border-br'">
            <div class="shrink-0 flex items-center justify-between gap-4 px-5 py-3.5 border-b border-[#F1F5F9]">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">
                        {{ generating ? '正在生成' : content ? (fullView ? (editMode ? '全屏编辑' : '全屏预览') : '已生成') : '待生成' }}
                    </div>
                    <div class="flex items-center gap-2 mt-0.5 min-w-0 text-xs text-slate-500">
                        <span class="truncate">
                            {{ topicName || '未选话题' }} · {{ templateLabel }}
                            <span v-if="form.keyword_ids.length"> · {{ form.keyword_ids.length }} 个场景问题</span>
                        </span>
                        <button
                            v-if="content && !generating"
                            type="button"
                            class="shrink-0 text-slate-400 hover:text-slate-700"
                            @click="restart">
                            重新开始
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div
                        class="flex items-center p-1 rounded-xl bg-slate-50"
                        role="group"
                        aria-label="预览或编辑">
                        <button
                            type="button"
                            class="h-9 px-3 rounded-lg text-sm text-slate-500"
                            :class="{ 'bg-white text-primary font-semibold shadow-sm': !editMode }"
                            :disabled="!content || generating"
                            @click="editMode = false">
                            预览
                        </button>
                        <button
                            type="button"
                            class="h-9 px-3 rounded-lg text-sm text-slate-500"
                            :class="{ 'bg-white text-primary font-semibold shadow-sm': editMode }"
                            :disabled="!content || generating"
                            @click="editMode = true">
                            编辑
                        </button>
                    </div>
                    <ElButton
                        class="!h-11 !rounded-xl"
                        :disabled="!fullView && (!content || generating)"
                        @click="toggleFullView">
                        {{ fullView ? '退出全屏' : '全屏' }}
                    </ElButton>
                    <ElButton
                        type="primary"
                        class="!h-11 !rounded-xl"
                        :disabled="!content || generating"
                        :loading="adopting"
                        @click="adopt">
                        采纳文章
                    </ElButton>
                </div>
            </div>

            <div
                class="flex-1 min-h-0"
                :class="editMode && content && !generating ? 'overflow-hidden flex flex-col' : 'overflow-y-auto overscroll-contain'">
                <div v-if="generating" class="h-full min-h-[320px]" v-spin="{ show: true, text: 'AI 正在创作中…' }"></div>
                <template v-else-if="content">
                    <div
                        v-if="editMode"
                        class="flex-1 min-h-0 flex flex-col mx-auto w-full px-8 pt-8"
                        :class="fullView ? 'max-w-[76ch] pt-10' : 'max-w-[68ch]'">
                        <input
                            v-model="content.title"
                            type="text"
                            placeholder="文章标题"
                            class="w-full bg-transparent outline-none font-semibold text-slate-900 leading-snug placeholder:text-slate-300"
                            :class="fullView ? 'text-2xl' : 'text-xl'" />
                        <div class="mt-5 mb-3 h-px bg-[#F1F5F9]"></div>
                        <div class="geo-md flex-1 min-h-[280px]">
                            <ClientOnly>
                                <Editor v-model="content.body" :is-preview="false" :toolbars="[]" />
                            </ClientOnly>
                        </div>
                        <div class="shrink-0 py-2.5 text-xs text-slate-400 tabular-nums">{{ bodyCount }} 字</div>
                    </div>
                    <div v-else class="mx-auto px-8 py-8" :class="fullView ? 'max-w-[76ch] py-10' : 'max-w-[68ch]'">
                        <article>
                            <h1 class="font-semibold text-slate-900 leading-snug" :class="fullView ? 'text-2xl' : 'text-xl'">{{ content.title }}</h1>
                            <div class="gen-article mt-6" v-html="articleHtml"></div>
                        </article>
                    </div>
                </template>
                <div v-else class="h-full grid place-items-center px-6">
                    <GeoEmpty description="文章会显示在这里，可先回到前面步骤确认要求" />
                </div>
            </div>

            <div v-if="content && !generating" class="shrink-0 px-5 py-3 border-t border-[#F1F5F9]">
                <div class="flex items-end gap-3 mx-auto" :class="fullView ? 'max-w-[76ch]' : 'max-w-[68ch]'">
                    <ElInput
                        v-model="adjustText"
                        type="textarea"
                        :rows="2"
                        resize="none"
                        :disabled="generating"
                        placeholder="想改哪里？例如：开头更口语，补一个农村自建房的例子"
                        @keydown.enter.exact.prevent="onAdjust" />
                    <ElButton
                        type="primary"
                        class="!h-11 !rounded-xl !ml-0 shrink-0"
                        :disabled="!adjustText.trim() || generating"
                        @click="onAdjust">
                        按意见重写
                    </ElButton>
                    <ElButton
                        class="!h-11 !rounded-xl !ml-0 shrink-0"
                        :loading="generating"
                        @click="regenerate">
                        再生成一篇
                    </ElButton>
                </div>
                <div class="mx-auto mt-1.5 text-xs text-slate-400" :class="fullView ? 'max-w-[76ch]' : 'max-w-[68ch]'">
                    有具体意见就重写，没有就整篇再来一次 · 按模型用量计费，失败不扣{{ fullView ? ' · Esc 退出全屏' : '' }}
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoConfirm } from '../_composables/geo-confirm'
import { geoTopics, geoQuestions, geoContentTemplates, geoChatGenerate, geoContentUpdate, geoContents, geoKnowledge, geoInsightOverview, geoChargeConfig } from '@/api/geo'
import GeoEmpty from './geo-empty.vue'

const props = defineProps<{ pid: number; info: any }>()
const emit = defineEmits(['done'])
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

type GenForm = { topic_id: number; keyword_ids: number[]; template: string; style: string; extra: string }
type GenSession = {
    step: number
    farthestStep?: number
    form: GenForm
    customStyle: boolean
    useKb: boolean
    contentId: number | null
    title?: string
    body?: string
    freshStart: boolean
}

const STEPS = ['明确需求', '创作方式', '参考来源', '生成内容']
const STEP_HINTS = [
    '先定话题，再勾选要覆盖的场景问题',
    '选择文章体裁，或直接写想要的风格',
    '决定是否引用品牌语料，并补充诉求',
    '预览生成结果，调整后采纳到文章列表'
]
const step = ref(0)
const farthestStep = ref(0)
const contentLoading = ref(false)
const topics = ref<any[]>([])
const questions = ref<any[]>([])
const templates = ref<any[]>([])
const kbCount = ref(0)
const customStyle = ref(false)
const useKb = ref(true)
const form = reactive<GenForm>({ topic_id: 0, keyword_ids: [], template: 'xuanxing', style: '', extra: '' })
const content = ref<any>(null)
const freshStart = ref(false)
const persistReady = ref(false)
const sessionKey = computed(() => `geo-gen-session-${props.pid}`)

const readSession = (): GenSession | null => {
    if (!import.meta.client) return null
    try {
        const raw = localStorage.getItem(sessionKey.value)
        if (!raw) return null
        const s = JSON.parse(raw)
        return s && typeof s === 'object' ? s : null
    } catch {
        return null
    }
}

const writeSession = () => {
    if (!import.meta.client || !persistReady.value) return
    const payload: GenSession = {
        step: step.value,
        farthestStep: farthestStep.value,
        form: {
            topic_id: form.topic_id,
            keyword_ids: [...form.keyword_ids],
            template: form.template,
            style: form.style,
            extra: form.extra
        },
        customStyle: customStyle.value,
        useKb: useKb.value,
        contentId: content.value?.id ?? null,
        title: content.value?.title,
        body: content.value?.body,
        freshStart: freshStart.value
    }
    localStorage.setItem(sessionKey.value, JSON.stringify(payload))
}

const applySessionForm = (session: GenSession) => {
    const f = session.form
    if (!f) return
    if (f.topic_id) form.topic_id = Number(f.topic_id)
    if (Array.isArray(f.keyword_ids)) form.keyword_ids = f.keyword_ids.map(Number).filter(Boolean)
    if (typeof f.template === 'string') form.template = f.template
    if (typeof f.style === 'string') form.style = f.style
    if (typeof f.extra === 'string') form.extra = f.extra
    customStyle.value = !!session.customStyle
    if (session.useKb !== undefined) useKb.value = !!session.useKb
}

const applyContentRow = (row: any) => {
    content.value = {
        id: row.id,
        title: row.title || '',
        body: row.body || '',
        topic_id: row.topic_id
    }
}

const applyFormFromContent = (row: any) => {
    if (row.topic_id) form.topic_id = Number(row.topic_id) || form.topic_id
    const ids = Array.isArray(row.keyword_ids) ? row.keyword_ids.map(Number).filter(Boolean) : []
    if (ids.length) form.keyword_ids = ids
    else if (row.keyword_id && !form.keyword_ids.length) form.keyword_ids = [Number(row.keyword_id)]
    if (row.template) {
        form.template = String(row.template)
        customStyle.value = false
    } else if (row.style) {
        form.template = ''
        form.style = String(row.style)
        customStyle.value = true
    } else {
        const tpl = templates.value.find((t: any) => t.label === row.content_type || t.key === row.content_type)
        if (tpl) form.template = tpl.key
    }
    if (row.extra != null && row.extra !== '') form.extra = String(row.extra)
    if (row.use_kb !== undefined && row.use_kb !== null) useKb.value = Number(row.use_kb) !== 0
}

const restoreSession = async () => {
    const session = readSession()
    if (session) {
        applySessionForm(session)
        freshStart.value = !!session.freshStart
    }

    let drafts: any[] = []
    try {
        const res = await geoContents({ project_id: props.pid, unpublished: 1, unadopted: 1 })
        drafts = Array.isArray(res) ? res : []
    } catch {
        /* 草稿回填失败时仍可用本地会话 */
    }

    const pickRow = (id: number) => drafts.find((d) => Number(d.id) === Number(id))

    if (!freshStart.value) {
        const row = (session?.contentId && pickRow(session.contentId)) || drafts[0] || null
        if (row) {
            applyContentRow(row)
            const same = !!(session?.contentId && Number(session.contentId) === Number(row.id))
            if (same) {
                if (session.title != null) content.value.title = session.title
                if (session.body != null) content.value.body = session.body
            } else {
                applyFormFromContent(row)
            }
            step.value = 3
            farthestStep.value = 3
            return
        }
    }

    if (session && session.step >= 0 && session.step <= 2) step.value = session.step
    farthestStep.value = Math.max(farthestStep.value, session?.farthestStep ?? 0, step.value)
}

const topicName = computed(() => topics.value.find((t) => t.id === form.topic_id)?.name || '')
const templateLabel = computed(() => (form.template ? templates.value.find((t: any) => t.key === form.template)?.label : '自定义风格') || '自定义风格')
const topicQuestions = computed(() => questions.value
    .filter((q) => q.topic_id === form.topic_id)
    .slice()
    .sort((a, b) => (a.online_rate ?? 101) - (b.online_rate ?? 101)))

const stepTone = (i: number) => (i === step.value ? 'current' : i <= farthestStep.value ? 'done' : 'todo')

watch(step, (v) => {
    if (v > farthestStep.value) farthestStep.value = v
})

const goStep = (i: number) => {
    if (i === step.value) return
    if (i <= farthestStep.value) {
        step.value = i
        return
    }
    if (i >= 1 && !form.topic_id) return ElMessage.warning('请先选择话题')
    if (i >= 2 && !form.template && !form.style.trim()) return ElMessage.warning('请先选择创作方式')
    if (i >= 3 && !content.value) return ElMessage.warning('请先生成内容')
    step.value = i
}

const pickTopic = (t: any) => { form.topic_id = t.id; form.keyword_ids = [] }
const toggleQuestion = (id: number) => {
    const i = form.keyword_ids.indexOf(id)
    if (i >= 0) form.keyword_ids.splice(i, 1)
    else if (form.keyword_ids.length >= 10) ElMessage.warning('最多选择 10 个场景问题')
    else form.keyword_ids.push(id)
}

const contentPrice = ref(0)
const chargeEnabled = ref(false)
const generating = ref(false)
const editMode = ref(false)
const fullView = ref(false)
const adjustText = ref('')
const adopting = ref(false)

const setBodyLock = (lock: boolean) => {
    if (!import.meta.client) return
    document.body.style.overflow = lock ? 'hidden' : ''
}
const closeFullView = () => {
    fullView.value = false
    setBodyLock(false)
}
const toggleFullView = () => {
    if (fullView.value) {
        closeFullView()
        return
    }
    if (!content.value || generating.value) return
    fullView.value = true
    setBodyLock(true)
}
const onFullViewKey = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && fullView.value) closeFullView()
}

const escapeHtml = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
const inlineMd = (s: string) => escapeHtml(s).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
const renderArticle = (body: string) => {
    const lines = String(body || '').replace(/\r\n/g, '\n').split('\n')
    const out: string[] = []
    let para: string[] = []
    const flush = () => {
        if (!para.length) return
        out.push(`<p>${para.join('<br>')}</p>`)
        para = []
    }
    for (const line of lines) {
        const h = line.match(/^(#{1,3})\s+(.+)$/)
        if (h) {
            flush()
            const lv = h[1].length <= 2 ? 2 : 3
            out.push(`<h${lv}>${inlineMd(h[2])}</h${lv}>`)
            continue
        }
        if (!line.trim()) {
            flush()
            continue
        }
        para.push(inlineMd(line))
    }
    flush()
    return out.join('')
}
const articleHtml = computed(() => renderArticle(content.value?.body || ''))
const bodyCount = computed(() => String(content.value?.body || '').replace(/\s/g, '').length)

const startGenerate = async () => {
    step.value = 3
    generating.value = true
    editMode.value = false
    const prev = content.value
    try {
        const res: any = await geoChatGenerate({
            project_id: props.pid, topic_id: form.topic_id, keyword_ids: form.keyword_ids,
            template: form.template, style: form.style, extra: form.extra, use_kb: useKb.value ? 1 : 0,
        })
        if (res?.task?.status === 'failed') {
            let msg = '生成失败,请重试'
            try {
                const logs = JSON.parse(res.task.logs || '[]')
                const last = logs[logs.length - 1]
                if (last?.message) msg = `生成失败:${last.message}`
            } catch (e) { /* 用默认文案 */ }
            content.value = prev
            ElMessage.error(msg)
        } else {
            const c: any = res?.content
            if (c?.id && c.body) {
                content.value = c
                freshStart.value = false
            } else {
                content.value = prev
                ElMessage.warning('生成结果为空,可重试或检查任务日志')
            }
        }
    } catch (e) {
        content.value = prev
        ElMessage.error(errText(e))
    } finally { generating.value = false }
}

const regenerate = async () => {
    if (chargeEnabled.value && content.value) {
        try {
            await geoConfirm({
                title: '重新生成',
                message: '将带着当前要求重新生成一整篇，失败不扣算力。',
                confirmText: '继续生成',
                tone: 'info',
                facts: [{ label: '计费方式', value: '按模型用量计费', emphasize: true }],
                note: '按篇计费，失败不扣'
            })
        } catch { return }
    }
    startGenerate()
}

const onAdjust = async () => {
    const t = adjustText.value.trim()
    if (!t || generating.value) return
    if (chargeEnabled.value && content.value) {
        try {
            await geoConfirm({
                title: '按意见重写',
                message: '将按你的修改意见重新生成一整篇，失败不扣算力。',
                confirmText: '继续生成',
                tone: 'info',
                facts: [{ label: '计费方式', value: '按模型用量计费', emphasize: true }],
                note: '按篇计费，失败不扣'
            })
        } catch { return }
    }
    form.extra = (form.extra ? form.extra + ';' : '') + t
    adjustText.value = ''
    startGenerate()
}

const adopt = async () => {
    if (!content.value) return
    adopting.value = true
    try {
        await geoContentUpdate({ id: content.value.id, title: content.value.title, body: content.value.body, adopted: 1 })
        ElMessage.success('文章已采纳,可在「内容管理」中发布')
        // 采纳即一轮创作闭环:回第一步并清空本轮残留(问题选择/修改意见/编辑态),
        // 与「从头再来」同口径;freshStart 防止重进时被其它未采纳草稿劫持到预览步
        closeFullView()
        step.value = 0
        farthestStep.value = 0
        content.value = null
        editMode.value = false
        adjustText.value = ''
        Object.assign(form, { keyword_ids: [], extra: '' })
        freshStart.value = true
        writeSession()
        emit('done')
    } catch (e) { ElMessage.error(errText(e)) } finally { adopting.value = false }
}

const restart = async () => {
    if (content.value?.id) {
        try { await geoContentUpdate({ id: content.value.id, adopted: 1 }) } catch { /* 仍重置本地 */ }
    }
    closeFullView()
    step.value = 0
    farthestStep.value = 0
    content.value = null
    editMode.value = false
    adjustText.value = ''
    Object.assign(form, { keyword_ids: [], extra: '' })
    freshStart.value = true
    writeSession()
}

watch([step, customStyle, useKb, content, form], writeSession, { deep: true })
watch(step, (v) => { if (v < 3) closeFullView() })

onBeforeUnmount(() => {
    if (import.meta.client) window.removeEventListener('keydown', onFullViewKey)
    closeFullView()
    writeSession()
})

onMounted(async () => {
    if (import.meta.client) window.addEventListener('keydown', onFullViewKey)
    contentLoading.value = true
    try {
        const [tRes, qRes, tplRes, kbRes]: any = await Promise.all([
            geoTopics(props.pid), geoQuestions({ project_id: props.pid, status: 1 }), geoContentTemplates(), geoKnowledge(props.pid),
        ])
        topics.value = tRes?.list || []
        questions.value = qRes || []
        templates.value = tplRes || []
        kbCount.value = (kbRes || []).length
        if (topics.value.length && !form.topic_id) form.topic_id = topics.value[0].id
        try {
            const cfg: any = (await geoChargeConfig()) || {}
            // 模型计费口径:确认弹窗开关看 enabled,score 已恒为 0 不能再作依据
            chargeEnabled.value = !!cfg.enabled
        } catch (e) { /* 忽略 */ }
        try {
            const ov: any = await geoInsightOverview({ project_id: props.pid })
            for (const d of ov?.topic_dim || []) {
                const t = topics.value.find((x) => x.id === d.topic_id)
                if (t) t.visibility = d.visibility
            }
        } catch (e) { /* 可选数据 */ }
        await restoreSession()
    } catch (e) { ElMessage.error(errText(e)) }
    finally {
        persistReady.value = true
        writeSession()
        contentLoading.value = false
    }
})
</script>

<style lang="scss" scoped>
.geo-md {
    :deep(.md-editor) {
        height: 100%;
        border: none;
        --md-bk-color: transparent;
        --md-border-color: transparent;
        --md-color: #334155;
    }
    :deep(.md-editor-toolbar-wrapper),
    :deep(.md-editor-footer) {
        display: none;
    }
    :deep(.cm-editor),
    :deep(.cm-scroller) {
        background: transparent;
    }
    :deep(.cm-content) {
        font-size: 14px;
        line-height: 1.75;
        padding-inline: 0;
    }
}
.gen-article {
    :deep(h2) {
        @apply text-base font-semibold text-slate-900 mt-8 mb-3;
    }
    :deep(h2:first-child) {
        @apply mt-0;
    }
    :deep(h3) {
        @apply text-sm font-semibold text-slate-800 mt-6 mb-2;
    }
    :deep(p) {
        @apply text-sm text-slate-700 leading-7 mb-4;
    }
    :deep(strong) {
        @apply font-semibold text-slate-900;
    }
}
</style>
