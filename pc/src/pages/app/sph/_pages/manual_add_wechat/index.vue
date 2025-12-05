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
                            <span class="ml-2">创建任务 </span>
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
                    <ElTableColumn prop="name" label="任务名称" width="240" fixed="left"> </ElTableColumn>
                    <ElTableColumn label="执行账号" min-width="140" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div class="flex gap-x-2 justify-center" v-if="row.wechats.length > 0">
                                <div
                                    v-for="item in row.wechats"
                                    class="rounded-md px-4 py-1 bg-app-bg-4 border border-app-border-2">
                                    {{ item.wechat_nickname }}
                                </div>
                            </div>
                            <div v-else>-</div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="任务状态" width="160" align="center">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-x-2">
                                <div class="w-[6px] h-[6px] rounded-full" :class="getStatusStyle(row.status)"></div>
                                <span>
                                    <template v-if="row.status == 0">未开始</template>
                                    <template v-if="row.status == 1">进行中</template>
                                    <template v-if="row.status == 2">已暂停</template>
                                    <template v-if="row.status == 3">已完成</template>
                                    <template v-if="row.status == 4">已结束</template>
                                </span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="exec_day" label="已执行天数" min-width="120"> </ElTableColumn>
                    <ElTableColumn prop="create_time" label="创建时间" width="180"></ElTableColumn>
                    <ElTableColumn label="操作" width="120" fixed="right" align="right">
                        <template #default="{ row }">
                            <div class="flex justify-end items-center">
                                <ElButton
                                    class="!border-app-border-2"
                                    color="#181818"
                                    size="small"
                                    @click="handleDetail(row.id)"
                                    >详情</ElButton
                                >
                                <ElButton type="danger" link size="small" @click="handleDelete(row.id)">删除</ElButton>
                            </div>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <div class="flex justify-center items-center h-full">
                            <ElEmpty description="暂无数据" />
                        </div>
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-center p-4">
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
        </div>
    </div>
    <CreatePanel v-if="isCreate" @back="handleBack" />
    <Detail v-if="isDetail" @back="handleBack" />
</template>

<script setup lang="ts">
import { getManualAddWechatList, updateManualAddWechatStatus, deleteManualAddWechat } from "@/api/sph";
import { SidebarTypeEnum } from "../../_enums/index";
import CreatePanel from "./_components/create-panel.vue";
import Detail from "./detail.vue";

const { query } = useRoute();
const nuxtApp = useNuxtApp();

const queryParams = reactive({
    device_code: "",
    wechat_no: "",
    channel: "",
    exec_type: "",
    name: "",
    status: "",
    page_size: 20,
});

// 是否是创建任务
const isCreate = ref(query.is_create == "1" && parseInt(query.type as string) == SidebarTypeEnum.MANUAL_ADD_WECHAT);
// 是否是详情
const isDetail = ref(query.is_detail == "1" && parseInt(query.type as string) == SidebarTypeEnum.MANUAL_ADD_WECHAT);

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getManualAddWechatList,
    params: queryParams,
});

const handleCreate = () => {
    isCreate.value = true;
    replaceState({
        is_create: 1,
    });
};

const handleDetail = (id: string) => {
    isDetail.value = true;
    replaceState({
        is_detail: 1,
        id: id,
    });
};

const getStatusStyle = (status: number) => {
    const styles = {
        0: "bg-[#999999]",
        1: "bg-primary",
        2: "bg-[#FFBC50]",
        3: "bg-[#3BB840]",
        4: "bg-[#3BB840]",
    };
    return styles[status];
};

const handleChangeStatus = async (row: any) => {
    try {
        await updateManualAddWechatStatus({ id: row.id, status: row.status == 1 ? 2 : 1 });
        feedback.msgSuccess(row.status == 1 ? "继续成功" : "暂停成功");
        getLists();
    } catch (error) {
        feedback.msgError(error || "操作失败");
    }
};

const handleDelete = async (id) => {
    nuxtApp.$confirm({
        message: "确定删除该记录吗？",
        theme: "dark",
        onConfirm: async () => {
            try {
                await deleteManualAddWechat({ id });
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
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.MANUAL_ADD_WECHAT}`);
    getLists();
};

onMounted(() => {
    if (!isCreate.value) {
        getLists();
    }
});
</script>

<style scoped lang="scss">
:deep(.el-form-item__label) {
    line-height: 40px;
}
</style>
