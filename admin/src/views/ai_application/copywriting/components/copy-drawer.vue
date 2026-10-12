<template>
    <el-drawer
        v-model="visible"
        :title="isEdit ? '编辑文案' : '新建文案'"
        size="1040px"
        destroy-on-close
        class="copy-drawer"
        @close="handleClose">
        <div class="dw-body">
            <div class="dw-form">
                <section class="fs">
                    <div class="fs-h">
                        <span class="step-n">1</span>
                        用途
                    </div>
                    <div class="type-cards">
                        <button
                            v-for="item in TYPE_CARDS"
                            :key="item.key"
                            type="button"
                            class="type-card"
                            :class="{ on: form.ui_type === item.key }"
                            @click="form.ui_type = item.key">
                            <div class="flex items-center gap-2">
                                <span class="dot"></span>
                                <span class="text-sm font-semibold text-[#303133]">{{ item.name }}</span>
                            </div>
                            <p class="text-xs text-[#909399] mt-1">{{ item.desc }}</p>
                        </button>
                    </div>
                    <div class="mt-4">
                        <div class="text-sm text-[#606266] mb-2">加入后落到</div>
                        <div class="flex flex-wrap gap-2">
                            <el-check-tag
                                v-for="item in LIBRARY_TYPES"
                                :key="item.key"
                                :checked="libraryKey === item.key"
                                @change="libraryKey = item.key">
                                {{ item.label }}
                            </el-check-tag>
                        </div>
                    </div>
                </section>

                <section class="fs">
                    <div class="fs-h">
                        <span class="step-n">2</span>
                        写正文
                        <el-button link type="primary" class="ml-auto !text-xs !font-normal" @click="fillExample">
                            填入示例
                        </el-button>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <button
                            v-for="item in PLACEHOLDERS"
                            :key="item.k"
                            type="button"
                            class="var-chip"
                            :title="`取客户人设里的 ${item.from}`"
                            @click="insertPlaceholder(item.k)">
                            <b>{{ '{' + item.k + '}' }}</b>
                            <span>{{ item.label }}</span>
                        </button>
                    </div>
                    <textarea
                        ref="textareaRef"
                        v-model="form.content"
                        class="text-area"
                        rows="8"
                        maxlength="1200"
                        :placeholder="currentCard.ph"></textarea>
                    <div class="flex items-start gap-2">
                        <p v-if="showEmptyError" class="err-t">正文还没写，客户那边没东西可挑</p>
                        <span class="ml-auto text-xs text-[#C0C4CC] mt-1">{{ form.content.length }} / 1200</span>
                    </div>
                    <div v-if="warnMissing.length || warnMixed.length" class="warn">
                        <div v-if="warnMissing.length">
                            <span v-for="token in warnMissing" :key="token" class="ph bad">{{ token }}</span>
                            人设没有这一项，同步时会删掉。
                        </div>
                        <div v-if="warnMixed.length">
                            <span v-for="token in warnMixed" :key="token" class="ph bad">{{ token }}</span>
                            括号写法不对，同步时会删掉。
                        </div>
                    </div>
                </section>

                <section class="fs">
                    <div class="fs-h">
                        <span class="step-n">3</span>
                        标题和行业
                        <span class="opt">选填</span>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="f-label">标题</label>
                            <el-input v-model="form.title" maxlength="30" :placeholder="showAutoTitle ? displayTitle : '留空取正文前 16 字'" />
                            <p v-if="titleLooksJunk && showAutoTitle" class="f-hint text-[#E6A23C]">正文几乎都是占位符，建议手填标题</p>
                        </div>
                        <div>
                            <label class="f-label">行业标签</label>
                            <el-input v-model="form.industry" maxlength="16" placeholder="空=不显示" />
                        </div>
                    </div>
                    <div class="mt-3 max-w-[240px]">
                        <label class="f-label">排序</label>
                        <el-input-number v-model="form.sort" :min="0" class="!w-full" />
                    </div>
                </section>
            </div>

            <aside class="dw-prev">
                <div class="text-sm font-semibold text-[#303133] mb-3">预览</div>
                <div class="mb-4">
                    <div class="text-xs text-[#909399] mb-2">公共池</div>
                    <div class="cx-card cx-pub">
                        <div class="flex items-start gap-2.5">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="cx-title">{{ displayTitle || '未命名' }}</p>
                                    <span v-if="form.industry" class="cx-ind">{{ form.industry }}</span>
                                </div>
                                <p class="cx-text" :class="{ dim: !form.content }">{{ publicPreviewText }}</p>
                            </div>
                            <span class="cx-circle"></span>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-[#909399] mb-2">加入后</div>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <button
                            v-for="item in SAMPLES"
                            :key="item.id"
                            type="button"
                            class="pill"
                            :class="{ on: sampleId === item.id }"
                            @click="sampleId = item.id">
                            {{ item.label }}
                        </button>
                    </div>
                    <div class="cx-card">
                        <div class="flex items-center gap-1.5 flex-wrap mb-1.5">
                            <span class="cx-tag" :class="form.ui_type === UI_TYPE_FRAME ? 'frame' : 'ready'">
                                {{ currentCard.name }}
                            </span>
                        </div>
                        <p class="cx-my-title">{{ displayTitle || '未命名' }}</p>
                        <p class="cx-my-text" :class="{ dim: !rewrittenText }">{{ rewrittenHint }}</p>
                    </div>
                    <div v-if="uniqueTokens.length" class="rp-map">
                        <span v-for="token in uniqueTokens" :key="token" :class="{ gone: !tokenValue(token) }">
                            <span class="ph" :class="{ bad: !tokenValue(token) }">{{ token }}</span>
                            → {{ tokenValue(token) || '删掉' }}
                        </span>
                    </div>
                    <p v-if="willBump" class="ver up">保存后升一版，已加入的会标「有更新」</p>
                </div>
            </aside>
        </div>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="handleSubmit">
                {{ isEdit ? '保存' : '保存并发布' }}
            </el-button>
        </template>
    </el-drawer>
</template>

<script setup lang="ts">
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { addPublicCopy, updatePublicCopy } from '@/api/ai_application/copywriting'
import {
    ALIAS,
    LIBRARY_TYPES,
    PLACEHOLDER_RE,
    PLACEHOLDERS,
    SAMPLES,
    TYPE_CARDS,
    UI_TYPE_FRAME,
    UI_TYPE_READY,
    autoTitle,
    rewriteText,
    tokenState,
    type PublicCopyRow
} from '../constants'

const props = defineProps<{
    modelValue: boolean
    row: PublicCopyRow | null
    defaultUiType?: number
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'saved'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const isEdit = computed(() => Boolean(props.row?.id))
const saving = ref(false)
const submitted = ref(false)
const sampleId = ref('full')
const textareaRef = ref<HTMLTextAreaElement | null>(null)
const libraryKey = ref<(typeof LIBRARY_TYPES)[number]['key']>('oral')
const form = reactive({
    title: '',
    content: '',
    ui_type: UI_TYPE_READY,
    industry: '',
    sort: 0
})

const currentCard = computed(() => TYPE_CARDS.find((item) => item.key === form.ui_type) || TYPE_CARDS[0])
const currentLibrary = computed(() => LIBRARY_TYPES.find((item) => item.key === libraryKey.value) || LIBRARY_TYPES[1])
const currentSample = computed(() => SAMPLES.find((item) => item.id === sampleId.value) || SAMPLES[0])
const displayTitle = computed(() => form.title.trim() || autoTitle(form.content) || '未命名')
const showAutoTitle = computed(() => !form.title.trim() && Boolean(form.content.trim()))
const titleLooksJunk = computed(() => [...form.content.replace(PLACEHOLDER_RE, '').trim()].length < 6)
const showEmptyError = computed(() => submitted.value && !form.content.trim())
const publicPreviewText = computed(() => {
    const text = form.content.trim()
    if (!text) return '写好正文后显示前 70 字'
    return text.length > 70 ? `${text.slice(0, 70)}…` : text
})
const rewrittenText = computed(() => rewriteText(form.content, currentSample.value.p))
const rewrittenHint = computed(() => {
    if (!form.content.trim()) return '写好正文后这里会按人设改写'
    return rewrittenText.value || '占位符会被删掉，这份会是空的'
})
const contentTokens = computed(() => [...form.content.matchAll(PLACEHOLDER_RE)].map((item) => item[0]))
const uniqueTokens = computed(() => [...new Set(contentTokens.value)])
const warnMissing = computed(() => uniqueTokens.value.filter((token) => tokenState(token) === 'missing'))
const warnMixed = computed(() => uniqueTokens.value.filter((token) => tokenState(token) === 'mixed'))
const willBump = computed(() => {
    if (!props.row?.id) return true
    return (
        (props.row.title || '') !== form.title.trim() ||
        (props.row.content || '') !== form.content ||
        Number(props.row.ui_type || UI_TYPE_READY) !== form.ui_type ||
        Number(props.row.library_type) !== currentLibrary.value.library_type ||
        Number(props.row.driver_type) !== currentLibrary.value.driver_type
    )
})

const tokenValue = (token: string) => {
    if (tokenState(token) !== 'ok') return ''
    const key = ALIAS[token.slice(1, -1)] || token.slice(1, -1)
    const fieldMap: Record<string, string> = {
        姓名: currentSample.value.p.name || '',
        行业: currentSample.value.p.industry || '',
        所在地: currentSample.value.p.city || '',
        主要服务: currentSample.value.p.service || '',
        目标客户: currentSample.value.p.customers || '',
        卖点: currentSample.value.p.selling || ''
    }
    return fieldMap[key] || ''
}

const insertPlaceholder = (key: string) => {
    const token = `{${key}}`
    const el = textareaRef.value
    if (!el) {
        form.content += token
        return
    }
    const start = el.selectionStart ?? form.content.length
    const end = el.selectionEnd ?? start
    form.content = `${form.content.slice(0, start)}${token}${form.content.slice(end)}`
    nextTick(() => {
        el.focus()
        const pos = start + token.length
        el.setSelectionRange(pos, pos)
    })
}

const fillExample = () => {
    form.content = currentCard.value.example
}

const resetForm = () => {
    submitted.value = false
    sampleId.value = 'full'
    const row = props.row
    if (row?.id) {
        const matched = LIBRARY_TYPES.find(
            (item) => item.library_type === Number(row.library_type) && item.driver_type === Number(row.driver_type)
        )
        libraryKey.value = matched?.key || 'oral'
        form.title = row.title || ''
        form.content = row.content || ''
        form.ui_type = Number(row.ui_type) === UI_TYPE_FRAME ? UI_TYPE_FRAME : UI_TYPE_READY
        form.industry = row.industry || ''
        form.sort = Number(row.sort || 0)
        return
    }
    libraryKey.value = 'oral'
    form.title = ''
    form.content = ''
    form.ui_type = Number(props.defaultUiType) === UI_TYPE_FRAME ? UI_TYPE_FRAME : UI_TYPE_READY
    form.industry = ''
    form.sort = 0
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) resetForm()
    }
)

const handleClose = () => {
    emit('update:modelValue', false)
}

const handleSubmit = async () => {
    submitted.value = true
    if (!form.content.trim()) return
    saving.value = true
    try {
        const payload = {
            title: form.title,
            content: form.content,
            ui_type: form.ui_type,
            library_type: currentLibrary.value.library_type,
            driver_type: currentLibrary.value.driver_type,
            industry: form.industry,
            sort: form.sort
        }
        if (props.row?.id) {
            await updatePublicCopy({ id: props.row.id, ...payload })
        } else {
            await addPublicCopy(payload)
        }
        emit('saved')
        visible.value = false
    } finally {
        saving.value = false
    }
}
</script>

<style lang="scss" scoped>
.dw-body {
    @apply flex min-h-0 h-full;
}
.dw-form {
    @apply flex-1 min-w-0 overflow-y-auto pr-5;
}
.dw-prev {
    @apply w-[380px] flex-shrink-0 overflow-y-auto bg-[#F4F5F7] border-l border-[#EBEEF5] pl-4 pr-1;
}
.fs {
    @apply pb-5 mb-5 border-b border-[#F2F3F5];
    &:last-child {
        @apply border-b-0 mb-0 pb-0;
    }
}
.fs-h {
    @apply flex items-center gap-2 text-[15px] font-semibold text-[#303133] mb-2.5;
}
.step-n {
    @apply w-[22px] h-[22px] rounded-full bg-primary text-white text-xs flex items-center justify-center flex-shrink-0;
}
.opt {
    @apply text-xs font-normal text-[#909399];
}
.f-hint {
    @apply text-xs text-[#909399] mt-1.5 leading-5;
}
.f-label {
    @apply block text-sm text-[#606266] mb-1.5;
}
.err-t {
    @apply text-xs text-danger mt-1;
}
.type-cards {
    @apply grid gap-2.5 md:grid-cols-2;
}
.type-card {
    @apply text-left rounded-md border-[1.5px] border-solid border-[#DCDFE6] bg-white px-3.5 py-3;
}
.type-card.on {
    @apply border-primary bg-[#F0F7FF];
    box-shadow: 0 0 0 3px rgba(64, 158, 255, 0.12);
}
.dot {
    @apply w-4 h-4 rounded-full border-[1.5px] border-solid border-[#C0C4CC] bg-white flex items-center justify-center;
}
.type-card.on .dot {
    @apply border-primary bg-primary;
    &::after {
        content: '';
        @apply w-1.5 h-1.5 rounded-full bg-white;
    }
}
.type-ex {
    @apply mt-2 text-xs text-[#909399] leading-5 rounded bg-[#F7F9FC] px-2 py-1.5;
    :deep(.ph) {
        @apply inline-block px-1 mx-px rounded bg-[#ECF5FF] text-primary border border-solid border-[#D9ECFF];
    }
}
.type-card.on .type-ex {
    @apply bg-white;
}
.var-chip {
    @apply inline-flex items-center gap-1 h-[26px] px-2.5 rounded-full bg-[#F0F6FF] text-primary text-xs border border-solid border-[#D9ECFF];
    span {
        @apply text-[#909399];
    }
}
.text-area {
    @apply w-full min-h-[180px] px-2.5 py-2 rounded border border-solid border-[#DCDFE6] text-sm text-[#606266] leading-7;
    &:focus {
        @apply border-primary outline-none;
    }
}
.warn {
    @apply mt-2 rounded bg-[#FDF6EC] border border-solid border-[#FAECD8] text-[#B88230] px-2.5 py-2 text-xs leading-6;
}
.ph {
    @apply inline-block px-1 mx-px rounded bg-[#ECF5FF] text-primary border border-solid border-[#D9ECFF] text-xs;
}
.ph.bad {
    @apply bg-[#FDF6EC] text-[#E6A23C] border-[#FAECD8];
}
.more-set {
    @apply mt-3.5 border-0;
}
.cx-card {
    @apply bg-white rounded-2xl p-3.5;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}
.cx-pub {
    @apply border-[1.5px] border-solid border-[#F0F0F0];
}
.cx-title,
.cx-my-title {
    @apply text-[13px] font-bold text-[#1D2129] m-0 break-all;
}
.cx-ind {
    @apply text-[10px] px-1.5 py-0.5 rounded-md text-[#2F73F6] bg-[#EBF2FF];
}
.cx-text,
.cx-my-text {
    @apply text-xs text-[#4E5969] leading-5 mt-1.5 break-all whitespace-pre-wrap;
}
.dim {
    @apply text-[#C0C4CC];
}
.cx-circle {
    @apply w-5 h-5 rounded-full border-[1.5px] border-solid border-[#D8DDE6] bg-white flex-shrink-0 mt-0.5;
}
.cx-tag {
    @apply text-[10px] font-bold px-1.5 py-0.5 rounded-md;
}
.cx-tag.ready {
    @apply text-[#2F73F6] bg-[#EBF2FF];
}
.cx-tag.frame {
    @apply text-[#7B61FF] bg-[#F3EEFF];
}
.cx-btns {
    @apply flex gap-2 mt-3 pt-3 border-t border-solid border-[#F2F4F8] text-xs text-[#4E5969];
    span {
        @apply flex-1 py-2 rounded-lg bg-[#F7F8FA] text-center;
    }
    .rewrite {
        @apply bg-[#EBF2FF] text-[#2F73F6];
    }
}
.pill {
    @apply rounded-full px-2.5 py-1 text-xs bg-white border border-solid border-[#E4E7ED] text-[#606266];
    small {
        @apply text-[#A8ABB2] ml-1;
    }
}
.pill.on {
    @apply bg-[#2F73F6] border-[#2F73F6] text-white;
    small {
        color: rgba(255, 255, 255, 0.8);
    }
}
.rp-map {
    @apply flex flex-wrap gap-x-2.5 gap-y-1 mt-2 text-[11px] text-[#606266] leading-5;
}
.rp-map .gone {
    @apply text-[#E6A23C];
}
.pv-cap {
    @apply text-[11px] text-[#909399] leading-5 mt-2;
}
.ver {
    @apply rounded-md px-2.5 py-2 text-xs leading-5 mb-3;
}
.ver.up {
    @apply bg-[#FDF6EC] text-[#B88230] border border-solid border-[#FAECD8];
}
.ver.same {
    @apply bg-white text-[#909399] border border-solid border-[#EBEEF5];
}
.tl {
    @apply m-0 pl-0 list-none;
    li {
        @apply relative pl-5 pb-3 text-xs text-[#606266] leading-5;
        &::before {
            content: '';
            @apply absolute left-0.5 top-1 w-2.5 h-2.5 rounded-full bg-white border-2 border-solid border-primary box-border;
        }
        &:last-child {
            @apply pb-0;
        }
    }
}
</style>
