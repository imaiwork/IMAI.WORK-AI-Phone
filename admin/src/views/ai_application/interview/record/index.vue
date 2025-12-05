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
                <el-form-item label="岗位名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.job_name"
                        placeholder="请输入岗位名称"
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
            <div class="mb-4">
                <el-button
                    v-perms="['ai_application.interview.record/del']"
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
                <el-table-column prop="user" label="用户" min-width="140" show-overflow-tooltip></el-table-column>
                <el-table-column
                    prop="interview_name"
                    label="面试者"
                    min-width="140"
                    show-overflow-tooltip></el-table-column>
                <el-table-column
                    prop="job_name"
                    label="面试岗位"
                    min-width="160"
                    show-overflow-tooltip></el-table-column>
                <el-table-column label="学历" prop="degree" min-width="120" show-overflow-tooltip></el-table-column>
                <el-table-column label="工作年限" prop="work_years" width="100" show-overflow-tooltip></el-table-column>
                <el-table-column label="AI评分" width="120" prop="best_score" show-overflow-tooltip></el-table-column>
                <el-table-column label="消耗算力" prop="chat_score" width="120">
                    <template #default="{ row }"> {{ row.chat_score }}算力 </template>
                </el-table-column>
                <el-table-column label="面试时长" width="120" prop="duration" show-overflow-tooltip> </el-table-column>
                <el-table-column label="面试状态" prop="status_text" width="120" show-overflow-tooltip>
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">
                            {{ row.status_text }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column
                    label="面试时间"
                    prop="create_time"
                    width="200"
                    show-overflow-tooltip></el-table-column>

                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.interview.record/detail']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.interview.record/detail'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.interview.record/del']"
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
import { getInterviewRecordList, deleteInterviewRecord } from "@/api/ai_application/interview/record";
import { formatAudioTime } from "@/utils/util";
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";
const queryParams = reactive({
    job_name: "",
    user: "",
    start_time: "",
    end_time: "",
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getInterviewRecordList,
    params: queryParams,
});

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteInterviewRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const getStatusType = (status: number) => {
    if (status == 1) return "success";
    if (status == 2) return "warning";
    if (status == 3) return "warning";
    if (status == 6) return "error";
    return "primary";
};

getLists();
</script>
