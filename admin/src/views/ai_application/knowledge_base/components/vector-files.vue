<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="文件名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入文件名称"
                        clearable
                        @keyup.enter="getLists()" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="getLists()">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4 flex items-center justify-between">
                <el-button type="primary" @click="handleImport">添加新文件</el-button>
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
                        {{ formatFileSize(row.size || 0) }}
                    </template>
                </el-table-column>
                <el-table-column label="最近更新时间" prop="update_time" min-width="180" />
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleChunkDetail(row)"> 编辑内容 </el-button>
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
    <vector-data ref="vectorDataRef" v-if="showVectorData" @close="showVectorData = false" />
    <vector-import ref="vectorImportRef" :kb-id="queryParams.kb_id" :kb-name="knName" @success="handleImportSuccess" />
</template>
<script lang="ts" setup>
import { knowKnowledgeVectorFileList, knowKnowledgeVectorFileDelete } from "@/api/ai_application/knowledge_base/files";
import { formatFileSize } from "@/utils/util";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import VectorData from "./vector-data.vue";
import VectorImport from "./vector-import.vue";
const router = useRoute();

const queryParams = reactive({
    kb_id: router.query.id as string,
    name: "",
});
const knName = computed(() => (router.query.name as string) || "");

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: knowKnowledgeVectorFileList,
    params: queryParams,
});

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await knowKnowledgeVectorFileDelete({ fids: id });
    getLists();
};

const vectorDataRef = ref<any>(null);
const showVectorData = ref(false);
const vectorImportRef = shallowRef<InstanceType<typeof VectorImport>>();

const handleChunkDetail = async (row: any) => {
    showVectorData.value = true;
    await nextTick();
    vectorDataRef.value.open({ kb_id: row.kb_id, id: row.id });
};

const handleImport = () => {
    vectorImportRef.value?.open();
};

const handleImportSuccess = () => {
    getLists();
};

onMounted(async () => {
    queryParams.kb_id = router.query.id as string;
    getLists();
});
</script>
