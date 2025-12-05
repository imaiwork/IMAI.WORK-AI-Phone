<template>
    <Chatting
        ref="chattingRef"
        :is-new-chat="false"
        :is-stop="isStopChat"
        :is-disabled-humanize="true"
        :content-list="chatContentList"
        :send-disabled="isReceiving"
        :is-upload-file="false"
        :is-network="false"
        @content-post="onContentPost"
        @close="stopChat">
        <template #content>
            <div class="h-full"></div>
        </template>
    </Chatting>
</template>

<script setup lang="ts">
import { cozeAgentChatRecord } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import { useCozeChat } from "../../_composables/useCozeChat";

/**
 * @description Coze智能体聊天组件
 */
const props = defineProps<{
    agentId: string;
    cozeId: string;
    detail: any;
    conversationId: string | null;
}>();

const emit = defineEmits(["new-conversation", "conversation-id-change", "update:isReceiving"]);

const userStore = useUserStore();
const { userInfo, userTokens } = toRefs(userStore);
const chattingRef = ref();
const chatContentLoading = ref(false);

// 使用Coze聊天逻辑
const { chatContentList, isReceiving, isStopChat, sendMessage, stopChat, setConversationId } = useCozeChat(
    toRef(props, "detail"),
    props.agentId
);

// 监听isReceiving状态并通知父组件
watch(isReceiving, (val) => emit("update:isReceiving", val));

// 发送消息
const onContentPost = async (content: string) => {
    if (userTokens.value <= 1) {
        feedback.msgPowerInsufficient();
        return;
    }
    await sendMessage(
        content,
        (newId) => {
            emit("conversation-id-change", newId);
        },
        (conv) => {
            emit("new-conversation", conv);
        }
    );
};

// 获取聊天记录
const getChatList = async (convId: string) => {
    if (!convId) {
        chatContentList.value = [];
        return;
    }
    chatContentLoading.value = true;
    try {
        const { lists } = await cozeAgentChatRecord({
            bot_id: props.cozeId,
            type: 1, // Coze智能体类型
            conversation_id: convId,
            page_size: 9999,
        });
        const historyMessages = lists.map((item: any) =>
            item.role === "user"
                ? { ...item, type: 1, message: item.content, form_avatar: userInfo.value.avatar }
                : {
                      ...item,
                      type: 2,
                      reply: item.content,
                      form_avatar: props.detail?.avatar,
                      consume_tokens: {
                          total_tokens: item.token_total,
                      },
                  }
        );
        chatContentList.value = historyMessages;
        await nextTick();
        chattingRef.value?.scrollToBottom();
    } catch (error) {
        feedback.msgError((error as string) || "发生错误");
    } finally {
        chatContentLoading.value = false;
    }
};

// 监听对话ID变化
watch(
    () => props.conversationId,
    (newId) => {
        setConversationId(newId || "");
        if (!isReceiving.value) {
            getChatList(newId || "");
        }
        chattingRef.value?.resetScroll();
    },
    { immediate: true }
);

// 监听聊天内容变化，自动滚动到底部
watch(
    () => chatContentList.value,
    async () => {
        await nextTick();
        chattingRef.value?.scrollToBottom();
    },
    { deep: true }
);
</script>
