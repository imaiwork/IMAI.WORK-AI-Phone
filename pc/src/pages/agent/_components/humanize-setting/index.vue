<template>
    <div class="h-full flex flex-col py-6">
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="px-[30px]">
                    <ElDivider content-position="left">参数设置</ElDivider>
                    <div class="flex items-center gap-4 px-5 my-6">
                        <div
                            v-for="(item, index) in defaultTypes"
                            :key="index"
                            class="px-[9px] h-[32px] flex items-center gap-x-2 rounded-lg shadow-[0_0_0_1px_rgba(0,0,0,0.1)] cursor-pointer text-xs font-bold"
                            :class="{ '!border-primary text-primary': formData.mode_type == item.type }"
                            @click="handleChangeType(item.type)">
                            <Icon :name="item.icon" :size="16" />
                            <span>{{ item.label }}</span>
                        </div>
                    </div>
                    <ElForm :model="formData" label-width="100px">
                        <!-- 上下文数 -->
                        <ElFormItem label="上下文数">
                            <div class="flex items-center w-full gap-x-4">
                                <div class="flex-1">
                                    <ElSlider v-model="formData.context_num" :min="0" :max="5" />
                                </div>
                                <ElInputNumber
                                    v-model="formData.context_num"
                                    controls-position="right"
                                    :min="0"
                                    :max="5">
                                    <template #suffix>
                                        <span>条</span>
                                    </template>
                                </ElInputNumber>
                            </div>
                        </ElFormItem>
                        <!-- 词汇多样性 -->
                        <ElFormItem label="词汇多样性">
                            <div class="flex items-center w-full gap-x-4">
                                <div class="flex-1">
                                    <ElSlider v-model="formData.top_p" :min="0.01" :max="1" :step="0.1" />
                                </div>
                                <ElInputNumber
                                    v-model="formData.top_p"
                                    controls-position="right"
                                    :min="0"
                                    :max="1"
                                    :step="0.1"></ElInputNumber>
                            </div>
                        </ElFormItem>
                        <!-- 重复词频率 -->
                        <ElFormItem label="重复词频率" v-if="formData.model_id == ModelIdEnum.GPT_4O">
                            <div class="flex items-center w-full gap-x-4">
                                <div class="flex-1">
                                    <ElSlider v-model="formData.frequency_penalty" :min="-2" :max="2" :step="0.1" />
                                </div>
                                <ElInputNumber
                                    v-model="formData.frequency_penalty"
                                    controls-position="right"
                                    :min="-2"
                                    :max="2"
                                    :step="0.1"></ElInputNumber>
                            </div>
                        </ElFormItem>
                        <!-- 特定词重复率 -->
                        <ElFormItem label="特定词重复率" v-if="formData.model_id == ModelIdEnum.GPT_4O">
                            <div class="flex items-center w-full gap-x-4">
                                <div class="flex-1">
                                    <ElSlider v-model="formData.presence_penalty" :min="0" :max="1" :step="0.1" />
                                </div>
                                <ElInputNumber
                                    v-model="formData.presence_penalty"
                                    controls-position="right"
                                    :min="0"
                                    :max="1"
                                    :step="0.1"></ElInputNumber>
                            </div>
                        </ElFormItem>
                        <!-- 结果相似性 -->
                        <ElFormItem label="结果相似性">
                            <div class="flex items-center w-full gap-x-4">
                                <div class="flex-1">
                                    <ElSlider
                                        v-model="formData.temperature"
                                        :min="0.01"
                                        :max="getMaxTemperature"
                                        :step="0.1" />
                                </div>
                                <ElInputNumber
                                    v-model="formData.temperature"
                                    controls-position="right"
                                    :min="0.01"
                                    :max="getMaxTemperature"
                                    :step="0.1"></ElInputNumber>
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
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ModelIdEnum } from "@/enums/appEnums";
import { Agent, ModeTypeEnum } from "../../_enums";

// 使用 defineModel 实现与父组件的双向绑定
const formData = defineModel<Agent>("modelValue");

// 提供默认类型对应的参数值
const defaultTypes = [
    { type: ModeTypeEnum.BALANCE, label: "平衡模式", icon: "local-icon-slider_circle" },
    { type: ModeTypeEnum.PRECISE, label: "精准模式", icon: "local-icon-location" },
    { type: ModeTypeEnum.CREATIVE, label: "创意模式", icon: "local-icon-tool_magic" },
    { type: ModeTypeEnum.CUSTOM, label: "自定义", icon: "local-icon-edit2" },
];

const getMaxTemperature = computed(() => {
    if (formData.value.model_id == ModelIdEnum.GPT_4O) {
        return 2;
    }
    return 1;
});

const handleChangeType = (type: ModeTypeEnum) => {
    formData.value.mode_type = type;
    if (type == ModeTypeEnum.BALANCE) {
        formData.value.top_p = 0.9;
        formData.value.temperature = 0.6;
        formData.value.presence_penalty = 0.2;
        formData.value.frequency_penalty = 0.2;
    }
    if (type == ModeTypeEnum.PRECISE) {
        formData.value.top_p = 0.8;
        formData.value.temperature = 0.3;
        formData.value.presence_penalty = 0;
        formData.value.frequency_penalty = 0;
    }
    if (type == ModeTypeEnum.CREATIVE) {
        formData.value.top_p = 1;
        formData.value.temperature = 0.9;
        formData.value.presence_penalty = 0.5;
        formData.value.frequency_penalty = 0.3;
    }
    if (type == ModeTypeEnum.CUSTOM) {
        formData.value.top_p = formData.value.top_p;
        formData.value.temperature = formData.value.temperature;
        formData.value.presence_penalty = formData.value.presence_penalty;
        formData.value.frequency_penalty = formData.value.frequency_penalty;
    }
};

// 暴露 validate 方法，以符合父组件的统一接口
// 此处没有实际的验证逻辑，仅作为占位符
defineExpose({
    validate: () => {
        // 当前表单没有需要验证的字段，直接返回成功
        return Promise.resolve();
    },
});
</script>

<style scoped lang="scss">
:deep(.el-input-number) {
    .el-input {
        .el-input__inner {
            height: 36px;
        }
    }
}
</style>
