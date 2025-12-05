<template>
    <el-drawer v-model="drawerVisible" title="切片详情" size="70%" @close="close">
        <div class="h-full flex flex-col">
            <div class="mb-4 flex justify-end">
                <el-input
                    v-model="queryParams.name"
                    placeholder="请输入文件内容"
                    clearable
                    class="w-[380px]"
                    @clear="getLists"
                    @keyup.enter="getLists">
                    <template #append>
                        <el-button type="primary" @click="getLists">搜索</el-button>
                    </template>
                </el-input>
            </div>
            <div class="grow min-h-0">
                <el-scrollbar>
                    <el-table :data="pager.lists" :row-style="{ height: '50px' }" v-loading="pager.loading">
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
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>
    </el-drawer>
</template>

<script setup lang="ts">
import { usePaging } from "@/hooks/usePaging";
import { knowKnowledgeChunkDetail } from "@/api/ai_application/knowledge_base/files";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const drawerVisible = ref(false);
const queryParams = ref<any>({
    id: "",
    name: "",
});

const { pager, getLists } = usePaging({
    fetchFun: knowKnowledgeChunkDetail,
    params: queryParams.value,
});

const open = (id: number) => {
    queryParams.value.id = id;
    drawerVisible.value = true;
    getLists();
};

const close = () => {
    drawerVisible.value = false;
    emit("close");
};

defineExpose({
    open,
    close,
});
</script>

<style scoped></style>
