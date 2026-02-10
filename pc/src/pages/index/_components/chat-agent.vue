<template>
    <div class="px-3">
        <div class="flex items-center gap-x-2">
            <ElInput v-model="search" placeholder="搜索智能体" :prefix-icon="Search" />
            <div class="shrink-0 w-[38px] h-[38px] flex items-center justify-center rounded-lg bg-[#F1F5F9]">
                <Icon name="el-icon-Plus" :size="16"></Icon>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Search } from "@element-plus/icons-vue";
import { getAllAgentList } from "@/api/agent";

const search = ref("");

const { pager, getLists } = usePaging({
    fetchFun: getAllAgentList,
    params: {},
});
getLists();
</script>

<style scoped></style>
