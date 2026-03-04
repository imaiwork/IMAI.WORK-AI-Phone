<template>
    <div class="h-full bg-slate-50">
        <ElScrollbar>
            <div class="p-4 space-y-3">
                <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-3">
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                            <span class="text-[14px] font-[900] text-[#1E293B]">生成模型</span>
                        </div>
                        <div
                            class="flex items-center gap-1 text-xs font-medium text-primary cursor-pointer hover:opacity-80 transition-opacity"
                            @click="fillExample">
                            <Icon name="el-icon-MagicStick" :size="14"></Icon>
                            <span>填入示例</span>
                        </div>
                    </div>

                    <ElSelect
                        v-model="formData.model"
                        class="w-full custom-select"
                        popper-class="custom-select-popper"
                        placeholder="请选择模型名称"
                        :show-arrow="false"
                        @change="handleModelChange">
                        <ElOption
                            v-for="item in getModelChannel"
                            :label="item.name"
                            :value="item.id"
                            :key="item.id"></ElOption>
                    </ElSelect>
                </div>

                <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-5">
                    <div class="flex items-center gap-2 px-1 mb-2">
                        <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                        <span class="text-[14px] font-[900] text-[#1E293B]">内容配置</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-[#94A3B8] ml-1">海报类型</label>
                            <ElInput v-model="formData.poster_type" placeholder="如：VLOG封面" class="custom-input" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-[#94A3B8] ml-1">配色方案</label>
                            <ElInput v-model="formData.poster_color" placeholder="如：马卡龙色" class="custom-input" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-[#94A3B8] ml-1">海报主标题</label>
                        <ElInput v-model="formData.poster_title" placeholder="请输入主标题" class="custom-input" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-[#94A3B8] ml-1">海报副标题</label>
                        <ElInput v-model="formData.poster_subtitle" placeholder="请输入副标题" class="custom-input" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-[#94A3B8] ml-1">海报主题描述</label>
                        <ElInput
                            v-model="formData.poster_description"
                            type="textarea"
                            resize="none"
                            :rows="4"
                            placeholder="描述海报的具体画面内容，如：人物动作、背景装饰..."
                            class="custom-textarea" />
                    </div>
                </div>

                <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-6">
                    <div class="flex items-center gap-2 px-1">
                        <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                        <span class="text-[14px] font-[900] text-[#1E293B]">输出设置</span>
                    </div>

                    <div
                        v-if="formData.model == ModelEnum.GENERAL"
                        class="p-4 bg-slate-50 rounded-2xl border border-[#F1F5F9] flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-[13px] font-[900] text-[#1E293B]">超分辨率生成</span>
                            <ElTooltip content="开启后将返回双倍清晰度的图像">
                                <Icon name="el-icon-QuestionFilled" :size="14" class="text-[#CBD5E1]"></Icon>
                            </ElTooltip>
                        </div>
                        <ElSwitch v-model="formData.use_sr" style="--el-switch-on-color: #4f46e5" />
                    </div>

                    <resolution-select :model="formData.model" @update:resolution="handleResolutionChange" />
                </div>
                <div
                    v-if="formData.model == ModelEnum.HIDREAMAI"
                    class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] space-y-6 sticky bottom-2 z-20">
                    <number-select v-model="formData.img_count" />
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { ModelEnum } from "../_enums";
import ResolutionSelect from "./resolution-select.vue";
import NumberSelect from "./number-select/index.vue";

const emit = defineEmits(["update:formData", "generatePrompt"]);

const appStore = useAppStore();
const getModelChannel = computed(() => {
    return appStore.getHdConfig?.channel
        .filter((item) => [ModelEnum.GENERAL, ModelEnum.HIDREAMAI, ModelEnum.SEEDREAM].includes(parseInt(item.id)))
        .map((item) => ({
            ...item,
            id: parseInt(item.id),
        }));
});

const formData = reactive<any>({
    model: "",
    poster_type: "",
    poster_color: "",
    poster_title: "",
    poster_subtitle: "",
    poster_description: "",
    use_sr: true,
    resolution: "",
    width: "",
    height: "",
    img_count: 1,
});

const fillExample = () => {
    formData.poster_type = "VLOG视频封面";
    formData.poster_color = "马卡龙配色";
    formData.poster_title = "威海旅游vlog";
    formData.poster_subtitle = "特种兵一日游 被低估的旅游城市";
    formData.poster_description = "是一个穿着短裙、梳双马尾的少女，人物白色描边";
};

const handleModelChange = () => {
    if (formData.model == ModelEnum.GENERAL) formData.img_count = 1;
};

const handleResolutionChange = (data: any) => {
    formData.width = data.width;
    formData.height = data.height;
    formData.resolution = data.label;
};

watch(
    () => getModelChannel.value,
    (value) => {
        if (value.length > 0 && !formData.model) {
            formData.model = value[0].id;
        }
    },
    { immediate: true }
);

watchEffect(() => {
    emit("update:formData", formData);
});

defineExpose({
    getFormData: () => {
        const {
            width,
            height,
            use_sr,
            poster_type,
            poster_color,
            poster_title,
            poster_subtitle,
            poster_description,
            model,
        } = formData;
        let params: any = {
            prompt: "",
            poster_type,
            poster_color,
            poster_title,
            poster_subtitle,
            poster_description,
            model,
        };
        if (model == ModelEnum.HIDREAMAI) {
            params = {
                ...params,
                negative_prompt: "",
                img_count: formData.img_count,
                aspect_ratio: formData.resolution,
            };
        } else {
            params = { ...params, use_sr: `${use_sr}`, width, height };
        }
        return { params, model, model_name: getModelChannel.value.find((item: any) => item.id == model)?.name };
    },
    validateForm: () => {
        return new Promise((resolve, reject) => {
            const fields = [
                { key: "poster_type", label: "海报类型" },
                { key: "poster_color", label: "海报配色" },
                { key: "poster_title", label: "海报主标题" },
                { key: "poster_subtitle", label: "海报副标题" },
                { key: "poster_description", label: "海报主题描述" },
            ];
            for (const field of fields) {
                if (!formData[field.key]) {
                    feedback.msgWarning(`请输入${field.label}`);
                    return reject(false);
                }
            }
            resolve(true);
        });
    },
});
</script>

<style scoped lang="scss">
:deep(.custom-input),
:deep(.custom-select) {
    .el-input__wrapper {
        @apply rounded-xl bg-slate-50 shadow-[none] border border-[#F1F5F9] h-11 transition-all;
        &:hover {
            @apply border-br;
        }
        &.is-focus {
            @apply border-[#0065fb] bg-white shadow-[0_0_0_4px_rgba(79,70,229,0.06)] !important;
        }
    }
}

:deep(.custom-select) {
    @apply h-11;
    .el-input {
        @apply h-11;
    }
}

:deep(input::-webkit-outer-spin-button),
:deep(input::-webkit-inner-spin-button) {
    -webkit-appearance: none;
    margin: 0;
}
</style>
