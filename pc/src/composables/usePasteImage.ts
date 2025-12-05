import { onMounted, onBeforeUnmount, ref, Ref } from "vue";
import { uploadGPTFile } from "@/api/app";
import dayjs from "dayjs";
import { useAppStore } from "@/stores/app";

export interface FileParams {
    uid?: string | number;
    file?: File | any;
    file_id?: number | string;
    url?: string;
    name?: string;
    size?: number;
    loading: boolean;
    status?: UPLOAD_STATUS; //上传状态 1是成功 2是等待 3失败
    create_time?: string | number;
    progress?: number; // 上传进度，0-100
    requestKey?: string; // 请求标识，用于取消上传
}

// 上传状态
export enum UPLOAD_STATUS {
    UPLOADING = "uploading",
    SUCCESS = "done",
    ERROR = "error",
}

interface UsePasteImageOptions {
    handleFun: (params: FileParams) => void;
    limit?: number;
    fileLists?: any[];
    isPaste?: boolean;
}

export function usePasteImage(options: UsePasteImageOptions) {
    const appStore = useAppStore();

    const isUploading = ref<boolean>(false);
    const imageUrl = ref<string>("");
    const { handleFun, limit = Infinity, fileLists = [] } = options;
    const createTime = ref(null);
    const handlePaste = (event: ClipboardEvent) => {
        createTime.value = dayjs().format("YYYY-MM-DD HH:mm:ss");
        const items = event.clipboardData?.items;
        if (items) {
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.startsWith("image/")) {
                    const file = items[i].getAsFile();
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = () => {
                            isUploading.value = true;
                            imageUrl.value = reader.result as string;
                            handleFun({
                                file,
                                url: imageUrl.value,
                                loading: isUploading.value,
                                status: UPLOAD_STATUS.UPLOADING,
                                create_time: createTime.value,
                            });
                            handleUploadImage(file);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            }
        }
    };

    const handleUploadImage = async (file: File) => {
        // 生成请求标识，在整个函数作用域内可用
        const requestKey = `upload_${file.name}_${Date.now()}`;

        try {
            const uploadedImage = await new Promise<any>((resolve, reject) => {
                if (fileLists.length > limit) {
                    feedback.msgError(`无法上传"${file.name}"，一次最多可上传 ${limit} 个文件`);
                    reject("");
                    return;
                }

                // 创建临时对象用于存储进度
                const tempProgress = { value: 0 };

                // 创建文件参数对象，包含请求标识
                const fileParams: FileParams = {
                    file,
                    url: imageUrl.value,
                    loading: isUploading.value,
                    status: UPLOAD_STATUS.UPLOADING,
                    create_time: createTime.value,
                    progress: 0,
                    requestKey: requestKey,
                };

                // 先通知处理函数，更新文件状态
                handleFun(fileParams);

                uploadGPTFile({
                    file,
                    purpose: "assistants",
                    requestKey: requestKey, // 传递请求标识
                    onProgress: (progress: number) => {
                        tempProgress.value = progress;
                        // 更新进度
                        fileParams.progress = progress;
                        handleFun(fileParams);
                    },
                })
                    .then((res) => {
                        resolve(res);
                    })
                    .catch((err) => {
                        reject(err);
                    });
            });
            isUploading.value = false;
            // 保持请求标识一致，更新上传成功的状态
            handleFun({
                file,
                file_id: uploadedImage.id,
                url: uploadedImage.uri,
                loading: isUploading.value,
                status: UPLOAD_STATUS.SUCCESS,
                create_time: createTime.value,
                progress: 100, // 上传完成，进度设为100%
                requestKey: requestKey, // 使用函数作用域内的requestKey
            });
        } catch (error) {
            // 保持请求标识一致，更新上传失败的状态
            handleFun({
                file,
                url: imageUrl.value,
                loading: false,
                status: UPLOAD_STATUS.ERROR,
                create_time: createTime.value,
                progress: 0, // 上传失败，进度重置为0
                requestKey: requestKey, // 使用函数作用域内的requestKey
            });
        } finally {
            isUploading.value = false;
        }
    };

    onMounted(() => {
        options.isPaste && document.addEventListener("paste", handlePaste);
    });

    onBeforeUnmount(() => {
        options.isPaste && document.removeEventListener("paste", handlePaste);
    });

    return {
        isUploading,
    };
}
