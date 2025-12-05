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
        @close="stopStream">
        <template #content>
            <div class="h-full"></div>
        </template>
    </Chatting>
</template>

<script setup lang="ts">
import { useChatManager } from "@/pages/index/_modules/composables/useChatManager";
import { useChatStore } from "@/pages/index/_modules/stores/chat";
import { useUserStore } from "@/stores/user";
/**
 * @description 标准智能体聊天组件
 */
const props = defineProps<{
    agentId: string;
    agentName?: string;
    taskId: string | null;
    detail: any;
}>();

const emit = defineEmits(["new-conversation", "update:isReceiving"]);

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const chatStore = useChatStore();
const { chattingRef, chatContentList, isStopChat, isReceiving, initialize, sendMessage, stopStream, fetchChatHistory } =
    useChatManager();

// 监听isReceiving状态并通知父组件
watch(isReceiving, (val) => emit("update:isReceiving", val));

// 发送消息
const onContentPost = async (content: string) => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    const isNewConversation = !props.taskId;
    await sendMessage(content, false, () => {
        // 如果是新对话，通知父组件
        if (isNewConversation) {
            setTimeout(() => {
                emit("new-conversation", {
                    task_id: chatStore.taskId,
                    title: content,
                });
            }, 500);
        }
    });
};

// 监听任务ID变化
watch(
    () => props.taskId,
    async (newId) => {
        chatStore.clearChat();
        await nextTick();
        setTimeout(() => {
            chatStore.setAgent({
                id: props.agentId,
                name: props.agentName || "",
            });
            stopStream();
        }, 500);

        if (newId) {
            chatStore.setTaskId(newId || "");

            await fetchChatHistory();
            chattingRef.value?.resetScroll();
        }
    },
    { immediate: true, deep: true }
);

watch(
    () => props.detail,
    (newVal) => {
        chatStore.setAgent({
            id: props.agentId,
            name: props.agentName || "",
        });
        chatStore.setDetail({
            logo: newVal.image,
            name: newVal.name,
        });
        initialize();
    }
);

onUnmounted(() => {
    chatStore.clearChat();
});
</script>
