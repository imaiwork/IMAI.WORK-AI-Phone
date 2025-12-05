<template>
    <ChattingFlow :detail="detail" :result="flowResult" @success="handleFlowSuccess" />
</template>

<script setup lang="ts">
import { cozeAgentChatRecord } from "@/api/agent";
import ChattingFlow from "../chatting-flow/index.vue";

/**
 * @description Coze工作流聊天组件
 */
const props = defineProps<{
    cozeId: string;
    detail: any;
    conversationId: string | null;
}>();

const emit = defineEmits(["new-conversation"]);

const flowResult = ref<any>({});
const chatContentLoading = ref(false);

// 获取聊天记录 (工作流只有一个结果)
const getChatList = async (convId: string) => {
    if (!convId) {
        flowResult.value = {};
        return;
    }
    chatContentLoading.value = true;
    try {
        const { lists } = await cozeAgentChatRecord({
            bot_id: props.cozeId,
            type: 2, // Coze工作流类型
            conversation_id: convId,
            page_size: 1,
        });
        flowResult.value = lists.length ? lists[0] : {};
    } catch (error) {
        feedback.msgError((error as string) || "发生错误");
    } finally {
        chatContentLoading.value = false;
    }
};

// 工作流运行成功回调
const handleFlowSuccess = (data: any) => {
    // 通知父组件创建了新会话
    emit("new-conversation", {
        id: data.conversation_id,
        title: "工作流运行结果", // 工作流没有用户输入，给一个默认标题
        ...data,
    });
};

// 监听对话ID变化
watch(
    () => props.conversationId,
    (newId) => {
        getChatList(newId || "");
    },
    { immediate: true }
);
</script>
