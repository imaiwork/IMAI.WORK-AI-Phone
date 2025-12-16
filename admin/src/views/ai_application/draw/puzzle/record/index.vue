<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.nickname"
                        placeholder="请输入用户昵称"
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
                        <el-option label="部分完成" value="4" />
                    </el-select>
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
            <div class="mb-4 flex justify-between">
                <el-button
                    v-perms="['ai_application.draw.puzzle.record/delete']"
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
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.user_avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="任务名称" prop="name" min-width="180" show-overflow-tooltip> </el-table-column>
                <el-table-column label="素材数量" prop="material_num" width="100"> </el-table-column>
                <el-table-column label="文案数量" prop="copywriting_num" width="100" />
                <el-table-column label="生成数量" prop="result_count" width="100" />
                <el-table-column label="实际生成数量" prop="success_puzzle_count" width="100" />
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
                        <template v-else-if="row.status == 4">
                            <el-tag type="success">部分完成</el-tag>
                        </template>
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="puzzle_token" min-width="120">
                    <template #default="{ row }"> {{ row.puzzle_token || 0 }}算力 </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.montage/create_detail']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.draw.puzzle.record/detail'),
                                    query: {
                                        id: row.id,
                                        name: row.name,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.draw.puzzle.record/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)">
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
import { getPuzzleTaskList, deletePuzzleTask } from "@/api/ai_application/draw/draw_records";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";
import { getRoutePath } from "@/router";

const queryParams = reactive({
    nickname: "",
    start_time: "",
    end_time: "",
    status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPuzzleTaskList,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDetail = async (row: any) => {};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deletePuzzleTask({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
