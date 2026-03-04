<template>
    <div class="h-full flex flex-col bg-[#FFFFFF]">
        <div class="grow min-h-[0]">
            <ElScrollbar>
                <div class="px-[30px] py-[24px]">
                    <div class="mb-[32px]">
                        <div class="flex items-center gap-[10px] mb-[16px]">
                            <div class="w-[4px] h-[16px] bg-[#0065fb] rounded-[full]"></div>
                            <span class="text-[15px] font-[900] text-[#0F172A]">预设模式</span>
                        </div>
                        <div class="flex items-center gap-[16px]">
                            <div
                                v-for="(item, index) in defaultTypes"
                                :key="index"
                                @click="handleChangeType(item.type)"
                                :class="[
                                    'mode-card',
                                    formData.mode_type == item.type ? 'mode-card-active' : 'mode-card-inactive',
                                ]">
                                <Icon :name="item.icon" :size="18" />
                                <span>{{ item.label }}</span>
                            </div>
                        </div>
                    </div>

                    <ElForm :model="formData" label-position="top" class="custom-param-form">
                        <div class="grid grid-cols-[1fr_1fr] gap-x-[40px] gap-y-[4px]">
                            <ElFormItem label="上下文记忆条数" class="col-span-1">
                                <div class="param-control-group">
                                    <div class="flex-1 px-[8px]">
                                        <ElSlider v-model="formData.context_num" :min="0" :max="5" />
                                    </div>
                                    <ElInputNumber
                                        v-model="formData.context_num"
                                        controls-position="right"
                                        :min="0"
                                        :max="5" />
                                </div>
                            </ElFormItem>

                            <ElFormItem label="最大回复长度 (Max Tokens)" class="col-span-1">
                                <div class="param-control-group">
                                    <div class="flex-1 px-[8px]">
                                        <ElSlider v-model="formData.max_tokens" :min="1" :max="getMaxTokens" />
                                    </div>
                                    <ElInputNumber
                                        v-model="formData.max_tokens"
                                        controls-position="right"
                                        :min="1"
                                        :max="getMaxTokens" />
                                </div>
                            </ElFormItem>

                            <ElFormItem label="结果随机性 (Temperature)" class="col-span-1">
                                <div class="param-control-group">
                                    <div class="flex-1 px-[8px]">
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
                                        :step="0.1" />
                                </div>
                            </ElFormItem>

                            <ElFormItem
                                v-if="formData.model_id != ModelIdEnum.CLAUDE_SONNET_4_5"
                                label="核采样 (Top P)"
                                class="col-span-1">
                                <div class="param-control-group">
                                    <div class="flex-1 px-[8px]">
                                        <ElSlider v-model="formData.top_p" :min="0.01" :max="1" :step="0.1" />
                                    </div>
                                    <ElInputNumber
                                        v-model="formData.top_p"
                                        controls-position="right"
                                        :min="0.01"
                                        :max="1"
                                        :step="0.1" />
                                </div>
                            </ElFormItem>

                            <ElFormItem
                                v-if="formData.model_id != ModelIdEnum.DEEPSEEK"
                                label="重复惩罚 (Frequency Penalty)"
                                class="col-span-1">
                                <div class="param-control-group">
                                    <div class="flex-1 px-[8px]">
                                        <ElSlider v-model="formData.frequency_penalty" :min="-2" :max="2" :step="0.1" />
                                    </div>
                                    <ElInputNumber
                                        v-model="formData.frequency_penalty"
                                        controls-position="right"
                                        :min="-2"
                                        :max="2"
                                        :step="0.1" />
                                </div>
                            </ElFormItem>

                            <ElFormItem
                                v-if="formData.model_id != ModelIdEnum.DEEPSEEK"
                                label="存在惩罚 (Presence Penalty)"
                                class="col-span-1">
                                <div class="param-control-group">
                                    <div class="flex-1 px-[8px]">
                                        <ElSlider v-model="formData.presence_penalty" :min="0" :max="1" :step="0.1" />
                                    </div>
                                    <ElInputNumber
                                        v-model="formData.presence_penalty"
                                        controls-position="right"
                                        :min="0"
                                        :max="1"
                                        :step="0.1" />
                                </div>
                            </ElFormItem>
                        </div>

                        <div
                            v-if="formData.model_id != ModelIdEnum.DEEPSEEK"
                            class="mt-[24px] p-[24px] rounded-[20px] bg-slate-50 border-[transparent]">
                            <div class="flex items-center justify-between mb-[20px]">
                                <div>
                                    <div class="text-[14px] font-[900] text-[#0F172A]">对数概率分析 (Logprobs)</div>
                                    <div class="text-xs text-[#94A3B8] mt-[4px]">
                                        显示模型输出词汇的概率分布情况，通常用于学术或精细化调试。
                                    </div>
                                </div>
                                <ElSwitch v-model="formData.logprobs" :active-value="1" :inactive-value="0" />
                            </div>

                            <transition name="el-fade-in">
                                <ElFormItem v-if="formData.logprobs" label="候选词对数概率展示数量">
                                    <div class="param-control-group !bg-[#FFFFFF]">
                                        <div class="flex-1 px-[8px]">
                                            <ElSlider v-model="formData.top_logprobs" :min="0" :max="20" />
                                        </div>
                                        <ElInputNumber
                                            v-model="formData.top_logprobs"
                                            controls-position="right"
                                            :min="0"
                                            :max="20" />
                                    </div>
                                </ElFormItem>
                            </transition>
                        </div>
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
    if (formData.value.model_id == ModelIdEnum.DEEPSEEK) {
        return 2;
    }
    return 1;
});

const getMaxTokens = computed(() => {
    if (formData.value.model_id == ModelIdEnum.DEEPSEEK) {
        return 4096;
    }
    return 10000;
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
.mode-card {
    @apply flex items-center gap-[10px] px-[20px] h-[44px] rounded-[12px] cursor-pointer font-[900] text-[13px] transition-all border-[2px];

    &-active {
        @apply bg-[#F1F6FF] border-primary text-primary shadow-[0_4px_12px_-2px_rgba(0,101,251,0.15)];
    }

    &-inactive {
        @apply bg-[#FFFFFF] border-[#F1F5F9] text-[#64748B] hover:border-[#E2E8F0];
    }
}

.param-control-group {
    @apply flex items-center w-full gap-[16px] bg-slate-50 px-[16px] py-[6px] rounded-[12px] border-[transparent] transition-all;

    &:focus-within {
        @apply bg-[#FFFFFF] shadow-[0_0_0_2px_#0065fb22] border-[#0065fb33];
    }
}
</style>
