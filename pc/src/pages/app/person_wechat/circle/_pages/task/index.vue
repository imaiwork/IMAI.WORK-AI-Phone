<template>
    <div class="h-full flex flex-col bg-[#FFFFFF] rounded-[20px] border border-br overflow-hidden">
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_10px_rgba(0,101,251,0.4)]"></div>
                <h3 class="text-lg font-[900] text-[#0F172A]">朋友圈任务管理</h3>
                <ElButton
                    type="primary"
                    class="!rounded-xl !h-10 !px-6 !font-black hover:scale-105 transition-transform"
                    @click="handleAddCircle">
                    <Icon name="el-icon-Plus" />
                    <span class="ml-1">添加新任务</span>
                </ElButton>
            </div>

            <div class="flex items-center gap-3">
                <ElSelect
                    v-model="queryParams.send_status"
                    class="!w-[160px] custom-select"
                    placeholder="任务状态"
                    clearable
                    @change="resetPage()">
                    <ElOption label="全部任务" value=""></ElOption>
                    <ElOption label="待执行" :value="SendStatus.PENDING"></ElOption>
                    <ElOption label="执行中" :value="SendStatus.EXECUTING"></ElOption>
                    <ElOption label="已完成" :value="SendStatus.COMPLETED"></ElOption>
                    <ElOption label="已失败" :value="SendStatus.FAILED"></ElOption>
                    <ElOption label="暂停中" :value="SendStatus.PAUSED"></ElOption>
                </ElSelect>

                <ElInput
                    v-model="queryParams.wechat_id"
                    placeholder="搜索微信号/昵称"
                    class="custom-input !w-[280px]"
                    clearable
                    @clear="getLists()"
                    @keyup.enter="getLists()">
                </ElInput>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable :data="pager.lists" height="100%" v-loading="pager.loading">
                <ElTableColumn label="发送账号" min-width="240">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center py-1">
                            <div
                                class="w-9 h-9 rounded-xl bg-primary/5 flex items-center justify-center text-primary mr-3 border border-[#0065fb]/10">
                                <Icon name="local-icon-wechat" :size="20" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[14px] font-black text-tx-primary leading-tight">{{
                                    row.wechat_nickname
                                }}</span>
                                <span class="text-[11px] text-tx-placeholder font-medium mt-1"
                                    >ID: {{ row.wechat_id }}</span
                                >
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="内容类型" width="120">
                    <template #default="{ row }">
                        <span class="type-tag">{{ getAttachmentType(row.attachment_type) }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="执行状态" width="130">
                    <template #default="{ row }">
                        <div class="flex justify-center">
                            <div :class="['status-pill', getStatusClass(row.send_status)]">
                                <span class="dot"></span>
                                <span class="text">{{ getStatusText(row.send_status) }}</span>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="任务模式" width="120">
                    <template #default="{ row }">
                        <div
                            class="flex items-center justify-center gap-1.5 font-medium text-[13px]"
                            :class="row.task_type == 1 ? 'text-amber-500' : 'text-blue-500'">
                            <Icon :name="row.task_type == 1 ? 'el-icon-AlarmClock' : 'el-icon-Lightning'" :size="14" />
                            {{ row.task_type == 1 ? "定时" : "即时" }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="send_time" label="计划执行时间" width="180">
                    <template #default="{ row }">
                        <span class="font-mono text-[13px] text-tx-regular">{{ row.send_time || "-" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="finish_time" label="实际完成时间" width="180">
                    <template #default="{ row }">
                        <span class="font-mono text-[13px] text-tx-placeholder">{{ row.finish_time || "-" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-1">
                            <ElButton type="primary" link class="!text-xs font-medium" @click="handleDetail(row.id)"
                                >详情</ElButton
                            >
                            <!-- <ElButton
                                v-if="
                                    [SendStatus.PENDING, SendStatus.EXECUTING, SendStatus.PAUSED].includes(
                                        row.send_status
                                    )
                                "
                                :type="row.send_status == SendStatus.PAUSED ? 'success' : 'warning'"
                                link
                                class="!text-xs font-medium"
                                @click="handlePause(row)">
                                {{ row.send_status == SendStatus.PAUSED ? "恢复" : "暂停" }}
                            </ElButton> -->
                            <div
                                v-if="
                                    [
                                        SendStatus.PENDING,
                                        SendStatus.PAUSED,
                                        SendStatus.FAILED,
                                        SendStatus.COMPLETED,
                                    ].includes(row.send_status)
                                "
                                class="w-[1px] h-3 bg-slate-200 mx-1"></div>
                            <ElButton type="danger" link class="!text-[13px] font-medium" @click="handleDelete(row.id)"
                                >删除</ElButton
                            >
                        </div>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <ElEmpty :image-size="120" description="暂无朋友圈任务"></ElEmpty>
                </template>
            </ElTable>
        </div>
        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
            <span class="text-xs font-medium text-[#94A3B8]"
                >显示 {{ pager.lists.length }} 条，共 {{ pager.count }} 条朋友圈任务</span
            >
            <pagination v-model="pager" @change="getLists"></pagination>
        </div>
    </div>
    <add-task ref="addTaskRef" v-if="showAddTask" @close="showAddTask = false" @success="getLists" />
</template>

<script setup lang="ts">
import { circleTaskLists, circleTaskDelete, circleTaskUpdate } from "@/api/person_wechat";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import AddTask from "./add.vue";

const nuxtApp = useNuxtApp();
enum SendStatus {
    PENDING = 0,
    EXECUTING = 1,
    COMPLETED = 2,
    FAILED = 3,
    PAUSED = 4,
}

const queryParams = reactive({
    send_status: "",
    wechat_id: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: circleTaskLists,
    params: queryParams,
});

const addTaskRef = ref<InstanceType<typeof AddTask>>();
const showAddTask = ref(false);

const handleAddCircle = async () => {
    showAddTask.value = true;
    await nextTick();
    addTaskRef.value?.open();
};

const handleDetail = async (id: number) => {
    showAddTask.value = true;
    await nextTick();
    addTaskRef.value?.open();
    addTaskRef.value?.getDetail(id);
};

const handleDelete = async (id: string) => {
    await nuxtApp.$confirm({
        message: "确定要删除任务吗？",
        onConfirm: async () => {
            try {
                await circleTaskDelete({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const handlePause = async (row: any) => {
    await nuxtApp.$confirm({
        message: `确定要${row.send_status == SendStatus.PAUSED ? "恢复" : "暂停"}任务吗？`,
        onConfirm: async () => {
            try {
                await circleTaskUpdate({
                    id: row.id,
                    send_status: row.send_status == SendStatus.PAUSED ? SendStatus.PENDING : SendStatus.PAUSED,
                });
                feedback.msgSuccess("操作成功");
                getLists();
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const getAttachmentType = (type: number) => {
    const map = {
        [MaterialTypeEnum.IMAGE]: "图片",
        [MaterialTypeEnum.VIDEO]: "视频",
        [MaterialTypeEnum.LINK]: "链接",
        [MaterialTypeEnum.MINI_PROGRAM]: "小程序",
        [MaterialTypeEnum.FILE]: "文件",
        [MaterialTypeEnum.TEXT]: "文本",
    };
    return map[type];
};

const getStatusClass = (status: number) => {
    const map = {
        [SendStatus.PENDING]: "is-pending",
        [SendStatus.EXECUTING]: "is-executing",
        [SendStatus.COMPLETED]: "is-completed",
        [SendStatus.FAILED]: "is-failed",
        [SendStatus.PAUSED]: "is-paused",
    };
    return map[status];
};

const getStatusText = (status: number) => {
    const map = {
        [SendStatus.PENDING]: "待执行",
        [SendStatus.EXECUTING]: "执行中",
        [SendStatus.COMPLETED]: "已完成",
        [SendStatus.FAILED]: "已失败",
        [SendStatus.PAUSED]: "已暂停",
    };
    return map[status];
};

getLists();
</script>

<style scoped lang="scss">
.status-pill {
    @apply flex items-center gap-1.5 px-3 py-1 rounded-full w-fit border text-xs font-medium;

    .dot {
        @apply w-1.5 h-1.5 rounded-full;
    }

    &.is-pending {
        @apply bg-slate-50 border-slate-200 text-slate-500;
        .dot {
            @apply bg-slate-400;
        }
    }
    &.is-executing {
        @apply bg-blue-50 border-blue-100 text-blue-600;
        .dot {
            @apply bg-blue-500 animate-pulse;
        }
    }
    &.is-completed {
        @apply bg-green-50 border-green-100 text-green-600;
        .dot {
            @apply bg-green-500;
        }
    }
    &.is-failed {
        @apply bg-red-50 border-red-100 text-red-600;
        .dot {
            @apply bg-red-500;
        }
    }
    &.is-paused {
        @apply bg-amber-50 border-amber-100 text-amber-600;
        .dot {
            @apply bg-amber-500;
        }
    }
}

.type-tag {
    @apply px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[11px] font-medium;
}
</style>
