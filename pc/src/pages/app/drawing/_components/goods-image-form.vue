<template>
    <div class="h-full">
        <ElScrollbar>
            <div class="p-4">
                <ElForm :model="formData" label-position="top">
                    <ElFormItem>
                        <image-upload
                            v-model:form-data="formData"
                            content="上传商品图"
                            :template-video-url="`${getApiUrl()}/static/videos/reference-image-tips-goods.mp4`" />
                    </ElFormItem>
                    <ElFormItem>
                        <div class="w-full">
                            <div class="flex items-center gap-2 text-[11px] text-white">
                                <div
                                    v-for="(item, index) in generateTypeTabs"
                                    class="h-[26px] rounded-md px-[11px] flex items-center cursor-pointer"
                                    :key="index"
                                    :class="[
                                        generateType === item.id
                                            ? 'bg-primary'
                                            : 'shadow-[0_0_0_1px_#2A2A2A] bg-app-bg-1 ',
                                    ]"
                                    @click="generateType = item.id">
                                    {{ item.name }}
                                </div>
                            </div>
                            <div
                                class="mt-3 rounded-md bg-app-bg-3 border border-app-border-2 p-[6px]"
                                v-if="generateType === GenerateTypeEnum.PLATFORM_CHOICE">
                                <div class="flex items-center gap-2 text-[11px] px-[6px] h-[26px]">
                                    <div
                                        v-for="item in optionsData.template.categories"
                                        class="text-white px-[11px] rounded-md h-[26px] flex items-center cursor-pointer"
                                        :class="
                                            templateCateActive === item.category_en
                                                ? 'shadow-[0_0_0_1px_rgba(255,255,255,0.10)] bg-[#262626] '
                                                : ''
                                        "
                                        @click="templateCateActive = item.category_en">
                                        {{ item.category_zh }}
                                    </div>
                                </div>
                                <div class="mt-[6px] h-[232px] bg-app-bg-3 rounded-md shadow-[0_0_0_1px_#2A2A2A]">
                                    <ElScrollbar>
                                        <div class="grid grid-cols-3 gap-2 p-[6px]">
                                            <div
                                                v-for="(item, index) in getTemplateOptions"
                                                class="h-[90px] rounded-md overflow-hidden cursor-pointer relative group"
                                                :class="{
                                                    'shadow-[0_0_0_3px_var(--color-primary)]':
                                                        formData.template_name_zh == item.name_zh,
                                                }"
                                                :key="index"
                                                @click="handleTemplateClick(item)">
                                                <ElImage :src="item.img" class="w-full h-full" fit="cover" lazy />
                                                <div
                                                    class="absolute bottom-0 left-0 right-0 z-10 bg-[rgba(0,0,0,0.05))]">
                                                    <div class="text-white text-center text-xs font-bold">
                                                        {{ item.name_zh }}
                                                    </div>
                                                </div>
                                                <div
                                                    class="absolute right-0 top-0 w-full h-full invisible group-hover:visible z-[888] flex items-center justify-center gap-2 bg-[var(--el-overlay-color-lighter)]">
                                                    <div
                                                        class="cursor-pointer absolute top-0 right-0"
                                                        @click.stop="previewRefImage(0, item.img)">
                                                        <Icon name="local-icon-fullscreen" :size="32"></Icon>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </ElScrollbar>
                                </div>
                            </div>
                            <div class="mt-3" v-if="generateType === GenerateTypeEnum.CREATIVE_DESCRIPTION">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-white">添加描述</div>
                                </div>
                                <ElInput
                                    v-model="formData.prompt"
                                    type="textarea"
                                    resize="none"
                                    placeholder="添加描述"
                                    :rows="6" />
                                <ElButton
                                    type="primary"
                                    class="!rounded-full !h-[50px] w-full mt-3"
                                    :disabled="!formData.prompt"
                                    @click="handleGeneratePrompt(CopywritingTypeEnum.AI_GOODS_IMAGE)">
                                    生成提示词
                                </ElButton>
                            </div>
                        </div>
                    </ElFormItem>
                    <ElFormItem label="生成风格">
                        <div class="w-full">
                            <div class="flex items-center gap-2 text-[11px] text-white">
                                <div
                                    v-for="(item, index) in styleOptions"
                                    class="h-[26px] rounded-md px-[11px] flex items-center cursor-pointer"
                                    :key="index"
                                    :class="[
                                        styleKey === item.key
                                            ? 'bg-primary'
                                            : 'shadow-[0_0_0_1px_#2A2A2A] bg-app-bg-1 ',
                                    ]"
                                    @click="styleKey = item.key">
                                    {{ item.label }}
                                </div>
                            </div>
                        </div>
                    </ElFormItem>
                    <ElFormItem>
                        <template #label>
                            <div class="flex items-center gap-2 text-white">
                                <span>生成规格</span>
                            </div>
                        </template>
                        <div class="w-full flex gap-2">
                            <ElSelect
                                v-model="currResolution"
                                popper-class="dark-select-popper"
                                class="!w-[120px]"
                                :show-arrow="false"
                                @change="handleResolutionChange">
                                <ElOption
                                    v-for="(item, index) in resolutionOptions"
                                    :key="index"
                                    :label="item.label"
                                    :value="item.label"></ElOption>
                            </ElSelect>
                            <div class="flex-1 flex items-center">
                                <div
                                    class="h-11 flex-1 flex items-center gap-x-2 bg-app-bg-3 border border-app-border-2 px-3 rounded-lg">
                                    <span class="text-white">宽</span>
                                    <span class="text-[#ffffff80]">{{ getResolutionSize.width }}</span>
                                </div>
                                <div>x</div>
                                <div
                                    class="h-11 flex-1 flex items-center gap-x-2 bg-app-bg-3 border border-app-border-2 px-3 rounded-lg">
                                    <span class="text-white">高</span>
                                    <span class="text-[#ffffff80]">{{ getResolutionSize.height }}</span>
                                </div>
                            </div>
                        </div>
                    </ElFormItem>
                    <ElFormItem label="生成张数">
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
    <div class="fixed right-4 top-[93px] z-[22]">
        <ElButton type="primary" class="!rounded-full w-[100px] !h-10" @click="openGoodsCaseImage"> 优秀案例 </ElButton>
    </div>
    <ElImageViewer
        v-if="showPreview"
        :initial-index="previewIndex"
        :url-list="previewUrl"
        @close="showPreview = false"></ElImageViewer>
    <case-image-v2 ref="caseImageRef" type="goods" @choose="handleChooseCase" />
</template>

<script setup lang="ts">
import { getApiUrl } from "@/utils/env";
import { getTemplateList } from "@/api/drawing";
import { goodsResolutionOptions as resolutionOptions } from "../_enums";
import { CopywritingTypeEnum } from "../../_enums/chatEnum";
import ImageUpload from "./image-upload.vue";
import CaseImageV2 from "./case-image-v2.vue";
const emit = defineEmits<{
    (event: "update:formData", value: any): void;
    (event: "generatePrompt", value: { promptId: number; prompt: string }): void;
}>();

enum GenerateTypeEnum {
    PLATFORM_CHOICE = 1,
    CREATIVE_DESCRIPTION = 2,
}
const formData = reactive({
    model: "",
    image: "",
    prompt: "",
    img_count: 1,
    resolution: resolutionOptions[0].value,
    template_category: "",
    template_name: "",
    template_name_zh: "",
});

// 生成类型 Start

const generateTypeTabs = [
    { name: "平台优选", id: GenerateTypeEnum.PLATFORM_CHOICE },
    { name: "创意描述", id: GenerateTypeEnum.CREATIVE_DESCRIPTION },
];
const generateType = ref(GenerateTypeEnum.PLATFORM_CHOICE);

// 生成类型 End

// 模版 Start

const templateCateActive = ref();

const { optionsData } = useDictOptions<{
    template: {
        templates: {
            name_en: string;
            img: string;
            category_en: string;
            name_zh: string;
        }[];
        categories: {
            category_en: string;
            category_zh: string;
        }[];
    };
}>({
    template: {
        api: getTemplateList,
        transformData: (data) => {
            const { templates, categories } = data.result;
            if (categories.length > 0) {
                templateCateActive.value = categories[0].category_en;
            }

            return { templates, categories };
        },
    },
});

const getTemplateOptions = computed(() => {
    const { templates } = optionsData.template;
    if (templates && templates.length > 0) {
        formData.template_name_zh = templates[0].name_zh;
        formData.template_category = templates[0].category_en;
        formData.template_name = templates[0].name_en;
        return templates.filter((item) => item.category_en === templateCateActive.value);
    }
    return [];
});

const handleTemplateClick = (item: any) => {
    formData.template_category = item.category_en;
    formData.template_name = item.name_en;
    formData.template_name_zh = item.name_zh;
};

// 模版 End

// 预览 Start

const showPreview = ref(false);
const previewIndex = ref(0);
const previewUrl = ref<any[]>([]);
const previewRefImage = (index: number, url?: string) => {
    showPreview.value = true;
    if (url) {
        previewIndex.value = 0;
        previewUrl.value = [url];
    }
};

// 预览 End

// 生成提示词 Start
const handleGeneratePrompt = (type: CopywritingTypeEnum) => {
    emit("generatePrompt", { promptId: type, prompt: formData.prompt });
};

const setPrompt = (prompt: string) => {
    formData.prompt = prompt;
};

// 生成提示词 End

// 分辨率 Start

const currResolution = ref(resolutionOptions[0].label);

const getResolutionSize = computed(() => {
    const [width, height] = currResolution.value.split("*");
    return {
        width: width,
        height: height,
    };
});

const handleResolutionChange = (value: any) => {
    formData.resolution = resolutionOptions.find((item) => item.label === value)?.value;
};

// 分辨率 End

// 优秀案例 Start

const caseImageRef = ref();
const openGoodsCaseImage = () => {
    caseImageRef.value.open();
};

const handleChooseCase = (data: any) => {
    const { images, text } = data;
    formData.image = images[0];
    formData.prompt = text;
    formData.template_category = "";
    formData.template_name = "";
    formData.template_name_zh = "";
    generateType.value = GenerateTypeEnum.CREATIVE_DESCRIPTION;
};

// 优秀案例 End

// 生成风格 Start

enum StyleEnum {
    BRIEF = "amozon",
    CLASSIC = "default",
}

const styleKey = ref(StyleEnum.BRIEF);

const styleOptions = [
    { label: "简介风格", key: StyleEnum.BRIEF },
    { label: "经典风格", key: StyleEnum.CLASSIC },
];

// 生成风格 End

watchEffect(() => {
    emit("update:formData", formData);
});

defineExpose({
    getFormData: () => {
        return {
            params: {
                image: formData.image,
                ref_image: [],
                img_count: formData.img_count,
                prompt: formData.prompt,
                custom_template: "false",
                resolution: formData.resolution,
                template_category: formData.template_category,
                template_name: formData.template_name,
                template_name_zh: formData.template_name_zh,
                style: styleKey.value,
            },
            type_name: generateTypeTabs.find((item) => item.id === generateType.value)?.name,
            style_name: styleOptions.find((item) => item.key === styleKey.value)?.label,
        };
    },
    validateForm: () => {
        return new Promise((resolve, reject) => {
            if (!formData.image) {
                feedback.msgWarning("请上传商品图");
                reject(false);
                return;
            }
            if (generateType.value === GenerateTypeEnum.CREATIVE_DESCRIPTION) {
                if (!formData.prompt) {
                    feedback.msgWarning("请添加描述");
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
