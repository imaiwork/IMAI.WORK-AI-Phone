<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.nickname"
                        placeholder="请输入用户"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <!-- 任务状态 -->
                <el-form-item label="任务状态">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.status"
                        placeholder="请选择任务状态"
                        clearable
                        :empty-values="[undefined, null]"
                        @change="resetPage"
                        @keyup.enter="resetPage">
                        <el-option label="全部" value="" />
                        <el-option label="等待处理" value="1" />
                        <el-option label="生成中" value="2" />
                        <el-option label="已生成" value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创作时间">
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
            <div class="mb-4 flex justify-between">
                <el-button
                    v-perms="['ai_application.montage.szr/delete']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
            </div>
            <el-table
                ref="tableRef"
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                row-key="id"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                <el-table-column label="ID" prop="id" min-width="60" />
                <el-table-column label="任务ID" prop="task_id" width="160" show-overflow-tooltip />
                <el-table-column label="创建用户" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="任务名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="任务状态" min-width="120">
                    <template #default="{ row }">
                        <template v-if="row.status == 1">
                            <el-tag type="info">等待处理</el-tag>
                        </template>
                        <template v-if="row.status == 2">
                            <el-tag type="warning">生成中</el-tag>
                        </template>
                        <template v-else-if="row.status == 3">
                            <el-tag type="success">已生成</el-tag>
                        </template>
                    </template>
                </el-table-column>
                <el-table-column label="创作进度" prop="name" min-width="100">
                    <template #default="{ row }"> {{ row.success_num }}/{{ row.video_count }} </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="video_token" min-width="120" />
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.montage.create.szr/detail']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.montage.create.szr/detail'),
                                    query: {
                                        id: row.id,
                                        name: row.name,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.montage.szr/delete']"
                            type="danger"
                            link
                            @click="handleDelete([row.id])">
                            删除
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
<script lang="ts" setup>
import { getMontageCreateRecord, deleteMontageCreateRecord } from "@/api/ai_application/digital_human/montage";
import { getRoutePath } from "@/router";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";

const queryParams = reactive({
    start_time: "",
    end_time: "",
    nickname: "",
    status: "",
    shanjian_type: 1,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getMontageCreateRecord,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteMontageCreateRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
