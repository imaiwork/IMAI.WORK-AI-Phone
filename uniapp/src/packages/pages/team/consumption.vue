<template>
    <view class="h-screen flex flex-col overflow-hidden bg-[#F4F6FA]">
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="lists"
                :fixed="false"
                :default-page-size="15"
                :safe-area-inset-bottom="true"
                :auto="false"
                @query="queryList">
                <template #top>
                    <view class="flex flex-col gap-[24rpx] px-[32rpx] pt-[28rpx] pb-[8rpx]">
                        <!-- 消耗列表 / 算力流转 -->
                        <view class="seg-tabs">
                            <view
                                class="seg-tab"
                                :class="{ active: listType === 'consume' }"
                                @click="handleTab('consume')">
                                消耗列表
                            </view>
                            <view
                                class="seg-tab"
                                :class="{ active: listType === 'transfer' }"
                                @click="handleTab('transfer')">
                                算力流转
                            </view>
                        </view>

                        <!-- 汇总卡 -->
                        <view class="overflow-hidden rounded-[40rpx] shadow-card">
                            <view
                                class="flex items-center justify-between px-[40rpx] py-[40rpx]"
                                :class="listType === 'transfer' ? 'sum-card--transfer' : 'sum-card'">
                                <view class="min-w-0 flex-1 pr-[20rpx]">
                                    <text
                                        class="mb-[12rpx] block text-[22rpx] font-semibold"
                                        style="color: rgba(255, 255, 255, 0.7)">
                                        {{ teamName }} · {{ listType === "transfer" ? "划出合计" : sumLabel }}
                                    </text>
                                    <text class="block text-[68rpx] font-bold leading-none text-white">
                                        {{ listType === "transfer" ? transferOutText : totalCostText }}
                                    </text>
                                    <text
                                        class="mt-[14rpx] block text-[20rpx] font-medium leading-[1.4]"
                                        style="color: rgba(255, 255, 255, 0.62)">
                                        {{
                                            listType === "transfer"
                                                ? "含划拨、制卡、OEM 等团队内部算力转移"
                                                : "仅业务消耗，划拨等见「算力流转」"
                                        }}
                                    </text>
                                </view>
                                <view class="flex-shrink-0 text-right">
                                    <text
                                        class="mb-[12rpx] block text-[22rpx] font-semibold"
                                        style="color: rgba(255, 255, 255, 0.7)">
                                        {{ listType === "transfer" ? "入账 / 退回合计" : "团队算力余额" }}
                                    </text>
                                    <text class="block text-[40rpx] font-bold leading-none text-white">
                                        {{ listType === "transfer" ? transferInText : balanceText }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <!-- 筛选栏:业务类型 / 时间 / 成员,点击下拉展开 -->
                        <view class="relative z-50">
                            <view class="filter-bar">
                                <view
                                    class="filter-bar__item"
                                    :class="{ 'filter-bar__item--on': !!bizKey || activePanel === 'biz' }"
                                    @click="togglePanel('biz')">
                                    <text class="truncate">{{ bizBarLabel }}</text>
                                    <u-icon
                                        :name="activePanel === 'biz' ? 'arrow-up-fill' : 'arrow-down-fill'"
                                        size="18"
                                        :color="!!bizKey || activePanel === 'biz' ? '#0065FB' : '#94A3B8'" />
                                </view>
                                <view class="filter-bar__divider" />
                                <view
                                    class="filter-bar__item"
                                    :class="{
                                        'filter-bar__item--on':
                                            rangeKey !== 'all' || hasCustomRange || activePanel === 'range',
                                    }"
                                    @click="togglePanel('range')">
                                    <text class="truncate">{{ rangeBarLabel }}</text>
                                    <u-icon
                                        :name="activePanel === 'range' ? 'arrow-up-fill' : 'arrow-down-fill'"
                                        size="18"
                                        :color="
                                            rangeKey !== 'all' || hasCustomRange || activePanel === 'range'
                                                ? '#0065FB'
                                                : '#94A3B8'
                                        " />
                                </view>
                                <view class="filter-bar__divider" />
                                <view
                                    class="filter-bar__item"
                                    :class="{ 'filter-bar__item--on': !!filterUserId || activePanel === 'member' }"
                                    @click="togglePanel('member')">
                                    <text class="truncate">{{ memberBarLabel }}</text>
                                    <u-icon
                                        :name="activePanel === 'member' ? 'arrow-up-fill' : 'arrow-down-fill'"
                                        size="18"
                                        :color="!!filterUserId || activePanel === 'member' ? '#0065FB' : '#94A3B8'" />
                                </view>
                            </view>

                            <!-- 下拉面板 -->
                            <view v-if="activePanel" class="filter-panel">
                                <!-- 业务类型 -->
                                <view v-if="activePanel === 'biz'" class="flex flex-wrap gap-[16rpx]">
                                    <view
                                        class="filter-chip filter-chip--sm"
                                        :class="{ active: !bizKey }"
                                        @click="handleBiz('')">
                                        全部类型
                                    </view>
                                    <view
                                        v-for="opt in bizOptions"
                                        :key="opt.key"
                                        class="filter-chip filter-chip--sm"
                                        :class="{ active: bizKey === opt.key }"
                                        @click="handleBiz(opt.key)">
                                        {{ opt.label }}
                                    </view>
                                </view>
                                <!-- 时间 -->
                                <view v-if="activePanel === 'range'">
                                    <view class="flex flex-wrap gap-[16rpx]">
                                        <view
                                            v-for="item in CONSUME_RANGE_FILTERS"
                                            :key="item.key"
                                            class="filter-chip filter-chip--sm"
                                            :class="{ active: !hasCustomRange && rangeKey === item.key }"
                                            @click="handleRange(item.key)">
                                            {{ item.label }}
                                        </view>
                                    </view>
                                    <!-- 自定义时间段(与 PC 端日期范围选择同步) -->
                                    <view class="mt-[24rpx] border-0 border-t-[2rpx] border-solid border-[#F0F4FB] pt-[24rpx]">
                                        <text class="mb-[16rpx] block text-[22rpx] font-semibold text-[#94A3B8]">
                                            自定义时间段
                                        </text>
                                        <view class="flex items-center gap-[12rpx]">
                                            <picker
                                                mode="date"
                                                :value="draftStart"
                                                :end="draftEnd || '2099-12-31'"
                                                class="min-w-0 flex-1"
                                                @change="onDraftStart">
                                                <view class="range-input" :class="{ 'range-input--empty': !draftStart }">
                                                    {{ draftStart || "开始日期" }}
                                                </view>
                                            </picker>
                                            <text class="flex-shrink-0 text-[22rpx] text-[#94A3B8]">至</text>
                                            <picker
                                                mode="date"
                                                :value="draftEnd"
                                                :start="draftStart"
                                                end="2099-12-31"
                                                class="min-w-0 flex-1"
                                                @change="onDraftEnd">
                                                <view class="range-input" :class="{ 'range-input--empty': !draftEnd }">
                                                    {{ draftEnd || "结束日期" }}
                                                </view>
                                            </picker>
                                            <view
                                                class="flex h-[64rpx] flex-shrink-0 items-center justify-center rounded-[18rpx] bg-primary px-[28rpx] active:opacity-90"
                                                @click="applyCustomRange">
                                                <text class="text-[24rpx] font-bold text-white">确定</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <!-- 成员 -->
                                <scroll-view v-if="activePanel === 'member'" scroll-y class="max-h-[420rpx]">
                                    <view class="flex flex-wrap gap-[16rpx]">
                                        <view
                                            class="filter-chip filter-chip--sm"
                                            :class="{ active: !filterUserId }"
                                            @click="handleMember(0)">
                                            全部成员
                                        </view>
                                        <view
                                            v-for="m in memberOptions"
                                            :key="m.id"
                                            class="filter-chip filter-chip--sm"
                                            :class="{ active: filterUserId === m.id }"
                                            @click="handleMember(m.id)">
                                            {{ m.nickname || `用户${m.id}` }}
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                        </view>
                    </view>
                    <!-- 遮罩:点击收起面板 -->
                    <view v-if="activePanel" class="filter-mask" @click="activePanel = ''" />
                </template>

                <view class="mx-[32rpx] overflow-hidden rounded-[40rpx] bg-white shadow-card">
                    <view
                        v-for="(item, index) in lists"
                        :key="item.id"
                        class="flex items-center gap-[24rpx] px-[24rpx] py-[28rpx] active:bg-[#F6F9FF]"
                        :class="index < lists.length - 1 ? 'row-border' : ''"
                        @click="openDetail(item)">
                        <view
                            class="flex h-[80rpx] w-[80rpx] flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#F1F5F9] text-[28rpx] font-bold text-[#64748B]">
                            <image v-if="item.avatar" :src="item.avatar" class="h-full w-full" mode="aspectFill" />
                            <text v-else>{{ String(item.user_name || "?").slice(0, 1) }}</text>
                        </view>
                        <view class="min-w-0 flex-1">
                            <view class="mb-[6rpx] flex min-w-0 items-center gap-[14rpx]">
                                <text class="truncate text-[26rpx] font-semibold text-[#0F172A]">
                                    {{ item.user_name }}
                                </text>
                                <view class="cat-tag" :style="bizStyle(item.biz_key)">
                                    {{ item.biz_name || "其他消耗" }}
                                </view>
                            </view>
                            <text class="block text-[22rpx] text-[#94A3B8]">
                                {{ maskMobile(item.mobile) }}
                            </text>
                        </view>
                        <view class="flex-shrink-0 text-right">
                            <text
                                class="mb-[6rpx] block text-[28rpx] font-bold"
                                :class="Number(item.action) === 1 ? 'text-[#16A34A]' : 'text-[#F59E0B]'">
                                {{ Number(item.action) === 1 ? "+" : "-" }}{{ formatNum(item.change_amount) }}
                            </text>
                            <text class="mb-[6rpx] block text-[20rpx] text-[#64748B]">
                                余额 {{ formatNum(item.tokens) }}
                            </text>
                            <text class="block text-[20rpx] text-[#94A3B8]">
                                {{ item.create_time || "—" }}
                            </text>
                        </view>
                        <u-icon name="arrow-right" size="28" color="#CBD5E1" />
                    </view>
                </view>

                <view class="px-[32rpx] pt-[28rpx] pb-[16rpx]">
                    <text class="block text-center text-[22rpx] leading-[1.7] text-[#BBBBBB]">
                        仅团队创始人与管理员可查看
                    </text>
                </view>

                <template #empty>
                    <view class="py-[80rpx] text-center text-[26rpx] text-[#94A3B8]">
                        {{ listType === "transfer" ? "暂无算力流转记录" : "暂无消耗记录" }}
                    </view>
                </template>
            </z-paging>
        </view>

        <consume-output-popup v-model="showOutput" :row="outputRow" />
    </view>
</template>

<script setup lang="ts">
import { getMemberOptions, getTeamConsumption, getTeamInfo } from "@/api/team";
import { onLoad, onShow } from "@dcloudio/uni-app";
import { BIZ_TAG_META, CONSUME_RANGE_FILTERS, TeamRole, type ConsumeRangeKey } from "./_enums";
import ConsumeOutputPopup from "./components/consume-output-popup.vue";

const pagingRef = shallowRef();
const lists = ref<any[]>([]);
const info = ref<any>(null);
const memberOptions = ref<any[]>([]);
const rangeKey = ref<ConsumeRangeKey>("all");
const filterUserId = ref(0);
const totalCost = ref(0);
// 列表类型:consume=消耗列表 / transfer=算力流转;业务类型筛选选项由后端下发
const listType = ref<"consume" | "transfer">("consume");
const bizKey = ref("");
const bizOptions = ref<{ key: string; label: string }[]>([]);
const transferOut = ref(0);
const transferIn = ref(0);
const showOutput = ref(false);
const outputRow = ref<any>(null);
// 淘宝式筛选栏当前展开的面板
const activePanel = ref<"" | "biz" | "range" | "member">("");
// 自定义时间段:customXxx 为已生效值,draftXxx 为面板内未确认的选择
const customStart = ref("");
const customEnd = ref("");
const draftStart = ref("");
const draftEnd = ref("");
const hasCustomRange = computed(() => !!(customStart.value && customEnd.value));

const myRole = computed(() => Number(info.value?.team_role) || 0);
const isManager = computed(() => myRole.value === TeamRole.Owner || myRole.value === TeamRole.Admin);
const teamName = computed(() => info.value?.name || "团队");
const balanceText = computed(() => formatNum(info.value?.owner_tokens));
const sumLabel = computed(() => {
    if (hasCustomRange.value) return "所选时间团队消耗（算力）";
    const hit = CONSUME_RANGE_FILTERS.find((f) => f.key === rangeKey.value);
    return hit?.sumLabel || "团队消耗（算力）";
});
const totalCostText = computed(() => formatWithComma(totalCost.value));
const transferOutText = computed(() => formatWithComma(transferOut.value));
const transferInText = computed(() => formatWithComma(transferIn.value));
const currentRange = computed(() => {
    const hit = CONSUME_RANGE_FILTERS.find((f) => f.key === rangeKey.value);
    // 「全部」的 range 为空字符串,不能被 || 吞成 month
    return hit ? hit.range : "month";
});

// 筛选栏显示文案:选中非默认值时显示具体项
const bizBarLabel = computed(() => {
    if (!bizKey.value) return "业务类型";
    return bizOptions.value.find((o) => o.key === bizKey.value)?.label || "业务类型";
});
const rangeBarLabel = computed(() => {
    if (hasCustomRange.value) return `${customStart.value.slice(5)}~${customEnd.value.slice(5)}`;
    if (rangeKey.value === "all") return "时间";
    return CONSUME_RANGE_FILTERS.find((f) => f.key === rangeKey.value)?.label || "时间";
});
const memberBarLabel = computed(() => {
    if (!filterUserId.value) return "成员";
    const m = memberOptions.value.find((x) => Number(x.id) === filterUserId.value);
    return m?.nickname || `用户${filterUserId.value}`;
});

const togglePanel = (key: "biz" | "range" | "member") => {
    const next = activePanel.value === key ? "" : key;
    if (next === "range") {
        draftStart.value = customStart.value;
        draftEnd.value = customEnd.value;
    }
    activePanel.value = next;
};

const onDraftStart = (e: any) => {
    draftStart.value = String(e?.detail?.value || "");
};
const onDraftEnd = (e: any) => {
    draftEnd.value = String(e?.detail?.value || "");
};

const applyCustomRange = () => {
    if (!draftStart.value || !draftEnd.value) {
        uni.$u.toast("请选择开始和结束日期");
        return;
    }
    if (draftStart.value > draftEnd.value) {
        uni.$u.toast("开始日期不能晚于结束日期");
        return;
    }
    customStart.value = draftStart.value;
    customEnd.value = draftEnd.value;
    activePanel.value = "";
    reloadList();
};

// YYYY-MM-DD 补时分秒;后端 ListsValidate 校验 date 规则,须传日期字符串(勿传 unix 秒)
const dateToDatetime = (s: string, endOfDay = false) => {
    return `${s} ${endOfDay ? "23:59:59" : "00:00:00"}`;
};

const errText = (e: any) => (typeof e === "string" ? e : e?.msg || e?.message || "操作失败");

const formatNum = (n: any) => {
    const v = Number(n);
    if (!Number.isFinite(v)) return "0";
    if (Number.isInteger(v)) return String(v);
    return String(Math.round(v * 100) / 100);
};

const formatWithComma = (n: any) => {
    const s = formatNum(n);
    const [intPart, dec] = s.split(".");
    const withComma = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return dec != null ? `${withComma}.${dec}` : withComma;
};

const maskMobile = (m: any) => {
    const s = String(m || "");
    if (!s) return "—";
    if (s.length < 7) return s;
    return `${s.slice(0, 3)}****${s.slice(-4)}`;
};

const bizStyle = (key: string) => {
    const m = BIZ_TAG_META[key] || BIZ_TAG_META.other;
    return { background: m.bg, color: m.color };
};

const loadInfo = async () => {
    try {
        info.value = await getTeamInfo();
        if (Number(info.value?.in_team) !== 1 || !isManager.value) {
            uni.$u.toast("仅创始人与管理员可查看");
            setTimeout(() => uni.navigateBack(), 400);
            return false;
        }
        if (Number(info.value?.expired) === 1) {
            uni.$u.toast("该团队成员资格已过期，无法进入");
            setTimeout(() => uni.navigateBack(), 400);
            return false;
        }
        return true;
    } catch (e: any) {
        uni.$u.toast(errText(e));
        return false;
    }
};

const loadMembers = async () => {
    try {
        const rows: any = await getMemberOptions();
        memberOptions.value = Array.isArray(rows) ? rows : rows?.lists || [];
    } catch {
        memberOptions.value = [];
    }
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const data: any = await getTeamConsumption({
            page_no,
            page_size,
            // 自定义范围时不传 range,改传 start_time/end_time(unix 秒)
            range: hasCustomRange.value ? "" : currentRange.value,
            start_time: hasCustomRange.value ? dateToDatetime(customStart.value) : "",
            end_time: hasCustomRange.value ? dateToDatetime(customEnd.value, true) : "",
            user_id: filterUserId.value || "",
            list_type: listType.value,
            biz: bizKey.value || "",
        });
        const rows = data?.lists || [];
        const extend = data?.extend || {};
        if (extend.total_cost != null) totalCost.value = Number(extend.total_cost) || 0;
        if (extend.total_out != null) transferOut.value = Number(extend.total_out) || 0;
        if (extend.total_in != null) transferIn.value = Number(extend.total_in) || 0;
        if (Array.isArray(extend.biz_options)) bizOptions.value = extend.biz_options;
        pagingRef.value?.complete(rows);
    } catch (e: any) {
        pagingRef.value?.complete([]);
    }
};

const reloadList = () => {
    pagingRef.value?.reload();
};

const handleRange = (key: ConsumeRangeKey) => {
    activePanel.value = "";
    if (rangeKey.value === key && !hasCustomRange.value) return;
    rangeKey.value = key;
    // 点快捷时间即放弃自定义范围
    customStart.value = "";
    customEnd.value = "";
    reloadList();
};

const handleMember = (uid: number) => {
    activePanel.value = "";
    if (filterUserId.value === uid) return;
    filterUserId.value = uid;
    reloadList();
};

const handleTab = (tab: "consume" | "transfer") => {
    if (listType.value === tab) return;
    listType.value = tab;
    activePanel.value = "";
    bizKey.value = ""; // 两个 tab 的业务类型选项不同,切换时重置
    reloadList();
};

const handleBiz = (key: string) => {
    activePanel.value = "";
    if (bizKey.value === key) return;
    bizKey.value = key;
    reloadList();
};

const openDetail = (row: any) => {
    outputRow.value = row;
    showOutput.value = true;
};

onLoad((query: any) => {
    const uid = Number(query?.user_id || 0);
    if (uid > 0) filterUserId.value = uid;
    const range = String(query?.range || "");
    if (CONSUME_RANGE_FILTERS.some((f) => f.key === range)) {
        rangeKey.value = range as ConsumeRangeKey;
    }
});

onShow(async () => {
    const ok = await loadInfo();
    if (!ok) return;
    await loadMembers();
    nextTick(() => reloadList());
});
</script>

<style lang="scss" scoped>
.sum-card {
    background: linear-gradient(135deg, #2b6eff 0%, #1a50d9 100%);
}
.sum-card--transfer {
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
}

.seg-tabs {
    @apply inline-flex items-center gap-[8rpx] self-start rounded-[20rpx] bg-[#EDF1F7] p-[8rpx];
}
.seg-tab {
    @apply rounded-[16rpx] px-[32rpx] py-[14rpx] text-[24rpx] font-semibold text-[#64748B];

    &.active {
        @apply bg-white font-bold text-primary;
        box-shadow: 0 2rpx 8rpx rgba(15, 23, 42, 0.08);
    }
}

.shadow-card {
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.07);
}

.filter-bar {
    @apply flex items-center rounded-[24rpx] bg-white px-[8rpx] py-[6rpx];
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.04);
}
.filter-bar__item {
    @apply flex min-w-0 flex-1 items-center justify-center gap-[8rpx] rounded-[18rpx] px-[16rpx] py-[18rpx] text-[24rpx] font-semibold text-[#64748B];

    &--on {
        @apply font-bold text-primary;
    }
}
.filter-bar__divider {
    @apply h-[28rpx] w-[2rpx] flex-shrink-0 bg-[#EDF1F7];
}
.filter-panel {
    @apply absolute left-0 right-0 top-full z-50 mt-[10rpx] rounded-[24rpx] bg-white p-[24rpx];
    box-shadow: 0 16rpx 48rpx rgba(15, 23, 42, 0.12);
}
.filter-mask {
    @apply fixed inset-0 z-40;
    background: rgba(15, 23, 42, 0.35);
}

.range-input {
    @apply flex h-[64rpx] items-center justify-center rounded-[18rpx] bg-[#F4F6FB] px-[16rpx] text-[24rpx] font-semibold text-[#0F172A];

    &--empty {
        @apply font-normal text-[#9CA3AF];
    }
}

.filter-chip {
    @apply inline-flex items-center rounded-full border-[2rpx] border-solid border-[#E5EAF3] bg-white px-[28rpx] py-[12rpx] text-[24rpx] font-semibold text-[#64748B];

    &--sm {
        @apply px-[26rpx] py-[10rpx] text-[22rpx];
    }

    &.active {
        @apply border-primary bg-primary text-white;
    }
}

.cat-tag {
    @apply flex-shrink-0 rounded-full px-[16rpx] py-[4rpx] text-[20rpx] font-bold;
}

.row-border {
    @apply border-0 border-b-[2rpx] border-solid border-[#F0F0F0];
}
</style>
