<template>
    <div
        class="top-0 absolute w-[380px] h-full bg-white/95 backdrop-blur-xl rounded-tr-[24px] rounded-br-[24px] border-l border-[#F1F5F9] left-full flex flex-col z-20 shadow-[20px_0_40px_rgba(0,0,0,0.05)] overflow-hidden animate-in slide-in-from-left duration-300">
        <div class="p-6 flex items-center justify-between border-b border-[#F8FAFC]">
            <div>
                <div class="text-[18px] font-[900] text-[#1E293B] flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                    AI 灵感助手
                </div>
                <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest mt-0.5">
                    Prompt Generator
                </div>
            </div>
            <div class="w-6 h-6" @click="emit('close')">
                <close-btn />
            </div>
        </div>

        <div class="grow min-h-0">
            <ElScrollbar ref="scrollRef">
                <div class="p-6 space-y-6 content-box">
                    <div
                        v-if="prompts.length === 0 && !isReceiving"
                        class="h-60 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4">
                            <Icon name="el-icon-MagicStick" :size="32" color="#CBD5E1"></Icon>
                        </div>
                        <p class="text-[#94A3B8] text-[13px] font-medium">描述你的想法，我来为你润色</p>
                    </div>

                    <div
                        v-for="(item, index) in prompts"
                        :key="index"
                        class="prompt-card group animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="prompt-content">
                            {{ item }}
                        </div>

                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-[#F1F5F9]/50">
                            <div class="flex gap-2">
                                <div
                                    class="action-btn hover:text-[#EF4444] hover:bg-red-50"
                                    @click="handleDelete(index)">
                                    <Icon name="el-icon-Delete" :size="14"></Icon>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <div class="action-btn hover:text-primary hover:bg-[#F5F7FF]" @click="copy(item)">
                                    <Icon name="el-icon-DocumentCopy" :size="14"></Icon>
                                    <span class="ml-1">复制</span>
                                </div>
                                <div class="use-btn" @click="handleUse(item)">
                                    <span class="mr-1">使用此创意</span>
                                    <Icon name="el-icon-ArrowRight" :size="12"></Icon>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="isReceiving" class="flex flex-col items-center py-4">
                        <div class="loader-dots"><span></span><span></span><span></span></div>
                        <span class="text-[11px] text-[#94A3B8] font-medium uppercase mt-3 tracking-[0.2em]"
                            >Thinking...</span
                        >
                    </div>
                </div>
            </ElScrollbar>
        </div>

        <div class="p-6 bg-slate-50 border-t border-[#F1F5F9]">
            <div class="relative">
                <ElInput
                    v-model="prompt"
                    type="textarea"
                    :rows="4"
                    resize="none"
                    placeholder="输入简单的中文关键词..."
                    class="custom-textarea"
                    @keydown.enter.prevent="lockGenerateAiPrompt()"></ElInput>

                <div class="absolute bottom-3 right-3 flex items-center gap-3">
                    <span class="text-[10px] font-medium text-[#CBD5E1]">{{ prompt.length }}/500</span>
                    <div
                        class="send-btn"
                        :class="{ 'is-loading': isReceiving || !prompt.trim() }"
                        @click="lockGenerateAiPrompt()">
                        <Icon v-if="!isReceiving" name="el-icon-Promotion" :size="18"></Icon>
                        <Icon v-else name="el-icon-Loading" class="animate-spin" :size="18"></Icon>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-[10px] text-[#94A3B8] font-medium leading-relaxed">
                * AI 会根据您的描述自动优化为中英文结合的专业绘图提示词。
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { chatPrompt } from "@/api/chat";
import { useUserStore } from "@/stores/user";

const emit = defineEmits(["use", "close"]);

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const prompt = ref("");
const prompts = ref<any[]>([]);
const promptId = ref();
const isReceiving = ref(false);

const generateAiPrompt = async (text?: string) => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }

    if (isReceiving.value) {
        feedback.msgWarning("正在生成中，请稍后再试");
        return;
    }
    if (!text && !prompt.value) {
        feedback.msgWarning("请输入创意描述");
        return;
    }
    try {
        isReceiving.value = true;
        const { reply } = await chatPrompt({
            message: text || prompt.value,
            prompt_id: promptId.value,
        });
        prompt.value = "";
        prompts.value.push(reply);
        setTimeout(() => {
            scrollBottom();
        }, 500);
    } catch (error) {
        feedback.msgError(error || "生成提示词失败");
    } finally {
        isReceiving.value = false;
        setTimeout(() => {
            scrollBottom();
        }, 500);
    }
};

const startGenerate = (options: { prompt?: string; promptId?: number }) => {
    promptId.value = options.promptId;
    // lockGenerateAiPrompt(options.prompt);
};

const scrollRef = shallowRef();
const scrollBottom = () => {
    scrollRef.value?.scrollTo(document.querySelector(".content-box").clientHeight);
};

const { copy } = useCopy();

const handleDelete = (index: number) => {
    prompts.value.splice(index, 1);
};

const handleUse = (item: string) => {
    emit("use", item);
    emit("close");
};

const { lockFn: lockGenerateAiPrompt, isLock } = useLockFn(generateAiPrompt);

defineExpose({
    startGenerate,
});
</script>

<style scoped lang="scss">
/* 灵感卡片样式 */
.prompt-card {
    @apply bg-white rounded-[20px] p-5 border border-[#F1F5F9] shadow-light hover:shadow-light transition-all duration-300;

    .prompt-content {
        @apply text-[14px] leading-relaxed text-[#475569] font-medium;
    }
}

/* 按钮样式 */
.action-btn {
    @apply flex items-center px-3 py-1.5 rounded-lg text-[#94A3B8] text-xs font-medium cursor-pointer transition-all;
}

.use-btn {
    @apply flex items-center px-4 py-1.5 rounded-xl bg-primary text-white text-xs font-black cursor-pointer hover:bg-[#4338CA] shadow-light shadow-[#0065fb]/20 active:scale-95 transition-all;
}

/* 发送按钮 */
.send-btn {
    @apply w-11 h-11 rounded-xl bg-primary text-white flex items-center justify-center cursor-pointer shadow-light shadow-[#0065fb]/20 transition-all;
    &.is-loading {
        @apply opacity-50 cursor-not-allowed bg-[#CBD5E1] shadow-[none];
    }
    &:not(.is-loading):hover {
        @apply -translate-y-0.5 bg-[#4338CA];
    }
}

/* 生成中的小球动画 */
.loader-dots {
    @apply flex gap-1.5;
    span {
        @apply w-1.5 h-1.5 bg-primary rounded-full;
        animation: dot-pulse 1.4s infinite ease-in-out both;
        &:nth-child(1) {
            animation-delay: -0.32s;
        }
        &:nth-child(2) {
            animation-delay: -0.16s;
        }
    }
}

@keyframes dot-pulse {
    0%,
    80%,
    100% {
        transform: scale(0);
        opacity: 0.3;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
