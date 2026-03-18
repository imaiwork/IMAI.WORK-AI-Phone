<template>
    <div>
        <el-card shadow="never" class="!border-none">
            <el-page-header content="下级人数列表" @back="$router.back()" />
        </el-card>

        <el-card shadow="never" class="!border-none mt-[10px]">
            <div class="text-xl font-medium">下级列表</div>
            <el-form ref="formRef" class="mt-4" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="!w-[280px]"
                        v-model="queryParams.user_keyword"
                        placeholder="请输入用户ID编号/用户昵称"
                        clearable />
                </el-form-item>
                <el-form-item label="代理状态">
                    <el-select class="!w-[280px]" v-model="queryParams.status">
                        <el-option value>全部</el-option>
                        <el-option value="1" label="未冻结">未冻结</el-option>
                        <el-option value="0" label="已冻结">已冻结</el-option>
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
            <el-tabs v-model="activeTab" @tab-change="handleTabChange">
                <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                    <el-table-column label="用户昵称" prop="nickname" min-width="190">
                        <template #default="{ row }">
                            <div class="flex items-center">
                                <div class="flex-none mr-2">
                                    <el-avatar :size="50" :src="row?.avatar">
                                        {{ row.nickname }}
                                    </el-avatar>
                                </div>
                                {{ row.nickname }}
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="上级邀请人" prop="parent_nickname" min-width="190" />
                    <el-table-column label="代理等级" min-width="190">
                        <template #default="{ row }">
                            <div>
                                {{ gradeList.find((item: any) => item.level == row.level)?.name || "普通用户" }}
                            </div>
                        </template>
                    </el-table-column>

                    <el-table-column label="代理状态" width="140">
                        <template #default="{ row }">
                            <el-tag :type="row.status == 1 ? 'success' : 'warning'">{{
                                row.status == 1 ? "正常" : "已冻结"
                            }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="注册时间" prop="create_time" sortable min-width="190" />
                </el-table>
            </el-tabs>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>
<script setup lang="ts">
import { getAgentGradeConfig, getAgentUserLowerList } from "@/api/marketing/agent";
import { usePaging } from "@/hooks/usePaging";

const route = useRoute();
const queryParams = reactive({
    user_id: route.query.id,
    user_keyword: "",
    is_agent: "",
    status: "",
    hierarchy: "",
});
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getAgentUserLowerList,
    params: queryParams,
});
const activeTab = ref(0);
const tabLists = ref([
    {
        name: "全部",
        numKey: "all",
    },
    {
        name: "下一级人数",
        numKey: "level1",
    },
    {
        name: "下二级人数",
        numKey: "level2",
    },
]);
const handleTabChange = (index: any) => {
    queryParams.hierarchy = tabLists.value[index].numKey as string;
    resetPage();
};

const gradeList = ref<any[]>([]);
const getGradeList = async () => {
    const res = await getAgentGradeConfig();
    gradeList.value = res;
};

getLists();
getGradeList();
</script>
