<template>
    <div
        class="h-full flex flex-col bg-[#FFFFFF] rounded-[20px] border border-br overflow-hidden"
        v-if="!isCreate && !isRecord">
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_10px_rgba(0,101,251,0.4)]"></div>
                <h3 class="text-lg font-[900] text-[#0F172A]">SOP 任务管理</h3>
                <ElButton
                    type="primary"
                    class="!rounded-xl !h-10 !px-6 !font-black hover:scale-105 transition-transform"
                    @click="handleCreate">
                    <Icon name="el-icon-Plus" />
                    <span class="ml-1">添加新任务</span>
                </ElButton>
            </div>

            <div class="flex items-center gap-3">
                <ElSelect
                    v-model="queryParams.status"
                    class="custom-select !w-[160px]"
                    placeholder="任务状态"
                    :show-arrow="false"
                    @change="getLists()">
                    <ElOption label="全部状态" value=""></ElOption>
                    <ElOption label="未配置" value="0"></ElOption>
                    <ElOption label="未开启" value="1"></ElOption>
                    <ElOption label="已开启" value="2"></ElOption>
                </ElSelect>

                <ElInput
                    v-model="queryParams.push_name"
                    placeholder="搜索任务名称..."
                    clearable
                    class="custom-input"
                    @clear="resetParams()"
                    @keyup.enter="getLists()">
                    <template #prefix>
                        <Icon name="el-icon-Search" :size="16" color="#94A3B8" />
                    </template>
                </ElInput>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable :data="pager.lists" height="100%" v-loading="pager.loading" :row-style="{ height: '72px' }">
                <ElTableColumn label="任务名称" min-width="220">
                    <template #default="{ row }">
                        <span class="font-[900] text-[#0F172A]">{{ row.push_name }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="营销周期" min-width="120">
                    <template #default="{ row }">
                        <div
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-50 border border-[#F1F5F9]">
                            <Icon name="el-icon-Calendar" :size="12" color="#64748B" />
                            <span class="text-xs font-black text-[#475569]">
                                {{ row.all_day ? `${row.all_day} 天` : "未设定" }}
                            </span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="状态控制" width="200">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-3">
                            <span
                                class="text-[11px] font-black px-2 py-0.5 rounded-md"
                                :class="statusStyle(row.status)">
                                {{ statusText(row.status) }}
                            </span>
                            <ElSwitch
                                v-if="row.status != 0"
                                :model-value="row.status"
                                :active-value="2"
                                :inactive-value="1"
                                class="custom-switch"
                                @change="handleChangeStatus(row)" />
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="时间安排" width="180">
                    <template #default="{ row }">
                        <div class="flex flex-col">
                            <span class="text-[11px] text-[#94A3B8] font-medium">推送：{{ row.push_day || "-" }}</span>
                            <span class="text-[11px] text-[#CBD5E1] font-medium">创建：{{ row.create_time }}</span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-1">
                            <ElButton
                                v-if="row.is_publish_edit == 2"
                                type="primary"
                                link
                                class="!text-primary !font-black !text-xs hover:scale-105"
                                @click="handleEdit(row.id)"
                                >编辑</ElButton
                            >
                            <div class="w-[1px] h-3 bg-[#F1F5F9] mx-1" v-if="row.is_publish_edit == 2"></div>
                            <ElButton
                                type="primary"
                                link
                                class="!text-[#64748B] !font-black !text-xs hover:!text-primary"
                                @click="handleRecord(row.id)"
                                >详情</ElButton
                            >
                            <ElButton
                                type="danger"
                                link
                                class="!text-[#EF4444] !font-black !text-xs hover:scale-105"
                                @click="handleDelete(row.id)"
                                >删除</ElButton
                            >
                        </div>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <ElEmpty description="暂无 SOP 任务数据" />
                </template>
            </ElTable>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
            <span class="text-xs font-medium text-[#94A3B8]">共计 {{ pager.count }} 个营销任务</span>
            <pagination v-model="pager" @change="getLists" />
        </div>
    </div>

    <create-panel ref="createPanelRef" v-if="isCreate" @back="back" />
    <send-record ref="recordRef" v-if="isRecord" @back="back" />
</template>

<script setup lang="ts">
import { sopPushLists, sopPushDelete, sopPushUpdate } from "@/api/person_wechat";
import { SidebarTypeEnum } from "@/pages/app/person_wechat/_enums";
import { PushTypeEnum } from "../../_enums";
import CreatePanel from "./_components/create-panel.vue";
import SendRecord from "../../_components/send-record.vue";
const nuxtApp = useNuxtApp();
const { query } = useRoute();

const queryParams = reactive({
    status: "",
    push_name: "",
    push_type: PushTypeEnum.TASK,
});

const { pager, getLists, resetParams } = usePaging({
    fetchFun: sopPushLists,
    params: queryParams,
});

const isCreate = ref(query.is_create == "1" && parseInt(query.type as string) == SidebarTypeEnum.SOP_TASK);
const isRecord = ref(query.is_record == "1" && parseInt(query.type as string) == SidebarTypeEnum.SOP_TASK);
const recordRef = shallowRef<InstanceType<typeof SendRecord>>();
const createPanelRef = shallowRef<InstanceType<typeof CreatePanel>>();

const statusStyle = (status: number) => {
    const maps: Record<number, string> = {
        0: "bg-[#F1F5F9] text-[#94A3B8]",
        1: "bg-[#FFFBEB] text-[#D97706]",
        2: "bg-[#F0FDF4] text-[#16A34A]",
    };
    return maps[status] || maps[0];
};

const statusText = (status: number) => {
    const maps: Record<number, string> = { 0: "未配置", 1: "未开启", 2: "进行中" };
    return maps[status] || "未知";
};

const handleCreate = () => {
    isCreate.value = true;
    replaceState({
        is_create: 1,
    });
};

const handleEdit = async (id: number | string) => {
    isCreate.value = true;
    replaceState({
        id,
        is_create: 1,
    });
};

const handleChangeStatus = async (row: any) => {
    nuxtApp.$confirm({
        message: `是否${row.status == 1 ? "开启" : "关闭"}该任务？`,
        onConfirm: async () => {
            try {
                await sopPushUpdate({ id: row.id, status: row.status == 1 ? 2 : 1, type: row.type });
                getLists();
                feedback.msgSuccess("操作成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const handleDelete = async (id: number) => {
    nuxtApp.$confirm({
        message: "是否删除该SOP任务？",
        onConfirm: async () => {
            try {
                await sopPushDelete({ id });
                getLists();
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const handleRecord = (id: number) => {
    isRecord.value = true;
    replaceState({
        id,
        is_record: 1,
    });
};

const back = () => {
    isCreate.value = false;
    isRecord.value = false;
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.SOP_TASK}`);
    getLists();
};

onMounted(() => {
    if (!isCreate.value) {
        getLists();
    }
});
</script>
