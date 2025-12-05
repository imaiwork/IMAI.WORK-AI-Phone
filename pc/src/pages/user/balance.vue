<template>
    <div class="p-5 h-full">
        <div class="bg-body h-full flex rounded-[12px] flex-col p-5">
            <div class="flex justify-between items-center gap-x-5">
                <div class="grow">
                    <ElTabs v-model="activeTab" @tab-change="handleTabChange">
                        <ElTabPane label="消耗记录" name="tokens" />
                        <ElTabPane label="订阅记录" name="balance" />
                    </ElTabs>
                </div>
                <div>
                    <ElButton type="primary" @click="resetPage">
                        <Icon name="el-icon-Refresh" :size="16" color="#ffffff"></Icon>
                    </ElButton>
                </div>
            </div>
            <div class="mt-4 flex-1 min-h-0 flex flex-col">
                <div class="flex-1 min-h-0">
                    <ElTable
                        :data="pager.lists"
                        height="100%"
                        stripe
                        :row-style="{ height: '60px' }"
                        :header-cell-style="{ height: '63px' }"
                        v-loading="pager.loading">
                        <ElTableColumn label="变动日期" prop="create_time"></ElTableColumn>
                        <ElTableColumn label="变动数量" prop="change_amount_desc"></ElTableColumn>
                        <ElTableColumn label="变动来源" prop="type_desc"></ElTableColumn>
                        <ElTableColumn label="变动详情">
                            <template #default="{ row }">
                                <div class="" v-if="row.extra">
                                    <div v-for="(value, key) in row.extra">{{ key }}：{{ value }}</div>
                                </div>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="剩余算力" prop="left_tokens"></ElTableColumn>
                        <template #empty>
                            <ElEmpty />
                        </template>
                    </ElTable>
                </div>
                <div class="flex justify-end mt-4">
                    <pagination v-model="pager" @change="getLists" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getTokensRecord } from "@/api/user";
import { useUserStore } from "~/stores/user";

const { userInfo } = useUserStore();
const activeTab = ref("tokens");

const params = reactive<{
    type: "tokens" | "balance";
    action: number;
}>({
    type: "tokens",
    action: 2,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getTokensRecord,
    params,
});

const handleTabChange = (tab: string) => {
    if (tab === "tokens") {
        params.action = 2;
    } else {
        params.action = 1;
    }
    getLists();
};

getLists();
definePageMeta({
    layout: "base",
});
</script>

<style scoped></style>
