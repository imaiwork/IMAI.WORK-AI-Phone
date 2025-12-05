<template>
    <div class="h-full flex flex-col bg-white rounded-xl" v-if="!isCreate && !isRecord">
        <div class="flex items-center justify-between p-4">
            <ElButton type="primary" @click="handleCreate">添加任务</ElButton>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2">
                    <ElSelect
                        v-model="queryParams.status"
                        :empty-values="[null, undefined]"
                        class="!w-[120px]"
                        @change="getLists()">
                        <ElOption label="全部" value=""></ElOption>
                        <ElOption label="未配置" value="0"></ElOption>
                        <ElOption label="未开启" value="1"></ElOption>
                        <ElOption label="已开启" value="2"></ElOption>
                    </ElSelect>
                </div>
                <div>
                    <ElInput
                        v-model="queryParams.push_name"
                        class="!w-[240px]"
                        placeholder="请输入任务名称"
                        clearable
                        @clear="resetParams()"
                        @keyup.enter="getLists()">
                        <template #append>
                            <ElButton @click="getLists()">
                                <Icon name="el-icon-Search"></Icon>
                            </ElButton>
                        </template>
                    </ElInput>
                </div>
            </div>
        </div>
        <div class="grow min-h-0 flex flex-col">
            <div class="grow min-h-0">
                <ElTable
                    :data="pager.lists"
                    stripe
                    height="100%"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '60px' }"
                    v-loading="pager.loading">
                    >
                    <ElTableColumn label="任务名称" prop="push_name" min-width="200"> </ElTableColumn>
                    <ElTableColumn label="营销总天数" prop="all_day" min-width="120">
                        <template #default="{ row }">
                            {{ row.all_day ? `${row.all_day}天` : "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="任务状态" width="220">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2 justify-center">
                                <template v-if="row.status == 0">
                                    <span class="text-info"> 未配置 </span>
                                </template>
                                <template v-if="row.status == 1">
                                    <span class="text-warning"> 未开启 </span>
                                </template>
                                <template v-if="row.status == 2">
                                    <span class="text-success"> 已开启 </span>
                                </template>
                                <ElSwitch
                                    v-if="row.status != 0"
                                    :model-value="row.status"
                                    :active-value="2"
                                    :inactive-value="1"
                                    style="
                                        --el-switch-on-color: var(--el-color-success);
                                        --el-switch-off-color: var(--el-color-warning);
                                    "
                                    @change="handleChangeStatus(row)" />
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="推送时间" prop="push_day" width="160"> </ElTableColumn>
                    <ElTableColumn label="创建时间" prop="create_time" width="180"> </ElTableColumn>
                    <ElTableColumn label="操作" width="160" fixed="right">
                        <template #default="{ row }">
                            <ElButton v-if="row.is_publish_edit == 2" type="primary" link @click="handleEdit(row.id)"
                                >编辑</ElButton
                            >
                            <ElButton type="primary" link @click="handleRecord(row.id)">详情</ElButton>
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
    </div>
    <create-panel ref="createPanelRef" v-if="isCreate" @back="back" />
    <send-record ref="recordRef" v-if="isRecord" @back="back" />
</template>

<script setup lang="ts">
import { sopPushLists, sopPushDelete, sopPushUpdate } from "@/api/person_wechat";
import { SidebarTypeEnum, PushTypeEnum } from "../../_enums";
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

const isCreate = ref(query.is_create == "1" && parseInt(query.type as string) == SidebarTypeEnum.TASK);
const isRecord = ref(query.is_record == "1" && parseInt(query.type as string) == SidebarTypeEnum.TASK);
const recordRef = shallowRef<InstanceType<typeof SendRecord>>();
const createPanelRef = shallowRef<InstanceType<typeof CreatePanel>>();

const handleCreate = () => {
    isCreate.value = true;
    replaceState({
        is_create: 1,
    });
};

const handleEdit = async (id: number | string) => {
    isCreate.value = true;
    await nextTick();
    createPanelRef.value?.getDetail(id);
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
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.TASK}`);
    getLists();
};

onMounted(() => {
    if (!isCreate.value) {
        getLists();
    }
});
</script>
<style lang="scss" scoped></style>
