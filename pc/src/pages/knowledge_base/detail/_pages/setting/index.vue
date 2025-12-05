<template>
    <div class="h-full bg-white flex flex-col rounded-[20px]">
        <div class="flex-shrink-0 h-[88px] flex items-center justify-between px-[30px] border-b border-[#0000000d]">
            <div>
                <span>知识库设置（{{ isRag ? "RAG" : "向量" }}）</span>
                <span class="text-[rgba(0,0,0,0.5)]">在这里，您可以修改此知识库的属性和检索设置</span>
            </div>
            <ElButton type="primary" class="w-[98px] !h-10 !rounded-full" :loading="isLock" @click="lockFn"
                >保存</ElButton
            >
        </div>
        <div class="grow min-h-0 py-[20px]">
            <ElScrollbar class="h-full">
                <div class="w-[456px] mx-auto">
                    <base-form ref="formRef" v-model="formData" :is-edit="true" />
                    <ElForm label-position="top">
                        <ElFormItem label="可见权限">
                            <ElSelect class="!h-11" v-model="visible_permission" placeholder="请选择" disabled>
                                <ElOption label="私人" value="1" />
                            </ElSelect>
                        </ElFormItem>
                        <ElFormItem label="索引模式">
                            <ElSelect class="!h-11" v-model="index_mode" placeholder="请选择" disabled>
                                <ElOption label="高质量" value="1" />
                            </ElSelect>
                        </ElFormItem>
                        <ElFormItem label="检索设置" v-if="!isRag">
                            <div class="w-full border border-[var(--el-border-color)] rounded-lg p-[14px] leading-5">
                                <div class="flex items-center gap-x-3">
                                    <div
                                        class="rounded-md border border-[rgba(0,0,0,0.1)] w-10 h-10 flex items-center justify-center">
                                        <Icon name="local-icon-list" :size="24"></Icon>
                                    </div>
                                    <div>
                                        <div class="font-bold">向量检索</div>
                                        <div class="text-[11px] text-[#00000080] mt-1">
                                            通过生成查询嵌入并查询与其向量表示最相似的文本分段
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-6 grid grid-cols-2 gap-x-3">
                                    <div class="">
                                        <div class="text-[#00000080]">Top K</div>
                                        <div class="mt-3">
                                            <ElInput
                                                class="!h-11"
                                                disabled
                                                v-model="top_k"
                                                v-number-input="{
                                                    min: 1,
                                                    max: 1024,
                                                }"
                                                type="number">
                                            </ElInput>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="text-[#00000080]">相似度阈值</div>
                                        <div class="mt-3">
                                            <ElInput
                                                class="!h-11"
                                                disabled
                                                v-model="rerank_min_score"
                                                v-number-input="{
                                                    min: 0,
                                                    max: 1,
                                                }"
                                                type="number">
                                            </ElInput>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 w-full p-[14px] rounded-lg bg-[#f6f6f6] border border-[#efefef] leading-5">
                                <div class="flex items-center gap-x-3">
                                    <div
                                        class="rounded-md border border-[rgba(0,0,0,0.1)] w-10 h-10 flex items-center justify-center">
                                        <Icon name="local-icon-list_search" :size="24"></Icon>
                                    </div>
                                    <div>
                                        <div class="font-bold">向量检索</div>
                                        <div class="text-[11px] text-[#00000080] mt-1">
                                            通过生成查询嵌入并查询与其向量表示最相似的文本分段
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </ElFormItem>
                    </ElForm>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    knowledgeBaseDetail,
    knowledgeBaseEdit,
    vectorKnowledgeBaseDetail,
    vectorKnowledgeBaseEdit,
} from "@/api/knowledge_base";
import { KnTypeEnum } from "@/pages/knowledge_base/_enums";
import { CreateFormData } from "@/pages/knowledge_base/_components/type";
import BaseForm from "@/pages/knowledge_base/_components/base-form.vue";

const route = useRoute();
const knId = computed(() => route.params.id);
const isRag = computed(() => route.query.kn_type == KnTypeEnum.RAG);

const formRef = shallowRef<InstanceType<typeof BaseForm>>();
const formData = reactive<CreateFormData>({
    name: "",
    description: "",
    cover: "",
});

const visible_permission = ref<string>("1");
const index_mode = ref<string>("1");
const top_k = ref<number>(2);
const rerank_min_score = ref<number>(2);

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validateForm();
    try {
        if (isRag.value) {
            await knowledgeBaseEdit({
                id: knId.value,
                name: formData.name,
                description: formData.description,
                image: formData.cover,
            });
        } else {
            await vectorKnowledgeBaseEdit({
                id: knId.value,
                name: formData.name,
                intro: formData.description,
                image: formData.cover,
                documents_model_id: 2,
                documents_model_sub_id: 2,
                embedding_model_id: 3,
                embedding_model_sub_id: 3,
            });
        }
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError(error);
    }
});

const getDetail = async () => {
    if (isRag.value) {
        const { name, description, image } = await knowledgeBaseDetail({ id: knId.value });
        formData.name = name;
        formData.description = description;
        formData.cover = image;
    } else {
        const { name, intro, image } = await vectorKnowledgeBaseDetail({ id: knId.value });
        formData.name = name;
        formData.description = intro;
        formData.cover = image;
    }
};

onMounted(() => {
    getDetail();
});
</script>
