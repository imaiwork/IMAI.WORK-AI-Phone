<template>
    <popup
        ref="popupRef"
        async
        title="编辑知识库"
        width="550px"
        :confirm-loading="isLock"
        confirm-button-text=""
        cancel-button-text=""
        @close="close"
        @confirm="lockFn">
        <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
            <ElFormItem label="知识库名称" prop="name">
                <ElInput v-model="formData.name" show-word-limit maxlength="12" placeholder="请输入知识库名称" />
            </ElFormItem>
            <ElFormItem label="知识库描述" prop="description">
                <ElInput
                    v-model="formData.description"
                    show-word-limit
                    maxlength="1000"
                    type="textarea"
                    resize="none"
                    placeholder="请输入知识库描述"
                    :rows="8" />
            </ElFormItem>
        </ElForm>
        <div class="flex justify-end mt-6 -mb-4">
            <ElButton @click="close">取消</ElButton>
            <ElButton type="primary" :loading="isLock" @click="lockFn"> 确定 </ElButton>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { knowledgeBaseAdd, knowledgeBaseEdit, knowledgeBaseDetail } from "@/api/knowledge_base";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const userStore = useUserStore();
const { getTokenByScene } = userStore;
const { userTokens } = toRefs(userStore);

const tokensValue = computed(() => {
    return getTokenByScene(TokensSceneEnum.KNOWLEDGE_CREATE)?.score;
});

const popupRef = ref<InstanceType<typeof Popup>>();
const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    id: "",
    name: "",
    description: "",
});

const rules = {
    name: [{ required: true, message: "请输入知识库名称" }],
    description: [{ required: true, message: "请输入知识库描述" }],
};

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    try {
        await knowledgeBaseEdit(formData);
        feedback.msgSuccess("操作成功");
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
};

const { lockFn, isLock } = useLockFn(handleSubmit);

const getDetail = async (id: number) => {
    const data = await knowledgeBaseDetail({ id });
    setFormData(data, formData);
};

defineExpose({
    open,
    getDetail,
});
</script>

<style scoped></style>
