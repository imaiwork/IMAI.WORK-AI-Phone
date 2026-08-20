<template>
    <template v-for="msg in messages" :key="msg.id">
        <chat-msg-user v-if="msg.role === 'user'" :text="msg.text" :user-avatar="userAvatar" />
        <chat-msg-assistant
            v-else
            :avatar-bg="assistantAvatar.bg"
            :avatar-icon="assistantAvatar.icon"
            :avatar-label="assistantAvatar.label">
            <div v-if="msg.state === 'thinking'" class="thinking">
                <span class="dot-spinner" />
                AI 正在思考，生成定制化问卷…
            </div>
            <ppt-followup
                v-else-if="msg.state === 'followup'"
                :description="msg.fuDescription ?? ''"
                :ppt-type="msg.fuPptType ?? ''"
                :fields="msg.fuFields ?? []"
                @confirm="(payload) => $emit('followupConfirm', msg.id, payload)"
                @cancel="$emit('followupCancel', msg.id)" />
            <div v-else-if="msg.state === 'generating'" class="flex items-start gap-3 px-0.5 py-1">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] border border-[#bfdbfe] bg-gradient-to-b from-[#eff6ff] to-[#dbeafe]"
                    aria-hidden="true">
                    <span
                        class="h-3.5 w-3.5 animate-[thinkSpin_0.8s_linear_infinite] rounded-full border-2 border-[#dbeafe] border-t-[#2563eb]" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-[#1f2937]">正在生成 PPT</div>
                    <div class="mt-0.5 text-xs text-[#6b7280]">
                        预计 {{ msg.pageCount }} 页 · 已完成 {{ msg.slides?.length ?? 0 }} 页，可在右侧预览
                    </div>
                </div>
            </div>
            <div v-else-if="msg.state === 'done'" class="min-w-[280px] max-w-[420px]">
                <div class="flex items-start gap-2.5">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] border border-[#a7f3d0] bg-gradient-to-b from-[#ecfdf5] to-[#d1fae5] text-[#059669]"
                        aria-hidden="true">
                        <Icon name="el-icon-SuccessFilled" :size="18" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-[#1f2937]">PPT 已生成</span>
                            <span
                                class="rounded-full bg-[#eff6ff] px-2 py-px text-[11px] font-semibold text-[#2563eb]"
                                >{{ msg.slides?.length ?? 0 }} 页</span
                            >
                        </div>
                        <div class="mt-0.5 truncate text-xs text-[#6b7280]" :title="msg.topic">{{ msg.topic }}</div>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-2xl border border-transparent bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] px-3 py-1.5 text-xs font-medium text-white"
                        @click="$emit('viewPpt', msg.id)">
                        <Icon name="el-icon-View" :size="14" />
                        查看预览
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-2xl border border-[#e5e7eb] bg-white px-3 py-1.5 text-xs font-medium text-[#4b5563] hover:border-[#93c5fd] hover:text-[#2563eb]"
                        @click="$emit('regeneratePpt', msg.id)">
                        <Icon name="el-icon-RefreshRight" :size="14" />
                        重新生成
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-2xl border border-[#e5e7eb] bg-white px-3 py-1.5 text-xs font-medium text-[#4b5563] hover:border-[#93c5fd] hover:text-[#2563eb]"
                        @click="$emit('exportPpt', msg)">
                        <Icon name="el-icon-Download" :size="14" />
                        导出
                    </button>
                </div>
            </div>
        </chat-msg-assistant>
    </template>
</template>

<script setup lang="ts">
import ChatMsgUser from './chat-msg-user.vue'
import ChatMsgAssistant from './chat-msg-assistant.vue'
import PptFollowup from './ppt-followup.vue'

defineProps<{
    messages: any[]
    userAvatar: string
    assistantAvatar: { bg: string; icon?: string; label: string }
}>()

defineEmits<{
    (e: 'followupConfirm', id: number, payload: any): void
    (e: 'followupCancel', id: number): void
    (e: 'viewPpt', id: number): void
    (e: 'regeneratePpt', id: number): void
    (e: 'exportPpt', msg: any): void
}>()
</script>

<style lang="scss" scoped>
.thinking {
    @apply flex items-center gap-2.5 px-1 py-1.5 text-[13px] text-[#6b7280];
}
.dot-spinner {
    @apply h-3.5 w-3.5 animate-[thinkSpin_0.8s_linear_infinite] rounded-full border-2 border-[#dbeafe] border-t-[#2563eb];
}
@keyframes thinkSpin {
    to {
        transform: rotate(360deg);
    }
}
</style>
