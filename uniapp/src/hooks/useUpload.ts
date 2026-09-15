import { chooseFile } from "@/components/file-upload/choose-file";
import { uploadFile } from "@/api/app";
import { isImageUrl } from "@/utils/util";

type FileCategory = "image" | "video" | "file" | "all";

export interface UseUploadOptions {
    isTranscode?: boolean | (() => boolean);
    isFetchVideoInfo?: boolean;
    clip?: Array<{ video: { duration: number } }>;
    count?: number;
    imageAccept?: string[];
    imageSize?: number;
    imageResolution?: [number, number];
    videoAccept?: string[];
    videoSize?: number;
    videoDuration?: [number, number] | (() => [number, number]);
    fileAccept?: string[];
    fileSize?: number;
    sizeType?: Array<"original" | "compressed">;
    sourceType?: Array<"album" | "camera">;
    onSuccess?: (materials: UploadedMaterial[]) => void;
    onPartialFail?: (failedItems: UploadFailedItem[]) => void;
}

export interface UploadedMaterial {
    id?: number;
    name: string;
    url: string;
    size: number;
    type: string;
    pic: string;
    duration: number;
    width: number;
    height: number;
    m_type: number;
}

export interface UploadFailedItem {
    name: string;
    reason: string;
}

interface UploadItem {
    tempFilePath: string;
    name: string;
    size: number;
    duration?: number;
    width?: number;
    height?: number;
    progress: number;
    status: "pending" | "success" | "fail";
}

/** 失败明细最多列几条，超出只报总数，避免弹窗被刷屏 */
const FAILED_DETAIL_LIMIT = 5;

const DEFAULT_OPTIONS = {
    // #ifdef MP-WEIXIN
    count: 20,
    // #endif
    // #ifdef APP
    count: 99,
    // #endif
    imageAccept: ["jpg", "png", "jpeg"] as string[],
    imageSize: 20,
    imageResolution: [2000, 2000] as [number, number],
    videoAccept: ["mp4", "mov"] as string[],
    videoSize: 200,
    videoDuration: [1, 600] as [number, number],
    fileAccept: ["jpg", "png", "jpeg", "mp4", "mov"] as string[],
    fileSize: 200,
    sizeType: ["original", "compressed"] as Array<"original" | "compressed">,
    sourceType: ["album"] as Array<"album" | "camera">,
};

const getExtension = (filename: string): string => filename.split(".").pop()?.toLowerCase() ?? "";

const getFileTypeByUrl = (url: string): string => {
    const ext = getExtension(url);
    if (["jpg", "png", "jpeg", "webp"].includes(ext)) return "image";
    if (["mp4", "mov", "avi", "mkv"].includes(ext)) return "video";
    if (["mp3", "wav", "m4a", "aac"].includes(ext)) return "audio";
    return "file";
};

export default function useUpload(options: UseUploadOptions = {}) {
    const {
        isTranscode = false,
        isFetchVideoInfo = false,
        clip = [{ video: { duration: 0 } }],
        count = DEFAULT_OPTIONS.count,
        imageAccept = DEFAULT_OPTIONS.imageAccept,
        imageSize = DEFAULT_OPTIONS.imageSize,
        imageResolution = DEFAULT_OPTIONS.imageResolution,
        videoAccept = DEFAULT_OPTIONS.videoAccept,
        videoSize = DEFAULT_OPTIONS.videoSize,
        videoDuration = DEFAULT_OPTIONS.videoDuration,
        fileAccept = DEFAULT_OPTIONS.fileAccept,
        fileSize = DEFAULT_OPTIONS.fileSize,
        sizeType = DEFAULT_OPTIONS.sizeType,
        sourceType = DEFAULT_OPTIONS.sourceType,
        onSuccess,
        onPartialFail,
    } = options;

    const uploadMaterialList = ref<UploadItem[]>([]);
    const showUploadProgress = ref<boolean>(false);

    // isTranscode 支持函数形式，按当前上传场景动态决定是否转码
    const getIsTranscode = (): boolean => (typeof isTranscode === "function" ? isTranscode() : isTranscode);

    const progressCallback = (progress: number, tempFilePath: string): void => {
        const index = uploadMaterialList.value.findIndex((item) => item.tempFilePath === tempFilePath);
        if (index !== -1) {
            uploadMaterialList.value[index].progress = progress;
        }
    };

    const validateImage = async (file: any): Promise<boolean> => {
        const ext = getExtension(file.name);
        if (imageAccept.length > 0 && !imageAccept.includes(ext)) {
            uni.$u.toast(`图片格式必须是 ${imageAccept.join("、")}`);
            return false;
        }
        if (file.size > imageSize * 1024 * 1024) {
            uni.$u.toast(`图片大小不能超过 ${imageSize}M`);
            return false;
        }
        if (getIsTranscode()) return true;

        try {
            const { width, height } = await uni.getImageInfo({ src: file.tempFilePath });
            if (width > imageResolution[0] || height > imageResolution[1]) {
                uni.$u.toast(`图片分辨率不能超过 ${imageResolution[0]}×${imageResolution[1]}`);
                return false;
            }
        } catch {
            // 不转码的场景（原图模式/直发）才会走到这里；读不出图片信息就丢文件，
            // 不给提示的话用户在相册选完什么都不会发生
            uni.$u.toast("读取图片信息失败，请更换文件重试");
            return false;
        }
        return true;
    };

    const getVideoDurationLimit = (): [number, number] =>
        typeof videoDuration === "function" ? videoDuration() : videoDuration;

    const validateVideo = (file: any): boolean => {
        const ext = getExtension(file.name);
        if (videoAccept.length > 0 && !videoAccept.includes(ext)) {
            uni.$u.toast(`视频格式必须是 ${videoAccept.join("、")}`);
            return false;
        }
        if (file.size > videoSize * 1024 * 1024) {
            uni.$u.toast(`视频大小不能超过 ${videoSize}M`);
            return false;
        }
        const durationLimit = getVideoDurationLimit();
        if (durationLimit.length > 0) {
            const durationOk = file.duration >= durationLimit[0] && file.duration <= durationLimit[1];
            if (!durationOk) {
                uni.$u.toast(`视频时长须在 ${durationLimit[0]}~${durationLimit[1]} 秒之间`);
                return false;
            }
        }
        return true;
    };

    const validateFile = (file: any): boolean => {
        const ext = getExtension(file.name || file.tempFilePath || file.path || "");
        if (fileAccept.length > 0 && !fileAccept.includes(ext)) {
            const formats = fileAccept.map((item) => item.toUpperCase()).join("、");
            uni.$u.toast(`仅支持 ${formats} 格式`);
            return false;
        }
        if (!getIsTranscode() && file.size > fileSize * 1024 * 1024) {
            uni.$u.toast(`文件大小不能超过 ${fileSize}M`);
            return false;
        }
        return true;
    };

    const filterFiles = async (tempFiles: any[], fileCategory: FileCategory): Promise<any[]> => {
        const result: any[] = [];
        for (const file of tempFiles) {
            let valid = false;
            if (fileCategory === "image") {
                valid = await validateImage(file);
            } else if (fileCategory === "video") {
                valid = validateVideo(file);
            } else {
                valid = validateFile(file);
            }
            if (valid) result.push(file);
        }
        return result;
    };

    const isImageUploadItem = (item: UploadItem, fileCategory: FileCategory): boolean => {
        if (fileCategory === "image") return true;
        if (fileCategory === "video") return false;
        const ext = getExtension(item.name || item.tempFilePath);
        return ["jpg", "png", "jpeg", "webp"].includes(ext);
    };

    const uploadSingleFile = async (
        item: UploadItem,
        fileCategory: FileCategory,
        extraFormData: Record<string, any> = {},
    ): Promise<UploadedMaterial> => {
        const needTranscode = getIsTranscode();
        const formData: Record<string, any> = {
            ffmpeg: needTranscode ? 1 : 0,
            generate_thumbnail: 1,
            fetch_video_info: isFetchVideoInfo ? 1 : 0,
            "clip[video][duration]": clip[0].video.duration,
            ...extraFormData,
        };
        // 图片不参与视频切割：业务侧传入的 ffmpeg=2 对图片强制降为 1
        if (isImageUploadItem(item, fileCategory) && Number(formData.ffmpeg) === 2) {
            formData.ffmpeg = 1;
            delete formData.persona_id;
        }
        const fileRes: any = await uploadFile(
            fileCategory === "all" ? "file" : fileCategory,
            {
                filePath: item.tempFilePath,
                formData,
            },
            (progress: number) => progressCallback(progress, item.tempFilePath),
        );

        // 上传接口业务失败会 reject 到外层；这里再兜一层"200 但没返回可用文件"的情况，
        // 不显式抛错的话下面会拿 undefined 拼出一条假的成功记录，用户只会看到"传了个寂寞"
        const uploadedUri = typeof fileRes?.uri === "string" ? fileRes.uri.trim() : "";
        if (!uploadedUri) {
            throw new Error("上传失败，未返回可用文件");
        }

        const resolvedType = ["file", "all"].includes(fileCategory) ? getFileTypeByUrl(fileRes.uri) : fileCategory;
        const mType = resolvedType === "video" ? 1 : 2;

        return {
            id: fileRes.id,
            name: item.name,
            url: fileRes.uri,
            size: item.size,
            type: resolvedType,
            pic: isImageUrl(fileRes.uri) ? fileRes.uri : fileRes.thumbnail_path ?? "",
            duration: fileRes.duration ?? item.duration ?? 0,
            width: fileRes.width ?? item.width ?? 0,
            height: fileRes.height ?? item.height ?? 0,
            m_type: mType,
        };
    };

    // 默认兜底提示：一条时用 toast，多条时列出文件名与原因，避免只弹最后一个错误
    const showFailedDetail = (failedList: UploadFailedItem[]): void => {
        if (!failedList.length) return;
        if (failedList.length === 1) {
            uni.$u.toast(failedList[0].reason || "上传失败");
            return;
        }
        const lines = failedList
            .slice(0, FAILED_DETAIL_LIMIT)
            .map((item) => `${item.name}：${item.reason || "上传失败"}`);
        if (failedList.length > FAILED_DETAIL_LIMIT) lines.push(`…等 ${failedList.length} 个文件`);
        uni.showModal({
            title: `${failedList.length} 个文件上传失败`,
            content: lines.join("\n"),
            showCancel: false,
            confirmText: "知道了",
        });
    };

    const uploadAndProcessFiles = async (
        fileCategory: FileCategory,
        extraFormData: Record<string, any> = {},
    ): Promise<void> => {
        uploadMaterialList.value = [];

        try {
            const extension: string[] =
                fileCategory === "image"
                    ? [...imageAccept]
                    : fileCategory === "video"
                    ? [...videoAccept]
                    : [...fileAccept];

            const { tempFiles } = await chooseFile({
                type: fileCategory,
                count,
                sourceType: [...sourceType],
                extension,
                sizeType: [...sizeType],
            });

            const validFiles = await filterFiles(tempFiles, fileCategory);
            if (validFiles.length === 0) return;

            uploadMaterialList.value = validFiles.map((file: any) => ({
                tempFilePath: file.tempFilePath,
                name: file.name,
                size: file.size,
                duration: file.duration,
                width: file.width,
                height: file.height,
                progress: 0,
                status: "pending" as const,
            }));

            showUploadProgress.value = true;

            const settledResults = await Promise.allSettled(
                uploadMaterialList.value.map((item, index) =>
                    uploadSingleFile(item, fileCategory, extraFormData)
                        .then((result) => {
                            uploadMaterialList.value[index].status = "success";
                            uploadMaterialList.value[index].progress = 100;
                            return result;
                        })
                        .catch((err) => {
                            uploadMaterialList.value[index].status = "fail";
                            throw err;
                        }),
                ),
            );

            const successList: UploadedMaterial[] = [];
            const failedList: UploadFailedItem[] = [];

            settledResults.forEach((result, index) => {
                if (result.status === "fulfilled") {
                    successList.push(result.value);
                } else {
                    failedList.push({
                        name: uploadMaterialList.value[index].name,
                        reason: (result.reason as any)?.errMsg ?? String(result.reason) ?? "上传失败",
                    });
                }
            });

            if (successList.length > 0) {
                onSuccess?.(successList);
            }

            // 失败明细：业务侧接管就交给它（如原图模式要列出被拦下的文件与原因），
            // 没接管则统一弹一次聚合提示——上传失败必须让用户知道原因，不能静默
            if (failedList.length > 0) {
                if (onPartialFail) {
                    onPartialFail(failedList);
                } else {
                    showFailedDetail(failedList);
                }
            }
        } catch (error: unknown) {
            const err = error as any;
            if (!err?.errMsg?.includes("cancel")) {
                uni.showToast({
                    title: err?.errMsg ?? String(err) ?? "上传失败",
                    icon: "none",
                    duration: 3000,
                });
            }
        } finally {
            showUploadProgress.value = false;
        }
    };

    return {
        uploadMaterialList,
        showUploadProgress,
        uploadAndProcessFiles,
    };
}
