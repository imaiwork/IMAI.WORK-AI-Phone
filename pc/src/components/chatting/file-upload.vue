<template>
    <div class="cursor-pointer">
        <input
            type="file"
            class="hidden"
            ref="fileInputRef"
            :accept="accept"
            :multiple="multiple"
            @change="changeFile" />
        <div @click="handleUpload()" v-if="fileLists.length <= fileLimit">
            <slot> </slot>
        </div>
    </div>
</template>

<script setup lang="ts">
import { uploadFile } from "@/api/app";
import { FileParams, UPLOAD_STATUS } from "@/composables/usePasteImage";
import { cancelRequest } from "@/utils/http";
import feedback from "@/utils/feedback";
import dayjs from "dayjs";

const props = withDefaults(
    defineProps<{
        modelValue: FileParams[] | any;
        fileLimit?: number;
        accept?: string;
        isPaste?: boolean;
        multiple?: boolean;
        type?: string;
    }>(),
    {
        value: () => [],
        fileLimit: 1,
        accept: "*",
        isPaste: true,
        multiple: true,
    }
);

const emit = defineEmits<{
    (event: "update:modelValue", value: any): void;
    (event: "change", value: any[]): void;
}>();

const fileInputRef = shallowRef<HTMLInputElement>();
// 上传文件
const fileLists = ref<FileParams[]>([]);
// 存储上传请求的标识，用于取消上传
const uploadRequestKeys = ref<string[]>([]);
const handleUpload = () => {
    fileInputRef.value?.click();
};

const changeFile = async (e: Event) => {
    const target = e.target as HTMLInputElement;
    const files: any = Array.from(target.files || []);

    if (files.length > props.fileLimit - fileLists.value.length) {
        feedback.msgError(`上传文件超出限制,最多可上传${props.fileLimit}个文件`);
        return;
    }
    // 判断文件不能超过20M
    const isOverSize = files.some((item: File) => item.size > 20 * 1024 * 1024);
    if (isOverSize) {
        feedback.msgError(`文件大小超出限制,最大支持20M`);
        return;
    }
    files.forEach((item: File) => {
        const reader = new FileReader();
        const fileItem: FileParams = reactive({
            uid: Date.now(),
            url: "",
            file: item,
            loading: true,
            name: item.name,
            size: item.size,
            status: UPLOAD_STATUS.UPLOADING,
            create_time: dayjs().format("YYYY-MM-DD HH:mm:ss"),
        });
        if (item.type.startsWith("image/")) {
            reader.onload = () => {
                fileItem.url = reader.result as string;
            };
            reader.readAsDataURL(item);
        }
        fileLists.value.push(fileItem);
    });

    emit("change", fileLists.value);
    await handleUploadFile();
    fileInputRef.value && (fileInputRef.value.value = "");
};

const handleUploadFile = async () => {
    const uploadPromises = fileLists.value.map((item, index) => submitFileUpload(item, index));
    await Promise.allSettled(uploadPromises);
    fileLists.value = fileLists.value.filter((item: FileParams) => item.status === UPLOAD_STATUS.SUCCESS);
    emit("update:modelValue", fileLists.value);
    emit("change", fileLists.value);
};

const submitFileUpload = async (item: FileParams, index: number) => {
    if (item.status !== UPLOAD_STATUS.UPLOADING) return;
    try {
        item.loading = true;
        // 添加进度属性
        if (!item.progress) {
            item.progress = 0;
        }

        // 生成请求标识
        const requestKey = `upload_${item.name}_${Date.now()}`;
        uploadRequestKeys.value.push(requestKey);

        // 保存请求标识到文件项
        item.requestKey = requestKey;

        const fileRes = await uploadFile(
            {
                file: item.file,
                requestKey: requestKey, // 传递请求标识
            },
            (progress: number) => {
                // 更新上传进度
                item.progress = progress;
            }
        );

        // 上传成功后从请求标识列表中移除
        const keyIndex = uploadRequestKeys.value.indexOf(requestKey);
        if (keyIndex > -1) {
            uploadRequestKeys.value.splice(keyIndex, 1);
        }

        item.file_id = fileRes.id;
        item.loading = false;
        item.status = UPLOAD_STATUS.SUCCESS;
        item.url = fileRes.uri;
        fileLists.value[index] = item;
    } catch (error) {
        // 上传失败或取消时，从请求标识列表中移除
        if (item.requestKey) {
            const keyIndex = uploadRequestKeys.value.indexOf(item.requestKey);
            if (keyIndex > -1) {
                uploadRequestKeys.value.splice(keyIndex, 1);
            }
        }

        // 判断是否为取消操作
        const errorMessage = typeof error === "string" ? error : error?.message || String(error);
        const isCanceled =
            errorMessage.includes("cancel") || errorMessage.includes("取消") || errorMessage.includes("abort");

        if (!isCanceled) {
            // 根据错误类型显示不同的错误信息
            let displayMessage = `上传失败："${item.file.name}"`;

            if (errorMessage.includes("文件格式")) {
                displayMessage = `文件格式不支持："${item.file.name}"`;
            } else if (errorMessage.includes("文件大小")) {
                displayMessage = `文件太大："${item.file.name}"`;
            } else if (errorMessage.includes("网络")) {
                displayMessage = `网络错误，请检查网络连接后重试`;
            } else if (errorMessage.includes("登录") || errorMessage.includes("身份")) {
                displayMessage = `身份验证失效，请重新登录`;
            } else if (errorMessage.trim()) {
                // 如果有具体的错误信息，显示它
                displayMessage = `上传失败：${errorMessage}`;
            }

            feedback.msgError(displayMessage);
        }

        item.loading = false;
        item.status = UPLOAD_STATUS.ERROR;
        fileLists.value = fileLists.value.filter((_, i) => i !== index);
    }
};

// 粘贴图片
const onPasteComplete = (params: FileParams) => {
    const { file, url, status, create_time } = params;
    // @ts-ignore
    const findIndex = fileLists.value.findIndex((item) => +new Date(item.create_time) == +new Date(create_time));
    if (!url || status === UPLOAD_STATUS.ERROR) {
        fileLists.value.splice(findIndex);
    } else {
        if (findIndex > -1) {
            fileLists.value[findIndex] = params;
        } else {
            fileLists.value.push(params);
        }
    }
};

watch(
    () => props.modelValue,
    (val: any[] | string) => {
        fileLists.value = Array.isArray(val) ? val : val == "" ? [] : [val];
    },
    {
        immediate: true,
    }
);

// 组件销毁前取消所有未完成的上传请求
onBeforeUnmount(() => {
    // 取消所有未完成的上传请求
    uploadRequestKeys.value.forEach((key) => {
        cancelRequest(key, "组件销毁，取消上传");
    });
    uploadRequestKeys.value = [];
});

usePasteImage({
    handleFun: onPasteComplete,
    limit: props.fileLimit,
    fileLists: fileLists.value,
    isPaste: props.isPaste,
});
</script>

<style scoped>
.upload-list {
    margin-top: 15px;
}

.upload-item {
    margin-bottom: 10px;
}

.upload-progress {
    margin-top: 10px;
    padding: 8px 12px;
    background-color: #f5f7fa;
    border-radius: 4px;
    display: flex;
    align-items: center;
    position: relative;
}

.file-name {
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
    color: #606266;
    word-break: break-all;
    max-width: 70%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.upload-progress .el-progress {
    flex: 1;
    margin-right: 10px;
}

.upload-progress .el-button {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
}
</style>
