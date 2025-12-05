<template>
    <div class="h-full flex flex-col py-6">
        <div class="px-[30px]">
            <el-divider content-position="left">参数设置</el-divider>
            <el-form :model="formData" label-width="100px">
                <!-- 上下文数 -->
                <el-form-item label="上下文数">
                    <div class="flex items-center w-full gap-x-4">
                        <div class="flex-1">
                            <el-slider v-model="formData.context_num" :min="0" :max="5" />
                        </div>
                        <el-input-number v-model="formData.context_num" controls-position="right" :min="0" :max="5">
                            <template #suffix>
                                <span>条</span>
                            </template>
                        </el-input-number>
                    </div>
                </el-form-item>
                <!-- 词汇多样性 -->
                <el-form-item label="词汇多样性">
                    <div class="flex items-center w-full gap-x-4">
                        <div class="flex-1">
                            <el-slider v-model="formData.top_p" :min="0" :max="1" :step="0.1" />
                        </div>
                        <el-input-number
                            v-model="formData.top_p"
                            controls-position="right"
                            :min="0"
                            :max="1"
                            :step="0.1"></el-input-number>
                    </div>
                </el-form-item>
                <!-- 重复词频率 -->
                <el-form-item label="重复词频率" v-if="formData.model_id != 4">
                    <div class="flex items-center w-full gap-x-4">
                        <div class="flex-1">
                            <el-slider v-model="formData.frequency_penalty" :min="-2" :max="2" :step="0.1" />
                        </div>
                        <el-input-number
                            v-model="formData.frequency_penalty"
                            controls-position="right"
                            :min="-2"
                            :max="2"
                            :step="0.1"></el-input-number>
                    </div>
                </el-form-item>
                <!-- 特定词重复率 -->
                <el-form-item label="特定词重复率" v-if="formData.model_id != 4">
                    <div class="flex items-center w-full gap-x-4">
                        <div class="flex-1">
                            <el-slider v-model="formData.presence_penalty" :min="0" :max="1" :step="0.1" />
                        </div>
                        <el-input-number
                            v-model="formData.presence_penalty"
                            controls-position="right"
                            :min="0"
                            :max="1"
                            :step="0.1"></el-input-number>
                    </div>
                </el-form-item>
                <!-- 结果相似性 -->
                <el-form-item label="结果相似性">
                    <div class="flex items-center w-full gap-x-4">
                        <div class="flex-1">
                            <el-slider v-model="formData.temperature" :min="0" :max="getMaxTemperature" :step="0.1" />
                        </div>
                        <el-input-number
                            v-model="formData.temperature"
                            controls-position="right"
                            :min="0"
                            :max="getMaxTemperature"
                            :step="0.1"></el-input-number>
                    </div>
                </el-form-item>
                <!-- 显示前几个候选词对数概率 -->
                <el-form-item label="显示前几个候选词对数概率" v-if="formData.model_id == 2">
                    <div class="flex items-center w-full gap-x-4">
                        <div class="flex-1">
                            <el-slider v-model="formData.top_logprobs" :min="0" :max="20" />
                        </div>
                    </div>
                </el-form-item>
                <!-- 显示候选词 -->
                <el-form-item label="显示候选词" v-if="formData.model_id == 2">
                    <el-switch v-model="formData.logprobs" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Agent } from "../enums";

// 使用 defineModel 实现与父组件的双向绑定
const formData = defineModel<Agent>("modelValue", {
    default: () => ({
        model_id: "",
        model_sub_id: "",
        context_num: 0,
        top_p: 0,
        frequency_penalty: 0,
        presence_penalty: 0,
        temperature: 0,
        top_logprobs: 10,
        logprobs: 0,
    }),
});

const getMaxTemperature = computed(() => {
    if (formData.value.model_id == 2) {
        return 2;
    }
    return 1;
});
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
