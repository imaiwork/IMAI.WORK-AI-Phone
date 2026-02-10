<template>
    <div class="h-full flex flex-col bg-[#FFFFFF] rounded-[20px] border border-br overflow-hidden">
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_10px_rgba(0,101,251,0.4)]"></div>
                <h3 class="text-lg font-[900] text-[#0F172A]">自动标签规则</h3>
                <ElButton
                    type="primary"
                    class="!rounded-xl !h-10 !px-6 !font-black hover:scale-105 transition-transform ml-2"
                    @click="handleAdd">
                    <Icon name="el-icon-Plus" />
                    <span class="ml-2">添加匹配标签</span>
                </ElButton>
                <ElButton
                    class="!h-10 !px-6 !rounded-xl font-medium border-[#E2E8F0] hover:!bg-slate-50 transition-all"
                    @click="handleImport">
                    <Icon name="el-icon-Upload" />
                    <span class="ml-2">批量导入</span>
                </ElButton>
            </div>
            <div>
                <ElInput
                    v-model="queryParams.tag_name"
                    placeholder="搜索标签名称..."
                    class="custom-input"
                    clearable
                    @clear="getLists()"
                    @keyup.enter="getLists()">
                    <template #prefix>
                        <Icon name="el-icon-Search" />
                    </template>
                </ElInput>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable :data="pager.lists" v-loading="pager.loading" height="100%" class="custom-modern-table">
                <ElTableColumn label="匹配模式" width="130">
                    <template #default="{ row }">
                        <div :class="['mode-tag', row.match_type == 0 ? 'fuzzy' : 'exact']">
                            {{ row.match_type == 0 ? "模糊匹配" : "精确匹配" }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="触发源" width="120">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-2">
                            <div
                                :class="[
                                    'w-2 h-2 rounded-full',
                                    row.match_mode == 0 ? 'bg-[#6366f1]' : 'bg-[#10b981]',
                                ]"></div>
                            <span class="text-[13px] font-medium text-tx-primary">
                                {{ row.match_mode == 0 ? "AI 回复" : "客户回复" }}
                            </span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="触发关键词" min-width="260">
                    <template #default="{ row }">
                        <div class="flex flex-wrap justify-center gap-1.5 py-2">
                            <span
                                v-for="(word, index) in row.match_keywords?.split(',')"
                                :key="index"
                                class="keyword-bubble">
                                {{ word }}
                            </span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="赋予标签" min-width="160">
                    <template #default="{ row }">
                        <div
                            class="inline-flex items-center px-3 py-1 rounded-lg bg-[#0065fb]/5 text-primary border border-[#0065fb]/10">
                            <Icon name="el-icon-PriceTag" :size="14" />
                            <span class="text-[13px] font-black ml-1.5">{{ row.tag_name }}</span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-1">
                            <ElButton type="primary" link class="!text-[13px] font-medium" @click="handleEdit(row)"
                                >编辑</ElButton
                            >
                            <div class="w-[1px] h-3 bg-slate-200 mx-1"></div>
                            <ElButton type="danger" link class="!text-[13px] font-medium" @click="handleDelete(row.id)"
                                >删除</ElButton
                            >
                        </div>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <div class="py-20">
                        <ElEmpty :image-size="140" description="尚未配置自动标签规则" />
                    </div>
                </template>
            </ElTable>
        </div>
        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
            <span class="text-xs font-medium text-[#94A3B8]">共计 {{ pager.count }} 个自动标签规则</span>
            <pagination v-model="pager" @change="getLists" />
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
import ImportDataPop from "@/pages/app/person_wechat/_components/import-data.vue";
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
<style scoped lang="scss">
.mode-tag {
    @apply inline-flex px-2.5 py-0.5 rounded-md text-[11px] font-black uppercase tracking-wider;
    &.fuzzy {
        @apply bg-amber-50 text-amber-600 border border-amber-100;
    }
    &.exact {
        @apply bg-blue-50 text-blue-600 border border-blue-100;
    }
}

.keyword-bubble {
    @apply px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs font-medium border border-[#e2e8f0]/50;
}
</style>
