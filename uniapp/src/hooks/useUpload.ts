import { chooseFile } from "@/components/file-upload/choose-file";
import { uploadFile } from "@/api/app";
import { isImageUrl } from "@/utils/util";

type FileCategory = "image" | "video" | "file" | "all";

export interface UseUploadOptions {
    isTranscode?: boolean;
    isFetchVideoInfo?: boolean;
    clip?: Array<{ video: { duration: number } }>;
    count?: number;
    imageAccept?: string[];
    imageSize?: number;
    imageResolution?: [number, number];
    videoAccept?: string[];
    videoSize?: number;
    videoDuration?: [number, number];
    fileAccept?: string[];
    fileSize?: number;
    sizeType?: Array<"original" | "compressed">;
    sourceType?: Array<"album" | "camera">;
    onSuccess?: (materials: UploadedMaterial[]) => void;
}

export interface UploadedMaterial {
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

interface UploadItem {
    tempFilePath: string;
    name: string;
    size: number;
    duration?: number;
    width?: number;
    height?: number;
    progress: number;
}

const DEFAULT_OPTIONS = {
    count: 9,
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
    } = options;

    const uploadMaterialList = ref<UploadItem[]>([]);
    const showUploadProgress = ref<boolean>(false);

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
        if (isTranscode) return true;

        if (file.size > imageSize * 1024 * 1024) {
            uni.$u.toast(`图片大小不能超过 ${imageSize}M`);
            return false;
        }
        try {
            const { width, height } = await uni.getImageInfo({ src: file.tempFilePath });
            if (width > imageResolution[0] || height > imageResolution[1]) {
                uni.$u.toast(`图片分辨率不能超过 ${imageResolution[0]}×${imageResolution[1]}`);
                return false;
            }
        } catch {
            return false;
        }
        return true;
    };

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
        if (videoDuration.length > 0) {
            const durationOk = file.duration >= videoDuration[0] && file.duration <= videoDuration[1];
            if (!durationOk) {
                uni.$u.toast(`视频时长须在 ${videoDuration[0]}~${videoDuration[1]} 秒之间`);
                return false;
            }
        }
        return true;
    };

    const validateFile = (file: any): boolean => {
        const ext = getExtension(file.name);
        if (fileAccept.length > 0 && !fileAccept.includes(ext)) {
            uni.$u.toast(`文件格式必须是 ${fileAccept.join("、")}`);
            return false;
        }
        // 转码模式下由服务端处理大小限制
        if (!isTranscode && file.size > fileSize * 1024 * 1024) {
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

    const uploadSingleFile = async (item: UploadItem, fileCategory: FileCategory): Promise<UploadedMaterial> => {
        const needTranscode = isTranscode;
        const fileRes: any = await uploadFile(
            fileCategory === "all" ? "file" : fileCategory,
            {
                filePath: item.tempFilePath,
                formData: {
                    ffmpeg: needTranscode ? 1 : 0,
                    generate_thumbnail: 1,
                    fetch_video_info: isFetchVideoInfo ? 1 : 0,
                    "clip[video][duration]": clip[0].video.duration,
                },
            },
            (progress: number) => progressCallback(progress, item.tempFilePath)
        );

        const resolvedType = ["file", "all"].includes(fileCategory) ? getFileTypeByUrl(fileRes.uri) : fileCategory;
        const mType = resolvedType === "video" ? 1 : 2;

        return {
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

    const uploadAndProcessFiles = async (fileCategory: FileCategory): Promise<void> => {
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

            // 初始化上传列表
            uploadMaterialList.value = validFiles.map((file: any) => ({
                tempFilePath: file.tempFilePath,
                name: file.name,
                size: file.size,
                duration: file.duration,
                width: file.width,
                height: file.height,
                progress: 0,
            }));

            showUploadProgress.value = true;

            const needTranscode = isTranscode && (fileCategory === "video" || fileCategory === "image");
            if (needTranscode) {
                uni.showLoading({ title: "素材处理中", mask: true });
            }

            const results = await Promise.all(
                uploadMaterialList.value.map((item) => uploadSingleFile(item, fileCategory))
            );
            uni.hideLoading();

            onSuccess?.(results);
        } catch (error: unknown) {
            const err = error as any;
            uni.hideLoading();

            if (!err?.errMsg?.includes("cancel")) {
                uni.showToast({
                    title: err?.errMsg ?? String(err) ?? "上传失败",
                    icon: "none",
                    duration: 3000,
                });
            }
        } finally {
            showUploadProgress.value = false;
            // uploadMaterialList.value = [];
        }
    };

    return {
        uploadMaterialList,
        showUploadProgress,
        uploadAndProcessFiles,
    };
}
