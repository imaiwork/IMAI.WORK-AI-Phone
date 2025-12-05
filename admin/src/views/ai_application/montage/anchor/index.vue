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
                    v-perms="['ai_application.montage.anchor/delete']"
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
                <el-table-column label="创建用户" prop="nickname" min-width="140" show-overflow-tooltip />
                <!-- <el-table-column label="形象名称" prop="name" min-width="180" show-overflow-tooltip /> -->
                <el-table-column label="形象视频" prop="name" min-width="140">
                    <template #default="{ row }">
                        <div
                            class="line-clamp-1 text-primary hover:underline cursor-pointer"
                            @click="jumpUrl(row.anchor_url)">
                            {{ row.anchor_url }}
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="授权视频" min-width="140">
                    <template #default="{ row }">
                        <div
                            class="line-clamp-1 text-primary hover:underline cursor-pointer"
                            @click="jumpUrl(row.authorized_url)">
                            {{ row.anchor_url }}
                        </div>
                    </template>
                </el-table-column>
                <!-- <el-table-column label="消耗算力" prop="name" min-width="120" /> -->
                <el-table-column label="说明" prop="remark" min-width="160"></el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ai_application.montage.anchor/delete']"
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
import { getMontageAnchorList, deleteMontageAnchor } from "@/api/ai_application/digital_human/montage";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";

const queryParams = reactive({
    nickname: "",
    start_time: "",
    end_time: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getMontageAnchorList,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteMontageAnchor({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const jumpUrl = (url: string) => {
    window.open(url, "_blank");
};

getLists();
</script>
