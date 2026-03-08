<template>
    <div class="h-full flex flex-col bg-[#FFFFFF]">
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="px-[30px] pb-[40px]">
                    <ElForm
                        :model="formData"
                        :rules="formRules"
                        ref="formRef"
                        label-position="top"
                        class="custom-agent-form">
                        <div
                            class="mt-[12px] w-full h-[200px] bg-no-repeat bg-cover rounded-[24px] flex flex-col justify-center items-center relative overflow-hidden group transition-all"
                            :style="{ backgroundImage: `url(${formData.bg_image || AgentBg})` }">
                            <div
                                class="absolute inset-[0] bg-[rgba(0,0,0,0.2)] group-hover:bg-[rgba(0,0,0,0.3)] transition-all border-[transparent]"></div>

                            <div class="relative z-[10] flex flex-col items-center">
                                <agent-logo v-model="formData.image" />
                                <div class="mt-4">
                                    <upload :limit="1" @success="getBgSuccessImage">
                                        <div class="glass-action-btn">
                                            <Icon name="el-icon-Picture" />
                                            <span class="ml-1">更换背景封面</span>
                                        </div>
                                    </upload>
                                </div>
                            </div>
                        </div>

                        <div class="flex mt-[32px] w-full gap-[32px]">
                            <div class="w-[320px] flex-shrink-0 space-y-[12px]">
                                <ElFormItem label="智能体名称" prop="name">
                                    <ElInput
                                        v-model="formData.name"
                                        class="custom-input"
                                        placeholder="为你的 AI 起个名字" />
                                </ElFormItem>

                                <ElFormItem label="智能体模型" prop="model_id">
                                    <ElSelect
                                        v-model="formData.model_id"
                                        class="custom-select"
                                        placeholder="选择底层模型架构"
                                        filterable
                                        :show-arrow="false"
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
                                        class="custom-textarea w-full"
                                        type="textarea"
                                        show-word-limit
                                        resize="none"
                                        placeholder="简单的描述一下这个智能体的核心能力或定位..."
                                        :maxlength="500"
                                        :rows="5" />
                                </ElFormItem>
                            </div>
                        </div>

                        <div class="h-[1px] bg-[#F1F5F9] my-[24px] border-[transparent] w-full"></div>

                        <div class="prompt-section">
                            <ElFormItem prop="roles_prompt">
                                <template #label>
                                    <div class="flex items-center justify-between w-full mb-[8px]">
                                        <div class="flex items-center gap-[8px]">
                                            <span class="text-[15px] font-[900] text-[#0F172A]"
                                                >提示词 (System Prompt)</span
                                            >
                                        </div>
                                        <ElButton type="primary" link @click="handleWriteExample()">
                                            <Icon name="el-icon-MagicStick" />
                                            <span class="ml-1">一键填入标准示例</span>
                                        </ElButton>
                                    </div>
                                </template>
                                <div class="w-full">
                                    <ElInput
                                        v-model="formData.roles_prompt"
                                        type="textarea"
                                        show-word-limit
                                        placeholder="请输入详细的提示词..."
                                        :maxlength="100000"
                                        :rows="12" />
                                </div>
                            </ElFormItem>
                        </div>
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

<style scoped lang="scss">
.avatar-container {
    @apply bg-[#FFFFFF] p-[4px] rounded-[20px] border-[4px] border-[rgba(255,255,255,0.4)];
}

.glass-action-btn {
    @apply flex items-center justify-center h-[32px] px-[16px] rounded-[32px] bg-[rgba(0,0,0,0.3)] backdrop-blur-[8px] border-[1px] border-[rgba(255,255,255,0.2)] text-[#FFFFFF] text-xs font-[900] cursor-pointer transition-all;

    &:hover {
        @apply bg-[rgba(0,0,0,0.5)];
    }
}
</style>
