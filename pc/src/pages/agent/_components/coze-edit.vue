<template>
    <popup
        ref="popupRef"
        async
        width="440px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        body-class="coze-edit-body"
        :show-close="false">
        <div class="relative overflow-hidden bg-white rounded-[24px]">
            <button
                class="absolute right-4 top-4 z-20 w-8 h-8 flex items-center justify-center rounded-full bg-black/10 hover:bg-black/20 transition-all text-white backdrop-blur-md"
                @click="close">
                <Icon name="el-icon-Close" :size="18" />
            </button>

            <div class="header-preview" :style="{ backgroundImage: `url(${formData.bg_image || CozeBg})` }">
                <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-white"></div>

                <div class="relative z-10 flex flex-col items-center pt-8">
                    <agent-logo v-model="formData.avatar" />
                    <div class="mt-4">
                        <upload :limit="1" show-progress :show-file-list="false" @success="getBgSuccessImage">
                            <div class="bg-glass-btn group">
                                <span class="mr-1 group-hover:rotate-12 transition-transform leading-[0]">
                                    <Icon name="el-icon-Picture" :size="14" />
                                </span>
                                <span>更换封面背景</span>
                            </div>
                        </upload>
                    </div>
                </div>
            </div>

            <div class="px-8 pb-10 relative z-20 pt-4">
                <div class="text-center mb-8">
                    <h3 class="text-[22px] font-[900] text-[#0F172A] leading-tight">
                        {{ formData.id ? "编辑" : "新增" }}智能助手
                    </h3>
                    <p class="text-xs text-[#94A3B8] font-medium mt-1 uppercase tracking-widest">
                        Configure Your Coze Intelligent Agent
                    </p>
                </div>

                <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top" class="custom-form">
                    <ElFormItem label="智能体名称" prop="name">
                        <ElInput
                            v-model="formData.name"
                            class="custom-input"
                            placeholder="智能体名称，例如：文案专家"
                            maxlength="50" />
                    </ElFormItem>
                    <ElFormItem label="智能体介绍" prop="introduced">
                        <ElInput
                            v-model="formData.introduced"
                            type="textarea"
                            class="custom-textarea"
                            placeholder="介绍一下它的专业领域和功能特点..."
                            resize="none"
                            :rows="3"
                            maxlength="500" />
                    </ElFormItem>

                    <ElFormItem label="Coze 配置 (API ID)" prop="coze_id">
                        <ElInput v-model="formData.coze_id" class="custom-input" placeholder="请输入 Coze Bot ID">
                            <template #prefix>
                                <Icon name="el-icon-Cpu" color="#94A3B8" />
                            </template>
                        </ElInput>
                    </ElFormItem>

                    <div class="grid grid-cols-1 gap-4 py-2">
                        <div
                            class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-[#F1F5F9]">
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-[#475569]">输出方式</span>
                                <span class="text-[11px] text-[#94A3B8]">流式输出体验更佳</span>
                            </div>
                            <ElRadioGroup v-model="formData.stream" class="custom-radio-group">
                                <ElRadioButton :label="1">流式</ElRadioButton>
                                <ElRadioButton :label="0">直接</ElRadioButton>
                            </ElRadioGroup>
                        </div>
                    </div>
                </ElForm>

                <div class="mt-8 flex flex-col items-center">
                    <ElButton
                        type="primary"
                        class="!h-[54px] !w-full !rounded-[18px] !font-black !text-base transition-all active:scale-[0.98]"
                        :loading="isLock"
                        @click="lockFn">
                        保存并同步配置
                    </ElButton>
                    <p class="mt-4 text-[11px] text-[#CBD5E1] font-medium">配置完成后可在前台 Agent 列表查看</p>
                </div>
            </div>
        </div>
    </popup>
</template>
<script setup lang="ts">
import { cozeAgentAdd, cozeAgentUpdate } from "@/api/agent";
import { uploadImage } from "@/api/app";
import { CozeTypeEnum } from "@/pages/agent/_enums";
import { useAppStore } from "@/stores/app";
import CozeBg from "@/assets/images/coze_bg.png";
import AgentLogo from "./agent-logo.vue";

const emit = defineEmits(["close", "success"]);

const appStore = useAppStore();

const getWebSiteLogo = computed(() => {
    const { shop_logo } = appStore.getWebsiteConfig || {};
    return shop_logo;
});

const popupRef = shallowRef();

// 表单数据
const formData = reactive({
    id: "",
    name: "",
    type: CozeTypeEnum.AGENT,
    introduced: "",
    bg_image: "",
    coze_id: "",
    stream: 1,
    avatar: getWebSiteLogo.value,
    permissions: 0,
});

// 表单验证规则
const rules = {
    name: [{ required: true, message: "请输入智能体名称" }],
    introduced: [{ required: true, message: "请输入智能体介绍" }],
    coze_id: [{ required: true, message: "请输入Coze智能体ID" }],
    stream: [{ required: true, message: "请选择输出方式" }],
};
const formRef = shallowRef();

/**
 * @description 背景图片上传成功回调
 * @param res - 上传接口返回的数据
 */
const getBgSuccessImage = (res: any) => {
    const { uri } = res.data;
    formData.bg_image = uri;
};

/**
 * @description 将静态资源图片转换为File对象并上传，以获取可后台存储的URL
 */
const uploadDefaultBackground = async () => {
    try {
        // 将静态导入的背景图转换为File对象
        const file = await urlToFile(CozeBg, "coze_bg.png");

        // 调用上传接口
        const { uri } = await uploadImage({ file });
        formData.bg_image = uri;
    } catch (error) {
        console.error("默认背景上传失败:", error);
    }
};

// 使用 useLockFn 防止重复提交
const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.avatar) {
        feedback.msgError("请上传智能体头像");
        return;
    }
    await formRef.value?.validate();

    // 确保在没有背景图片时，上传默认背景
    if (!formData.bg_image) {
        await uploadDefaultBackground();
    }

    try {
        // 根据是否存在ID判断是新增还是更新
        formData.id ? await cozeAgentUpdate(formData) : await cozeAgentAdd(formData);
        feedback.msgSuccess(`${formData.id ? "编辑" : "添加"}成功`);
        close();
        emit("success");
    } catch (error) {
        feedback.msgError(error as string);
    }
});

// 打开弹窗
const open = async () => {
    popupRef.value.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
};

// 暴露方法给父组件
defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>
<style lang="scss">
.coze-edit-body {
    .header-preview {
        @apply relative w-full h-[220px] bg-no-repeat bg-cover flex flex-col items-center;
    }

    .bg-glass-btn {
        @apply flex items-center justify-center px-4 py-1.5 rounded-full bg-[#ffffff]/20 backdrop-blur-md  border border-[#ffffff]/30 text-white text-xs font-black cursor-pointer transition-all hover:bg-[#ffffff]/40;
    }

    .avatar-ring {
        @apply p-1 bg-white rounded-3xl border-4 border-white shadow-light;
        .agent-logo-wrapper {
            @apply m-0;
        }
    }

    .custom-form {
        .el-form-item__label {
            @apply text-[13px] font-black text-[#0F172A] mb-2 pl-1;
        }
        .modern-input .el-input__wrapper,
        .modern-textarea .el-textarea__inner {
            @apply bg-slate-50 shadow-[none] border-2 border-[transparent] rounded-xl transition-all;
            &:hover {
                @apply border-[#E2E8F0];
            }
            &.is-focus {
                @apply bg-white border-primary;
            }
        }
    }

    .custom-radio-group {
        @apply gap-2;
        .el-radio-button__inner {
            @apply rounded-lg border-none bg-[transparent] text-[#64748B] font-medium text-xs px-4 py-2;
        }
        .el-radio-button__original-radio:checked + .el-radio-button__inner {
            @apply bg-white text-primary;
        }
    }
}
</style>
