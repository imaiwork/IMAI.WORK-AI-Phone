<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        :async="true"
        width="860px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <el-form ref="formRef" :model="formData" :rules="rules" label-position="top">
            <div class="grid grid-cols-2 gap-4">
                <el-form-item label="文档内容" prop="question">
                    <el-input
                        v-model="formData.question"
                        type="textarea"
                        :rows="7"
                        resize="none"
                        placeholder="请输入文档内容" />
                </el-form-item>
                <el-form-item label="补充内容" prop="answer">
                    <el-input
                        v-model="formData.answer"
                        type="textarea"
                        :rows="7"
                        resize="none"
                        placeholder="请输入补充内容" />
                </el-form-item>
            </div>

            <el-form-item label="图片">
                <material-picker v-model="imageUrls" type="image" :limit="9" />
            </el-form-item>

            <div class="grid grid-cols-2 gap-4">
                <el-form-item label="视频">
                    <material-picker v-model="videoUrl" type="video" :limit="1" />
                </el-form-item>
                <el-form-item label="附件">
                    <upload type="file" :limit="1" :show-file-list="false" @success="handleFileSuccess">
                        <div class="file-upload">
                            <Icon name="el-icon-Upload" />
                            <span>选择附件</span>
                        </div>
                    </upload>
                    <div class="mt-2 w-full">
                        <div v-for="(item, index) in formData.files" :key="index" class="file-item">
                            <span class="truncate">{{ item.name }}</span>
                            <el-button link type="danger" @click="formData.files.splice(index, 1)">删除</el-button>
                        </div>
                    </div>
                </el-form-item>
            </div>
        </el-form>
    </popup>
</template>

<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
import { useLockFn } from "@/hooks/useLockFn";
import { setFormData } from "@/utils/util";
import {
    knowKnowledgeVectorFileChunkAdd,
    knowKnowledgeVectorFileChunkDetail,
    knowKnowledgeVectorFileChunkEdit,
} from "@/api/ai_application/knowledge_base/files";

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const popupRef = shallowRef<InstanceType<typeof Popup>>();
const formRef = shallowRef<FormInstance>();
const mode = ref<"add" | "edit">("add");
const popupTitle = computed(() => (mode.value === "add" ? "新增分段" : "编辑分段"));

const defaultFormData = () => ({
    kb_id: "",
    fd_id: "",
    uuid: "",
    question: "",
    answer: "",
    images: [] as any[],
    files: [] as any[],
    video: [] as any[],
});

const formData = reactive(defaultFormData());

const rules = {
    question: [{ required: true, message: "请输入文档内容", trigger: "blur" }],
};

const resetFormData = () => {
    Object.assign(formData, defaultFormData());
    nextTick(() => formRef.value?.clearValidate());
};

const open = (type: "add" | "edit" = "add") => {
    mode.value = type;
    if (type === "add") resetFormData();
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const getUploadData = (res: any) => ({
    name: res?.name || res?.data?.name || "",
    url: res?.uri || res?.data?.uri || "",
});

// 从 url 推导素材名称（material-picker 仅返回 uri，名称非后端必填项）
const getFileName = (url: string) => decodeURIComponent(url.split("/").pop()?.split("?")[0] || "");

// material-picker 以 uri 字符串/数组作为 v-model，这里在 uri 与 { name, url } 之间互转，复用已有名称
const imageUrls = computed<string[]>({
    get: () => formData.images.map((item) => item.url),
    set: (urls) => {
        formData.images = urls.map((url) => {
            const existed = formData.images.find((item) => item.url === url);
            return { url, name: existed?.name || getFileName(url) };
        });
    },
});

const videoUrl = computed<string>({
    get: () => formData.video[0]?.url || "",
    set: (url) => {
        if (!url) {
            formData.video = [];
            return;
        }
        const existed = formData.video[0];
        formData.video = [{ url, name: existed?.url === url ? existed.name : getFileName(url) }];
    },
});

const handleFileSuccess = (res: any) => {
    formData.files.push(getUploadData(res));
};

const normalizeFormData = () => {
    formData.images = Array.isArray(formData.images) ? formData.images : [];
    formData.files = Array.isArray(formData.files) ? formData.files : [];
    formData.video = Array.isArray(formData.video) ? formData.video : [];
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    mode.value === "edit"
        ? await knowKnowledgeVectorFileChunkEdit(formData)
        : await knowKnowledgeVectorFileChunkAdd(formData);
    popupRef.value?.close();
    emit("success");
};

const { lockFn, isLock } = useLockFn(handleSubmit);

const getDetail = async (uuid: string) => {
    const data = await knowKnowledgeVectorFileChunkDetail({ uuid });
    resetFormData();
    setFormData(data || {}, formData);
    normalizeFormData();
};

defineExpose({
    open,
    setFormData: (data: any) => {
        setFormData(data, formData);
        normalizeFormData();
    },
    getDetail,
});
</script>

<style scoped lang="scss">
.file-upload {
    @apply h-9 px-3 border border-br rounded flex items-center gap-2 text-sm cursor-pointer hover:border-primary hover:text-primary;
}

.file-item {
    @apply flex items-center justify-between gap-2 px-3 py-2 rounded bg-page text-sm;
}
</style>
