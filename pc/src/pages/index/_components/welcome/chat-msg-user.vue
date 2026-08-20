<template>
    <div class="msg user ml-auto flex max-w-[88%] items-start gap-2.5 self-end">
        <div
            class="bubble break-words rounded-[14px] bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] px-3.5 py-2.5 text-sm leading-relaxed text-white">
            <div v-if="attachments?.length" class="mb-2 flex flex-wrap gap-1.5">
                <div
                    v-for="a in attachments"
                    :key="a.id"
                    class="h-[88px] w-[88px] shrink-0 overflow-hidden rounded-lg border border-white/30 bg-white/20">
                    <img v-if="a.url" :src="a.url" :alt="a.name" class="block h-full w-full object-cover" />
                    <div v-else class="flex h-full w-full items-center justify-center text-[28px] text-white">📄</div>
                </div>
            </div>
            <div v-if="text" class="break-words">{{ text }}</div>
        </div>
        <div
            class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold text-white">
            <img v-if="userAvatar" :src="userAvatar" alt="" class="h-full w-full object-cover" />
            <span v-else>我</span>
        </div>
    </div>
</template>

<script setup lang="ts">
export interface ChatMsgAttachment {
    id: number
    url: string
    name: string
}

withDefaults(
    defineProps<{
        text?: string
        attachments?: ChatMsgAttachment[]
        userAvatar?: string
    }>(),
    {
        text: '',
        attachments: () => [],
        userAvatar: ''
    }
)
</script>
