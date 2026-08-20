<template>
    <section class="panel">
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div class="section-title !mb-0"><span class="bar"></span>算力消耗明细</div>
            <!-- 消耗列表 / 算力流转 -->
            <div class="consume-tabs">
                <button
                    type="button"
                    class="consume-tab"
                    :class="{ active: consumeTab === 'consume' }"
                    @click="setConsumeTab('consume')">
                    消耗列表
                </button>
                <button
                    type="button"
                    class="consume-tab"
                    :class="{ active: consumeTab === 'transfer' }"
                    @click="setConsumeTab('transfer')">
                    算力流转
                </button>
            </div>
        </div>

        <!-- 消耗列表:业务净消耗合计 + 团队余额 -->
        <div v-if="consumeTab === 'consume'" class="consume-sum mb-5">
            <div class="min-w-0">
                <p class="consume-sum__label">{{ teamName }} · {{ consumeSumLabel }}</p>
                <p class="consume-sum__value">{{ formatNum(consumeTotalCost) }}</p>
                <p class="consume-sum__tip">仅业务消耗，划拨等算力流转见右上角切换</p>
            </div>
            <div class="text-right shrink-0">
                <p class="consume-sum__label">团队算力余额</p>
                <p class="consume-sum__balance">{{ formatNum(ownerTokens) }}</p>
            </div>
        </div>
        <!-- 算力流转:划出 / 入账合计 -->
        <div v-else class="consume-sum consume-sum--transfer mb-5">
            <div class="min-w-0">
                <p class="consume-sum__label">{{ teamName }} · 划出合计</p>
                <p class="consume-sum__value">{{ formatNum(transferTotalOut) }}</p>
                <p class="consume-sum__tip">含划拨、制卡、OEM 等团队内部算力转移</p>
            </div>
            <div class="text-right shrink-0">
                <p class="consume-sum__label">入账 / 退回合计</p>
                <p class="consume-sum__balance">{{ formatNum(transferTotalIn) }}</p>
            </div>
        </div>

        <!-- 查询栏：时间快捷筛选 + 成员/关键词搜索 -->
        <div class="consume-toolbar mb-5">
            <div class="consume-filters">
                <button
                    v-for="item in CONSUME_RANGE_FILTERS"
                    :key="item.key"
                    type="button"
                    class="consume-filter-chip"
                    :class="{ active: !consumeDateRange && consumeRange === item.key }"
                    @click="setConsumeRange(item.key)">
                    {{ item.label }}
                </button>
                <ElDatePicker
                    v-model="consumeDateRange"
                    type="daterange"
                    value-format="x"
                    range-separator="至"
                    start-placeholder="开始日期"
                    end-placeholder="结束日期"
                    class="consume-range-picker !w-[250px]"
                    @change="onTeamConsumeFilter" />
            </div>
            <div class="consume-search-wrap">
                <ElSelect
                    v-model="consumeBiz"
                    placeholder="全部业务类型"
                    clearable
                    class="consume-member-select !w-[160px]"
                    @change="onTeamConsumeFilter">
                    <ElOption v-for="opt in consumeBizOptions" :key="opt.key" :label="opt.label" :value="opt.key" />
                </ElSelect>
                <ElSelect
                    v-model="teamConsumeUser"
                    placeholder="全部成员"
                    clearable
                    filterable
                    class="consume-member-select"
                    @change="onTeamConsumeFilter">
                    <ElOption
                        v-for="m in members"
                        :key="m.id"
                        :label="`${m.nickname}（${m.mobile || '-'}）`"
                        :value="m.id" />
                </ElSelect>
                <div class="consume-search">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="w-4 h-4 text-slate-400 shrink-0">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m21 21-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <input
                        v-model="consumeKeyword"
                        class="consume-search-input"
                        type="text"
                        placeholder="搜索成员昵称 / 手机号"
                        @keyup.enter="onTeamConsumeFilter" />
                    <button
                        v-if="consumeKeyword"
                        type="button"
                        class="consume-search-clear"
                        @click="consumeKeyword = ''; onTeamConsumeFilter()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                    <button type="button" class="consume-search-btn" @click="onTeamConsumeFilter">搜索</button>
                </div>
            </div>
        </div>

        <ElTable :data="teamConsumePager.lists" v-loading="teamConsumePager.loading" class="rounded-xl overflow-hidden">
            <ElTableColumn label="用户" min-width="170">
                <template #default="{ row }">
                    <div class="flex items-center gap-2.5">
                        <ElAvatar :size="30" :src="row.avatar" />
                        <div class="min-w-0">
                            <div class="font-medium truncate">{{ row.user_name }}</div>
                            <div class="text-slate-400 text-xs">{{ row.mobile }}</div>
                        </div>
                    </div>
                </template>
            </ElTableColumn>
            <ElTableColumn label="业务类型" min-width="130">
                <template #default="{ row }">
                    <ElTag effect="light" round size="small">{{ row.biz_name }}</ElTag>
                </template>
            </ElTableColumn>
            <ElTableColumn :label="consumeTab === 'transfer' ? '变动算力' : '消耗算力'" min-width="110" align="right">
                <template #default="{ row }">
                    <span
                        class="font-bold"
                        :class="Number(row.action) === 1 ? 'text-emerald-500' : 'text-amber-500'">
                        {{ Number(row.action) === 1 ? "+" : "-" }}{{ formatNum(row.change_amount) }}
                    </span>
                </template>
            </ElTableColumn>
            <ElTableColumn label="剩余算力" min-width="120" align="right">
                <template #default="{ row }">
                    <span class="text-slate-600 font-medium">{{ formatNum(row.tokens) }}</span>
                </template>
            </ElTableColumn>
            <ElTableColumn label="时间" prop="create_time" min-width="160" />
            <ElTableColumn label="操作" width="90" fixed="right">
                <template #default="{ row }">
                    <ElButton link type="primary" @click="onViewOutput(row)">详情</ElButton>
                </template>
            </ElTableColumn>
            <template #empty>
                <ElEmpty :description="consumeTab === 'transfer' ? '暂无算力流转记录' : '暂无消耗记录'" />
            </template>
        </ElTable>
        <div class="flex justify-end mt-4">
            <pagination v-model="teamConsumePager" @change="getTeamConsumeLists" />
        </div>
    </section>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { formatNum } from "../_composables/helpers";
import { CONSUME_RANGE_FILTERS } from "../_enums";

const { info: infoCtx, members: membersCtx, consumption } = useTeamContext();
const { info } = infoCtx;
const { members } = membersCtx;
const {
    consumeTab,
    setConsumeTab,
    teamConsumeUser,
    consumeKeyword,
    consumeRange,
    consumeDateRange,
    consumeBiz,
    consumeBizOptions,
    teamConsumePager,
    getTeamConsumeLists,
    onTeamConsumeFilter,
    setConsumeRange,
    consumeTotalCost,
    transferTotalOut,
    transferTotalIn,
    consumeSumLabel,
    onViewOutput,
} = consumption;

const teamName = computed(() => info.value?.name || "团队");
const ownerTokens = computed(() => Number(info.value?.owner_tokens) || 0);
</script>

<style lang="scss" scoped>
@import "@/pages/team/_styles/console.scss";

.consume-sum {
    @apply flex items-center justify-between gap-4;
    padding: 18px 22px;
    border-radius: 16px;
    background: linear-gradient(135deg, #2b6eff 0%, #1a50d9 100%);
    box-shadow: 0 10px 28px -12px rgba(26, 80, 217, 0.55);
}
.consume-sum--transfer {
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    box-shadow: 0 10px 28px -12px rgba(91, 33, 182, 0.55);
}

.consume-tabs {
    @apply inline-flex items-center gap-1 p-1 rounded-xl;
    background: #f1f5f9;
}
.consume-tab {
    @apply h-8 px-4 rounded-[10px] text-[13px] font-semibold transition-colors;
    color: #64748b;
}
.consume-tab:hover {
    color: #0f172a;
}
.consume-tab.active {
    color: #0065fb;
    background: #fff;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
    font-weight: 700;
}
.consume-sum__label {
    @apply text-[12px] font-semibold mb-1.5;
    color: rgba(255, 255, 255, 0.72);
}
.consume-sum__value {
    @apply text-[32px] font-[800] text-white leading-none tracking-tight;
}
.consume-sum__tip {
    @apply mt-2 text-[12px] font-medium leading-snug;
    color: rgba(255, 255, 255, 0.62);
}
.consume-sum__balance {
    @apply text-[22px] font-[800] text-white leading-none;
}

.consume-toolbar {
    @apply flex items-center justify-between gap-3 flex-wrap;
    padding: 12px 14px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}

.consume-filters {
    @apply flex items-center gap-2 flex-wrap min-w-0;
}

.consume-filter-chip {
    @apply inline-flex items-center h-8 px-3 rounded-full text-[13px] font-semibold transition-colors;
    color: #64748b;
    background: #fff;
    border: 1px solid #e5eaf3;
}
.consume-filter-chip:hover {
    color: #0f172a;
    border-color: #cbd5e1;
}
.consume-filter-chip.active {
    color: #fff;
    background: #0065fb;
    border-color: #0065fb;
}

.consume-search-wrap {
    @apply flex items-center gap-2 flex-wrap min-w-0;
}

.consume-range-picker :deep(.el-range-input) {
    font-size: 13px;
}
.consume-range-picker.el-date-editor {
    height: 32px;
    border-radius: 16px;
    box-shadow: none;
    border: 1px solid #e5eaf3;
}

.consume-member-select {
    width: 200px;
}
.consume-member-select :deep(.el-select__wrapper) {
    min-height: 36px;
    border-radius: 12px;
    box-shadow: none !important;
    border: 1px solid #e5eaf3;
    background: #fff;
}

.consume-search {
    @apply flex items-center gap-2 h-9 px-3 rounded-xl bg-white min-w-[240px] max-w-[320px];
    border: 1px solid #e5eaf3;
}
.consume-search:focus-within {
    border-color: rgba(0, 101, 251, 0.45);
    box-shadow: 0 0 0 3px rgba(0, 101, 251, 0.08);
}
.consume-search-input {
    @apply flex-1 min-w-0 bg-transparent text-[13px] text-slate-800 outline-none;
}
.consume-search-input::placeholder {
    color: #94a3b8;
}
.consume-search-clear {
    @apply w-5 h-5 rounded-full grid place-items-center text-slate-400 hover:text-slate-600 hover:bg-slate-100;
}
.consume-search-btn {
    @apply h-7 px-3 rounded-lg text-[12px] font-bold text-primary shrink-0;
    background: rgba(0, 101, 251, 0.08);
}
.consume-search-btn:hover {
    background: rgba(0, 101, 251, 0.14);
}
</style>
