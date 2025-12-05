<template>
    <div class="h-full flex flex-col">
        <div class="grow min-h-0">
            <el-scrollbar>
                <div>
                    <el-form :model="formData" :rules="formRules" ref="formRef" label-position="top">
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
                                <el-form-item label="智能体名称" prop="name">
                                    <el-input v-model="formData.name" placeholder="请输入智能体名称" />
                                </el-form-item>

                                <el-form-item label="智能体模型" prop="model_id">
                                    <el-select
                                        v-model="formData.model_id"
                                        placeholder="请选择智能体模型"
                                        filterable
                                        @change="handleModelChange">
                                        <el-option
                                            v-for="item in aiModelChannel"
                                            :key="item.id"
                                            :label="item.name"
                                            :value="item.model_id"></el-option>
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="智能体分类" prop="cate_id">
                                    <el-select
                                        v-model="formData.cate_id"
                                        class="!h-10"
                                        placeholder="请选择智能体分类"
                                        filter-method
                                        remote
                                        reserve-keyword
                                        :remote-method="getCategoryList">
                                        <el-option
                                            v-for="item in categoryList"
                                            :label="item.name"
                                            :value="item.id"
                                            :key="item.id">
                                        </el-option>
                                    </el-select>
                                    <router-link
                                        class="text-primary"
                                        :to="getRoutePath('ai_application.agent/cate')"
                                        target="_blank">
                                        去创建分类
                                    </router-link>
                                </el-form-item>
                            </div>
                            <div class="flex-1">
                                <el-form-item label="相关介绍" prop="intro">
                                    <el-input
                                        v-model="formData.intro"
                                        type="textarea"
                                        show-word-limit
                                        resize="none"
                                        placeholder="请输入相关介绍 ..."
                                        :maxlength="500"
                                        :rows="6" />
                                </el-form-item>
                            </div>
                        </div>
                        <el-divider class="!border-[#0000000d] !mt-2"></el-divider>
                        <!-- 提示词设置 -->
                        <el-form-item>
                            <template #label>
                                <div class="flex justify-between">
                                    <div>
                                        <span class="font-bold">提示词</span>
                                        <span class="ml-2 text-[#00000080]"
                                            >角色、背景、职责、工作流程、沟通方式、目的</span
                                        >
                                    </div>
                                    <el-button link type="primary" @click="handleWriteExample()"
                                        >一键填入示例</el-button
                                    >
                                </div>
                            </template>
                            <el-input
                                v-model="formData.roles_prompt"
                                type="textarea"
                                show-word-limit
                                placeholder="请输入相关提示词 ..."
                                :maxlength="20000"
                                :rows="10" />
                        </el-form-item>
                    </el-form>
                </div>
            </el-scrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getCateList } from "@/api/ai_application/agent/cate";
import { type FormInstance } from "element-plus";
import { getRoutePath } from "@/router";
import useAppStore from "@/stores/modules/app";
import AgentBg from "@/assets/images/agent_bg.png";
import AgentLogo from "../agent-logo.vue";
import type { Agent } from "../enums";
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
const aiModelChannel = computed(() => appStore.config.ai_model?.channel || []);

// 表单引用和数据模型
const formRef = ref<FormInstance>();
const formData = defineModel<Agent>("modelValue", {
    default: () => ({
        image: "",
        name: "",
        intro: "",
        roles_prompt: "",
        model_id: "",
        model_sub_id: "",
        bg_image: "",
        cate_id: "",
    }),
});

// 表单验证规则
const formRules = {
    image: [{ required: true, message: "请上传机器人logo" }],
    name: [{ required: true, message: "请输入机器人名称" }],
    intro: [{ required: true, message: "请输入机器人角色简介" }],
    cate_id: [{ required: true, message: "请选择类目" }],
};

const categoryList = ref<any[]>([]);
const getCategoryList = async (query?: any) => {
    const { lists } = await getCateList({
        name: query,
        type: 1,
        page_size: 25000,
    });
    categoryList.value = lists;
    return lists;
};

/**
 * @description 背景图片上传成功回调
 * @param res - 上传接口返回的数据
 */
const getBgSuccessImage = (res: any) => {
    const { uri } = res;
    formData.value.bg_image = uri;
};

/**
 * @description 处理智能体模型变化
 * @param value - 当前选中的模型ID
 */
const handleModelChange = (value?: string) => {
    const selectedModel = aiModelChannel.value.find((item: any) => item.model_id == value);
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
    formData.value.roles_prompt = `
        # Role: B2B2C AI 数字员工系统售前客服

        ## Profile

        - version: 1.0
        - language: 中文
        - description: 你是一位智能售前客服，服务于B2B2C企业级数字员工系统。你的职责是解答潜在客户在购买前提出的所有问题，包括产品功能、技术架构、部署方式、定价策略、行业适配、数据安全等方面。

        ## Skills

        - 精通数字员工系统的核心模块与功能场景
        - 能提供个性化推荐与行业解决方案
        - 理解B2B2C业务模型及企业客户需求
        - 能识别客户意图并提供精准解答
        - 理解并简洁表述复杂技术术语

        ## Background(可选项):

        本系统作为企业客户引入AI助手的关键入口，售前客服必须快速、准确、专业地回应各种咨询，减少人工客服负担，提高客户转化率。

        ## Goals(可选项):

        - 提升客户首次接触系统的好感度和信任度
        - 降低获客沟通成本，提升线索质量
        - 为销售提供精准客户画像与需求反馈

        ## OutputFormat(可选项):

        针对每个问题，简单输出以下内容：
        1. 问题简述
        2. 回答概要
        3. 可视化资源建议（如文档、视频、截图等）

        ## Rules

        1. 回答必须清晰、专业，避免冗长或模糊表达
        2. 可根据用户角色（企业老板/CTO/HR负责人）调整语言风格
        3. 若问题涉及敏感商业条款，需提示用户联系人工客服
        4. 鼓励引导用户深入了解更多功能模块

        ## Workflows

        1. 接收客户提问（明确提问意图）
        2. 调用产品知识库或FAQ生成专业解答
        3. 根据行业或客户背景个性化回答
        4. 可选推荐文档、案例、视频或预约演示

        ## Init

        您好，请问您想了解什么？
    `;
};

// 组件挂载后，处理模型默认值
onMounted(() => {
    handleModelChange(formData.value.model_id as string);
    getCategoryList();
});

// 暴露验证方法，供父组件调用
defineExpose({
    validate: () => {
        return new Promise((resolve, reject) => formRef.value?.validate().then(resolve).catch(reject));
    },
});
</script>

<style scoped></style>
