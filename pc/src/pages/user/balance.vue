<template>
    <div class="p-6 h-full bg-slate-50">
        <div class="bg-white h-full flex rounded-[20px] flex-col p-8 border border-br">
            <div class="flex justify-between items-center gap-x-8 mb-6">
                <div class="grow flex items-center gap-6">
                    <div class="flex items-center gap-2 pr-6 border-r border-br-light">
                        <div class="w-1.5 h-5 rounded-full bg-primary"></div>
                        <span class="text-xl font-[900] text-[#0F172A]">账单记录</span>
                    </div>

                    <div class="custom-tabs">
                        <div
                            v-for="tab in tabsConfig"
                            :key="tab.name"
                            @click="handleTabChange(tab)"
                            class="tab-item"
                            :class="{ 'is-active': activeTab === tab.action }">
                            {{ tab.label }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        v-if="spaceTeamId > 0"
                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#FFF7ED] text-[#EA580C] text-xs font-black">
                        当前企业·{{ spaceTeamName || "企业空间" }}
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-xs font-black">
                        当前·个人空间
                    </span>
                    <ElButton
                        class="!w-10 !h-10 !p-0 !rounded-xl !border-[#E2E8F0] hover:!border-primary hover:!text-primary transition-all"
                        @click="onRefresh">
                        <Icon name="el-icon-Refresh" :size="16"></Icon>
                    </ElButton>
                </div>
            </div>

            <div class="flex-1 min-h-0 flex flex-col">
                <div class="flex-1 min-h-0">
                    <ElTable
                        :data="pager.lists"
                        height="100%"
                        v-loading="pager.loading"
                        :row-style="{ height: '72px' }">
                        <ElTableColumn label="订单号" min-width="180">
                            <template #default="{ row }">
                                <span class="text-xs font-medium text-[#64748B]">{{ row.sn || "--" }}</span>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="变动日期" min-width="180">
                            <template #default="{ row }">
                                <span class="text-xs font-medium text-[#64748B]">{{ row.create_time }}</span>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn label="变动数量" min-width="150">
                            <template #default="{ row }">
                                <div class="flex items-center justify-center gap-1">
                                    <span
                                        class="text-base font-[900]"
                                        :class="
                                            parseFloat(row.change_amount) == 0
                                                ? 'text-[#64748B]'
                                                : row.change_amount_desc.indexOf('+') > -1
                                                ? 'text-[#EF4444]'
                                                : 'text-[#16A34A]'
                                        ">
                                        {{
                                            parseFloat(row.change_amount) == 0
                                                ? row.change_amount
                                                : row.change_amount_desc
                                        }}
                                    </span>
                                </div>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn label="变动来源" min-width="200">
                            <template #default="{ row }">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="inline-flex flex-wrap items-center justify-center gap-1">
                                        <span
                                            class="inline-flex px-2.5 py-1 rounded-lg bg-[#F1F6FF] text-primary text-[11px] font-black">
                                            {{ row.type_desc }}
                                        </span>
                                        <!-- 与来源同行,避免固定行高把企业标裁掉 -->
                                        <span
                                            v-if="isTeamRow(row)"
                                            class="inline-flex px-2 py-0.5 rounded-md bg-[#FFF7ED] text-[#EA580C] text-[10px] font-black">
                                            {{ row.team_name ? "企业·" + row.team_name : "企业空间" }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn label="变动详情" min-width="240">
                            <template #default="{ row }">
                                <div class="flex justify-center flex-wrap gap-x-4 gap-y-1" v-if="row.extra">
                                    <div
                                        v-for="(value, key) in row.extra"
                                        :key="key"
                                        class="text-xs flex items-center gap-1">
                                        <span class="text-[#94A3B8] font-medium">{{ key }}:</span>
                                        <span class="text-[#475569] font-medium">{{ value }}</span>
                                    </div>
                                </div>
                                <span v-else class="text-[#CBD5E1] text-xs">--</span>
                            </template>
                        </ElTableColumn>

                        <ElTableColumn label="剩余算力" min-width="160">
                            <template #default="{ row }">
                                <div class="flex justify-center items-center gap-1.5">
                                    <span
                                        v-if="isTeamRow(row)"
                                        class="inline-flex px-1.5 py-0.5 rounded bg-[#FFF7ED] text-[#EA580C] text-[10px] font-black">
                                        团队
                                    </span>
                                    <span class="text-sm font-black text-[#0F172A]">{{ row.left_tokens }}</span>
                                </div>
                            </template>
                        </ElTableColumn>

                        <template #empty>
                            <ElEmpty description="暂无变动记录" />
                        </template>
                    </ElTable>
                </div>

                <div class="flex justify-between items-center mt-6 pt-4 border-t border-[#F8FAFC]">
                    <span class="text-xs font-medium text-[#94A3B8]">共 {{ pager.count }} 条变动详情</span>
                    <pagination v-model="pager" @change="getLists" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getTokensRecord } from "@/api/user";
import { getTeamInfo } from "@/api/team";
import { useUserStore } from "@/stores/user";

const userStore = useUserStore();

const tabsConfig = [
    { label: "消耗记录", name: "tokens", action: 2 },
    { label: "订阅记录", name: "tokens", action: 1 },
];
const activeTab = ref(tabsConfig[0].action);

/** 当前空间:企业则筛该企业流水并展示企业标 */
const spaceTeamId = ref(0);
const spaceTeamName = ref("");

const params = reactive({
    type: "tokens",
    action: 2,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getTokensRecord,
    params,
});

const isTeamRow = (row: any) => Number(row?.is_team) === 1 || Number(row?.team_id) > 0;

const loadSpaceContext = async () => {
    try {
        const info: any = await getTeamInfo();
        if (Number(info?.in_team) === 1 && Number(info?.team_id) > 0) {
            spaceTeamId.value = Number(info.team_id);
            spaceTeamName.value = String(info.name || "");
        } else {
            spaceTeamId.value = 0;
            spaceTeamName.value = "";
        }
    } catch {
        spaceTeamId.value = 0;
        spaceTeamName.value = "";
    }
};

const syncHeaderTokens = () => {
    userStore.refreshTokens();
};

const handleTabChange = (item: any) => {
    const { name, action } = item;
    activeTab.value = action;
    params.type = name;
    params.action = action;
    getLists();
    syncHeaderTokens();
};

const onRefresh = async () => {
    await loadSpaceContext();
    resetPage();
    syncHeaderTokens();
};

onMounted(async () => {
    await loadSpaceContext();
    getLists();
    syncHeaderTokens();
});

watch(
    () => userStore.teamVersion,
    () => {
        loadSpaceContext();
    },
);

definePageMeta({ layout: "base" });
</script>
<style lang="scss">
.custom-tabs {
    @apply flex p-1 bg-[#F1F5F9] rounded-xl;
    .tab-item {
        @apply px-6 py-1.5 rounded-lg text-sm font-black text-[#64748B] cursor-pointer transition-all;
        &.is-active {
            @apply bg-white text-primary;
        }
    }
}
</style>
