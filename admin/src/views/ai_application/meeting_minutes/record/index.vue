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
                <el-form-item label="会议名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入会议名称"
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
                    v-perms="['ai_application.meeting_minutes.record/delete']"
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
                <el-table-column label="会议标题" prop="name" min-width="180" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.user_avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="创建用户" prop="user_name" min-width="180" show-overflow-tooltip />
                <el-table-column label="选择语种" prop="language_name" min-width="120" />
                <el-table-column label="会议时长" width="100">
                    <template #default="{ row }">
                        {{ getAudioTime(row) }}
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="points" min-width="120">
                    <template #default="{ row }">
                        {{ row.status == 4 ? row.points : 0 }}
                    </template>
                </el-table-column>
                <el-table-column label="当前状态" width="120">
                    <template #default="{ row }">
                        <el-tag type="info" v-if="row.status == 3">转写中</el-tag>
                        <el-tag type="success" v-else-if="row.status == 4">成功</el-tag>
                        <el-tag type="danger" v-else-if="row.status == 5">失败</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.meeting_minutes.record/detail']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.meeting_minutes.record/detail'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.meeting_minutes.record/delete']"
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
import { getMeetingRecordList, deleteMeetingRecord } from "@/api/ai_application/meeting_minutes";
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

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getMeetingRecordList,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteMeetingRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const getAudioTime = (row: any) => {
    const { AudioInfo } = row.response?.Result?.Transcription?.Transcription || {};
    if (!AudioInfo) return "-";
    return formatAudioTime(AudioInfo.Duration / 1000);
};

getLists();
</script>
