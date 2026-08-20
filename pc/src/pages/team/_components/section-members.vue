<template>
    <section class="panel">
        <!-- 标题行 -->
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div class="section-title !mb-0">
                <span class="bar"></span>团队成员
                <span class="text-slate-400 font-normal ml-1">({{ roleCounts.all || memberPager.count }})</span>
            </div>
            <ElButton type="primary" class="!rounded-xl !h-9" @click="onInvite">
                <span class="inline-flex items-center gap-1.5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM19 8v6M22 11h-6" />
                    </svg>
                    邀请成员
                </span>
            </ElButton>
        </div>

        <!-- 查询栏：角色筛选 + 搜索 -->
        <div class="member-toolbar mb-5">
            <div class="member-filters">
                <button
                    v-for="item in ROLE_FILTERS"
                    :key="item.key"
                    type="button"
                    class="member-filter-chip"
                    :class="{ active: roleFilter === item.key }"
                    @click="setRoleFilter(item.key)">
                    {{ item.label }}
                    <span class="member-filter-count">{{ roleCounts[item.key] ?? 0 }}</span>
                </button>
            </div>
            <div class="member-search">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="w-4 h-4 text-slate-400 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                </svg>
                <input
                    v-model="memberQuery.keyword"
                    class="member-search-input"
                    type="text"
                    placeholder="搜索成员昵称 / 手机号"
                    @keyup.enter="resetMemberPage" />
                <button
                    v-if="memberQuery.keyword"
                    type="button"
                    class="member-search-clear"
                    @click="memberQuery.keyword = ''; resetMemberPage()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
                <button type="button" class="member-search-btn" @click="resetMemberPage">搜索</button>
            </div>
        </div>

        <ElTable :data="memberPager.lists" v-loading="memberPager.loading" class="rounded-xl overflow-hidden">
            <ElTableColumn label="成员名" min-width="200">
                <template #default="{ row }">
                    <div class="flex items-center gap-3">
                        <ElAvatar :size="34" :src="row.avatar" />
                        <span class="font-medium text-slate-800">{{ row.nickname }}</span>
                    </div>
                </template>
            </ElTableColumn>
            <ElTableColumn label="成员角色" min-width="150">
                <template #default="{ row }">
                    <span v-if="row.team_role === 2" class="role-chip is-owner">
                        <span class="role-dot"></span>创始人
                    </span>
                    <ElDropdown v-else-if="isOwner" trigger="click" @command="(r: number) => onChangeRole(row, r)">
                        <span class="role-chip cursor-pointer" :class="row.team_role === 3 ? 'is-admin' : 'is-member'">
                            <span class="role-dot"></span>{{ row.role_desc }}
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.4"
                                class="w-3 h-3 ml-0.5 opacity-60">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                            </svg>
                        </span>
                        <template #dropdown>
                            <ElDropdownMenu>
                                <ElDropdownItem :command="3" :disabled="row.team_role === 3">设为管理员</ElDropdownItem>
                                <ElDropdownItem :command="1" :disabled="row.team_role === 1">设为成员</ElDropdownItem>
                            </ElDropdownMenu>
                        </template>
                    </ElDropdown>
                    <span v-else class="role-chip" :class="row.team_role === 3 ? 'is-admin' : 'is-member'">
                        <span class="role-dot"></span>{{ row.role_desc }}
                    </span>
                </template>
            </ElTableColumn>
            <ElTableColumn label="剩余算力" min-width="120" align="right">
                <template #default="{ row }">
                    <span class="font-bold text-primary">{{ formatNum(row.tokens) }}</span>
                </template>
            </ElTableColumn>
            <ElTableColumn label="最近使用时间" min-width="160">
                <template #default="{ row }">
                    <span class="text-slate-600">{{ row.last_used_time_desc || row.last_used_time || "-" }}</span>
                </template>
            </ElTableColumn>
            <ElTableColumn label="到期时间" min-width="160">
                <template #default="{ row }">
                    <span v-if="row.team_role === 2" class="text-slate-400">永久</span>
                    <span v-else :class="row.expired ? 'text-red-500' : 'text-slate-600'">
                        {{ row.team_expire_time_desc || row.team_expire_time || "永久" }}
                    </span>
                </template>
            </ElTableColumn>
            <ElTableColumn label="算力累计消耗" min-width="140" align="right">
                <template #default="{ row }">
                    <span class="font-bold" :class="Number(row.total_consumed) > 0 ? 'text-amber-500' : 'text-slate-400'">
                        {{ Number(row.total_consumed) > 0 ? "-" : "" }}{{ formatNum(row.total_consumed) }}
                    </span>
                </template>
            </ElTableColumn>
            <ElTableColumn label="操作" width="280" fixed="right">
                <template #default="{ row }">
                    <ElButton link type="primary" @click="onConsumption(row)">查看明细</ElButton>
                    <template v-if="canManage(row)">
                        <ElButton link type="primary" @click="onEditTokens(row)">修改算力</ElButton>
                        <ElButton link type="primary" @click="onSetExpire(row)">设置到期</ElButton>
                        <ElButton link type="danger" @click="onRemoveMember(row)">移出团队</ElButton>
                    </template>
                </template>
            </ElTableColumn>
            <template #empty><ElEmpty description="暂无成员" /></template>
        </ElTable>
        <div class="flex justify-end mt-4">
            <pagination v-model="memberPager" @change="getMemberList" />
        </div>
    </section>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { formatNum } from "../_composables/helpers";
import { ROLE_FILTERS } from "../_enums";

const { info, members, consumption } = useTeamContext();
const { isOwner } = info;
const {
    memberQuery,
    memberPager,
    getMemberList,
    resetMemberPage,
    roleFilter,
    roleCounts,
    setRoleFilter,
    onInvite,
    canManage,
    onChangeRole,
    onEditTokens,
    onSetExpire,
    onRemoveMember,
} = members;
const { onConsumption } = consumption;
</script>

<style lang="scss" scoped>
@import "@/pages/team/_styles/console.scss";

.member-toolbar {
    @apply flex items-center justify-between gap-3 flex-wrap;
    padding: 12px 14px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}

.member-filters {
    @apply flex items-center gap-2 flex-wrap min-w-0;
}

.member-filter-chip {
    @apply inline-flex items-center gap-1.5 h-8 px-3 rounded-full text-[13px] font-semibold transition-colors;
    color: #64748b;
    background: #fff;
    border: 1px solid #e5eaf3;
}
.member-filter-chip:hover {
    color: #0f172a;
    border-color: #cbd5e1;
}
.member-filter-chip.active {
    color: #fff;
    background: #0065fb;
    border-color: #0065fb;
}
.member-filter-count {
    @apply text-[12px] font-bold tabular-nums;
    opacity: 0.85;
}

.member-search {
    @apply flex items-center gap-2 h-9 px-3 rounded-xl bg-white min-w-[260px] max-w-[360px] flex-1;
    border: 1px solid #e5eaf3;
}
.member-search:focus-within {
    border-color: rgba(0, 101, 251, 0.45);
    box-shadow: 0 0 0 3px rgba(0, 101, 251, 0.08);
}
.member-search-input {
    @apply flex-1 min-w-0 bg-transparent text-[13px] text-slate-800 outline-none;
}
.member-search-input::placeholder {
    color: #94a3b8;
}
.member-search-clear {
    @apply w-5 h-5 rounded-full grid place-items-center text-slate-400 hover:text-slate-600 hover:bg-slate-100;
}
.member-search-btn {
    @apply h-7 px-3 rounded-lg text-[12px] font-bold text-primary shrink-0;
    background: rgba(0, 101, 251, 0.08);
}
.member-search-btn:hover {
    background: rgba(0, 101, 251, 0.14);
}
</style>
