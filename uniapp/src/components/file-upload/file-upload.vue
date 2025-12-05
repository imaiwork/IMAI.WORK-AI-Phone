<template>
    <view class="file-upload w-full flex-col">
        <view class="file-picker w-full">
            <view @click="choose" class="w-full">
                <slot name="trigger" v-if="$slots.trigger"> </slot>
                <template v-else>
                    <view
                        class="flex flex-col items-center justify-center border border-solid border-[#efefef] rounded-[10rpx] bg-white px-2 py-4">
                        <u-icon name="/static/images/icons/upload.svg" :size="48"></u-icon>
                        <view class="ml-[10rpx] text-xs mt-2">
                            {{ getBtnText }}
                        </view>
                    </view>
                </template>
            </view>
        </view>

        <view class="file-list">
            <view class="file-item relative" v-for="item in filesLists" :key="item.url">
                <view class="bg-primary mr-[10rpx] flex p-[6rpx] rounded-[10rpx]">
                    <u-icon v-if="fileType == 'file'" :size="30" name="file-text" color="#ffffff" />
                    <image v-if="fileType == 'image'" :src="item.path || item.url" class="w-[32rpx] h-[32rpx]" />
                    <u-icon v-if="fileType == 'video'" :size="30" name="camera" color="#ffffff" />
                </view>
                <view class="flex-1 min-w-0 mr-[20rpx] w-0">
                    <view class="overflow-ellipsis overflow-hidden whitespace-nowrap">
                        {{ item.name }}
                    </view>
                </view>
                <view
                    v-if="!disabled"
                    @click.stop="removeFile(item.url!)"
                    class="w-4 h-4 flex items-center justify-center rounded-full bg-primary">
                    <u-icon name="close" :size="16" color="#ffffff" />
                </view>
                <view
                    class="absolute bottom-[0px] w-full left-0 flex"
                    v-if="
                        showProgress 
                        && item.progress! >= 0 
                        && item.progress 
                        && item.status !== 'success'
                    ">
                    <u-line-progress
                        class="w-full h-auto"
                        height="6"
                        :show-percent="false"
                        active-color="#0065FB"
                        :percent="item.progress"></u-line-progress>
                </view>
                <view
                    v-if="item.status === 'error'"
                    class="absolute inset-0 bg-[rgba(0,0,0,0.4)] flex items-center justify-center text-white">
                    <view @click="retry(item)"> 点击重试 </view>
                </view>
            </view>
        </view>
    </view>
</template>
<script lang="ts" setup>
import { PropType, computed, ref, watch } from "vue";
import { ChooseResult, FileData, chooseFile, getFileName, getFilesByExtname, normalizeFileData } from "./choose-file";
import { uploadFile, uploadGPTFile } from "@/api/app";
import { useAppStore } from "@/stores/app";
const props = defineProps({
    modelValue: {
        type: [Array, Object],
        default() {
            return [];
        },
    },
    limit: {
        type: Number,
        default: 10,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    // 文件类型
    fileType: {
        type: String as PropType<"image" | "video" | "file" | "all">,
        default: "file",
        validator: (value: string) => {
            return ["image", "video", "file", "all"].includes(value);
        },
    },
    returnType: {
        type: String as PropType<"object" | "array">,
        default: "array",
    },
    // 文件类型筛选
    fileExtname: {
        type: Array as PropType<string[]>,
        default() {
            return [];
        },
    },
    data: {
        type: Object,
        default() {
            return {};
        },
    },
    header: {
        type: Object,
        default() {
            return {};
        },
    },
    sizeType: {
        type: Array as PropType<string[]>,
        default() {
            return ["original", "compressed"];
        },
    },
    sourceType: {
        type: Array as PropType<string[]>,
        default() {
            return ["album", "camera"];
        },
    },
    showProgress: {
        type: Boolean,
        default: true,
    },
    //最大上传量
    maxCount: {
        type: Number,
        default: 2,
    },
    isGpt: {
        type: Boolean,
        default: false,
    },
});
const emit = defineEmits<{
    (event: "update:modelValue", value: any): void;
}>();

const appStore = useAppStore();
const { uploadAssistantId } = toRefs(appStore);

const filesLists = ref<Partial<FileData>[]>([]);
const limitLength = computed(() => {
    if (props.returnType === "object") {
        return 1;
    }
    if (!props.limit) {
        return 1;
    }

    return props.limit;
});

const fileType = ref<"image" | "video" | "file" | "all">(props.fileType);

const choose = async () => {
    if (props.disabled) return;

    // 检查文件数量限制
    const remainingCount = limitLength.value - filesLists.value.length;
    if (remainingCount <= 0 && props.returnType === "array" && limitLength.value > 1) {
        uni.showToast({
            title: `您最多选择 ${limitLength.value} 个文件`,
            icon: "none",
        });
        return;
    }

    // 通用的文件选择配置
    const fileChooseConfig = {
        compressed: false,
        sizeType: props.sizeType,
        sourceType: props.sourceType,
        extension: props.fileExtname.length ? props.fileExtname : undefined,
        count: remainingCount,
    };

    // 处理所有文件类型的情况
    if (props.fileType === "all") {
        const FILE_TYPE_MAP = {
            0: "image",
            1: "video",
            2: "file",
        };

        uni.showActionSheet({
            itemList: ["选择图片", "选择视频", "选择文件"],
            success: async (res) => {
                fileType.value = FILE_TYPE_MAP[res.tapIndex as keyof typeof FILE_TYPE_MAP] as
                    | "image"
                    | "video"
                    | "file";
                const filesResult = await chooseFile({
                    type: fileType.value,
                    ...fileChooseConfig,
                });
                chooseFileCallback(filesResult);
            },
        });
        return;
    }

    // 处理单一文件类型的情况
    const filesResult = await chooseFile({
        type: fileType.value as "image" | "video" | "file",
        ...fileChooseConfig,
    });
    chooseFileCallback(filesResult);
};

const chooseFileCallback = async (filesResult: ChooseResult) => {
    const isOne = Number(limitLength.value) === 1;
    if (isOne) {
        filesLists.value = [];
    }
    const { files } = getFilesByExtname(filesResult, props.fileExtname);
    const currentData = [];
    for (let i = 0; i < files.length; i++) {
        if (limitLength.value - filesLists.value.length <= 0) break;
        const filedata = normalizeFileData(files[i]);
        filesLists.value.push(filedata);
        currentData.push(filedata);
    }
    await upload(currentData);
    emitUpdateValue();
};

const emitUpdateValue = () => {
    let value: any = {};
    if (props.returnType === "object") {
        const [item] = filesLists.value;
        if (item.status === "success") {
            value = {
                url: item.url,
                name: item.name,
            };
        }
    } else {
        const data = filesLists.value.filter((item) => item.status === "success");
        value = data.map((item) => ({
            url: item.url,
            name: item.name,
            id: item.id,
        }));
    }
    emit("update:modelValue", value);
};

//上传，并处理并发问题
const upload = (files: FileData[]): Promise<void> => {
    const len = files.length;
    let index = 0;
    let count = 0;
    return new Promise((resolve) => {
        const run = async () => {
            const cur = index++;
            const fileItem = files[cur];
            const currentIndex = filesLists.value.findIndex((item) => item.path === fileItem.path);
            try {
                const { uri, id }: any = props.isGpt
                    ? await uploadGPTFile(
                          {
                              filePath: fileItem.url,
                              formData: {
                                  purpose: "assistants",
                                  assistants_id: uploadAssistantId.value,
                              },
                              header: props.header,
                          },
                          (progress) => {
                              filesLists.value[currentIndex].progress = progress;
                          }
                      )
                    : await uploadFile(
                          fileType.value as "image" | "video" | "file",
                          {
                              filePath: fileItem.url,
                              formData: props.data,
                              header: props.header,
                          },
                          (progress) => {
                              filesLists.value[currentIndex].progress = progress;
                          }
                      );

                filesLists.value[currentIndex].status = "success";
                filesLists.value[currentIndex].url = uri;
                filesLists.value[currentIndex].id = id;
            } catch (error) {
                console.log(error);
                filesLists.value[currentIndex].errMsg = error as string;
                filesLists.value[currentIndex].status = "error";
            }
            count++;
            if (count === len) {
                resolve();
                return;
            }
            if (index < len) {
                run();
            }
        };
        for (let i = 0; i < Math.min(len, props.maxCount); i++) {
            run();
        }
    });
};

const removeFile = (url: string) => {
    const index = filesLists.value.findIndex((item) => item.url === url);
    if (index > -1) {
        filesLists.value.splice(index, 1);
        emitUpdateValue();
    }
};

const retry = async (item: any) => {
    item.status = "ready";
    item.progress = 0;
    await upload([{ ...item }]);
};

const getBtnText = computed(() => {
    switch (props.fileType) {
        case "image":
            return "上传图片";
        case "video":
            return "上传视频";
        default:
            return "上传文件";
    }
});
const setValueItem = (item: any) => {
    if (!item.url) return;
    const isInFiles = filesLists.value.some((file) => file.url == item.url);
    if (!isInFiles) {
        if (!item.name) {
            item.name = getFileName(item.url);
        }
        item.status = "success";
        filesLists.value.push({ ...item });
    }
};
watch(
    () => props.modelValue,
    (newVal) => {
        if (Array.isArray(newVal)) {
            newVal.forEach((item: any) => {
                setValueItem(item);
            });
        } else {
            if (!newVal.url) {
                filesLists.value = [];
            }
            setValueItem(newVal);
        }
    },
    {
        immediate: true,
    }
);

const clear = () => {
    filesLists.value = [];
};

defineExpose({
    clear,
});
</script>

<style lang="scss">
.file-upload {
    // display: flex;
    &.file-upload--line {
        display: flex;
        align-items: center;
        .file-list {
            .file-item {
                margin-top: 0;
            }
        }
    }
    .file-picker {
        display: flex;
        margin-right: 20rpx;
        flex: none;
    }
    .file-list {
        flex: 1;
        min-width: 0;
        .file-item {
            padding: 15rpx 20rpx;
            border-radius: 10rpx;
            background: #f8f9ff;
            font-size: 26rpx;
            display: flex;
            align-items: center;
            margin-top: 15rpx;
            overflow: hidden;
        }
    }
}
</style>
