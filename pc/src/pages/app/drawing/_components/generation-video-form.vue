<template>
    <div class="h-full flex flex-col bg-slate-50">
        <div class="shrink-0 px-6 h-[72px] flex items-center bg-white border-b border-[#F1F5F9]">
            <div class="flex p-1 rounded-[16px] w-full bg-[#F1F5F9]">
                <div
                    v-for="tab in typeTabs"
                    :key="tab.value"
                    @click="formData.type = tab.value"
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
                <div class="p-3 space-y-3">
                    <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-3">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 px-1">
                                <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                                <span class="text-[14px] font-[900] text-[#1E293B]">生成模型</span>
                            </div>
                            <ElSelect
                                v-model="formData.model"
                                class="w-full custom-select"
                                placeholder="请选择模型"
                                :show-arrow="false">
                                <ElOption
                                    v-for="item in getModelChannel"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id" />
                            </ElSelect>
                        </div>

                        <div
                            v-if="formData.type === GenerateVideoTypeEnum.IMG2VIDEO"
                            class="animate-in fade-in slide-in-from-top-2 space-y-4">
                            <div class="flex p-1 bg-[#F1F5F9] rounded-[12px] w-fit">
                                <div
                                    v-for="item in imageTypeTabs"
                                    :key="item.id"
                                    @click="
                                        imageTypeTabActive = item.id;
                                        formData.image_url = '';
                                    "
                                    class="px-4 py-1.5 rounded-[9px] text-[11px] font-black cursor-pointer transition-all"
                                    :class="
                                        imageTypeTabActive === item.id ? 'bg-white text-primary ' : 'text-[#94A3B8]'
                                    ">
                                    {{ item.label }}
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border-2 border-dashed border-[#F1F5F9] p-2 transition-all hover:border-[#0065fb]/30">
                                <div v-if="imageTypeTabActive === ImageTypeEnum.LOCAL_IMAGE">
                                    <image-upload
                                        v-model="formData"
                                        content="点击上传参考图片"
                                        img-key="image_url"
                                        :template-video-url="`${getApiUrl()}/static/videos/reference-image-tips-video.mp4`" />
                                </div>
                                <div v-else class="p-6 space-y-4">
                                    <div class="text-center space-y-1">
                                        <div class="text-[13px] font-[900] text-[#1E293B]">图片 URL 链接</div>
                                        <div class="text-[11px] text-[#94A3B8]">
                                            输入网络图片地址，AI 将根据图片内容生成视频
                                        </div>
                                    </div>
                                    <ElInput
                                        v-model="formData.image_url"
                                        placeholder="https://..."
                                        class="custom-input">
                                        <template #prefix><Icon name="el-icon-Link" :size="14" /></template>
                                    </ElInput>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-4">
                        <div class="flex items-center justify-between px-1">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                                <span class="text-[14px] font-[900] text-[#1E293B]">创作描述</span>
                            </div>
                            <span class="text-[10px] font-black text-[#CBD5E1] uppercase tracking-tighter">
                                {{ formData.text?.length || 0 }} / {{ maxTextLength }}
                            </span>
                        </div>

                        <div class="relative group">
                            <ElInput
                                v-model="formData.text"
                                type="textarea"
                                resize="none"
                                :rows="8"
                                :maxlength="maxTextLength"
                                placeholder="描述视频中发生的动作、场景、光影细节..."
                                class="custom-textarea" />

                            <div class="absolute bottom-3 right-3">
                                <div
                                    class="tool-btn"
                                    @click="handleGeneratePrompt(CopywritingTypeEnum.AI_GENERATION_VIDEO)">
                                    <Icon name="el-icon-MagicStick" :size="14"></Icon>
                                    <span class="text-[11px] font-medium ml-1">随机灵感</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-[#94A3B8] px-1 italic">
                            提示：详细的动作描述（如：缓慢运镜、人物微笑）能获得更好的生成效果。
                        </p>
                    </div>

                    <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-4">
                        <div class="flex items-center gap-2 px-1">
                            <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                            <span class="text-[14px] font-[900] text-[#1E293B]">生成规格</span>
                        </div>
                        <resolution-select @update:resolution="handleResolutionChange" />
                    </div>
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
import { getApiUrl } from "@/utils/env";

const emit = defineEmits(["update:formData", "generatePrompt"]);

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

// 图片输入源类型 Start
enum ImageTypeEnum {
    LOCAL_IMAGE = 1,
    LINK_IMAGE = 2,
}
const imageTypeTabs = [
    { label: "本地图片", id: ImageTypeEnum.LOCAL_IMAGE },
    { label: "链接地址", id: ImageTypeEnum.LINK_IMAGE },
];
const imageTypeTabActive = ref(imageTypeTabs[0].id);

// 逻辑处理
const handleGeneratePrompt = (type: CopywritingTypeEnum) => {
    emit("generatePrompt", { promptId: type, prompt: formData.text });
};

const setPrompt = (prompt: string) => {
    formData.text = prompt.slice(0, maxTextLength);
};

const handleResolutionChange = (data: any) => {
    formData.aspect_ratio = data.label;
};

watch(
    () => getModelChannel.value,
    (value) => {
        if (value.length > 0 && !formData.model) formData.model = value[0].id;
    },
    { immediate: true }
);

watchEffect(() => {
    emit("update:formData", formData);
});

defineExpose({
    getFormData: () => ({
        params: { image_url: formData.image_url, text: formData.text, aspect_ratio: formData.aspect_ratio },
        type: formData.type,
        type_name: formData.type == GenerateVideoTypeEnum.TXT2VIDEO ? "文生视频" : "图生视频",
        model: formData.model,
        model_name: getModelChannel.value.find((item: any) => item.id == formData.model)?.name,
    }),
    validateForm: () => {
        return new Promise((resolve, reject) => {
            if (!formData.text) return reject(feedback.msgWarning("创作描述不能为空"));
            if (!formData.image_url && formData.type == GenerateVideoTypeEnum.IMG2VIDEO)
                return reject(feedback.msgWarning("请提供参考图片"));
            resolve(true);
        });
    },
    setPrompt,
});
</script>
