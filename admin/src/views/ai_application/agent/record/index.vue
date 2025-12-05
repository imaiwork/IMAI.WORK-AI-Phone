<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user"
                        placeholder="请输入用户昵称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="关键词">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.message"
                        placeholder="请输入关键词"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="提问时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_date"
                        v-model:endTime="queryParams.end_date" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4">
                <el-tabs v-model="activeTab" @tab-click="handleTabClick">
                    <el-tab-pane label="智能体" :name="AgentTypeEnum.AGENT"></el-tab-pane>
                    <el-tab-pane label="Coze智能体" :name="AgentTypeEnum.COZE_AGENT"></el-tab-pane>
                    <el-tab-pane label="Coze工作流" :name="AgentTypeEnum.COZE_FLOW"></el-tab-pane>
                </el-tabs>
            </div>
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <image-contain
                                radius="50%"
                                class="flex-none"
                                v-if="row.image || row.avatar"
                                :src="row.image || row.avatar"
                                :width="48"
                                :height="48"
                                :preview-src-list="[row.image || row.avatar]"
                                preview-teleported
                                fit="cover" />
                        </div>
                    </template>
                </el-table-column>
                <!-- 昵称 -->
                <el-table-column label="昵称" prop="nickname" min-width="140" />
                <el-table-column
                    label="提问内容"
                    min-width="140"
                    show-overflow-tooltip
                    v-if="activeTab != AgentTypeEnum.COZE_FLOW">
                    <template #default="{ row }">
                        {{ row.content || row.message }}
                    </template>
                </el-table-column>
                <el-table-column label="提问时间" prop="create_time" min-width="180" />
                <el-table-column label="消耗算力" prop="points" min-width="140">
                    <template #default="{ row }"> {{ row.points }}算力 </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleReply(row)">查看</el-button>
                        <el-button
                            v-perms="['ai_application.agent.record/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
    <reply-pop v-if="showReplyPop" ref="replyPopRef" @close="showReplyPop = false" />
</template>
<script lang="ts" setup>
import { usePaging } from "@/hooks/usePaging";
import { getAgentChatRecord, deleteAgentChatRecord } from "@/api/ai_application/agent";
import { getCozeAgentRecordList, cozeAgentRecordDelete } from "@/api/ai_application/agent/coze";
import feedback from "@/utils/feedback";
import ReplyPop from "./reply-pop.vue";
import { AgentTypeEnum } from "../lists/components/enums";

const activeTab = ref(AgentTypeEnum.AGENT);
const queryParams = reactive({
    user: "",
    message: "",
    start_date: "",
    end_date: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: (params) => {
        if (activeTab.value === AgentTypeEnum.AGENT) {
            return getAgentChatRecord(params);
        } else {
            return getCozeAgentRecordList({
                ...params,
                type: activeTab.value,
            });
        }
    },
    params: queryParams,
});

const handleTabClick = (tab: any) => {
    activeTab.value = tab.paneName;
    getLists();
};

const handleDelete = async (row: any) => {
    await feedback.confirm("确定要删除吗？");
    if (activeTab.value === AgentTypeEnum.AGENT) {
        await deleteAgentChatRecord({ id: row.id });
    } else {
        await cozeAgentRecordDelete({ id: row.id, conversation_id: row.conversation_id });
    }
    getLists();
};

const showReplyPop = ref(false);
const replyPopRef = ref<InstanceType<typeof ReplyPop>>();
const handleReply = async (row: any) => {
    showReplyPop.value = true;
    await nextTick();
    await replyPopRef.value?.open({
        content: row.content || row.message,
        reply: row.reply,
        type: activeTab.value,
        ...row,
    });
};

getLists();
</script>
