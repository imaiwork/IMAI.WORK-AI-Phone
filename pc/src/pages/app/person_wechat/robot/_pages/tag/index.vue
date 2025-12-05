<template>
    <div class="h-full flex flex-col bg-white rounded-xl">
        <div class="flex items-center justify-between p-4">
            <div>
                <ElButton type="primary" @click="handleAdd">添加标签</ElButton>
                <ElButton type="primary" @click="handleImport"> 批量导入标签 </ElButton>
            </div>
            <div class="flex items-center gap-x-2">
                <ElInput
                    class="!w-[250px]"
                    v-model="queryParams.tag_name"
                    clearable
                    placeholder="请输入标签名称"
                    @clear="getLists()">
                    <template #append>
                        <ElButton @click="getLists()">
                            <Icon name="el-icon-Search"></Icon>
                        </ElButton>
                    </template>
                </ElInput>
            </div>
        </div>
        <div class="grow flex flex-col min-h-0 rounded-xl">
            <div class="grow min-h-0">
                <ElTable
                    :data="pager.lists"
                    v-loading="pager.loading"
                    stripe
                    height="100%"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '60px' }">
                    <ElTableColumn label="匹配模式" width="120">
                        <template #default="{ row }">
                            {{ row.match_type == 0 ? "模糊匹配" : "精确匹配" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="匹配对象" prop="keywords" min-width="120">
                        <template #default="{ row }">
                            {{ row.match_mode == 0 ? "AI回复" : "客户回复" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="匹配关键词" prop="match_keywords" min-width="200"></ElTableColumn>
                    <ElTableColumn label="匹配标签" min-width="140">
                        <template #default="{ row }">
                            <ElTag type="primary" plain>{{ row.tag_name }}</ElTag>
                        </template>
                    </ElTableColumn>
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
    </div>
    <EditPop v-if="showEdit" ref="editPopupRef" @close="showEdit = false" @success="getLists" />
    <ImportDataPop
        v-if="showImport"
        ref="importDataPopupRef"
        title="批量上传标签"
        type="tags"
        @close="showImport = false"
        @success="getLists" />
</template>

<script setup lang="ts">
import { tagLists, deleteTag } from "@/api/person_wechat";
import ImportDataPop from "../../../_components/import-data.vue";
import EditPop from "./edit.vue";

const nuxtApp = useNuxtApp();
const queryParams = reactive<{
    tag_name: string;
}>({
    tag_name: "",
});

const { pager, getLists, resetParams } = usePaging({
    fetchFun: tagLists,
    params: queryParams,
});

const showEdit = ref<boolean>(false);
const editPopupRef = ref<InstanceType<typeof EditPop>>();

const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    editPopupRef.value?.open();
};

const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    editPopupRef.value?.open("edit");
    editPopupRef.value?.setFormData(row);
};

const showImport = ref<boolean>(false);
const importDataPopupRef = ref<InstanceType<typeof ImportDataPop>>();
const handleImport = async () => {
    showImport.value = true;
    await nextTick();
    importDataPopupRef.value?.open();
};

const handleDelete = (id: string) => {
    nuxtApp.$confirm({
        message: "确定删除该标签吗？",
        onConfirm: async () => {
            try {
                await deleteTag({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

getLists();
</script>

<style scoped lang="scss"></style>
