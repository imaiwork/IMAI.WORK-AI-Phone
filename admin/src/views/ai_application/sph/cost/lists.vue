<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user"
                        placeholder="请输入用户昵称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="创建时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-table ref="tableRef" size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="执行用户" prop="nickname" min-width="160" />
                <el-table-column label="执行设备" prop="device_codes" width="180" show-overflow-tooltip />
                <el-table-column label="任务名称" prop="name" min-width="150" />
                <el-table-column label="关键词执行数量">
                    <template #default="{ row }">
                        {{ row.number_of_implemented_keywords }}/{{ row.implementation_keywords_number }}
                    </template>
                </el-table-column>
                <el-table-column label="当前执行数量">
                    <template #default="{ row }"> {{ row.current_progress }}/{{ row.total_progress }} </template>
                </el-table-column>
                <el-table-column label="执行状态" width="120">
                    <template #default="{ row }">
                        <el-tag type="info" v-if="row.status == 0">未执行</el-tag>
                        <el-tag type="primary" v-else-if="row.status == 1">进行中</el-tag>
                        <el-tag type="danger" v-else-if="row.status == 2">暂停中</el-tag>
                        <el-tag type="success" v-else-if="row.status == 3">已完成</el-tag>
                        <el-tag type="info" v-else-if="row.status == 4">已结束</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="points" min-width="120">
                    <template #default="{ row }"> {{ row.tokens || 0 }}算力 </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row.keywords)">查看关键词</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>
<script lang="ts" setup>
import { getCostRecord } from "@/api/ai_application/sph";
import { usePaging } from "@/hooks/usePaging";
import { ElTable, ElMessageBox } from "element-plus";
const queryParams = reactive({
    name: "",
    user: "",
    start_time: "",
    end_time: "",
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getCostRecord,
    params: queryParams,
});

const handleDetail = (keywords: string) => {
    if (!keywords) return;
    keywords = JSON.parse(keywords).join("、");
    ElMessageBox({
        title: "关键词",
        message: h("p", null, keywords),
    });
};

getLists();
</script>
