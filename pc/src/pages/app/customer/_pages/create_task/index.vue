<template>
    <div
        class="h-full flex flex-col rounded-[20px] bg-white border border-br min-w-[1000px]"
        v-if="!isCreate && !isDetail">
        <div class="flex-shrink-0 px-8 py-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-[900] text-gray-950">AI 获客任务</h1>
                <p class="text-xs text-tx-placeholder font-medium mt-0.5">自动监测全网线索，精准锁定潜在客户</p>
            </div>

            <div class="flex items-center gap-4">
                <ElInput
                    v-model="queryParams.name"
                    class="custom-input !w-[280px]"
                    placeholder="搜索任务名称..."
                    clearable
                    @clear="getLists()"
                    @keydown.enter="getLists()">
                    <template #prefix>
                        <Icon name="el-icon-Search" />
                    </template>
                </ElInput>

                <ElButton
                    type="primary"
                    class="!rounded-xl !h-[44px] px-6 !font-medium transition-all hover:scale-105 active:scale-95"
                    @click="handleCreate">
                    <Icon name="local-icon-add_circle" color="#ffffff" :size="18"></Icon>
                    <span class="ml-2">发布新任务</span>
                </ElButton>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable
                height="100%"
                :data="pager.lists"
                class="!w-full"
                v-loading="pager.loading"
                row-class-name="cursor-pointer"
                @row-click="handleDetail">
                <ElTableColumn label="任务信息" min-width="220" fixed="left">
                    <template #default="{ row }">
                        <div
                            class="flex items-center justify-center gap-x-2 cursor-pointer group"
                            @click.stop="handleEdit(row)">
                            <span
                                class="text-[14px] font-black text-[#1E293B] break-all line-clamp-1 group-hover:text-primary transition-colors"
                                >{{ row.name }}</span
                            >
                            <span class="opacity-0 group-hover:opacity-100 text-primary transition-opacity">
                                <Icon name="local-icon-edit" :size="14" />
                            </span>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="任务类型" width="80">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-2">
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-medium tracking-wider uppercase"
                                :class="
                                    row.auto_type == 0 ? 'bg-orange-50 text-orange-500' : 'bg-blue-50 text-blue-500'
                                ">
                                {{ row.auto_type == 0 ? "手动" : "24H" }}
                            </span>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="线索词" min-width="200">
                    <template #default="{ row }">
                        <div class="flex justify-center flex-wrap gap-1">
                            <span
                                v-for="tag in row.keywords.slice(0, 2)"
                                :key="tag"
                                class="text-xs text-[#64748B] bg-[#F1F5F9] px-2 py-0.5 rounded-md">
                                {{ tag }}
                            </span>
                            <span v-if="row.keywords.length > 2" class="text-[11px] text-[#94A3B8]">
                                +{{ row.keywords.length - 2 }}
                            </span>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn prop="crawl_number" label="已获客资" width="120" align="center">
                    <template #default="{ row }">
                        <span class="text-[15px] font-[900] text-primary">{{ row.crawl_number || 0 }}</span>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="任务状态" width="130">
                    <template #default="{ row }">
                        <div class="status-tag" :class="`status-${row.status}`">
                            <span class="dot"></span>
                            <span class="text">{{ getStatusText(row.status) }}</span>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="执行进度" width="140">
                    <template #default="{ row }">
                        <div class="flex flex-col gap-1 w-full pr-4">
                            <div class="flex justify-between text-[11px] font-medium text-[#94A3B8]">
                                <span>进度</span>
                                <span
                                    >{{
                                        Math.round(
                                            (row.number_of_implemented_keywords / row.implementation_keywords_number) *
                                                100
                                        ) || 0
                                    }}%</span
                                >
                            </div>
                            <div class="w-full h-1.5 bg-[#F1F5F9] rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-primary transition-all duration-500"
                                    :style="{
                                        width: `${
                                            (row.number_of_implemented_keywords / row.implementation_keywords_number) *
                                            100
                                        }%`,
                                    }"></div>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn prop="create_time" label="创建时间" width="160" />
                <ElTableColumn label="操作" width="60" fixed="right" align="right">
                    <template #default="{ row }">
                        <div class="flex justify-end items-center gap-2" @click.stop>
                            <ElPopover
                                popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light"
                                trigger="click"
                                :show-arrow="false">
                                <template #reference>
                                    <div
                                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-[#F1F5F9] cursor-pointer transition-all">
                                        <Icon name="el-icon-MoreFilled" color="#94A3B8" />
                                    </div>
                                </template>
                                <div class="p-1 space-y-1">
                                    <export-data
                                        :params="{ task_id: row.id }"
                                        :fetch-fun="getTaskClue"
                                        :export-fun="getTaskClue"
                                        v-if="row.status != 0">
                                        <template #trigger>
                                            <div class="table-action-item">
                                                <Icon name="el-icon-Download" /> 导出线索
                                            </div>
                                        </template>
                                    </export-data>
                                    <div class="table-action-item" @click.stop="handleDetail(row)">
                                        <Icon name="el-icon-View" /> 详情
                                    </div>
                                    <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                    <div
                                        v-if="row.auto_type == 0"
                                        class="table-action-item !text-red-500 hover:!bg-red-50"
                                        @click.stop="handleDelete(row.id)">
                                        <Icon name="el-icon-Delete" /> 删除
                                    </div>
                                </div>
                            </ElPopover>
                        </div>
                    </template>
                </ElTableColumn>
                <template #empty>
                    <div class="py-20 flex flex-col items-center">
                        <div class="w-20 h-20 bg-[#0065fb]/10 rounded-full flex items-center justify-center mb-4">
                            <Icon name="local-icon-add_circle" :size="40" color="var(--color-primary)" />
                        </div>
                        <p class="text-[#94A3B8] mb-6">暂无获客任务，点击下方按钮开启第一条线索库</p>
                        <ElButton
                            type="primary"
                            class="!rounded-xl px-8 shadow-lg shadow-primary/20"
                            @click="handleCreate">
                            立即发布任务
                        </ElButton>
                    </div>
                </template>
            </ElTable>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between">
            <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 个获客任务</div>
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
        </div>

        <EditPopup v-if="showEdit" ref="editPopupRef" @close="showEdit = false" @success="getLists()" />
    </div>

    <CreatePanel v-if="isCreate" @back="handleBack" />
    <Detail v-if="isDetail" ref="detailRef" @back="handleBack" />
</template>

<script setup lang="ts">
import { getTaskList, deleteTask, changeTaskStatus, retryTask, getTaskClue } from "~/api/customer";
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

const getStatusText = (status: number) => {
    const map = { 0: "未执行", 1: "进行中", 2: "已暂停", 3: "已完成", 4: "已结束" };
    return map[status] || "未知";
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

<style lang="scss" scoped>
.status-tag {
    @apply inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black;

    .dot {
        @apply w-1.5 h-1.5 rounded-full;
    }

    &.status-0 {
        @apply bg-gray-50 text-gray-400;
        .dot {
            @apply bg-gray-300;
        }
    }
    &.status-1 {
        @apply bg-blue-50 text-primary;
        .dot {
            @apply bg-primary animate-pulse;
        }
    }
    &.status-2 {
        @apply bg-orange-50 text-orange-500;
        .dot {
            @apply bg-orange-400;
        }
    }
    &.status-3 {
        @apply bg-green-50 text-green-500;
        .dot {
            @apply bg-green-500;
        }
    }
    &.status-4 {
        @apply bg-gray-100 text-gray-500;
        .dot {
            @apply bg-gray-400;
        }
    }
}
</style>
