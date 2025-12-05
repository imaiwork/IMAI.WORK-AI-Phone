<template>
    <div class="h-full flex flex-col">
        <!-- 表格区域 -->
        <div class="grow min-h-0">
            <ElTable
                :data="pager.lists"
                height="100%"
                stripe
                :header-cell-style="{ height: '64px' }"
                :row-style="{ height: '60px' }">
                <ElTableColumn label="用户信息" min-width="160">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-x-2">
                            <ElAvatar :src="row.avatar" />
                            <div>
                                <div>{{ row.nickname }}</div>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="提问问题" prop="message" show-overflow-tooltip min-width="200" />
                <ElTableColumn label="回答内容" prop="reply" show-overflow-tooltip min-width="200" />
                <ElTableColumn label="应用端" prop="source" min-width="100" />
                <ElTableColumn label="提问时间" prop="create_time" width="180" />
                <ElTableColumn label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <ElButton type="primary" link @click="handleReply(row)">查看</ElButton>
                        <ElButton type="danger" link @click="handleDelete(row.task_id)">删除</ElButton>
                    </template>
                </ElTableColumn>
            </ElTable>
        </div>
        <!-- 分页区域 -->
        <div class="flex justify-center my-4">
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
        </div>
    </div>
    <reply ref="replyRef" v-if="showReply" @close="showReply = false" />
</template>

<script setup lang="ts">
import { getChatRecord, deleteChatRecord } from "@/api/chat";
import Reply from "./reply.vue";

const props = defineProps<{
    agentId: string | number;
}>();

const chatType = 9006;

// 使用 usePaging 组合式函数进行分页
const { pager, getLists } = usePaging({
    // TODO: 替换为实际的API请求函数
    fetchFun: getChatRecord,
    params: {
        robot_id: props.agentId,
        type: chatType,
    },
});

const replyRef = ref<InstanceType<typeof Reply>>();
const showReply = ref(false);

const handleReply = async (row: any) => {
    showReply.value = true;
    await nextTick();
    replyRef.value.open();
    replyRef.value.setFormData(row);
};
const handleDelete = async (task_id: number) => {
    await useNuxtApp().$confirm({
        message: "确定删除此记录吗？",
        onConfirm: async () => {
            try {
                await deleteChatRecord({ task_id, robot_id: props.agentId, chat_type: chatType });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};
getLists();
</script>

<style scoped lang="scss">
:deep(.el-table) {
    thead th.el-table__cell.is-leaf {
        border-top: 0;
    }
}
</style>
