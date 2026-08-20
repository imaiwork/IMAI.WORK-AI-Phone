<template>
    <ElDialog
        v-model="visible"
        :show-close="false"
        :close-on-click-modal="true"
        append-to-body
        width="420px"
        align-center
        custom-class="!rounded-[28px] !p-0 !bg-transparent !shadow-none">
        <div class="mp-card">
            <!-- 头部：返回 / 标题 / 关闭 -->
            <div class="mp-header">
                <div class="mp-header-side">
                    <button
                        v-if="activeView !== MembershipView.OVERVIEW"
                        class="mp-back"
                        @click="activeView = MembershipView.OVERVIEW">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="w-3.5 h-3.5">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                        <span>返回</span>
                    </button>
                </div>
                <span class="mp-title">{{ headerTitle }}</span>
                <div class="mp-header-side justify-end">
                    <button class="mp-close" @click="visible = false">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="w-4 h-4">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- 信息展示屏 -->
            <div v-if="activeView === MembershipView.OVERVIEW" class="mp-body">
                <div class="membership-banner" :class="{ 'is-free': !data?.is_member }">
                    <span class="orb orb-lg" />
                    <span class="orb orb-sm" />
                    <div class="banner-content">
                        <div class="banner-top">
                            <span class="vip-chip" :class="data?.is_member ? 'is-member' : 'is-free'">
                                <svg v-if="data?.is_member" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3">
                                    <path d="M5 16L3 7l5.5 4L12 4l3.5 7L21 7l-2 9H5zm0 2h14v2H5v-2z" />
                                </svg>
                                {{ data?.is_member ? "VIP 会员" : "普通用户" }}
                            </span>
                            <span class="expiry">{{ expiryText }}</span>
                        </div>
                        <div class="plan-name">{{ planName }}</div>
                        <div class="plan-desc">{{ planDescription }}</div>
                    </div>
                </div>

                <div class="usage-head">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="w-4 h-4">
                        <line x1="8" y1="6" x2="21" y2="6" />
                        <line x1="8" y1="12" x2="21" y2="12" />
                        <line x1="8" y1="18" x2="21" y2="18" />
                        <line x1="3" y1="6" x2="3.01" y2="6" />
                        <line x1="3" y1="12" x2="3.01" y2="12" />
                        <line x1="3" y1="18" x2="3.01" y2="18" />
                    </svg>
                    <span>套餐用量</span>
                </div>

                <div class="usage-list">
                    <div v-for="item in usageItems" :key="item.key" class="usage-item">
                        <div class="usage-row">
                            <span class="usage-label">{{ item.label }}</span>
                            <span class="usage-val">
                                <b>{{ item.used }}</b>
                                <span class="usage-slash">/</span>
                                <span :class="{ 'usage-banned': item.limit === -1 }">{{ item.limitText }}</span>
                            </span>
                        </div>
                        <div class="usage-bar">
                            <div
                                class="usage-bar-fill"
                                :class="{ 'is-over': item.overLimit }"
                                :style="{ width: item.pct + '%' }" />
                        </div>
                    </div>
                </div>

                <div class="mp-foot">
                    <!--
                      会员：输入兑换码
                      普通用户有上级：联系上级（隐藏兑换码）
                      普通用户无上级：联系客服（隐藏兑换码）
                    -->
                    <button v-if="data?.is_member" class="mp-cta" @click="openRedeem">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="w-4 h-4">
                            <path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4" />
                            <path d="M4 6v12c0 1.1.9 2 2 2h14v-4" />
                            <path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z" />
                        </svg>
                        输入兑换码升级套餐
                    </button>
                    <template v-else>
                        <button class="mp-cta" @click="openContact">
                            <svg
                                v-if="hasSuperior"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="w-4 h-4">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                            <svg
                                v-else
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                class="w-4 h-4">
                                <path d="M5 16L3 7l5.5 4L12 4l3.5 7L21 7l-2 9H5zm0 2h14v2H5v-2z" />
                            </svg>
                            {{ hasSuperior ? "联系上级" : "联系客服" }}
                        </button>
                        <button class="mp-close-btn" @click="visible = false">关闭</button>
                    </template>
                    <p v-if="data?.is_member" class="mp-hint">兑换码可向客服或代理商获取</p>
                </div>
            </div>

            <!-- 普通用户：上级/客服二维码 -->
            <div v-else-if="activeView === MembershipView.CONTACT" class="mp-body">
                <p class="redeem-tip">{{ contactHint }}</p>
                <div class="contact-qr-wrap">
                    <img v-if="contactQrcode" :src="contactQrcode" class="contact-qr" alt="联系二维码" />
                    <p v-else class="contact-empty">暂无二维码，请稍后再试</p>
                </div>
                <div class="mp-foot">
                    <button
                        class="mp-cta"
                        :class="{ 'is-disabled': !contactQrcode }"
                        :disabled="!contactQrcode"
                        @click="downloadContactQrcode">
                        保存二维码 / 添加微信
                    </button>
                </div>
            </div>

            <!-- 兑换屏 -->
            <div v-else class="mp-body">
                <p class="redeem-tip">输入兑换码，即可解锁对应套餐权益</p>
                <input
                    v-model="redeemCode"
                    class="redeem-input"
                    type="text"
                    maxlength="64"
                    placeholder="输入兑换码，如 VIP-XXXX-XXXX"
                    @keydown.enter="doRedeem" />
                <div class="redeem-warn">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="w-4 h-4 shrink-0 mt-0.5">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <span
                        >兑换码一码一用，提交后立即生效；已有会员时新订阅会替换当前等级和有效期，请确认无误后再兑换。</span
                    >
                </div>

                <div class="mp-foot">
                    <button
                        class="mp-cta"
                        :class="{ 'is-disabled': !isRedeemAvailable }"
                        :disabled="!isRedeemAvailable"
                        @click="doRedeem">
                        {{ redeemLoading ? "兑换中..." : "立即兑换" }}
                    </button>
                </div>
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import { useRedeemCode } from "@/api/recharge";
import { getAgentUserParentQrcode } from "@/api/user";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import feedback from "@/utils/feedback";
import { downloadFile } from "@/utils/util";

enum MembershipView {
    OVERVIEW = "overview",
    REDEEM = "redeem",
    CONTACT = "contact",
}

const visible = ref(false);
const data = ref<any>(null);
const activeView = ref<MembershipView>(MembershipView.OVERVIEW);

function open(payload: any) {
    data.value = payload ?? null;
    activeView.value = MembershipView.OVERVIEW;
    visible.value = true;
}
defineExpose({ open });

const userStore = useUserStore();
const appStore = useAppStore();

// ─── 普通用户：上级二维码 / 平台客服 ─────────────────
// 以 userInfo.has_parent_agent 区分：有上级联系上级，无上级联系平台客服
const superiorQrcode = ref("");
const platformServiceQrcode = computed(
    () => appStore.getWebsiteConfig?.customer_service?.wx_image || "",
);
const hasSuperior = computed(() => {
    const v = userStore.userInfo?.has_parent_agent;
    return v === true || Number(v) === 1;
});
const contactQrcode = computed(() =>
    hasSuperior.value ? superiorQrcode.value || "" : platformServiceQrcode.value || "",
);
const contactHint = computed(() =>
    hasSuperior.value
        ? "扫码添加上级微信，获取会员兑换码"
        : "扫码添加平台客服微信，获取会员兑换码",
);
const headerTitle = computed(() => {
    if (activeView.value === MembershipView.REDEEM) return "兑换码";
    if (activeView.value === MembershipView.CONTACT) {
        return hasSuperior.value ? "联系上级获取兑换码" : "联系客服获取兑换码";
    }
    return "会员订阅";
});

async function openContact() {
    if (hasSuperior.value) {
        try {
            const res = await getAgentUserParentQrcode();
            superiorQrcode.value = res?.qr_code || "";
        } catch {
            superiorQrcode.value = "";
        }
    } else {
        superiorQrcode.value = "";
    }
    if (!contactQrcode.value) {
        feedback.msgError(hasSuperior.value ? "暂无上级二维码" : "暂无客服二维码");
        return;
    }
    activeView.value = MembershipView.CONTACT;
}

function downloadContactQrcode() {
    if (!contactQrcode.value) return;
    downloadFile(contactQrcode.value);
}

// ─── 兑换码 ──────────────────────────────────────────
const redeemCode = ref("");
const redeemLoading = ref(false);
const normalizedCode = computed(() => redeemCode.value.trim());
const isRedeemAvailable = computed(() => normalizedCode.value.length >= 6 && !redeemLoading.value);

function openRedeem() {
    redeemCode.value = "";
    activeView.value = MembershipView.REDEEM;
}

async function doRedeem() {
    if (!isRedeemAvailable.value) return;
    const sn = normalizedCode.value;
    redeemLoading.value = true;
    try {
        await useRedeemCode({ sn });
        feedback.msgSuccess("兑换成功");
        // getUser 内已 ensureMemberQuota(true)，勿再重复请求
        try {
            await userStore.getUser();
            data.value = appStore.memberQuota;
        } catch {}
        activeView.value = MembershipView.OVERVIEW;
    } catch (error) {
        feedback.msgError(error || "兑换失败");
    } finally {
        redeemLoading.value = false;
    }
}

// ─── 信息展示 ────────────────────────────────────────
const formatDate = (ts: number) => {
    if (!ts) return "";
    const d = new Date(ts * 1000);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

const planName = computed(() => data.value?.level_name || "普通用户");
const expiryText = computed(() => {
    if (data.value?.is_member && data.value?.end_time) return `有效期至 ${formatDate(data.value.end_time)}`;
    return "尚未开通会员";
});
const planDescription = computed(() =>
    data.value?.is_member ? "尊享全部会员权益，畅享 AI 创作能力" : "升级会员，解锁更多创作与资源权益",
);

const ENTITY_DEFS = [
    { key: "robots", label: "智能体", limitKey: "max_robots" },
    { key: "knowledges", label: "知识库", limitKey: "max_knowledges" },
    { key: "personas", label: "IP 人设", limitKey: "max_personas" },
    { key: "mobiles", label: "绑定手机", limitKey: "max_mobiles" },
    { key: "digital_humans", label: "数字人形象", limitKey: "max_digital_humans" },
    { key: "voices", label: "音色克隆", limitKey: "max_voices" },
];

const usageItems = computed(() => {
    if (!data.value) return [];
    return ENTITY_DEFS.map((e) => {
        const limit = Number(data.value.quota?.[e.limitKey] ?? 0);
        const used = Number(data.value.usage?.[e.key] ?? 0);
        const limitText = limit === -1 ? "禁止" : limit === 0 ? "不限" : `${limit} 个`;
        const overLimit = limit > 0 && used > limit;
        const pct = limit > 0 ? Math.min(Math.round((used / limit) * 100), 100) : 0;
        return { key: e.key, label: e.label, limit, used, limitText, pct, overLimit };
    });
});

watch(visible, (v) => {
    if (!v) {
        activeView.value = MembershipView.OVERVIEW;
        redeemCode.value = "";
        superiorQrcode.value = "";
    }
});
</script>

<style scoped lang="scss">
.mp-card {
    background: #fff;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
}

.mp-header {
    @apply flex items-center justify-between px-5 h-14 border-b border-slate-100;
    .mp-header-side {
        @apply flex items-center w-16;
    }
    .mp-title {
        @apply text-base font-extrabold text-[#0F172A];
    }
    .mp-back {
        @apply flex items-center gap-1 text-xs font-semibold text-[#475569] transition-colors;
        &:hover {
            @apply text-primary;
        }
    }
    .mp-close {
        @apply flex items-center justify-center w-8 h-8 rounded-full bg-[#F1F5F9] text-[#64748B] transition-colors;
        &:hover {
            @apply bg-[#E2E8F0] text-[#334155];
        }
    }
}

.mp-body {
    @apply px-6 pt-5 pb-6;
}

/* 会员 Banner */
.membership-banner {
    @apply relative overflow-hidden rounded-[20px] p-5;
    background: linear-gradient(135deg, #3b4a8a 0%, #5b6bc8 100%);
    &.is-free {
        background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%);
    }
    .orb {
        @apply pointer-events-none absolute rounded-full;
        background: rgba(255, 255, 255, 0.08);
    }
    .orb-lg {
        @apply w-32 h-32;
        top: -32px;
        right: -28px;
    }
    .orb-sm {
        @apply w-24 h-24;
        bottom: -40px;
        right: 30px;
        background: rgba(255, 255, 255, 0.05);
    }
    .banner-content {
        @apply relative z-10;
    }
    .banner-top {
        @apply flex items-center gap-2.5 mb-3;
    }
    .vip-chip {
        @apply inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold;
        &.is-member {
            @apply text-[#7A4800];
            background: #fcd34d;
        }
        &.is-free {
            @apply text-white;
            background: rgba(255, 255, 255, 0.22);
        }
    }
    .expiry {
        @apply text-[11px];
        color: rgba(255, 255, 255, 0.78);
    }
    .plan-name {
        @apply text-lg font-extrabold text-white truncate;
    }
    .plan-desc {
        @apply mt-1.5 text-xs leading-5;
        color: rgba(255, 255, 255, 0.76);
    }
}

.usage-head {
    @apply flex items-center gap-2 mt-6 mb-4 text-sm font-bold text-[#0F172A];
    svg {
        color: #2563eb;
    }
}

.usage-list {
    @apply flex flex-col gap-3.5;
}
.usage-item {
    .usage-row {
        @apply flex items-center justify-between gap-4 mb-1.5;
        .usage-label {
            @apply text-sm font-semibold text-[#0F172A] truncate;
        }
        .usage-val {
            @apply shrink-0 text-xs text-[#94A3B8];
            b {
                @apply text-sm font-bold text-[#0F172A] mr-0.5;
            }
            .usage-slash {
                @apply mx-0.5;
            }
            .usage-banned {
                @apply text-[#EF4444] font-semibold;
            }
        }
    }
    .usage-bar {
        @apply h-1.5 rounded-full overflow-hidden;
        background: #eef2f8;
        .usage-bar-fill {
            @apply h-full rounded-full bg-primary;
            transition: width 0.4s, background-color 0.4s;
            &.is-over {
                background: #ef4444;
            }
        }
    }
}

/* 底部操作区 */
.mp-foot {
    @apply mt-6;
}
.mp-cta {
    @apply w-full h-12 rounded-2xl inline-flex items-center justify-center gap-2 text-base font-extrabold text-[#7A4800] transition-all;
    background: linear-gradient(135deg, #fcd34d 0%, #f59e0b 100%);
    box-shadow: 0 12px 28px rgba(245, 158, 11, 0.28);
    &:hover {
        @apply -translate-y-0.5;
        filter: brightness(1.03);
    }
    &.is-disabled {
        background: #e5eaf3;
        color: #64748b;
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
    }
}
.mp-hint {
    @apply mt-3 text-center text-[11px] text-[#64748B];
}
.mp-close-btn {
    @apply w-full h-11 mt-3 rounded-2xl text-sm font-semibold text-[#64748B] transition-colors;
    &:hover {
        @apply bg-[#F1F5F9] text-[#334155];
    }
}

.contact-qr-wrap {
    @apply mt-6 flex items-center justify-center;
    .contact-qr {
        @apply w-[200px] h-[200px] rounded-2xl border border-solid border-[#E2E8F0] bg-white p-2;
    }
    .contact-empty {
        @apply py-16 text-sm text-[#94A3B8];
    }
}

/* 兑换屏 */
.redeem-tip {
    @apply text-center text-xs leading-6 text-[#475569] mt-1;
}
.redeem-input {
    @apply w-full h-14 mt-6 rounded-2xl border border-solid border-[#D9E2F0] bg-[#F7F9FC] px-6 text-center text-base text-[#0F172A] outline-none transition-colors;
    letter-spacing: 1px;
    &:focus {
        @apply border-primary bg-white;
    }
    &::placeholder {
        @apply text-[#94A3B8] text-sm tracking-normal;
    }
}
.redeem-warn {
    @apply mt-5 flex items-start gap-2.5 rounded-2xl border border-solid border-[#FED7AA] bg-[#FFF7ED] p-4 text-xs leading-5 text-[#9A3412];
    svg {
        color: #c2410c;
    }
}
</style>
