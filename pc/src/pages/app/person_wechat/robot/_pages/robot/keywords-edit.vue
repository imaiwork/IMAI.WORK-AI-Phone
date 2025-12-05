<template>
    <popup
        ref="popupRef"
        async
        width="550px"
        :title="popupTitle"
        :confirm-loading="isLock"
        @confirm="lockConfirm"
        @close="close">
        <ElForm ref="formRef" :model="formData" :rules="formRules" label-position="top">
            <ElFormItem label="匹配方式" prop="match_type">
                <ElRadioGroup v-model="formData.match_type">
                    <ElRadio :value="0">模糊匹配</ElRadio>
                    <ElRadio :value="1">精确匹配</ElRadio>
                </ElRadioGroup>
            </ElFormItem>
            <ElFormItem label="匹配内容" prop="keyword">
                <ElInput
                    v-model="formData.keyword"
                    placeholder="回复内容支持多词组匹配，以分号相隔; 例如：你好;你好啊;你好呀" />
            </ElFormItem>
            <ElFormItem label="回复内容" prop="reply">
                <div class="h-[600px] w-full overflow-hidden">
                    <add-content ref="addContentRef" v-model="formData.reply" :show-preview="false" />
                </div>
            </ElFormItem>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import { addRobotKeywords, updateRobotKeywords } from "@/api/person_wechat";
import { type FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
import AddContent from "../../../_components/add-content.vue";

const emit = defineEmits(["close", "success"]);

const route = useRoute();

const popupRef = ref<InstanceType<typeof Popup>>();

const mode = ref("add");
const popupTitle = computed(() => {
    return mode.value === "add" ? "新增关键词回复" : "编辑关键词回复";
});

const formRef = ref<FormInstance>();
const formData = reactive({
    id: "",
    match_type: 0,
    keyword: "",
    reply: [],
});

const formRules = {
    keyword: [{ required: true, message: "请输入匹配内容" }],
    reply: [{ required: true, message: "请输入回复内容" }],
};

const open = (type?: any) => {
    mode.value = type ? "edit" : "add";
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const handleConfirm = async () => {
    await formRef.value.validate();
    try {
        formData.id
            ? await updateRobotKeywords(formData)
            : await addRobotKeywords({
                  ...formData,
                  robot_id: route.query.id,
              });
        feedback.msgSuccess(`${mode.value === "add" ? "新增" : "编辑"}成功`);
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        feedback.msgError(error || `${mode.value === "add" ? "新增" : "编辑"}失败`);
    }
};

const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
    formData.reply = formData.reply.map((item: any) => {
        return {
            ...item,
            type: parseInt(item.type),
        };
    });
};

defineExpose({
    open,
    setFormData,
});
</script>

<style scoped></style>
