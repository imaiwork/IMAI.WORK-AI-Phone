<template>
    <popup
        ref="popupRef"
        async
        width="415px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        body-class="coze-edit-body"
        style="padding: 0"
        :show-close="false">
        <div>
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <!-- 顶部背景和Logo -->
            <div class="top" :style="{ backgroundImage: `url(${formData.bg_image || CozeBg})` }">
                <div class="mt-10">
                    <agent-logo v-model="formData.avatar" />
                </div>
                <div class="mt-[25px]">
                    <upload :limit="1" show-progress :show-file-list="false" @success="getBgSuccessImage">
                        <div
                            class="w-[72px] h-[29px] flex items-center justify-center rounded-[32px] bg-[#00000066] text-white">
                            更换背景
                        </div>
                    </upload>
                </div>
            </div>
            <!-- 表单区域 -->
            <div class="p-8">
                <div class="text-lg font-bold">新增Coze智能体</div>
                <div class="text-xs text-[#0000004d] mt-2">快速搭建对话式智能体</div>
                <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top" class="mt-6">
                    <ElFormItem label="智能体名称" prop="name">
                        <ElInput v-model="formData.name" class="!h-10" placeholder="请输入名称" />
                    </ElFormItem>
                    <ElFormItem label="智能体介绍" prop="introduced">
                        <ElInput
                            v-model="formData.introduced"
                            type="textarea"
                            placeholder="请输入智能体的说明"
                            resize="none"
                            :rows="6" />
                    </ElFormItem>
                    <ElFormItem label="Coze智能体ID" prop="coze_id">
                        <ElInput v-model="formData.coze_id" class="!h-10" placeholder="请输入Coze智能体ID" />
                    </ElFormItem>
                    <ElFormItem label="输出方式" prop="stream">
                        <ElRadioGroup v-model="formData.stream">
                            <ElRadio label="流式输出" :value="1"></ElRadio>
                            <ElRadio label="直接返回" :value="0"></ElRadio>
                        </ElRadioGroup>
                    </ElFormItem>
                </ElForm>
                <!-- 保存按钮 -->
                <div class="flex justify-center">
                    <ElButton
                        color="#000000"
                        class="!rounded-full !h-[50px] w-[310px] shadow-[0_6px_12px_0px_#0065FB33]"
                        :loading="isLock"
                        @click="lockFn">
                        保存
                    </ElButton>
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
    .top {
        @apply w-full h-[235px] bg-no-repeat bg-cover rounded-tl-[20px] rounded-tr-[20px] flex flex-col justify-center items-center;
    }
}
</style>
