<template>
    <div class="w-full h-full px-2">
        <!-- 聊天组件 -->
        <Chatting
            ref="chattingRef"
            :content-list="chatContentList"
            :is-disabled-humanize="true"
            :send-disabled="isReceiving"
            :is-stop="isStopChat"
            :is-upload-file="false"
            :is-network="false"
            @content-post="sendMessage"
            @close="stopStream">
            <template #content>
                <div class="h-full"></div>
            </template>
        </Chatting>
    </div>
</template>

<script setup lang="ts">
import { useChatStore } from "@/pages/index/_modules/stores/chat";
import { useChatManager } from "@/pages/index/_modules/composables/useChatManager";

// 定义组件props
const props = defineProps<{
    modelValue: any; // 数据模型
    agentId: number; // 智能体ID
}>();

// store
const chatStore = useChatStore();

// 使用 useChatManager 组合式函数管理聊天逻辑
const { chattingRef, chatContentList, isStopChat, isReceiving, initialize, sendMessage, startNewChat, stopStream } =
    useChatManager();

// 组件挂载时初始化并设置机器人ID
onMounted(() => {
    initialize();
    chatStore.setAgent({
        id: props.agentId,
    });
});

// 组件卸载时清理聊天内容
onUnmounted(() => {
    chatStore.clearChat();
});

// 暴露 startNewChat 方法，供父组件调用
defineExpose({
    startNewChat,
});
</script>

<style scoped></style>
