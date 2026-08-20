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
                                            v-for="item in modelSelectOptions"
                                            :key="item.id"
                                            :label="item.label"
                                            :value="item.model_id"
                                            :disabled="item.disabled"></ElOption>
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
    },
);

// store
const appStore = useAppStore();
const allowedModels = computed(() => appStore.getAllowedChatModel || []);
const allChatModels = computed(() => appStore.getChatModel || []);

const findModelInChannel = (list: any[], modelId?: string | number, modelSubId?: string | number) => {
    if (!list.length || modelId == null || modelId === "") return null;
    return (
        list.find((item: any) => item.model_id == modelId && item.model_sub_id == modelSubId) ||
        list.find((item: any) => item.model_id == modelId) ||
        list.find((item: any) => item.id == modelId) ||
        null
    );
};

/** 下拉选项：仅会员允许模型可选；已保存但不在会员范围的仅用于回显且禁用 */
const modelSelectOptions = computed(() => {
    const options = allowedModels.value.map((item: any) => ({
        id: item.id,
        model_id: item.model_id,
        model_sub_id: item.model_sub_id,
        label: item.name,
        disabled: false,
    }));
    const currentId = formData.value?.model_id;
    if (!currentId) return options;
    const inAllowed = options.some((item) => item.model_id == currentId);
    if (inAllowed) return options;
    const saved = findModelInChannel(allChatModels.value, currentId, formData.value?.model_sub_id);
    if (!saved) return options;
    // 仅回显：禁用，不可再选
    return [
        {
            id: `saved-${saved.id}`,
            model_id: saved.model_id,
            model_sub_id: saved.model_sub_id,
            label: `${saved.name}（当前会员不可用）`,
            disabled: true,
        },
        ...options,
    ];
});

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
const handleModelChange = (value?: string | number) => {
    // 只允许切到会员可用模型
    const selectedModel = allowedModels.value.find((item: any) => item.model_id == value);
    if (!selectedModel) return;
    formData.value.model_id = selectedModel.model_id;
    formData.value.model_sub_id = selectedModel.model_sub_id;
};

/** 编辑时保留已保存模型；新建时默认选允许列表第一项 */
const syncSavedModel = () => {
    const currentId = formData.value?.model_id;
    if (!currentId) return;
    const matched = findModelInChannel(allChatModels.value, currentId, formData.value?.model_sub_id);
    if (!matched) return;
    formData.value.model_sub_id = matched.model_sub_id;
    if (formData.value.model_id != matched.model_id) {
        formData.value.model_id = matched.model_id;
    }
};

const ensureModelSelection = () => {
    if (formData.value?.id) {
        syncSavedModel();
        return;
    }
    const list = allowedModels.value;
    if (!list.length) return;
    const currentId = formData.value?.model_id;
    const inList = !!currentId && list.some((item) => item.model_id == currentId);
    if (!inList) {
        const defaultModel = list[0];
        formData.value.model_id = defaultModel.model_id;
        formData.value.model_sub_id = defaultModel.model_sub_id;
        return;
    }
    handleModelChange(currentId);
};

/**
 * @description 一键填入示例提示词
 */
const handleWriteExample = () => {
    formData.value.roles_prompt = agentExamplePrompt;
};

// 组件挂载后，处理模型默认值
onMounted(async () => {
    // 每次进入新建/编辑都拉最新会员配额,保证模型列表与等级同步
    await appStore.ensureMemberQuota(true);
    ensureModelSelection();
});

watch([modelSelectOptions, () => formData.value?.model_id, () => formData.value?.id], ensureModelSelection);

/** 当前 model 是否在会员等级允许列表内 */
const isModelAllowed = (modelId?: string | number, modelSubId?: string | number) => {
    if (modelId == null || modelId === "") return false;
    return allowedModels.value.some((item: any) => item.model_id == modelId);
};

// 暴露验证方法，供父组件调用
defineExpose({
    validate: async () => {
        await appStore.ensureMemberQuota(true);
        await formRef.value?.validate();
        if (!isModelAllowed(formData.value?.model_id, formData.value?.model_sub_id)) {
            throw "当前模型不在会员等级可用范围内，请重新选择";
        }
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
