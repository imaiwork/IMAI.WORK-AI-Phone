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
            <div class="text-2xl font-bold mb-5">用量设置</div>
            <!-- 表单 -->
            <ElForm :model="formData" :rules="rules" ref="formRef" label-position="top">
                <ElFormItem label="限制每个用户总对话数" prop="limit_total_chat">
                    <ElInput
                        class="!w-64 mr-4 !h-11"
                        v-model="formData.limit_total_chat"
                        v-number-input="{ min: 0 }"
                        type="number" />条
                </ElFormItem>
                <ElFormItem label="限制每个用户每天总对话数" prop="limit_today_chat">
                    <ElInput
                        class="!w-64 mr-4 !h-11"
                        v-model="formData.limit_today_chat"
                        v-number-input="{ min: 0 }"
                        type="number" />条
                </ElFormItem>
                <ElFormItem label="超出将默认回复" prop="limit_exceed">
                    <ElInput v-model="formData.limit_exceed" class="!h-11 !w-68" placeholder="超出将默认回复" />
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
import { pushUsageSetting } from "@/api/agent";
import { PublishTypeEnum } from "../../../_enums";

/**
 * @description 发布渠道用量设置弹窗
 * @summary 用户可以为每个发布链接设置总对话数、每日对话数限制以及超出限制后的回复语。
 */

// 定义props
const props = defineProps<{
    type: PublishTypeEnum;
    agentId: string | number;
}>();

const emit = defineEmits(["close", "success"]);

const popupRef = shallowRef();

// 表单数据
const formData = reactive({
    id: "",
    robot_id: props.agentId,
    limit_today_chat: 0,
    limit_total_chat: 0,
    limit_exceed: "",
    type: props.type,
});
const formRef = shallowRef();

// 表单验证规则
const rules = reactive({
    limit_total_chat: [{ required: true, message: "请输入总对话数限制" }],
    limit_today_chat: [{ required: true, message: "请输入每日对话数限制" }],
    limit_exceed: [{ required: true, message: "请输入超出限制后的回复内容" }],
});

// 使用 useLockFn 防止重复提交
const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value.validate();
    try {
        await pushUsageSetting(formData);
        feedback.msgSuccess("保存成功");
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
