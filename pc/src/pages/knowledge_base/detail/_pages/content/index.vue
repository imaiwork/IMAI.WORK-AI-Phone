<template>
    <div class="flex flex-col h-full bg-white rounded-[20px]" v-if="!isAddFile && !isDetail">
        <div
            class="flex-shrink-0 h-[88px] flex items-center justify-between gap-x-2 px-[30px] border-b border-[#0000000d]">
            <div class="">
                <span class="font-bold">文档内容 （{{ isRag ? "RAG" : "向量" }}）</span>
                <span class="text-[#00000080]"
                    >知识库的所有文件都在这里显示，整个知识库都可以被系统中的功能引用或通过聊天进行索引</span
                >
            </div>
            <ElButton type="primary" class="!rounded-full !h-10" @click="handleAddFile">
                <Icon name="local-icon-add_circle" />
                <span class="ml-2">添加文件</span>
            </ElButton>
        </div>
        <div class="flex items-center justify-end gap-4 px-[30px] h-[62px]">
            <template v-if="isRag">
                <ElSelect
                    v-model="queryParams.takeover_mode"
                    class="!w-[120px] !h-10"
                    placeholder="请选择"
                    :empty-values="[null, undefined]"
                    @change="getLists()">
                    <ElOption label="全部" value=""></ElOption>
                    <ElOption label="解析中" :value="0"></ElOption>
                    <ElOption label="解析完成" :value="1"></ElOption>
                    <ElOption label="解析失败" :value="2"></ElOption>
                </ElSelect>
                <ElInput
                    v-model="queryParams.name"
                    class="!h-10 !w-[240px]"
                    clearable
                    placeholder="请输入文件名称"
                    @clear="getLists()"
                    @keyup.enter="getLists()">
                </ElInput>
            </template>
            <ElInput
                v-if="isVector"
                v-model="queryParams.keyword"
                class="!h-10 !w-[240px]"
                clearable
                placeholder="请输入文件名称"
                @clear="getLists()"
                @keyup.enter="getLists()">
            </ElInput>
        </div>
        <div class="grow min-h-0">
            <ElTable
                :data="pager.lists"
                v-loading="pager.loading"
                stripe
                height="100%"
                :row-style="{ height: '60px', cursor: 'pointer' }"
                :header-row-style="{ height: '62px' }"
                @row-click="handleEdit">
                <ElTableColumn label="文档名称" prop="name" min-width="200px" />
                <ElTableColumn prop="type" label="文件格式" min-width="100px">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-x-2">
                            <img :src="getFileType(row.type)" v-if="getFileType(row.type)" class="w-5 h-5" />
                            <span>{{ row.type || "-" }}</span>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="文件大小" prop="size" min-width="80px">
                    <template #default="{ row }">
                        {{ formatFileSize(row.size) }}
                    </template>
                </ElTableColumn>
                <ElTableColumn label="上传时间" prop="create_time" width="180px" />
                <ElTableColumn prop="status" label="解析状态" width="120px" v-if="isRag">
                    <template #default="{ row }">
                        <ElTag v-if="row.status == 'INIT'" type="info">待解析</ElTag>
                        <ElTag v-else-if="row.status == 'PARSING'" type="warning">解析中</ElTag>
                        <ElTag v-else-if="row.status == 'PARSE_SUCCESS'" type="success">解析完成</ElTag>
                        <ElTag v-else-if="row.status == 'PARSE_FAILED'" type="danger">解析失败</ElTag>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="操作" prop="action" width="160px" align="right" fixed="right">
                    <template #default="{ row }">
                        <ElButton link type="primary" @click.stop="handleEdit(row)">{{
                            isRag ? "查看" : "编辑"
                        }}</ElButton>
                        <ElButton type="danger" link @click.stop="handleDelete(row)">删除</ElButton>
                    </template>
                </ElTableColumn>
                <template #empty>
                    <div class="flex items-center justify-center h-full">
                        <ElButton class="!h-[50px] !rounded-full w-[200px]" @click="handleAddFile"
                            >点击添加文件</ElButton
                        >
                    </div>
                </template>
            </ElTable>
        </div>
        <div class="flex justify-center p-4">
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
        </div>
    </div>
    <template v-if="isAddFile">
        <rag-panel
            v-if="isRag"
            :kn-id="knId"
            :index-id="index_id"
            :category-id="category_id"
            :kn-name="kn_name"
            @back="back" />
        <vector-panel v-if="isVector" :kn-id="knId" :kn-name="kn_name" @back="back" />
    </template>
    <detail v-if="isDetail" :kn-type="kn_type" :kn-id="knId" @back="back" />
</template>

<script setup lang="ts">
import {
    knowledgeBaseFileLists,
    knowledgeBaseFileDelete,
    vectorKnowledgeBaseFileDelete,
    vectorKnowledgeBaseFileLists,
} from "@/api/knowledge_base";
import { SidebarTypeEnum, KnTypeEnum } from "@/pages/knowledge_base/_enums";
import RagPanel from "./_components/rag-panel.vue";
import VectorPanel from "./_components/vector-panel.vue";
import Detail from "./detail.vue";
import { usePaging } from "@/composables/usePaging";

const route = useRoute();
const router = useRouter();
const nuxtApp = useNuxtApp();

const { kn_type, category_id, index_id, kn_name } = toRefs(route.query);
const knId = computed(() => route.params.id as string);

const isAddFile = ref(false);
const isDetail = ref(false);

const isRag = computed(() => kn_type.value === KnTypeEnum.RAG);
const isVector = computed(() => kn_type.value === KnTypeEnum.VECTOR);

const queryParams = reactive({
    indexid: index_id?.value,
    category_id: category_id?.value,
    name: "",
    takeover_mode: "",
    kb_id: knId.value,
    keyword: "",
    status: "",
});

const { pager, getLists, resetParams } = usePaging({
    fetchFun: (params: any) => (isRag.value ? knowledgeBaseFileLists(params) : vectorKnowledgeBaseFileLists(params)),
    params: queryParams,
});

const getFileType = (type: string) => {
    const importImage = (path: string) => {
        if (!path) return "";
        return new URL(`../../../../../assets/images/${path}.png`, import.meta.url).href;
    };
    switch (type) {
        case "docx":
        case "doc":
            return importImage("docx");
        case "ppt":
        case "pptx":
            return importImage("ppt");
        case "xls":
        case "xlsx":
        case "csv":
            return importImage("excel");
        case "jpg":
        case "jpeg":
            return importImage("jpg");
        case "pdf":
            return importImage("pdf");
        case "md":
            return importImage("txt");
        default:
            return importImage(type);
    }
};

const updateRouteQuery = (query: Record<string, any>) => {
    const newQuery = { ...route.query, ...query };
    for (const key in newQuery) {
        if (newQuery[key] === undefined || newQuery[key] === null) {
            delete newQuery[key];
        }
    }
    router.replace({ query: newQuery });
};

const handleAddFile = () => {
    isAddFile.value = true;
    updateRouteQuery({ is_add_file: "1" });
};

const handleEdit = (row: any) => {
    isDetail.value = true;
    updateRouteQuery({ is_detail: "1", file_name: row.name, file_id: row.id });
};

const handleDelete = (row: any) => {
    nuxtApp.$confirm({
        message: "确定删除该文件吗？",
        onConfirm: async () => {
            try {
                isRag.value
                    ? await knowledgeBaseFileDelete({ id: row.id })
                    : await vectorKnowledgeBaseFileDelete({ fd_id: row.id });
                getLists();
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error as string);
            }
        },
    });
};

const back = () => {
    isAddFile.value = false;
    isDetail.value = false;
    updateRouteQuery({
        is_add_file: undefined,
        is_detail: undefined,
        file_name: undefined,
        file_id: undefined,
    });
    getLists();
};

watch(
    () => route.query,
    (query) => {
        isAddFile.value = query.is_add_file === "1" && parseInt(query.type as string) === SidebarTypeEnum.CONTENT;
        isDetail.value = query.is_detail === "1" && parseInt(query.type as string) === SidebarTypeEnum.CONTENT;
    },
    { immediate: true }
);

onMounted(() => {
    if (!isDetail.value && !isAddFile.value) {
        getLists();
    }
});
</script>

<style scoped lang="scss">
:deep(.el-input) {
    .el-input__wrapper {
        border-radius: 100px;
    }
}
:deep(.el-select) {
    .el-select__wrapper {
        border-radius: 100px;
    }
}
</style>
