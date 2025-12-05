<template>
    <div
        class="absolute w-[350px] h-full bg-app-bg-3 rounded-tr-[20px] rounded-br-[20px] shadow-[0_0_0_1px_#333333] left-full flex flex-col py-[16px] z-20">
        <div class="absolute w-6 h-6 top-4 right-4 cursor-pointer" @click="emit('close')">
            <close-btn />
        </div>
        <div class="grow min-h-0 mt-[50px]">
            <ElScrollbar ref="scrollRef">
                <div class="px-[16px] content-box">
                    <div class="text-white">Deepseek- R1 灵感版</div>
                    <div v-for="(item, index) in prompts" :key="index" class="mt-4">
                        <div
                            class="rounded-md border border-app-border-2 bg-app-bg-3 mt-[11px] relative p-3"
                            :key="index">
                            <div class="text-[#ffffff4d]">
                                {{ item }}
                            </div>
                            <div class="mt-4">
                                <ElButton
                                    class="shadow-[0_0_0_1px_var(--app-border-color-2)] !h-[26px]"
                                    color="#1F1F1F"
                                    @click="handleDelete(index)"
                                    >删除</ElButton
                                >
                                <ElButton
                                    class="shadow-[0_0_0_1px_var(--app-border-color-2)] !h-[26px]"
                                    color="#1F1F1F"
                                    @click="copy(item)"
                                    >复制</ElButton
                                >
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <ElButton type="primary" class="!h-[26px]" @click="handleUse(item)">使用提示词</ElButton>
                        </div>
                    </div>
                    <div v-if="isReceiving" class="chat-loader mt-2 text-white"></div>
                </div>
            </ElScrollbar>
        </div>
        <div class="flex-shrink-0 px-[16px] mt-4">
            <div class="relative rounded-md shadow-[0_0_0_1px_var(--app-border-color-2)] bg-app-bg-3">
                <ElInput
                    v-model="prompt"
                    placeholder="请输入创意描述"
                    type="textarea"
                    resize="none"
                    :rows="4"></ElInput>
                <div class="flex justify-end">
                    <div
                        class="cursor-pointer m-2"
                        :class="prompt ? 'text-primary-light-9' : 'text-[#f4f4f4]'"
                        @click="generateAiPrompt()">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="30"
                            height="30"
                            viewBox="0 0 30 30"
                            fill="currentColor">
                            <rect opacity="0.05" width="30" height="30" rx="15" fill="currentColor" />
                            <path
                                :opacity="prompt ? '1' : '0.3'"
                                d="M10 14L15 9M15 9L20 14M15 9V21"
                                stroke="white"
                                stroke-width="1.4"
                                stroke-linecap="square"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
            </div>
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

    if (isReceiving.value) return;
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
    lockGenerateAiPrompt(options.prompt);
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
@import "@/pages/app/_assets/styles/index.scss";
:deep(.el-textarea__inner) {
    box-shadow: none !important;
    padding-bottom: 10px !important;
}
</style>
