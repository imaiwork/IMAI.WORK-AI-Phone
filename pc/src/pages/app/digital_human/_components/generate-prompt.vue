<template>
    <popup
        v-model="show"
        width="620px"
        top="8vh"
        style="padding: 0; background-color: #ffffff"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        :show-close="false"
        @close="close">
        <div class="flex flex-col max-h-[85vh]">
            <div class="px-8 py-5 flex items-center justify-between shrink-0 border-b border-br-extra-light">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                        <Icon name="el-icon-MagicStick" color="var(--color-primary)" :size="20" />
                    </div>
                    <span class="text-gray-950 text-lg font-black tracking-tight">AI 智能创作文案</span>
                </div>
                <div class="w-9 h-9" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="px-6 py-6 bg-gray-50/50 grow overflow-y-auto custom-scrollbar">
                <div class="flex flex-col gap-5">
                    <div class="bg-white rounded-2xl p-6 border border-br-extra-light transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-black text-gray-950 flex items-center gap-1.5">
                                文案主题描述
                                <ElTooltip content="详细的描述能让 AI 生成更精准的内容" placement="top">
                                    <Icon
                                        name="el-icon-QuestionFilled"
                                        color="var(--el-text-color-placeholder)"
                                        :size="14" />
                                </ElTooltip>
                            </span>
                            <div
                                class="text-[11px] font-bold text-primary bg-[#0065FB]/5 px-2 py-1 rounded-md cursor-pointer hover:bg-[#0065FB]/10 transition-all select-none flex items-center justify-center gap-1"
                                @click="setRandomSubject">
                                <Icon name="el-icon-Refresh" />
                                <span>试试随机主题</span>
                            </div>
                        </div>

                        <div class="relative">
                            <ElInput
                                v-model="contentVal"
                                type="textarea"
                                resize="none"
                                maxlength="500"
                                show-word-limit
                                :rows="4"
                                :disabled="isLock"
                                class="custom-textarea"
                                placeholder="描述您的推广目标、受众或核心卖点..." />
                        </div>

                        <div class="mt-6 flex items-center justify-between">
                            <div class="text-sm font-black text-gray-950">期望文案长度</div>
                            <div class="flex bg-gray-100 p-1 rounded-xl gap-1">
                                <div
                                    v-for="item in getPromptList"
                                    :key="item.id"
                                    class="px-5 h-8 flex items-center justify-center rounded-lg text-xs font-black cursor-pointer transition-all"
                                    :class="[
                                        currentPromptValue === item.value
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-tx-placeholder hover:text-tx-secondary',
                                    ]"
                                    @click="currentPromptValue = item.value">
                                    {{ item.label }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <ElButton
                                type="primary"
                                class="!w-full !h-[52px] !rounded-xl !text-base !font-black shadow-primary/20 hover:scale-[1.01] active:scale-[0.99] transition-all"
                                :loading="isReceiving"
                                :disabled="isLock || !contentVal"
                                @click.stop="lockSubmit">
                                <template #loading>
                                    <div class="flex items-center gap-2">
                                        <div class="ai-loading-icon"></div>
                                        <span>正在深度创作中...</span>
                                    </div>
                                </template>
                                <Icon v-if="!isReceiving" name="el-icon-Cpu" />
                                <span class="ml-2">{{
                                    resultList.length > 0 ? "重新生成内容" : "立即生成 AI 文案"
                                }}</span>
                            </ElButton>
                        </div>
                    </div>

                    <div
                        v-if="resultList.length > 0"
                        class="space-y-4 animate-in fade-in slide-in-from-top-4 duration-500 pb-4">
                        <div class="flex items-center gap-2 px-1">
                            <span class="w-1 h-3 bg-primary rounded-full"></span>
                            <span class="text-sm font-black text-gray-950">智能生成结果</span>
                        </div>

                        <div
                            class="bg-white rounded-2xl p-5 border border-br-extra-light relative group"
                            v-for="(item, index) in resultList"
                            :key="index">
                            <template v-if="!item.loading">
                                <div class="bg-gray-50/80 rounded-xl p-2 border border-dashed border-br-light">
                                    <ElInput
                                        v-model="item.content"
                                        class="custom-textarea"
                                        type="textarea"
                                        resize="none"
                                        show-word-limit
                                        :rows="6"
                                        :maxlength="maxSize || 2000" />
                                </div>

                                <div class="flex items-center justify-between mt-4">
                                    <div class="text-[11px] text-tx-placeholder flex items-center gap-1">
                                        <Icon name="el-icon-InfoFilled" />
                                        生成的文案可点击上方区域直接编辑
                                    </div>
                                    <div class="flex gap-2">
                                        <ElButton
                                            class="!rounded-full !px-6 !font-black !bg-gray-950 !text-white !border-none hover:!bg-gray-800"
                                            @click="useContent(item.content)">
                                            使用此文案
                                        </ElButton>
                                    </div>
                                </div>
                            </template>

                            <div v-else class="flex flex-col items-center justify-center py-12 gap-4">
                                <div class="writing-animation"><span></span><span></span><span></span></div>
                                <div class="text-xs font-bold text-primary animate-pulse tracking-widest">
                                    AI WRITING...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<style scoped lang="scss">
:deep(.result-textarea) {
    .el-textarea__inner {
        @apply bg-[transparent] p-3 shadow-[none] border-[none] text-tx-regular leading-relaxed;
    }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-gray-200 rounded-full;
}

.writing-animation {
    @apply flex gap-1.5;
    span {
        @apply w-2 h-2 rounded-full bg-primary;
        animation: jump 0.6s infinite alternate;
        &:nth-child(2) {
            animation-delay: 0.2s;
        }
        &:nth-child(3) {
            animation-delay: 0.4s;
        }
    }
}

@keyframes jump {
    from {
        transform: translateY(0);
        opacity: 0.4;
    }
    to {
        transform: translateY(-8px);
        opacity: 1;
    }
}

.ai-loading-icon {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
<script setup lang="ts">
import { generatePrompt } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { CopywritingTypeEnum } from "@/pages/app/_enums/chatEnum";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        promptType: CopywritingTypeEnum;
        maxSize?: number;
        disabled?: boolean;
    }>(),
    {
        modelValue: false,
        promptType: CopywritingTypeEnum.AI_DIGITAL_HUMAN_COPYWRITING,
        maxSize: 0,
        disabled: false,
    }
);

const emit = defineEmits(["use-content", "update:modelValue", "close"]);

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value: boolean) {
        emit("update:modelValue", value);
    },
});

// State
const contentVal = ref<string>("");
const resultList = ref<{ loading: boolean; content: string }[]>([]);
const isReceiving = ref(false);

// 字数配置
const promptList = [
    { id: 1, label: "短", value: 150 },
    { id: 2, label: "中", value: 300 },
    { id: 3, label: "长", value: 1000 },
];
const getPromptList = computed(() => {
    return promptList.filter((item) => item.value <= props.maxSize);
});

const currentPromptValue = ref<any>(getPromptList.value[0]?.value);

// 随机主题库
const randomSubjects = [
    "北京旅游探险攻略",
    "秋季养生小知识分享",
    "新款智能手表评测",
    "职场沟通技巧",
    "周末居家美食制作",
];

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    emit("close");
};

const isResultVisible = computed(() => {
    return isReceiving.value;
});

const setRandomSubject = () => {
    const randomIndex = Math.floor(Math.random() * randomSubjects.length);
    contentVal.value = randomSubjects[randomIndex];
};

const handleGeneratePrompt = async () => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    try {
        isReceiving.value = true;
        const currentResult = reactive({
            loading: true,
            content: "",
        });
        resultList.value.unshift(currentResult);

        const { content } = await generatePrompt({
            keywords: contentVal.value,
            number: currentPromptValue.value,
        });

        currentResult.loading = false;
        currentResult.content = content;
    } catch (error) {
        feedback.msgError(error || "生成失败，请重试");
    } finally {
        isReceiving.value = false;
        userStore.getUser();
    }
};

const useContent = (content: string) => {
    emit("use-content", content);
    close();
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleGeneratePrompt);

defineExpose({
    open,
    close,
});
</script>
