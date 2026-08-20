<template>
    <el-drawer v-model="drawerVisible" title="文档内容" size="72%" @close="close">
        <div class="h-full flex flex-col">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <el-button type="primary" @click="handleAdd">新建分段</el-button>
                </div>
                <el-input
                    v-model="chunkParams.keyword"
                    placeholder="请输入关键词"
                    clearable
                    class="w-[360px]"
                    @clear="getChunkLists"
                    @keyup.enter="getChunkLists">
                    <template #append>
                        <el-button type="primary" @click="getChunkLists">搜索</el-button>
                    </template>
                </el-input>
            </div>
            <div class="grow min-h-0">
                <el-scrollbar>
                    <el-table
                        ref="tableRef"
                        :data="chunkPager.lists"
                        row-key="uuid"
                        v-loading="chunkPager.loading"
                        @selection-change="handleSelectionChange">
                        <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                        <el-table-column label="序号" type="index" width="60" />
                        <el-table-column label="文档内容" prop="question" min-width="240">
                            <template #default="{ row }">
                                <div class="whitespace-pre-line line-clamp-3">
                                    {{ row.question }}
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="补充内容" prop="answer" min-width="220">
                            <template #default="{ row }">
                                <div class="whitespace-pre-line line-clamp-3">
                                    {{ row.answer || "-" }}
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="创建时间" prop="create_time" min-width="170" show-overflow-tooltip />
                        <el-table-column label="操作" width="170" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="handleCheck(row)">查看</el-button>
                                <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
                                <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-scrollbar>
            </div>
            <div class="flex justify-end mt-4">
                <pagination v-model="chunkPager" @change="getChunkLists" />
            </div>
        </div>
    </el-drawer>
    <data-view v-model:show="showDetail" v-model:model-value="detailData" />
    <chunk-edit ref="chunkEditRef" v-if="showChunkEdit" @close="showChunkEdit = false" @success="handleEditSuccess" />
</template>

<script setup lang="ts">
import { ElTable } from "element-plus";
import {
    knowKnowledgeVectorFileChunkDelete,
    knowKnowledgeVectorFileChunkList,
} from "@/api/ai_application/knowledge_base/files";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import DataView from "./data-view.vue";
import ChunkEdit from "./chunk-edit.vue";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const drawerVisible = ref(false);
const tableRef = shallowRef<InstanceType<typeof ElTable>>();
const chunkEditRef = shallowRef<InstanceType<typeof ChunkEdit>>();
const showChunkEdit = ref(false);
const multipleSelection = ref<any[]>([]);

const chunkParams = reactive({
    kb_id: "",
    fd_id: "",
    keyword: "",
});

const { pager: chunkPager, getLists: getChunkLists } = usePaging({
    fetchFun: knowKnowledgeVectorFileChunkList,
    params: chunkParams,
});

const showDetail = ref(false);
const detailData = ref<any>({});

const handleCheck = (row: any) => {
    detailData.value = row;
    showDetail.value = true;
};

const handleSelectionChange = (value: any[]) => {
    multipleSelection.value = value;
};

const handleAdd = async () => {
    showChunkEdit.value = true;
    await nextTick();
    chunkEditRef.value?.open("add");
    chunkEditRef.value?.setFormData({
        kb_id: chunkParams.kb_id,
        fd_id: chunkParams.fd_id,
    });
};

const handleEdit = async (row: any) => {
    showChunkEdit.value = true;
    await nextTick();
    chunkEditRef.value?.open("edit");
    await chunkEditRef.value?.getDetail(row.uuid);
    chunkEditRef.value?.setFormData({
        kb_id: row.kb_id,
        fd_id: row.fd_id,
        uuid: row.uuid,
    });
};

const handleEditSuccess = () => {
    showChunkEdit.value = false;
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
    getChunkLists();
};

const handleDelete = async (row: any) => {
    await feedback.confirm("确定要删除所选分段吗？");
    await knowKnowledgeVectorFileChunkDelete({
        kb_id: row.kb_id,
        uuids: [row.uuid],
    });
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
    getChunkLists();
};

const open = (data: any) => {
    chunkParams.kb_id = data.kb_id;
    chunkParams.fd_id = data.id;
    chunkParams.keyword = "";
    multipleSelection.value = [];
    drawerVisible.value = true;
    getChunkLists();
};

const close = () => {
    drawerVisible.value = false;
    emit("close");
};

defineExpose({
    open,
});
</script>
