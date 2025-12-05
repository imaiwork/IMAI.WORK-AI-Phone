<template>
    <div class="h-full flex flex-col">
        <div class="flex items-center justify-between px-4">
            <div>
                <ElButton type="primary" @click="handleAdd">新增关键词话术</ElButton>
                <ElButton type="primary" @click="handleImport"> 批量上传回复 </ElButton>
            </div>
            <div class="flex items-center gap-x-2">
                <ElInput
                    class="h-[34px] !w-[250px]"
                    v-model="queryParams.keyword"
                    placeholder="请输入关键词"
                    clearable
                    @clear="getLists()"
                    @keyup.enter="getLists()">
                    <template #append>
                        <ElButton @click="getLists()">
                            <Icon name="el-icon-Search"></Icon>
                        </ElButton>
                    </template>
                </ElInput>
            </div>
        </div>
        <div class="grow min-h-0 mt-4">
            <ElTable :data="pager.lists" v-loading="pager.loading" stripe height="100%" :row-style="{ height: '60px' }">
                <ElTableColumn label="匹配模式" width="120">
                    <template #default="{ row }">
                        {{ row.match_type === 0 ? "模糊匹配" : "精确匹配" }}
                    </template>
                </ElTableColumn>
                <ElTableColumn label="匹配内容" prop="keyword" min-width="200"></ElTableColumn>
                <ElTableColumn label="回复内容" min-width="300">
                    <template #default="{ row }">
                        <div class="flex flex-col gap-y-2">
                            <div v-for="(item, index) in row.reply" :key="index" class="text-sm w-full">
                                <div
                                    v-if="item.type == MaterialTypeEnum.TEXT"
                                    class="text-center inline-block bg-[var(--el-color-primary-light-9)] text-primary rounded-lg p-2 text-xs">
                                    {{ item.content }}
                                </div>
                                <div
                                    v-if="item.type == MaterialTypeEnum.IMAGE"
                                    class="bg-[var(--el-color-primary-light-9)] inline-block rounded-lg p-2 leading-[0]">
                                    <ElImage
                                        :src="item.content"
                                        :preview-src-list="[item.content]"
                                        preview-teleported
                                        class="w-14 h-14" />
                                </div>
                                <div
                                    v-if="item.type == MaterialTypeEnum.MINI_PROGRAM"
                                    class="w-[200px] h-[150px] mx-auto bg-[var(--el-color-primary-light-9)] rounded-lg p-2">
                                    <mini-program-card
                                        :title="item.content.title"
                                        :pic="item.content.pic"
                                        :link="item.content.link" />
                                </div>
                                <div
                                    v-if="item.type == MaterialTypeEnum.LINK"
                                    class="w-[200px] max-h-[150px] bg-[var(--el-color-primary-light-9)] rounded-lg p-2 relative text-left inline-block">
                                    <link-card
                                        :title="item.content.name"
                                        :desc="item.content.desc"
                                        :img="item.content.img" />
                                </div>
                                <div
                                    v-if="item.type == MaterialTypeEnum.VIDEO"
                                    class="bg-[var(--el-color-primary-light-9)] inline-block rounded-lg p-2 leading-[0] relative">
                                    <video :src="item.content" class="max-w-[200px]"></video>
                                    <div
                                        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
                                        @click="handlePreview(item.content)">
                                        <play-btn />
                                    </div>
                                </div>
                                <div
                                    v-if="item.type == MaterialTypeEnum.FILE"
                                    class="bg-[var(--el-color-primary-light-9)] inline-block rounded-lg p-2">
                                    <file-card :name="item.content.name" :url="item.content.url" />
                                </div>
                            </div>
                        </div>
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
    <edit-pop v-if="showEdit" ref="editPopupRef" @close="showEdit = false" @success="getLists" />
    <import-data-popup
        v-if="showImport"
        ref="importDataPopupRef"
        type="speech"
        title="批量上传话术"
        @close="showImport = false"
        @success="getLists" />
    <preview-video v-if="showPreview" ref="previewVideoRef" @close="showPreview = false" />
</template>

<script setup lang="ts">
import { robotKeywordsLists, deleteRobotKeywords } from "@/api/person_wechat";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import EditPop from "./keywords-edit.vue";
import ImportDataPopup from "../../../_components/import-data.vue";
import MiniProgramCard from "../../../_components/mini-program-card.vue";
import LinkCard from "../../../_components/link-card.vue";
import FileCard from "../../../_components/file-card.vue";
const route = useRoute();
const nuxtApp = useNuxtApp();

const queryParams = reactive<{
    keyword: string;
    robot_id: string;
}>({
    keyword: "",
    robot_id: "",
});

const { pager, getLists, resetParams } = usePaging({
    fetchFun: robotKeywordsLists,
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

const handleDelete = (id: number) => {
    nuxtApp.$confirm({
        message: "确定删除该问答话术吗？",
        onConfirm: async () => {
            try {
                await deleteRobotKeywords({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError("删除失败");
            }
        },
    });
};

const showPreview = ref<boolean>(false);
const previewVideoRef = ref();

const handlePreview = async (url: string) => {
    showPreview.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};
const showImport = ref<boolean>(false);
const importDataPopupRef = ref<InstanceType<typeof ImportDataPopup>>();
const handleImport = async () => {
    showImport.value = true;
    await nextTick();
    importDataPopupRef.value?.open();
};

watch(
    () => route.query.id,
    async (newVal) => {
        if (newVal) {
            queryParams.robot_id = newVal as string;
            getLists();
        }
    },
    {
        immediate: true,
    }
);
</script>

<style scoped></style>
