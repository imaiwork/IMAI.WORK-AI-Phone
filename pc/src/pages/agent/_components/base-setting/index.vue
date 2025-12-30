<template>
    <div class="h-full flex flex-col">
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="px-[30px]">
                    <ElForm :model="formData" :rules="formRules" ref="formRef" label-position="top">
                        <!-- 背景和Logo设置 -->
                        <div
                            class="mt-3 w-full h-[180px] bg-no-repeat bg-cover rounded-tl-[20px] rounded-tr-[20px] flex flex-col justify-center items-center"
                            :style="{ backgroundImage: `url(${formData.bg_image || AgentBg})` }">
                            <div class="mt-4">
                                <agent-logo v-model="formData.image" />
                            </div>
                            <div class="mt-[10px]">
                                <upload :limit="1" show-progress :show-file-list="false" @success="getBgSuccessImage">
                                    <div
                                        class="w-[72px] h-[29px] flex items-center justify-center rounded-[32px] bg-[#00000066] text-white">
                                        更换背景
                                    </div>
                                </upload>
                            </div>
                        </div>
                        <!-- 基础信息表单 -->
                        <div class="flex mt-6 w-full gap-8">
                            <div>
                                <ElFormItem label="智能体名称" prop="name">
                                    <ElInput v-model="formData.name" class="!h-11" placeholder="请输入智能体名称" />
                                </ElFormItem>

                                <ElFormItem label="智能体模型" prop="model_id">
                                    <ElSelect
                                        v-model="formData.model_id"
                                        class="!h-11"
                                        placeholder="请选择智能体模型"
                                        filterable
                                        @change="handleModelChange">
                                        <ElOption
                                            v-for="item in aiModelChannel"
                                            :key="item.id"
                                            :label="item.name"
                                            :value="item.model_id"></ElOption>
                                    </ElSelect>
                                </ElFormItem>
                            </div>
                            <div class="flex-1">
                                <ElFormItem label="相关介绍" prop="intro">
                                    <ElInput
                                        v-model="formData.intro"
                                        type="textarea"
                                        show-word-limit
                                        resize="none"
                                        placeholder="请输入相关介绍 ..."
                                        :maxlength="500"
                                        :rows="6" />
                                </ElFormItem>
                            </div>
                        </div>
                        <ElDivider class="!border-[#0000000d] !mt-2"></ElDivider>
                        <!-- 提示词设置 -->
                        <ElFormItem>
                            <template #label>
                                <div class="flex justify-between">
                                    <div>
                                        <span class="font-bold">提示词</span>
                                        <span class="ml-2 text-[#00000080]"
                                            >角色、背景、职责、工作流程、沟通方式、目的</span
                                        >
                                    </div>
                                    <ElButton link type="primary" @click="handleWriteExample()">一键填入示例</ElButton>
                                </div>
                            </template>
                            <ElInput
                                v-model="formData.roles_prompt"
                                type="textarea"
                                show-word-limit
                                placeholder="请输入相关提示词 ..."
                                :maxlength="100000"
                                :rows="6" />
                        </ElFormItem>
                    </ElForm>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { type FormInstance } from "element-plus";
import { useAppStore } from "@/stores/app";
import { agentExamplePrompt } from "@/config/common";
import AgentBg from "@/assets/images/agent_bg.png";
import AgentLogo from "../agent-logo.vue";
import { Agent } from "../../_enums";

// 定义组件props
const props = withDefaults(
    defineProps<{
        modelValue: Agent;
    }>(),
    {
        modelValue: () => ({} as Agent),
    }
);

// store
const appStore = useAppStore();
const aiModelChannel = computed(() => appStore.getAiModelConfig.channel || []);

// 表单引用和数据模型
const formRef = ref<FormInstance>();
const formData = defineModel<Agent>("modelValue");

// 表单验证规则
const formRules = {
    image: [{ required: true, message: "请上传机器人logo" }],
    name: [{ required: true, message: "请输入机器人名称" }],
    intro: [{ required: true, message: "请输入机器人角色简介" }],
};

/**
 * @description 背景图片上传成功回调
 * @param res - 上传接口返回的数据
 */
const getBgSuccessImage = (res: any) => {
    const { uri } = res.data;
    formData.value.bg_image = uri;
};

/**
 * @description 处理智能体模型变化
 * @param value - 当前选中的模型ID
 */
const handleModelChange = (value?: string) => {
    const selectedModel = aiModelChannel.value.find((item) => item.model_id == value);
    if (selectedModel) {
        formData.value.model_sub_id = selectedModel.model_sub_id;
    } else if (!value && aiModelChannel.value.length > 0) {
        // 如果没有选中值且模型列表不为空，则默认选中第一个
        const defaultModel = aiModelChannel.value[0];
        formData.value.model_id = defaultModel.model_id;
        formData.value.model_sub_id = defaultModel.model_sub_id;
    }
};

/**
 * @description 一键填入示例提示词
 */
const handleWriteExample = () => {
    formData.value.roles_prompt = agentExamplePrompt;
};

// 组件挂载后，处理模型默认值
onMounted(() => {
    handleModelChange(formData.value.model_id as string);
});

// 暴露验证方法，供父组件调用
defineExpose({
    validate: () => {
        return new Promise((resolve, reject) => formRef.value?.validate().then(resolve).catch(reject));
    },
});
</script>

<style scoped></style>
