<template>
    <div class="h-full bg-app-bg-2 rounded-[20px] overflow-y-auto dynamic-scroller">
        <div class="h-full flex flex-col min-w-[1000px]">
            <div class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-b border-[#ffffff1a]">
                <div class="flex items-center gap-2 cursor-pointer" @click="handleBack">
                    <Icon name="el-icon-ArrowLeft" color="#ffffff"></Icon>
                    <div class="text-white">返回上一步</div>
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
            <div class="grow min-h-0 flex flex-col mx-auto p-5">
                <div class="flex justify-end items-center gap-x-2 flex-shrink-0">
                    <ElButton
                        class="!rounded-full !h-10 w-[106px] !border-app-border-2"
                        color="#181818"
                        @click="handleAi">
                        智能生成
                    </ElButton>
                </div>
                <div class="grow min-h-0 flex justify-center gap-x-[18px] mt-5">
                    <template v-if="formData.copywriting_type === 1">
                        <div class="content-item">
                            <copywriting-card v-model:model-value="formData.title" :type="1" />
                        </div>
                        <div class="content-item">
                            <copywriting-card v-model:model-value="formData.described" :type="2" />
                        </div>
                    </template>
                    <template v-if="formData.copywriting_type === 2">
                        <div class="content-item">
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
        style="background-color: var(--app-bg-color-2); box-shadow: 0px 0px 0px 1px var(--app-border-color-1)"
        :show-close="false"
        @closed="showAiPopup = false">
        <div class="-my-2">
            <div class="w-6 h-6 absolute top-[18px] right-[18px] cursor-pointer" @click="handleAiClose">
                <close-btn :theme="ThemeEnum.DARK"></close-btn>
            </div>
            <div class="text-white text-[15px] font-bold">智能生成</div>
            <div class="mt-[18px]">
                <ElInput v-model="aiFormData.keyword" placeholder="您希望生成什么样的主题内容" class="!h-11" />
                <ElInput
                    v-model="aiFormData.total_num"
                    v-number-input="{ decimal: 0 }"
                    :min="1"
                    type="number"
                    placeholder="您希望生成的数量"
                    class="!h-11 mt-2" />
            </div>
            <div class="mt-[18px]">
                <ElButton
                    type="primary"
                    class="!rounded-full !h-[50px] w-full"
                    :loading="isAiGenerating"
                    @click="handleAiGen">
                    开始（消耗{{ getKeywordToCopywritingToken }}算力）
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { AppTypeEnum, ThemeEnum } from "@/enums/appEnums";
import {
    addCopywritingLibrary,
    updateCopywritingLibrary,
    getCopywritingLibraryDetail,
    getCopywritingLibraryContentAi,
} from "@/api/redbook";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import CopywritingCard from "../../../_components/copywriting-card.vue";

// --- Props and Emits ---
const props = defineProps<{
    type: number;
}>();

const emit = defineEmits(["back"]);

// --- State Management ---

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

// --- Computed Properties ---
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

// --- API & Data Handling ---
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

// --- AI Generation ---
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
            formData.described.push(...described);
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

// --- Event Handlers ---
const handleBack = () => {
    emit("back");
};

// --- Lifecycle Hooks ---
onMounted(() => {
    if (formData.id) {
        getDetail();
    }
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
.content-item {
    @apply rounded-xl bg-app-bg-3 py-[14px] border border-app-border-1 flex flex-col min-h-0 w-[360px];
    :deep(.el-select__wrapper) {
        background-color: var(--app-bg-color-1) !important;
    }
    :deep(.el-input) {
        .el-input__wrapper {
            background-color: transparent !important;
            box-shadow: none !important;
        }
    }
}
</style>
