<template>
    <ElDialog
        v-model="visible"
        :title="headerTitle"
        :show-close="false"
        :close-on-click-modal="true"
        append-to-body
        width="480px"
        align-center
        class="member-plan-dialog"
    >
        <template #header>
            <div class="mp-header">
                <button
                    v-if="activeView !== MembershipView.OVERVIEW"
                    type="button"
                    class="mp-back"
                    @click="activeView = MembershipView.OVERVIEW"
                >
                    <ArrowLeft class="h-4 w-4" />
                    返回
                </button>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-tx-primary">
                        {{ headerTitle }}
                    </h2>
                    <p
                        v-if="activeView === MembershipView.OVERVIEW"
                        class="mt-1 text-xs text-slate-600"
                    >
                        查看当前套餐及资源使用情况
                    </p>
                </div>
                <button
                    type="button"
                    class="mp-close"
                    aria-label="关闭会员弹窗"
                    @click="visible = false"
                >
                    <Close class="h-4 w-4" />
                </button>
            </div>
        </template>

        <div class="mp-body">
            <template v-if="activeView === MembershipView.OVERVIEW">
                <div
                    class="membership-summary"
                    :class="{ 'is-free': !data?.is_member }"
                >
                    <div class="flex items-start gap-3">
                        <div class="membership-mark">
                            <Tickets v-if="data?.is_member" class="h-5 w-5" />
                            <User v-else class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h3
                                    class="min-w-0 truncate text-lg font-bold text-tx-primary"
                                    :title="planName"
                                >
                                    {{ planName }}
                                </h3>
                                <span
                                    class="plan-chip"
                                    :class="{ 'is-member': data?.is_member }"
                                >
                                    {{
                                        data?.is_member ? '已开通' : '当前套餐'
                                    }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs leading-5 text-slate-600">
                                {{ planDescription }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex items-center justify-between gap-4 border-t border-br-light pt-3 text-xs"
                    >
                        <span class="text-slate-600">有效期</span>
                        <span class="font-medium text-tx-primary">{{
                            expiryText
                        }}</span>
                    </div>
                </div>

                <div class="mb-3 mt-5 flex items-end justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-tx-primary">
                            资源用量
                        </h3>
                        <p class="mt-1 text-xs text-slate-600">
                            额度以当前套餐配置为准
                        </p>
                    </div>
                    <span class="shrink-0 text-xs text-slate-600"
                        >已用 / 总量</span
                    >
                </div>
                <div v-if="usageItems.length" class="usage-list">
                    <div
                        v-for="item in usageItems"
                        :key="item.key"
                        class="usage-item"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-tx-primary"
                                >
                                    {{ item.label }}
                                </p>
                                <p
                                    class="usage-state"
                                    :class="{ 'is-over': item.overLimit }"
                                >
                                    {{ item.stateText }}
                                </p>
                            </div>
                            <p class="shrink-0 text-xs text-slate-600">
                                <b
                                    class="mr-1 text-base font-semibold text-tx-primary"
                                    >{{ item.used }}</b
                                >
                                / {{ item.limitText }}
                            </p>
                        </div>
                        <div
                            v-if="item.limit > 0"
                            class="usage-bar"
                            role="meter"
                            :aria-label="item.label"
                            :aria-valuemin="0"
                            :aria-valuemax="item.limit"
                            :aria-valuenow="
                                Math.min(Math.max(item.used, 0), item.limit)
                            "
                            :aria-valuetext="
                                item.used +
                                ' / ' +
                                item.limit +
                                '，' +
                                item.stateText
                            "
                        >
                            <div
                                class="usage-bar-fill"
                                :class="{ 'is-over': item.overLimit }"
                                :style="{ width: item.pct + '%' }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-slate-600">
                    暂无用量信息，请关闭后重新打开
                </p>
            </template>

            <template v-else-if="activeView === MembershipView.CONTACT">
                <p class="redeem-tip">{{ contactHint }}</p>
                <div class="contact-qr-wrap">
                    <img
                        v-if="contactQrcode"
                        :src="contactQrcode"
                        class="contact-qr"
                        alt="联系二维码"
                    />
                    <p v-else class="py-16 text-sm text-slate-600">
                        暂无二维码，请稍后再试
                    </p>
                </div>
            </template>

            <template v-else>
                <label
                    for="membership-redeem-code"
                    class="text-sm font-semibold text-tx-primary"
                    >输入兑换码</label
                >
                <p class="mt-2 text-xs leading-5 text-slate-600">
                    输入兑换码，即可解锁对应套餐权益
                </p>
                <input
                    id="membership-redeem-code"
                    v-model="redeemCode"
                    class="redeem-input"
                    type="text"
                    maxlength="64"
                    placeholder="输入兑换码，如 VIP-XXXX-XXXX"
                    @keydown.enter="doRedeem"
                />
                <div class="redeem-warn">
                    <Warning class="mt-0.5 h-4 w-4 shrink-0" />
                    <span
                        >兑换码一码一用，提交后立即生效；已有会员时新订阅会替换当前等级和有效期，请确认无误后再兑换。</span
                    >
                </div>
            </template>
        </div>

        <template #footer>
            <div class="mp-foot">
                <template v-if="activeView === MembershipView.OVERVIEW">
                    <button
                        v-if="data?.is_member"
                        type="button"
                        class="mp-cta"
                        @click="openRedeem"
                    >
                        <Tickets class="h-4 w-4" />
                        使用兑换码升级
                    </button>
                    <template v-else>
                        <button
                            type="button"
                            class="mp-cta"
                            @click="openContact"
                        >
                            <Service class="h-4 w-4" />
                            {{ hasSuperior ? '联系上级开通' : '联系客服开通' }}
                        </button>
                        <button
                            type="button"
                            class="mp-close-btn"
                            @click="visible = false"
                        >
                            暂不开通
                        </button>
                    </template>
                    <p v-if="data?.is_member" class="mp-hint">
                        兑换码可向客服或代理商获取
                    </p>
                </template>
                <button
                    v-else-if="activeView === MembershipView.CONTACT"
                    type="button"
                    class="mp-cta"
                    :disabled="!contactQrcode"
                    @click="downloadContactQrcode"
                >
                    保存二维码 / 添加微信
                </button>
                <button
                    v-else
                    type="button"
                    class="mp-cta"
                    :disabled="!isRedeemAvailable"
                    :aria-busy="redeemLoading"
                    @click="doRedeem"
                >
                    {{ redeemLoading ? '兑换中...' : '立即兑换' }}
                </button>
            </div>
        </template>
    </ElDialog>
</template>

<script setup lang="ts">
import {
    ArrowLeft,
    Close,
    Service,
    Tickets,
    User,
    Warning
} from '@element-plus/icons-vue'
import { useRedeemCode } from '@/api/recharge'
import { getAgentUserParentQrcode } from '@/api/user'
import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'
import feedback from '@/utils/feedback'
import { downloadFile } from '@/utils/util'

enum MembershipView {
    OVERVIEW = 'overview',
    REDEEM = 'redeem',
    CONTACT = 'contact'
}

const visible = ref(false)
const data = ref<any>(null)
const activeView = ref<MembershipView>(MembershipView.OVERVIEW)

function open(payload: any) {
    data.value = payload ?? null
    activeView.value = MembershipView.OVERVIEW
    visible.value = true
}
defineExpose({ open })

const userStore = useUserStore()
const appStore = useAppStore()

// ─── 普通用户：上级二维码 / 平台客服 ─────────────────
// 以 userInfo.has_parent_agent 区分：有上级联系上级，无上级联系平台客服
const superiorQrcode = ref('')
const platformServiceQrcode = computed(
    () => appStore.getWebsiteConfig?.customer_service?.wx_image || ''
)
const hasSuperior = computed(() => {
    const v = userStore.userInfo?.has_parent_agent
    return v === true || Number(v) === 1
})
const contactQrcode = computed(() =>
    hasSuperior.value
        ? superiorQrcode.value || ''
        : platformServiceQrcode.value || ''
)
const contactHint = computed(() =>
    hasSuperior.value
        ? '扫码添加上级微信，获取会员兑换码'
        : '扫码添加平台客服微信，获取会员兑换码'
)
const headerTitle = computed(() => {
    if (activeView.value === MembershipView.REDEEM) return '兑换码'
    if (activeView.value === MembershipView.CONTACT) {
        return hasSuperior.value ? '联系上级获取兑换码' : '联系客服获取兑换码'
    }
    return '会员与用量'
})

async function openContact() {
    if (hasSuperior.value) {
        try {
            const res = await getAgentUserParentQrcode()
            superiorQrcode.value = res?.qr_code || ''
        } catch {
            superiorQrcode.value = ''
        }
    } else {
        superiorQrcode.value = ''
    }
    if (!contactQrcode.value) {
        feedback.msgError(
            hasSuperior.value ? '暂无上级二维码' : '暂无客服二维码'
        )
        return
    }
    activeView.value = MembershipView.CONTACT
}

function downloadContactQrcode() {
    if (!contactQrcode.value) return
    downloadFile(contactQrcode.value)
}

// ─── 兑换码 ──────────────────────────────────────────
const redeemCode = ref('')
const redeemLoading = ref(false)
const normalizedCode = computed(() => redeemCode.value.trim())
const isRedeemAvailable = computed(
    () => normalizedCode.value.length >= 6 && !redeemLoading.value
)

function openRedeem() {
    redeemCode.value = ''
    activeView.value = MembershipView.REDEEM
}

async function doRedeem() {
    if (!isRedeemAvailable.value) return
    const sn = normalizedCode.value
    redeemLoading.value = true
    try {
        await useRedeemCode({ sn })
        feedback.msgSuccess('兑换成功')
        // getUser 内已 ensureMemberQuota(true)，勿再重复请求
        try {
            await userStore.getUser()
            data.value = appStore.memberQuota
        } catch {}
        activeView.value = MembershipView.OVERVIEW
    } catch (error) {
        feedback.msgError(error || '兑换失败')
    } finally {
        redeemLoading.value = false
    }
}

// ─── 信息展示 ────────────────────────────────────────
const formatDate = (ts: number) => {
    if (!ts) return ''
    const d = new Date(ts * 1000)
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(
        2,
        '0'
    )}-${String(d.getDate()).padStart(2, '0')}`
}

const planName = computed(() => data.value?.level_name || '普通用户')
const expiryText = computed(() => {
    if (data.value?.is_member && data.value?.end_time)
        return `至 ${formatDate(data.value.end_time)}`
    return '尚未开通会员'
})
const planDescription = computed(() =>
    data.value?.is_member
        ? '尊享全部会员权益，畅享 AI 创作能力'
        : '升级会员，解锁更多创作与资源权益'
)

const QUOTA_UNAVAILABLE = -1
const QUOTA_UNLIMITED = 0

const ENTITY_DEFS = [
    { key: 'robots', label: '智能体', limitKey: 'max_robots' },
    { key: 'knowledges', label: '知识库', limitKey: 'max_knowledges' },
    { key: 'personas', label: 'IP 人设', limitKey: 'max_personas' },
    { key: 'mobiles', label: '绑定手机', limitKey: 'max_mobiles' },
    {
        key: 'digital_humans',
        label: '数字人形象',
        limitKey: 'max_digital_humans'
    },
    { key: 'voices', label: '音色克隆', limitKey: 'max_voices' }
]

const usageItems = computed(() => {
    if (!data.value) return []
    return ENTITY_DEFS.map((e) => {
        const limit = Number(data.value.quota?.[e.limitKey] ?? 0)
        const used = Number(data.value.usage?.[e.key] ?? 0)
        const limitText =
            limit === QUOTA_UNAVAILABLE
                ? '禁止'
                : limit === QUOTA_UNLIMITED
                ? '不限'
                : String(limit)
        const overLimit = limit > 0 && used > limit
        const pct =
            limit > 0 ? Math.min(Math.round((used / limit) * 100), 100) : 0
        const stateText =
            limit === QUOTA_UNAVAILABLE
                ? '当前套餐未开放'
                : limit === QUOTA_UNLIMITED
                ? '不限额度'
                : overLimit
                ? `已超出 ${used - limit}`
                : `剩余 ${Math.max(limit - used, 0)}`
        return {
            key: e.key,
            label: e.label,
            limit,
            used,
            limitText,
            pct,
            overLimit,
            stateText
        }
    })
})

watch(visible, (v) => {
    if (!v) {
        activeView.value = MembershipView.OVERVIEW
        redeemCode.value = ''
        superiorQrcode.value = ''
    }
})
</script>

<style scoped lang="scss">
:global(.el-dialog.member-plan-dialog) {
    --text-color-primary: var(--el-text-color-primary);
    @apply flex max-w-[calc(100vw-32px)] flex-col overflow-hidden rounded-[20px] bg-white p-0 shadow-light;
    max-height: calc(100dvh - 48px);
}

:global(.member-plan-dialog > .el-dialog__header),
:global(.member-plan-dialog > .el-dialog__footer) {
    @apply m-0 shrink-0 p-0;
}

:global(.member-plan-dialog > .el-dialog__body) {
    @apply min-h-0 overflow-y-auto p-0;
}

.mp-header {
    @apply flex items-center gap-3 border-b border-br-light px-6 py-5;
}

.mp-back {
    @apply flex shrink-0 items-center gap-1 rounded-lg px-2 py-2 text-xs text-slate-600 transition-colors hover:bg-page hover:text-primary;
}

.mp-close {
    @apply flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-page text-slate-600 transition-colors hover:bg-fill;
}

.mp-body {
    @apply bg-page p-6;
}

.membership-summary {
    @apply rounded-2xl border border-primary-light-8 bg-primary-light-9 p-4;

    &.is-free {
        @apply border-br-light bg-page;
    }
}

.membership-mark {
    @apply flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-white;
}

.plan-chip {
    @apply shrink-0 rounded-full bg-fill px-2 py-1 text-xs font-medium text-slate-600;

    &.is-member {
        @apply bg-primary text-white;
    }
}

.usage-list {
    @apply rounded-2xl bg-white px-4;
}

.usage-item {
    @apply border-b border-br-extra-light py-3 last:border-b-0;
}

.usage-state {
    @apply mt-1 text-xs text-slate-600;

    &.is-over {
        color: var(--el-color-danger-dark-2);
    }
}

.usage-bar {
    @apply mt-2 h-1 overflow-hidden rounded-full bg-fill;
}

.usage-bar-fill {
    @apply h-full rounded-full bg-primary;

    &.is-over {
        @apply bg-danger;
    }
}

.mp-foot {
    @apply border-t border-br-light bg-white px-6 py-4;
}

.mp-cta {
    @apply inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-primary text-sm font-semibold text-white transition-colors hover:bg-primary-dark-2;

    &:disabled {
        @apply cursor-not-allowed bg-fill text-tx-disabled;
    }
}

.mp-hint {
    @apply mt-3 text-center text-xs text-slate-600;
}

.mp-close-btn {
    @apply mt-2 h-9 w-full rounded-lg text-xs text-slate-600 transition-colors hover:bg-page;
}

.mp-cta,
.mp-close,
.mp-back,
.mp-close-btn {
    &:focus-visible {
        outline: 2px solid var(--el-color-primary);
        outline-offset: 3px;
    }
}

.contact-qr-wrap {
    @apply mt-5 flex justify-center;
}

.contact-qr {
    @apply h-[200px] w-[200px] rounded-xl border border-br-light bg-white p-2;
}

.redeem-tip {
    @apply text-center text-sm leading-6 text-slate-600;
}

.redeem-input {
    @apply mt-5 h-12 w-full rounded-xl border border-br bg-white px-4 text-center text-base text-tx-primary outline-none transition-colors focus:border-primary;
    letter-spacing: 1px;

    &::placeholder {
        @apply text-sm tracking-normal text-slate-600;
    }
}

.redeem-warn {
    @apply mt-4 flex items-start gap-2 rounded-xl bg-orange-50 p-4 text-xs leading-5 text-orange-800;
}

@media (prefers-reduced-motion: reduce) {
    .mp-cta,
    .mp-close,
    .mp-back,
    .mp-close-btn,
    .redeem-input {
        transition: none;
    }
}
</style>
