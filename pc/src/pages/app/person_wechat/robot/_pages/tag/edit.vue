<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        width="610px"
        async
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
            <ElFormItem label="匹配模式" prop="match_type">
                <ElRadioGroup v-model="formData.match_type">
                    <ElRadio :value="0">模糊匹配</ElRadio>
                    <ElRadio :value="1">精确匹配</ElRadio>
                </ElRadioGroup>
            </ElFormItem>
            <ElFormItem label="匹配对象" prop="match_mode">
                <ElRadioGroup v-model="formData.match_mode">
                    <ElRadio :value="0">AI回复</ElRadio>
                    <ElRadio :value="1">客户回复</ElRadio>
                </ElRadioGroup>
            </ElFormItem>
            <ElFormItem label="匹配关键词" prop="match_keywords">
                <ElInput
                    v-model="formData.match_keywords"
                    type="textarea"
                    resize="none"
                    placeholder="请输入匹配关键词"
                    maxlength="500"
                    :rows="4" />
            </ElFormItem>
            <ElFormItem label="匹配标签" prop="tag_name">
                <ElInput v-model="formData.tag_name" placeholder="请输入匹配标签" maxlength="50" />
            </ElFormItem>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import { tagInfo, addTag, updateTag } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";

const emit = defineEmits(["close", "success"]);
const mode = ref("add");
const popupTitle = computed(() => {
    return mode.value == "add" ? "新增标签" : "编辑标签";
});

const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    id: "",
    match_type: 0,
    match_mode: 0,
    match_keywords: "",
    tag_name: "",
    wechat_id: "",
});
const rules = {
    match_keywords: [{ required: true, message: "请输入匹配关键词" }],
    tag_name: [{ required: true, message: "请输入匹配标签" }],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    try {
        mode.value == "add" ? await addTag(formData) : await updateTag(formData);
        popupRef.value?.close();
        feedback.msgSuccess("操作成功");
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
});

const open = (type = "add") => {
    mode.value = type;
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    setFormData: (data) => setFormData(data, formData),
});
</script>

<style scoped></style>
