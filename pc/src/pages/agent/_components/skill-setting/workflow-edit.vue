<template>
    <popup
        ref="popupRef"
        async
        width="550px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        :show-close="false">
        <div>
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <div class="text-2xl font-bold mb-5">工作流</div>
            <!-- 表单 -->
            <ElForm ref="formRef" :model="formData" :rules="formRules" label-position="top">
                <ElFormItem label="工作流ID" prop="workflow_id">
                    <ElInput v-model="formData.workflow_id" placeholder="请输入工作流ID" />
                </ElFormItem>
                <ElFormItem label="授权Token" prop="api_token">
                    <ElInput v-model="formData.api_token" placeholder="请输入授权Token" />
                </ElFormItem>
            </ElForm>
            <!-- 操作按钮 -->
            <div class="flex">
                <ElButton class="!rounded-full flex-1 !h-[50px]" @click="close">取消</ElButton>
                <ElButton type="primary" class="!rounded-full flex-1 !h-[50px]" @click="handleConfirm"> 保存 </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { type FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";

/**
 * @description 添加或编辑工作流技能的弹窗组件
 */

const emit = defineEmits(["close", "success"]);

const popupRef = ref<InstanceType<typeof Popup>>();

// 表单ref和数据
const formRef = ref<FormInstance>();
const formData = reactive({
    workflow_id: "",
    bot_id: "",
    app_id: "",
    api_token: "",
});

// 表单验证规则
const formRules = {
    workflow_id: [{ required: true, message: "请输入工作流ID" }],
    bot_id: [{ required: true, message: "请输入智能体ID" }],
    app_id: [{ required: true, message: "请输入应用ID" }],
    api_token: [{ required: true, message: "请输入授权Token" }],
};

// 打开弹窗
const open = () => {
    popupRef.value?.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
};

/**
 * @description 提交表单
 */
const handleConfirm = async () => {
    await formRef.value.validate();
    try {
        close();
        emit("success", formData);
        feedback.msgSuccess(`保存成功`);
    } catch (error) {
        feedback.msgError((error as string) || `保存失败`);
    }
};

// 暴露方法
defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped></style>
