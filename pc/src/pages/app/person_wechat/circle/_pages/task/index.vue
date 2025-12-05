<template>
    <div class="h-full flex flex-col bg-white rounded-xl">
        <div class="flex items-center justify-between flex-shrink-0 p-4">
            <ElButton type="primary" @click="handleAddCircle">添加朋友圈任务</ElButton>
            <div class="flex items-center justify-end gap-2 grow">
                <ElSelect
                    v-model="queryParams.send_status"
                    class="!w-[140px]"
                    placeholder="请选择状态"
                    clearable
                    :empty-values="[null, undefined]"
                    @change="resetPage()">
                    <ElOption label="全部" value=""></ElOption>
                    <ElOption label="待执行" :value="SendStatus.PENDING"></ElOption>
                    <ElOption label="执行中" :value="SendStatus.EXECUTING"></ElOption>
                    <ElOption label="已完成" :value="SendStatus.COMPLETED"></ElOption>
                    <ElOption label="已失败" :value="SendStatus.FAILED"></ElOption>
                    <ElOption label="暂停中" :value="SendStatus.PAUSED"></ElOption>
                </ElSelect>
                <div>
                    <ElInput v-model="queryParams.wechat_id" placeholder="请选择微信号">
                        <template #append>
                            <ElButton @click="getLists()">
                                <Icon name="el-icon-Search"></Icon>
                            </ElButton>
                        </template>
                    </ElInput>
                </div>
            </div>
        </div>
        <div class="grow min-h-0 flex flex-col overflow-hidden">
            <div class="grow min-h-0">
                <ElTable
                    :data="pager.lists"
                    stripe
                    height="100%"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '60px' }"
                    v-loading="pager.loading">
                    <ElTableColumn prop="wechat_nickname" label="发送账号" min-width="200">
                        <template #default="{ row }"> {{ row.wechat_nickname }}（{{ row.wechat_id }}） </template>
                    </ElTableColumn>
                    <ElTableColumn prop="device_model" label="朋友圈类型" width="120">
                        <template #default="{ row }">
                            {{ getAttachmentType(row.attachment_type) }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="send_status" label="状态" width="120">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2 justify-center">
                                <span class="text-warning" v-if="row.send_status == SendStatus.EXECUTING">执行中</span>
                                <span class="text-success" v-if="row.send_status == SendStatus.COMPLETED">已完成</span>
                                <span class="text-[#918980]" v-if="row.send_status == SendStatus.PAUSED">暂停中</span>
                                <span class="text-danger" v-if="row.send_status == SendStatus.FAILED">已失败</span>
                                <span class="text-info" v-if="row.send_status == SendStatus.PENDING">待执行</span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="发送类型" width="120">
                        <template #default="{ row }">
                            {{ row.task_type == 1 ? "定时发送" : "即时" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="send_time" label="执行时间" width="180">
                        <template #default="{ row }">
                            {{ row.send_time || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="finish_time" label="完成时间" width="180">
                        <template #default="{ row }">
                            {{ row.finish_time || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="操作" width="160" fixed="right">
                        <template #default="{ row }">
                            <ElButton type="primary" link @click="handleDetail(row.id)">详情</ElButton>
                            <ElButton
                                type="warning"
                                link
                                @click="handlePause(row)"
                                v-if="
                                    [SendStatus.PENDING, SendStatus.EXECUTING, SendStatus.PAUSED].includes(
                                        row.send_status
                                    )
                                "
                                >{{ row.send_status == SendStatus.PAUSED ? "恢复" : "暂停" }}</ElButton
                            >
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

getLists();
</script>

<style scoped lang="scss"></style>
