<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="任务名称">
                    <el-input
                        class="w-[180px]"
                        v-model="queryParams.name"
                        placeholder="请输入任务名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="任务状态">
                    <el-select class="!w-[160px]" v-model="queryParams.status" placeholder="请选择状态" clearable>
                        <el-option label="草稿箱" :value="GenStatus.DRAFT" />
                        <el-option label="待处理" :value="GenStatus.WAITING" />
                        <el-option label="生成中" :value="GenStatus.GENERATING" />
                        <el-option label="已完成" :value="GenStatus.SUCCESS" />
                        <el-option label="失败" :value="GenStatus.FAILED" />
                        <el-option label="部分完成" :value="GenStatus.PARTIAL_SUCCESS" />
                    </el-select>
                </el-form-item>
                <el-form-item label="任务类型">
                    <el-select class="!w-[130px]" v-model="queryParams.media_type" placeholder="请选择类型" clearable>
                        <el-option label="图片" :value="CreationType.IMAGE" />
                        <el-option label="视频" :value="CreationType.VIDEO" />
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
                    v-perms="['ai_application.redbook.creation/delete']"
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
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" />
                <el-table-column label="ID" prop="id" width="100" />
                <el-table-column label="创建用户" prop="user_nickname" min-width="160" />
                <el-table-column label="任务名称" prop="name" min-width="300" />
                <el-table-column label="发布账号" min-width="240">
                    <template #default="{ row }">
                        <div v-if="row.account">
                            {{ row.account }} <template v-if="row.nickname"> ({{ row.nickname }}) </template>
                        </div>
                        <div v-else>-</div>
                    </template>
                </el-table-column>
                <el-table-column label="任务状态" min-width="100">
                    <template #default="{ row }">
                        {{ getStatusText(row.status) }}
                    </template>
                </el-table-column>
                <el-table-column label="任务类型" prop="" width="100">
                    <template #default="{ row }">
                        {{ row.media_type == 1 ? "视频" : "图片" }}
                    </template>
                </el-table-column>
                <ElTableColumn label="发布周期" min-width="100">
                    <template #default="{ row }">
                        <div>{{ getPublishCycle(row) }}</div>
                    </template>
                </ElTableColumn>
                <el-table-column prop="next_publish_time" label="下组视频发布点" width="180">
                    <template #default="{ row }">
                        <div>{{ row.next_publish_time || "-" }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="发布进度" prop="" min-width="100">
                    <template #default="{ row }"> {{ row.published_count || 0 }}/{{ row.count }} </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.redbook.creation/record']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.redbook.creation/record'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.redbook.creation/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)"
                            >删除</el-button
                        >
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
import { usePaging } from "@/hooks/usePaging";
import { getPublishList, deletePublish } from "@/api/ai_application/redbook";
import feedback from "@/utils/feedback";
import { getRoutePath } from "@/router";
import { dayjs, ElTable } from "element-plus";

const router = useRouter();

const queryParams = reactive({
    name: "",
    start_time: "",
    end_time: "",
    type: 3,
    status: "",
    media_type: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPublishList,
    params: queryParams,
});

enum CreationType {
    IMAGE = 2, // 图片
    VIDEO = 1, // 视频
}

enum GenStatus {
    DRAFT = 0, // 草稿箱
    WAITING = 1, // 待处理
    GENERATING = 2, // 生成中
    SUCCESS = 3, // 已完成
    FAILED = 4, // 生成失败
    PARTIAL_SUCCESS = 5, // 部分完成
}

// 获取对应状态文本
const getStatusText = (status: number) => {
    const statusMap = {
        [GenStatus.DRAFT]: "草稿箱",
        [GenStatus.WAITING]: "待处理",
        [GenStatus.GENERATING]: "生成中",
        [GenStatus.SUCCESS]: "已完成",
        [GenStatus.FAILED]: "失败",
        [GenStatus.PARTIAL_SUCCESS]: "部分完成",
    } as Record<number, string>;
    return statusMap[status];
};

// 获取发布周期
const getPublishCycle = (row: any) => {
    const { publish_start, publish_end } = row;
    if (publish_start && publish_end) {
        return dayjs(publish_end).diff(dayjs(publish_start), "day") + 1 + "天";
    }
    return "-";
};

const multipleSelection = ref<any[]>([]);

const tableRef = ref<InstanceType<typeof ElTable>>();

const handleSelectionChange = (val: any[]) => {
    const row = val[0];
    if (![GenStatus.SUCCESS, GenStatus.PARTIAL_SUCCESS, GenStatus.DRAFT].includes(row.status)) {
        feedback.msgError("当前视频正在处理中，不可选中");
        return;
    }
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deletePublish({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
