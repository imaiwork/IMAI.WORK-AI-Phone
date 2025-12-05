<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.founder"
                        placeholder="请输入用户昵称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="岗位名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
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
            <div class="mb-4 flex gap-4">
                <el-button
                    v-perms="['ai_application.interview.job/delete']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
            </div>
            <el-table
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed />
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="岗位名称" prop="name" min-width="180" />
                <el-table-column label="岗位头像" min-width="100">
                    <template #default="{ row }">
                        <image-contain
                            :src="row.avatar"
                            radius="50%"
                            width="48"
                            height="48"
                            preview-teleported
                            :preview-src-list="[row.avatar]" />
                    </template>
                </el-table-column>
                <el-table-column label="招聘公司" prop="company" min-width="140" show-overflow-tooltip />
                <el-table-column label="面试方式" width="100">
                    <template #default="{ row }">
                        {{ row.type === 1 ? "文字" : "语音" }}
                    </template>
                </el-table-column>
                <el-table-column label="创建用户" prop="user" min-width="140" show-overflow-tooltip />
                <el-table-column label="面试人数" prop="interview_user_num" width="100" />
                <el-table-column label="消耗算力" prop="chat_score" width="120">
                    <template #default="{ row }"> {{ row.chat_score }}算力 </template>
                </el-table-column>
                <el-table-column label="状态" width="100" v-perms="['ai_application.interview.job/status']">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="changeStatus(row)" />
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.interview.job/detail']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.interview.job/detail'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.interview.job/delete']"
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
import { formatAudioTime } from "@/utils/util";
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import feedback from "@/utils/feedback";
import { getInterviewJobList, interviewJobDelete, interviewJobStatus } from "@/api/ai_application/interview/job";
const queryParams = reactive({
    name: "",
    founder: "",
    start_time: "",
    end_time: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getInterviewJobList,
    params: queryParams,
});

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await interviewJobDelete({ id });
    getLists();
};

const changeStatus = async (row: any) => {
    try {
        await interviewJobStatus({ id: row.id, status: row.status });
    } finally {
        getLists();
    }
};

getLists();
</script>
