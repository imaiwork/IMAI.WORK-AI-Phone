<template>
    <div class="h-full flex flex-col">
        <div class="flex items-center justify-between px-4">
            <ElButton type="primary" @click="handleAdd">新增名片回复规则</ElButton>
            <div>
                <ElInput
                    v-model="queryParams.keyword"
                    placeholder="请输入匹配内容"
                    prefix-icon="el-icon-Search"
                    clearable
                    @clear="resetParams"
                    @keyup.enter="getLists()" />
            </div>
        </div>
        <div class="grow min-h-0 mt-4">
            <ElTable v-loading="pager.loading" :data="pager.lists" height="100%" stripe :row-style="{ height: '60px' }">
                <ElTableColumn label="匹配类型">
                    <template #default="{ row }">
                        <span v-if="row.match_type == 0">模糊匹配</span>
                        <span v-else>精确匹配</span>
                    </template>
                </ElTableColumn>
                <ElTableColumn prop="keyword" label="匹配内容" />
                <ElTableColumn label="回复内容">
                    <template #default="{ row }">
                        <div class="flex justify-center">
                            <div
                                v-for="item in row.reply"
                                :key="item.id"
                                class="flex flex-col text-left gap-2 bg-primary-light-9 rounded px-4 py-2">
                                <span class="font-bold">{{ item.name }}</span>
                                <span>{{ item.code }}</span>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn prop="name" label="操作" width="120">
                    <template #default="{ row }">
                        <ElButton type="primary" link @click="handleEdit(row)">编辑</ElButton>
                        <ElButton type="danger" link @click="handleDelete(row.id)">删除</ElButton>
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
    <MaterialEdit
        v-if="showMaterialEdit"
        ref="materialEditRef"
        :type="5"
        :app-type="type"
        :account="account"
        @close="showMaterialEdit = false"
        @success="getLists()" />
</template>

<script setup lang="ts">
import { accountKeywordList, deleteAccountKeyword } from "@/api/service";
import MaterialEdit from "./material-edit.vue";

const route = useRoute();
const nuxtApp = useNuxtApp();
const account = computed(() => route.query.account as string);
const type = computed(() => route.query.app_type as string);

const queryParams = reactive({
    keyword: "",
    account: account.value,
    type: type.value,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: accountKeywordList,
    params: queryParams,
});

const showMaterialEdit = ref(false);
const materialEditRef = ref();

const handleAdd = async () => {
    showMaterialEdit.value = true;
    await nextTick();
    materialEditRef.value?.open();
};

const handleEdit = async (row: any) => {
    showMaterialEdit.value = true;
    await nextTick();
    materialEditRef.value?.open("edit");
    materialEditRef.value?.getDetail(row.id);
};

const handleDelete = (id: string) => {
    nuxtApp.$confirm({
        message: "确定删除该名片回复规则吗？",
        onConfirm: async () => {
            try {
                await deleteAccountKeyword({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

watch(
    () => route.query.app_type,
    (newVal) => {
        if (newVal) {
            queryParams.type = newVal as string;
            getLists();
        }
    },
    {
        immediate: true,
    }
);
</script>

<style scoped></style>
