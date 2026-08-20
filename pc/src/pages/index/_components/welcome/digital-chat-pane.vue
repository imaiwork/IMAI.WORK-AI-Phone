<template>
    <template v-for="msg in messages" :key="msg.id">
        <chat-msg-user v-if="msg.role === 'user'" :text="msg.text" :user-avatar="userAvatar" />

        <chat-msg-assistant
            v-else-if="msg.role === 'followup'"
            :avatar-bg="assistantAvatar.bg"
            :avatar-icon="assistantAvatar.icon"
            :avatar-label="assistantAvatar.label">
            <div v-if="msg.state === 'thinking-form'" class="thinking">
                <span class="dot-spinner" />
                AI 正在思考,生成定制化追问表单…
            </div>
            <ppt-followup
                v-else-if="msg.state === 'form-ready'"
                :description="msg.description ?? ''"
                :ppt-type="msg.pptType ?? ''"
                :fields="msg.fields ?? []"
                submit-label="提交生成文案"
                @confirm="(payload) => $emit('followupConfirm', msg.id, payload)"
                @cancel="$emit('followupCancel', msg.id)" />
            <div v-else-if="msg.state === 'collapsed'" class="rounded-xl border border-[#e5e7eb] bg-[#f9fafb] px-3 py-2.5">
                <div class="flex items-center gap-2 text-[13px]">
                    <span>📝</span>
                    <span class="font-semibold text-[#1f2937]">追问已完成</span>
                    <span
                        class="ml-auto cursor-pointer text-xs text-[#2563eb] hover:underline"
                        @click="msg.state = 'form-ready'"
                        >展开重填</span
                    >
                </div>
                <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-xs text-[#6b7280]">
                    <span v-for="(v, k) in msg.summary" :key="k">
                        <b class="text-[#374151]">{{ k }}:</b>{{ v }}
                    </span>
                </div>
            </div>
            <div v-else-if="msg.state === 'failed'" class="thinking">
                <Icon name="el-icon-WarningFilled" :size="14" />
                失败:{{ msg.errorMsg ?? '请重试' }}
            </div>
        </chat-msg-assistant>

        <chat-msg-assistant
            v-else-if="msg.role === 'copies'"
            :avatar-bg="assistantAvatar.bg"
            :avatar-icon="assistantAvatar.icon"
            :avatar-label="assistantAvatar.label">
            <div v-if="msg.state === 'thinking-params'" class="thinking">
                <span class="dot-spinner" />
                正在根据你的回答提炼文案参数…
            </div>
            <div v-else-if="msg.state === 'thinking-copy'" class="thinking">
                <span class="dot-spinner" />
                正在生成口播文案…
            </div>
            <template v-else-if="msg.state === 'ready'">
                <div class="mb-2 flex items-center gap-2 text-[13px] font-semibold text-[#1f2937]">
                    <span>✨</span>
                    <span>已生成 {{ msg.copies.length }} 条口播文案(可编辑)</span>
                </div>
                <div v-for="(c, ci) in msg.copies" :key="ci" class="mt-2.5 rounded-xl border border-[#f0f1f4] bg-[#fafbfc] p-3">
                    <div class="mb-1.5 text-xs font-semibold text-[#6b7280]">第 {{ ci + 1 }} 条</div>
                    <textarea
                        v-model="msg.copies[ci]"
                        class="w-full resize-none rounded-lg border border-[#e5e7eb] bg-white px-3 py-2 text-[13px] leading-relaxed text-[#1f2937] outline-none focus:border-[#93c5fd]"
                        rows="4" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        <div
                            class="inline-flex cursor-pointer items-center gap-1 rounded-2xl border border-[#e5e7eb] bg-white px-3 py-1.5 text-xs text-[#4b5563] hover:border-[#93c5fd] hover:text-[#2563eb]"
                            @click="$emit('copyText', msg.copies[ci])">
                            <Icon name="el-icon-DocumentCopy" :size="14" /> 复制
                        </div>
                        <div
                            class="inline-flex cursor-pointer items-center gap-1 rounded-2xl border border-[#e5e7eb] bg-white px-3 py-1.5 text-xs text-[#4b5563] hover:border-[#93c5fd] hover:text-[#2563eb]"
                            :class="{ 'pointer-events-none opacity-50': msg.regenerating[ci] }"
                            @click="!msg.regenerating[ci] && $emit('regenerateCopy', msg.id, ci)">
                            <Icon name="el-icon-RefreshRight" :size="14" />
                            {{ msg.regenerating[ci] ? '重新生成中…' : '重新生成' }}
                        </div>
                        <div
                            class="inline-flex cursor-pointer items-center gap-1 rounded-2xl border border-transparent bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] px-3 py-1.5 text-xs font-medium text-white"
                            @click="$emit('useOneCopy', msg.id, ci)">
                            <Icon name="el-icon-VideoCamera" :size="14" />
                            用此条生成视频
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <div
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-2xl border border-transparent bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] px-4 py-2 text-xs font-semibold text-white"
                        @click="$emit('useAllCopies', msg.id)">
                        <Icon name="el-icon-VideoPlay" :size="14" />
                        使用全部文案生成视频
                    </div>
                </div>
            </template>
            <template v-else-if="msg.state === 'collapsed'">
                <div class="rounded-xl border border-[#e5e7eb] bg-[#f9fafb] px-3 py-2.5">
                    <div class="flex items-center gap-2 text-[13px]">
                        <span>📄</span>
                        <span class="font-semibold text-[#1f2937]">文案已使用({{ msg.copies.length }} 条)</span>
                        <span
                            class="ml-auto cursor-pointer text-xs text-[#2563eb] hover:underline"
                            @click="msg.state = 'ready'"
                            >展开编辑</span
                        >
                    </div>
                </div>
            </template>
            <div v-else-if="msg.state === 'failed'" class="thinking">
                <Icon name="el-icon-WarningFilled" :size="14" />
                失败:{{ msg.errorMsg ?? '请重试' }}
            </div>
        </chat-msg-assistant>

        <chat-msg-assistant
            v-else-if="msg.role === 'video-task'"
            :avatar-bg="assistantAvatar.bg"
            :avatar-icon="assistantAvatar.icon"
            :avatar-label="assistantAvatar.label">
            <div v-if="msg.state === 'uploading'" class="thinking">
                <span class="dot-spinner" />
                正在上传素材 {{ msg.uploadProgress?.done ?? 0 }}/{{ msg.uploadProgress?.total ?? 0 }}…
            </div>
            <div v-else-if="msg.state === 'starting'" class="thinking">
                <span class="dot-spinner" />
                正在为 {{ msg.copies.length }} 条文案启动视频生成任务…
            </div>
            <div v-else-if="msg.state === 'ready'" class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-3.5 py-3">
                <div class="flex items-center gap-2 text-[13px] font-semibold text-[#166534]">
                    <span>🎬</span>
                    已提交 {{ msg.copies.length }} 个视频生成任务
                </div>
                <div class="mt-1 text-xs text-[#4b5563]">渲染需要 2-5 分钟,可去「我的作品」查看进度</div>
            </div>
            <div v-else-if="msg.state === 'failed'" class="thinking">
                <Icon name="el-icon-WarningFilled" :size="14" />
                失败:{{ msg.errorMsg ?? '请重试' }}
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
    (e: 'copyText', text: string): void
    (e: 'regenerateCopy', id: number, idx: number): void
    (e: 'useOneCopy', id: number, idx: number): void
    (e: 'useAllCopies', id: number): void
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
