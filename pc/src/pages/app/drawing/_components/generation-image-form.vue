<template>
    <div class="h-full flex flex-col">
        <div class="flex-shrink-0">
            <ElTabs v-model="formData.type" @tab-click="handleTypeTabClick">
                <ElTabPane
                    v-for="(tab, index) in typeTabs"
                    :name="tab.value"
                    :label="tab.label"
                    :key="index"></ElTabPane>
            </ElTabs>
        </div>
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="p-4">
                    <ElForm :model="formData" label-position="top">
                        <ElFormItem label="生成模型">
                            <ElSelect
                                v-model="formData.model"
                                class="!h-11"
                                popper-class="dark-select-popper"
                                placeholder="请选择模型名称"
                                :show-arrow="false"
                                @change="handleModelChange">
                                <ElOption
                                    v-for="item in getModelChannel"
                                    :label="item.name"
                                    :value="item.id"
                                    :key="item.id"></ElOption>
                            </ElSelect>
                        </ElFormItem>
                        <ElFormItem label="提示词" v-if="formData.type === FormTypeEnum.TXT2IMAGE">
                            <ElInput
                                v-model="formData.prompt"
                                type="textarea"
                                show-word-limit
                                resize="none"
                                placeholder="请输入提示词"
                                :autosize="{ minRows: 6, maxRows: 15 }"
                                :maxlength="getPromptMaxlength" />
                            <ElButton
                                type="primary"
                                class="!rounded-full !h-[50px] w-full mt-3"
                                :disabled="!formData.prompt"
                                @click="handleGeneratePrompt(CopywritingTypeEnum.AI_TEXT_TO_IMAGE)">
                                生成提示词
                            </ElButton>
                        </ElFormItem>
                        <template v-if="formData.type === FormTypeEnum.IMAGE2IMAGE">
                            <ElFormItem>
                                <div class="w-full">
                                    <div class="flex items-center gap-2 text-[11px] text-white">
                                        <div
                                            class="h-[26px] rounded-md px-[11px] bg-primary flex items-center cursor-pointer">
                                            单图参考
                                        </div>
                                        <div
                                            class="h-[26px] rounded-md px-[11px] bg-app-bg-3 border border-app-border-2 flex items-center cursor-not-allowed">
                                            多图参考（开发中）
                                        </div>
                                    </div>
                                    <div class="mt-3 rounded-md bg-app-bg-3 border border-app-border-2 p-[6px]">
                                        <div class="flex items-center gap-1 text-[11px] px-[6px]">
                                            <div
                                                v-for="(item, index) in imageTypeTabs"
                                                class="text-white px-[11px] rounded-md h-[26px] flex items-center"
                                                :class="
                                                    imageTypeTabActive === item.id
                                                        ? 'shadow-[0_0_0_1px_rgba(255,255,255,0.10)] bg-app-bg-3 cursor-pointer'
                                                        : 'cursor-not-allowed'
                                                ">
                                                {{ item.label }}
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <image-upload
                                                v-model:form-data="formData"
                                                :template-video-url="`${getApiUrl()}/static/videos/reference-image-tips-char.mp4`" />
                                        </div>
                                    </div>
                                </div>
                            </ElFormItem>
                            <ElFormItem>
                                <template #label>
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            创意描述（{{ formData.model === ModelEnum.SEEDREAM ? "必填" : "选填" }}）
                                        </div>
                                        <ElButton type="primary" size="small" @click="handleInspiration">灵感</ElButton>
                                    </div>
                                </template>
                                <ElInput
                                    v-model="formData.prompt"
                                    type="textarea"
                                    resize="none"
                                    placeholder="选填内容"
                                    :rows="6" />
                                <ElButton
                                    type="primary"
                                    class="!rounded-full !h-[50px] w-full mt-3"
                                    :disabled="!formData.prompt"
                                    @click="handleGeneratePrompt(CopywritingTypeEnum.AI_IMAGE_TO_IMAGE)">
                                    生成提示词
                                </ElButton>
                            </ElFormItem>
                        </template>
                        <ElFormItem v-if="formData.model == ModelEnum.GENERAL">
                            <ElTooltip
                                placement="right"
                                popper-class="w-[228px]"
                                content="开启后可将【生成规格】中的宽高均乘以2返回，如上述宽高均为512和512，此参数关闭出图 512*512 ，此参数打开出图1024 * 1024">
                                <div
                                    class="flex items-center justify-between w-full border border-app-border-2 rounded-lg bg-app-bg-3 px-3 h-11">
                                    <div class="flex items-center gap-2 text-white">
                                        超分辨率生成
                                        <Icon name="local-icon-question" :size="16"></Icon>
                                    </div>
                                    <ElSwitch v-model="formData.use_sr" />
                                </div>
                            </ElTooltip>
                        </ElFormItem>
                        <ElFormItem>
                            <template #label>
                                <div class="flex items-center gap-2 text-white">
                                    <span>生成规格</span>
                                </div>
                            </template>
                            <resolution-select :model="formData.model" @update:resolution="handleResolutionChange" />
                        </ElFormItem>
                        <ElFormItem label="生成张数" v-if="formData.model == ModelEnum.HIDREAMAI">
                            <ElInput
                                v-model="formData.img_count"
                                v-number-input="{ decimal: 0 }"
                                min="1"
                                max="4"
                                type="number"
                                class="!h-11"></ElInput>
                        </ElFormItem>
                    </ElForm>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <div class="fixed right-4 top-[93px] z-[22]">
        <ElButton type="primary" class="!rounded-full w-[100px] !h-10" @click="openCaseImage"> 优秀案例 </ElButton>
    </div>
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

const handleTypeTabClick = (tab: any) => {
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
