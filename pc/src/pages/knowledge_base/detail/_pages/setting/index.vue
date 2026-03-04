<template>
    <div class="h-full flex flex-col min-w-[1000px]">
        <div class="flex flex-col h-full bg-white rounded-[24px] border border-br overflow-hidden w-full">
            <div class="flex-shrink-0 h-[88px] flex items-center justify-between px-8 border-b border-br">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <span class="text-[18px] font-[900] text-[#1E293B]">知识库设置</span>
                        <span class="px-2 py-0.5 rounded-md bg-[#0065fb]/10 text-primary text-[11px] font-black italic">
                            {{ isRag ? "RAG ENGINE" : "VECTOR DB" }}
                        </span>
                    </div>
                    <div class="text-[13px] font-medium text-[#94A3B8]">
                        修改知识库的基本属性、可见权限以及底层检索参数配置。
                    </div>
                </div>
                <ElButton type="primary" class="save-btn" :loading="isLock" @click="lockFn"> 保存设置 </ElButton>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar>
                    <div class="w-[600px] mx-auto py-10 space-y-8">
                        <div class="setting-section">
                            <div class="section-header">
                                <div class="w-1 h-4 bg-primary rounded-full"></div>
                                <span class="text-[15px] font-[900] text-[#1E293B]">基础信息</span>
                            </div>
                            <div class="bg-white rounded-2xl border border-br p-6">
                                <base-form ref="formRef" v-model="formData" :is-edit="true" />
                            </div>
                        </div>

                        <div class="setting-section">
                            <div class="section-header">
                                <div class="w-1 h-4 bg-primary rounded-full"></div>
                                <span class="text-[15px] font-[900] text-[#1E293B]">访问控制与模式</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="info-card">
                                    <span class="label">可见权限</span>
                                    <div class="value"><Icon name="el-icon-Lock" /> <span class="ml-1">私人</span></div>
                                </div>
                                <div class="info-card">
                                    <span class="label">索引模式</span>
                                    <div class="value">
                                        <Icon name="el-icon-Star" /> <span class="ml-1">高质量</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="setting-section" v-if="!isRag">
                            <div class="section-header">
                                <div class="w-1 h-4 bg-primary rounded-full"></div>
                                <span class="text-[15px] font-[900] text-[#1E293B]">检索参数（预览）</span>
                            </div>

                            <div class="bg-slate-50 rounded-[24px] border border-[#F1F5F9] p-6 space-y-6">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-12 h-12 rounded-xl bg-white border border-br flex items-center justify-center text-primary">
                                        <Icon name="local-icon-list" :size="28"></Icon>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-[14px] font-[900] text-[#1E293B]">语义向量检索</div>
                                        <div class="text-xs font-medium text-[#94A3B8] mt-1">
                                            通过 Embedding 模型生成向量，检索余弦相似度最高的内容分段。
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="param-box">
                                        <div class="p-label">Top K</div>
                                        <div class="p-value">{{ top_k }}</div>
                                    </div>
                                    <div class="param-box">
                                        <div class="p-label">相似度阈值</div>
                                        <div class="p-value">{{ rerank_min_score }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 pt-4 border-t border-br">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-[#F1F5F9] flex items-center justify-center text-[#94A3B8]">
                                        <Icon name="local-icon-list_search" :size="20"></Icon>
                                    </div>
                                    <div class="text-[13px] font-medium text-[#475569]">全文关键字检索 (已禁用)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
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

<style scoped lang="scss">
.save-btn {
    @apply rounded-xl h-[44px] px-8 font-black text-[15px] border-none transition-all;
    box-shadow: 0 8px 16px -4px rgba(var(--el-color-primary), 0.4);
    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 20px -4px rgba(var(--el-color-primary), 0.5);
    }
}

.setting-section {
    @apply space-y-4;
    .section-header {
        @apply flex items-center gap-2 px-1;
    }
}

.info-card {
    @apply bg-white border border-[#F1F5F9] rounded-2xl p-4 flex flex-col gap-1;
    .label {
        @apply text-[11px] font-black text-[#94A3B8] uppercase tracking-wider;
    }
    .value {
        @apply text-[14px] font-black text-[#1E293B] flex items-center;
    }
}

.param-box {
    @apply bg-white border border-br rounded-xl p-3;
    .p-label {
        @apply text-[11px] font-medium text-[#94A3B8] mb-1;
    }
    .p-value {
        @apply text-[16px] font-black text-primary;
    }
}

:deep(.el-form-item__label) {
    @apply text-[13px] font-black text-[#64748B] mb-2;
}
</style>
