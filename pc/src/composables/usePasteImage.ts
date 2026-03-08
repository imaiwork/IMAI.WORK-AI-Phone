import { onMounted, onBeforeUnmount, ref, Ref } from "vue";
import { uploadFile } from "@/api/app";
import dayjs from "dayjs";
import feedback from "@/utils/feedback";

export interface FileParams {
    uid?: string | number;
    file?: File | any;
    file_id?: number | string;
    url?: string;
    name?: string;
    size?: number;
    loading: boolean;
    status?: UPLOAD_STATUS;
    create_time?: string | number;
    progress?: number;
    requestKey?: string;
}

export enum UPLOAD_STATUS {
    UPLOADING = "uploading",
    SUCCESS = "done",
    ERROR = "error",
}

interface UsePasteImageOptions {
    handleFun: (params: FileParams) => void;
    canUpload?: () => boolean; // ✅ 改为回调函数，由外部决定是否可以上传
    isPaste?: boolean;
}

export function usePasteImage(options: UsePasteImageOptions) {
    const { handleFun, canUpload, isPaste = false } = options;

    const isUploading = ref<boolean>(false);

    const buildFileParams = (file: File, extra: Partial<FileParams> = {}): FileParams => ({
        uid: Date.now(),
        file,
        name: file.name,
        size: file.size,
        loading: true,
        status: UPLOAD_STATUS.UPLOADING,
        create_time: dayjs().format("YYYY-MM-DD HH:mm:ss"),
        progress: 0,
        ...extra,
    });

    const readFileAsDataURL = (file: File): Promise<string> => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result as string);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    };

    const handleUploadImage = async (file: File, localUrl: string) => {
        // ✅ 通过外部回调判断是否可以上传
        if (canUpload && !canUpload()) {
            feedback.msgError(`文件数量已达上限，无法继续上传`);
            return;
        }

        const requestKey = `upload_${file.name}_${Date.now()}`;
        const fileParams = buildFileParams(file, { url: localUrl, requestKey });

        isUploading.value = true;
        handleFun(fileParams);

        try {
            const uploadedImage = await uploadFile({ file, requestKey }, (progress: number) => {
                handleFun({ ...fileParams, progress });
            });

            handleFun({
                ...fileParams,
                file_id: uploadedImage.id,
                url: uploadedImage.uri,
                loading: false,
                status: UPLOAD_STATUS.SUCCESS,
                progress: 100,
            });
        } catch (error) {
            handleFun({
                ...fileParams,
                url: localUrl,
                loading: false,
                status: UPLOAD_STATUS.ERROR,
                progress: 0,
            });
        } finally {
            isUploading.value = false;
        }
    };

    const handlePaste = async (event: ClipboardEvent) => {
        const items = event.clipboardData?.items;
        if (!items) return;

        for (const item of Array.from(items)) {
            if (!item.type.startsWith("image/")) continue;
            const file = item.getAsFile();
            if (!file) continue;

            try {
                const localUrl = await readFileAsDataURL(file);
                await handleUploadImage(file, localUrl);
            } catch (error) {
                console.error("粘贴图片处理失败:", error);
                feedback.msgError("图片处理失败，请重试");
            }
        }
    };

    onMounted(() => {
        if (isPaste) document.addEventListener("paste", handlePaste);
    });

    onBeforeUnmount(() => {
        if (isPaste) document.removeEventListener("paste", handlePaste);
    });

    return {
        isUploading,
        handleUploadImage,
    };
}
