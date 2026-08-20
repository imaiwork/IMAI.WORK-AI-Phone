<template>
    <template v-for="msg in messages" :key="msg.id">
        <chat-msg-user
            v-if="msg.role === 'user'"
            :text="msg.text"
            :attachments="msg.attachments"
            :user-avatar="userAvatar" />
        <chat-msg-assistant v-else :show-avatar="false">
            <output-image :task="msg.task" @regenerate="(task) => $emit('regenerate', task)" />
        </chat-msg-assistant>
    </template>
</template>

<script setup lang="ts">
import ChatMsgUser from './chat-msg-user.vue'
import ChatMsgAssistant from './chat-msg-assistant.vue'
import OutputImage from './output-image.vue'

defineProps<{
    messages: any[]
    userAvatar: string
}>()

defineEmits<{
    (e: 'regenerate', task: any): void
}>()
</script>
