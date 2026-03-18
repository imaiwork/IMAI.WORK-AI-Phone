<template>
    <div class="w-[350px] h-full relative">
        <div
            class="h-full rounded-[20px] bg-white flex flex-col overflow-hidden"
            :class="{ 'rounded-tr-none rounded-br-none': showPromptDialog }">
            <div class="grow min-h-0">
                <component
                    ref="createPanelRef"
                    :is="getComponents"
                    @update:formData="handleUpdateFormData"
                    @generate-prompt="handlePrompt"></component>
            </div>
            <div class="flex-shrink-0 px-6 py-4 bg-[#ffffff]/80 backdrop-blur-md border-t border-[#F1F5F9]">
                <div class="flex items-center justify-between mb-5 px-1">
                    <div class="flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#0065fb] opacity-60"></div>
                        <span class="text-xs font-black text-[#64748B] uppercase tracking-wider">预计消耗</span>
                        <span class="text-[13px] font-[900] text-[#1E293B] ml-1">
                            {{ consumeTokens }}
                            <span class="text-[10px] text-[#94A3B8] font-medium">{{ consumeTokensUnit }}</span>
                        </span>
                    </div>

                    <ElTooltip effect="light" placement="top" popper-class="custom-tooltip">
                        <div
                            class="flex items-center gap-1 text-[11px] font-medium text-[#94A3B8] hover:text-[#0065fb] cursor-pointer transition-colors group">
                            <span>查看明细</span>
                            <Icon name="el-icon-QuestionFilled" :size="12"></Icon>
                        </div>
                        <template #content>
                            <div class="w-[200px] p-1">
                                <div class="text-[13px] font-black text-[#1E293B] mb-3">算力消耗明细</div>
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-[#94A3B8] font-medium">参考生成单价</span>
                                        <span class="text-[#1E293B] font-black">{{ consumeTokens }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-[#94A3B8] font-medium">生成数量</span>
                                        <span class="text-[#1E293B] font-black">×{{ formData.img_count || 1 }}</span>
                                    </div>
                                    <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-[#0065fb] font-black">预计总计</span>
                                        <span class="text-[#0065fb] font-[900]"
                                            >{{ getTokensCount }} {{ consumeTokensUnit }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </template>
                    </ElTooltip>
                </div>

                <div class="relative group">
                    <div
                        class="absolute -inset-0.5 bg-gradient-to-r from-[#0065fb] to-[#818CF8] rounded-full blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
                    <div class="relative z-10">
                        <ElButton
                            type="primary"
                            class="generate-btn w-full !h-[54px] !rounded-full !border-none"
                            @click="handleGenerate">
                            <div class="flex items-center justify-center gap-2">
                                <Icon name="el-icon-MagicStick" :size="18"></Icon>
                                <span class="text-[15px] font-[900] tracking-[2px]">立即生成</span>
                            </div>
                        </ElButton>
                    </div>
                </div>
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
<style scoped lang="scss">
/* 生成按钮深度定制 */
.generate-btn {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
    box-shadow: 0 10px 20px -6px rgba(79, 70, 229, 0.4) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.5) !important;
        background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%) !important;
    }

    &:active {
        transform: translateY(0);
        box-shadow: 0 5px 10px -3px rgba(79, 70, 229, 0.4) !important;
    }
}

/* Tooltip 弹窗美化 */
:deep(.custom-tooltip) {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid #f1f5f9 !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05) !important;

    .el-popper__arrow::before {
        background: rgba(255, 255, 255, 0.95) !important;
        border: 1px solid #f1f5f9 !important;
    }
}
</style>
