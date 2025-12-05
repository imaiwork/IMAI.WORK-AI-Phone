<template>
    <ElDrawer
        v-model="visible"
        title="添加文件"
        size="600px"
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <div class="pb-8">
            <file-add-form ref="fileAddFormRef" v-model="formData"></file-add-form>
        </div>
        <div class="absolute bottom-0 left-0 w-full flex justify-end p-4 bg-white">
            <ElButton type="primary" :loading="isLock" @click="lockFn">确定</ElButton>
            <ElButton @click="close">取消</ElButton>
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
import { knowledgeBaseFileAdd } from "@/api/knowledge_base";
import { ElDrawer } from "element-plus";
import FileAddForm from "./file-add-form.vue";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const visible = ref(false);
const formData = reactive<Record<string, any>>({
    id: "",
    index_id: "",
    category_id: "",
    strategy: 1,
    separator: "",
    chunk_size: 600,
    overlap_size: 100,
    rerank_min_score: 0.5,
    documents: [],
});
const fileAddFormRef = shallowRef<InstanceType<typeof FileAddForm>>();

const open = async () => {
    visible.value = true;
    await nextTick();
    fileAddFormRef.value?.clearUploadFile();
};

const close = () => {
    emit("close");
};

const handleSubmit = async () => {
    if (!fileAddFormRef.value?.validateForm()) return;
    const params = { ...formData };
    if (formData.strategy == 2) {
        const separator = formData.separator.map((item: string) => {
            if (JSON.stringify(item) == JSON.stringify("\\n")) {
                return `\n`;
            }
            return item;
        });
        params.separator = separator;
    }

    try {
        await knowledgeBaseFileAdd(params);
        feedback.msgSuccess("操作成功");
        visible.value = false;
        emit("success");
    } catch (error: any) {
        feedback.msgError(error || "操作失败");
    }
};

const { lockFn, isLock } = useLockFn(handleSubmit);

const getDetail = async (data: any) => {
    setFormData(data, formData);
};

defineExpose({
    open,
    getDetail,
});
</script>

<style scoped lang="scss">
:deep(.el-upload-dragger) {
    @apply p-0;
}
</style>
