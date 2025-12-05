<template>
    <div
        class="w-[350px] h-full rounded-[20px] shadow-[0_0_0_1px_#333333] bg-app-bg-2 flex flex-col relative"
        :class="{ 'rounded-tr-none rounded-br-none': showPromptDialog }">
        <div class="grow min-h-0">
            <component
                ref="createPanelRef"
                :is="getComponents"
                @update:formData="handleUpdateFormData"
                @generate-prompt="handlePrompt"></component>
        </div>
        <div class="flex-shrink-0 px-6">
            <div class="flex items-center justify-between">
                <div class="text-[#ffffff80]">
                    算力消耗：<span class="text-white">{{ consumeTokens }}{{ consumeTokensUnit }}</span>
                </div>
                <ElTooltip placement="top" popper-class="!rounded-xl !p-3">
                    <div class="text-[#ffffff80] cursor-pointer">查看明细</div>
                    <template #content>
                        <div class="w-[246px]">
                            <div class="text-white text-sm">算力消耗明细</div>
                            <div class="text-xs text-[#ffffff99] mt-2">
                                <div class="flex items-center justify-between">
                                    <span>参考生成</span>
                                    <span>{{ consumeTokens }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>生成数量</span>
                                    <span>X{{ formData.img_count || 1 }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>总计：</span>
                                    <span>{{ getTokensCount }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </ElTooltip>
            </div>
            <div class="mt-4 mb-4">
                <ElButton type="primary" class="w-full !h-[50px] !rounded-full" @click="handleGenerate"
                    >立即生成</ElButton
                >
            </div>
        </div>
        <prompt-dialog
            v-if="showPromptDialog"
            ref="promptDialogRef"
            @use="handlePromptUse"
            @close="showPromptDialog = false"></prompt-dialog>
    </div>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { SidebarEnum, drawTypeEnumMap, DrawTypeEnum, GenerateVideoTypeEnum, ModelEnum } from "../_enums";
import GenerationImageForm from "./generation-image-form.vue";
import GoodsImageForm from "./goods-image-form.vue";
import FashionImageForm from "./fashion-image-form.vue";
import PosterImageForm from "./poster-image-form.vue";
import GenerationVideoForm from "./generation-video-form.vue";
import PromptDialog from "./prompt-dialog.vue";
import useCreateForm from "../_hooks/useCreateForm";
const props = defineProps<{
    type: number;
}>();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const createPanelRef = ref();
const consumeTokens = ref(0);
const consumeTokensUnit = ref("");

const getTokensCount = computed(() => {
    return (formData.img_count || 1) * consumeTokens.value;
});
// 获取消耗tokens
const getConsumeTokens = () => {
    // 初始化tokens和单位
    let tokens = 0;
    consumeTokensUnit.value = "";

    // 定义token获取映射
    const tokenMappings = {
        [SidebarEnum.IMAGE_GENERATION]: {
            [drawTypeEnumMap[DrawTypeEnum.TXT2IMAGE]]: {
                [ModelEnum.HIDREAMAI]: TokensSceneEnum.TEXT_TO_IMAGE,
                [ModelEnum.GENERAL]: TokensSceneEnum.VOLC_TEXT_TO_IMAGE,
                [ModelEnum.SEEDREAM]: TokensSceneEnum.VOLC_TEXT_TO_IMAGE_V2,
            },
            [drawTypeEnumMap[DrawTypeEnum.IMAGE2IMAGE]]: {
                [ModelEnum.HIDREAMAI]: TokensSceneEnum.IMAGE_TO_IMAGE,
                [ModelEnum.SEEDREAM]: TokensSceneEnum.VOLC_IMAGE_TO_IMAGE_V2,
            },
        },
        [SidebarEnum.POSTER_IMAGE]: {
            [ModelEnum.HIDREAMAI]: TokensSceneEnum.TEXT_TO_IMAGE,
            [ModelEnum.GENERAL]: TokensSceneEnum.VOLC_TEXT_TO_POSTERIMG,
            [ModelEnum.SEEDREAM]: TokensSceneEnum.VOLC_TEXT_TO_POSTERIMG_V2,
        },
        [SidebarEnum.VIDEO_GENERATION]: {
            [GenerateVideoTypeEnum.TXT2VIDEO]: {
                [ModelEnum.GENERAL]: TokensSceneEnum.VOLC_TEXT_TO_VIDEO,
                [ModelEnum.SEEDANCE]: TokensSceneEnum.DOUBAO_TEXT_TO_VIDEO,
            },
            [GenerateVideoTypeEnum.IMG2VIDEO]: {
                [ModelEnum.GENERAL]: TokensSceneEnum.VOLC_IMAGE_TO_VIDEO,
                [ModelEnum.SEEDANCE]: TokensSceneEnum.DOUBAO_IMAGE_TO_VIDEO,
            },
        },
    };

    // 特殊场景直接映射
    const directMappings = {
        [SidebarEnum.GOODS_IMAGE]: TokensSceneEnum.GOODS_IMAGE,
        [SidebarEnum.FASHION_IMAGE]: TokensSceneEnum.MODEL_IMAGE,
    };

    // 处理直接映射场景
    if (directMappings[props.type]) {
        const { score, unit } = userStore.getTokenByScene(directMappings[props.type]);
        tokens = score;
        consumeTokensUnit.value = unit;
    } else if (props.type == SidebarEnum.POSTER_IMAGE) {
        const typeMapping = tokenMappings[props.type];
        const { score, unit } = userStore.getTokenByScene(typeMapping[formData.model]);
        tokens = score;
        consumeTokensUnit.value = unit;
    } else if (tokenMappings[props.type]) {
        const typeMapping = tokenMappings[props.type];
        const modelMapping = typeMapping[formData.type];

        if (modelMapping) {
            const scene = modelMapping[formData.model];
            if (scene) {
                const { score, unit } = userStore.getTokenByScene(scene);
                tokens = score;
                consumeTokensUnit.value = unit;
            }
        }
    }

    consumeTokens.value = tokens;
};

const formData = reactive<any>({
    img_count: 1,
    model: "",
    type: "",
});

const getComponents = computed(() => {
    const components = {
        [SidebarEnum.IMAGE_GENERATION]: GenerationImageForm,
        [SidebarEnum.GOODS_IMAGE]: GoodsImageForm,
        [SidebarEnum.FASHION_IMAGE]: FashionImageForm,
        [SidebarEnum.POSTER_IMAGE]: PosterImageForm,
        [SidebarEnum.VIDEO_GENERATION]: GenerationVideoForm,
    };
    return components[props.type];
});

const promptDialogRef = ref<InstanceType<typeof PromptDialog>>();
const showPromptDialog = ref(false);

const handleUpdateFormData = (data: any) => {
    setFormData(data, formData);
    getConsumeTokens();
};

const handlePrompt = async (options: { prompt?: string; promptId?: number }) => {
    showPromptDialog.value = true;
    await nextTick();
    promptDialogRef.value?.startGenerate(options);
};

const handlePromptUse = (prompt: string) => {
    createPanelRef.value?.setPrompt(prompt);
};

const handleGenerate = async () => {
    if (userTokens.value < getTokensCount.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    await createPanelRef.value?.validateForm();
    const formData = createPanelRef.value?.getFormData();
    useCreateForm().setFormData(formData);
};

watch(
    () => props.type,
    (newVal) => {
        showPromptDialog.value = false;
    }
);
</script>
