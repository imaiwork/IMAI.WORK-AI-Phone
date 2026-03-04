<template>
    <div class="h-full flex flex-col bg-[#FFFFFF]">
        <div class="grow min-h-[0]">
            <ElScrollbar>
                <div class="px-[30px] py-[24px]">
                    <ElForm
                        :model="formData"
                        :rules="formRules"
                        ref="formRef"
                        label-position="top"
                        class="custom-knb-form">
                        <section class="config-group">
                            <div class="group-header">
                                <div class="indicator"></div>
                                <span class="group-title">基础配置</span>
                            </div>

                            <div class="flex flex-wrap gap-[24px]">
                                <ElFormItem label="知识库类型" prop="kb_type" class="flex-1 min-w-[300px]">
                                    <ElSelect
                                        v-model="formData.kb_type"
                                        class="custom-select"
                                        :show-arrow="false"
                                        placeholder="请选择知识库类型"
                                        @change="handleKnChange">
                                        <ElOption label="向量知识库" :value="KnbTypeEnum.VECTOR" />
                                    </ElSelect>
                                </ElFormItem>

                                <ElFormItem label="挂靠知识库" prop="kb_ids" class="flex-1 min-w-[300px]">
                                    <ElSelect
                                        v-model="formData.kb_ids"
                                        ref="selectKbRef"
                                        clearable
                                        class="custom-select"
                                        placeholder="搜索并选择知识库"
                                        remote
                                        filterable
                                        collapse-tags
                                        collapse-tags-tooltip
                                        :show-arrow="false"
                                        :multiple="formData.kb_type == KnbTypeEnum.VECTOR"
                                        :remote-method="getKnLists">
                                        <ElOption
                                            v-for="item in knLists"
                                            :key="item.id"
                                            :label="item.name"
                                            :value="`${item.id}`" />
                                    </ElSelect>
                                </ElFormItem>
                            </div>

                            <ElFormItem prop="search_tokens" class="mt-[8px]">
                                <template #label>
                                    <div class="flex items-center gap-[6px]">
                                        <span>引用上限 (Tokens)</span>
                                        <ElTooltip placement="top">
                                            <div class="text-[#94A3B8] cursor-help">
                                                <Icon name="el-icon-QuestionFilled" :size="14" />
                                            </div>
                                            <template #content>
                                                <div class="max-w-[200px] leading-[1.6]">
                                                    单次检索最大消耗。引用越多消耗越大，请确保不超过模型限制。
                                                </div>
                                            </template>
                                        </ElTooltip>
                                    </div>
                                </template>
                                <div class="slider-box">
                                    <div class="flex-1 px-[8px]">
                                        <ElSlider v-model="formData.search_tokens" :min="100" :max="20000" />
                                    </div>
                                    <ElInputNumber
                                        v-model="formData.search_tokens"
                                        controls-position="right"
                                        :min="100"
                                        :max="20000" />
                                </div>
                            </ElFormItem>
                        </section>

                        <template v-if="formData.kb_type == KnbTypeEnum.VECTOR">
                            <section class="config-group mt-[32px]">
                                <div class="group-header">
                                    <div class="indicator"></div>
                                    <span class="group-title">召回策略</span>
                                </div>

                                <div class="grid grid-cols-[1fr_1fr] gap-x-[32px] gap-y-[12px]">
                                    <ElFormItem label="检索模式">
                                        <ElSelect
                                            v-model="formData.search_mode"
                                            class="custom-select"
                                            :show-arrow="false"
                                            placeholder="请选择搜索模式">
                                            <ElOption
                                                v-for="opt in searchOptions"
                                                :key="opt.value"
                                                :label="opt.label"
                                                :value="opt.value" />
                                        </ElSelect>
                                    </ElFormItem>

                                    <ElFormItem label="引用上下文">
                                        <div class="slider-box">
                                            <div class="flex-1 px-[8px]">
                                                <ElSlider v-model="formData.context_num" :min="0" :max="8" />
                                            </div>
                                            <ElInputNumber
                                                v-model="formData.context_num"
                                                controls-position="right"
                                                :min="0"
                                                :max="8" />
                                        </div>
                                    </ElFormItem>

                                    <ElFormItem class="col-span-2">
                                        <template #label>
                                            <span class="text-[#0F172A] font-[900]">最低相似度 (Confidence)</span>
                                        </template>
                                        <div class="slider-box">
                                            <div class="flex-1 px-[8px]">
                                                <ElSlider
                                                    v-model="formData.search_similar"
                                                    :disabled="formData.search_mode !== 'similar'"
                                                    :min="0"
                                                    :max="1"
                                                    :step="0.001" />
                                            </div>
                                            <ElInputNumber
                                                v-model="formData.search_similar"
                                                :disabled="formData.search_mode !== 'similar'"
                                                controls-position="right"
                                                :min="0"
                                                :max="1"
                                                :step="0.001" />
                                        </div>
                                    </ElFormItem>
                                </div>
                            </section>

                            <div class="grid grid-cols-[1fr_1fr] gap-[24px] mt-[32px]">
                                <div class="switch-card">
                                    <div class="flex items-center justify-between mb-[12px]">
                                        <span class="text-[14px] font-[900] text-[#0F172A]">语义重排 (Rerank)</span>
                                        <ElSwitch
                                            v-model="formData.ranking_status"
                                            :active-value="1"
                                            :inactive-value="0" />
                                    </div>
                                    <p class="text-xs text-[#94A3B8] leading-[1.6] mb-[16px]">
                                        开启后对检索内容进行二次精密排序，建议混合检索时开启。
                                    </p>
                                    <div
                                        v-if="formData.ranking_status === 1"
                                        class="pt-[12px] border-t-[1px] border-[#F1F5F9] border-[transparent]">
                                        <div class="text-xs font-medium mb-[8px]">重排过滤分数</div>
                                        <ElSlider v-model="formData.ranking_score" :min="0" :max="1" :step="0.001" />
                                    </div>
                                </div>

                                <div class="switch-card">
                                    <div class="flex items-center justify-between mb-[12px]">
                                        <span class="text-[14px] font-[900] text-[#0F172A]">问题优化 (Rewrite)</span>
                                        <ElSwitch
                                            v-model="formData.optimize_ask"
                                            :active-value="1"
                                            :inactive-value="0" />
                                    </div>
                                    <p class="text-xs text-[#94A3B8] leading-[1.6] mb-[16px]">
                                        结合历史多维度生成相似问题，提升检索精准度。
                                    </p>
                                    <div
                                        v-if="formData.optimize_ask == 1"
                                        class="pt-[12px] border-t-[1px] border-[#F1F5F9] border-[transparent]">
                                        <ElSelect
                                            v-model="formData.optimize_m_id"
                                            class="custom-select"
                                            :show-arrow="false"
                                            placeholder="选择重写模型">
                                            <ElOption
                                                v-for="item in aiModelChannel"
                                                :key="item.id"
                                                :label="item.name"
                                                :value="parseInt(item.id)" />
                                        </ElSelect>
                                    </div>
                                </div>
                            </div>

                            <section class="config-group mt-[32px]">
                                <div class="group-header">
                                    <div class="indicator"></div>
                                    <span class="group-title">兜底策略 (Empty Response)</span>
                                </div>
                                <div class="bg-slate-50 p-[20px] rounded-[16px] border-[transparent]">
                                    <ElRadioGroup v-model="formData.search_empty_type" class="mb-[16px]">
                                        <ElRadio :value="1">AI 自由发挥</ElRadio>
                                        <ElRadio :value="2">指定自定义内容</ElRadio>
                                    </ElRadioGroup>
                                    <ElInput
                                        v-if="formData.search_empty_type === 2"
                                        v-model="formData.search_empty_text"
                                        type="textarea"
                                        :rows="3"
                                        placeholder="当搜索无结果时，回复此处内容..."
                                        class="custom-textarea" />
                                </div>
                            </section>
                        </template>
                    </ElForm>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>
<script setup lang="ts">
import { knowledgeBaseLists, vectorKnowledgeBaseLists } from "@/api/knowledge_base";
import { searchOptions } from "@/pages/knowledge_base/_config";
import { KnbTypeEnum } from "@/pages/knowledge_base/_enums";
import { useAppStore } from "@/stores/app";
import { type FormInstance } from "element-plus";
import { Agent } from "../../_enums";

// 定义组件props
const props = withDefaults(
    defineProps<{
        modelValue: Agent;
    }>(),
    {
        modelValue: () => ({} as Agent),
    }
);

// store
const appStore = useAppStore();
const aiModelChannel = computed(() => appStore.getAiModelConfig.channel || []);

// 表单ref和数据模型
const formRef = ref<FormInstance>();
const formData = defineModel<Agent>("modelValue");

// 表单验证规则
const formRules = {
    kb_type: [{ required: true, message: "请选择知识库类型" }],
    kb_ids: [{ required: true, message: "请选择挂靠知识库" }],
    search_mode: [{ required: true, message: "请选择搜索模式" }],
    search_similar: [{ required: true, message: "请输入最低相似度" }],
    ranking_status: [{ required: true, message: "请选择重排开关" }],
    ranking_score: [{ required: true, message: "请输入重排分数" }],
    optimize_ask: [{ required: true, message: "请选择优化开关" }],
    optimize_m_id: [{ required: true, message: "请选择优化模型" }],
};

// 知识库列表
const knLists = ref<any[]>([]);
const selectKbRef = ref();

/**
 * @description 根据知识库类型和查询字符串，远程获取知识库列表
 * @param query - 查询字符串
 */
const getKnLists = async (query?: string) => {
    try {
        const { lists } = await vectorKnowledgeBaseLists({ page_size: 25000, name: query });
        knLists.value = lists || [];
    } catch (error) {
        knLists.value = [];
    }
};

/**
 * @description 当知识库类型改变时，清空已选知识库并重新获取列表
 */
const handleKnChange = () => {
    formData.value.kb_ids = [];
    getKnLists();
};

// 侦听知识库类型变化，以便在加载时和类型切换时获取列表
watch(
    () => props.modelValue.kb_type,
    () => {
        getKnLists();
    },
    {
        immediate: true, // 立即执行一次
    }
);

// 暴露validate方法给父组件
defineExpose({
    validate: () => {
        return new Promise((resolve, reject) => formRef.value?.validate().then(resolve).catch(reject));
    },
});
</script>

<style scoped lang="scss">
.config-group {
    @apply flex flex-col;

    .group-header {
        @apply flex items-center gap-[10px] mb-[20px];

        .indicator {
            @apply w-[4px] h-[16px] bg-primary rounded-full;
        }
        .group-title {
            @apply text-[15px] font-[900] text-[#0F172A];
        }
    }
}

.slider-box {
    @apply flex items-center w-full gap-[20px] bg-slate-50 px-[16px] py-[8px] rounded-[12px] border-[transparent];
}

.switch-card {
    @apply p-[20px] rounded-[20px] bg-[#FFFFFF] border-[1px] border-[#F1F5F9] transition-all shadow-[0_4px_6px_-1px_rgba(0,0,0,0.02)];

    &:hover {
        @apply border-primary shadow-[0_10px_15px_-3px_rgba(0,101,251,0.05)];
    }
}
</style>
