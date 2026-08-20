<template>
    <div>
        <el-card shadow="never" class="!border-none">
            <el-form ref="formRef" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="!w-[260px]"
                        v-model="queryParams.user_keyword"
                        placeholder="请输入用户ID/编号/昵称"
                        clearable />
                </el-form-item>
                <el-form-item label="代理类型">
                    <el-select class="!w-[200px]" v-model="queryParams.is_agent" placeholder="全部">
                        <el-option value="" label="全部" />
                        <el-option value="1" label="仅代理用户" />
                        <el-option value="0" label="仅普通用户" />
                    </el-select>
                </el-form-item>
                <el-form-item label="代理状态">
                    <el-select class="!w-[200px]" v-model="queryParams.status" placeholder="全部">
                        <el-option value="" label="全部" />
                        <el-option value="1" label="正常" />
                        <el-option value="0" label="已冻结" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists" class="mt-2">
                <el-table-column label="用户昵称" min-width="220">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <el-avatar :size="40" :src="row?.avatar" class="flex-none mr-2">
                                {{ (row.nickname || "?").slice(0, 1) }}
                            </el-avatar>
                            <div>
                                <div>{{ row.nickname || "—" }}</div>
                                <div class="text-xs text-gray-400">ID: {{ row.user_id }} · SN: {{ row.sn || "—" }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="上级邀请人" prop="parent_nickname" min-width="160" />
                <el-table-column label="代理等级" min-width="120">
                    <template #default="{ row }">
                        <el-tag v-if="row.level > 0" type="primary">
                            {{ gradeList.find((g: any) => g.level == row.level)?.name || `${row.level}级` }}
                        </el-tag>
                        <el-tag v-else type="info">普通用户</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.status == 1 ? 'success' : 'warning'">
                            {{ row.status == 1 ? "正常" : "已冻结" }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="注册时间" prop="create_time" sortable min-width="170" />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="goDetail(row)">详情</el-button>
                        <el-button type="primary" link @click="goLower(row)" :disabled="row.level <= 0">
                            下级列表
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import { getAgentGradeConfig, getAgentUserLowerList } from "@/api/marketing/agent";
import { usePaging } from "@/hooks/usePaging";

const router = useRouter();

// 不传 user_id → 后端 subLists 走"全站代理"分支(setSearch 跳过 parent_id 过滤)
const queryParams = reactive({
    user_keyword: "",
    is_agent: "1",   // 默认只看代理用户
    status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getAgentUserLowerList,
    params: queryParams,
});

const gradeList = ref<any[]>([]);
const loadGrades = async () => {
    gradeList.value = (await getAgentGradeConfig()) as any[];
};

function goDetail(row: any) {
    router.push({ path: "/marketing/agent/detail", query: { id: row.user_id } });
}
function goLower(row: any) {
    router.push({ path: "/marketing/agent/lowerDetail", query: { id: row.user_id } });
}

getLists();
loadGrades();
</script>
