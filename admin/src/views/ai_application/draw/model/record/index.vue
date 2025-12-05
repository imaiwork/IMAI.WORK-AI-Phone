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
                <el-form-item label="创作类型">
                    <el-select
                        v-model="type"
                        class="!w-[160px]"
                        placeholder="请选择创作类型"
                        clearable
                        @change="changeType">
                        <el-option label="商品图" value="1" />
                        <el-option label="模特换衣图" value="2" />
                        <el-option label="海报图" value="5" />
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
                    v-perms="['ai_application.draw_model.record/delete']"
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
                <el-table-column label="ID" prop="id" min-width="80" fixed="left" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="创作类型" prop="type_name" min-width="120" />
                <el-table-column label="消耗算力" prop="points" min-width="120"></el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button
                            v-perms="['ai_application.draw_model.record/delete']"
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
    <detail-pop ref="detailPopRef" v-if="showDetailPop" @close="showDetailPop = false"></detail-pop>
</template>
<script lang="ts" setup>
import { getDrawRecordList, delDrawRecord } from "@/api/ai_application/draw/draw_records";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import DetailPop from "./detail.vue";
import { ElTable } from "element-plus";

const type = ref();

const queryParams = reactive({
    user: "",
    start_time: "",
    end_time: "",
    type: [1, 2, 5],
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDrawRecordList,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const changeType = (val: any) => {
    queryParams.type = [val];
};
const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const showDetailPop = ref(false);
const detailPopRef = ref<InstanceType<typeof DetailPop>>();

const handleDetail = async (row: any) => {
    showDetailPop.value = true;
    await nextTick();
    detailPopRef.value?.open(row);
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await delDrawRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
