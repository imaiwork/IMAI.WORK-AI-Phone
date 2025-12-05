<template>
    <el-drawer v-model="drawerVisible" title="分段详情" size="40%" @close="close">
        <div class="h-full flex flex-col">
            <div class="mb-4">
                <el-input v-model="chunkParams.keyword" placeholder="请输入关键词" clearable class="w-[380px]">
                    <template #append>
                        <el-button type="primary" @click="getChunkLists">搜索</el-button>
                    </template>
                </el-input>
            </div>
            <div class="grow min-h-0">
                <el-scrollbar>
                    <el-table :data="chunkPager.lists" :row-style="{ height: '50px' }">
                        <el-table-column label="序号" type="index" width="60" />
                        <el-table-column label="文档内容" prop="question" min-width="200">
                            <template #default="{ row }">
                                <div class="whitespace-pre-line line-clamp-3">
                                    {{ row.question }}
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="补充内容" prop="answer">
                            <template #default="{ row }">
                                <div class="whitespace-pre-line line-clamp-3">
                                    {{ row.answer || "-" }}
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="80" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="handleCheck(row)"> 查看</el-button>
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
    <DataView v-model:show="showDetail" v-model:model-value="detailData" />
</template>

<script setup lang="ts">
import { knowKnowledgeVectorFileDetail } from "@/api/ai_application/knowledge_base/files";
import { usePaging } from "@/hooks/usePaging";
import DataView from "./data-view.vue";
const emit = defineEmits<{
    (e: "close"): void;
}>();

const drawerVisible = ref(false);
const chunkParams = ref<any>({
    kb_id: "",
    fd_id: "",
    keyword: "",
});

const { pager: chunkPager, getLists: getChunkLists } = usePaging({
    fetchFun: knowKnowledgeVectorFileDetail,
    params: chunkParams.value,
});

const showDetail = ref(false);
const detailData = ref<any>({});
const handleCheck = (row: any) => {
    detailData.value = row;
    showDetail.value = true;
};

const open = (data: any) => {
    chunkParams.value.kb_id = data.kb_id;
    chunkParams.value.fd_id = data.id;
    getChunkLists();
    drawerVisible.value = true;
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped></style>
