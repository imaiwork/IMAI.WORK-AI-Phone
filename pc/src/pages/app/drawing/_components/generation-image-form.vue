<template>
    <div class="h-full w-full flex flex-col bg-slate-50">
        <div class="shrink-0 px-6 h-[72px] flex items-center bg-white border-b border-[#F1F5F9]">
            <div class="flex p-1 rounded-[16px] w-full bg-[#F1F5F9]">
                <div
                    v-for="tab in typeTabs"
                    :key="tab.value"
                    @click="handleTypeTabClick(tab.value)"
                    class="flex-1 py-2 text-center cursor-pointer transition-all duration-300 rounded-[12px] text-[13px] font-[900]"
                    :class="
                        formData.type === tab.value ? 'bg-white text-primary ' : 'text-[#64748B] hover:text-[#1E293B]'
                    ">
                    {{ tab.label }}
                </div>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="p-4 space-y-3">
                    <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9]">
                        <div class="flex items-center gap-2 mb-4 px-1">
                            <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                            <span class="text-[14px] font-[900] text-[#1E293B]">绘制模型</span>
                            <span class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-wider ml-auto"
                                >Drawing Model</span
                            >
                        </div>
                        <ElSelect
                            v-model="formData.model"
                            class="custom-select w-full"
                            popper-class="custom-select-popper"
                            placeholder="请选择模型"
                            :show-arrow="false"
                            @change="handleModelChange">
                            <ElOption
                                v-for="item in getModelChannel"
                                :label="item.name"
                                :value="item.id"
                                :key="item.id">
                            </ElOption>
                        </ElSelect>
                    </div>

                    <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9]">
                        <div class="flex items-center justify-between mb-4 px-1">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                                <span class="text-[14px] font-[900] text-[#1E293B]">
                                    {{ formData.type === FormTypeEnum.TXT2IMAGE ? "画面描述" : "修改建议" }}
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <div
                                    class="tool-btn"
                                    @click="handleGeneratePrompt(CopywritingTypeEnum.AI_IMAGE_TO_IMAGE)">
                                    <Icon name="el-icon-MagicStick" :size="14"></Icon>
                                    <span class="ml-1 text-[11px] font-medium">随机灵感</span>
                                </div>
                            </div>
                        </div>

                        <div class="relative group">
                            <ElInput
                                v-model="formData.prompt"
                                type="textarea"
                                :rows="6"
                                resize="none"
                                placeholder="描述你想要生成的画面，建议使用英文标签以获得更好效果..."
                                class="custom-prompt-input"></ElInput>
                            <div
                                class="absolute bottom-3 right-3 text-[10px] font-medium text-[#CBD5E1] group-focus-within:text-primary">
                                {{ formData.prompt.length }} / 500
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="formData.type === FormTypeEnum.IMAGE2IMAGE"
                        class="bg-white rounded-[24px] p-5 border border-[#F1F5F9]">
                        <div class="flex items-center gap-2 mb-4 px-1">
                            <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                            <span class="text-[14px] font-[900] text-[#1E293B]">参考图</span>
                            <span class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-wider ml-auto"
                                >Reference Image</span
                            >
                        </div>
                        <div class="mt-2">
                            <image-upload
                                v-model="formData"
                                :template-video-url="`${getApiUrl()}/static/videos/reference-image-tips-char.mp4`" />
                        </div>
                    </div>
                    <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9]">
                        <div class="flex items-center gap-2 mb-4 px-1">
                            <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                            <span class="text-[14px] font-[900] text-[#1E293B]">画面比例</span>
                            <span class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-wider ml-auto"
                                >Aspect Ratio</span
                            >
                        </div>

                        <resolution-select :model="formData.model" @update:resolution="handleResolutionChange" />
                    </div>
                    <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-6 sticky bottom-2 z-20">
                        <number-select v-model="formData.img_count" />
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <case-btn @on-click="openCaseImage" />
    <inspiration-image ref="inspirationImageRef" @use-assemble="handleUseAssemble" />
    <case-image-v1 ref="caseImageRef" type="goods" @choose="handleChooseCase" />
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { getApiUrl } from "@/utils/env";
import { ModelEnum, drawTypeEnumMap, DrawTypeEnum } from "../_enums";
import { CopywritingTypeEnum } from "../../_enums/chatEnum";
import ImageUpload from "./image-upload.vue";
import ResolutionSelect from "./resolution-select.vue";
import InspirationImage from "./inspiration-image.vue";
import CaseImageV1 from "./case-image-v1.vue";
import CaseBtn from "./case-btn/index.vue";
import NumberSelect from "./number-select/index.vue";

const emit = defineEmits<{
    (event: "update:formData", value: any): void;
    (event: "generatePrompt", value: { promptId: number; prompt: string }): void;
}>();

enum FormTypeEnum {
    TXT2IMAGE = drawTypeEnumMap[DrawTypeEnum.TXT2IMAGE],
    IMAGE2IMAGE = drawTypeEnumMap[DrawTypeEnum.IMAGE2IMAGE],
}

const appStore = useAppStore();
const getModelChannel = computed(() => {
    appStore.getHdConfig.channel.forEach((item) => (item.id = parseInt(item.id)));
    if (formData.type == FormTypeEnum.IMAGE2IMAGE) {
        return appStore.getHdConfig.channel.filter((item: any) =>
            [ModelEnum.HIDREAMAI, ModelEnum.SEEDREAM].includes(item.id)
        );
    }
    if (formData.type == FormTypeEnum.TXT2IMAGE) {
        return appStore.getHdConfig.channel.filter((item: any) =>
            [ModelEnum.HIDREAMAI, ModelEnum.GENERAL, ModelEnum.SEEDREAM].includes(item.id)
        );
    }
});

const formData = reactive<any>({
    type: FormTypeEnum.TXT2IMAGE,
    model: "",
    prompt: "",
    use_sr: true,
    width: "",
    height: "",
    resolution: "",
    img_count: 1,
    image: "",
    content: "",
});

const getPromptMaxlength = computed(() => {
    if (formData.model == ModelEnum.HIDREAMAI) {
        return 1000;
    }
    return 2000;
});

// 生成类型 Start

const typeTabs = [
    { label: "文生图", value: FormTypeEnum.TXT2IMAGE },
    { label: "参考生图", value: FormTypeEnum.IMAGE2IMAGE },
];

const handleTypeTabClick = (tab: FormTypeEnum) => {
    formData.type = tab;
    formData.model = getModelChannel.value[0].id;
};

// 生成类型 End

// 生成模型 Start

const handleModelChange = (data: any) => {
    if (formData.model == ModelEnum.GENERAL) {
        formData.img_count = 1;
    }
};

// 生成模型 End

// 分辨率 Start

const handleResolutionChange = (data: any) => {
    formData.resolution = data.label;
    formData.width = data.width;
    formData.height = data.height;
};

// 分辨率 End

// 图片参考 Start

const imageTypeTabs = [
    { label: "通用垫图", id: 1 },
    { label: "角色特征", id: 2 },
    { label: "人物长相", id: 3 },
    { label: "风格转绘", id: 4 },
];

const imageTypeTabActive = ref(imageTypeTabs[0].id);

// 图片参考 End

// 灵感 Start

const inspirationImageRef = shallowRef();

const handleInspiration = async () => {
    inspirationImageRef.value.open();
};

const handleUseAssemble = (prompt: string[]) => {
    formData.prompt = prompt.join(",");
};

// 灵感 End

// 生成提示词 Start
const handleGeneratePrompt = (type: CopywritingTypeEnum) => {
    emit("generatePrompt", { promptId: type, prompt: formData.prompt });
};

const setPrompt = (prompt: string) => {
    formData.prompt = prompt.slice(0, getPromptMaxlength.value);
};

// 生成提示词 End

// 优秀案例 Start

const caseImageRef = shallowRef();

const openCaseImage = () => {
    caseImageRef.value.open();
};

const handleChooseCase = (title: string) => {
    formData.prompt = title;
};

// 优秀案例 End

watch(
    () => getModelChannel.value,
    (value) => {
        if (value.length > 0) {
            formData.model = value[0].id;
        }
    },
    {
        immediate: true,
    }
);

watchEffect(() => {
    emit("update:formData", formData);
});

defineExpose({
    getFormData: () => {
        const { model, type, resolution, prompt, img_count, width, height, use_sr, image } = formData;

        const data: any = {
            model,
            model_name: getModelChannel.value.find((item: any) => item.id == model)?.name,
            type,
            resolution,
            type_name: type == FormTypeEnum.TXT2IMAGE ? "文生图" : "参考生图",
            params: {
                prompt,
            },
        };

        if (type == FormTypeEnum.TXT2IMAGE) {
            if (model == ModelEnum.HIDREAMAI) {
                data.params.aspect_ratio = resolution;
                data.params.img_count = img_count;
            } else if (model == ModelEnum.GENERAL || model == ModelEnum.SEEDREAM) {
                data.params.width = width;
                data.params.height = height;
                data.params.use_sr = `${use_sr}`;
                data.params.model = model;
            }
        } else if (type == FormTypeEnum.IMAGE2IMAGE) {
            if (data.model == ModelEnum.HIDREAMAI) {
                data.params.image = [image];
                data.params.aspect_ratio = resolution;
                data.params.img_count = img_count;
                data.params.negative_prompt = "";
            } else if (data.model == ModelEnum.SEEDREAM) {
                data.params.width = width;
                data.params.height = height;
                data.params.image_url = image;
                data.params.model = model;
            }
        }
        return data;
    },
    validateForm: () => {
        return new Promise((resolve, reject) => {
            if (formData.type == FormTypeEnum.TXT2IMAGE) {
                if (!formData.prompt) {
                    feedback.msgWarning("请输入提示词");
                    reject(false);
                    return;
                }
            }
            if (formData.type == FormTypeEnum.IMAGE2IMAGE) {
                if (!formData.image) {
                    feedback.msgWarning("请上传参考图");
                    reject(false);
                    return;
                } else if (!formData.prompt && formData.model == ModelEnum.SEEDREAM) {
                    feedback.msgWarning("请输入提示词");
                    reject(false);
                    return;
                }
            }
            resolve(true);
        });
    },
    setPrompt,
});
</script>

<style scoped lang="scss">
.ratio-card {
    @apply flex flex-col items-center justify-center p-3 rounded-xl border-2 border-[#F1F5F9] cursor-pointer transition-all;
    &:hover {
        @apply border-[#0065fb]/30 bg-slate-50;
    }
    &.is-active {
        @apply border-[#0065fb] bg-[#F5F7FF] text-primary;
        .ratio-box {
            @apply border-[#0065fb] bg-[#0065fb]/20;
        }
    }
    .ratio-box {
        @apply border-2 border-[#CBD5E1] rounded-[2px] transition-all;
    }
}

.upload-box {
    @apply h-[160px] w-full flex flex-col items-center justify-center relative rounded-[18px] border-2 border-dashed border-br bg-[#FBFDFF] hover:border-[#0065fb] hover:bg-[#F5F7FF] transition-all overflow-hidden;
}

.upload-mask {
    @apply absolute inset-0 bg-[#000000]/20 opacity-0 transition-opacity flex items-center justify-center z-10;
}

:deep(.el-slider) {
    --el-slider-main-bg-color: #4f46e5;
    --el-slider-runway-bg-color: #f1f5f9;
    --el-slider-stop-bg-color: #cbd5e1;
}
</style>
