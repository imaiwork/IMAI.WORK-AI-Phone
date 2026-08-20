<template>
    <section class="space-y-5">
        <div class="panel">
            <div class="flex items-start justify-between mb-5 flex-wrap gap-3">
                <div class="min-w-0">
                    <div class="section-title !mb-0"><span class="bar"></span>我的卡密</div>
                    <p class="text-[13px] text-slate-400 mt-2 leading-relaxed">
                        团队主转移给你的算力卡 / 会员兑换码会出现在这里。未使用可复制卡号发给用户兑换。
                    </p>
                </div>
                <ElButton class="!rounded-xl shrink-0" :loading="cardPager.loading" @click="getCardLists">
                    刷新
                </ElButton>
            </div>

            <ElTable :data="cardPager.lists" v-loading="cardPager.loading" class="rounded-xl overflow-hidden">
                <ElTableColumn label="卡号" min-width="200">
                    <template #default="{ row }">
                        <div class="card-code-cell">
                            <span class="card-code-text" :title="row.card_code">{{ row.card_code }}</span>
                            <button
                                v-if="row.card_code && row.status !== 1"
                                type="button"
                                class="card-copy-btn"
                                title="复制卡号"
                                @click="onCopy(row.card_code)">
                                复制
                            </button>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="类型" min-width="120">
                    <template #default="{ row }">
                        <span class="card-type-tag" :class="Number(row.type) === 6 ? 'is-member' : 'is-token'">
                            {{ row.type_desc || (Number(row.type) === 6 ? "会员兑换码" : "算力卡") }}
                        </span>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="内容" min-width="150">
                    <template #default="{ row }">
                        <span class="card-content">
                            {{ row.content || (Number(row.type) === 6 ? "—" : `${row.tokens ?? 0} 算力`) }}
                        </span>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="使用状态" width="110">
                    <template #default="{ row }">
                        <span class="card-status" :class="row.status === 1 ? 'is-used' : 'is-free'">
                            {{ row.status_desc || (row.status === 1 ? "已使用" : "未使用") }}
                        </span>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="使用者" min-width="120">
                    <template #default="{ row }">
                        <span v-if="row.used_by_nickname" class="card-user-name">{{ row.used_by_nickname }}</span>
                        <span v-else class="card-empty">—</span>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="使用时间" min-width="160">
                    <template #default="{ row }">
                        <span class="card-time">{{ row.use_time || "—" }}</span>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="获得时间" min-width="160">
                    <template #default="{ row }">
                        <span class="card-time">{{ row.create_time || "—" }}</span>
                    </template>
                </ElTableColumn>
                <template #empty>
                    <ElEmpty description="还没有分配给你的卡密，请联系团队主转移" />
                </template>
            </ElTable>

            <div class="flex justify-end mt-4">
                <pagination v-model="cardPager" @change="getCardLists" />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { getTeamCardLists } from "@/api/team";
import { useCopy } from "@/composables/useCopy";
import { usePaging } from "@/composables/usePaging";

const { copy } = useCopy();
const { pager: cardPager, getLists: getCardLists } = usePaging({ fetchFun: getTeamCardLists });

const onCopy = (code: string) => {
    if (!code) return;
    copy(code);
};

onMounted(() => {
    getCardLists();
});
</script>

<style lang="scss" scoped>
@import "@/pages/team/_styles/console.scss";

.card-code-cell {
    @apply flex items-center gap-2 min-w-0;
}
.card-code-text {
    @apply font-bold text-primary tracking-wide truncate;
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 13px;
}
.card-copy-btn {
    @apply shrink-0 h-6 px-2 rounded-md text-[12px] font-semibold text-primary;
    background: rgba(0, 101, 251, 0.08);
}
.card-copy-btn:hover {
    background: rgba(0, 101, 251, 0.14);
}
.card-type-tag {
    @apply inline-flex items-center h-6 px-2.5 rounded-full text-[12px] font-semibold;
}
.card-type-tag.is-token {
    color: #0065fb;
    background: rgba(0, 101, 251, 0.1);
}
.card-type-tag.is-member {
    color: #b45309;
    background: rgba(245, 158, 11, 0.14);
}
.card-content {
    @apply text-[13px] font-semibold text-slate-800;
}
.card-user-name {
    @apply text-[13px] font-medium text-slate-700;
}
.card-status {
    @apply inline-flex items-center h-6 px-2.5 rounded-full text-[12px] font-semibold;
}
.card-status.is-free {
    color: #059669;
    background: rgba(16, 185, 129, 0.12);
}
.card-status.is-used {
    color: #64748b;
    background: #f1f5f9;
}
.card-time {
    @apply text-[12px] text-slate-500 tabular-nums;
}
.card-empty {
    @apply text-slate-300 text-xs;
}
</style>
