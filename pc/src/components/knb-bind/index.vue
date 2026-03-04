<template>
    <popup
        ref="popupRef"
        title="关联知识库"
        async
        width="520px"
        custom-class="knowledge-popup"
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <div class="px-2 py-1">
            <ElForm :model="formData" label-position="top" :rules="rules" ref="formRef" class="custom-form">
                <ElFormItem label="目标知识库" prop="id">
                    <ElSelect
                        v-model="formData.id"
                        placeholder="搜索或选择现有知识库"
                        filterable
                        clearable
                        class="w-full custom-select">
                        <ElOption
                            v-for="item in optionsData.knbLists"
                            :key="item.index_id"
                            :label="item.name"
                            :value="item.index_id">
                            <div class="flex items-center gap-2">
                                <Icon name="el-icon-Collection" :size="14" class="text-[#94A3B8]" />
                                <span>{{ item.name }}</span>
                            </div>
                        </ElOption>
                    </ElSelect>
                </ElFormItem>

                <ElFormItem label="数据切割策略" prop="strategy">
                    <div class="grid grid-cols-2 gap-3 w-full">
                        <div
                            class="strategy-card"
                            :class="{ active: formData.strategy === 1 }"
                            @click="formData.strategy = 1">
                            <div class="flex items-center gap-2 mb-1">
                                <Icon name="el-icon-Cpu" :size="16" />
                                <span class="font-black text-[13px]">智能切分</span>
                            </div>
                            <div class="text-[11px] opacity-60">由 AI 自动识别段落语义进行切分</div>
                        </div>
                        <div
                            class="strategy-card"
                            :class="{ active: formData.strategy === 2 }"
                            @click="formData.strategy = 2">
                            <div class="flex items-center gap-2 mb-1">
                                <Icon name="el-icon-Setting" :size="16" />
                                <span class="font-black text-[13px]">自定义切分</span>
                            </div>
                            <div class="text-[11px] opacity-60">手动控制长度与切割符号</div>
                        </div>
                    </div>
                </ElFormItem>

                <transition name="el-zoom-in-top">
                    <div
                        v-if="formData.strategy === 2"
                        class="bg-slate-50 rounded-[20px] p-5 border border-[#F1F5F9] space-y-5 mt-4">
                        <ElFormItem label="切割符号" prop="separator" class="!mb-0">
                            <ElSelect
                                v-model="formData.separator"
                                placeholder="选择切分标识符"
                                multiple
                                collapse-tags
                                class="custom-select">
                                <ElOption
                                    v-for="item in punctuationOptions"
                                    :key="item.value"
                                    :label="`${item.label} ${item.value}`"
                                    :value="item.value">
                                    <span class="text-[#94A3B8]">{{ item.label }}：</span>
                                    <span class="font-medium">{{ item.value }}</span>
                                </ElOption>
                            </ElSelect>
                        </ElFormItem>

                        <div class="grid grid-cols-2 gap-4">
                            <ElFormItem label="切割长度" prop="chunk_size" class="!mb-0">
                                <ElInput
                                    v-model="formData.chunk_size"
                                    type="number"
                                    placeholder="默认 600"
                                    class="custom-input" />
                            </ElFormItem>
                            <ElFormItem label="切割重叠度" prop="overlap_size" class="!mb-0">
                                <ElInput
                                    v-model="formData.overlap_size"
                                    type="number"
                                    placeholder="默认 100"
                                    class="custom-input" />
                            </ElFormItem>
                        </div>

                        <ElFormItem prop="rerank_min_score" class="!mb-0">
                            <template #label>
                                <div class="flex items-center gap-1">
                                    <span>相似度阈值</span>
                                    <ElTooltip content="设定最低分数标准，仅保留超过该阈值的检索结果" placement="top">
                                        <div class="leading-[0]">
                                            <Icon
                                                name="el-icon-QuestionFilled"
                                                :size="14"
                                                class="text-[#CBD5E1] cursor-help" />
                                        </div>
                                    </ElTooltip>
                                </div>
                            </template>
                            <div class="w-full flex items-center gap-4 px-1">
                                <ElSlider
                                    v-model="formData.rerank_min_score"
                                    :min="0"
                                    :max="1"
                                    :step="0.01"
                                    class="flex-1 custom-slider" />
                                <div class="text-[13px] font-black text-primary w-8 text-right">
                                    {{ formData.rerank_min_score }}
                                </div>
                            </div>
                        </ElFormItem>
                    </div>
                </transition>
            </ElForm>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";
import { knowledgeBaseLists, knowledgeBaseFileAdd, knowledgeBaseFileUpload } from "@/api/knowledge_base";
import { punctuationOptions } from "@/config/common";

const emit = defineEmits(["close", "success"]);
const popupRef = ref<InstanceType<typeof Popup>>(null);
const formRef = ref<InstanceType<typeof ElForm>>(null);

const formData = reactive({
    id: "",
    category_id: "",
    strategy: 1,
    separator: "",
    chunk_size: 600,
    overlap_size: 100,
    rerank_min_score: 0.5,
});

const rules = {
    id: [{ required: true, message: "请选择目标知识库", trigger: "change" }],
    separator: [{ required: true, message: "请选择切割符号", trigger: "change" }],
};

const { optionsData } = useDictOptions<{ knbLists: any[] }>({
    knbLists: {
        api: knowledgeBaseLists,
        params: { page_size: 25000 },
        transformData: (data) => data.lists,
    },
});

const currKnbData = computed(() => {
    return optionsData.knbLists.find((item) => item.index_id === formData.id);
});

const handleConfirm = async () => {
    await formRef.value?.validate();
    const { type, fileName, content } = uploadData.value;

    if (type === "txt") {
        const encoder = new TextEncoder();
        const contentBytes = encoder.encode(content);
        const file = new File([contentBytes], `${fileName}.txt`, { type: "text/plain;charset=utf-8" });

        const fileFormData = new FormData();
        fileFormData.append("file", file);
        fileFormData.append("indexid", formData.id);

        try {
            const uploadRes = await knowledgeBaseFileUpload(fileFormData);
            const { id, category_id } = currKnbData.value;

            await knowledgeBaseFileAdd({
                ...formData,
                id,
                category_id,
                documents: [uploadRes],
            });

            feedback.msgSuccess("关联成功");
            popupRef.value?.close();
            emit("success");
        } catch (error) {
            feedback.msgError(error || "操作失败");
        }
    }
};

const { lockFn, isLock } = useLockFn(handleConfirm);

const open = () => popupRef.value?.open();
const close = () => emit("close");

interface OpenData {
    type: "txt";
    fileName: string;
    content: string;
}
const uploadData = ref<OpenData | null>(null);
const setFormData = (data: OpenData) => {
    uploadData.value = data;
};

defineExpose({ open, setFormData });
</script>

<style scoped lang="scss">
.strategy-card {
    @apply p-4 rounded-2xl border-2 border-[#F1F5F9] bg-white cursor-pointer transition-all duration-300;
    &:hover {
        @apply border-br translate-y-[-2px];
    }
    &.active {
        @apply border-[#0065fb] bg-[#F5F7FF] text-primary  shadow-[#0065fb]/10;
    }
}

.custom-form {
    :deep(.el-form-item__label) {
        @apply text-xs font-black text-[#94A3B8] mb-2 px-1 uppercase tracking-wider;
    }
}

:deep(.custom-select),
:deep(.custom-input) {
    .el-input__wrapper {
        @apply rounded-xl bg-slate-50 shadow-[none] border border-[#F1F5F9] h-11 transition-all;
        &.is-focus {
            @apply bg-white border-[#0065fb] shadow-[0_0_0_4px_rgba(79,70,229,0.06)] !important;
        }
    }
}

:deep(.custom-slider) {
    .el-slider__runway {
        @apply bg-[#E2E8F0] h-1.5;
    }
    .el-slider__bar {
        @apply bg-primary h-1.5;
    }
    .el-slider__button {
        @apply border-2 border-[#0065fb] w-4 h-4;
    }
}

:deep(.knowledge-popup) {
    .el-dialog__header {
        @apply border-none pb-0;
    }
    .el-dialog__footer {
        @apply border-none pt-4 pb-8 px-8;
    }
}
</style>
