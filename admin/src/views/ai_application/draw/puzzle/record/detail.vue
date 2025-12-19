<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header @back="$router.back()" :content="route.meta.title" />
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
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
                    v-perms="['ai_application.draw.puzzle.record.detail/delete']"
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
                <el-table-column label="拼图类型" prop="type_text" min-width="120"> </el-table-column>
                <el-table-column label="图片" min-width="250">
                    <template #default="{ row }">
                        <div class="grid grid-cols-4 gap-2">
                            <div v-for="item in row.puzzle_url" class="w-[60px] h-[60px]">
                                <image-contain
                                    :src="item"
                                    width="60"
                                    height="60"
                                    fit="cover"
                                    :preview-src-list="[item]"
                                    preview-teleported></image-contain>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="img_token" min-width="120">
                    <template #default="{ row }"> {{ row.img_token || 0 }}算力 </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ai_application.draw.puzzle.record.detail/delete']"
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
import { getPuzzleTaskRecordList, deletePuzzleTaskRecord } from "@/api/ai_application/draw/draw_records";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";

const route = useRoute();

const queryParams = reactive({
    user: "",
    start_time: "",
    end_time: "",
    puzzle_setting_id: route.query.id,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPuzzleTaskRecordList,
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
    await deletePuzzleTaskRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
