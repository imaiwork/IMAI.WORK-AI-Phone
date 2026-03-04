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
                            @click="handleTabChange(tab.name)"
                            class="tab-item"
                            :class="{ 'is-active': activeTab === tab.name }">
                            {{ tab.label }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <ElButton
                        class="!w-10 !h-10 !p-0 !rounded-xl !border-[#E2E8F0] hover:!border-primary hover:!text-primary transition-all"
                        @click="resetPage">
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

                        <ElTableColumn label="变动来源" min-width="140">
                            <template #default="{ row }">
                                <div
                                    class="inline-flex px-2.5 py-1 rounded-lg bg-[#F1F6FF] text-primary text-[11px] font-black">
                                    {{ row.type_desc }}
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

                        <ElTableColumn label="剩余算力" min-width="140">
                            <template #default="{ row }">
                                <div class="flex justify-center items-center gap-1.5">
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

const activeTab = ref("tokens");
const tabsConfig = [
    { label: "消耗记录", name: "tokens" },
    { label: "订阅记录", name: "balance" },
];

const params = reactive({
    type: "tokens",
    action: 2,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getTokensRecord,
    params,
});

const handleTabChange = (name: string) => {
    activeTab.value = name;
    params.type = name as any;
    params.action = name === "tokens" ? 2 : 1;
    getLists();
};

getLists();
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
