<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        :async="true"
        width="560px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
            <el-form-item label="知识库名称" prop="name">
                <el-input v-model="formData.name" maxlength="20" show-word-limit placeholder="请输入知识库名称" />
            </el-form-item>
            <el-form-item label="知识库描述" prop="intro">
                <el-input
                    v-model="formData.intro"
                    type="textarea"
                    :rows="4"
                    maxlength="200"
                    show-word-limit
                    resize="none"
                    placeholder="请输入知识库描述" />
            </el-form-item>
            <el-form-item label="知识库封面">
                <material-picker v-model="formData.image" :limit="1" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
import { useLockFn } from "@/hooks/useLockFn";
import { setFormData } from "@/utils/util";
import { knowKnowledgeVectorAdd, knowKnowledgeVectorEdit } from "@/api/ai_application/knowledge_base/lists";

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const popupRef = shallowRef<InstanceType<typeof Popup>>();
const formRef = shallowRef<FormInstance>();
const popupTitle = ref("");
const mode = ref<"add" | "edit">("add");

const defaultFormData = () => ({
    id: "",
    name: "",
    intro: "",
    image: "",
});

const formData = reactive(defaultFormData());

const rules = {
    name: [{ required: true, message: "请输入知识库名称", trigger: "blur" }],
    image: [{ required: true, message: "请上传知识库封面", trigger: "blur" }],
};

const resetFormData = () => {
    Object.assign(formData, defaultFormData());
    nextTick(() => formRef.value?.clearValidate());
};

const open = (type: "add" | "edit" = "add") => {
    mode.value = type;
    popupTitle.value = type === "add" ? "新建知识库" : "编辑知识库";
    if (type === "add") resetFormData();
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    const params = {
        id: formData.id,
        name: formData.name,
        intro: formData.intro,
        image: formData.image,
        documents_model_id: 2,
        documents_model_sub_id: 2,
        embedding_model_id: 3,
        embedding_model_sub_id: 3,
    };
    mode.value === "edit" ? await knowKnowledgeVectorEdit(params) : await knowKnowledgeVectorAdd(params);
    popupRef.value?.close();
    emit("success");
};

const { lockFn, isLock } = useLockFn(handleSubmit);

defineExpose({
    open,
    setFormData: (data: Record<string, any>) => {
        resetFormData();
        setFormData(data, formData);
        formData.intro = data.intro ?? data.description ?? formData.intro;
    },
});
</script>

<style scoped lang="scss">
.cover-uploader {
    @apply w-[160px] h-[110px] rounded border border-dashed border-br overflow-hidden cursor-pointer relative bg-page;
}
</style>
