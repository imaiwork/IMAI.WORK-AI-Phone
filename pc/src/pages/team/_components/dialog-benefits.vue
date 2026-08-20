<template>
    <popup
        ref="popupRef"
        width="820px"
        class="consume-detail-dialog benefits-dialog"
        :show-close="false"
        cancel-button-text=""
        confirm-button-text=""
        footer-class="!p-0"
        header-class="!p-0"
        @close="onPopupClose">
        <div class="benefits">
            <button type="button" class="cd-close" @click="showBenefits = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <!-- 头 -->
            <div class="benefits__head">
                <div class="benefits__badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" class="w-6 h-6">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="benefits__title">套餐权益</h3>
                    <p class="benefits__sub">个人版与企业 OEM 能力对比，快速看清差异</p>
                </div>
            </div>

            <!-- 套餐卡 -->
            <div class="plan-grid">
                <article class="plan-card" :class="{ 'is-active': !isOem }">
                    <div class="plan-card__top">
                        <span class="plan-card__name">个人版</span>
                        <span v-if="!isOem" class="plan-card__tag">当前套餐</span>
                    </div>
                    <div class="plan-card__price">
                        <b>¥0</b>
                        <span>注册即用</span>
                    </div>
                    <p class="plan-card__desc">按算力付费，充值 / 卡密兑换均可</p>
                    <ul class="plan-card__points">
                        <li>全部 AI 功能按算力使用</li>
                        <li>个人账号独立使用</li>
                    </ul>
                </article>

                <article class="plan-card plan-card--oem" :class="{ 'is-active': isOem }">
                    <div class="plan-card__top">
                        <span class="plan-card__name">
                            企业 OEM
                            <span class="plan-card__star" aria-hidden="true">★</span>
                        </span>
                        <span v-if="isOem" class="plan-card__tag">当前套餐</span>
                        <span v-else-if="isPending" class="plan-card__tag is-pending">审核中</span>
                    </div>
                    <div class="plan-card__price">
                        <b>长期有效</b>
                    </div>
                    <p class="plan-card__desc">独立品牌站点 · 自主经营 · 名额由平台授权</p>
                    <ul class="plan-card__points">
                        <li>成员席位 {{ info?.seat_limit ?? "—" }} 个</li>
                        <li>含个人版全部功能 + 品牌经营能力</li>
                    </ul>
                </article>
            </div>

            <!-- 对比 -->
            <div class="compare">
                <div class="compare__head">
                    <span class="compare__col compare__col--feature">权益项</span>
                    <span class="compare__col">个人版</span>
                    <span class="compare__col compare__col--oem">企业 OEM</span>
                </div>
                <div class="compare__body">
                    <section v-for="group in benefitGroups" :key="group.title" class="compare-group">
                        <div class="compare-group__title">{{ group.title }}</div>
                        <div v-for="row in group.rows" :key="row.name" class="compare-row">
                            <div class="compare__col compare__col--feature">{{ row.name }}</div>
                            <div class="compare__col">
                                <span v-if="row.personal === true" class="cell-yes" aria-label="支持">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span v-else-if="row.personal === false" class="cell-no">—</span>
                                <span v-else class="cell-text">{{ row.personal }}</span>
                            </div>
                            <div class="compare__col compare__col--oem">
                                <span v-if="row.oem === true" class="cell-yes is-oem" aria-label="支持">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span v-else-if="row.oem === false" class="cell-no">—</span>
                                <span v-else class="cell-text is-oem">{{ row.oem }}</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="benefits__foot">
                <ElButton
                    type="primary"
                    class="!h-11 !px-8 !rounded-xl !text-[15px] !font-semibold"
                    @click="showBenefits = false">
                    我知道了
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { info: infoCtx } = useTeamContext();
const { info, showBenefits, benefitGroups, oemStatus } = infoCtx;
const { popupRef, onPopupClose } = usePopupBridge(showBenefits);

const isOem = computed(() => Number(oemStatus.value) === 2);
const isPending = computed(() => Number(oemStatus.value) === 1);
</script>

<style lang="scss" scoped>
.benefits {
    position: relative;
    padding: 4px 2px 2px;
}

.benefits__head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-right: 40px;
    margin-bottom: 20px;
}
.benefits__badge {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0065fb, #4f9dff);
    box-shadow: 0 10px 22px -8px rgba(0, 101, 251, 0.5);
}
.benefits__title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}
.benefits__sub {
    margin-top: 4px;
    font-size: 13px;
    color: #94a3b8;
}

.plan-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 18px;
}
.plan-card {
    position: relative;
    border-radius: 16px;
    padding: 18px 18px 16px;
    background: #f8fafc;
    border: 1px solid #e8eef6;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.plan-card--oem {
    background: linear-gradient(165deg, #f5f9ff 0%, #eef5ff 100%);
    border-color: rgba(0, 101, 251, 0.18);
}
.plan-card.is-active {
    border-color: rgba(0, 101, 251, 0.45);
    box-shadow: 0 10px 28px -16px rgba(0, 101, 251, 0.45);
}
.plan-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}
.plan-card__name {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}
.plan-card__star {
    margin-left: 4px;
    color: #f59e0b;
    font-size: 13px;
}
.plan-card__tag {
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    background: #0065fb;
    border-radius: 999px;
    padding: 3px 9px;
    line-height: 1.3;
}
.plan-card__tag.is-pending {
    background: #f59e0b;
}
.plan-card__price {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 6px;
}
.plan-card__price b {
    font-size: 26px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1;
}
.plan-card__price span {
    font-size: 13px;
    color: #94a3b8;
}
.plan-card__desc {
    font-size: 12px;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 12px;
}
.plan-card__points {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding-top: 12px;
    border-top: 1px dashed #e2e8f0;
}
.plan-card__points li {
    position: relative;
    padding-left: 16px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    line-height: 1.4;
}
.plan-card__points li::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0.55em;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #94a3b8;
}
.plan-card--oem .plan-card__points li::before,
.plan-card.is-active .plan-card__points li::before {
    background: #0065fb;
}

.compare {
    border: 1px solid #eef2f7;
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
}
.compare__head {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) 1fr 1fr;
    gap: 8px;
    padding: 12px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #eef2f7;
    position: sticky;
    top: 0;
    z-index: 1;
}
.compare__col {
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    text-align: center;
}
.compare__col--feature {
    text-align: left;
    color: #64748b;
}
.compare__col--oem {
    color: #0065fb;
}
.compare__body {
    max-height: 38vh;
    overflow-y: auto;
}
.compare-group + .compare-group {
    border-top: 1px solid #f1f5f9;
}
.compare-group__title {
    padding: 12px 16px 6px;
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    background: #fff;
}
.compare-row {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) 1fr 1fr;
    gap: 8px;
    padding: 10px 16px;
    align-items: center;
}
.compare-row:hover {
    background: #f8faff;
}
.compare-row .compare__col--feature {
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    line-height: 1.4;
}

.cell-yes {
    display: inline-grid;
    place-items: center;
    width: 22px;
    height: 22px;
    border-radius: 999px;
    color: #0065fb;
    background: rgba(0, 101, 251, 0.08);
}
.cell-yes svg {
    width: 13px;
    height: 13px;
}
.cell-yes.is-oem {
    color: #0065fb;
    background: rgba(0, 101, 251, 0.12);
}
.cell-no {
    color: #cbd5e1;
    font-weight: 600;
    font-size: 14px;
}
.cell-text {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}
.cell-text.is-oem {
    color: #0065fb;
}

.benefits__foot {
    display: flex;
    justify-content: flex-end;
    margin-top: 18px;
}

@media (max-width: 720px) {
    .plan-grid {
        grid-template-columns: 1fr;
    }
}
</style>
