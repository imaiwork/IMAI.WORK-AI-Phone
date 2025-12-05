<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="名称">
                    <el-input
                        class="w-[180px]"
                        v-model="queryParams.name"
                        placeholder="请输入名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select
                        class="!w-[160px]"
                        v-model="queryParams.status"
                        placeholder="请选择状态"
                        clearable
                        :empty-values="[null, undefined]"
                        @change="getLists()">
                        <el-option label="全部" value="" />
                        <el-option label="草稿箱" :value="GenStatus.DRAFT" />
                        <el-option label="待处理" :value="GenStatus.WAITING" />
                        <el-option label="生成中" :value="GenStatus.GENERATING" />
                        <el-option label="已完成" :value="GenStatus.SUCCESS" />
                        <el-option label="失败" :value="GenStatus.FAILED" />
                        <el-option label="部分完成" :value="GenStatus.PARTIAL_SUCCESS" />
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
                    v-perms="['ai_application.redbook.digital_human/delete']"
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
                <el-table-column type="selection" width="55" fixed="left" />
                <el-table-column label="ID" prop="id" width="100" />
                <el-table-column label="创建用户" prop="nickname" min-width="160" />
                <el-table-column label="创作名称" prop="name" min-width="180" />
                <el-table-column label="生成状态" min-width="100">
                    <template #default="{ row }">
                        {{ getStatusText(row.status) }}
                    </template>
                </el-table-column>
                <el-table-column label="生成数量" min-width="100">
                    <template #default="{ row }"> {{ row.success_num || 0 }}/{{ row.video_count }} </template>
                </el-table-column>
                <el-table-column label="费用记录" min-width="100">
                    <template #default="{ row }"> {{ row.all_token }}算力 </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.redbook.digital_human/detail']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.redbook.digital_human/detail'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.redbook.digital_human/delete']"
                            type="danger"
                            link
                            :disabled="
                                ![GenStatus.SUCCESS, GenStatus.PARTIAL_SUCCESS, GenStatus.DRAFT].includes(row.status)
                            "
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
import { getDigitalHumanList, deleteDigitalHuman } from "@/api/ai_application/redbook";
import feedback from "@/utils/feedback";
import { getRoutePath } from "@/router";

const router = useRouter();

const queryParams = reactive({
    name: "",
    start_time: "",
    end_time: "",
    status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDigitalHumanList,
    params: queryParams,
});

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

const multipleSelection = ref<any[]>([]);

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
    await deleteDigitalHuman({ id });
    getLists();
};

getLists();
</script>
