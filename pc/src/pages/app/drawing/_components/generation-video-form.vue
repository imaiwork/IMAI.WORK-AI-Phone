<template>
    <div class="h-full flex flex-col">
        <div class="type-tabs">
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
                                :show-arrow="false">
                                <ElOption
                                    v-for="item in getModelChannel"
                                    :label="item.name"
                                    :value="item.id"
                                    :key="item.id"></ElOption>
                            </ElSelect>
                        </ElFormItem>
                        <ElFormItem v-if="formData.type == GenerateVideoTypeEnum.IMG2VIDEO">
                            <template #label>
                                <div class="flex items-center gap-2 text-[11px] px-[6px]">
                                    <div
                                        v-for="(item, index) in imageTypeTabs"
                                        class="h-[26px] rounded-md px-[11px] flex items-center cursor-pointer"
                                        :key="index"
                                        :class="[
                                            imageTypeTabActive === item.id
                                                ? 'bg-primary'
                                                : 'shadow-[0_0_0_1px_#2A2A2A] bg-app-bg-1 ',
                                        ]"
                                        @click="
                                            imageTypeTabActive = item.id;
                                            formData.image_url = '';
                                        ">
                                        {{ item.label }}
                                    </div>
                                </div>
                            </template>
                            <div class="w-full">
                                <div v-if="imageTypeTabActive === ImageTypeEnum.LOCAL_IMAGE">
                                    <image-upload
                                        v-model:form-data="formData"
                                        content="上传图片"
                                        img-key="image_url"
                                        :template-video-url="`${getApiUrl()}/static/videos/reference-image-tips-video.mp4`" />
                                </div>
                                <div
                                    v-else-if="imageTypeTabActive === ImageTypeEnum.LINK_IMAGE"
                                    class="h-[126px] rounded-md border border-app-border-2 bg-app-bg-3 p-4 flex flex-col items-center justify-center">
                                    <div class="text-[11px] text-white mb-5">输入图片链接开始制作吧</div>
                                    <ElInput v-model="formData.image_url" placeholder="请输入图片链接"> </ElInput>
                                </div>
                            </div>
                        </ElFormItem>
                        <ElFormItem label="创作描述">
                            <ElInput
                                v-model="formData.text"
                                type="textarea"
                                resize="none"
                                placeholder="请输入创作描述"
                                show-word-limit
                                :maxlength="maxTextLength"
                                :rows="11" />
                            <ElButton
                                type="primary"
                                class="!rounded-full !h-[50px] w-full mt-3"
                                :disabled="!formData.text"
                                @click="handleGeneratePrompt(CopywritingTypeEnum.AI_GENERATION_VIDEO)">
                                生成提示词
                            </ElButton>
                        </ElFormItem>
                        <ElFormItem>
                            <template #label>
                                <div class="flex items-center gap-2 text-white">
                                    <span>生成规格</span>
                                </div>
                            </template>
                            <resolution-select @update:resolution="handleResolutionChange" />
                        </ElFormItem>
                    </ElForm>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { GenerateVideoTypeEnum, ModelEnum } from "../_enums";
import { CopywritingTypeEnum } from "../../_enums/chatEnum";
import ResolutionSelect from "./resolution-select.vue";
import ImageUpload from "./image-upload.vue";
const emit = defineEmits<{
    (event: "update:formData", value: any): void;
    (event: "generatePrompt", value: { promptId: number; prompt: string }): void;
}>();

const appStore = useAppStore();
const getModelChannel = computed(() => {
    return appStore.getHdConfig.channel.filter((item: any) =>
        [ModelEnum.GENERAL, ModelEnum.SEEDANCE].includes(parseInt(item.id))
    );
});

const formData = reactive<any>({
    type: GenerateVideoTypeEnum.TXT2VIDEO,
    model: "",
    text: "",
    aspect_ratio: "",
    image_url: undefined,
});

const maxTextLength = 150;

// 生成类型 Start

const typeTabs = [
    { label: "文生视频", value: GenerateVideoTypeEnum.TXT2VIDEO },
    { label: "图生视频", value: GenerateVideoTypeEnum.IMG2VIDEO },
];

const handleTypeTabClick = (value: any) => {
    formData.model = getModelChannel.value[0].id;
};

// 生成类型 End

// 图片类型 Start

enum ImageTypeEnum {
    LOCAL_IMAGE = 1,
    LINK_IMAGE = 2,
}

const imageTypeTabs = [
    { label: "本地图片", id: ImageTypeEnum.LOCAL_IMAGE },
    { label: "链接图片", id: ImageTypeEnum.LINK_IMAGE },
];

const imageTypeTabActive = ref(imageTypeTabs[0].id);

// 图片类型 End

// 生成提示词 Start

const handleGeneratePrompt = (type: CopywritingTypeEnum) => {
    emit("generatePrompt", { promptId: type, prompt: formData.text });
};

const setPrompt = (prompt: string) => {
    formData.text = prompt.slice(0, maxTextLength);
};

// 生成提示词 End

// 分辨率 Start
const handleResolutionChange = (data: any) => {
    formData.aspect_ratio = data.label;
};

// 分辨率 End
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
        const { image_url, text, aspect_ratio } = formData;
        return {
            params: {
                image_url,
                text,
                aspect_ratio,
            },
            type: formData.type,
            type_name: formData.type == GenerateVideoTypeEnum.TXT2VIDEO ? "文生视频" : "图生视频",
            model: formData.model,
            model_name: getModelChannel.value.find((item: any) => item.id == formData.model)?.name,
        };
    },
    validateForm: () => {
        return new Promise((resolve, reject) => {
            if (!formData.text) {
                feedback.msgWarning("创作描述不能为空");
                reject("创作描述不能为空");
            } else if (!formData.image_url && formData.type == GenerateVideoTypeEnum.IMG2VIDEO) {
                feedback.msgWarning("图片不能为空");
                reject("图片不能为空");
            }
            resolve(true);
        });
    },
    setPrompt,
});
</script>
