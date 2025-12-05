<template>
    <div class="h-full flex flex-col py-6">
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="px-[30px]">
                    <ElForm :model="formData" :rules="formRules" ref="formRef" label-position="top">
                        <ElDivider content-position="left">基础设置</ElDivider>
                        <!-- 知识库类型 -->
                        <ElFormItem label="知识库类型" prop="kb_type">
                            <ElSelect
                                v-model="formData.kb_type"
                                class="!h-11 !w-[390px]"
                                placeholder="请选择知识库类型"
                                @change="handleKnChange">
                                <ElOption label="向量知识库" :value="KnbTypeEnum.VECTOR" />
                                <ElOption label="RAG知识库" :value="KnbTypeEnum.RAG" />
                            </ElSelect>
                        </ElFormItem>
                        <!-- 挂靠知识库 -->
                        <ElFormItem label="挂靠知识库" prop="kb_ids">
                            <ElSelect
                                v-model="formData.kb_ids"
                                ref="selectKbRef"
                                class="!h-11 !w-[390px]"
                                clearable
                                placeholder="请选择挂靠知识库"
                                remote
                                filterable
                                :multiple="formData.kb_type == KnbTypeEnum.VECTOR"
                                :remote-method="getKnLists">
                                <ElOption
                                    v-for="item in knLists"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="`${item.id}`" />
                            </ElSelect>
                        </ElFormItem>
                        <!-- 引用上限 -->
                        <ElFormItem prop="search_tokens">
                            <template #label="{ label }">
                                <span class="flex items-center">
                                    引用上限
                                    <ElTooltip placement="top">
                                        <span class="ml-1 cursor-pointer">
                                            <Icon name="local-icon-privacy" color="#00000080"></Icon>
                                        </span>
                                        <template #content>
                                            <p>该参数表示单次文档从知识库检索最大的Tokens数量</p>
                                            <p>说明: 引用越多意味着所需消耗的token越多</p>
                                            <p>注意: 切记不要超出模型的最大token限制</p>
                                        </template>
                                    </ElTooltip>
                                </span>
                            </template>
                            <div class="flex items-center w-full gap-x-4">
                                <div class="flex-1">
                                    <ElSlider v-model="formData.search_tokens" :min="100" :max="20000" />
                                </div>
                                <ElInputNumber
                                    v-model="formData.search_tokens"
                                    controls-position="right"
                                    :min="100"
                                    :max="20000">
                                    <template #suffix>
                                        <span>tokens</span>
                                    </template>
                                </ElInputNumber>
                            </div>
                        </ElFormItem>

                        <!-- 仅在向量知识库时显示的额外设置 -->
                        <template v-if="formData.kb_type == KnbTypeEnum.VECTOR">
                            <div class="mt-4">
                                <ElDivider content-position="left">召回设置</ElDivider>
                            </div>
                            <!-- 检索模式 -->
                            <ElFormItem>
                                <template #label="{ label }">
                                    <span class="flex items-center">
                                        检索模式
                                        <ElTooltip placement="top">
                                            <span class="ml-1 cursor-pointer">
                                                <Icon name="local-icon-privacy" color="#00000080"></Icon>
                                            </span>
                                            <template #content>
                                                <p>语义检索: 采用向量化模型进行向量检索</p>
                                                <p>全文检索: 使用传统的数据库检索方式检索</p>
                                                <p>混合检索: 语义检索+全文检索,具有更好的效果,建议搭配重排模型使用。</p>
                                                <p>
                                                    注意:
                                                    当您使用全文或混合检索时，是没有语义相似度的，建议您开启重排模型
                                                </p>
                                            </template>
                                        </ElTooltip>
                                    </span>
                                </template>
                                <ElSelect
                                    class="!h-11 !w-[390px]"
                                    v-model="formData.search_mode"
                                    placeholder="请选择搜索模式">
                                    <ElOption
                                        v-for="option in searchOptions"
                                        :key="option.value"
                                        :label="option.label"
                                        :value="option.value" />
                                </ElSelect>
                            </ElFormItem>
                            <!-- 引用上下文 -->
                            <ElFormItem prop="context_num">
                                <template #label="{ label }">
                                    <span class="flex items-center">
                                        引用上下文
                                        <ElTooltip placement="top">
                                            <span class="ml-1 cursor-pointer">
                                                <Icon name="local-icon-privacy" color="#00000080"></Icon>
                                            </span>
                                            <template #content>
                                                <p>上下文记忆功能，对话时让大模型知道您前 N次 的对话。</p>
                                            </template>
                                        </ElTooltip>
                                    </span>
                                </template>
                                <div class="flex items-center w-full gap-x-4">
                                    <div class="flex-1">
                                        <ElSlider v-model="formData.context_num" :min="0" :max="8" :step="1" />
                                    </div>
                                    <ElInputNumber
                                        v-model="formData.context_num"
                                        controls-position="right"
                                        :min="0"
                                        :max="8">
                                        <template #suffix>条</template>
                                    </ElInputNumber>
                                </div>
                            </ElFormItem>
                            <!-- 最低相似度 -->
                            <ElFormItem>
                                <template #label="{ label }">
                                    <span class="flex items-center">
                                        最低相似度
                                        <ElTooltip placement="top">
                                            <span class="ml-1 cursor-pointer">
                                                <Icon name="local-icon-privacy" color="#00000080"></Icon>
                                            </span>
                                            <template #content>
                                                <p>语义检索的精度，提问检索的内容需要达到该精度才会被引用</p>
                                                <ol class="list-decimal pl-4">
                                                    <li>
                                                        高语义相似度(>=0.8):
                                                        会检索相关性高的知识，会更准确，同时也容易未命中。
                                                    </li>
                                                    <li>
                                                        低语义相似度(如0.4):
                                                        检索范围更大，更容易匹配知识，但可能回答会不准确。
                                                    </li>
                                                    <li>
                                                        不同的向量模型检索相似度不一样，具体情况到 『知识库 -
                                                        搜索测试』进行调试。
                                                    </li>
                                                    <li>
                                                        如果您使用的是『全文检索/混合检索』这个值则不会生效，仅对语义检索有效。
                                                    </li>
                                                </ol>
                                            </template>
                                        </ElTooltip>
                                    </span>
                                </template>
                                <div class="flex items-center w-full gap-x-4">
                                    <div class="flex-1">
                                        <ElSlider
                                            v-model="formData.search_similar"
                                            size="small"
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
                            <!-- 重排设置 -->
                            <div class="mt-4">
                                <ElDivider content-position="left">重排设置</ElDivider>
                            </div>
                            <div>
                                <ElFormItem label="重排开关">
                                    <div class="flex justify-between w-full gap-x-4">
                                        <div class="form-tips">
                                            开启后，则会对从数据库检索的内容进行重新排序(取分数最高的N条数据作为引用)
                                        </div>
                                        <ElSwitch
                                            v-model="formData.ranking_status"
                                            :active-value="1"
                                            :inactive-value="0"></ElSwitch>
                                    </div>
                                </ElFormItem>
                                <ElFormItem label="重排分数" v-if="formData.ranking_status === 1">
                                    <div class="flex items-center w-full gap-x-4">
                                        <div class="flex-1">
                                            <ElSlider
                                                v-model="formData.ranking_score"
                                                size="small"
                                                :min="0"
                                                :max="1"
                                                :step="0.001" />
                                        </div>
                                        <ElInputNumber
                                            v-model="formData.ranking_score"
                                            controls-position="right"
                                            :min="0"
                                            :max="1"
                                            :step="0.001" />
                                    </div>
                                    <div class="form-tips">表示如果数据重排后，分数没有达到该值则会过滤掉。</div>
                                </ElFormItem>
                            </div>
                            <!-- 问题优化 -->
                            <div class="mt-4">
                                <ElDivider content-position="left">问题优化</ElDivider>
                            </div>
                            <div>
                                <ElFormItem label="优化开关">
                                    <div class="flex justify-between w-full gap-x-4">
                                        <div class="form-tips">
                                            综合历史记录和问题, 多维度的生成相似问题, 可增加知识库检索时的精准度。
                                        </div>
                                        <ElSwitch
                                            v-model="formData.optimize_ask"
                                            :active-value="1"
                                            :inactive-value="0"></ElSwitch>
                                    </div>
                                </ElFormItem>
                                <ElFormItem label="优化模型" prop="optimize_m_id" v-if="formData.optimize_ask == 1">
                                    <ElSelect
                                        v-model="formData.optimize_m_id"
                                        class="!h-11 !w-[390px]"
                                        placeholder="请选择优化模型">
                                        <ElOption
                                            v-for="item in aiModelChannel"
                                            :key="item.id"
                                            :label="item.name"
                                            :value="parseInt(item.id)" />
                                    </ElSelect>
                                </ElFormItem>
                            </div>
                            <!-- 空搜索回复 -->
                            <ElFormItem label="空搜索回复">
                                <div>
                                    <div>
                                        <ElRadioGroup v-model="formData.search_empty_type">
                                            <ElRadio :value="1"> AI回复</ElRadio>
                                            <ElRadio :value="2"> 自定义回复</ElRadio>
                                        </ElRadioGroup>
                                    </div>
                                    <div class="form-tips">当知识库检索为空时候如何进行回复</div>
                                </div>
                            </ElFormItem>
                            <ElFormItem v-if="formData.search_empty_type === 2">
                                <ElInput
                                    v-model="formData.search_empty_text"
                                    placeholder="请输入回复内容，当搜索匹配不上内容时，直接回复填写的内容"
                                    type="textarea"
                                    :autosize="{ minRows: 6, maxRows: 6 }"
                                    :maxlength="1000"
                                    show-word-limit
                                    resize="none" />
                            </ElFormItem>
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
    const apiCall = formData.value.kb_type == KnbTypeEnum.VECTOR ? vectorKnowledgeBaseLists : knowledgeBaseLists;
    try {
        const { lists } = await apiCall({ page_size: 25000, name: query });
        knLists.value = lists || [];
    } catch (error) {
        console.error("获取知识库列表失败:", error);
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
:deep(.el-input-number) {
    .el-input-number__increase,
    .el-input-number__decrease {
        --el-input-number-controls-height: 19px;
    }
    .el-input {
        .el-input__inner {
            height: 36px;
        }
    }
}
</style>
