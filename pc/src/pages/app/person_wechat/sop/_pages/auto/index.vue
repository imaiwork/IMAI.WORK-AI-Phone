<template>
    <div
        class="h-full flex flex-col bg-[#FFFFFF] rounded-[20px] border border-br overflow-hidden"
        v-if="!isCreate && !isRecord">
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_10px_rgba(0,101,251,0.4)]"></div>
                <h3 class="text-xl font-[900] text-[#0F172A] tracking-tight">自动 SOP 任务</h3>
                <ElButton
                    type="primary"
                    class="!rounded-xl !h-10 !px-6 !font-black hover:scale-105 transition-transform ml-2"
                    @click="handleCreate()">
                    <Icon name="el-icon-MagicStick" />
                    <span class="ml-1">新增 SOP</span>
                </ElButton>
            </div>

            <div class="flex items-center gap-3">
                <ElSelect
                    v-model="queryParams.status"
                    class="custom-select !w-[160px]"
                    :show-arrow="false"
                    placeholder="筛选状态"
                    @change="getLists()">
                    <ElOption label="全部状态" value=""></ElOption>
                    <ElOption label="未配置" value="0"></ElOption>
                    <ElOption label="未开启" value="1"></ElOption>
                    <ElOption label="已开启" value="2"></ElOption>
                </ElSelect>
                <ElInput
                    v-model="queryParams.push_name"
                    class="custom-input"
                    placeholder="搜索 SOP 任务名称..."
                    clearable
                    @clear="resetParams()"
                    @keyup.enter="getLists()">
                    <template #prefix>
                        <Icon name="el-icon-Search" color="#94A3B8" />
                    </template>
                </ElInput>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable :data="pager.lists" v-loading="pager.loading" height="100%" :row-style="{ height: '76px' }">
                <ElTableColumn label="SOP 任务信息" min-width="180" class-name="!text-left">
                    <template #default="{ row }">
                        <div class="flex flex-col gap-0.5">
                            <span class="font-[900] text-[#0F172A] text-sm text-left">{{ row.push_name }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-medium text-[#94A3B8]">ID: {{ row.id }}</span>
                                <span class="w-1 h-1 rounded-full bg-[#CBD5E1]"></span>
                                <span class="text-[11px] font-black text-primary">{{ SendWayMap[row.type] }}</span>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="营销周期" width="140">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-[#F1F6FF] flex items-center justify-center">
                                <Icon name="el-icon-Timer" color="var(--color-primary)" :size="16" />
                            </div>
                            <span class="text-sm font-black text-[#475569]"
                                >{{ row.all_day }} <small class="font-normal opacity-60">天</small></span
                            >
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="运行状态" width="220">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-3">
                            <div
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-full border"
                                :class="statusConfig[row.status].class">
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[row.status].dot"></span>
                                <span class="text-[11px] font-black">{{ statusConfig[row.status].label }}</span>
                            </div>
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

                <ElTableColumn label="创建日期" prop="create_time" width="180">
                    <template #default="{ row }">
                        <span class="text-xs font-medium text-[#94A3B8]">{{ row.create_time }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="管理操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-1">
                            <ElButton
                                type="primary"
                                link
                                class="!text-primary !font-black !text-xs hover:scale-105"
                                @click="handleEdit(row.id)"
                                >编辑流程</ElButton
                            >
                            <div class="w-[1px] h-3 bg-[#F1F5F9] mx-1"></div>
                            <ElTooltip content="查看记录详情" placement="top">
                                <ElButton
                                    type="primary"
                                    link
                                    class="!text-[#64748B] !font-black !text-xs hover:!text-primary"
                                    @click="handleRecord(row.id)"
                                    >记录</ElButton
                                >
                            </ElTooltip>
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
                    <ElEmpty description="还没有配置自动 SOP 任务" />
                </template>
            </ElTable>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
            <span class="text-xs font-medium text-[#94A3B8]">共计 {{ pager.count }} 个自动化方案</span>
            <pagination v-model="pager" @change="getLists"></pagination>
        </div>
    </div>

    <create-panel ref="createPanelRef" v-if="isCreate" @back="back" />
    <send-record ref="sendRecordRef" v-if="isRecord" @back="back" />
</template>

<script setup lang="ts">
import { sopPushLists, sopPushUpdate, sopPushDelete } from "@/api/person_wechat";
import { SidebarTypeEnum } from "@/pages/app/person_wechat/_enums";
import { PushTypeEnum, SendWayMap } from "../../_enums";
import CreatePanel from "./_components/create-panel.vue";
import SendRecord from "../../_components/send-record.vue";
const nuxtApp = useNuxtApp();
const { query } = useRoute();

const queryParams = reactive({
    status: "",
    push_name: "",
    push_type: PushTypeEnum.AUTO_SOP,
});

const { pager, getLists, resetParams } = usePaging({
    fetchFun: sopPushLists,
    params: queryParams,
});

const isCreate = ref(query.is_create == "1" && parseInt(query.type as string) == SidebarTypeEnum.SOP_AUTO_TASK);
const isRecord = ref(query.is_record == "1" && parseInt(query.type as string) == SidebarTypeEnum.SOP_AUTO_TASK);
const createPanelRef = shallowRef<InstanceType<typeof CreatePanel>>();
const sendRecordRef = shallowRef<InstanceType<typeof SendRecord>>();

const statusConfig = {
    0: { label: "未配置", dot: "bg-[#94A3B8]", class: "bg-[#F1F5F9] border-[#E2E8F0] text-[#64748B]" },
    1: { label: "待启用", dot: "bg-[#D97706]", class: "bg-[#FFFBEB] border-[#FEF3C7] text-[#D97706]" },
    2: { label: "运行中", dot: "bg-[#16A34A]", class: "bg-[#F0FDF4] border-[#DCFCE7] text-[#16A34A]" },
};

const handleCreate = async (id?: number) => {
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
                await sopPushUpdate({
                    id: row.id,
                    status: row.status == 1 ? 2 : 1,
                    type: row.type,
                    stage_id: row.stage_id,
                    flow_id: row.flow_id,
                });
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
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.SOP_AUTO_TASK}`);
    getLists();
};

onMounted(() => {
    if (!isCreate.value) {
        getLists();
    }
});
</script>
