<template>
    <section class="flex flex-col">
        <div class="px-6 py-4 border-b border-slate-50 bg-[#f8fafc]/50 flex items-center justify-between">
            <h3 class="text-sm font-[1000] text-slate-800 flex items-center gap-2">
                口播内容配置
                <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-500 text-[10px] font-black">
                    {{ modelValue.length }}
                </span>
            </h3>
            <div class="flex items-center gap-2">
                <button
                    @click="handleAddManual"
                    class="h-9 px-4 rounded-xl border border-br text-slate-500 text-xs font-black hover:border-[#0065fb]/30 hover:text-primary transition-all flex items-center gap-2 bg-white">
                    <Icon name="el-icon-Plus" /> 手动添加
                </button>
                <button
                    @click="openAiGenerateContent"
                    class="h-9 px-4 bg-primary text-white rounded-xl text-xs font-[500] flex items-center gap-2 shadow-light shadow-[#0065fb]/20 hover:scale-105 transition-all">
                    <Icon name="el-icon-MagicStick" /> AI 生成
                </button>
            </div>
        </div>

        <div class="flex max-h-[420px]">
            <div class="w-48 border-r border-slate-50 flex flex-col bg-[#f8fafc]/30">
                <div ref="copywritingListRef" class="grow overflow-y-auto p-2 space-y-1 custom-scrollbar">
                    <div
                        v-for="(item, index) in modelValue"
                        :key="index"
                        :data-copywriting-index="index"
                        :class="[
                            'group px-3 py-3 rounded-xl cursor-pointer transition-all relative',
                            currentActiveIndex === index
                                ? 'bg-white shadow-sm ring-1 ring-slate-100'
                                : 'hover:bg-[#ffffff]/50',
                            copywritingErrors[index] ? 'ring-1 ring-red-400 bg-[#fef2f2]/50' : '',
                        ]"
                        @click="currentActiveIndex = index">
                        <div
                            v-if="currentActiveIndex === index"
                            class="absolute left-0 top-3 bottom-3 w-1 bg-primary rounded-full" />
                        <div
                            v-if="copywritingErrors[index]"
                            class="absolute right-7 top-2 w-2 h-2 rounded-full bg-red-400" />
                        <div class="flex flex-col gap-1">
                            <span
                                :class="[
                                    'text-[11px] font-black uppercase',
                                    copywritingErrors[index]
                                        ? 'text-red-400'
                                        : currentActiveIndex === index
                                        ? 'text-primary'
                                        : 'text-slate-400',
                                ]">
                                #{{ (index + 1).toString().padStart(2, "0") }}
                            </span>
                            <span class="text-xs font-medium text-slate-600 truncate w-full">
                                {{ item.title || "未命名文案" }}
                            </span>
                        </div>
                        <button
                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 w-5 h-5 flex items-center justify-center rounded-md hover:bg-red-50 hover:text-red-500 text-slate-300 transition-all"
                            @click.stop="handleRemoveContent(index)">
                            <Icon name="el-icon-Close" :size="12" />
                        </button>
                    </div>

                    <div
                        v-if="modelValue.length === 0"
                        class="h-full flex flex-col items-center justify-center p-4 text-center">
                        <div class="text-xs font-black text-slate-300 uppercase leading-relaxed">请添加内容</div>
                    </div>
                </div>
            </div>

            <div class="flex-1 p-3 overflow-y-auto custom-scrollbar">
                <template v-if="modelValue[currentActiveIndex]">
                    <div class="space-y-3 animate-in fade-in duration-300">
                        <div v-if="showTitle" class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider ml-1">
                                口播标题
                            </label>
                            <ElInput
                                v-model="modelValue[currentActiveIndex].title"
                                placeholder="输入口播标题..."
                                maxlength="100"
                                show-word-limit />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider ml-1">
                                口播内容
                            </label>
                            <ElInput
                                v-model="modelValue[currentActiveIndex].content"
                                type="textarea"
                                :rows="10"
                                placeholder="在这里输入或调整您的口播脚本内容..."
                                resize="none"
                                :maxlength="contentMaxLength"
                                :class="[copywritingErrors[currentActiveIndex] ? 'error-textarea' : '']" />
                            <div class="flex justify-between items-center pr-2">
                                <transition name="fade">
                                    <span
                                        v-if="copywritingErrors[currentActiveIndex]"
                                        class="text-[11px] text-red-400 font-medium flex items-center gap-1">
                                        <Icon name="el-icon-Warning" :size="12" />
                                        {{ copywritingErrors[currentActiveIndex] }}
                                    </span>
                                    <span v-else class="text-[11px] text-slate-300 italic">
                                        字数需在 {{ contentMinLength }} ~ {{ contentMaxLength }} 之间
                                    </span>
                                </transition>
                                <span
                                    :class="[
                                        'text-[10px] font-black italic',
                                        copywritingErrors[currentActiveIndex] ? 'text-red-400' : 'text-slate-300',
                                    ]">
                                    {{ modelValue[currentActiveIndex].content?.length || 0 }} / {{ contentMaxLength }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="h-full flex flex-col items-center justify-center space-y-4">
                    <p class="text-xs font-medium text-slate-300">点击"手动添加"开始编写内容</p>
                </div>
            </div>
        </div>
    </section>
    <generate-prompt
        v-if="showGeneratePrompt"
        ref="generatePromptRef"
        :prompt-type="promptType"
        :system-agent-ids="systemAgentIds"
        @use-content="handleGenerateContent"
        @close="showGeneratePrompt = false" />
</template>

<script setup lang="ts">
import { CreateVideoTypeEnum } from "@/pages/app/digital_human/_enums";
import { copywritingLimit } from "@/pages/app/digital_human/_config";
import GeneratePrompt from "@/pages/app/digital_human/_components/generate-prompt.vue";

// ─── 类型 ────────────────────────────────────────────────────
export interface CopywritingItem {
    title: string;
    content: string;
}

/** validate() 的返回值结构 */
export interface ValidateResult {
    valid: boolean;
    firstErrorIndex: number;
    firstErrorMsg: string;
    errors: Record<number, string>;
}

// ─── Props / Emits ───────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        modelValue: CopywritingItem[];
        promptType: CreateVideoTypeEnum;
        showTitle?: boolean;
        contentMaxLength?: number;
        contentMinLength?: number;
        systemAgentIds?: number[];
    }>(),
    {
        showTitle: true,
        contentMaxLength: copywritingLimit.maxLength,
        contentMinLength: copywritingLimit.minLength,
        systemAgentIds: () => [],
    },
);

const { showTitle, contentMaxLength, contentMinLength } = toRefs(props);

const emit = defineEmits<{
    (e: "update:modelValue", val: CopywritingItem[]): void;
}>();

// ─── 内部状态 ─────────────────────────────────────────────────
const currentActiveIndex = ref(0);
const copywritingListRef = ref<HTMLElement>();
const showGeneratePrompt = ref(false);
const generatePromptRef = shallowRef<InstanceType<typeof GeneratePrompt>>();

// ─── 校验 ─────────────────────────────────────────────────────
const copywritingErrors = computed<Record<number, string | undefined>>(() => {
    const errors: Record<number, string | undefined> = {};
    props.modelValue.forEach((item, index) => {
        const contentLength = item.content?.trim().length ?? 0;
        const titleLength = item.title?.trim().length ?? 0;
        if (showTitle.value) {
            if (titleLength === 0) {
                errors[index] = "口播标题不能为空";
            } else if (titleLength > 30) {
                errors[index] = `标题过长，最多 30 个字符（当前 ${titleLength} 个）`;
            }
        }
        if (contentLength === 0) {
            errors[index] = "口播内容不能为空";
        } else if (contentLength < contentMinLength.value) {
            errors[index] = `内容过短，至少需要 ${contentMinLength.value} 个字符（当前 ${contentLength} 个）`;
        } else if (contentLength > contentMaxLength.value) {
            errors[index] = `内容过长，最多 ${contentMaxLength.value} 个字符（当前 ${contentLength} 个）`;
        } else {
            errors[index] = undefined;
        }
    });
    return errors;
});

// ─── 方法 ─────────────────────────────────────────────────────
const handleAddManual = () => {
    props.modelValue.push({ title: "", content: "" });
    currentActiveIndex.value = props.modelValue.length - 1;
};

const handleRemoveContent = (index: number) => {
    props.modelValue.splice(index, 1);
    if (currentActiveIndex.value >= props.modelValue.length) {
        currentActiveIndex.value = Math.max(0, props.modelValue.length - 1);
    }
};

const openAiGenerateContent = async () => {
    showGeneratePrompt.value = true;
    await nextTick();
    generatePromptRef.value?.open();
};

const handleGenerateContent = (list: any[]) => {
    props.modelValue.push(...list.map((item) => ({ title: item.title, content: item.content })));
    currentActiveIndex.value = 0;
};

const scrollToCopywriting = async (index: number) => {
    currentActiveIndex.value = index;
    await nextTick();
    const container = copywritingListRef.value;
    if (!container) return;
    const target = container.querySelector<HTMLElement>(`[data-copywriting-index="${index}"]`);
    target?.scrollIntoView({ behavior: "smooth", block: "nearest" });
};

const validate = async (): Promise<ValidateResult> => {
    if (props.modelValue.length === 0) {
        return { valid: false, firstErrorIndex: -1, firstErrorMsg: "请至少添加一条口播内容", errors: {} };
    }

    const errorEntries = Object.entries(copywritingErrors.value).filter(
        (entry): entry is [string, string] => entry[1] !== undefined,
    );

    if (errorEntries.length === 0) {
        return { valid: true, firstErrorIndex: -1, firstErrorMsg: "", errors: {} };
    }

    const firstErrorIndex = Math.min(...errorEntries.map(([k]) => Number(k)));
    const firstErrorMsg = copywritingErrors.value[firstErrorIndex] as string;
    await scrollToCopywriting(firstErrorIndex);

    return {
        valid: false,
        firstErrorIndex,
        firstErrorMsg,
        errors: Object.fromEntries(errorEntries.map(([k, v]) => [Number(k), v])),
    };
};

defineExpose({ scrollToCopywriting, validate });
</script>

<style lang="scss">
.error-textarea {
    :deep(.el-textarea__inner) {
        border-color: #f87171 !important;
        box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.15) !important;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
