<template>
    <div class="h-full bg-slate-50">
        <ElScrollbar>
            <div class="p-4 space-y-3">
                <div class="bg-white rounded-[24px] p-2 border border-[#F1F5F9]">
                    <image-upload
                        v-model="formData"
                        content="上传商品图片"
                        :template-video-url="`${getApiUrl()}/static/videos/reference-image-tips-goods.mp4`" />
                </div>

                <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-3">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 px-1">
                            <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                            <span class="text-[14px] font-[900] text-[#1E293B]">生成模式</span>
                        </div>
                        <div class="flex p-1 bg-[#F1F5F9] rounded-[14px] w-fit">
                            <div
                                v-for="item in generateTypeTabs"
                                :key="item.id"
                                @click="generateType = item.id"
                                class="px-6 py-1.5 rounded-[10px] text-xs font-black cursor-pointer transition-all duration-300"
                                :class="
                                    generateType === item.id
                                        ? 'bg-white text-primary'
                                        : 'text-[#94A3B8] hover:text-[#64748B]'
                                ">
                                {{ item.name }}
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="generateType === GenerateTypeEnum.PLATFORM_CHOICE"
                        class="animate-in fade-in slide-in-from-top-2">
                        <div class="bg-slate-50 rounded-[20px] py-2 border border-[#F1F5F9] space-y-4">
                            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar px-2">
                                <div
                                    v-for="item in optionsData.template.categories"
                                    class="px-4 py-1.5 rounded-lg text-[11px] font-black cursor-pointer transition-all whitespace-nowrap"
                                    :class="
                                        templateCateActive === item.category_en
                                            ? 'bg-primary text-white  shadow-[#0065fb]/20'
                                            : 'bg-white text-[#94A3B8] border border-br'
                                    "
                                    @click="templateCateActive = item.category_en">
                                    {{ item.category_zh }}
                                </div>
                            </div>

                            <div class="h-[320px] mt-2">
                                <ElScrollbar>
                                    <div class="grid grid-cols-3 gap-3 px-2">
                                        <div
                                            v-for="(item, index) in getTemplateOptions"
                                            class="template-card group"
                                            :class="{ 'is-active': formData.template_name_zh == item.name_zh }"
                                            :key="index"
                                            @click="handleTemplateClick(item)">
                                            <div
                                                class="relative aspect-square rounded-xl overflow-hidden border-2 border-transparent transition-all">
                                                <ElImage :src="item.img" class="w-full h-full" fit="cover" lazy />
                                                <div
                                                    class="active-badge"
                                                    v-if="formData.template_name_zh == item.name_zh">
                                                    <Icon name="el-icon-Check" :size="10" color="white"></Icon>
                                                </div>
                                                <div
                                                    class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <div
                                                        @click.stop="previewRefImage(0, item.img)"
                                                        class="w-8 h-8 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white">
                                                        <Icon name="el-icon-ZoomIn" :size="16"></Icon>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="mt-2 text-center text-[10px] font-black text-[#64748B] group-hover:text-primary truncate">
                                                {{ item.name_zh }}
                                            </div>
                                        </div>
                                    </div>
                                </ElScrollbar>
                            </div>
                        </div>
                    </div>

                    <div v-else class="animate-in fade-in slide-in-from-top-2 space-y-4">
                        <div class="relative group">
                            <ElInput
                                v-model="formData.prompt"
                                type="textarea"
                                :rows="5"
                                resize="none"
                                placeholder="描述背景、光影、材质..."
                                class="custom-textarea"></ElInput>
                            <div class="absolute bottom-3 right-3">
                                <div class="tool-btn" @click="handleGeneratePrompt(CopywritingTypeEnum.AI_GOODS_IMAGE)">
                                    <Icon name="el-icon-MagicStick" :size="14"></Icon>
                                    <span class="text-[11px] font-medium ml-1">随机灵感</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-6">
                    <div>
                        <div class="text-xs font-black text-[#94A3B8] mb-4 uppercase tracking-widest px-1">风格</div>
                        <div class="flex items-center gap-2">
                            <div
                                v-for="item in styleOptions"
                                class="style-capsule"
                                :class="{ 'is-active': styleKey === item.key }"
                                @click="styleKey = item.key">
                                {{ item.label }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-black text-[#94A3B8] mb-4 uppercase tracking-widest px-1">分辨率</div>
                        <div class="flex gap-2">
                            <ElSelect
                                v-model="currResolution"
                                class="!w-[120px] custom-select"
                                popper-class="custom-select-popper"
                                :show-arrow="false"
                                @change="handleResolutionChange">
                                <ElOption
                                    v-for="item in resolutionOptions"
                                    :key="item.label"
                                    :label="item.label"
                                    :value="item.label" />
                            </ElSelect>
                            <div
                                class="flex-1 flex items-center gap-2 px-4 h-11 bg-slate-50 border border-[#F1F5F9] rounded-xl text-xs font-medium text-[#64748B]">
                                <span class="opacity-40">W</span> {{ getResolutionSize.width }}
                                <span class="mx-1 opacity-20">|</span>
                                <span class="opacity-40">H</span> {{ getResolutionSize.height }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-6 sticky bottom-2 z-20">
                    <number-select v-model="formData.img_count" />
                </div>
            </div>
        </ElScrollbar>
    </div>

    <case-btn @on-click="openGoodsCaseImage" />

    <ElImageViewer
        v-if="showPreview"
        :initial-index="previewIndex"
        :url-list="previewUrl"
        @close="showPreview = false" />
    <case-image-v2 ref="caseImageRef" type="goods" @choose="handleChooseCase" />
</template>

<script setup lang="ts">
import { getApiUrl } from "@/utils/env";
import { getTemplateList } from "@/api/drawing";
import { goodsResolutionOptions as resolutionOptions } from "../_enums";
import { CopywritingTypeEnum } from "../../_enums/chatEnum";
import ImageUpload from "./image-upload.vue";
import CaseImageV2 from "./case-image-v2.vue";
import CaseBtn from "./case-btn/index.vue";
import NumberSelect from "./number-select/index.vue";

const emit = defineEmits(["update:formData", "generatePrompt"]);

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

const generateTypeTabs = [
    { name: "场景预设", id: GenerateTypeEnum.PLATFORM_CHOICE },
    { name: "自由描述", id: GenerateTypeEnum.CREATIVE_DESCRIPTION },
];
const generateType = ref(GenerateTypeEnum.PLATFORM_CHOICE);

// 模版逻辑 Start
const templateCateActive = ref();
const { optionsData } = useDictOptions({
    template: {
        api: getTemplateList,
        transformData: (data) => {
            const { templates, categories } = data.result;
            if (categories.length > 0) templateCateActive.value = categories[0].category_en;
            return { templates, categories };
        },
    },
});

const getTemplateOptions = computed(() => {
    const { templates } = optionsData.template;
    if (templates?.length) {
        return templates.filter((item) => item.category_en === templateCateActive.value);
    }
    return [];
});

const handleTemplateClick = (item: any) => {
    formData.template_category = item.category_en;
    formData.template_name = item.name_en;
    formData.template_name_zh = item.name_zh;
};

// 预览 Start
const showPreview = ref(false);
const previewIndex = ref(0);
const previewUrl = ref<any[]>([]);
const previewRefImage = (index: number, url?: string) => {
    showPreview.value = true;
    previewIndex.value = 0;
    previewUrl.value = [url];
};

const handleGeneratePrompt = (type: CopywritingTypeEnum) => {
    emit("generatePrompt", { promptId: type, prompt: formData.prompt });
};

const setPrompt = (prompt: string) => {
    formData.prompt = prompt;
};

// 分辨率 Start
const currResolution = ref(resolutionOptions[0].label);
const getResolutionSize = computed(() => {
    const [width, height] = currResolution.value.split("*");
    return { width, height };
});
const handleResolutionChange = (value: any) => {
    formData.resolution = resolutionOptions.find((item) => item.label === value)?.value;
};

// 优秀案例
const caseImageRef = ref();
const openGoodsCaseImage = () => {
    caseImageRef.value.open();
};
const handleChooseCase = (data: any) => {
    formData.image = data.images[0];
    formData.prompt = data.text;
    generateType.value = GenerateTypeEnum.CREATIVE_DESCRIPTION;
};

// 风格 Start
enum StyleEnum {
    BRIEF = "amozon",
    CLASSIC = "default",
}
const styleKey = ref(StyleEnum.BRIEF);
const styleOptions = [
    { label: "简介风格", key: StyleEnum.BRIEF },
    { label: "经典风格", key: StyleEnum.CLASSIC },
];

watchEffect(() => {
    emit("update:formData", formData);
});

defineExpose({
    getFormData: () => {
        if (generateType.value === GenerateTypeEnum.PLATFORM_CHOICE) {
            formData.prompt = "";
        }

        return {
            params: { ...formData, custom_template: "false", style: styleKey.value },
            type_name: generateTypeTabs.find((item) => item.id === generateType.value)?.name,
            style_name: styleOptions.find((item) => item.key === styleKey.value)?.label,
        };
    },
    validateForm: () => {
        return new Promise((resolve, reject) => {
            if (!formData.image) return reject(feedback.msgWarning("请上传商品图"));
            if (generateType.value === GenerateTypeEnum.PLATFORM_CHOICE && !formData.template_name)
                return reject(feedback.msgWarning("请选择场景预设"));
            if (generateType.value === GenerateTypeEnum.CREATIVE_DESCRIPTION && !formData.prompt)
                return reject(feedback.msgWarning("请添加描述"));
            resolve(true);
        });
    },
    setPrompt,
});
</script>

<style scoped lang="scss">
.template-card {
    @apply cursor-pointer;
    .aspect-square {
        @apply border-2 border-[transparent];
    }
    &.is-active .aspect-square {
        @apply border-[#0065fb] shadow-light shadow-[#0065fb]/20;
    }
}

.active-badge {
    @apply absolute top-1 right-1 w-4 h-4 bg-primary rounded-full flex items-center justify-center border border-white z-10;
}

.style-capsule {
    @apply px-4 py-1.5 rounded-full bg-white border border-[#F1F5F9] text-[11px] font-black text-[#94A3B8] cursor-pointer transition-all;
    &.is-active {
        @apply bg-primary text-white border-[#0065fb] shadow-light shadow-[#0065fb]/20;
    }
}

.tool-btn {
    @apply flex items-center px-2.5 py-1 rounded-lg bg-slate-50 text-[#94A3B8] hover:text-primary hover:bg-[#F5F7FF] transition-all cursor-pointer;
}
</style>
