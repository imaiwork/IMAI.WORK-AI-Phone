<template>
    <div
        class="h-full flex flex-col bg-white rounded-[20px] border border-br overflow-hidden"
        v-if="!isCreate && !isDetail">
        <div class="flex-shrink-0 px-8 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-[900] text-gray-950">手动加微记录</h1>
                <p class="text-xs text-tx-placeholder font-medium mt-0.5">管理及追踪所有手动触发的微信添加任务</p>
            </div>

            <div class="flex items-center gap-4">
                <ElInput
                    v-model="queryParams.name"
                    class="custom-input !w-[280px]"
                    placeholder="按任务名称搜索..."
                    clearable
                    @clear="getLists()"
                    @keydown.enter="getLists()">
                    <template #prefix>
                        <Icon name="el-icon-Search" :size="16" />
                    </template>
                </ElInput>

                <ElButton
                    type="primary"
                    class="!rounded-xl !h-[44px] px-6 !font-medium transition-all hover:scale-105 active:scale-95"
                    @click="handleCreate">
                    <Icon name="local-icon-add_circle" color="#ffffff" :size="18"></Icon>
                    <span class="ml-2">创建加微任务</span>
                </ElButton>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable
                height="100%"
                :data="pager.lists"
                v-loading="pager.loading"
                row-class-name="cursor-pointer"
                @row-click="handleDetail">
                <ElTableColumn prop="name" label="任务名称" min-width="200" fixed="left">
                    <template #default="{ row }">
                        <span class="text-gray-950 font-black text-sm">{{ row.name || "未命名任务" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="执行账号" min-width="180">
                    <template #default="{ row }">
                        <div class="flex justify-center flex-wrap gap-1.5" v-if="row.wechats?.length > 0">
                            <span
                                v-for="item in row.wechats"
                                :key="item.wechat_id"
                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-50 border border-br-extra-light text-[11px] font-medium text-tx-regular">
                                <Icon name="local-icon-wechat" color="var(--green-500)" :size="12" />
                                <span class="ml-1">
                                    {{ item.wechat_nickname }}
                                </span>
                            </span>
                        </div>
                        <span v-else class="text-tx-placeholder">-</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="任务状态" width="140" align="center">
                    <template #default="{ row }">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full border"
                            :class="getStatusBadgeClass(row.status)">
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-[currentColor]"
                                :class="row.status == 1 ? 'animate-pulse' : ''"></span>
                            <span class="text-xs font-black">
                                {{ getStatusText(row.status) }}
                            </span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="执行周期" width="220">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center text-xs text-tx-secondary font-medium">
                            <span>{{ dayjs(row.start_time).format("YYYY.MM.DD") }}</span>
                            <span class="mx-1.5 text-tx-placeholder">→</span>
                            <span>{{ dayjs(row.end_time).format("YYYY.MM.DD") }}</span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="exec_day" label="执行天数" width="100" align="center">
                    <template #default="{ row }">
                        <span class="font-mono font-medium text-gray-950">{{ row.exec_day }}</span>
                        <span class="text-[10px] text-tx-placeholder ml-0.5">天</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn
                    prop="create_time"
                    label="创建时间"
                    width="160"
                    class-name="text-xs text-tx-placeholder" />

                <ElTableColumn label="操作" width="100" fixed="right" align="right">
                    <template #default="{ row }">
                        <div class="flex justify-end items-center gap-1" @click.stop>
                            <ElButton
                                type="primary"
                                link
                                size="small"
                                class="!font-medium"
                                @click.stop="handleDetail(row)"
                                >详情</ElButton
                            >
                            <div class="w-[1px] h-3 bg-br-extra-light mx-1"></div>
                            <ElButton
                                type="danger"
                                class="!font-medium"
                                link
                                size="small"
                                @click.stop="handleDelete(row.id)"
                                >删除</ElButton
                            >
                        </div>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <div class="py-20 flex flex-col items-center">
                        <ElEmpty description="暂无加微任务记录" :image-size="120" />
                    </div>
                </template>
            </ElTable>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between">
            <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 条加微任务数据</div>
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
        </div>
    </div>

    <CreatePanel v-if="isCreate" @back="handleBack" />
    <Detail v-if="isDetail" @back="handleBack" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { getManualAddWechatList, updateManualAddWechatStatus, deleteManualAddWechat } from "~/api/customer";
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

const handleDetail = ({ id }) => {
    isDetail.value = true;
    replaceState({
        is_detail: 1,
        id: id,
    });
};

// 状态文字映射
const getStatusText = (status: number) => {
    return { 0: "未开始", 1: "进行中", 2: "已暂停", 3: "已完成", 4: "已结束" }[status] || "未知";
};

// 状态样式映射 (遵循您的色彩系统)
const getStatusBadgeClass = (status: number) => {
    const maps = {
        0: "bg-gray-50 text-gray-400 border-gray-100", // 未开始
        1: "bg-blue-50 text-primary border-blue-100", // 进行中
        2: "bg-orange-50 text-orange-500 border-orange-100", // 已暂停
        3: "bg-green-50 text-green-600 border-green-100", // 已完成
        4: "bg-gray-100 text-tx-placeholder border-br-light", // 已结束
    };
    return maps[status] || maps[0];
};

const handleDelete = async (id) => {
    nuxtApp.$confirm({
        message: "确定删除该记录吗？",
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
<style lang="scss" scoped>
// /* 搜索框深度美化 */
// :deep(.custom-search-input) {
//     .el-input__wrapper {
//         @apply !bg-gray-50 !rounded-xl !shadow-none border border-br-light h-11 transition-all px-4;
//         &.is-focus {
//             @apply !bg-white border-primary shadow-light;
//         }
//     }
// }
</style>
