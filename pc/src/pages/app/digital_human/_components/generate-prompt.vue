<template>
    <ElDrawer
        v-model="visible"
        direction="rtl"
        size="1100px"
        header-class="!p-0 !mb-0"
        body-class="!p-0"
        :show-close="false"
        :close-on-click-modal="true"
        :close-on-press-escape="true"
        class="ai-prompt-drawer"
        @close="handleDrawerClose">
        <template #header>
            <div class="flex items-center justify-between w-full px-8 py-5 border-b border-br-extra-light">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-[#0065fb]/10 flex items-center justify-center">
                        <Icon name="el-icon-MagicStick" color="#0065fb" :size="20" />
                    </div>
                    <div>
                        <p class="text-gray-950 text-base font-[1000] tracking-tight leading-tight">AI 智能创作文案</p>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                            选择智能体，输入主题，一键生成高质量文案
                        </p>
                    </div>
                </div>
                <div
                    class="w-9 h-9 flex items-center justify-center cursor-pointer hover:bg-gray-100 rounded-full transition-colors"
                    @click="close">
                    <close-btn />
                </div>
            </div>
        </template>

        <div class="flex h-full overflow-hidden">
            <div class="w-[400px] shrink-0 h-full border-r border-br-extra-light bg-white overflow-hidden">
                <agent-select
                    ref="agentSelectRef"
                    :system-agent-ids="systemAgentIds"
                    @select-engine="handleEngineType"
                    @select-agent="handleSelectAgent"
                    @select-agent-type="(value: number) => agentData.agentType = value" />
            </div>

            <ElScrollbar class="flex-1 h-full bg-[#f9f9f9]/60">
                <div class="px-6 py-6 space-y-5 mx-auto">
                    <div class="rounded-2xl p-6 border border-br-extra-light bg-white shadow-sm">
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

                        <div class="mt-5 pt-5 border-t border-dashed border-slate-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-[1000] text-gray-950 flex items-center gap-1.5">
                                    绑定人设
                                    <ElTooltip content="为文案绑定一个人设，AI 将以该角色口吻进行创作" placement="top">
                                        <div class="cursor-pointer leading-[0]">
                                            <Icon name="el-icon-QuestionFilled" color="#94a3b8" :size="14" />
                                        </div>
                                    </ElTooltip>
                                </span>
                                <div
                                    v-if="!selectedPersona"
                                    class="text-[11px] font-bold text-primary bg-[#0065FB]/5 px-2.5 py-1.5 rounded-lg cursor-pointer hover:bg-[#0065FB]/10 transition-all flex items-center gap-1"
                                    @click="openPersonaSelect">
                                    <Icon name="el-icon-Plus" :size="12" />
                                    <span>选择人设</span>
                                </div>
                                <div v-else class="flex items-center gap-1.5">
                                    <div
                                        class="bg-slate-100 px-2.5 py-1.5 rounded-lg cursor-pointer hover:bg-slate-200 transition-all flex items-center gap-1"
                                        @click="openPersonaSelect">
                                        <Icon name="el-icon-Switch" :size="12" />
                                        <span class="text-[11px] font-bold text-slate-500">更换</span>
                                    </div>
                                    <div
                                        class="bg-slate-100 px-2.5 py-1.5 rounded-lg cursor-pointer hover:bg-red-50 hover:text-red-400 transition-all flex items-center gap-1"
                                        @click="clearPersona">
                                        <Icon name="el-icon-Close" :size="12" />
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="!selectedPersona"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 cursor-pointer hover:border-[#0065fb]/40 hover:bg-[#0065FB]/5 transition-all group"
                                @click="openPersonaSelect">
                                <div
                                    class="w-8 h-8 rounded-full bg-slate-200 group-hover:bg-[#0065FB]/10 flex items-center justify-center transition-colors shrink-0">
                                    <Icon name="el-icon-UserFilled" color="#94a3b8" :size="16" />
                                </div>
                                <span class="text-xs text-slate-400 font-medium"
                                    >点击选择人设，让 AI 以指定角色口吻创作</span
                                >
                            </div>

                            <div
                                v-else
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border border-[#0065fb]/20 bg-[#0065FB]/5">
                                <div
                                    class="w-10 h-10 rounded-full overflow-hidden border-2 border-white shadow-sm shrink-0">
                                    <el-image
                                        v-if="selectedPersona.avatar_url"
                                        :src="selectedPersona.avatar_url"
                                        fit="cover"
                                        class="w-full h-full" />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-[#E8EDF2]">
                                        <Icon name="el-icon-UserFilled" color="#B0BEC5" :size="20" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate">
                                        {{ selectedPersona.persona_name }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ PersonTypeMap[selectedPersona.persona_type as keyof typeof PersonTypeMap] }}
                                    </p>
                                </div>
                                <div
                                    class="shrink-0 flex items-center gap-1 bg-[#ECFDF5] border border-[#A7F3D0] px-2 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] inline-block"></span>
                                    <span class="text-[10px] text-[#059669] font-medium">已绑定</span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="isSystem"
                            class="mt-5 pt-5 flex items-center justify-between border-t border-dashed border-slate-100">
                            <div class="text-sm font-[1000] text-gray-950">期望文案长度</div>
                            <div class="flex bg-gray-100 p-1 rounded-xl gap-1">
                                <div
                                    v-for="item in getPromptList"
                                    :key="item.id"
                                    class="px-5 h-8 flex items-center justify-center rounded-lg text-xs font-black cursor-pointer transition-all"
                                    :class="[
                                        currentPromptValue === item.length
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-slate-400 hover:text-slate-600',
                                    ]"
                                    @click="currentPromptValue = item.length">
                                    {{ item.name }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="isSystem && showGenerateCount"
                            class="mt-4 pt-4 flex items-center justify-between border-t border-dashed border-slate-100">
                            <div class="text-sm font-[1000] text-gray-950 flex items-center gap-1.5">
                                生成数量
                                <ElTooltip content="一次生成多条文案，方便对比选择" placement="top">
                                    <div class="cursor-pointer leading-[0]">
                                        <Icon name="el-icon-QuestionFilled" color="#94a3b8" :size="14" />
                                    </div>
                                </ElTooltip>
                            </div>
                            <div class="flex bg-gray-100 p-1 rounded-xl gap-1">
                                <div
                                    v-for="item in generateCountList"
                                    :key="item.value"
                                    class="w-10 h-8 flex items-center justify-center rounded-lg text-xs font-black cursor-pointer transition-all"
                                    :class="[
                                        currentGenerateCount === item.value
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-slate-400 hover:text-slate-600',
                                    ]"
                                    @click="currentGenerateCount = item.value">
                                    {{ item.value }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <ElButton
                                type="primary"
                                class="!w-full !rounded-2xl !text-base !font-[1000] shadow-lg shadow-[#0065fb]/20 hover:scale-[1.01] active:scale-[0.99] transition-all"
                                :class="needsTitleRequest ? '!h-auto !py-3' : '!h-14'"
                                :loading="isReceiving"
                                :disabled="isLock || !contentVal || !isSelectedAgent"
                                @click.stop="lockSubmit">
                                <template #loading>
                                    <div class="flex items-center gap-2">
                                        <div class="ai-loading-icon"></div>
                                        <span>正在深度创作中...</span>
                                    </div>
                                </template>
                                <div v-if="!isReceiving" class="flex flex-col items-center gap-1">
                                    <div class="flex items-center gap-2">
                                        <span>
                                            {{ resultList.length > 0 ? "重新生成内容" : "立即生成 AI 文案" }}
                                        </span>
                                        <span class="opacity-80" v-if="isSystem">
                                            (消耗 {{ totalTokenCost }} 算力)
                                        </span>
                                    </div>
                                    <div
                                        v-if="needsTitleRequest && isSystem"
                                        class="flex items-center gap-1.5 text-[11px] font-medium opacity-75">
                                        <span> 内容 {{ contentTokenCost }} + 标题 {{ titleTokenCost }} </span>
                                        <span
                                            class="bg-[#ffffff]/20 border border-[#ffffff]/30 rounded-full px-2 py-0.5 text-[10px] font-bold">
                                            含标题生成
                                        </span>
                                    </div>
                                </div>
                            </ElButton>
                            <p
                                v-if="!isSelectedAgent"
                                class="text-center text-[10px] text-red-400 font-bold mt-2 uppercase tracking-widest">
                                请先在左侧选择一个智能体
                            </p>
                        </div>
                    </div>

                    <template v-if="resultList.length > 0">
                        <div class="flex items-center gap-2 px-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">生成结果</span>
                        </div>

                        <div
                            class="bg-white rounded-2xl p-6 border border-br-extra-light shadow-sm"
                            v-for="(item, index) in resultList"
                            :key="index">
                            <template v-if="!item.loading">
                                <div
                                    v-if="needsTitleRequest"
                                    class="bg-[#f9fafb] rounded-xl px-4 py-3 border border-slate-100 mb-3">
                                    <div class="text-[11px] font-bold text-slate-400 mb-1.5">标题</div>
                                    <ElInput
                                        v-model="item.title"
                                        class="result-textarea"
                                        resize="none"
                                        placeholder="请输入文案标题"
                                        :maxlength="30"
                                        show-word-limit />
                                </div>

                                <div class="bg-[#f9fafb] rounded-xl p-4 border border-slate-100">
                                    <div class="text-[11px] font-bold text-slate-400 mb-1.5">内容</div>
                                    <ElInput
                                        v-model="item.content"
                                        class="result-textarea"
                                        type="textarea"
                                        resize="none"
                                        show-word-limit
                                        :rows="6"
                                        :maxlength="maxSize || 1000" />
                                </div>

                                <div v-if="!showGenerateCount" class="flex items-center justify-between mt-5">
                                    <div class="text-[11px] text-slate-400 font-bold flex items-center gap-1">
                                        <Icon name="el-icon-InfoFilled" />
                                        文案可直接点击修改
                                    </div>
                                    <button
                                        class="px-8 h-10 bg-slate-950 text-white rounded-xl text-xs font-[1000] hover:bg-primary transition-all"
                                        @click="useContent(item)">
                                        使用此文案
                                    </button>
                                </div>
                                <div v-else class="mt-5">
                                    <div class="text-[11px] text-slate-400 font-bold flex items-center gap-1">
                                        <Icon name="el-icon-InfoFilled" />
                                        文案可直接点击修改
                                    </div>
                                </div>
                            </template>

                            <div v-else class="flex flex-col items-center justify-center py-12 gap-4">
                                <div class="writing-animation"><span></span><span></span><span></span></div>
                                <div
                                    class="text-[10px] font-black text-primary animate-pulse tracking-[0.3em] uppercase">
                                    AI 创作中...
                                </div>
                            </div>
                        </div>
                        <div v-if="showGenerateCount && !isReceiving && finishedResultList.length > 0" class="pb-6">
                            <button
                                class="w-full h-12 bg-slate-950 text-white rounded-2xl text-sm font-[1000] hover:bg-primary transition-all shadow-lg"
                                @click="useAllContent">
                                使用全部文案（{{ finishedResultList.length }} 条）
                            </button>
                        </div>
                    </template>
                </div>
            </ElScrollbar>
        </div>
    </ElDrawer>

    <persona-select
        v-if="showPersonaSelect"
        ref="personaSelectRef"
        :limit="1"
        :skip-un-config="true"
        @select="handlePersonaSelect"
        @close="showPersonaSelect = false" />
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum, PersonTypeMap } from "@/enums/appEnums";
import { CreateVideoTypeEnum } from "@/pages/app/digital_human/_enums";
import { copywritingLimit } from "@/pages/app/digital_human/_config";
import useAgent from "../_hooks/useAgent";
import AgentSelect from "./agent-select.vue";

const props = withDefaults(
    defineProps<{
        promptType: CreateVideoTypeEnum;
        maxSize?: number;
        disabled?: boolean;
        systemAgentIds?: number[];
        showTitle?: boolean;
    }>(),
    {
        promptType: CreateVideoTypeEnum.DIGITAL_HUMAN,
        maxSize: copywritingLimit.maxLength,
        disabled: false,
        systemAgentIds: () => [],
        showTitle: true,
    },
);

const emit = defineEmits(["use-content", "close"]);

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// ── 抽屉显隐 ──────────────────────────────────────────
const visible = ref(false);

// ── State ──────────────────────────────────────────────
const contentVal = ref<string>("");
const resultList = ref<{ loading: boolean; content: string; title: string }[]>([]);
const isReceiving = ref(false);

// ── 人设 ───────────────────────────────────────────────
const personaSelectRef = shallowRef();
const selectedPersona = ref<any>(null);
const showPersonaSelect = ref(false);

const openPersonaSelect = async () => {
    showPersonaSelect.value = true;
    await nextTick();
    personaSelectRef.value?.open();
    if (selectedPersona.value) {
        personaSelectRef.value?.setChooseLists([selectedPersona.value]);
    }
};

const handlePersonaSelect = (item: any) => {
    selectedPersona.value = item;
};

const clearPersona = () => {
    selectedPersona.value = null;
};

// ── Agent ──────────────────────────────────────────────
const agentData = reactive<{
    agentType: number;
    agentId: number;
    engineType: number;
}>({
    agentType: 1,
    agentId: -1,
    engineType: 1,
});

const promptList = [
    { id: 1, name: "长", length: 500 },
    { id: 2, name: "中", length: 300 },
    { id: 3, name: "短", length: 150 },
];

const generateCountList = [
    { label: "1条", value: 1 },
    { label: "3条", value: 3 },
    { label: "5条", value: 5 },
    { label: "10条", value: 10 },
    { label: "20条", value: 20 },
];

const SHOW_COUNT_TYPES = [CreateVideoTypeEnum.ORAL_MIX, CreateVideoTypeEnum.NEWS, CreateVideoTypeEnum.MATERIAL_MIX];

const randomSubjects = [
    "北京旅游探险攻略",
    "秋季养生小知识分享",
    "新款智能手表评测",
    "职场沟通技巧",
    "周末居家美食制作",
];

const isSelectedAgent = computed(() => agentData.agentId !== -1);
const getPromptList = computed(() => promptList.filter((item) => item.length <= props.maxSize));

/** 内容单次算力 */
const getToken = computed(() => {
    const token =
        agentData.engineType == 1
            ? userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score
            : userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING_SENIOR)?.score;
    return parseFloat(token);
});

const currentPromptValue = ref<any>(getPromptList.value[0]?.length);
const showGenerateCount = computed(() => SHOW_COUNT_TYPES.includes(props.promptType));
const currentGenerateCount = ref<number>(generateCountList[0].value);
const finishedResultList = computed(() => resultList.value.filter((item) => !item.loading));
const isSystem = computed(() => agentData.agentType === 1);
const isNewsMode = computed(() => props.promptType === CreateVideoTypeEnum.NEWS);
const isStoryboardMixMode = computed(() => props.promptType === CreateVideoTypeEnum.STORYBOARD);
const showTitle = computed(() => props.showTitle);

const needsTitleRequest = computed(() => showTitle.value && !isNewsMode.value && !isStoryboardMixMode.value);

const titleTokenCost = computed(() => {
    if (!needsTitleRequest.value) return 0;
    const token =
        agentData.engineType == 1
            ? userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING)?.score
            : userStore.getTokenByScene(TokensSceneEnum.COZE_COPYWRITING_SENIOR)?.score;
    const num = isSystem.value ? (showGenerateCount.value ? currentGenerateCount.value : 1) : 1;
    return parseFloat(token) * num;
});

/** 内容算力（按生成数量计算） */
const contentTokenCost = computed(() => {
    if (!isSystem.value) return 0;
    const num = showGenerateCount.value ? currentGenerateCount.value : 1;
    return getToken.value * num;
});

/** 总算力 = 内容算力 + 标题算力 */
const totalTokenCost = computed(() => {
    return contentTokenCost.value + titleTokenCost.value;
});

watch(
    () => props.promptType,
    () => {
        currentGenerateCount.value = 1;
    },
);

const agentSelectRef = ref<InstanceType<typeof AgentSelect>>();

const handleSelectAgent = (item: any) => {
    agentData.agentId = item.agentId;
    agentData.agentType = item.agentType;
};

const handleEngineType = (type: number) => {
    agentData.engineType = type;
};

const setRandomSubject = () => {
    const randomIndex = Math.floor(Math.random() * randomSubjects.length);
    contentVal.value = randomSubjects[randomIndex];
};

// ── 构建标题请求参数 ──────────────────────────────────
const buildTitleParams = (count: number) => ({
    sn: 8,
    keywords: contentVal.value,
    number: count,
    length: currentPromptValue.value,
    type: agentData.engineType,
    persona_id: selectedPersona.value?.id ?? undefined,
});

// ── pending title（Agent 流式模式用）──────────────────
const pendingTitle = ref("");

const { result, systemChat, handleGenerate, getDetail } = useAgent({
    onfinish: () => {
        const currentResult = resultList.value.find((item) => item.loading);
        if (currentResult) {
            currentResult.content = result.value;
            currentResult.title = isNewsMode.value || isStoryboardMixMode.value ? result.value : pendingTitle.value;
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
    if (agentData.agentId === -1) {
        feedback.msgError("请选择智能体");
        return;
    }
    // 校验改为总算力（内容 + 标题）
    if (userTokens.value <= totalTokenCost.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    isReceiving.value = true;

    const generateCount = showGenerateCount.value ? currentGenerateCount.value : 1;
    const currentResult = reactive({ loading: true, content: "", title: "" });
    resultList.value.unshift(currentResult);

    if (isSystem.value) {
        try {
            if (isNewsMode.value || isStoryboardMixMode.value || !showTitle.value) {
                // 新闻 / 分镜混剪：不请求标题，不额外扣算力
                const { content } = await systemChat({
                    sn: agentData.agentId,
                    keywords: contentVal.value,
                    number: generateCount,
                    length: currentPromptValue.value,
                    type: agentData.engineType,
                    persona_id: selectedPersona.value?.id ?? undefined,
                });
                if (content && content.length > 0) {
                    resultList.value.shift();
                    content.forEach((text: string) => {
                        resultList.value.unshift(reactive({ loading: false, content: text, title: text }));
                    });
                }
            } else {
                // 非新闻 / 非分镜：内容与标题并行，额外扣标题算力
                const [contentRes, titleRes] = await Promise.all([
                    systemChat({
                        sn: agentData.agentId,
                        keywords: contentVal.value,
                        number: generateCount,
                        length: currentPromptValue.value,
                        type: agentData.engineType,
                        persona_id: selectedPersona.value?.id ?? undefined,
                    }),
                    systemChat(buildTitleParams(generateCount)),
                ]);
                if (contentRes.content && contentRes.content.length > 0) {
                    resultList.value.shift();
                    contentRes.content.forEach((text: string, index: number) => {
                        resultList.value.unshift(
                            reactive({
                                loading: false,
                                content: text,
                                title: titleRes.content?.[index] ?? contentVal.value,
                            }),
                        );
                    });
                }
            }
        } catch (error) {
            resultList.value.shift();
            feedback.msgError(error || "生成失败，请重试");
        } finally {
            isReceiving.value = false;
            userStore.getUser();
        }
    } else {
        pendingTitle.value = "";
        if (isNewsMode.value || isStoryboardMixMode.value) {
            // 新闻 / 分镜混剪：不请求标题，不额外扣算力
            await getDetail(agentData.agentId, agentData.agentType);
            await handleGenerate(contentVal.value, agentData.agentType);
        } else {
            // 非新闻模式：流式内容与标题并行，额外扣标题算力（固定 1 条）
            const [_, titleRes] = await Promise.all([
                (async () => {
                    await getDetail(agentData.agentId, agentData.agentType);
                    await handleGenerate(contentVal.value, agentData.agentType);
                })(),
                systemChat(buildTitleParams(1)),
            ]);
            pendingTitle.value = titleRes.content?.[0] ?? contentVal.value;
        }
    }
};

const useContent = (content: { title: string; content: string }) => {
    emit("use-content", [content]);
    close();
};

const useAllContent = () => {
    const contents = finishedResultList.value.map((item) => ({
        title: item.title || contentVal.value,
        content: item.content,
    }));
    emit("use-content", contents);
    close();
};

// ── 抽屉控制 ──────────────────────────────────────────
const open = async () => {
    visible.value = true;
    await nextTick();
    agentSelectRef?.value?.getLists();
};

const close = () => {
    visible.value = false;
};

const handleDrawerClose = () => {
    emit("close");
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleGeneratePrompt);

defineExpose({ open, close });
</script>

<style scoped lang="scss">
:deep(.ai-prompt-drawer) {
    .el-drawer__header {
        @apply mb-0 px-0 py-0 border-b border-br-extra-light;
    }
    .el-drawer__body {
        @apply p-0 overflow-hidden;
    }
}

:deep(.result-textarea) {
    .el-textarea__inner {
        border: none;
        box-shadow: none;
        @apply p-0 bg-[transparent] text-[14px] leading-relaxed font-bold text-slate-700;
    }
    .el-input__count {
        @apply bg-[transparent] bottom-[-12px];
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
</style>
