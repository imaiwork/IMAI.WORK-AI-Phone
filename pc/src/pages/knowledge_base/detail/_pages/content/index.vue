<template>
    <div class="flex flex-col h-full min-w-[1000px]" v-if="!isAddFile && !isDetail">
        <div class="flex flex-col h-full bg-white rounded-[24px] border border-br overflow-hidden mx-auto w-full">
            <div class="flex-shrink-0 h-[90px] flex items-center justify-between px-8 border-b border-br">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <span class="text-[18px] font-[900] text-[#1E293B]">文档内容</span>
                        <span class="px-2 py-0.5 rounded-md bg-[#0065fb]/10 text-primary text-[11px] font-black italic">
                            {{ isRag ? "RAG ENGINE" : "VECTOR DB" }}
                        </span>
                    </div>
                    <div class="text-[13px] font-medium text-[#94A3B8]">
                        管理知识库文件，系统将根据这些文档进行语义索引与对话回复。
                    </div>
                </div>
                <ElButton type="primary" class="add-doc-btn" @click="handleAddFile">
                    <Icon name="local-icon-add_circle" :size="18" />
                    <span class="ml-2">添加新文件</span>
                </ElButton>
            </div>

            <div class="flex items-center justify-between px-8 h-[72px] bg-[#f8fafc]/50">
                <div class="flex items-center gap-2">
                    <div class="w-1 h-4 bg-primary rounded-full"></div>
                    <span class="text-[14px] font-[900] text-[#475569]">所有文档 ({{ pager.count }})</span>
                </div>

                <div class="flex items-center gap-3">
                    <template v-if="isRag">
                        <ElSelect
                            v-model="queryParams.takeover_mode"
                            class="custom-select !w-[130px]"
                            placeholder="解析状态"
                            @change="getLists()">
                            <ElOption label="全部状态" value=""></ElOption>
                            <ElOption label="解析中" :value="0"></ElOption>
                            <ElOption label="解析完成" :value="1"></ElOption>
                            <ElOption label="解析失败" :value="2"></ElOption>
                        </ElSelect>
                        <ElInput
                            v-model="queryParams.name"
                            class="custom-input !w-[260px]"
                            clearable
                            placeholder="输入文件名搜索..."
                            @clear="getLists()"
                            @keyup.enter="getLists()">
                            <template #prefix><Icon name="el-icon-Search" /></template>
                        </ElInput>
                    </template>

                    <ElInput
                        v-if="isVector"
                        v-model="queryParams.keyword"
                        class="custom-input !w-[260px]"
                        clearable
                        placeholder="快速检索文档..."
                        @clear="getLists()"
                        @keyup.enter="getLists()">
                        <template #prefix><Icon name="el-icon-Search" /></template>
                    </ElInput>
                </div>
            </div>

            <div class="grow min-h-0">
                <ElTable
                    :data="pager.lists"
                    v-loading="pager.loading"
                    height="100%"
                    class="custom-table"
                    :header-cell-style="{
                        background: 'transparent',
                        color: '#64748B',
                        fontSize: '12px',
                        fontWeight: '900',
                    }"
                    :header-row-style="{ height: '64px' }"
                    @row-click="handleEdit">
                    <ElTableColumn label="文档名称" min-width="240px">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-3 cursor-pointer">
                                <div
                                    class="w-10 h-10 rounded-xl bg-[#F1F5F9] flex items-center justify-center border border-br">
                                    <img :src="getFileType(row.type)" class="w-6 h-6 object-contain" />
                                </div>
                                <span
                                    class="text-[14px] font-medium text-[#1E293B] hover:text-primary transition-colors"
                                    >{{ row.name }}</span
                                >
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn prop="type" label="格式" width="100px" align="center">
                        <template #default="{ row }">
                            <span
                                class="px-2 py-1 rounded bg-[#F1F5F9] text-[10px] font-black text-[#64748B] uppercase">
                                {{ row.type || "N/A" }}
                            </span>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="大小" width="100px">
                        <template #default="{ row }">
                            <span class="text-[13px] font-medium text-[#64748B]">{{ formatFileSize(row.size) }}</span>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="上传节点" width="180px">
                        <template #default="{ row }">
                            <div class="text-xs text-[#94A3B8] font-medium">{{ row.create_time }}</div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn prop="status" label="解析状态" width="140px" v-if="isRag">
                        <template #default="{ row }">
                            <div class="status-badge" :class="row.status">
                                <span class="status-dot"></span>
                                {{
                                    row.status === "INIT"
                                        ? "待解析"
                                        : row.status === "PARSING"
                                        ? "解析中"
                                        : row.status === "PARSE_SUCCESS"
                                        ? "已完成"
                                        : "失败"
                                }}
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="操作" width="150px" align="right" fixed="right">
                        <template #default="{ row }">
                            <div class="flex justify-end gap-2">
                                <ElButton
                                    link
                                    type="primary"
                                    class="!font-black !text-[13px]"
                                    @click.stop="handleEdit(row)">
                                    {{ isRag ? "查看" : "编辑" }}
                                </ElButton>
                                <div class="w-[1px] h-3 bg-[#E2E8F0] self-center"></div>
                                <ElButton
                                    link
                                    type="danger"
                                    class="!font-black !text-[13px]"
                                    @click.stop="handleDelete(row)">
                                    删除
                                </ElButton>
                            </div>
                        </template>
                    </ElTableColumn>

                    <template #empty>
                        <div class="py-20 flex flex-col items-center justify-center grayscale opacity-60">
                            <Icon name="local-icon-empty" :size="100" />
                            <p class="text-[14px] font-medium text-[#94A3B8] mt-4">
                                没有任何文档，点击上方按钮开始上传
                            </p>
                        </div>
                    </template>
                </ElTable>
            </div>

            <div class="flex-shrink-0 h-[70px] flex items-center justify-center border-t border-[#F1F5F9]">
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
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
.add-doc-btn {
    @apply rounded-xl   h-[44px] px-6 font-black text-[15px] border-none transition-all;
    box-shadow: 0 8px 16px -4px rgba(var(--el-color-primary), 0.4);
    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 20px -4px rgba(var(--el-color-primary), 0.5);
    }
}

.custom-table {
    @apply border-none;
    :deep(.el-table__inner-wrapper::before) {
        display: none;
    }
    :deep(.el-table__row) {
        @apply transition-colors;
        &:hover td {
            background-color: #f8fafc !important;
        }
    }
    :deep(td.el-table__cell) {
        @apply border-b border-[#F1F5F9] py-4;
    }
}

.status-badge {
    @apply inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black border;
    .status-dot {
        @apply w-1.5 h-1.5 rounded-full;
    }

    &.INIT {
        @apply bg-[#f9fafb] text-[#6b7280] border-[##e5e7eb];
        .status-dot {
            @apply bg-[#9ca3af];
        }
    }
    &.PARSING {
        @apply bg-[#fffbeb] text-[#d97706] border-[#fef3c7];
        .status-dot {
            @apply bg-[#f59e0b];
        }
    }
    &.PARSE_SUCCESS {
        @apply bg-[#ecfdf5] text-[#059669] border-[#d1fae5];
        .status-dot {
            @apply bg-[#10b981];
        }
    }
    &.PARSE_FAILED {
        @apply bg-[#fef2f2] text-[#dc2626] border-[#fee2e2];
        .status-dot {
            @apply bg-[#ef4444];
        }
    }
}
</style>
