<template>
    <div class="h-full flex flex-col bg-white rounded-lg">
        <div class="bg-white rounded-lg p-4">
            <ElInput
                v-model="chunkQueryParams.keywords"
                class="h-[32px] !w-[240px]"
                clearable
                placeholder="请输入关键词"
                @clear="
                    chunkQueryParams.keywords = '';
                    getLists();
                "
                @keyup.enter="getLists()">
                <template #append>
                    <ElButton @click="getLists()">
                        <Icon name="el-icon-Search"></Icon>
                    </ElButton>
                </template>
            </ElInput>
        </div>
        <div class="grow min-h-0 overflow-hidden">
            <ElTable :data="pager.lists" height="100%" stripe v-loading="pager.loading">
                <ElTableColumn type="index" label="序号" width="80px" />
                <ElTableColumn prop="content" label="切片内容" min-width="350">
                    <template #default="{ row }">
                        <div class="p-2 bg-primary-light-9 leading-7 rounded-lg">
                            <div class="line-clamp-2">
                                {{ row.content }}
                            </div>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn prop="create_time" label="创建时间" width="180px"> </ElTableColumn>
                <ElTableColumn label="操作" width="100px" fixed="right">
                    <template #default="{ row }">
                        <ElButton link type="primary" @click="handleView(row.content)">查看</ElButton>
                    </template>
                </ElTableColumn>
                <template #empty>
                    <ElEmpty description="暂无数据" />
                </template>
            </ElTable>
        </div>
        <div class="flex justify-end p-4">
            <pagination v-model="pager" @change="getLists"></pagination>
        </div>
    </div>
</template>

<script setup lang="ts">
import { knowledgeBaseFileChunkLists } from "@/api/knowledge_base";
const chunkQueryParams = reactive({
    id: "",
    keywords: "",
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: knowledgeBaseFileChunkLists,
    params: chunkQueryParams,
});

const handleView = (content: string) => {
    ElMessageBox.alert(content, "切片内容", {
        confirmButtonText: "确定",
        showCancelButton: false,
        customClass: "min-w-[600px] !rounded-lg",
    });
};

const setFormData = (data: any) => {
    chunkQueryParams.id = data.id;
    getLists();
};

defineExpose({
    setFormData,
});
</script>

<style scoped></style>
