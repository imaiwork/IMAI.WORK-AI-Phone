<template>
    <popup
        ref="popupRef"
        top="10vh"
        width="700px"
        cancel-button-text=""
        confirm-button-text=""
        style="padding: 0"
        :show-close="false">
        <div class="-my-4 p-[18px]">
            <div class="absolute top-[18px] right-[18px] w-6 h-6" @click="close">
                <close-btn :theme="ThemeEnum.LIGHT" />
            </div>
            <div class="font-bold text-[20px]">向量检索配置</div>
            <div class="border-b border-[#0000001a] my-3"></div>
            <ElForm ref="formRef" :model="formData" label-width="100px">
                <ElFormItem label="检索模式" prop="search_mode">
                    <template #label="{ label }">
                        <span class="flex items-center">
                            {{ label }}
                            <ElTooltip placement="top">
                                <div class="ml-1 cursor-pointer">
                                    <Icon name="local-icon-privacy" color="#00000080" />
                                </div>
                                <template #content>
                                    <p>语义检索: 采用向量化模型进行向量检索</p>
                                    <p>全文检索: 使用传统的数据库检索方式检索</p>
                                    <p>混合检索: 语义检索+全文检索,具有更好的效果,建议搭配重排模型使用。</p>
                                    <p>注意: 当您使用全文或混合检索时，是没有语义相似度的，建议您开启重排模型</p>
                                </template>
                            </ElTooltip>
                        </span>
                    </template>
                    <ElSelect class="!w-64" v-model="formData.search_mode" placeholder="请选择搜索模式">
                        <ElOption
                            v-for="option in searchOptions"
                            :key="option.value"
                            :label="option.label"
                            :value="option.value" />
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="引用上限" prop="search_tokens">
                    <template #label="{ label }">
                        <span class="flex items-center">
                            {{ label }}
                            <ElTooltip placement="top">
                                <div class="ml-1 cursor-pointer">
                                    <Icon name="local-icon-privacy" color="#00000080" />
                                </div>
                                <template #content>
                                    <p>该参数表示单次文档从知识库检索最大的Tokens数量</p>
                                    <p>说明: 引用越多意味着所需消耗的token越多</p>
                                    <p>注意: 切记不要超出模型的最大token限制</p>
                                </template>
                            </ElTooltip>
                        </span>
                    </template>
                    <div class="flex items-center">
                        <ElSlider class="!w-64" v-model="formData.search_tokens" size="small" :min="100" :max="30000" />
                        <ElInputNumber
                            class="ml-4"
                            v-model="formData.search_tokens"
                            size="small"
                            :min="100"
                            :max="30000" />
                    </div>
                </ElFormItem>
                <template v-if="isVisibleSearchSimilar">
                    <ElFormItem label="最低相似度" prop="search_similar">
                        <template #label="{ label }">
                            <span class="flex items-center">
                                {{ label }}
                                <ElTooltip placement="top">
                                    <div class="ml-1 cursor-pointer">
                                        <Icon name="local-icon-privacy" color="#00000080" />
                                    </div>
                                    <template #content>
                                        <p>语义检索的精度，提问检索的内容需要达到该精度才会被引用</p>
                                        <ol class="list-decimal pl-4">
                                            <li>
                                                高语义相似度(>=0.8): 会检索相关性高的知识，会更准确，同时也容易未命中。
                                            </li>
                                            <li>
                                                低语义相似度(如0.4): 检索范围更大，更容易匹配知识，但可能回答会不准确。
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
                        <div class="flex items-center">
                            <ElSlider
                                class="!w-64"
                                v-model="formData.search_similar"
                                size="small"
                                :min="0"
                                :max="1"
                                :step="0.001"
                                :disabled="formData.search_mode !== 'similar'" />
                            <ElInputNumber
                                class="ml-4"
                                v-model="formData.search_similar"
                                size="small"
                                :min="0"
                                :max="1"
                                :step="0.001"
                                :disabled="formData.search_mode !== 'similar'" />
                        </div>
                    </ElFormItem>
                </template>

                <ElDivider />

                <ElFormItem label="结果重排" prop="ranking_status">
                    <template #label="{ label }">
                        <span class="flex items-center">
                            {{ label }}
                            <ElTooltip content="使用重排模型来进行二次排序, 可增强综合排名。" placement="top">
                                <div class="ml-1 cursor-pointer">
                                    <Icon name="local-icon-privacy" color="#00000080" />
                                </div>
                            </ElTooltip>
                        </span>
                    </template>
                    <ElSwitch v-model="formData.ranking_status" :active-value="1" :inactive-value="0" />
                </ElFormItem>
                <ElFormItem label="重排权重" prop="ranking_score">
                    <template #label="{ label }">
                        <span class="flex items-center">
                            {{ label }}
                            <ElTooltip content="低于这个分数的数据将会被过滤。" placement="top">
                                <div class="ml-1 cursor-pointer">
                                    <Icon name="local-icon-privacy" color="#00000080" />
                                </div>
                            </ElTooltip>
                        </span>
                    </template>
                    <div class="flex items-center">
                        <ElSlider
                            class="!w-64"
                            v-model="formData.ranking_score"
                            size="small"
                            :min="0"
                            :max="1"
                            :step="0.001" />
                        <ElInputNumber
                            class="ml-4"
                            v-model="formData.ranking_score"
                            size="small"
                            :min="0"
                            :max="1"
                            :step="0.001" />
                    </div>
                </ElFormItem>
                <!-- <ElFormItem label="重排模型" prop="ranking_model">
                    <ElSelect class="!w-64" v-model="formData.search_mode" placeholder="请选择搜索模式">
                        <ElOption
                            v-for="option in searchOptions"
                            :key="option.value"
                            :label="option.label"
                            :value="option.value" />
                    </ElSelect>
                </ElFormItem> -->
            </ElForm>
            <ElDivider />
            <div class="mt-3 flex">
                <ElButton class="flex-1 !rounded-full !h-[50px] w-[98px]" @click="close()">取消</ElButton>
                <ElButton type="primary" class="flex-1 !rounded-full !h-[50px] w-[98px]" @click="save"> 保存 </ElButton>
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

<style scoped></style>
