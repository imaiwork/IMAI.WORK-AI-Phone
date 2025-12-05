<template>
    <div class="upload">
        <el-upload
            v-model:file-list="fileList"
            ref="uploadRefs"
            v-bind="$attrs"
            :multiple="multiple"
            :limit="limit"
            :show-file-list="showFileList"
            :on-progress="handleProgress"
            :on-success="handleSuccess"
            :on-exceed="handleExceed"
            :on-error="handleError"
            :accept="getAccept"
            :on-change="handleChange"
            :on-remove="handleRemove"
            :http-request="httpRequest">
            <template v-for="(slot, key) in $slots" #[key]="slotData">
                <slot :name="key" v-bind="slotData" />
            </template>
        </el-upload>
        <el-dialog
            v-if="!showFileList && fileList.length"
            v-model="visible"
            title="上传进度"
            :close-on-click-modal="false"
            width="500px"
            :modal="false"
            @close="handleClose">
            <div class="file-list p-4">
                <template v-for="(item, index) in fileList" :key="index">
                    <div class="mb-5">
                        <div>{{ item.name }}</div>
                        <div class="flex-1">
                            <el-progress :percentage="parseInt(String(item.percentage))" />
                        </div>
                    </div>
                </template>
            </div>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup>
import feedback from "@/utils/feedback";
import type { ElUpload, UploadRequestOptions, UploadUserFile } from "element-plus";
import { uploadFile } from "@/api/app";
import type { PropType } from "vue";

const props = defineProps({
    files: {
        type: Array as PropType<UploadUserFile[]>,
        default: () => [],
    },
    // 上传文件类型
    type: {
        type: String,
        default: "image",
    },
    // 是否支持多选
    multiple: {
        type: Boolean,
        default: true,
    },
    // 多选时最多选择几条
    limit: {
        type: Number,
        default: 10,
    },
    // 上传时的额外参数
    data: {
        type: Object,
        default: () => ({}),
    },
    // 上传文件的字段名
    name: {
        type: String,
        default: "file",
    },
    // 上传时的请求头额外参数
    header: {
        type: Object,
        default: () => ({}),
    },
    //是否显示列表进度，显示列表进度将
    showFileList: {
        type: Boolean,
        default: false,
    },
});
const emit = defineEmits(["end", "change", "error", "success", "update:files"]);
const uploadRefs = shallowRef<InstanceType<typeof ElUpload>>();

const visible = ref(false);
const fileList = ref<UploadUserFile[]>([]);

const handleProgress = () => {
    visible.value = true;
};
const handleChange = (file: any) => {
    emit("change", file);
    if (fileList.value.length == 0) {
        emit("end");
    }
};
const handleSuccess = (response: any, file: any) => {
    handleChange(file);
    emit("success", response);
    emit("update:files", [
        ...props.files,
        {
            url: response.uri,
            name: response.name,
        },
    ]);
    const fileIndex = fileList.value.indexOf(file);
    !props.showFileList && fileList.value.splice(fileIndex, 1);
};

const handleRemove = (file: any) => {
    const fileIndex = fileList.value.indexOf(file);
    const newFiles = props.files;
    newFiles.splice(fileIndex, 1);
    emit("update:files", [...newFiles]);
};

const handleError = (event: any, file: any) => {
    handleChange(file);
    feedback.msgError(`${file.name}文件上传失败`);
    uploadRefs.value?.abort(file);
    visible.value = false;
    emit("error", file);
};
const handleExceed = () => {
    feedback.msgError(`超出上传上限${props.limit}，请重新上传`);
};
const handleClose = () => {
    fileList.value = [];
    visible.value = false;
};

const getAccept = computed(() => {
    switch (props.type) {
        case "image":
            return ".jpg,.png,.gif,.jpeg";
        case "video":
            return ".wmv,.avi,.mpg,.mpeg,.3gp,.mov,.mp4,.flv,.rmvb,.mkv";
        case "audio":
            return ".mp3,.m4a,.wav,.mov";
        default:
            return "*";
    }
});
const httpRequest = (options: UploadRequestOptions) => {
    return uploadFile(
        props.type as any,
        {
            file: options.file,
            name: props.name,
            header: props.header,
            data: props.data,
        },
        (evt: any) => {
            // 处理进度条
            const progressEvt = evt;
            progressEvt.percent = evt.total > 0 ? (evt.loaded / evt.total) * 100 : 0;
            options.onProgress(progressEvt);
        }
    );
};

watch(
    () => props.files,
    (value) => {
        if (!fileList.value.length && value?.length) {
            fileList.value = [...value];
        }
    },
    {
        immediate: true,
    }
);
</script>

<style lang="scss"></style>
