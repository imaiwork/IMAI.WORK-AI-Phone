<template>
    <div class="upload">
        <el-upload
            v-model:file-list="fileList"
            class="w-[inherit] upload-wrap"
            ref="uploadRefs"
            :drag="drag"
            :action="action"
            :multiple="multiple"
            :limit="limit"
            :disabled="disabled"
            :show-file-list="showFileList"
            :list-type="listType"
            :headers="headers"
            :data="data"
            :before-upload="beforeUpload"
            :on-progress="handleProgress"
            :on-success="handleSuccess"
            :on-exceed="handleExceed"
            :on-error="handleError"
            :on-remove="handleRemove"
            :accept="getAccept">
            <slot></slot>
        </el-upload>
        <el-dialog
            v-if="showProgress && fileList.length"
            v-model="visible"
            title="上传进度"
            append-to-body
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :show-close="false"
            width="500px"
            :modal="false"
            @close="handleClose">
            <div class="file-list p-4">
                <template v-for="(item, index) in fileList" :key="index">
                    <div class="mb-5">
                        <div>{{ item.name }}</div>
                        <div class="flex-1">
                            <el-progress :percentage="parseInt(item.percentage)"></el-progress>
                        </div>
                    </div>
                </template>
            </div>
        </el-dialog>
    </div>
</template>

<script lang="ts">
import { computed, defineComponent, ref, shallowRef } from "vue";
import { useUserStore } from "@/stores/user";
import { getApiPrefix, getApiUrl, getVersion } from "@/utils/env";
import feedback from "@/utils/feedback";
import { genFileId, type ElUpload, type UploadRawFile, type UploadProps } from "element-plus";
import { RequestCodeEnum } from "@/enums/requestEnums";
export default defineComponent({
    components: {},
    props: {
        action: {
            type: String,
            default: "",
        },
        // 上传文件类型
        type: {
            type: String,
            default: "image",
        },
        // 是否拖拽上传
        drag: {
            type: Boolean,
            default: false,
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
        // 是否显示上传进度
        showProgress: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        listType: {
            type: String as PropType<"text" | "picture" | "picture-card">,
            default: "picture",
        },
        showFileList: {
            type: Boolean,
            default: true,
        },
        accept: {
            type: String,
            default: "",
        },
        maxSize: {
            type: Number,
            default: Infinity, // MB
        },
        minSize: {
            type: Number,
            default: 0, // MB
        },
        ratioSize: {
            type: Array as unknown as PropType<[number, number]>,
            default: () => [0, 0],
        },
        // 视频分辨率
        videoMaxWidth: {
            type: Number,
            default: Infinity,
        },
        videoMinWidth: {
            type: Number,
            default: 0,
        },
        // 视频时长
        minDuration: {
            type: Number,
            default: 0,
        },
        maxDuration: {
            type: Number,
            default: Infinity,
        },
    },
    emits: ["change", "error", "remove", "success", "on-progress"],
    setup(props, { emit, expose }) {
        const userStore = useUserStore();
        const uploadRefs = shallowRef<InstanceType<typeof ElUpload>>();
        const action = ref(props.action || `${getApiUrl()}${getApiPrefix()}/upload/${props.type}`);
        const headers = computed(() => ({
            token: userStore.token,
            version: getVersion(),
        }));
        const visible = ref(false);
        const fileList = ref<any[]>([]);

        const beforeUpload: UploadProps["beforeUpload"] = async (rawFile) => {
            const { type, ratioSize, videoMaxWidth, videoMinWidth, minDuration, maxDuration, minSize, maxSize } = props;
            const sizeInMB = rawFile.size / 1024 / 1024;

            // 文件大小校验
            const validateFileSize = () => {
                if (sizeInMB < minSize) {
                    feedback.msgError(`上传文件大小不能小于 ${minSize} MB`);
                    return false;
                }
                if (sizeInMB > maxSize) {
                    feedback.msgError(`上传文件大小不能大于 ${maxSize} MB`);
                    return false;
                }
                return true;
            };

            // 图片尺寸校验
            const validateImageSize = async () => {
                if (ratioSize[0] <= 0 || ratioSize[1] <= 0) return true;

                return new Promise<boolean>((resolve) => {
                    const img = new Image();
                    img.onload = () => {
                        const isValid = img.height >= ratioSize[0] && img.width >= ratioSize[1];
                        if (!isValid) {
                            feedback.msgError(`上传图片尺寸不能小于 ${ratioSize[0]}*${ratioSize[1]}`);
                        }
                        resolve(isValid);
                    };
                    img.src = URL.createObjectURL(rawFile);
                });
            };

            // 音频时长校验
            const validateAudioDuration = async () => {
                if (minDuration <= 0 || maxDuration <= 0) return true;

                return new Promise<boolean>((resolve) => {
                    const audio = new Audio(URL.createObjectURL(rawFile));
                    audio.addEventListener("loadedmetadata", () => {
                        const duration = audio.duration;
                        const isValid = duration >= minDuration && duration <= maxDuration;
                        if (!isValid) {
                            feedback.msgError(`上传音频时长不能小于${minDuration}秒或大于${maxDuration}秒`);
                        }
                        resolve(isValid);
                    });
                });
            };

            // 视频校验
            const validateVideo = async () => {
                if (videoMaxWidth <= 0 || videoMinWidth <= 0) return true;

                return new Promise<boolean>((resolve) => {
                    const video = document.createElement("video");
                    video.src = URL.createObjectURL(rawFile);
                    video.muted = true;
                    video.playsInline = true;
                    video.preload = "auto";
                    video.crossOrigin = "anonymous";

                    video.addEventListener("loadedmetadata", () => {
                        const { videoWidth, duration } = video;
                        const fileDuration = Math.floor(duration);
                        const isResolutionValid = videoWidth >= videoMinWidth && videoWidth <= videoMaxWidth;
                        const isDurationValid = fileDuration >= minDuration && fileDuration <= maxDuration;

                        if (!isResolutionValid) {
                            feedback.msgError(`上传视频分辨率不能满足${videoMinWidth}*${videoMaxWidth}`);
                        }
                        if (!isDurationValid) {
                            feedback.msgError(`上传视频时长不能小于${minDuration}秒或大于${maxDuration}秒`);
                        }
                        resolve(isResolutionValid && isDurationValid);
                    });
                });
            };

            // 执行校验
            if (!validateFileSize()) return false;

            switch (type) {
                case "image":
                    return await validateImageSize();
                case "audio":
                    return await validateAudioDuration();
                case "video":
                    return await validateVideo();
                default:
                    return true;
            }
        };

        const handleProgress = (event: any, file: any, fileLists: any[]) => {
            visible.value = true;
            fileList.value = toRaw(fileLists);
            emit("on-progress", event.percent);
        };

        const handleSuccess = (response: any, file: any, fileLists: any[]) => {
            const allSuccess = fileLists.every((item) => item.status == "success");
            if (allSuccess) {
                // uploadRefs.value?.clearFiles();
                visible.value = false;
            }
            emit("change", file);
            if (response.code == RequestCodeEnum.SUCCESS) {
                feedback.msgSuccess(response.msg || "上传成功");
                emit("success", response);
            }
            if (response.code == RequestCodeEnum.FAIL) {
                fileList.value.splice(
                    fileList.value.findIndex((item: any) => item.raw.uid == file.raw.uid),
                    1
                );
                feedback.msgError(response.msg || "上传失败");
            }
        };
        const handleError = (event: any, file: any) => {
            feedback.msgError(`${file.name}文件上传失败`);
            uploadRefs.value?.abort(file);
            visible.value = false;
            emit("change", file);
            emit("error", file);
        };
        const handleExceed = (files) => {
            if (props.limit === 1) {
                uploadRefs.value.clearFiles();
                const file = files[0] as UploadRawFile;
                file.uid = genFileId();
                uploadRefs.value!.handleStart(file);
                uploadRefs.value.submit();
            } else {
                feedback.msgError(`文件数量上限为${props.limit}个，请重新上传`);
            }
        };

        const handleRemove = (file: any) => {
            fileList.value = fileList.value.filter((item) => item.uid !== file.uid);
            emit("remove", file);
        };

        const handleClose = () => {
            uploadRefs.value?.clearFiles();
            visible.value = false;
        };

        // 清除文件
        const clearFile = () => {
            uploadRefs.value?.clearFiles();
            fileList.value = [];
        };

        const getAccept = computed(() => {
            if (props.accept) {
                return props.accept;
            }

            switch (props.type) {
                case "image":
                    return ".jpg,.png,.gif,.jpeg";
                case "video":
                    return ".wmv,.avi,.mpg,.mpeg,.3gp,.mov,.mp4,.flv,.rmvb,.mkv";
                case "audio":
                    return ".mp3,.wav";
                default:
                    return "*";
            }
        });

        watch(
            () => props.type,
            (newVal) => {
                action.value = `${getApiUrl()}${getApiPrefix()}/upload/${newVal}`;
            }
        );
        expose({
            setFileList: (data: any[]) => {
                fileList.value = data;
            },
        });

        return {
            uploadRefs,
            action,
            headers,
            visible,
            fileList,
            getAccept,
            clearFile,
            beforeUpload,
            handleProgress,
            handleSuccess,
            handleError,
            handleExceed,
            handleRemove,
            handleClose,
        };
    },
});
</script>

<style lang="scss" scoped>
.upload {
    :deep(.el-upload) {
        width: inherit;
    }
}
</style>
