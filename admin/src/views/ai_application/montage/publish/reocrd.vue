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

                <el-form-item label="状态">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.status"
                        placeholder="请选择状态"
                        clearable
                        @change="getLists()">
                        <el-option label="进行中" value="1" />
                        <el-option label="已完成" value="2" />
                        <el-option label="暂停" value="4" />
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
                    v-perms="['ai_application.montage.publish_record/delete']"
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
                <el-table-column label="用户" prop="user_nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="发布账号" width="180">
                    <template #default="{ row }">
                        <div class="break-all">
                            <div v-for="(item, index) in row.accounts" :key="index">
                                {{ item.type == 1 ? "个微" : "小红书" }}：{{ item.account }}
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="任务名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="任务状态" min-width="120">
                    <template #default="{ row }">
                        <template v-if="row.status == 1 || row.status == 2">
                            <el-tag type="warning">进行中</el-tag>
                        </template>
                        <template v-else-if="row.status == 3">
                            <el-tag type="success">已完成</el-tag>
                        </template>
                        <template v-else-if="row.status == 4">
                            <el-tag type="info">已暂停</el-tag>
                        </template>
                        <template v-else>-</template>
                    </template>
                </el-table-column>
                <el-table-column label="发布周期" prop="name" min-width="100">
                    <template #default="{ row }">
                        <span>{{ row.publish_cycle }}天</span>
                    </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.montage/publish_detail']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.montage/publish_detail'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-if="row.status || 1 || row.status == 2 || row.status == 4"
                            v-perms="['ai_application.montage.publish_record/start:pause']"
                            type="primary"
                            link
                            @click="handleChangeStatus(row)">
                            {{ row.status == 2 || row.status == 1 ? "暂停" : "开始" }}
                        </el-button>
                        <el-button
                            v-perms="['ai_application.montage.publish_record/delete']"
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
import {
    getMontageRecord,
    deleteMontageRecord,
    changeMontageRecordStatus,
} from "@/api/ai_application/digital_human/montage";
import { getRoutePath } from "@/router";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";

const queryParams = reactive({
    nickname: "",
    start_time: "",
    end_time: "",
    status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getMontageRecord,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleChangeStatus = async (row: any) => {
    await changeMontageRecordStatus({ id: row.id, status: row.status == 1 ? 4 : 1 });
    getLists();
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteMontageRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
