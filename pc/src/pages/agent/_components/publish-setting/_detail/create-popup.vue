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
            <!-- 标题 -->
            <div class="text-2xl font-bold mb-5">{{ formData.id ? "编辑" : "添加" }}链接</div>
            <!-- 表单 -->
            <ElForm :model="formData" :rules="rules" ref="formRef" label-position="top">
                <ElFormItem label="名称" prop="name">
                    <ElInput v-model="formData.name" class="!h-11" placeholder="记录名称，仅用于展示" />
                </ElFormItem>
                <ElFormItem label="密码" prop="password">
                    <ElInput
                        v-model="formData.password"
                        type="password"
                        placeholder="不设置密码，可直接访问"
                        show-password
                        class="!h-11" />
                </ElFormItem>
                <ElFormItem label="对话模式" prop="chat_type" v-if="showChatType && !formData.id">
                    <div>
                        <ElRadioGroup v-model="formData.chat_type">
                            <ElRadio :value="1">文本对话</ElRadio>
                            <ElRadio :value="2">形象对话</ElRadio>
                        </ElRadioGroup>
                        <div class="form-tips">若关闭或没有配置形象选择后，默认展示文本</div>
                    </div>
                </ElFormItem>
            </ElForm>
            <!-- 操作按钮 -->
            <div class="flex">
                <ElButton class="!rounded-full flex-1 !h-[50px]" @click="close">取消</ElButton>
                <ElButton type="primary" class="!rounded-full flex-1 !h-[50px]" :loading="isLock" @click="lockFn">
                    保存
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { addPublish, updatePublish } from "@/api/agent";
import { PublishTypeEnum } from "../../../_enums";

// 定义props
const props = defineProps<{
    showChatType?: boolean | number; // 是否显示对话模式选项
    type: PublishTypeEnum; // 发布类型
    agentId: string | number; // 智能体ID
}>();

const emit = defineEmits(["close", "success"]);

const popupRef = shallowRef();

// 表单数据
const formData = reactive({
    robot_id: props.agentId,
    type: props.type,
    id: "",
    name: "",
    password: "",
    chat_type: 1,
});
const formRef = shallowRef();

// 表单验证规则
const rules = reactive({
    name: [
        {
            required: true,
            message: "请输入分享名称",
        },
    ],
    // 密码为非必填项，故不加规则
});

// 使用 useLockFn 防止重复提交
const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value.validate();
    try {
        // 根据是否存在ID判断是新增还是更新
        formData.id ? await updatePublish(formData) : await addPublish(formData);
        feedback.msgSuccess("发布成功");
        close();
        emit("success");
    } catch (error) {
        feedback.msgError(error as string);
    }
});

// 打开弹窗
const open = () => {
    popupRef.value.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
};

// 暴露方法
defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped lang="scss"></style>
