<template>
    <div class="h-full bg-white rounded-[20px]">
        <div class="h-full flex flex-col">
            <div class="flex-shrink-0 flex items-center justify-between px-8 h-[80px] border-b border-[#F3F4F6]">
                <div
                    class="group flex items-center gap-2 cursor-pointer transition-all hover:translate-x-[-4px]"
                    @click="handleBack">
                    <div
                        class="w-8 h-8 rounded-full bg-[#F9FAFB] flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
                        <Icon name="el-icon-ArrowLeft" :size="14"></Icon>
                    </div>
                    <span class="text-sm font-medium text-[#6B7280] group-hover:text-[#111827] transition-colors"
                        >返回上一步</span
                    >
                </div>
                <div class="flex items-center gap-1">
                    <ElButton
                        type="primary"
                        class="!rounded-full !h-10 w-[98px]"
                        :loading="isSaving"
                        :disabled="isSaveDisabled"
                        @click="handleSave">
                        保存
                    </ElButton>
                </div>
            </div>
            <div class="grow min-h-0 flex flex-col p-5 relative">
                <div class="flex items-center justify-between mb-6 flex-shrink-0">
                    <div class="flex flex-col">
                        <h3 class="text-slate-800 font-medium text-[18px] tracking-tight">文案创作</h3>
                        <p class="text-slate-400 text-xs font-medium uppercase tracking-widest">Copywriting Studio</p>
                    </div>

                    <button class="ai-premium-btn" @click="handleAi">
                        <div class="btn-content">
                            <svg
                                class="ai-stars-icon"
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5">
                                <path
                                    d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z" />
                            </svg>
                            <span>AI 智能生成</span>
                        </div>
                        <div class="btn-glow"></div>
                    </button>
                </div>
                <div class="grow min-h-0 flex justify-center gap-x-[18px]">
                    <template v-if="formData.copywriting_type === 1">
                        <div class="content-item">
                            <copywriting-card v-model:model-value="formData.title" :type="1" />
                        </div>
                        <div class="content-item">
                            <copywriting-card v-model:model-value="formData.described" :type="2" />
                        </div>
                    </template>
                    <template v-if="formData.copywriting_type === 2">
                        <div class="content-item !w-[500px] !mx-auto !grow-0 !flex-none">
                            <copywriting-card v-model:model-value="formData.oral_copy" :type="3" :show-topic="false" />
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
    <popup
        v-if="showAiPopup"
        ref="popupRef"
        cancel-button-text=""
        confirm-button-text=""
        style="padding: 0"
        header-class="!p-0"
        footer-class="!p-0"
        :show-close="false"
        @closed="showAiPopup = false">
        <div class="rounded-[28px] overflow-hidden bg-white shadow-2xl relative">
            <div class="flex items-center justify-between h-[72px] px-8 border-b border-[#F1F5F9] bg-white">
                <div class="flex items-center gap-x-3">
                    <div
                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-gradient-to-br from-[#0065fb] to-[#7C3AED] text-white shadow-[#0065fb]/20">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z" />
                            <path d="M5 3v4M3 5h4M21 17v4M19 19h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">AI 智能内容生成</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest text-gradient">
                            AI Creative Assistant
                        </div>
                    </div>
                </div>

                <div class="w-8 h-8" @click="handleAiClose">
                    <close-btn />
                </div>
            </div>

            <div class="p-8 bg-slate-50">
                <div class="space-y-6">
                    <div class="bg-white p-5 rounded-2xl border border-br">
                        <label class="text-xs font-black text-[#64748B] uppercase mb-3 ml-1 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            内容主题
                        </label>
                        <ElInput
                            v-model="aiFormData.keyword"
                            type="textarea"
                            :rows="3"
                            resize="none"
                            placeholder="描述您希望生成的内容主题，例如：关于职场心理学的短视频口播文案..."
                            class="custom-ai-input" />
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-br flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-black text-[#64748B] uppercase mb-1 ml-1">生成数量</label>
                            <p class="text-[11px] text-[#94A3B8]">单次最高支持生成 10 条内容</p>
                        </div>
                        <div class="w-[140px]">
                            <ElInput
                                v-model="aiFormData.total_num"
                                v-number-input="{ decimal: 0 }"
                                :min="1"
                                :max="10"
                                resize="none"
                                type="number"
                                class="custom-number-input">
                                <template #suffix>条</template>
                            </ElInput>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 px-4 py-3 rounded-xl bg-[#0065fb]/10 border border-[#0065fb]/10 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Icon name="el-icon-Cpu" :size="14" color="var(--color-primary)"></Icon>
                        <span class="text-xs text-primary font-medium">算力预估计算</span>
                    </div>
                    <div class="text-[14px] text-primary font-black italic">
                        {{ getKeywordToCopywritingToken }} <span class="text-[10px] ml-0.5">算力</span>
                    </div>
                </div>
            </div>

            <div class="p-8 bg-white border-t border-[#F1F5F9]">
                <ElButton
                    type="primary"
                    :loading="isAiGenerating"
                    class="!h-[56px] !w-full !rounded-full !text-[16px] !font-black !shadow-[#0065fb]/30 !border-none bg-gradient-to-r from-[#0065fb] to-[#7C3AED] hover:opacity-90 active:scale-[0.98] transition-all"
                    @click="handleAiGen">
                    <template #loading> 正在构思中... </template>
                    立即开启智能创作
                </ElButton>
                <p class="text-center mt-4 text-[11px] text-[#94A3B8] font-medium">
                    生成内容由 AI 技术驱动，请结合实际需求进行合规性审核
                </p>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";
import {
    addCopywritingLibrary,
    updateCopywritingLibrary,
    getCopywritingLibraryDetail,
    getCopywritingLibraryContentAi,
} from "@/api/matrix";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import CopywritingCard from "../../../_components/copywriting-card.vue";

const props = defineProps<{
    type: number;
}>();

const emit = defineEmits(["back"]);

const userStore = useUserStore();

const getKeywordToCopywritingToken = computed(() => {
    return aiFormData.total_num * userStore.getTokenByScene(TokensSceneEnum.KEYWORD_TO_COPYWRITING)?.score;
});

const isSaving = ref(false);
const isAiGenerating = ref(false);

const query = searchQueryToObject();

const formData = reactive({
    id: (query.id as string) || "",
    title: [],
    oral_copy: [],
    described: [],
    copywriting_type: props.type || Number(query.copywriting_type),
});

const aiFormData = reactive({
    keyword: "",
    total_num: 1,
});

const isSaveDisabled = computed(() => {
    const hasContent = (arr: any[]) => arr.some((item) => item.content?.trim());

    if (formData.copywriting_type === 1) {
        return !hasContent(formData.title) || !hasContent(formData.described);
    }
    if (formData.copywriting_type === 2) {
        return !hasContent(formData.oral_copy);
    }
    return true;
});

const getDetail = async () => {
    if (!formData.id) return;
    const data = await getCopywritingLibraryDetail({ id: formData.id });
    formData.title = data.title || [];
    formData.described = data.described || [];
    formData.oral_copy = data.oral_copy || [];
};

const handleSave = async () => {
    if (isSaveDisabled.value) {
        feedback.msgWarning("请确保所有必填项都已填写内容");
        return;
    }

    isSaving.value = true;
    try {
        const hasContent = (item: any) => item.content?.trim();
        const params = {
            ...formData,
            type: AppTypeEnum.XHS,
            title: formData.title.filter(hasContent),
            oral_copy: formData.oral_copy.filter(hasContent),
            described: formData.described.filter(hasContent),
        };

        formData.id ? await updateCopywritingLibrary(params) : await addCopywritingLibrary(params);

        feedback.msgSuccess("操作成功");
        emit("back");
    } catch (error) {
        feedback.msgError(error);
    } finally {
        isSaving.value = false;
    }
};

const popupRef = ref();
const showAiPopup = ref(false);

const handleAi = async () => {
    showAiPopup.value = true;
    await nextTick();
    popupRef.value?.open();
};

const handleAiClose = () => {
    popupRef.value?.close();
};

const handleAiGen = async () => {
    if (userStore.userTokens <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }

    if (!aiFormData.keyword.trim()) {
        return feedback.msgWarning("请输入您希望生成的主题内容");
    }
    if (!aiFormData.total_num || aiFormData.total_num < 1) {
        return feedback.msgWarning("生成数量必须大于0");
    }

    isAiGenerating.value = true;
    try {
        const data: any = await getCopywritingLibraryContentAi({
            ...aiFormData,
            type: AppTypeEnum.XHS,
            copywriting_type: formData.copywriting_type,
            channel: 2,
        });

        if (!data) {
            return feedback.msgError("生成失败！");
        }

        const { described, oral_copy, title } = data;
        if (formData.copywriting_type === 1) {
            const newTitle = title.map((item: any) => {
                item.content = item.content.slice(0, 20);
                return item;
            });
            formData.title.push(...newTitle);
            formData.described.push(
                ...described.map((item: any) => ({ content: item.content, topic: item?.topic || [] }))
            );
        } else if (formData.copywriting_type === 2) {
            formData.oral_copy.push(...oral_copy);
        }
        userStore.getUser();
        feedback.msgSuccess("生成成功！");
        handleAiClose();
    } catch (error) {
        feedback.msgError(error);
    } finally {
        isAiGenerating.value = false;
    }
};

const handleBack = () => {
    emit("back");
};

onMounted(() => {
    if (formData.id) {
        getDetail();
    }
});
</script>

<style scoped lang="scss">
.ai-premium-btn {
    --hover-color: #0055d6;

    position: relative;
    padding: 10px 24px;
    background: var(--color-primary);
    border-radius: 14px;
    border: none;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    box-shadow: 0 10px 20px -5px rgba(0, 101, 251, 0.4);

    .btn-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 8px;
        color: white;
        font-weight: 900;
        font-size: 14px;
        letter-spacing: 0.5px;
    }

    .ai-stars-icon {
        animation: star-spin 4s linear infinite;
    }

    .btn-glow {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
    }

    &:hover {
        background: var(--hover-color);
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -8px rgba(0, 101, 251, 0.5);

        .btn-glow {
            opacity: 1;
        }
    }

    &:active {
        transform: translateY(0);
    }
}

/* 旋转动画 */
@keyframes star-spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
.content-item {
    @apply rounded-xl border border-[var(--el-border-color)] py-[14px] flex flex-col grow min-h-0 flex-1;

    :deep(.el-input__inner::placeholder) {
        font-size: 10px;
    }
}
</style>
