<template>
    <div class="h-full bg-white rounded-[20px] flex flex-col">
        <div
            class="flex-shrink-0 h-[88px] flex items-center justify-between gap-x-2 px-[30px] border-b border-[#0000000d]">
            <div class="flex items-center gap-2 cursor-pointer" @click="emit('back')">
                <Icon name="el-icon-ArrowLeft"></Icon>
                <div>{{ fileName }}</div>
            </div>
            <ElButton type="primary" class="!rounded-full !h-10" @click="handleAdd" v-if="!isRag">
                <Icon name="local-icon-add_circle"></Icon>
                <span class="ml-2">添加分段</span>
            </ElButton>
        </div>
        <div class="grow min-h-0 flex flex-col">
            <div
                class="flex-shrink-0 h-[62px] flex items-center justify-between gap-x-2 px-[30px] border-b border-[#0000000d]">
                <div class="flex items-center gap-x-2">
                    <span class="inline-block w-[6px] h-[6px] rounded-full bg-primary"></span>
                    <span>{{ pager.count }}分段</span>
                </div>
                <ElInput
                    v-model="queryParams.keywords"
                    prefix-icon="el-icon-Search"
                    class="!w-[240px] search-name-input"
                    placeholder="请输入关键词"
                    clearable
                    @clear="getLists()"
                    @keydown.enter="getLists()">
                </ElInput>
            </div>
            <div class="grow min-h-0" v-loading="pager.loading">
                <ElScrollbar v-if="pager.lists.length">
                    <div class="px-3">
                        <div
                            v-for="(item, index) in pager.lists"
                            :key="item.id"
                            class="flex gap-x-2 border-b border-[#0000000d] py-[15px] px-[30px] hover:bg-[#f6f6f6] cursor-pointer"
                            @click="handleChoose(item.uuid)">
                            <div class="mt-[3px]" v-if="!isRag">
                                <Icon
                                    name="local-icon-success_fill"
                                    :color="isChoose(item.uuid) ? 'var(--color-primary)' : '#0000000d'"></Icon>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <div>分段-{{ index + 1 }}</div>
                                    <div
                                        v-if="!isRag"
                                        class="w-5 h-5 rounded-md bg-[#0000000d] flex items-center justify-center cursor-pointer"
                                        @click.stop="handleEdit(item)">
                                        <Icon name="local-icon-edit3" :size="14"></Icon>
                                    </div>
                                </div>
                                <div class="mt-[10px] flex text-[11px]">
                                    <div class="flex-shrink-0">文档内容：</div>
                                    <div class="text-[#00000080]">
                                        {{ item.content || item.question }}
                                    </div>
                                </div>
                                <div class="mt-[10px] flex text-[11px]" v-if="!isRag">
                                    <div class="flex-shrink-0">补充内容：</div>
                                    <div class="text-[#00000080]">
                                        {{ item.answer || "-" }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
                <div v-else class="flex items-center justify-center h-full">
                    <ElEmpty description="暂无分段" />
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-y-2 px-[30px] justify-center items-center my-4">
            <div
                class="w-[428px] h-[50px] bg-[#f6f6f6] rounded-md border border-[#efefef] flex justify-between items-center px-[14px] transition-all duration-300"
                :class="[chooseList.length > 0 ? 'opacity-100 translate-y-[0]' : 'opacity-0 translate-y-[100%]']"
                v-if="!isRag">
                <div>已选择{{ chooseList.length }}项</div>
                <div>
                    <ElButton color="#FF3C26" @click="handleDelete">删除</ElButton>
                    <ElButton @click="handleCancel">取消</ElButton>
                </div>
            </div>
            <div class="relative z-[8887]">
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
:deep(.el-input) {
    .el-input__wrapper {
        border-radius: 100px;
    }
}
</style>
