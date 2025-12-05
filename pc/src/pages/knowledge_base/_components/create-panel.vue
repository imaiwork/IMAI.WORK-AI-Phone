<template>
    <div class="p-4 h-full">
        <div class="h-full flex flex-col bg-white rounded-xl">
            <div class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-b border-[#0000000d]">
                <div class="flex items-center gap-2 cursor-pointer" @click="back">
                    <Icon name="el-icon-ArrowLeft"></Icon>
                    <div>返回上一步</div>
                </div>
            </div>
            <div class="grow min-h-0 mt-4 flex flex-col w-[456px] mx-auto">
                <div class="text-[20px] font-bold mt-6">创建新的知识库</div>
                <div class="flex-shrink min-h-0 mt-[23px]">
                    <ElScrollbar>
                        <div class="px-2">
                            <base-form ref="baseFormRef" v-model="formData"></base-form>
                        </div>
                    </ElScrollbar>
                </div>
                <div class="flex justify-center my-[30px]">
                    <ElButton
                        type="primary"
                        class="!h-[50px] !rounded-full w-[318px]"
                        :loading="isLock"
                        :disabled="userTokens < tokensValue && isRag"
                        @click="lockFn">
                        创建知识库
                        <template v-if="tokensValue && isRag">({{ tokensValue }}算力)</template>
                    </ElButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { knowledgeBaseAdd, vectorKnowledgeBaseAdd } from "@/api/knowledge_base";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import type { CreateFormData } from "./type";
import { KnTypeEnum } from "../_enums";
import BaseForm from "./base-form.vue";

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
    type: route.query.type as KnTypeEnum,
});

const baseFormRef = shallowRef<InstanceType<typeof BaseForm>>();

const handleNext = async () => {
    await baseFormRef.value.validateForm();
    try {
        const { name, description, cover } = formData;
        const data = isRag.value
            ? await knowledgeBaseAdd({
                  name,
                  description,
                  image: cover,
              })
            : await vectorKnowledgeBaseAdd({
                  name,
                  intro: description,
                  image: cover,
                  documents_model_id: 2,
                  documents_model_sub_id: 2,
                  embedding_model_id: 3,
                  embedding_model_sub_id: 3,
              });
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
        formData.type = route.query.type as KnTypeEnum;
    }
);
</script>
<style lang="scss">
.kb-type-select {
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.06);
    backdrop-filter: blur(6px);
    .el-select-dropdown__list {
        @apply p-2 flex flex-col gap-y-2;
    }
    .el-select-dropdown__item {
        @apply h-11 px-3 rounded-md;
        &.is-selected {
            @apply bg-[#F6F6F6] text-black shadow-[0_0_0_1px_rgba(239,239,239,1)];
            .options-item {
                .item-icon {
                    @apply bg-primary;
                    svg {
                        color: #ffffff !important;
                    }
                }
            }
        }

        .options-item {
            @apply h-full;
            .item-icon {
                @apply w-5 h-5 flex items-center justify-center rounded bg-[#00000003];
            }
        }
    }
}
</style>
