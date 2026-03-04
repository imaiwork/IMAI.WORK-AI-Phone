<template>
    <div class="h-full min-w-[1000px] px-4 pb-4">
        <div class="w-full h-full bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
            <div class="flex-shrink-0 flex items-center justify-between px-8 h-[80px] border-b border-[#F1F5F9]">
                <div
                    class="group flex items-center gap-2 cursor-pointer text-[#64748B] hover:text-primary transition-all"
                    @click="back">
                    <div
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 group-hover:bg-[#F0F6FF]">
                        <Icon name="el-icon-ArrowLeft" :size="16" />
                    </div>
                    <span class="text-[14px] font-black">返回上一步</span>
                </div>
                <div
                    class="px-3 py-1 bg-[#F0F6FF] rounded-full text-[11px] font-black text-primary uppercase tracking-wider">
                    {{ isRag ? "RAG Mode" : "Vector Mode" }}
                </div>
            </div>

            <div class="grow flex flex-col overflow-hidden">
                <div class="w-full max-w-[480px] mx-auto flex flex-col h-full">
                    <div class="mt-12 mb-8 text-center">
                        <h1 class="text-[28px] font-[900] text-[#0F172A] tracking-tight">创建新的知识库</h1>
                        <p class="text-[14px] font-medium text-[#94A3B8] mt-2">为您的 AI 提供精准的私有数据支撑</p>
                    </div>

                    <div class="grow min-h-0">
                        <ElScrollbar>
                            <div class="px-4 pb-10">
                                <div class="bg-slate-50 rounded-[24px] p-6 border border-[#F1F5F9]">
                                    <base-form ref="baseFormRef" v-model="formData" />
                                </div>

                                <div
                                    v-if="tokensValue && isRag"
                                    class="mt-6 flex items-center justify-center gap-2 p-3 bg-[#FFF9F0] rounded-xl border border-[#FFE4BA]">
                                    <Icon name="el-icon-WarningFilled" color="#ED6A0C" :size="16" />
                                    <span class="text-xs font-medium text-[#ED6A0C]">
                                        本次操作将消耗
                                        <span class="text-[15px] font-black">{{ tokensValue }}</span> 算力
                                    </span>
                                </div>
                            </div>
                        </ElScrollbar>
                    </div>

                    <div
                        class="flex-shrink-0 py-8 px-4 flex flex-col items-center gap-4 bg-white border-t border-[#F1F5F9]">
                        <ElButton
                            type="primary"
                            class="!h-[56px] !rounded-2xl w-full !text-[16px] !font-black hover:!scale-[1.02] active:!scale-[0.98] transition-all shadow-light shadow-[#0065FB]/20"
                            :loading="isLock"
                            :disabled="userTokens < tokensValue && isRag"
                            @click="lockFn">
                            立即开启知识库
                        </ElButton>

                        <div
                            v-if="userTokens < tokensValue && isRag"
                            class="text-xs font-medium text-red-500 flex items-center gap-1">
                            <Icon name="el-icon-CircleClose" />
                            当前算力不足 (剩余 {{ userTokens }})
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { knowledgeBaseAdd, vectorKnowledgeBaseAdd } from "@/api/knowledge_base";
import { uploadImage } from "@/api/app";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import type { CreateFormData } from "./type";
import { KnTypeEnum } from "../_enums";
import BaseForm from "./base-form.vue";
import KnDefaultCover from "@/assets/images/kn_default_cover.png";
import { urlToFile } from "@/utils/util"; // 确保导入工具函数

const emit = defineEmits(["success", "back"]);
const route = useRoute();
const router = useRouter();
const userStore = useUserStore();
const { getTokenByScene } = userStore;
const { userTokens } = toRefs(userStore);

const tokensValue = computed(() => {
    return getTokenByScene(TokensSceneEnum.KNOWLEDGE_CREATE)?.score;
});

const isRag = computed(() => {
    return formData.type == KnTypeEnum.RAG;
});

const formData = reactive<CreateFormData>({
    name: "",
    description: "",
    cover: "",
    type: (route.query.type as KnTypeEnum) || KnTypeEnum.VECTOR,
});

const baseFormRef = shallowRef<InstanceType<typeof BaseForm>>();

const handleNext = async () => {
    await baseFormRef.value.validateForm();
    try {
        const { name, description, cover } = formData;
        let coverUrl = cover;
        if (!cover) {
            const file = await urlToFile(KnDefaultCover, "kn_default_cover.png");
            const res = await uploadImage({ file });
            coverUrl = res.uri;
        }

        const data = isRag.value
            ? await knowledgeBaseAdd({
                  name,
                  description,
                  image: coverUrl,
              })
            : await vectorKnowledgeBaseAdd({
                  name,
                  intro: description,
                  image: coverUrl,
                  documents_model_id: 2,
                  documents_model_sub_id: 2,
                  embedding_model_id: 3,
                  embedding_model_sub_id: 3,
              });

        feedback.msgSuccess("创建成功");
        router.replace({
            path: `/knowledge_base/detail/${data.id}`,
            query: {
                kn_type: formData.type,
                index_id: data.index_id,
                category_id: data.category_id,
                kn_name: data.name,
            },
        });
    } catch (error) {
        feedback.msgError(error || "操作失败");
    }
};

const back = () => {
    emit("back");
};

const { lockFn, isLock } = useLockFn(handleNext);

watch(
    () => route.query,
    () => {
        if (route.query.type) formData.type = route.query.type as KnTypeEnum;
    }
);
</script>

<style lang="scss" scoped>
:deep(.kb-type-select) {
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);

    .el-select-dropdown__item.is-selected {
        background-color: #f0f6ff;
        color: #0065fb;

        .options-item .item-icon {
            background-color: #0065fb;
            color: #ffffff;
        }
    }
}

/* 按钮动画提升 */
.el-button--primary {
    --el-button-bg-color: #0065fb;
    --el-button-border-color: #0065fb;
    --el-button-hover-bg-color: #3384fc;
    --el-button-hover-border-color: #3384fc;
}
</style>
