<template>
    <div class="h-full flex flex-col rounded-[20px] bg-white w-full min-w-[1000px] border border-br overflow-hidden">
        <div class="px-8 py-5 border-b border-br-extra-light flex items-center justify-between bg-white">
            <div
                class="flex items-center gap-2 cursor-pointer group hover:text-primary transition-colors"
                @click="emit('back')">
                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-blue-50">
                    <span class="text-tx-regular group-hover:text-primary leading-[0]">
                        <Icon name="el-icon-ArrowLeft"></Icon>
                    </span>
                </div>
                <div class="text-base font-black text-gray-950">加微执行详情</div>
            </div>
        </div>

        <div class="px-8 py-6 bg-gray-50/30">
            <div
                class="relative transition-all duration-300 overflow-hidden"
                :class="isExpanded ? 'max-h-[80px]' : 'max-h-[500px]'">
                <div class="grid grid-cols-1 gap-y-4 text-sm">
                    <div class="flex items-start">
                        <span class="w-24 text-tx-secondary font-medium">执行设备：</span>
                        <div class="flex flex-wrap gap-2 flex-1">
                            <span
                                class="px-3 py-1 rounded-lg bg-white border border-br-light text-tx-regular text-xs font-medium shadow-sm"
                                v-for="item in formatDeviceCodes(detail?.device_codes)"
                                :key="item"
                                >{{ item }}</span
                            >
                        </div>
                    </div>

                    <div class="flex items-start">
                        <span class="w-24 text-tx-secondary font-medium">执行微信：</span>
                        <div class="flex flex-wrap gap-2 flex-1">
                            <div
                                v-for="item in detail?.wechat_id"
                                :key="item"
                                class="flex items-center gap-x-2 px-3 py-1 rounded-lg bg-white border border-br-light text-tx-regular text-xs font-medium shadow-sm">
                                <img src="@/assets/images/wechat_icon.png" class="w-3.5 h-3.5" /> {{ item }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 p-4 bg-white rounded-xl border border-br-extra-light">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-tx-placeholder uppercase font-black">加微规则</span>
                            <span class="text-tx-regular font-medium">{{
                                detail?.wechat_reg_type == 1
                                    ? "微信号优先"
                                    : detail?.wechat_reg_type == 2
                                    ? "手机号优先"
                                    : "全部模式"
                            }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-tx-placeholder uppercase font-black">加微频率</span>
                            <span class="text-tx-regular font-medium"
                                >当天{{ detail?.add_number }}次 / 间隔{{ detail?.add_interval_time }}min</span
                            >
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-tx-placeholder uppercase font-black">任务进度</span>
                            <span class="text-primary font-medium">{{ pager.count }} 条待执行线索</span>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <span class="w-24 text-tx-secondary font-medium">申请备注：</span>
                        <div class="flex flex-wrap gap-2 flex-1">
                            <span
                                class="px-3 py-1 rounded-lg bg-gray-100 text-tx-regular text-xs font-medium"
                                v-for="item in detail?.remarks"
                                :key="item"
                                >{{ item }}</span
                            >
                        </div>
                    </div>
                </div>
                <div
                    v-if="isExpanded"
                    class="absolute bottom-0 left-0 w-full h-12 bg-gradient-to-t from-gray-50 to-transparent pointer-events-none"></div>
            </div>

            <div
                class="flex items-center justify-center mt-4 cursor-pointer text-primary hover:text-blue-700 transition-all"
                @click="isExpanded = !isExpanded">
                <span class="text-xs font-medium mr-1">{{ !isExpanded ? "收起参数" : "展开配置详情" }}</span>
                <Icon :name="!isExpanded ? 'el-icon-ArrowUp' : 'el-icon-ArrowDown'" :size="12"></Icon>
            </div>
        </div>

        <div class="grow min-h-0 flex flex-col px-4">
            <ElTable height="100%" :data="pager.lists" class="dh-custom-table" v-loading="pager.loading">
                <ElTableColumn prop="clue_wechat" label="线索信息" min-width="140">
                    <template #default="{ row }">
                        <span class="text-gray-950 font-black">{{ row.clue_wechat || "-" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="wechat_no" label="执行账号" min-width="140" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-1.5 text-tx-secondary text-xs">
                            <Icon name="local-icon-wechat" color="var(--green-500)" :size="12" />
                            {{ row.wechat_no || "-" }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="申请备注词" min-width="180" show-overflow-tooltip>
                    <template #default="{ row }">
                        <span class="text-tx-regular text-xs">{{ row.remark || "-" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="任务状态" width="140" align="center">
                    <template #default="{ row }">
                        <div class="status-pill" :class="getStatusClass(row.status)">
                            <span class="dot" :class="row.status == 2 ? 'animate-pulse' : ''"></span>
                            {{ getStatusText(row.status) }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="result" label="执行通知" min-width="150" show-overflow-tooltip>
                    <template #default="{ row }">
                        <span :class="row.status == 0 ? 'text-danger' : 'text-tx-placeholder'" class="text-xs italic">{{
                            row.result || "-"
                        }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="exec_time" label="执行时间" width="170">
                    <template #default="{ row }">
                        <span class="text-tx-placeholder text-xs">{{ row.exec_time || "-" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="操作" width="80" fixed="right" align="right">
                    <template #default="{ row }">
                        <ElButton type="danger" link size="small" @click="handleDelete(row.id)">删除</ElButton>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <div class="py-20 flex flex-col items-center">
                        <ElEmpty description="暂无加微执行记录" :image-size="100" />
                    </div>
                </template>
            </ElTable>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between">
            <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 条加微详情数据</div>
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
        </div>
    </div>
</template>

<script setup lang="ts">
// 原有 logic 保持不变
import { getManualAddWechatDetail, getManualAddWechatRecord, deleteManualAddWechatRecord } from "~/api/customer";

const emit = defineEmits(["back"]);
const isExpanded = ref(false);
const query = searchQueryToObject();
const detail = ref<any>({});
const queryParams = reactive({ id: query.id });

const { pager, getLists } = usePaging({
    fetchFun: getManualAddWechatRecord,
    params: queryParams,
});

// 新增辅助函数用于样式切换
const getStatusClass = (status: number) => {
    return (
        {
            0: "fail",
            1: "success",
            2: "running",
            4: "waiting",
        }[status] || ""
    );
};

const getStatusText = (status: number) => {
    return (
        {
            0: "添加失败",
            1: "添加成功",
            2: "执行中",
            4: "等待添加",
        }[status] || "未知"
    );
};

// ...原有 methods (formatDeviceCodes, handleDelete, getDetail, init) ...
const formatDeviceCodes = (deviceCodes: any) => {
    if (!deviceCodes) return [];
    try {
        return typeof deviceCodes === "string" ? JSON.parse(deviceCodes) : deviceCodes;
    } catch (e) {
        return [];
    }
};

const getDetail = async () => {
    const data = await getManualAddWechatDetail({ id: query.id });
    detail.value = data;
};

const handleDelete = async (id: string) => {
    useNuxtApp().$confirm({
        message: "确定删除该记录吗？",
        onConfirm: async () => {
            try {
                await deleteManualAddWechatRecord({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

const init = async () => {
    await getDetail();
    await getLists();
};

onMounted(init);
</script>

<style lang="scss" scoped>
.status-pill {
    @apply inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border;
    .dot {
        @apply w-1.5 h-1.5 rounded-full bg-[currentColor];
    }

    &.success {
        @apply bg-green-50 text-green-600 border-green-100;
    }
    &.fail {
        @apply bg-red-50 text-red-500 border-red-100;
    }
    &.running {
        @apply bg-orange-50 text-orange-500 border-orange-100;
    }
    &.waiting {
        @apply bg-blue-50 text-primary border-blue-100;
    }
}
</style>
