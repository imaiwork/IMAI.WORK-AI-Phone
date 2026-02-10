<template>
    <popup
        v-model="show"
        width="1000px"
        top="8vh"
        style="padding: 0; overflow: hidden"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        :show-close="false"
        @close="close">
        <div class="flex flex-col rounded-2xl h-[800px] overflow-hidden">
            <div
                class="px-8 py-5 flex items-center justify-between shrink-0 border-b border-br-extra-light bg-white z-10">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                        <Icon name="el-icon-MagicStick" color="#0065fb" :size="20" />
                    </div>
                    <span class="text-gray-950 text-lg font-[1000] tracking-tight">AI 智能创作文案</span>
                </div>
                <div
                    class="w-9 h-9 flex items-center justify-center cursor-pointer hover:bg-gray-100 rounded-full transition-colors"
                    @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex flex-1 min-h-0">
                <div class="w-[300px] h-full border-r border-br-extra-light bg-white">
                    <agent-select
                        ref="agentSelectRef"
                        @select-agent="handleSelectAgent"
                        @select-agent-type="(value: number) => agentData.agentType = value" />
                </div>

                <div class="flex-1 bg-[#f9f9f9]/50 h-full mx-auto py-4 space-y-6 flex flex-col">
                    <div class="px-4">
                        <div class="rounded-2xl p-6 border border-br-extra-light transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-[1000] text-gray-950 flex items-center gap-1.5">
                                    文案主题描述
                                    <ElTooltip content="详细的描述能让 AI 生成更精准的内容" placement="top">
                                        <div class="cursor-pointer leading-[0]">
                                            <Icon name="el-icon-QuestionFilled" color="#94a3b8" :size="14" />
                                        </div>
                                    </ElTooltip>
                                </span>
                                <div
                                    class="text-[11px] font-bold text-primary bg-[#0065FB]/5 px-2.5 py-1.5 rounded-lg cursor-pointer hover:bg-[#0065FB]/10 transition-all flex items-center gap-1"
                                    @click="setRandomSubject">
                                    <Icon name="el-icon-Refresh" />
                                    <span>试试随机主题</span>
                                </div>
                            </div>

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

                            <div
                                class="mt-6 flex items-center justify-between pt-6 border-t border-dashed border-slate-100"
                                v-if="isSystem">
                                <div class="text-sm font-[1000] text-gray-950">期望文案长度</div>
                                <div class="flex bg-gray-100 p-1 rounded-xl gap-1">
                                    <div
                                        v-for="item in getPromptList"
                                        :key="item.id"
                                        class="px-5 h-8 flex items-center justify-center rounded-lg text-xs font-black cursor-pointer transition-all"
                                        :class="[
                                            currentPromptValue === item.value
                                                ? 'bg-white text-primary shadow-sm'
                                                : 'text-slate-400 hover:text-slate-600',
                                        ]"
                                        @click="currentPromptValue = item.value">
                                        {{ item.label }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8">
                                <ElButton
                                    type="primary"
                                    class="!w-full !h-14 !rounded-2xl !text-base !font-[1000] shadow-lg shadow-[#0065fb]/20 hover:scale-[1.01] active:scale-[0.99] transition-all"
                                    :loading="isReceiving"
                                    :disabled="isLock || !contentVal || !isSelectedAgent"
                                    @click.stop="lockSubmit">
                                    <template #loading>
                                        <div class="flex items-center gap-2">
                                            <div class="ai-loading-icon"></div>
                                            <span>正在深度创作中...</span>
                                        </div>
                                    </template>
                                    <span class="ml-2" v-if="!isReceiving"
                                        >{{ resultList.length > 0 ? "重新生成内容" : "立即生成 AI 文案" }} (消耗{{
                                            isSystem ? getToken : 0
                                        }}
                                        算力)</span
                                    >
                                </ElButton>
                                <div
                                    v-if="!isSelectedAgent"
                                    class="text-center text-[10px] text-red-400 font-bold mt-2 uppercase tracking-widest">
                                    请先在左侧选择一个智能体
                                </div>
                            </div>
                        </div>
                    </div>
                    <ElScrollbar v-if="resultList.length > 0">
                        <div class="px-4">
                            <div class="space-y-4 pb-10">
                                <div class="flex items-center gap-2 px-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                                    <span class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]"
                                        >生成结果</span
                                    >
                                </div>

                                <div
                                    class="bg-white rounded-2xl p-6 border border-br-extra-light relative group transition-all"
                                    v-for="(item, index) in resultList"
                                    :key="index">
                                    <template v-if="!item.loading">
                                        <div class="bg-[#f9fafb] rounded-xl p-4 border border-slate-100">
                                            <ElInput
                                                v-model="item.content"
                                                class="result-textarea"
                                                type="textarea"
                                                resize="none"
                                                show-word-limit
                                                :rows="6"
                                                :maxlength="maxSize || 2000" />
                                        </div>

                                        <div class="flex items-center justify-between mt-5">
                                            <div class="text-[11px] text-slate-400 font-bold flex items-center gap-1">
                                                <Icon name="el-icon-InfoFilled" />
                                                文案可直接点击修改
                                            </div>
                                            <button
                                                class="px-8 h-10 bg-slate-950 text-white rounded-xl text-xs font-[1000] hover:bg-primary transition-all"
                                                @click="useContent(item.content)">
                                                使用此文案
                                            </button>
                                        </div>
                                    </template>

                                    <div v-else class="flex flex-col items-center justify-center py-12 gap-4">
                                        <div class="writing-animation"><span></span><span></span><span></span></div>
                                        <div
                                            class="text-[10px] font-black text-primary animate-pulse tracking-[0.3em] uppercase">
                                            AI Writing...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
        </div>
    </popup>
</template>
<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { CopywritingTypeEnum } from "@/pages/app/_enums/chatEnum";
import useAgent from "../_hooks/useAgent";
import AgentSelect from "./agent-select.vue";

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

const agentData = reactive<{
    agentType: number;
    agentId: number;
}>({
    agentType: 1,
    agentId: -1,
});

// 字数配置
const promptList = [
    { id: 1, label: "短", value: 150 },
    { id: 2, label: "中", value: 300 },
    { id: 3, label: "长", value: 1000 },
];

// 随机主题库
const randomSubjects = [
    "北京旅游探险攻略",
    "秋季养生小知识分享",
    "新款智能手表评测",
    "职场沟通技巧",
    "周末居家美食制作",
];

const isSelectedAgent = computed(() => {
    return agentData.agentId !== -1;
});

const getPromptList = computed(() => {
    return promptList.filter((item) => item.value <= props.maxSize);
});

const getToken = computed(() => {
    const token = userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score;
    return parseFloat(token);
});

const currentPromptValue = ref<any>(getPromptList.value[0]?.value);

const isSystem = computed(() => {
    return agentData.agentType === 1;
});

const agentSelectRef = ref<InstanceType<typeof AgentSelect>>();
const handleSelectAgent = (item: any) => {
    agentData.agentId = item.agentId;
    agentData.agentType = item.agentType;
};

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    emit("close");
};

const setRandomSubject = () => {
    const randomIndex = Math.floor(Math.random() * randomSubjects.length);
    contentVal.value = randomSubjects[randomIndex];
};

const { result, systemChat, handleGenerate, getDetail } = useAgent({
    onfinish: () => {
        // 找到
        const currentResult = resultList.value.find((item) => item.loading);
        if (currentResult) {
            currentResult.content = result.value;
            currentResult.loading = false;
            userStore.getUser();
        }
        isReceiving.value = false;
    },
    onerror: (error) => {
        feedback.msgError(error || "生成失败，请重试");
        isReceiving.value = false;
        resultList.value.pop();
    },
});

const handleGeneratePrompt = async () => {
    if (agentData.agentId === -1 || !agentData.agentId) {
        feedback.msgError("请选择智能体");
        return;
    }
    if (userTokens.value <= (isSystem.value ? getToken.value : 0)) {
        feedback.msgPowerInsufficient();
        return;
    }
    isReceiving.value = true;
    const currentResult = reactive({
        loading: true,
        content: "",
    });
    resultList.value.unshift(currentResult);

    if (isSystem.value) {
        try {
            if (isSystem.value) {
                const { content } = await systemChat({
                    sn: agentData.agentId,
                    keywords: contentVal.value,
                    number: 1,
                    length: currentPromptValue.value,
                });
                if (content && content.length > 0) {
                    currentResult.content = content[0];
                    currentResult.loading = false;
                }
            }
        } catch (error) {
            resultList.value.pop();
            feedback.msgError(error || "生成失败，请重试");
        } finally {
            isReceiving.value = false;
            userStore.getUser();
        }
    } else {
        await getDetail(agentData.agentId, agentData.agentType);
        await handleGenerate(contentVal.value, agentData.agentType);
    }
};

const useContent = (content: string) => {
    emit("use-content", content);
    close();
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleGeneratePrompt);

watch(show, async (newVal) => {
    if (newVal) {
        await nextTick();
        agentSelectRef?.value.getLists();
    }
});

defineExpose({
    open,
    close,
});
</script>

<style scoped lang="scss">
:deep(.premium-textarea) {
    .el-textarea__inner {
        @apply border-[none] p-0 bg-[transparent] text-base font-bold text-slate-800 shadow-[none];
        &::placeholder {
            @apply text-slate-300 font-medium;
        }
    }
}

:deep(.result-textarea) {
    .el-textarea__inner {
        border: none;
        box-shadow: none;
        @apply p-0 bg-[transparent] text-[14px] leading-relaxed font-bold text-slate-700;
    }
}

.writing-animation {
    @apply flex gap-2;
    span {
        @apply w-2.5 h-2.5 rounded-full bg-primary;
        animation: jump 0.6s infinite alternate;
        &:nth-child(2) {
            animation-delay: 0.15s;
        }
        &:nth-child(3) {
            animation-delay: 0.3s;
        }
    }
}

@keyframes jump {
    from {
        transform: translateY(0);
        opacity: 0.3;
    }
    to {
        transform: translateY(-10px);
        opacity: 1;
    }
}

.ai-loading-icon {
    width: 18px;
    height: 18px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-in {
    animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
