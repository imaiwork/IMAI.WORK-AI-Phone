<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="状态">
                    <el-select
                        class="!w-[120px]"
                        v-model="queryParams.status"
                        placeholder="请选择状态"
                        :empty-values="[null, undefined]"
                        clearable
                        @change="getLists()">
                        <el-option label="全部" value="" />
                        <el-option label="解析中" value="PARSING" />
                        <el-option label="解析成功" value="PARSE_SUCCESS" />
                        <el-option label="解析失败" value="PARSE_FAILED" />
                    </el-select>
                </el-form-item>
                <el-form-item label="文件名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入文件名称"
                        clearable
                        @keyup.enter="getLists()" />
                </el-form-item>
                <el-form-item label="导入时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="getLists()">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4">
                <el-button
                    v-perms="['ai_application.kn.files/del']"
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
                row-key="id"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column prop="name" label="文件名称" min-width="140" show-overflow-tooltip></el-table-column>
                <el-table-column prop="type" label="文件格式" min-width="100" show-overflow-tooltip></el-table-column>
                <el-table-column label="文件大小" min-width="80" show-overflow-tooltip>
                    <template #default="{ row }">
                        {{ formatFileSize(row.size) }}
                    </template>
                </el-table-column>
                <el-table-column label="解析状态" prop="status_text" width="120" show-overflow-tooltip>
                    <template #default="{ row }">
                        <el-tag v-if="row.status == 'INIT'" type="info">待解析</el-tag>
                        <el-tag v-else-if="row.status == 'PARSING'" type="warning">解析中</el-tag>
                        <el-tag v-else-if="row.status == 'PARSE_SUCCESS'" type="success">解析完成</el-tag>
                        <el-tag v-else-if="row.status == 'PARSE_FAILED'" type="danger">解析失败</el-tag>
                    </template>
                </el-table-column>
                <el-table-column
                    label="导入时间"
                    prop="create_time"
                    width="180"
                    show-overflow-tooltip></el-table-column>
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleChunkDetail(row.id)"> 查看切片 </el-button>
                        <el-button
                            v-perms="['ai_application.kn.files/del']"
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
    <rag-data ref="ragDataRef" v-if="showRagData" @close="showRagData = false" />
</template>
<script lang="ts" setup>
import { getKnowledgeTrainingFiles, deleteKnowledgeTrainingFile } from "@/api/ai_application/knowledge_base/files";
import { formatFileSize } from "@/utils/util";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import RagData from "./rag-data.vue";
const router = useRoute();

const queryParams = reactive({
    id: "",
    status: "",
    name: "",
    start_time: "",
    end_time: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getKnowledgeTrainingFiles,
    params: queryParams,
});

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteKnowledgeTrainingFile({ id });
    getLists();
};

const ragDataRef = ref<any>(null);
const showRagData = ref(false);

const handleChunkDetail = async (id: number) => {
    showRagData.value = true;
    await nextTick();
    ragDataRef.value.open({ id });
};

onMounted(async () => {
    queryParams.id = router.query.id as string;

    getLists();
});
</script>
