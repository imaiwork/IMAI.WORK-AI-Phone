<template>
    <div class="h-full min-w-[1100px] p-4">
        <div class="h-full flex rounded-[20px] bg-white overflow-hidden">
            <!-- 不可用：整页阻断（防止手动改 URL 强行进入） -->
            <div
                v-if="isAgentUnavailable"
                class="grow flex flex-col items-center justify-center px-8 text-center gap-4">
                <div
                    class="w-16 h-16 rounded-full bg-[#FFF7E6] border border-[#F4B400]/30 flex items-center justify-center">
                    <Icon name="el-icon-WarningFilled" :size="32" color="#B45309" />
                </div>
                <div class="text-[20px] font-semibold text-slate-800">无法进入该智能体</div>
                <div class="text-sm text-slate-500 max-w-[360px]">
                    {{ AGENT_UNAVAILABLE_TIP }}，请联系管理员或升级会员后再试。
                </div>
                <ElButton type="primary" round class="mt-2" @click="router.replace('/agent')">返回智能体中心</ElButton>
            </div>

            <template v-else>
                <!-- 左侧聊天记录 -->
                <ChatHistory
                    :pager="chatRecordPager"
                    :current-record-id="currentRecordId"
                    :is-receiving="isReceiving"
                    @select="handleSelectRecord"
                    @create="handleCreateRecord"
                    @delete="handleDeleteRecord"
                    @load-more="loadRecordMore" />

                <!-- 右侧聊天窗口 -->
                <ChatArea :detail="detail" :loading="loading" :unavailable="isAgentUnavailable">
                    <component
                        :is="chatComponent"
                        :agent-id="agentId"
                        :coze-id="cozeId"
                        :detail="detail"
                        :unavailable="isAgentUnavailable"
                        :task-id="agentType === AgentTypeEnum.AGENT ? currentRecordId : null"
                        :conversation-id="agentType !== AgentTypeEnum.AGENT ? currentRecordId : null"
                        :agent-name="detail.name"
                        @new-conversation="handleNewConversation"
                        @conversation-id-change="handleConversationIdChange"
                        @update:is-receiving="(val) => (isReceiving = val)" />
                </ChatArea>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAgentManager } from "./_composables/useAgentManager";
import { AGENT_UNAVAILABLE_TIP } from "@/utils/agentPermission";
import { AgentTypeEnum } from "./_enums";
import ChatHistory from "./_components/chatting/chat-history.vue";
import ChatArea from "./_components/chatting/chat-area.vue";
import StandardAgentChat from "./_components/chatting/standard-agent-chat.vue";
import CozeAgentChat from "./_components/chatting/coze-agent-chat.vue";
import CozeFlowChat from "./_components/chatting/coze-flow-chat.vue";

const router = useRouter();

/**
 * @description 智能体聊天页面 (重构后)
 * @summary 动态加载不同类型的智能体聊天组件，实现逻辑分离。
 */

// 使用统一的管理器
const {
    agentId,
    cozeId,
    agentType,
    detail,
    loading,
    isAgentUnavailable,
    chatRecordPager,
    currentRecordId,
    loadRecordMore,
    resetChatRecordPager,
    handleSelectRecord: selectRecord,
    handleCreateRecord,
    handleDeleteRecord,
} = useAgentManager();

const isReceiving = ref(false);

// 动态选择聊天组件
const chatComponent = computed(() => {
    switch (agentType) {
        case AgentTypeEnum.AGENT:
            return markRaw(StandardAgentChat);
        case AgentTypeEnum.COZE_AGENT:
            return markRaw(CozeAgentChat);
        case AgentTypeEnum.COZE_FLOW:
            isReceiving.value = false; // 工作流没有持续的接收状态
            return markRaw(CozeFlowChat);
        default:
            return null;
    }
});

// 处理新对话创建
const handleNewConversation = async (record: any) => {
    await resetChatRecordPager();
    // 更新URL和当前选中ID
    selectRecord(record);
};

// 处理Coze对话ID变更
const handleConversationIdChange = (newId: string) => {
    currentRecordId.value = newId;
};

const handleSelectRecord = (record: any) => {
    if (isReceiving.value) {
        feedback.msgWarning("正在接收消息，请稍后再试~");
        return;
    }
    selectRecord(record);
};
</script>
