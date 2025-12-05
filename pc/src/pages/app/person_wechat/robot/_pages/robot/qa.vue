<template>
    <div class="h-full flex flex-col">
        <div class="flex items-center justify-between px-4">
            <div>
                <ElButton type="primary" @click="handleAdd">新增问答话术</ElButton>
                <ElButton type="primary" @click="handleImport"> 批量上传话术 </ElButton>
                <ElButton type="primary" @click="handleExport"> 导出话术列表 </ElButton>
            </div>
            <div>
                <ElInput v-model="queryParams.name" placeholder="请输入关键词" class="">
                    <template #append>
                        <ElButton @click="getLists()">
                            <Icon name="el-icon-Search" :size="16"></Icon>
                        </ElButton>
                    </template>
                </ElInput>
            </div>
        </div>
        <div class="grow min-h-0 mt-4">
            <ElTable
                v-model="pager.lists"
                v-loading="pager.loading"
                stripe
                height="100%"
                :row-style="{ height: '60px' }">
                <ElTableColumn prop="id" label="ID" width="60" fixed="left"></ElTableColumn>
                <ElTableColumn label="问题" min-width="160"></ElTableColumn>
                <ElTableColumn label="回答" min-width="300"></ElTableColumn>
                <ElTableColumn label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <ElButton link type="primary" @click="handleEdit(row)"> 编辑 </ElButton>
                        <ElButton link type="danger" @click="handleDelete(row.id)"> 删除 </ElButton>
                    </template>
                </ElTableColumn>
                <template #empty>
                    <ElEmpty description="暂无数据"></ElEmpty>
                </template>
            </ElTable>
        </div>
        <div class="flex justify-end p-4">
            <pagination v-model="pager" @change="getLists"></pagination>
        </div>
    </div>
    <EditPopup ref="editPopupRef" v-if="showEdit" @close="showEdit = false" @success="getLists" />
    <ImportDataPopup
        ref="importDataPopupRef"
        title="批量上传话术"
        v-if="showImport"
        @close="showImport = false"
        @success="getLists" />
</template>

<script setup lang="ts">
import EditPopup from "./edit.vue";
import ImportDataPopup from "../../../_components/import-data.vue";

const nuxtApp = useNuxtApp();
const queryParams = reactive({
    name: "",
});

const { pager, getLists } = usePaging({
    fetchFun: async () => {},
    params: queryParams,
});

const showEdit = ref<boolean>(false);
const editPopupRef = ref<InstanceType<typeof EditPopup>>();

const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    editPopupRef.value?.open();
};

const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    editPopupRef.value?.open(row);
};

const handleDelete = (id: number) => {
    nuxtApp.$confirm({
        message: "确定删除该问答话术吗？",
        onConfirm: async () => {
            try {
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError("删除失败");
            }
        },
    });
};

const showImport = ref<boolean>(false);
const importDataPopupRef = ref<InstanceType<typeof ImportDataPopup>>();
const handleImport = async () => {
    showImport.value = true;
    await nextTick();
    importDataPopupRef.value?.open();
};

const handleExport = async () => {};
</script>

<style scoped></style>
