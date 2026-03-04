<template>
    <popup
        ref="popupRef"
        top="10vh"
        width="680px"
        header-class="!p-0"
        footer-class="!p-0"
        cancel-button-text=""
        confirm-button-text=""
        :show-close="false">
        <div class="relative">
            <div class="absolute right-2 top-2 w-8 h-8 z-20" @click="close">
                <close-btn />
            </div>

            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 flex items-center justify-center text-primary">
                    <Icon name="el-icon-Operation" :size="20" />
                </div>
                <div>
                    <div class="text-[18px] font-[900] text-[#1E293B]">向量检索配置</div>
                    <div class="text-xs font-medium text-[#94A3B8]">配置模型检索的精度、上限及重排策略</div>
                </div>
            </div>

            <ElForm ref="formRef" :model="formData" label-width="110px" label-position="left">
                <div class="form-section-card mb-6">
                    <div class="section-title">基础检索</div>

                    <ElFormItem prop="search_mode">
                        <template #label>
                            <span class="custom-label"
                                >检索模式
                                <ElTooltip placement="top">
                                    <div class="help-icon">
                                        <Icon name="el-icon-QuestionFilled" />
                                    </div>
                                    <template #content>
                                        <div class="space-y-1">
                                            <p><b class="text-primary">语义检索:</b> 采用向量化模型进行向量检索</p>
                                            <p><b class="text-primary">全文检索:</b> 使用传统的关键词数据库检索</p>
                                            <p><b class="text-primary">混合检索:</b> 语义+全文，推荐配合重排使用</p>
                                        </div>
                                    </template>
                                </ElTooltip>
                            </span>
                        </template>
                        <ElSelect
                            class="!w-full"
                            v-model="formData.search_mode"
                            placeholder="请选择搜索模式"
                            :show-arrow="false">
                            <ElOption
                                v-for="option in searchOptions"
                                :key="option.value"
                                :label="option.label"
                                :value="option.value" />
                        </ElSelect>
                    </ElFormItem>

                    <ElFormItem prop="search_tokens">
                        <template #label>
                            <span class="custom-label"
                                >引用上限
                                <ElTooltip
                                    content="单次从知识库检索的最大 Tokens 数量，引用越多消耗越高。"
                                    placement="top">
                                    <div class="help-icon">
                                        <Icon name="el-icon-QuestionFilled" />
                                    </div>
                                </ElTooltip>
                            </span>
                        </template>
                        <div class="flex items-center gap-4 w-full">
                            <ElSlider class="flex-1" v-model="formData.search_tokens" :min="100" :max="30000" />
                            <ElInputNumber
                                v-model="formData.search_tokens"
                                :min="100"
                                :max="30000"
                                :controls="false"
                                class="!w-20 custom-number" />
                        </div>
                    </ElFormItem>

                    <ElFormItem v-if="isVisibleSearchSimilar" prop="search_similar">
                        <template #label>
                            <span class="custom-label"
                                >最低相似度
                                <ElTooltip placement="top">
                                    <div class="help-icon">
                                        <Icon name="el-icon-QuestionFilled" />
                                    </div>
                                    <template #content>
                                        <div class="max-w-[300px] leading-5">
                                            设定语义匹配的最低精度。数值越高（如0.8）回答越准确但也容易未命中；数值越低（如0.4）检索越广。
                                        </div>
                                    </template>
                                </ElTooltip>
                            </span>
                        </template>
                        <div class="flex items-center gap-4 w-full">
                            <ElSlider
                                class="flex-1"
                                v-model="formData.search_similar"
                                :min="0"
                                :max="1"
                                :step="0.001"
                                :disabled="formData.search_mode !== 'similar'" />
                            <ElInputNumber
                                v-model="formData.search_similar"
                                :min="0"
                                :max="1"
                                :step="0.001"
                                :controls="false"
                                class="!w-20 custom-number" />
                        </div>
                    </ElFormItem>
                </div>

                <div class="form-section-card bg-slate-50">
                    <div class="flex items-center justify-between mb-4">
                        <div class="section-title !mb-0">结果重排 (Rerank)</div>
                        <ElSwitch v-model="formData.ranking_status" :active-value="1" :inactive-value="0" />
                    </div>

                    <transition name="el-fade-in">
                        <div v-if="formData.ranking_status === 1" class="space-y-4 pt-2">
                            <ElFormItem prop="ranking_score" label-width="110px">
                                <template #label>
                                    <span class="custom-label"
                                        >重排权重
                                        <ElTooltip
                                            content="重排模型进行二次打分，低于此分数的数据将被过滤。"
                                            placement="top">
                                            <div class="help-icon">
                                                <Icon name="el-icon-QuestionFilled" />
                                            </div>
                                        </ElTooltip>
                                    </span>
                                </template>
                                <div class="flex items-center gap-4 w-full">
                                    <ElSlider
                                        class="flex-1"
                                        v-model="formData.ranking_score"
                                        :min="0"
                                        :max="1"
                                        :step="0.001" />
                                    <ElInputNumber
                                        v-model="formData.ranking_score"
                                        :min="0"
                                        :max="1"
                                        :step="0.001"
                                        :controls="false"
                                        class="!w-20 custom-number" />
                                </div>
                            </ElFormItem>
                        </div>
                    </transition>
                </div>
            </ElForm>

            <div class="mt-8 flex gap-3">
                <ElButton class="action-btn !text-[#64748B]" color="#F1F5F9" @click="close()">取消修改</ElButton>
                <ElButton type="primary" class="action-btn is-save" @click="save"> 保存配置 </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { cloneDeep } from "lodash";
import { ThemeEnum } from "@/enums/appEnums";
import { searchOptions } from "@/pages/knowledge_base/_config";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm", formData: any): void;
}>();

const popupRef = ref();

const formData = reactive({
    search_mode: "similar",
    search_similar: 0,
    search_tokens: 8000,
    optimize_ask: 0,
    optimize_m_id: "",
    optimize_s_id: "",
    ranking_status: 0,
    ranking_score: 0,
    ranking_model: "",
});

const isVisibleSearchSimilar = computed(() => formData.search_mode === "similar");

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const save = () => {
    const params = cloneDeep(formData);
    if (!isVisibleSearchSimilar.value) {
        delete params.search_similar;
    }
    emit("confirm", params);
};

defineExpose({
    open,
    setFormData: (data) => setFormData(data, formData),
});
</script>
<style scoped lang="scss">
/* 分段卡片 */
.form-section-card {
    @apply p-5 rounded-2xl border border-[#F1F5F9] bg-white transition-all;

    .section-title {
        @apply text-[14px] font-[900] text-[#1E293B] mb-5 flex items-center gap-2;
        &::before {
            content: "";
            @apply w-1 h-3.5 bg-primary rounded-full;
        }
    }
}

/* 标签样式 */
.custom-label {
    @apply flex items-center text-[13px] font-medium text-[#64748B];
    .help-icon {
        @apply ml-1.5 text-[#CBD5E1] cursor-pointer hover:text-primary transition-colors leading-[0];
    }
}

:deep(.custom-number) {
    .el-input__inner {
        @apply text-center font-medium text-primary bg-white rounded-lg border-br;
    }
}

:deep(.el-slider__bar) {
    @apply bg-primary;
}
:deep(.el-slider__button) {
    @apply border-primary border-[3px];
}

.action-btn {
    @apply flex-1 !h-12 !rounded-xl !border-none !font-black !text-[15px] transition-all;
}

:deep(.el-form-item) {
    @apply mb-5 last:mb-0;
}
</style>
