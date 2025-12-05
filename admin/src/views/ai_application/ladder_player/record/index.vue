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
                <el-form-item label="场景名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入场景名称"
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
                    v-perms="['ai_application.lp.record/delete']"
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
                <el-table-column label="场景名称" prop="scene_name" min-width="180" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.ask_user_avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="创建用户" prop="ask_user_name" min-width="140" show-overflow-tooltip />
                <el-table-column label="练习时长" width="100">
                    <template #default="{ row }">
                        {{ formatAudioTime(row.duration) }}
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="points" min-width="120">
                    <template #default="{ row }"> {{ row.status == 3 ? 0 : row.points || 0 }}算力 </template>
                </el-table-column>
                <el-table-column label="当前状态" width="120">
                    <template #default="{ row }">
                        <el-tag type="success" v-if="row.status == 2">成功</el-tag>
                        <el-tag type="danger" v-else-if="row.status == 3">失败</el-tag>
                        <el-tag type="info" v-else>转写中</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="start_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="row.status == 2"
                            v-perms="['ai_application.lp.record/detail']"
                            type="primary"
                            link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.lp.record/detail'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.lp.record/delete']"
                            type="danger"
                            link
                            @click="handleDelete([row.id])">
                            删除
                        </el-button>
                        <el-button v-if="row.status == 3" type="primary" link @click="handleReanalysis(row.id)">
                            重新分析
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
import { getLpRecordLists, lpRecordDelete, lpAnalysisReanalysis } from "@/api/ai_application/ladder_player/record";
import { formatAudioTime } from "@/utils/util";
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";
const queryParams = reactive({
    name: "",
    user: "",
    start_time: "",
    end_time: "",
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getLpRecordLists,
    params: queryParams,
});

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await lpRecordDelete({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const handleReanalysis = async (id: number | number[]) => {
    await feedback.confirm("确定要重新分析吗？");
    try {
        feedback.loading("重新分析中...");
        await lpAnalysisReanalysis({ id });
        getLists();
    } finally {
        feedback.closeLoading();
    }
};

getLists();
</script>
