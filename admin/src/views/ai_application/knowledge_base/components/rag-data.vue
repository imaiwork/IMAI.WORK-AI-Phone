<template>
    <el-drawer v-model="drawerVisible" title="分段详情" size="40%" @close="close">
        <div class="h-full flex flex-col">
            <div class="mb-4">
                <el-input
                    v-model="chunkParams.name"
                    placeholder="请输入关键词"
                    clearable
                    class="w-[380px]"
                    @clear="getChunkLists"
                    @keyup.enter="getChunkLists">
                    <template #append>
                        <el-button type="primary" @click="getChunkLists">搜索</el-button>
                    </template>
                </el-input>
            </div>
            <div class="grow min-h-0">
                <el-scrollbar>
                    <el-table :data="chunkPager.lists" :row-style="{ height: '50px' }">
                        <el-table-column label="序号" type="index" width="60" />
                        <el-table-column label="文件内容" prop="content">
                            <template #default="{ row }">
                                <div class="p-2 bg-primary-light-9 leading-7 rounded-lg">
                                    <div class="whitespace-pre-line">
                                        {{ row.content }}
                                    </div>
                                </div>
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
</template>

<script setup lang="ts">
import { knowKnowledgeChunkDetail } from "@/api/ai_application/knowledge_base/files";
import { usePaging } from "@/hooks/usePaging";
const emit = defineEmits<{
    (e: "close"): void;
}>();

const drawerVisible = ref(false);
const chunkParams = reactive({
    id: "",
    name: "",
});

const { pager: chunkPager, getLists: getChunkLists } = usePaging({
    fetchFun: knowKnowledgeChunkDetail,
    params: chunkParams,
});

const open = (data: any) => {
    chunkParams.id = data.id;
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
