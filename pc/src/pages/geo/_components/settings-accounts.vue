<template>
    <div v-spin="{ show: contentLoading, text: '加载中...' }">
        <div class="bg-white rounded-xl border border-br overflow-hidden">
            <div class="px-6 py-5 border-b border-[#F1F5F9]">
                <div class="text-sm font-semibold text-slate-900">授权账号</div>
                <div class="text-xs text-slate-500 mt-0.5">
                    投稿时用你自己的账号经官方接口直发。点「去授权」，按弹窗步骤填写凭据即可。
                </div>
            </div>

            <div v-if="okPlatforms.length" class="acc-head acc-row text-xs text-slate-500">
                <span>平台</span>
                <span>类型</span>
                <span>状态</span>
                <span class="text-right">操作</span>
            </div>

            <template v-if="authorizedPlatforms.length">
                <div class="acc-group">已授权 {{ authorizedPlatforms.length }}</div>
                <div
                    v-for="p in authorizedPlatforms"
                    :key="p.platform"
                    class="acc-row acc-item">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-slate-800 truncate">{{ p.label }}</div>
                        <div v-if="p.account_name" class="text-xs text-slate-500 truncate mt-0.5">{{ p.account_name }}</div>
                    </div>
                    <div class="acc-forms">
                        <span v-for="f in formList(p)" :key="f" class="acc-chip">{{ f }}</span>
                    </div>
                    <div class="min-w-0 flex items-center gap-2">
                        <span class="acc-chip shrink-0" :class="statusChipClass(p)">{{ statusText(p) }}</span>
                        <span v-if="p.last_check" class="text-xs truncate" :class="checkClass(p)" :title="p.last_check">{{ p.last_check }}</span>
                    </div>
                    <div class="acc-actions">
                        <ElSwitch :model-value="!!p.enabled" :loading="toggling === p.account_id" @change="toggle(p)" />
                        <div class="acc-btns">
                            <button type="button" class="acc-btn" :disabled="checking === p.account_id" @click="check(p)">
                                {{ checking === p.account_id ? '检测中' : '检测' }}
                            </button>
                            <button type="button" class="acc-btn" @click="openAuth(p)">编辑</button>
                            <button type="button" class="acc-btn acc-btn--danger" @click="remove(p)">解除</button>
                        </div>
                    </div>
                </div>
            </template>

            <template v-if="pendingPlatforms.length">
                <div class="acc-group">尚未授权 {{ pendingPlatforms.length }}</div>
                <div
                    v-for="p in pendingPlatforms"
                    :key="p.platform"
                    class="acc-row acc-item">
                    <div class="min-w-0 text-sm font-semibold text-slate-800 truncate">{{ p.label }}</div>
                    <div class="acc-forms">
                        <span v-for="f in formList(p)" :key="f" class="acc-chip">{{ f }}</span>
                    </div>
                    <div>
                        <span class="acc-chip" :class="statusChipClass(p)">{{ statusText(p) }}</span>
                    </div>
                    <div class="acc-actions">
                        <ElButton type="primary" class="!h-8 !px-3.5 !rounded-lg" @click="openAuth(p)">去授权</ElButton>
                    </div>
                </div>
            </template>

            <div v-if="!okPlatforms.length && !contentLoading" class="py-10">
                <GeoEmpty description="暂无可授权平台" />
            </div>

            <div v-if="phonePlatforms.length" class="px-6 py-4 space-y-2.5">
                <div v-if="phonePlatforms.length" class="flex items-start gap-3 text-xs leading-6">
                    <span class="shrink-0 w-[72px] text-slate-500">AI 手机</span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap gap-x-3 text-slate-700">
                            <ElTooltip v-for="p in phonePlatforms" :key="p.platform" :content="p.tip" placement="top">
                                <span class="cursor-default">{{ p.label }}</span>
                            </ElTooltip>
                        </div>
                        <div class="text-slate-500">无需在此授权，到 AI 手机绑定账号后投稿即可</div>
                    </div>
                </div>
            </div>
        </div>

        <GeoDialog
            v-model="showAuth"
            layout="panel"
            :title="`授权 ${cur?.label || ''}`"
            desc="按左侧步骤拿到凭据，填到右侧即可完成授权"
            width="820px"
            confirm-text="保存并检测"
            :confirm-loading="saving"
            @confirm="save">
            <div v-if="cur" class="grid grid-cols-2 gap-6">
                <div>
                    <div class="font-bold text-slate-800 mb-2">怎么拿到凭据</div>
                    <div v-if="cur.need" class="rounded-xl bg-[#fff8e6] text-amber-800 text-xs p-3 mb-3">
                        前置条件:{{ cur.need }}
                    </div>
                    <ol v-if="cur.steps?.length" class="space-y-2.5">
                        <li v-for="(s, i) in cur.steps" :key="i" class="flex gap-2.5 text-sm text-slate-600 leading-relaxed">
                            <span class="step-no">{{ i + 1 }}</span>
                            <span>{{ s }}</span>
                        </li>
                    </ol>
                    <div v-else class="text-slate-500 text-sm">{{ cur.tip }}</div>
                    <ElButton v-if="cur.doc" size="small" class="!rounded-lg mt-4" @click="openDoc(cur.doc)">
                        去 {{ cur.label }} 开发者后台 ↗
                    </ElButton>
                </div>
                <div class="space-y-3">
                    <div class="font-bold text-slate-800 mb-2">填写凭据</div>
                    <div>
                        <div class="text-slate-500 text-sm mb-1">账号备注名</div>
                        <ElInput v-model="form.name" :placeholder="cur.label" />
                        <div class="text-slate-500 text-xs mt-1">只是给你自己看的名字,随便填</div>
                    </div>
                    <div v-for="f in cur.fields" :key="f.key">
                        <div class="text-slate-500 text-sm mb-1">
                            {{ f.label }}
                            <span v-if="fieldSaved(f.key)" class="text-emerald-600 text-xs ml-1">已保存</span>
                        </div>
                        <ElInput v-model="form.credentials[f.key]" :type="f.secret ? 'password' : 'text'" show-password-icon
                            :placeholder="fieldSaved(f.key) ? '留空表示不修改' : (f.placeholder || `请输入${f.label}`)" />
                    </div>
                    <ElAlert v-if="!cur.can_publish" type="warning" :closable="false" class="!rounded-xl"
                        title="该平台的自动发布通道尚未打通,凭据先托管保存;通道开通后无需重新授权即可生效" />
                    <div v-if="checkMsg" class="text-sm rounded-xl p-3" :class="checkOk ? 'bg-[#f0fdf4] text-emerald-700' : 'bg-[#fef2f2] text-rose-600'">
                        {{ checkMsg }}
                    </div>
                </div>
            </div>
        </GeoDialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import GeoDialog from './geo-dialog.vue'
import { geoConfirm } from '../_composables/geo-confirm'
import { geoAuthPlatforms, geoAuthAccountSave, geoAuthAccountToggle, geoAuthAccountDelete, geoAuthAccountCheck } from '@/api/geo'

defineProps<{ pid: number; info: any }>()
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

const platforms = ref<any[]>([])
const contentLoading = ref(false)
const okPlatforms = computed(() => platforms.value.filter((p) => p.status === 'ok' || p.status === 'limited'))
const authorizedPlatforms = computed(() => okPlatforms.value.filter((p) => p.authorized))
const pendingPlatforms = computed(() => okPlatforms.value.filter((p) => !p.authorized))
const phonePlatforms = computed(() => platforms.value.filter((p) => p.status === 'aiphone'))

const formList = (p: any) => {
    const list = (p.forms || []).map((f: string) => (f === 'video' ? '视频' : '图文'))
    return list.length ? list : ['—']
}

const statusText = (p: any) => {
    if (p.authorized && !p.can_publish) return '凭据托管'
    if (p.authorized && p.enabled) return '直发中'
    if (p.authorized) return '已暂停'
    if (p.status === 'limited') return '能力受限'
    return '未授权'
}

const statusChipClass = (p: any) => {
    if (p.last_check && String(p.last_check).startsWith('✗')) return 'acc-chip--danger'
    if (p.authorized && p.enabled) return 'acc-chip--ok'
    if (p.authorized && !p.can_publish) return 'acc-chip--mute'
    if (p.authorized) return 'acc-chip--mute'
    if (p.status === 'limited') return 'acc-chip--warn'
    return 'acc-chip--mute'
}

const checkClass = (p: any) => (String(p.last_check || '').startsWith('✗') ? 'text-rose-600' : 'text-slate-500')

const load = async (silent = false) => {
    if (!silent) contentLoading.value = true
    try { platforms.value = (await geoAuthPlatforms()) || [] } catch (e) { ElMessage.error(errText(e)) }
    finally { if (!silent) contentLoading.value = false }
}
onMounted(() => load())

const showAuth = ref(false)
const cur = ref<any>(null)
const saving = ref(false)
const form = reactive<{ name: string; credentials: Record<string, string> }>({ name: '', credentials: {} })

const checkMsg = ref('')
const checkOk = ref(false)
const openDoc = (url: string) => window.open(url, '_blank', 'noopener')
const fieldSaved = (key: string) => !!(cur.value?.authorized && cur.value?.has_fields?.[key])

const openAuth = (p: any) => {
    cur.value = p
    form.name = p.account_name || ''
    form.credentials = {}
    checkMsg.value = ''
    showAuth.value = true
}

const save = async () => {
    const cred: Record<string, string> = {}
    for (const f of cur.value?.fields || []) {
        const v = String(form.credentials[f.key] || '').trim()
        if (v) cred[f.key] = v
        else if (!fieldSaved(f.key)) return ElMessage.warning(`请输入${f.label}`)
    }
    saving.value = true
    checkMsg.value = ''
    try {
        const res: any = await geoAuthAccountSave({ platform: cur.value.platform, name: form.name, credentials: cred })
        const id = Number(res?.id || 0)
        if (!id) { ElMessage.success('已保存授权'); showAuth.value = false; load(true); return }
        try {
            const c: any = await geoAuthAccountCheck(id)
            checkOk.value = !!c?.ok
            checkMsg.value = (c?.ok ? '✓ ' : '✗ ') + (c?.msg || '')
            if (c?.ok) {
                ElMessage.success(c.msg || '已保存授权')
                showAuth.value = false
            } else {
                ElMessage.warning('凭据已保存,但检测未通过,请对照左侧步骤核对')
            }
        } catch (e) {
            ElMessage.success('已保存授权(检测未能完成,可在列表点「检测」重试)')
            showAuth.value = false
        }
        load(true)
    } catch (e) { ElMessage.error(errText(e)) } finally { saving.value = false }
}

const toggling = ref(0)
const toggle = async (p: any) => {
    toggling.value = p.account_id
    try { await geoAuthAccountToggle(p.account_id); load(true) } catch (e) { ElMessage.error(errText(e)) } finally { toggling.value = 0 }
}

const checking = ref(0)
const check = async (p: any) => {
    checking.value = p.account_id
    try {
        const res: any = await geoAuthAccountCheck(p.account_id)
        res?.ok ? ElMessage.success(res.msg) : ElMessage.warning(res?.msg || '检测未通过')
        load(true)
    } catch (e) { ElMessage.error(errText(e)) } finally { checking.value = 0 }
}

const remove = async (p: any) => {
    // 媒体代发已下线:所有直发平台解除后都无法投稿,需重新授权
    const after = `解除后媒体库里的「${p.label}」将无法投稿,重新授权后恢复。`
    try {
        await geoConfirm({
            title: '解除授权',
            message: `确定解除「${p.label}」的授权？`,
            confirmText: '解除',
            tone: 'warning',
            impacts: [after]
        })
    } catch { return }
    try { await geoAuthAccountDelete(p.account_id); ElMessage.success('已解除授权'); load(true) } catch (e) { ElMessage.error(errText(e)) }
}
</script>

<style lang="scss" scoped>
.step-no { @apply shrink-0 w-5 h-5 rounded-full bg-primary text-white text-xs font-bold grid place-items-center; }

.acc-row {
    display: grid;
    grid-template-columns: minmax(168px, 1.1fr) 128px minmax(220px, 1.3fr) 252px;
    column-gap: 16px;
    align-items: center;
    padding-left: 24px;
    padding-right: 24px;
}

.acc-head {
    @apply py-2.5 border-b border-[#F1F5F9];
}

.acc-group {
    @apply px-6 py-2 text-xs font-medium text-slate-500 bg-[#FAFBFC] border-b border-[#F1F5F9];
}

.acc-item {
    @apply py-3.5 border-b border-[#F1F5F9] transition-colors duration-150;
    &:hover { background: #FAFBFC; }
}

.acc-forms {
    @apply flex flex-wrap gap-1;
}

.acc-chip {
    @apply inline-flex items-center h-6 px-2 rounded-md text-xs text-slate-600 bg-[#F1F5F9];
}
.acc-chip--ok { @apply text-emerald-700 bg-emerald-50; }
.acc-chip--warn { @apply text-amber-700 bg-amber-50; }
.acc-chip--danger { @apply text-rose-700 bg-rose-50; }
.acc-chip--mute { @apply text-slate-500 bg-[#F1F5F9]; }

.acc-actions {
    @apply flex items-center justify-end gap-3;
}

.acc-btns {
    @apply inline-flex items-center overflow-hidden;
    height: 32px;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
}

.acc-btn {
    @apply h-8 px-3 text-xs text-slate-600 bg-white;
    border-right: 1px solid #E2E8F0;
    transition: background-color 150ms ease, color 150ms ease;
    &:last-child { border-right: none; }
    &:hover:not(:disabled) { background: #F8FAFC; color: var(--el-color-primary); }
    &:disabled { color: #94a3b8; cursor: not-allowed; }
    &--danger:hover:not(:disabled) { color: var(--el-color-danger); background: #FEF2F2; }
}

@media (prefers-reduced-motion: reduce) {
    .acc-item,
    .acc-btn { transition: none; }
}
</style>
