<template>
    <div class="h-full">
        <ElScrollbar>
            <div class="p-4">
                <ElForm :model="formData" label-position="top">
                    <ElFormItem>
                        <template #label>
                            <div class="flex justify-between items-center">
                                <span>生成模型</span>
                                <ElButton link class="!text-[#ffffff80]" @click="fillExample">填入示例</ElButton>
                            </div>
                        </template>
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
                    <ElFormItem label="海报类型">
                        <ElInput
                            v-model="formData.poster_type"
                            placeholder="VLOG视频封面"
                            maxlength="20"
                            show-word-limit />
                    </ElFormItem>
                    <ElFormItem label="海报配色">
                        <ElInput
                            v-model="formData.poster_color"
                            placeholder="马卡龙配色"
                            maxlength="20"
                            show-word-limit />
                    </ElFormItem>
                    <ElFormItem label="海报主标题">
                        <ElInput
                            v-model="formData.poster_title"
                            placeholder="威海旅游vlog"
                            maxlength="20"
                            show-word-limit />
                    </ElFormItem>
                    <ElFormItem label="海报副标题">
                        <ElInput
                            v-model="formData.poster_subtitle"
                            placeholder="特种兵一日游 被低估的旅游城市"
                            maxlength="20"
                            show-word-limit />
                    </ElFormItem>
                    <ElFormItem label="海报主题描述">
                        <ElInput
                            v-model="formData.poster_description"
                            type="textarea"
                            resize="none"
                            :autosize="{
                                minRows: 4,
                                maxRows: 10,
                            }"
                            placeholder="是一个穿着短裙、梳双马尾的少女，人物白色描边"
                            maxlength="500"
                            show-word-limit />
                    </ElFormItem>
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
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import ResolutionSelect from "./resolution-select.vue";
import { ModelEnum } from "../_enums";

const emit = defineEmits<{
    (event: "update:formData", value: any): void;
    (event: "generatePrompt", value: { promptId: number; prompt: string }): void;
}>();

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

// 填入示例 Start

const fillExample = () => {
    formData.poster_type = "VLOG视频封面";
    formData.poster_color = "马卡龙配色";
    formData.poster_title = "威海旅游vlog";
    formData.poster_subtitle = "特种兵一日游 被低估的旅游城市";
    formData.poster_description = "是一个穿着短裙、梳双马尾的少女，人物白色描边";
};

// 填入示例 End

// 生成模型 Start

const handleModelChange = (data: any) => {
    if (formData.model == ModelEnum.GENERAL) {
        formData.img_count = 1;
    }
};

// 生成模型 End

// 分辨率 Start

const handleResolutionChange = (data: any) => {
    formData.width = data.width;
    formData.height = data.height;
    formData.resolution = data.label;
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
        } else if (model == ModelEnum.GENERAL || model == ModelEnum.SEEDREAM) {
            params = {
                ...params,
                use_sr: `${use_sr}`,
                width,
                height,
            };
        }
        return {
            params,
            model,
            model_name: getModelChannel.value.find((item) => item.id == model)?.name,
        };
    },
    validateForm: () => {
        return new Promise((resolve, reject) => {
            if (!formData.poster_type) {
                feedback.msgWarning("请输入海报类型");
                reject(false);
                return;
            } else if (!formData.poster_color) {
                feedback.msgWarning("请输入海报配色");
                reject(false);
                return;
            } else if (!formData.poster_title) {
                feedback.msgWarning("请输入海报主标题");
                reject(false);
                return;
            } else if (!formData.poster_subtitle) {
                feedback.msgWarning("请输入海报副标题");
                reject(false);
                return;
            } else if (!formData.poster_description) {
                feedback.msgWarning("请输入海报主题描述");
                reject(false);
                return;
            } else {
                resolve(true);
            }
        });
    },
});
</script>
