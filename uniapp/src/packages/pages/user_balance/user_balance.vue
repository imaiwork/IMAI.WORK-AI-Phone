<template>
    <view class="h-screen flex flex-col bg-[#F3F5FA]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }"
            title="算力使用记录"
            title-bold>
        </u-navbar>

        <view class="grow min-h-0 flex flex-col">
            <!-- 消耗记录 / 充值记录 -->
            <view class="px-[32rpx] pt-[16rpx] pb-[28rpx]">
                <view class="tab2">
                    <view
                        class="tab2-item"
                        :class="{ 'tab2-item--on': activeTab === BalanceTab.Consume }"
                        @click="handleTab(BalanceTab.Consume)">
                        <u-icon
                            name="integral"
                            size="30"
                            :color="activeTab === BalanceTab.Consume ? '#ffffff' : '#8A93A3'" />
                        <text>消耗记录</text>
                    </view>
                    <view
                        class="tab2-item"
                        :class="{ 'tab2-item--on': activeTab === BalanceTab.Recharge }"
                        @click="handleTab(BalanceTab.Recharge)">
                        <u-icon
                            name="rmb-circle"
                            size="30"
                            :color="activeTab === BalanceTab.Recharge ? '#ffffff' : '#8A93A3'" />
                        <text>充值记录</text>
                    </view>
                </view>
            </view>

            <!-- ══ 消耗：按日 + 大类汇总 ══ -->
            <view v-if="activeTab === BalanceTab.Consume" class="grow min-h-0">
                <scroll-view scroll-y class="h-full w-full">
                    <view class="flex flex-col gap-[28rpx] px-[32rpx] pb-[48rpx]">
                        <!-- 日期导航：前一天 / 选日期 / 后一天 -->
                        <view class="card flex items-center gap-[12rpx] px-[24rpx] py-[20rpx]">
                            <view class="day-btn active:bg-[#E4ECFA]" @click="handleShiftDay(-1)">
                                <u-icon name="arrow-left" size="32" color="#5B6472" />
                            </view>
                            <picker
                                mode="date"
                                :value="consumeDate"
                                :end="today"
                                class="min-w-0 flex-1"
                                @change="handlePickDate">
                                <view class="flex items-center justify-center gap-[12rpx] py-[12rpx]">
                                    <u-icon name="calendar" size="30" color="#2B6EFF" />
                                    <text class="text-[30rpx] font-bold text-[#1A1A1A]">{{ consumeDateLabel }}</text>
                                    <text v-if="isToday" class="text-[24rpx] text-[#9AA3B2]">
                                        {{ fmtDateShort(consumeDate) }}
                                    </text>
                                    <u-icon name="arrow-down" size="22" color="#C0C8D8" />
                                </view>
                            </picker>
                            <view
                                class="day-btn active:bg-[#E4ECFA]"
                                :class="{ 'day-btn--off': isToday }"
                                @click="handleShiftDay(1)">
                                <u-icon name="arrow-right" size="32" color="#5B6472" />
                            </view>
                        </view>

                        <!-- 当日小结 -->
                        <view class="hero hero--consume">
                            <view class="relative z-10 flex items-center justify-between gap-[20rpx]">
                                <text class="hero-label">当日消耗</text>
                                <view class="hero-btn" hover-class="hero-btn--pressed" @click="showPricePopup = true">
                                    <u-icon name="order" size="22" color="#ffffff" />
                                    <text>计费标准</text>
                                    <u-icon name="arrow-right" size="20" color="#ffffff" />
                                </view>
                            </view>
                            <view class="relative z-10 mt-[12rpx] flex items-end justify-between">
                                <view v-if="consumeLoading" class="hero-skeleton h-[64rpx] w-[240rpx]" />
                                <view v-else class="flex items-baseline gap-[10rpx]">
                                    <text class="hero-num">{{ formatWithComma(consumeSummary.total) }}</text>
                                    <text class="hero-unit">算力</text>
                                </view>
                                <view v-if="consumeLoading" class="hero-skeleton h-[28rpx] w-[96rpx]" />
                                <text v-else class="hero-cnt">
                                    {{ consumeSummary.count ? `共 ${consumeSummary.count} 笔` : "无消耗" }}
                                </text>
                            </view>
                        </view>

                        <!-- 大类汇总：点开看逐笔 -->
                        <view>
                            <text class="sec-label">{{ catHint }}</text>
                            <record-skeleton v-if="consumeLoading" :rows="8" />
                            <view v-else class="card overflow-hidden">
                                <view
                                    v-for="(cat, index) in catRows"
                                    :key="cat.key"
                                    class="cat-row active:bg-[#F8FAFD]"
                                    :class="{ 'cat-row--border': index < catRows.length - 1 }"
                                    @click="handleOpenCat(cat)">
                                    <view class="cat-icon" :style="{ background: cat.meta.bg }">
                                        <u-icon :name="cat.meta.icon" :color="cat.meta.color" size="36" />
                                    </view>
                                    <view class="min-w-0 flex-1">
                                        <view class="flex items-center justify-between gap-[12rpx]">
                                            <text class="truncate text-[29rpx] font-bold text-[#1A1A1A]">
                                                {{ cat.name }}
                                            </text>
                                            <text
                                                v-if="cat.count"
                                                class="flex-shrink-0 text-[31rpx] font-extrabold"
                                                :style="{ color: cat.meta.color }">
                                                {{ signedConsume(cat.total) }}
                                            </text>
                                            <text
                                                v-else
                                                class="flex-shrink-0 text-[28rpx] font-extrabold text-[#D3D9E3]">
                                                —
                                            </text>
                                        </view>
                                        <view class="mt-[4rpx] flex items-center justify-between gap-[16rpx]">
                                            <text class="truncate text-[22rpx] text-[#9AA3B2]">{{
                                                cat.meta.desc
                                            }}</text>
                                            <text v-if="cat.count" class="flex-shrink-0 text-[22rpx] text-[#9AA3B2]">
                                                {{ cat.count }} 笔 · {{ cat.pct }}%
                                            </text>
                                        </view>
                                        <view v-if="cat.count" class="bar">
                                            <view
                                                class="bar-fill"
                                                :style="{
                                                    width: `${Math.max(3, cat.pct)}%`,
                                                    background: cat.meta.color,
                                                }" />
                                        </view>
                                    </view>
                                    <u-icon name="arrow-right" size="26" color="#C0C8D8" />
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <!-- ══ 充值：默认全部 + 时间快筛 ══ -->
            <view v-else class="grow min-h-0">
                <z-paging
                    ref="pagingRef"
                    v-model="rechargeLists"
                    :fixed="false"
                    :default-page-size="20"
                    :safe-area-inset-bottom="true"
                    auto-show-back-to-top
                    @query="queryRechargeList">
                    <template #top>
                        <view class="flex flex-col gap-[28rpx] px-[32rpx] pb-[28rpx]">
                            <view class="flex flex-wrap gap-[16rpx]">
                                <view
                                    v-for="range in RECHARGE_QUICK_RANGES"
                                    :key="range.key"
                                    class="chip"
                                    :class="{ 'chip--on': rechargeRange === range.key }"
                                    @click="handleRange(range.key)">
                                    {{ range.label }}
                                </view>
                                <picker
                                    mode="date"
                                    :value="rechargeDay || today"
                                    :end="today"
                                    @change="handlePickRechargeDay">
                                    <view class="chip" :class="{ 'chip--on': rechargeRange === RECHARGE_RANGE_DAY }">
                                        {{ rechargeDay ? fmtDateShort(rechargeDay) : "指定日期" }}
                                    </view>
                                </picker>
                            </view>

                            <view class="hero hero--recharge">
                                <text class="hero-label">{{ rechargeHeroLabel }}</text>
                                <view class="relative z-10 mt-[12rpx] flex items-end justify-between">
                                    <view v-if="rechargeLoading" class="hero-skeleton h-[64rpx] w-[240rpx]" />
                                    <view v-else class="flex items-baseline gap-[10rpx]">
                                        <text class="hero-num">+{{ formatWithComma(rechargeTotal) }}</text>
                                        <text class="hero-unit">算力</text>
                                    </view>
                                    <view v-if="rechargeLoading" class="hero-skeleton h-[28rpx] w-[96rpx]" />
                                    <text v-else class="hero-cnt">
                                        {{ rechargeCount ? `共 ${rechargeCount} 笔` : "无记录" }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </template>

                    <view v-if="rechargeLoading" class="mx-[32rpx]">
                        <record-skeleton :rows="6" />
                    </view>
                    <view v-else class="card mx-[32rpx] overflow-hidden">
                        <view
                            v-for="(item, index) in rechargeLists"
                            :key="`${item.record_type}-${item.id}`"
                            class="rec-row"
                            :class="{ 'rec-row--border': index < rechargeLists.length - 1 }">
                            <view class="rec-icon">
                                <u-icon :name="rechargeIcon(item.remark)" color="#22B96C" size="36" />
                            </view>
                            <view class="min-w-0 flex-1">
                                <text class="line-clamp-2 text-[27rpx] font-bold leading-[1.4] text-[#1A1A1A]">
                                    {{ item.remark }}
                                </text>
                                <text class="mt-[4rpx] block break-all text-[22rpx] leading-[1.5] text-[#9AA3B2]">
                                    {{ rechargeDetail(item) }}
                                </text>
                            </view>
                            <view class="flex-shrink-0 text-right">
                                <text class="block text-[31rpx] font-extrabold text-[#22B96C]">
                                    {{ item.change_amount_desc }}
                                </text>
                                <text
                                    v-if="shouldShowLeftTokens(item)"
                                    class="mt-[4rpx] block text-[20rpx] text-[#9AA3B2]">
                                    剩余 {{ item.left_tokens }}
                                </text>
                            </view>
                        </view>
                    </view>

                    <template #empty>
                        <empty text="该时段没有充值记录" />
                    </template>
                </z-paging>
            </view>
        </view>

        <cat-detail-popup
            v-model="showCatPopup"
            :cat="activeCat"
            :date-label="consumeDateLabel"
            :loading="catLoading"
            :detail="catDetail" />
        <price-popup v-model="showPricePopup" :list="tokensConfig" :loading="priceLoading" />
    </view>
</template>

<script setup lang="ts">
import { accountLog, accountLogMnpLists } from "@/api/user";
import { useUserStore } from "@/stores/user";
import {
    BalanceTab,
    CAT_META,
    CAT_META_FALLBACK,
    ONLINE_PAY_KEYWORDS,
    RECHARGE_LOG_QUERY,
    RECHARGE_RANGES,
    RECORD_TYPE_DEVICE_AUTH,
    type RechargeRangeKey,
} from "./_enums";
import {
    dayEnd,
    dayStart,
    fmtDateCN,
    fmtDateShort,
    formatWithComma,
    monthRange,
    rechargeIcon,
    shiftDateKey,
    signedConsume,
    todayKey,
} from "./_utils";
import CatDetailPopup from "./components/popups/cat-detail-popup.vue";
import PricePopup from "./components/popups/price-popup.vue";
import RecordSkeleton from "./components/record-skeleton.vue";

/** 从「我的-订单」进来直接落到充值记录 */
const QUERY_TYPE_ORDER = "order";
const RECHARGE_RANGE_DAY: RechargeRangeKey = "day";
const RECHARGE_QUICK_RANGES = RECHARGE_RANGES.filter((r) => r.key !== RECHARGE_RANGE_DAY);
/** 接口异常时仍按八大类铺满，避免整卡空白 */
const DEFAULT_CATS = Object.keys(CAT_META)
    .filter((key) => key !== "other")
    .map((key) => ({ key, name: CAT_META[key].name, total: 0, count: 0 }));

const userStore = useUserStore();
const { tokensConfig } = toRefs(userStore);

const activeTab = ref<BalanceTab>(BalanceTab.Consume);
const today = todayKey();

/* ══ 消耗记录 ══ */
const consumeDate = ref(today);
const consumeLoading = ref(false);
const consumeSummary = reactive({ total: 0, count: 0 });
const consumeCats = ref<any[]>([]);
let consumeSeq = 0;

const isToday = computed(() => consumeDate.value === today);
const consumeDateLabel = computed(() => (isToday.value ? "今天" : fmtDateCN(consumeDate.value)));
const catHint = computed(() => {
    if (consumeLoading.value) return "按功能大类 · 统计中";
    return consumeSummary.count ? "按功能大类 · 点击查看扣费明细" : "这一天没有消耗 · 单价看右上角「计费标准」";
});

const catRows = computed(() => {
    const source = consumeCats.value.length ? consumeCats.value : DEFAULT_CATS;
    return source.map((cat: any) => {
        const meta = CAT_META[cat.key] || CAT_META_FALLBACK;
        const amount = Number(cat.total) || 0;
        const pct = consumeSummary.total > 0 && amount > 0 ? Math.round((amount / consumeSummary.total) * 100) : 0;
        return {
            key: String(cat.key),
            name: cat.name || meta.name,
            meta,
            total: amount,
            count: Number(cat.count) || 0,
            pct,
        };
    });
});

// 快速翻日期时旧请求可能晚返回，按序号丢弃过期响应
const queryConsume = async () => {
    const seq = ++consumeSeq;
    consumeLoading.value = true;
    try {
        const data: any = await accountLogMnpLists({ date: consumeDate.value });
        if (seq !== consumeSeq) return;
        consumeSummary.total = Number(data?.total) || 0;
        consumeSummary.count = Number(data?.count) || 0;
        consumeCats.value = Array.isArray(data?.cats) ? data.cats : [];
        // 非法/未来日期后端会钳回今天，以返回值为准
        if (data?.date) consumeDate.value = String(data.date);
    } catch {
        if (seq !== consumeSeq) return;
        consumeSummary.total = 0;
        consumeSummary.count = 0;
        consumeCats.value = [];
    } finally {
        if (seq === consumeSeq) consumeLoading.value = false;
    }
};

const handleShiftDay = (delta: number) => {
    const next = shiftDateKey(consumeDate.value, delta);
    if (next > today) return;
    consumeDate.value = next;
    queryConsume();
};

const handlePickDate = (e: any) => {
    const value = String(e?.detail?.value || "");
    if (!value) return;
    consumeDate.value = value > today ? today : value;
    queryConsume();
};

/* ══ 大类明细弹窗 ══ */
const showCatPopup = ref(false);
const activeCat = ref<{ key: string; name: string } | null>(null);
const catLoading = ref(false);
const catDetail = ref<any>(null);
let catSeq = 0;

const handleOpenCat = async (cat: { key: string; name: string }) => {
    activeCat.value = { key: cat.key, name: cat.name };
    catDetail.value = null;
    showCatPopup.value = true;
    const seq = ++catSeq;
    catLoading.value = true;
    try {
        const data = await accountLogMnpLists({ date: consumeDate.value, cat: cat.key });
        if (seq !== catSeq) return;
        catDetail.value = data;
    } catch {
        if (seq !== catSeq) return;
        catDetail.value = { total: 0, count: 0, items: [] };
    } finally {
        if (seq === catSeq) catLoading.value = false;
    }
};

/* ══ 计费标准 ══ */
const showPricePopup = ref(false);
const priceLoading = ref(false);

const queryTokensConfig = async () => {
    priceLoading.value = true;
    try {
        await userStore.getTokensConfig();
    } catch {
        // 价目表拉取失败时弹窗按空列表展示
    } finally {
        priceLoading.value = false;
    }
};

/* ══ 充值记录 ══ */
const pagingRef = shallowRef();
const rechargeLists = ref<any[]>([]);
const rechargeRange = ref<RechargeRangeKey>("all");
const rechargeDay = ref("");
const rechargeTotal = ref(0);
const rechargeCount = ref(0);
// 首页/切筛选时整卡换骨架屏；翻页由 z-paging 自己的底部加载态承接
const rechargeLoading = ref(false);

const rechargeHeroLabel = computed(
    () => RECHARGE_RANGES.find((r) => r.key === rechargeRange.value)?.heroLabel || "全部充值",
);

// 后端 ListsValidate 按 date 规则校验，须传日期时间串
const buildRechargeRange = () => {
    if (rechargeRange.value === "month" || rechargeRange.value === "last") {
        const range = monthRange(rechargeRange.value === "month" ? 0 : -1);
        return { start_time: dayStart(range.start), end_time: dayEnd(range.end) };
    }
    if (rechargeRange.value === RECHARGE_RANGE_DAY && rechargeDay.value) {
        return { start_time: dayStart(rechargeDay.value), end_time: dayEnd(rechargeDay.value) };
    }
    return {};
};

const queryRechargeList = async (page_no: number, page_size: number) => {
    if (page_no === 1) rechargeLoading.value = true;
    try {
        const data: any = await accountLog({
            page_no,
            page_size,
            ...RECHARGE_LOG_QUERY,
            ...buildRechargeRange(),
        });
        rechargeCount.value = Number(data?.count) || 0;
        rechargeTotal.value = Number(data?.extend?.total_tokens) || 0;
        pagingRef.value?.complete(Array.isArray(data?.lists) ? data.lists : []);
    } catch {
        pagingRef.value?.complete([]);
    } finally {
        if (page_no === 1) rechargeLoading.value = false;
    }
};

const handleRange = (key: RechargeRangeKey) => {
    if (rechargeRange.value === key) return;
    rechargeRange.value = key;
    rechargeDay.value = "";
    pagingRef.value?.reload();
};

const handlePickRechargeDay = (e: any) => {
    const value = String(e?.detail?.value || "");
    if (!value) return;
    rechargeRange.value = RECHARGE_RANGE_DAY;
    rechargeDay.value = value > today ? today : value;
    pagingRef.value?.reload();
};

/** 支付方式 / 套餐等附加信息 + 时间，拼成一行说明 */
const rechargeDetail = (item: any) => {
    const extra = item?.extra && typeof item.extra === "object" ? item.extra : {};
    const parts = Object.entries(extra)
        .filter(([, value]) => value !== "" && value != null)
        .map(([key, value]) => (key === "支付方式" ? String(value) : `${key}：${value}`));
    if (!parts.length && item?.pay_way) parts.push(String(item.pay_way));
    if (!parts.length && item?.sn) parts.push(`单号 ${item.sn}`);
    if (item?.create_time) parts.push(String(item.create_time));
    return parts.join(" · ");
};

/** 微信/虚拟支付与设备 CDK 订单不展示「剩余」 */
const shouldShowLeftTokens = (item: any) => {
    if (item?.show_left_tokens === false) return false;
    if (item?.record_type === RECORD_TYPE_DEVICE_AUTH) return false;
    const payWay = String(item?.pay_way || item?.extra?.["支付方式"] || "");
    return !ONLINE_PAY_KEYWORDS.some((keyword) => payWay.includes(keyword));
};

/* ══ 页签 ══ */
const handleTab = (tab: BalanceTab) => {
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    if (tab === BalanceTab.Consume) queryConsume();
};

onLoad((query: any) => {
    if (query?.type === QUERY_TYPE_ORDER) activeTab.value = BalanceTab.Recharge;
});

onShow(() => {
    queryTokensConfig();
    if (activeTab.value === BalanceTab.Consume) queryConsume();
});
</script>

<style lang="scss" scoped>
.tab2 {
    @apply flex rounded-[24rpx] bg-white p-[8rpx];
    box-shadow: 0 4rpx 24rpx rgba(99, 120, 200, 0.08);
}
.tab2-item {
    @apply flex flex-1 items-center justify-center gap-[10rpx] rounded-[20rpx] py-[18rpx] text-[27rpx] font-bold text-[#8A93A3];

    &--on {
        @apply text-white;
        background: linear-gradient(90deg, #2f80f7, #28b7f0);
        box-shadow: 0 8rpx 20rpx rgba(47, 128, 247, 0.28);
    }
}

.card {
    @apply rounded-[32rpx] bg-white;
    box-shadow: 0 4rpx 24rpx rgba(99, 120, 200, 0.08);
}

.day-btn {
    @apply flex h-[72rpx] w-[72rpx] flex-shrink-0 items-center justify-center rounded-[20rpx] bg-[#F3F6FB];

    &--off {
        opacity: 0.3;
        pointer-events: none;
    }
}

.hero {
    @apply relative overflow-hidden rounded-[32rpx] px-[36rpx] py-[32rpx] text-white;

    &--consume {
        background: linear-gradient(120deg, #2f80f7 0%, #28b7f0 100%);
        box-shadow: 0 16rpx 40rpx rgba(47, 128, 247, 0.25);
    }
    &--recharge {
        background: linear-gradient(120deg, #22b96c 0%, #3ed598 100%);
        box-shadow: 0 16rpx 40rpx rgba(34, 185, 108, 0.24);
    }
    &::after {
        @apply pointer-events-none;
        content: "";
        position: absolute;
        right: -56rpx;
        top: -68rpx;
        width: 240rpx;
        height: 240rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
    }
    &::before {
        @apply pointer-events-none;
        content: "";
        position: absolute;
        right: 52rpx;
        bottom: -92rpx;
        width: 192rpx;
        height: 192rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
}
.hero-btn {
    @apply flex flex-shrink-0 items-center justify-center gap-[6rpx] rounded-full px-[18rpx] py-[10rpx] text-[23rpx] font-bold text-white;
    background: rgba(255, 255, 255, 0.2);
    border: 2rpx solid rgba(255, 255, 255, 0.32);

    &--pressed {
        background: rgba(255, 255, 255, 0.32);
    }
}
.hero-label {
    @apply relative z-10 block text-[23rpx] font-semibold;
    color: rgba(255, 255, 255, 0.78);
}
.hero-num {
    @apply text-[64rpx] font-extrabold leading-none;
}
.hero-unit {
    @apply text-[24rpx] font-semibold;
    color: rgba(255, 255, 255, 0.82);
}
.hero-cnt {
    @apply text-[24rpx] font-semibold;
    color: rgba(255, 255, 255, 0.88);
}
.hero-skeleton {
    @apply rounded-[12rpx];
    background: rgba(255, 255, 255, 0.35);
    animation: hero-skeleton-pulse 1.2s ease-in-out infinite;
}
@keyframes hero-skeleton-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.45;
    }
}

.sec-label {
    @apply mb-[16rpx] block px-[12rpx] text-[25rpx] font-bold text-[#8A93A3];
}

.cat-row {
    @apply flex items-center gap-[24rpx] px-[32rpx] py-[28rpx];

    &--border {
        @apply border-[0] border-b-[2rpx] border-solid border-[#F2F4F9];
    }
}
.cat-icon {
    @apply flex h-[88rpx] w-[88rpx] flex-shrink-0 items-center justify-center rounded-[24rpx];
}
.bar {
    @apply mt-[12rpx] h-[8rpx] overflow-hidden rounded-full bg-[#EFF2F8];
}
.bar-fill {
    @apply h-full rounded-full;
}

.chip {
    @apply rounded-full border-[3rpx] border-solid border-[transparent] bg-white px-[30rpx] py-[14rpx] text-[25rpx] font-bold text-[#8A93A3];
    box-shadow: 0 4rpx 16rpx rgba(99, 120, 200, 0.07);

    &--on {
        @apply border-[#2B6EFF] bg-[#EBF2FF] text-[#2B6EFF];
        box-shadow: none;
    }
}

.rec-row {
    @apply flex items-center gap-[24rpx] px-[32rpx] py-[26rpx];

    &--border {
        @apply border-[0] border-b-[2rpx] border-solid border-[#F2F4F9];
    }
}
.rec-icon {
    @apply flex h-[88rpx] w-[88rpx] flex-shrink-0 items-center justify-center rounded-[24rpx] bg-[#E9F9EE];
}
</style>
