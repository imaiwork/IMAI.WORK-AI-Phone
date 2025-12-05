<template>
    <popup
        ref="popupRef"
        :title="mode === 'add' ? '新建匹配条件' : '编辑匹配条件'"
        width="610px"
        async
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
            <ElFormItem label="匹配类型" prop="match_type">
                <ElSelect v-model="formData.match_type" placeholder="请选择匹配类型" @change="handleMatchTypeChange">
                    <ElOption label="动作匹配" :value="1"></ElOption>
                    <ElOption label="聊天内容匹配" :value="2"></ElOption>
                </ElSelect>
            </ElFormItem>
            <template v-if="formData.match_type == 1">
                <ElFormItem label="动作类型" prop="action_type">
                    <ElRadioGroup v-model="formData.action_type">
                        <ElRadio :value="1">刚成为好友</ElRadio>
                        <ElRadio :value="2" disabled>更多</ElRadio>
                    </ElRadioGroup>
                </ElFormItem>
            </template>
            <template v-if="formData.match_type == 2">
                <ElFormItem label="匹配模式" prop="chat_match_mode">
                    <ElRadioGroup v-model="formData.chat_match_mode">
                        <ElRadio :value="1">模糊匹配</ElRadio>
                        <ElRadio :value="2">精确匹配</ElRadio>
                    </ElRadioGroup>
                </ElFormItem>
                <ElFormItem label="匹配对象" prop="chat_match_object">
                    <ElRadioGroup v-model="formData.chat_match_object">
                        <ElRadio :value="1">AI回复</ElRadio>
                        <ElRadio :value="2">客户回复</ElRadio>
                    </ElRadioGroup>
                </ElFormItem>
                <ElFormItem label="匹配关键词" prop="chat_keywords">
                    <ElInput v-model="formData.chat_keywords" placeholder="请输入匹配关键词" maxlength="100"></ElInput>
                </ElFormItem>
            </template>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import { sopAddTagTrigger, sopUpdateTagTrigger } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const mode = ref<"add" | "edit">("add");

const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    flow_id: "",
    stage_id: "",
    trigger_id: "",
    match_type: 1,
    action_type: 1,
    chat_match_mode: 1,
    chat_match_object: 1,
    chat_keywords: "",
});
const rules = {
    chat_keywords: [{ required: true, message: "请输入匹配关键词", trigger: "blur" }],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const handleMatchTypeChange = () => {
    if (formData.match_type == 1) {
        formData.action_type = 1;
    }
    if (formData.match_type == 2) {
        formData.chat_match_mode = 1;
        formData.chat_match_object = 1;
        formData.chat_keywords = "";
    }
};

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    let params: any = {
        flow_id: formData.flow_id,
        stage_id: formData.stage_id,
        trigger_id: formData.trigger_id,
        match_type: formData.match_type,
    };
    params = {
        ...params,
        action_type: formData.match_type == 1 ? formData.action_type : "",
        chat_match_mode: formData.match_type == 2 ? formData.chat_match_mode : "",
        chat_match_object: formData.match_type == 2 ? formData.chat_match_object : "",
        chat_keywords: formData.match_type == 2 ? formData.chat_keywords : "",
    };
    try {
        formData.trigger_id ? await sopUpdateTagTrigger(params) : await sopAddTagTrigger(params);
        popupRef.value?.close();
        emit("success");
        feedback.msgSuccess("操作成功");
    } catch (error) {
        feedback.msgError(error);
    }
});

const open = (type: "add" | "edit") => {
    mode.value = type;
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    setFormData: (data: any) => {
        setFormData(data, formData);
    },
});
</script>

<style scoped></style>
