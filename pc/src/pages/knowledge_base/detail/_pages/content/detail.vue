<template>
    <div class="h-full flex flex-col min-w-[1000px]">
        <div class="grow flex flex-col bg-white rounded-[24px] border border-br overflow-hidden w-full">
            <div class="flex-shrink-0 h-[72px] flex items-center justify-between px-8 border-b border-br">
                <div class="flex items-center gap-3 cursor-pointer group transition-all" @click="emit('back')">
                    <div
                        class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center group-hover:bg-[#0065fb]/10 group-hover:text-primary transition-all">
                        <Icon name="el-icon-ArrowLeft" :size="16"></Icon>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-medium text-[#94A3B8] leading-none mb-1">返回列表</span>
                        <span class="text-[15px] font-[900] text-[#1E293B]">{{ fileName }}</span>
                    </div>
                </div>

                <ElButton v-if="!isRag" type="primary" class="add-chunk-btn" @click="handleAdd">
                    <Icon name="local-icon-add_circle" :size="16"></Icon>
                    <span class="ml-2">新建分段</span>
                </ElButton>
            </div>

            <div class="flex-shrink-0 h-[64px] flex items-center justify-between px-8 bg-[#f8fafc]/50">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                    <span class="text-[14px] font-[900] text-[#1E293B]">分段列表</span>
                    <span
                        class="px-2 py-0.5 rounded-md bg-white border border-br text-[11px] font-black text-primary ml-1">
                        {{ pager.count }}
                    </span>
                </div>
                <div>
                    <ElInput
                        v-model="queryParams.keywords"
                        prefix-icon="el-icon-Search"
                        class="custom-input"
                        placeholder="搜索分段关键词..."
                        clearable
                        @clear="getLists()"
                        @keydown.enter="getLists()">
                    </ElInput>
                </div>
            </div>

            <div class="grow min-h-0 relative" v-loading="pager.loading">
                <ElScrollbar v-if="pager.lists.length">
                    <div class="p-6 grid grid-cols-1 gap-4">
                        <div
                            v-for="(item, index) in pager.lists"
                            :key="item.id"
                            class="chunk-card group"
                            :class="{ 'is-selected': isChoose(item.uuid) }"
                            @click="handleChoose(item.uuid)">
                            <div v-if="!isRag" class="absolute left-4 top-1/2 -translate-y-1/2">
                                <div class="checkbox-box" :class="{ 'is-checked': isChoose(item.uuid) }">
                                    <Icon v-if="isChoose(item.uuid)" name="el-icon-Check" :size="12" color="white" />
                                </div>
                            </div>

                            <div class="flex-1" :class="[!isRag ? 'pl-10' : '']">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="chunk-index">#{{ index + 1 }}</span>
                                        <span class="text-xs text-[#94A3B8] font-medium">{{
                                            item.create_time || "分段详情"
                                        }}</span>
                                    </div>
                                    <div
                                        v-if="!isRag"
                                        class="action-edit-btn opacity-0 group-hover:opacity-100"
                                        @click.stop="handleEdit(item)">
                                        <Icon name="local-icon-edit3" :size="14"></Icon>
                                    </div>
                                </div>

                                <div class="content-section">
                                    <div class="label">文档内容</div>
                                    <div class="text line-clamp-3">{{ item.content || item.question }}</div>
                                </div>

                                <div v-if="!isRag" class="content-section mt-3 pb-0 border-none">
                                    <div class="label">补充内容</div>
                                    <div class="text italic">{{ item.answer || "无补充内容" }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>

                <div v-else class="h-full flex items-center justify-center opacity-40">
                    <ElEmpty description="暂无分段数据" />
                </div>

                <Transition name="slide-up">
                    <div v-if="chooseList.length > 0 && !isRag" class="floating-batch-bar">
                        <div class="flex items-center gap-4">
                            <span class="text-[13px] font-medium"
                                >已选择 <b class="text-primary mx-1">{{ chooseList.length }}</b> 项分段</span
                            >
                            <div class="w-[1px] h-4 bg-[#E2E8F0]"></div>
                            <ElButton type="danger" link class="!font-black hover:!text-red-500" @click="handleDelete"
                                >批量删除</ElButton
                            >
                            <ElButton link class="!text-[#64748B] !font-black" @click="handleCancel">取消选择</ElButton>
                        </div>
                    </div>
                </Transition>
            </div>

            <div class="flex-shrink-0 py-6 flex justify-center border-t border-[#F1F5F9] bg-slate-50/30">
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
        </div>
    </div>
    <subsection-edit v-if="showEdit" ref="subsectionEditRef" @close="showEdit = false" @success="getLists()" />
</template>

<script setup lang="ts">
import {
    knowledgeBaseFileChunkLists,
    vectorKnowledgeBaseFileChunkLists,
    vectorKnowledgeBaseFileChunkDelete,
} from "@/api/knowledge_base";
import { KnTypeEnum } from "@/pages/knowledge_base/_enums";
import SubsectionEdit from "./_components/subsection-edit.vue";

const props = defineProps<{ knType: any; knId: any }>();
const emit = defineEmits<{ (e: "back"): void }>();

const route = useRoute();
const nuxtApp = useNuxtApp();

const showEdit = ref(false);
const subsectionEditRef = ref<InstanceType<typeof SubsectionEdit>>();
const chooseList = ref<string[]>([]);
const queryParams = reactive({ keywords: "" });

const fileId = ref(route.query.file_id as string);
const fileName = computed(() => route.query.file_name as string);
const isRag = computed(() => props.knType == KnTypeEnum.RAG);

const { pager, getLists } = usePaging({
    fetchFun: (params: any) =>
        isRag.value
            ? knowledgeBaseFileChunkLists({
                  ...params,
                  keywords: queryParams.keywords,
                  id: fileId.value,
              })
            : vectorKnowledgeBaseFileChunkLists({
                  ...params,
                  kb_id: props.knId,
                  fd_id: fileId.value,
                  keyword: queryParams.keywords,
              }),
    params: queryParams,
});
const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    subsectionEditRef.value?.open();
    subsectionEditRef.value?.setFormData({
        fd_id: fileId.value,
        kb_id: props.knId,
    });
};

const handleEdit = async (item: any) => {
    showEdit.value = true;
    await nextTick();
    subsectionEditRef.value?.open("edit");
    subsectionEditRef.value?.getDetail(item.uuid);
};

const isChoose = (uuid: string) => chooseList.value.includes(uuid);

const handleChoose = (uuid: string) => {
    const index = chooseList.value.indexOf(uuid);
    if (index > -1) {
        chooseList.value.splice(index, 1);
    } else {
        chooseList.value.push(uuid);
    }
};

const handleDelete = () => {
    nuxtApp.$confirm({
        message: "确定删除所选分段吗？",
        onConfirm: async () => {
            try {
                await vectorKnowledgeBaseFileChunkDelete({
                    kb_id: props.knId,
                    uuids: chooseList.value,
                });
                feedback.msgSuccess("删除成功");
                chooseList.value = [];
                getLists();
            } catch (error) {
                feedback.msgError(error as string);
            }
        },
    });
};

const handleCancel = () => {
    chooseList.value = [];
};

watch(
    () => route.query.file_id,
    (val) => {
        fileId.value = val as string;
        if (val) getLists();
    },
    { immediate: true }
);
</script>

<style scoped lang="scss">
.add-chunk-btn {
    @apply rounded-xl h-10 px-6 font-black border-none bg-primary;
    box-shadow: 0 4px 12px -2px rgba(var(--el-color-primary), 0.3);
    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px -2px rgba(var(--el-color-primary), 0.4);
    }
}

.chunk-card {
    @apply relative p-5 bg-white border border-br rounded-[20px] transition-all cursor-pointer select-none;

    &:hover {
        background-color: #f8fafc;
    }

    &.is-selected {
        @apply border-primary bg-[#F0F6FF];
    }
}

.chunk-index {
    @apply px-2 py-0.5 rounded bg-[#1E293B] text-white text-[10px] font-black italic;
}

.checkbox-box {
    @apply w-5 h-5 rounded-md border-2 border-br bg-white flex items-center justify-center transition-all;
    &.is-checked {
        @apply border-primary bg-primary;
    }
}

.content-section {
    @apply border-l-2 border-[#F1F5F9] pl-3;
    .label {
        @apply text-[11px] font-black text-[#94A3B8] uppercase tracking-wider mb-1;
    }
    .text {
        @apply text-[13px] text-[#475569] leading-6;
    }
}

.action-edit-btn {
    @apply w-8 h-8 rounded-lg bg-white border border-br flex items-center justify-center text-[#64748B] hover:text-primary hover:border-primary transition-all;
}

.floating-batch-bar {
    @apply absolute bottom-6 left-1/2 -translate-x-1/2 bg-white border border-br px-6 py-3 rounded-full flex items-center z-[100];
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translate(-50%, 20px);
    opacity: 0;
}
</style>
