<template>
    <ElPopover
        trigger="click"
        width="500px"
        :show-arrow="false"
        placement="top"
        popper-class="!rounded-xl !shadow-[0px_0px_1px_0px_rgba(0,0,0,0.05)]"
        :popper-options="{
            modifiers: [
                {
                    name: 'offset',
                    options: {
                        offset: [250, 10],
                    },
                },
            ],
        }">
        <template #reference>
            <div class="rounded-full border border-[#EBEBEB]">
                <ElTooltip content="参数微调" placement="top">
                    <div class="p-2 rounded-2xl hover:bg-[#00000008] cursor-pointer">
                        <Icon name="local-icon-setting3" :size="14" color="#333333"></Icon>
                    </div>
                </ElTooltip>
            </div>
        </template>
        <div>
            <div class="text-lg font-bold">参数设置</div>
            <div class="mt-4">
                <ElForm :model="formData" label-width="100px">
                    <ElFormItem label="上下文数">
                        <div class="flex items-center w-full gap-x-4">
                            <div class="flex-1">
                                <ElSlider v-model="formData.context_num" :min="0" :max="5" @change="saveConfig" />
                            </div>
                            <ElInputNumber
                                v-model="formData.context_num"
                                controls-position="right"
                                :min="0"
                                :max="5"
                                @change="saveConfig">
                                <template #suffix>
                                    <span>条</span>
                                </template>
                            </ElInputNumber>
                        </div>
                    </ElFormItem>
                    <ElFormItem label="词汇多样性">
                        <div class="flex items-center w-full gap-x-4">
                            <div class="flex-1">
                                <ElSlider v-model="formData.top_p" :min="0" :max="1" :step="0.1" @change="saveConfig" />
                            </div>
                            <ElInputNumber
                                v-model="formData.top_p"
                                controls-position="right"
                                :min="0"
                                :max="1"
                                :step="0.1"
                                @change="saveConfig">
                            </ElInputNumber>
                        </div>
                    </ElFormItem>
                    <ElFormItem label="重复词频率" v-if="formData.model_id == ModelIdEnum.GPT_4O">
                        <div class="flex items-center w-full gap-x-4">
                            <div class="flex-1">
                                <ElSlider
                                    v-model="formData.frequency_penalty"
                                    :min="-2"
                                    :max="2"
                                    :step="0.1"
                                    @change="saveConfig" />
                            </div>
                            <ElInputNumber
                                v-model="formData.frequency_penalty"
                                controls-position="right"
                                :min="-2"
                                :max="2"
                                :step="0.1"
                                @change="saveConfig">
                            </ElInputNumber>
                        </div>
                    </ElFormItem>
                    <ElFormItem label="特定词重复率" v-if="formData.model_id == ModelIdEnum.GPT_4O">
                        <div class="flex items-center w-full gap-x-4">
                            <div class="flex-1">
                                <ElSlider
                                    v-model="formData.presence_penalty"
                                    :min="0"
                                    :max="1"
                                    :step="0.1"
                                    @change="saveConfig" />
                            </div>
                            <ElInputNumber
                                v-model="formData.presence_penalty"
                                controls-position="right"
                                :min="0"
                                :max="1"
                                :step="0.1"
                                @change="saveConfig">
                            </ElInputNumber>
                        </div>
                    </ElFormItem>
                    <ElFormItem label="结果相似性">
                        <div class="flex items-center w-full gap-x-4">
                            <div class="flex-1">
                                <ElSlider
                                    v-model="formData.temperature"
                                    :min="0"
                                    :max="getMaxTemperature"
                                    :step="0.1"
                                    @change="saveConfig" />
                            </div>
                            <ElInputNumber
                                v-model="formData.temperature"
                                controls-position="right"
                                :min="0"
                                :max="getMaxTemperature"
                                :step="0.1"
                                @change="saveConfig">
                            </ElInputNumber>
                        </div>
                    </ElFormItem>
                    <!-- 显示前几个候选词对数概率 -->
                    <ElFormItem label="显示前几个候选词对数概率" v-if="formData.model_id == ModelIdEnum.GPT_4O">
                        <div class="flex items-center w-full gap-x-4">
                            <div class="flex-1">
                                <ElSlider v-model="formData.top_logprobs" :min="0" :max="20" />
                            </div>
                            <ElInputNumber
                                v-model="formData.top_logprobs"
                                controls-position="right"
                                :min="0"
                                :max="20"></ElInputNumber>
                        </div>
                    </ElFormItem>
                    <!-- 显示候选词 -->
                    <ElFormItem label="显示候选词" v-if="formData.model_id == ModelIdEnum.GPT_4O">
                        <ElSwitch v-model="formData.logprobs" :active-value="1" :inactive-value="0" />
                    </ElFormItem>
                </ElForm>
            </div>
        </div>
    </ElPopover>
</template>

<script setup lang="ts">
import { ModelIdEnum } from "@/enums/appEnums";
import { getUserChatConfig, saveUserChatConfig } from "@/api/chat";
import { debounce } from "lodash-es";

const props = withDefaults(
    defineProps<{
        modelId: string | number;
        modelSubId: string | number;
    }>(),
    {
        modelId: "",
        modelSubId: "",
    }
);

const formData = reactive<any>({
    top_p: 0.5, //词汇多样性（0.01-1）
    temperature: 1, //结果相似性（0-2）
    presence_penalty: 0.1, //特定词重复率 (0-1)
    frequency_penalty: 2, //重复词频率(-2到2）
    context_num: 3, //上下文数量（1-5）
    top_logprobs: 10, //显示前几个候选词对数概率(0到20)
    logprobs: 0, //显示候选词 0关闭 1开启
    model_id: "",
    model_sub_id: "",
});

const getMaxTemperature = computed(() => {
    if (formData.model_id == ModelIdEnum.DEEPSEEK_R1) {
        return 2;
    }
    return 1;
});

const getConfig = async () => {
    const data = await getUserChatConfig({
        model_id: props.modelId,
        model_sub_id: props.modelSubId,
    });
    Object.keys(data).forEach((key) => {
        data[key] = Number(data[key]);
    });
    setFormData(
        {
            ...data,
            model_id: props.modelId,
            model_sub_id: props.modelSubId,
        },
        formData
    );
};

const saveConfig = debounce(async () => {
    await saveUserChatConfig(formData);
    getConfig();
}, 300);

watch(
    () => props.modelId,
    (val) => {
        if (val) {
            getConfig();
        }
    },
    {
        immediate: true,
    }
);

defineExpose({
    formData,
});
</script>

<style scoped></style>
