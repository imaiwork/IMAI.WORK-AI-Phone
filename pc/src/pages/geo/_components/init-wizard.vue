<template>
    <GeoPageSkeleton v-if="!pageReady" />

    <div v-else class="h-full flex flex-col min-w-[1000px] px-4 pb-4">
        <header class="shrink-0 rounded-[20px] bg-white border border-br px-8 py-5">
            <div class="flex items-center justify-between gap-4 mb-5">
                <div class="flex items-center gap-4 min-w-0">
                    <ElButton link class="!px-0 !text-slate-500" @click="emit('back')">返回品牌列表</ElButton>
                    <div class="w-px h-5 bg-slate-200"></div>
                    <div class="min-w-0">
                        <div class="text-lg font-semibold text-slate-800">{{ projectId ? '初始化配置' : '新建 GEO 项目' }}</div>
                        <div class="text-sm text-slate-500 mt-0.5">{{ STEP_HINTS[step] }}</div>
                    </div>
                </div>
                <div class="text-xs text-slate-400 shrink-0">第 {{ step + 1 }} / {{ STEPS.length }} 步</div>
            </div>
            <nav class="flex items-center" aria-label="创建进度">
                <template v-for="(s, i) in STEPS" :key="s">
                    <button
                        type="button"
                        class="flex items-center gap-2 min-w-0 text-left"
                        :class="stepTone(i) === 'current' ? 'text-primary' : stepTone(i) === 'done' ? 'text-slate-700 cursor-pointer' : 'text-slate-400 cursor-pointer'"
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
                    <div v-if="i < STEPS.length - 1" class="flex-1 h-px mx-3" :class="i < farthestStep ? 'bg-primary' : 'bg-slate-200'"></div>
                </template>
            </nav>
        </header>

        <div class="grow min-h-0 flex flex-col bg-white rounded-[20px] border border-br overflow-hidden mt-4">
            <main class="flex-1 min-h-0" :class="step === 2 ? 'overflow-hidden' : 'overflow-y-auto p-8'">
                <!-- 步骤1:设定品牌信息 -->
                <div v-if="step === 0" class="max-w-[680px]">
                    <div class="mb-6">
                        <div class="text-base font-semibold text-slate-800">品牌是谁</div>
                        <div class="text-sm text-slate-500 mt-1">填对外使用的正式名称，会写入监测口径</div>
                    </div>
                    <div class="space-y-5">
                        <div>
                            <label class="text-sm font-semibold text-slate-800">品牌 / 公司名称 <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-2 mt-2">
                                <ElInput v-model="brand.brand_name" size="large" class="flex-1" placeholder="如 IMAI数字员工" @keyup.enter="onReDiagnose" />
                                <ElSelect v-if="matchModelList.length" v-model="matchModel" filterable size="large" style="width:148px" placeholder="模型">
                                    <ElOption v-for="m in matchModelList" :key="m.value" :value="m.value" :label="m.label" />
                                </ElSelect>
                                <ElButton type="primary" class="!h-10 !px-4 !rounded-lg shrink-0" :loading="busy.rediag" @click="onReDiagnose">
                                    <Icon name="el-icon-MagicStick" :size="14" class="mr-1" />
                                    AI 匹配
                                </ElButton>
                            </div>
                            <div class="text-xs text-slate-500 mt-2">按模型用量计费，失败不扣，结果可改</div>
                            <div
                                v-if="matched"
                                class="text-xs mt-1.5"
                                :class="matchConfidence === 'low' ? 'text-amber-700' : 'text-emerald-700'">
                                {{ matchConfidence === 'low'
                                    ? `已按「${brand.brand_name}」推测行业与别名，请核对后再继续`
                                    : `已根据「${brand.brand_name}」匹配出行业与别名，不准确可以直接改` }}
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-800">所属行业 <span class="text-rose-500">*</span></label>
                            <ElInput v-model="brand.industry" size="large" class="mt-2" placeholder="如 软件工具 / 办公工具" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label class="text-sm font-semibold text-slate-800">品牌别名</label>
                                <span class="text-xs text-slate-500">{{ aliases.length }} 个</span>
                            </div>
                            <div class="text-xs text-slate-500 mt-1 mb-2">简称、英文名、产品名都会纳入监测，点标签可修改</div>
                            <div class="chip-well">
                                <template v-for="(a, i) in aliases" :key="'alias-' + i">
                                    <ElInput
                                        v-if="aliasEditIndex === i"
                                        ref="aliasEditRef"
                                        v-model="aliasEditValue"
                                        size="small"
                                        class="!w-[148px]"
                                        @keyup.enter="commitEditAlias"
                                        @keyup.esc="cancelEditAlias"
                                        @blur="commitEditAlias" />
                                    <ElTag
                                        v-else
                                        closable
                                        size="large"
                                        effect="light"
                                        class="cursor-pointer"
                                        @click="startEditAlias(i)"
                                        @close="aliases.splice(i, 1)">{{ a }}</ElTag>
                                </template>
                                <ElInput v-model="aliasInput" class="!w-[168px]" size="small" placeholder="回车添加" @keyup.enter="addAlias" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 步骤2:确定话题 -->
                <div v-if="step === 1" class="max-w-[680px]">
                    <div class="mb-8">
                        <div class="text-base font-semibold text-slate-800">监测哪些话题</div>
                        <div class="text-sm text-slate-500 mt-1">可直接使用推荐结果，也可删改；最多 {{ maxTopics }} 个</div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label class="text-sm font-semibold text-slate-800">关键话题 <span class="text-rose-500">*</span></label>
                                <span class="text-xs text-slate-400">{{ topics.length }} / {{ maxTopics }}</span>
                            </div>
                            <div class="chip-well mt-2">
                                <ElTag v-for="(t, i) in topics" :key="t" closable size="large" effect="light" @close="topics.splice(i, 1)">{{ t }}</ElTag>
                                <ElInput v-if="topics.length < maxTopics" v-model="topicInput" class="!w-[180px]" size="small" placeholder="回车添加话题" @keyup.enter="addTopic" />
                            </div>
                            <div class="flex items-center gap-3 mt-3">
                                <ElButton plain type="primary" :loading="busy.aiTopics" :disabled="topics.length >= maxTopics" @click="aiRecommendTopics">
                                    <Icon name="el-icon-MagicStick" :size="14" class="mr-1" />
                                    AI 推荐话题
                                </ElButton>
                                <span class="text-slate-400 text-xs">按模型用量计费，推荐仅供挑选</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-800">每个话题生成的场景问题数</label>
                            <div class="text-xs text-slate-400 mt-1 mb-2">数量越多覆盖越全，生成耗时和算力也会增加</div>
                            <ElInputNumber v-model="questionCount" :min="3" :max="20" />
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-800">补充说明 <span class="text-slate-400 font-normal">可选</span></label>
                            <div class="text-xs text-slate-400 mt-1 mb-2">产品特性、服务优势、重点区域等，会带入场景问题生成</div>
                            <ElInput v-model="extraInfo" type="textarea" :rows="4" placeholder="例如：面向中小商家，强调私域成交和算力成本可控" />
                        </div>
                    </div>
                </div>

                <!-- 步骤3:设置场景问题 -->
                <div v-if="step === 2" class="h-full flex">
                    <aside class="w-[240px] shrink-0 border-r border-[#F1F5F9] bg-[#f8fafc] flex flex-col">
                        <div class="px-5 py-4 border-b border-[#F1F5F9]">
                            <div class="text-sm font-semibold text-slate-800">按话题查看</div>
                            <div class="text-xs text-slate-400 mt-0.5">共 {{ questions.length }} 条场景问题</div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-3 space-y-1">
                            <button
                                type="button"
                                class="topic-item"
                                :class="{ 'is-active': !filterTopicId }"
                                @click="filterTopicId = 0">
                                <span class="truncate">全部话题</span>
                                <span class="topic-count">{{ questions.length }}</span>
                            </button>
                            <button
                                v-for="t in topicRows"
                                :key="t.id"
                                type="button"
                                class="topic-item"
                                :class="{ 'is-active': filterTopicId === t.id }"
                                @click="filterTopicId = t.id">
                                <span class="truncate pr-2">{{ t.name }}</span>
                                <span class="topic-count">{{ t.question_count }}</span>
                            </button>
                        </div>
                    </aside>
                    <div class="flex-1 min-w-0 flex flex-col">
                        <div class="px-6 py-4 border-b border-[#F1F5F9] flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ filterTopicName }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">这些是向 AI 平台提问的原话，可改写得更接近真实用户问法</div>
                            </div>
                            <div class="text-xs text-slate-400 shrink-0 pt-0.5">{{ shownQuestions.length }} 条</div>
                        </div>
                        <div class="flex-1 min-h-0 overflow-y-auto px-6 py-3">
                            <div v-if="shownQuestions.length" class="space-y-2">
                                <div v-for="(row, idx) in shownQuestions" :key="row.id" class="q-item">
                                    <div class="w-7 h-7 mt-0.5 rounded-lg bg-slate-100 text-slate-500 text-xs font-semibold grid place-items-center shrink-0">
                                        {{ idx + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <ElInput
                                            v-if="editingId === row.id"
                                            v-model="editingValue"
                                            @keyup.enter="saveEdit(row)"
                                            @keyup.esc="cancelEdit" />
                                        <div v-else class="text-sm text-slate-800 leading-6 break-words">{{ row.value }}</div>
                                        <ElTag v-if="editingId !== row.id && !filterTopicId && row.topic_name" size="small" effect="plain" class="!mt-1.5">{{ row.topic_name }}</ElTag>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <template v-if="editingId === row.id">
                                            <ElButton type="primary" class="!h-9 !rounded-lg" @click="saveEdit(row)">保存</ElButton>
                                            <ElButton class="!h-9 !rounded-lg" @click="cancelEdit">取消</ElButton>
                                        </template>
                                        <template v-else>
                                            <ElButton link type="primary" class="!h-9 !px-2" @click="startEdit(row)">编辑</ElButton>
                                            <ElButton link type="danger" class="!h-9 !px-2" @click="delQuestion(row)">删除</ElButton>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <GeoEmpty v-else description="该话题还没有场景问题，可在下方添加" />
                        </div>
                        <div class="q-composer px-6 py-4 border-t border-[#F1F5F9] bg-[#f8fafc] flex items-center gap-3">
                            <ElSelect v-model="addTopicId" placeholder="选择话题" class="!w-[180px]">
                                <ElOption v-for="t in topicRows" :key="t.id" :label="t.name" :value="t.id" />
                            </ElSelect>
                            <ElInput v-model="addValue" class="flex-1" placeholder="输入新的场景问题，回车添加" @keyup.enter="addQuestion" />
                            <ElButton type="primary" class="!h-11 !px-5 !rounded-xl" @click="addQuestion">添加问题</ElButton>
                        </div>
                    </div>
                </div>

                <!-- 步骤4:提交成功 -->
                <div v-if="step === 3" class="h-full grid place-items-center">
                    <div class="text-center max-w-[520px] py-8">
                        <div class="w-16 h-16 rounded-2xl bg-[#F5F7FF] text-primary grid place-items-center mx-auto mb-5">
                            <Icon name="el-icon-DataLine" :size="28" />
                        </div>
                        <div class="text-xl font-semibold text-slate-800 mb-2">诊断已提交</div>
                        <div class="text-slate-500 text-sm mb-5">
                            已完成 {{ diagDone }} / {{ diagTotal }} 项监测{{ diagDone >= diagTotal && diagTotal > 0 ? '，全部完成' : '，采集在后台进行，可先进入工作台' }}
                        </div>
                        <ElProgress :percentage="diagTotal ? Math.round(diagDone / diagTotal * 100) : 0" :stroke-width="10" class="mb-6" />
                      
                    </div>
                </div>
            </main>

            <footer v-if="step < 3" class="shrink-0 border-t border-[#F1F5F9] px-8 py-4 flex items-center justify-between gap-4 bg-white">
                <div class="text-xs text-slate-400 min-w-0">
                    <template v-if="step === 0">{{ canSkipRecommend ? '品牌信息可修改后直接进入下一步' : '下一步将根据品牌信息推荐监测话题' }}</template>
                    <template v-else-if="step === 1">
                        <span v-if="busy.questions && genProgress">{{ genProgress }}</span>
                        <span v-else>下一步将为每个话题生成场景问题，按模型用量计费（失败不扣）</span>
                    </template>
                    <template v-else>确认问题后提交首轮 AI 诊断</template>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <ElButton v-if="step > 0" class="!h-11 !px-6 !rounded-xl" @click="step -= 1">返回上一步</ElButton>
                    <ElButton v-if="step === 0" type="primary" class="!h-11 !px-6 !rounded-xl" :loading="busy.topics" @click="onStep0Primary">
                        {{ canSkipRecommend ? '下一步' : '智能推荐话题' }}
                    </ElButton>
                    <ElButton v-else-if="step === 1" type="primary" class="!h-11 !px-6 !rounded-xl" :loading="busy.questions" @click="toQuestions">
                        继续
                    </ElButton>
                    <ElButton v-else type="primary" class="!h-11 !px-6 !rounded-xl" :disabled="!questions.length" @click="submitDiagnosis">
                        完成并提交诊断
                    </ElButton>
                </div>
            </footer>
            <footer v-else class="shrink-0 border-t border-[#F1F5F9] px-8 py-4 flex justify-end bg-white">
                <ElButton type="primary" class="!h-11 !px-6 !rounded-xl" @click="emit('done', projectId)">进入工作台</ElButton>
            </footer>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoConfirm } from '../_composables/geo-confirm'
import {
    geoProjectCreate, geoProjectUpdate, geoAiTopics, geoTopics, geoTopicSave, geoTopicDelete,
    geoAiQuestions, geoQuestions, geoQuestionSave, geoQuestionBatch,
    geoMonitorEngines, geoChargeConfig, geoAiMatchBrand, geoMatchModels, geoInitState,
    geoMonitorBatch, geoMonitorProgress,
} from '@/api/geo'
import { geoDiagnosisSubmitted, wizardPageOfResume } from '../_enums/nav'
import GeoPageSkeleton from './geo-page-skeleton.vue'
import GeoEmpty from './geo-empty.vue'

// pid=0 → 建品牌模式(第1步先创建项目);pid>0 → 已有品牌的初始化/重配
const props = defineProps<{ pid: number; info?: any; autoResume?: boolean; resumeStep?: number }>()
const emit = defineEmits(['done', 'back'])
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

// 内部项目 id:建品牌模式下由第1步创建后填入
const projectId = ref(Number(props?.pid) || 0)

const STEPS = ['设定品牌信息', '确定话题', '设置场景问题', 'AI诊断报告']
const STEP_HINTS = [
    '品牌名称会用于 AI 问答采集，请填写对外使用的正式名称',
    '话题决定监测覆盖的场景，可直接使用推荐结果',
    '场景问题是向 AI 平台提问的原话，可增删改',
    '提交后开始首轮监测，可先进入工作台'
]
const step = ref(props.autoResume ? wizardPageOfResume(Number(props.resumeStep ?? 0)) : 0)
const farthestStep = ref(step.value)
const pageReady = ref(props.pid === 0)
const canSkipRecommend = computed(() => topics.value.length > 0 || farthestStep.value >= 1)
const stepTone = (i: number) => (i === step.value ? 'current' : i <= farthestStep.value ? 'done' : 'locked')
watch(step, (v) => {
    if (v > farthestStep.value) farthestStep.value = v
})
const busy = reactive({ rediag: false, topics: false, questions: false, aiTopics: false })

// 组件是否已销毁:所有"逐条循环调 AI"的地方都要检查它。
// 否则用户中途离开后，循环会在已销毁的组件里继续发几十个慢请求并持续扣费。
let disposed = false
// 是否有正在跑的 AI 生成 —— 用于离开拦截
const aiRunning = computed(() => busy.topics || busy.questions || busy.aiTopics || diagRunning.value)

// 计费单价(演示模式为 0,自动隐藏所有价格提示)
const topicPrice = ref(0)
const questionPrice = ref(0)
const monitorPrice = ref(0)
const matchPrice = ref(0)
const loadPrices = async () => {
    try {
        const cfg: any = (await geoChargeConfig()) || {}
        const charges: any[] = Array.isArray(cfg) ? cfg : cfg.list || []
        const p = (s: string) => Number(charges.find((c: any) => c.scene === s)?.score || 0)
        topicPrice.value = p('geo_topic_ai')
        questionPrice.value = p('geo_question_ai')
        monitorPrice.value = p('geo_monitor')
        matchPrice.value = p('geo_match_brand')
    } catch (e) { /* 取不到价则不展示提示 */ }
}

// ---- 步1:品牌信息 ----
const brand = reactive({ brand_name: '', industry: '' })
const aliases = ref<string[]>([])
const aliasInput = ref('')
const aliasEditIndex = ref(-1)
const aliasEditValue = ref('')
const aliasEditRef = ref()
const addAlias = () => {
    const v = aliasInput.value.trim()
    if (!v) return
    if (aliases.value.includes(v)) return ElMessage.warning('别名不可重复')
    aliases.value.push(v); aliasInput.value = ''
}
const startEditAlias = async (i: number) => {
    if (aliasEditIndex.value === i) return
    if (aliasEditIndex.value >= 0 && !commitEditAlias()) return
    aliasEditIndex.value = i
    aliasEditValue.value = aliases.value[i]
    await nextTick()
    const el = aliasEditRef.value as any
    ;(Array.isArray(el) ? el[0] : el)?.focus?.()
}
const commitEditAlias = () => {
    const i = aliasEditIndex.value
    if (i < 0) return true
    const v = aliasEditValue.value.trim()
    if (!v) {
        aliases.value.splice(i, 1)
    } else if (aliases.value.some((a, j) => j !== i && a === v)) {
        ElMessage.warning('别名不可重复')
        return false
    } else {
        aliases.value[i] = v
    }
    aliasEditIndex.value = -1
    return true
}
const cancelEditAlias = () => {
    aliasEditIndex.value = -1
}
// AI 匹配品牌信息:品牌名 → 回填 行业+别名+简介(走主系统模型网关,模型可切换)
const matched = ref(false)
const matchConfidence = ref('')
const matchModel = ref('')
const matchModelList = ref<any[]>([])
const matchedIntro = ref('') // AI 匹配到的一句话简介,随品牌保存进 intro 字段(喂给后续话题/内容生成)
const onReDiagnose = async () => {
    if (!brand.brand_name.trim()) return ElMessage.warning('请先填写品牌名称')
    busy.rediag = true
    try {
        const res: any = await geoAiMatchBrand(brand.brand_name.trim(), matchModel.value)
        // 行业直接回填(可改);别名合并去重,保留用户已手填的
        if (res?.industry) brand.industry = res.industry
        for (const a of res?.aliases || []) {
            if (a && !aliases.value.includes(a)) aliases.value.push(a)
        }
        if (res?.intro) matchedIntro.value = res.intro
        matchConfidence.value = res?.confidence || 'low'
        matched.value = true
        ElMessage.success(matchConfidence.value === 'high' ? '已匹配行业与别名,确认后点击「智能推荐话题」' : '已按名称推测行业,请核对修改后继续')
    } catch (e) { ElMessage.error(errText(e)) } finally { busy.rediag = false }
}
// 建品牌模式:首次保存时创建项目并记录新 id;后续/已有品牌走更新
const saveBrand = async () => {
    const payload: any = { brand_name: brand.brand_name.trim(), industry: brand.industry.trim(), aliases: aliases.value }
    if (matchedIntro.value) payload.intro = matchedIntro.value
    if (projectId.value > 0) {
        await geoProjectUpdate({ id: projectId.value, ...payload })
    } else {
        const res: any = await geoProjectCreate(payload)
        projectId.value = Number(res?.id) || 0
        if (!projectId.value) throw new Error('创建品牌失败')
    }
}

// ---- 步2:话题 ----
const maxTopics = ref(3)
const topics = ref<string[]>([])
const suggestedTopics = ref<string[]>([])
const topicInput = ref('')
const questionCount = ref(10)
const extraInfo = ref('')
const addTopic = () => {
    const v = topicInput.value.trim()
    if (!v) return
    if (topics.value.includes(v)) return ElMessage.warning('话题不可重复')
    if (topics.value.length >= maxTopics.value) return ElMessage.warning(`最多 ${maxTopics.value} 个话题`)
    topics.value.push(v); topicInput.value = ''
}
const toTopics = async () => {
    if (!brand.brand_name.trim()) return ElMessage.warning('请填写品牌名称')
    busy.topics = true
    try {
        await saveBrand()
        if (!topics.value.length) {
            const res: any = suggestedTopics.value.length ? { topics: suggestedTopics.value } : await geoAiTopics(projectId.value, maxTopics.value)
            topics.value = (res?.topics || []).slice(0, maxTopics.value)
        }
        step.value = 1
    } catch (e) { ElMessage.error(errText(e)) } finally { busy.topics = false }
}

const nextFromBrand = async () => {
    if (!brand.brand_name.trim()) return ElMessage.warning('请填写品牌名称')
    busy.topics = true
    try {
        await saveBrand()
        step.value = 1
    } catch (e) { ElMessage.error(errText(e)) } finally { busy.topics = false }
}

const onStep0Primary = () => {
    if (canSkipRecommend.value) nextFromBrand()
    else toTopics()
}

const goStep = async (i: number) => {
    if (i === step.value) return
    if (i === 3 && !(diagTotal.value > 0 || diagRunning.value || farthestStep.value >= 3)) {
        return ElMessage.warning('请先确认场景问题并提交诊断')
    }
    if (i === 2 && !questions.value.length && farthestStep.value < 2) {
        return ElMessage.warning('请先确定话题并生成场景问题')
    }
    if (i === 1 && !canSkipRecommend.value && !brand.brand_name.trim()) {
        return ElMessage.warning('请先填写品牌名称')
    }
    if (i > 0 && !brand.brand_name.trim()) {
        return ElMessage.warning('请先完成「设定品牌信息」')
    }
    if (i >= 2 && !topics.value.length && !questions.value.length) {
        return ElMessage.warning('请先完成「确定话题」')
    }
    if (i === 1 && step.value === 0) {
        await nextFromBrand()
        return
    }
    if (i === 2 && !topicRows.value.length && projectId.value > 0) {
        try { await loadTopicData() } catch (e) { /* 进页后再看空态 */ }
    }
    step.value = i
}

// 第2步内的 AI 推荐:不受"已有话题"守卫限制,重配模式/删光后都能再次请求(PRD 4.2)
const aiRecommendTopics = async () => {
    if (topics.value.length >= maxTopics.value) return ElMessage.warning(`话题已满 ${maxTopics.value} 个,删除后再推荐`)
    busy.aiTopics = true
    try {
        const res: any = await geoAiTopics(projectId.value, maxTopics.value)
        const fresh = (res?.topics || []).filter((t: string) => !topics.value.includes(t))
        if (!fresh.length) { ElMessage.info('AI 未给出新的话题建议,可手动输入'); return }
        for (const t of fresh) {
            if (topics.value.length >= maxTopics.value) break
            topics.value.push(t)
        }
        ElMessage.success(`已补充 ${Math.min(fresh.length, maxTopics.value)} 个 AI 推荐话题,可删改后继续`)
    } catch (e) { ElMessage.error(errText(e)) } finally { busy.aiTopics = false }
}

// ---- 步3:场景问题 ----
const topicRows = ref<any[]>([])
const questions = ref<any[]>([])
const filterTopicId = ref(0)
const genProgress = ref('')
const shownQuestions = computed(() => (filterTopicId.value ? questions.value.filter((q) => q.topic_id === filterTopicId.value) : questions.value))
const filterTopicName = computed(() => {
    if (!filterTopicId.value) return '全部场景问题'
    return topicRows.value.find((t) => t.id === filterTopicId.value)?.name || '场景问题'
})

const loadTopicData = async () => {
    const [tRes, qRes]: any = await Promise.all([geoTopics(projectId.value), geoQuestions({ project_id: projectId.value })])
    topicRows.value = tRes?.list || []
    questions.value = qRes || []
}

const toQuestions = async () => {
    if (!topics.value.length) return ElMessage.warning('请至少确定一个话题')
    busy.questions = true
    try {
        const existing: any = await geoTopics(projectId.value)
        const existNames: string[] = (existing?.list || []).map((t: any) => t.name)
        // 先建后删:反过来的话，中途失败会出现"旧话题已删、新话题没建全"的空档，
        // 用户重进向导看到的是被削掉一半的话题列表。
        for (const name of topics.value) {
            if (disposed) return
            if (existNames.includes(name)) continue
            await geoTopicSave({ project_id: projectId.value, name, question_target: questionCount.value })
        }
        for (const t of existing?.list || []) {
            if (disposed) return
            if (!topics.value.includes(t.name)) await geoTopicDelete(projectId.value, t.id)
        }
        // 逐话题生成场景问题(每次都是一轮真实 AI 调用，慢且计费)
        const tRes: any = await geoTopics(projectId.value)
        const rows = tRes?.list || []
        for (let i = 0; i < rows.length; i++) {
            // 用户已离开就立刻停:继续跑既看不到结果，又在持续扣算力
            if (disposed) return
            const t = rows[i]
            if (t.question_count >= questionCount.value) continue
            genProgress.value = `正在为「${t.name}」生成场景问题(${i + 1}/${rows.length})…`
            await geoAiQuestions(projectId.value, t.id, questionCount.value, extraInfo.value)
        }
        if (disposed) return
        await loadTopicData()
        step.value = 2
    } catch (e) { ElMessage.error(errText(e)) } finally { busy.questions = false; genProgress.value = '' }
}

// 编辑/增删问题
const editingId = ref(0)
const editingValue = ref('')
const startEdit = (row: any) => { editingId.value = row.id; editingValue.value = row.value }
const cancelEdit = () => { editingId.value = 0; editingValue.value = '' }
const saveEdit = async (row: any) => {
    const v = editingValue.value.trim()
    editingId.value = 0
    if (!v || v === row.value) return
    try { await geoQuestionSave({ project_id: projectId.value, id: row.id, value: v }); row.value = v } catch (e) { ElMessage.error(errText(e)) }
}
const delQuestion = async (row: any) => {
    try { await geoQuestionBatch(projectId.value, [row.id], 'delete'); await loadTopicData() } catch (e) { ElMessage.error(errText(e)) }
}
const addTopicId = ref<number | null>(null)
const addValue = ref('')
watch(filterTopicId, (id) => {
    cancelEdit()
    if (id) addTopicId.value = id
})
const addQuestion = async () => {
    const v = addValue.value.trim()
    if (!v) return
    if (!addTopicId.value) return ElMessage.warning('请选择话题')
    try {
        await geoQuestionSave({ project_id: projectId.value, topic_id: addTopicId.value, value: v })
        addValue.value = ''
        await loadTopicData()
    } catch (e) { ElMessage.error(errText(e)) }
}

// ---- 步4:提交诊断(后台逐问题×引擎跑监测) ----
const diagTotal = ref(0)
const diagDone = ref(0)
const diagRunning = ref(false)
// 组件卸载(用户点「查看报告」离开)后停止循环,避免在死组件里继续发几十个慢请求
let diagCancelled = false

// 离开拦截:AI 生成中直接关标签页会让已扣的算力白花，且进度只能靠断点续跑找回
const onBeforeUnload = (e: BeforeUnloadEvent) => {
    if (!aiRunning.value) return
    e.preventDefault()
    e.returnValue = ''
}
onMounted(() => window.addEventListener('beforeunload', onBeforeUnload))
onUnmounted(() => {
    diagCancelled = true
    disposed = true
    if (progressTimer) clearTimeout(progressTimer)
    window.removeEventListener('beforeunload', onBeforeUnload)
})
const submitDiagnosis = async () => {
    if (diagRunning.value) return
    // 询价与确认分开 catch:询价失败要明确报错,不能与"用户取消"混为一谈静默返回
    let unit = 0, availCount = 1, enabledCount = 0
    try {
        const engines0: any = (await geoMonitorEngines()) || []
        availCount = Math.max(1, engines0.filter((e: any) => e.available).length)
        enabledCount = questions.value.filter((q) => q.status !== 0).length
        const cfg: any = (await geoChargeConfig()) || {}
        // 模型计费口径:是否弹确认看 enabled,不能再看场景价(score 恒为 0 会导致
        // 首轮几百次监测调用无任何确认直接扣费)
        unit = cfg.enabled ? 1 : 0
    } catch (e) {
        ElMessage.error('获取引擎与价格配置失败,请稍后重试')
        return
    }
    if (unit > 0 && enabledCount) {
        try {
            await geoConfirm({
                title: 'AI 诊断',
                message: '将对场景问题发起首轮监测，按各引擎模型用量计费，失败的引擎不扣。',
                confirmText: '提交诊断',
                tone: 'info',
                facts: [
                    { label: '场景问题', value: `${enabledCount} 个` },
                    { label: 'AI 引擎', value: `${availCount} 个` },
                    { label: '计费方式', value: '按模型用量计费', emphasize: true }
                ],
                note: '失败的引擎不扣费'
            })
        } catch { return } // 用户取消
    }
    diagRunning.value = true
    step.value = 3
    try {
        // 一次提交，服务端把 问题×引擎 全部入队后立即返回。
        // 旧做法是在这里逐 cell 串行发 HTTP：20 题 × 4 引擎 × 约 15s ≈ 20 分钟，
        // 生产 60s 网关必然中断，用户只能看着进度条卡死。
        const res: any = await geoMonitorBatch({ project_id: projectId.value })
        diagTotal.value = Number(res?.total || 0)
        diagDone.value = 0
        pollProgress(Number(res?.since || 0))
    } catch (e) {
        const msg = errText(e)
        // 后端防重复提交:已有进行中的批次 → 恢复其进度轮询而不是报错
        if (msg.includes('还在进行中')) {
            ElMessage.info(msg)
            diagRunning.value = true
            pollProgress(0)
            return
        }
        ElMessage.error(msg)
        diagRunning.value = false
    }
}

// 轮询采集进度。队列在后台跑，用户可以直接进工作台，回来还能看到进度。
let progressTimer: any = null
const diagStalled = ref(false)
const pollProgress = async (since: number) => {
    if (progressTimer) { clearTimeout(progressTimer); progressTimer = null }
    diagStalled.value = false
    let ticks = 0
    const tick = async () => {
        if (diagCancelled || !diagRunning.value) return
        try {
            const p: any = await geoMonitorProgress(projectId.value, since)
            diagDone.value = Number(p?.done || 0)
            diagTotal.value = Number(p?.total || diagTotal.value)
            if (p?.finished) { diagRunning.value = false; diagStalled.value = false; return }
            // 失速检测：任务已入队但两分钟内一条都没落库，基本可以断定
            // 后台没有 queue:work 常驻。不提示的话用户只会看到进度条永远 0%。
            if (++ticks >= 24 && diagDone.value === 0) diagStalled.value = true
        } catch (e) { /* 单次查询失败不中断轮询 */ }
        progressTimer = setTimeout(tick, 5000)
    }
    tick()
}

onMounted(async () => {
    brand.brand_name = props.info?.brand_name || ''
    brand.industry = props.info?.industry || ''
    aliases.value = [...(props.info?.aliases || [])] // 拷贝,避免直接改父级 info.aliases
    loadPrices()
    // 「AI匹配品牌信息」可切换的模型(主系统 la_models,默认 GPT 系);取不到则不显示选择器,后端用默认模型
    geoMatchModels().then((res: any) => {
        matchModelList.value = res?.list || []
        matchModel.value = res?.default || ''
    }).catch(() => { /* 忽略 */ })
    try {
        // 已有项目:读取话题上限,并回填已有话题到第2步列表——
        // 否则重配模式下用户看不到旧话题,「继 续」会把它们当作已移除而连带删除
        if (projectId.value > 0) {
            try {
                const res: any = await geoTopics(projectId.value)
                if (res?.max) maxTopics.value = res.max
                topics.value = (res?.list || []).map((t: any) => t.name).slice(0, maxTopics.value)
            } catch (e) { /* 忽略,用默认 */ }
            await resumeIfInterrupted(!!props.autoResume, Number(props.resumeStep ?? 0))
        }
    } finally {
        pageReady.value = true
    }
})

// ---- 断点续跑 ----
// 上次没走到"创建完成"就离开(关页面/刷新/切路由/AI 生成中断)时，
// 把用户带回中断那一步，并且必须手动确认才继续 —— 不自动接着跑，
// 因为每一步都会真实调 AI 并扣算力，不能替用户决定。
const applyResumeStep = async (resumeStep: number, diagnosisSubmitted = false) => {
    const page = wizardPageOfResume(resumeStep, diagnosisSubmitted)
    if (page >= 2) {
        try { await loadTopicData() } catch (e) { /* 拉不到就让用户在该步自行刷新 */ }
    }
    step.value = page
}

// 诊断步续跑:存在进行中的批次则直接恢复"诊断已提交"进度视图并继续轮询
// (后端 monitorProgress 不传批次 id 时自动取该项目最近批次)
const resumeDiagIfRunning = async () => {
    if (step.value !== 3 || diagRunning.value) return
    try {
        const p: any = await geoMonitorProgress(projectId.value, 0)
        if (Number(p?.batch_task_id || 0) > 0 && p?.finished === false) {
            diagTotal.value = Number(p?.total || 0)
            diagDone.value = Number(p?.done || 0)
            diagRunning.value = true
            pollProgress(0)
        }
    } catch (e) { /* 恢复失败不阻断向导 */ }
}

const resumeIfInterrupted = async (fromList = false, listResumeStep = 0) => {
    if (fromList) {
        let submitted = false
        try {
            submitted = geoDiagnosisSubmitted(await geoInitState(projectId.value))
        } catch { /* 按未提交处理，停在场景问题页 */ }
        await applyResumeStep(listResumeStep, submitted)
        await resumeDiagIfRunning()
        return
    }

    let state: any
    try {
        state = await geoInitState(projectId.value)
    } catch (e) { return }   // 拿不到状态就按默认从第1步引导，不打断用户
    if (!state?.interrupted) return

    const target = Number(state.resume_step)
    if (!(target > 0 && target <= 3)) return

    const submitted = geoDiagnosisSubmitted(state)
    const p = state.progress || {}
    const detail = [
        p.topic_count ? `已有话题 ${p.topic_count} 个` : '',
        p.question_count ? `场景问题 ${p.question_count} 条` : '',
        p.cell_expect ? `监测进度 ${p.cell_done}/${p.cell_expect}` : '',
    ].filter(Boolean).join('，')
    const page = wizardPageOfResume(target, submitted)
    const label = STEPS[page] || STEPS[target]

    try {
        await geoConfirm({
            title: '继续未完成的创建',
            message: `${state.hint || '上次创建未完成'}。是否从「${label}」继续？`,
            confirmText: `从「${label}」继续`,
            cancelText: '从头重新设置',
            tone: 'warning',
            impacts: detail ? [detail] : []
        })
    } catch {
        return   // 用户选择从头来过，保持 step=0，已有数据在各步里仍可编辑
    }

    await applyResumeStep(target, submitted)
    await resumeDiagIfRunning()

    if (state.last_failed_task) {
        ElMessage.warning('上次有一个生成任务失败了，可在本步重新发起')
    }
}
</script>

<style lang="scss" scoped>
.chip-well {
    @apply flex flex-wrap items-center gap-2 w-full min-h-[52px] rounded-xl border border-br bg-slate-50 px-3 py-2;
}
.topic-item {
    @apply w-full px-3 py-2.5 flex items-center justify-between gap-2 text-left text-sm rounded-xl text-slate-600;
    &:hover {
        @apply bg-white;
    }
    &.is-active {
        @apply bg-white text-primary font-semibold;
        .topic-count {
            @apply bg-[#F5F7FF] text-primary;
        }
    }
}
.topic-count {
    @apply shrink-0 min-w-[22px] h-5 px-1.5 rounded-md bg-slate-100 text-slate-400 text-xs grid place-items-center;
}
.q-item {
    @apply flex items-start gap-3 rounded-xl border border-[#F1F5F9] px-3 py-3;
    &:hover {
        @apply bg-slate-50;
    }
}
.q-composer {
    :deep(.el-select__wrapper),
    :deep(.el-input__wrapper) {
        min-height: 44px;
    }
}
</style>

