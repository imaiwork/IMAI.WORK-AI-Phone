<template>
    <div class="h-full flex flex-col bg-app-bg-2 rounded-[20px]" v-if="!isCreate && !isDetail">
        <div class="flex-shrink-0 px-[14px]">
            <ElScrollbar>
                <div class="flex items-center justify-end h-[88px]">
                    <div class="flex items-center gap-[14px]">
                        <ElInput
                            v-model="queryParams.name"
                            prefix-icon="el-icon-Search"
                            class="!w-[240px] search-name-input"
                            placeholder="请输入任务名称"
                            clearable
                            @clear="getLists()"
                            @keydown.enter="getLists()">
                            <template #append>
                                <ElButton text @click="getLists()"> 搜索 </ElButton>
                            </template>
                        </ElInput>

                        <ElButton type="primary" class="!rounded-full !h-10" @click="handleCreate">
                            <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                            <span class="ml-2">发布获客任务 </span>
                        </ElButton>
                        <div>
                            <ElTooltip content="刷新">
                                <ElButton
                                    circle
                                    color="#1f1f1f"
                                    icon="el-icon-Refresh"
                                    class="!w-10 !h-10"
                                    @click="resetPage()"></ElButton>
                            </ElTooltip>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="grow min-h-0 overflow-hidden flex flex-col">
            <div class="grow min-h-0">
                <ElTable
                    height="100%"
                    :data="pager.lists"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '50px' }"
                    v-loading="pager.loading">
                    <ElTableColumn prop="name" label="任务名称" width="240" fixed="left">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-2 cursor-pointer" @click="handleEdit(row)">
                                <div class="text-white">{{ row.name }}</div>
                                <Icon name="local-icon-edit" color="#ffffff" />
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="线索词" min-width="200" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.keywords.join("；") }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="crawl_number" label="已获线索数" width="120"></ElTableColumn>
                    <ElTableColumn label="任务状态" width="120">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-2">
                                <div
                                    class="w-[6px] h-[6px] rounded-full"
                                    :class="{
                                        'bg-primary': row.status == 1,
                                        'bg-info': row.status == 2,
                                        'bg-success': row.status == 3,
                                        'bg-warning': row.status == 4,
                                    }"
                                    v-if="row.status != 0"></div>
                                <div v-if="row.status == 0">未执行</div>
                                <div v-else-if="row.status == 1">进行中</div>
                                <div v-else-if="row.status == 2">已暂停</div>
                                <div v-else-if="row.status == 3">已完成</div>
                                <div v-else-if="row.status == 4">已结束</div>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="当前执行进度" min-width="120">
                        <template #default="{ row }">
                            {{ row.number_of_implemented_keywords || 0 }} /
                            {{ row.implementation_keywords_number || 0 }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="create_time" label="创建时间" width="180"></ElTableColumn>
                    <ElTableColumn label="操作" width="180" fixed="right" align="right">
                        <template #default="{ row }">
                            <div class="flex justify-end items-center">
                                <export-data
                                    class="mx-3"
                                    :params="{
                                        task_id: row.id,
                                    }"
                                    :fetch-fun="getTaskClue"
                                    :export-fun="getTaskClue"
                                    v-if="row.status != 0">
                                    <template #trigger>
                                        <ElButton class="!border-app-border-2" color="#181818" size="small"
                                            >导出</ElButton
                                        >
                                    </template>
                                    <template #form-item="{ formData }">
                                        <ElFormItem label="线索有效性：">
                                            <ElSelect
                                                v-model="formData.status"
                                                class="!w-[260px] !h-10"
                                                placeholder="请选择"
                                                filterable
                                                clearable
                                                :empty-values="[undefined, null]"
                                                :show-arrow="false">
                                                <ElOption label="全部" value=""></ElOption>
                                                <ElOption label="未校验" value="0"></ElOption>
                                                <ElOption label="已通过校验" value="1"></ElOption>
                                                <ElOption label="未通过校验" value="2"></ElOption>
                                                <ElOption label="待执行" value="4"></ElOption>
                                            </ElSelect>
                                        </ElFormItem>
                                    </template>
                                </export-data>
                                <ElButton
                                    class="!border-app-border-2"
                                    color="#181818"
                                    size="small"
                                    @click="handleDetail(row)"
                                    >详情</ElButton
                                >
                                <ElButton type="danger" link size="small" @click="handleDelete(row.id)">删除</ElButton>
                            </div>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <div class="leading-6">
                            <ElButton type="primary" class="!rounded-full !h-10" @click="handleCreate">
                                <Icon name="local-icon-add_circle" color="#ffffff"></Icon>
                                <span class="ml-2">发布获客任务 </span>
                            </ElButton>
                        </div>
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-center p-4">
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
        </div>
        <EditPopup v-if="showEdit" ref="editPopupRef" @close="showEdit = false" @success="getLists()" />
    </div>
    <CreatePanel v-if="isCreate" @back="handleBack" />
    <Detail v-if="isDetail" ref="detailRef" @back="handleBack" />
</template>

<script setup lang="ts">
import { getTaskList, deleteTask, changeTaskStatus, retryTask, getTaskClue } from "@/api/sph";
import { SidebarTypeEnum } from "../../_enums";
import CreatePanel from "./_components/create-panel.vue";
import EditPopup from "./_components/edit.vue";
import Detail from "./detail.vue";
const { query } = useRoute();
const nuxtApp = useNuxtApp();

const queryParams = reactive({
    name: "",
    page_size: 20,
    status: "",
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getTaskList,
    params: queryParams,
});

const showEdit = ref(false);
const editPopupRef = ref<InstanceType<typeof EditPopup>>();

const changeStatus = async (row: any) => {
    try {
        await changeTaskStatus({ id: row.id, status: row.status == 1 ? 2 : 1 });
        feedback.msgSuccess("操作成功");
        getLists();
    } catch (error) {
        feedback.msgError(error || "操作失败");
    }
};

const isCreate = ref(query.is_create == "1" && parseInt(query.type as string) == SidebarTypeEnum.AUTO_GET_CUSTOMER);
const handleCreate = () => {
    isCreate.value = true;
    replaceState({
        is_create: 1,
    });
};

const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    editPopupRef.value.open();
    editPopupRef.value.setFormData(row);
};

const isDetail = ref(query.is_detail == "1" && parseInt(query.type as string) == SidebarTypeEnum.AUTO_GET_CUSTOMER);
const handleDetail = (row: any) => {
    isDetail.value = true;
    replaceState({
        is_detail: 1,
        id: row.id,
    });
};

const handleDelete = async (id) => {
    nuxtApp.$confirm({
        message: "是否删除该任务？",
        theme: "dark",
        onConfirm: async () => {
            try {
                await deleteTask({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

const handleBack = () => {
    isCreate.value = false;
    isDetail.value = false;
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.AUTO_GET_CUSTOMER}`);
    getLists();
};

onMounted(() => {
    if (!isCreate.value && !isDetail.value) {
        getLists();
    }
});
</script>

<style scoped></style>
