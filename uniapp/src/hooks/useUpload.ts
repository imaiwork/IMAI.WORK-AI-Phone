import { chooseFile } from "@/components/file-upload/choose-file";
import { uploadFile } from "@/api/app";

export default function useUpload(options: {
    count?: number;
    imageAccept?: string[];
    imageSize?: number;
    imageResolution?: number[];
    videoAccept?: string[];
    videoSize?: number;
    videoDuration?: number[];
    fileAccept?: string[];
    fileSize?: number;
    onSuccess?: (materials: any[]) => void;
}) {
    const {
        count = 9,
        imageAccept = [],
        imageSize = 20,
        imageResolution = [],
        videoAccept = [],
        videoSize = 200,
        videoDuration = [1, 600],
        fileAccept = [],
        fileSize = 200,
        onSuccess,
    } = options;
    const uploadMaterialList = ref<any[]>([]);
    const showUploadProgress = ref(false);

    const uploadAndProcessFiles = async (fileType: "image" | "video" | "file" | "all") => {
        uploadMaterialList.value = [];
        try {
            const isImage = fileType === "image";
            const isVideo = fileType === "video";
            const isFile = fileType === "file";
            const isAll = fileType === "all";
            const { tempFiles } = await chooseFile({
                type: fileType,
                count,
                sourceType: ["album"],
                extension: isImage ? imageAccept : isVideo ? videoAccept : fileAccept,
            });

            // 先过滤图片
            const fileList = [];
            for (const file of tempFiles) {
                if (isImage) {
                    try {
                        // 1. 获取图片宽高
                        const { width, height } = await uni.getImageInfo({
                            src: file.tempFilePath,
                        });
                        if (imageAccept.length > 0 && !imageAccept.includes(file.name.split(".").pop())) {
                            uni.$u.toast(`图片格式必须是${imageAccept.join("、")}`);
                            continue;
                        }
                        if (width > imageResolution[0] || height > imageResolution[1]) {
                            uni.$u.toast(`图片分辨率不能超过${imageResolution[0]}*${imageResolution[1]}`);
                            continue;
                        }
                        if (file.size > imageSize * 1024 * 1024) {
                            uni.$u.toast(`图片大小不能超过${imageSize}M`);
                            continue;
                        }
                        fileList.push(file);
                    } catch (error) {
                        continue;
                    }
                }
                if (isVideo) {
                    const durationOk = file.duration >= videoDuration[0] && file.duration <= videoDuration[1];
                    if (!durationOk && videoDuration.length > 0) {
                        uni.$u.toast(`视频时长不能小于${videoDuration[0]}秒，不能超过${videoDuration[1]}秒`);
                        continue;
                    }
                    if (file.size > videoSize * 1024 * 1024) {
                        uni.$u.toast(`视频大小不能超过${videoSize}M`);
                        continue;
                    }
                    if (videoAccept.length > 0 && !videoAccept.includes(file.name.split(".").pop())) {
                        uni.$u.toast(`视频格式必须是${videoAccept.join("、")}`);
                        continue;
                    }
                    fileList.push(file);
                }
                if (isFile || isAll) {
                    if (fileAccept.length > 0 && !fileAccept.includes(file.name.split(".").pop())) {
                        uni.$u.toast(`文件格式必须是${fileAccept.join("、")}`);
                        continue;
                    }
                    if (file.size > fileSize * 1024 * 1024) {
                        uni.$u.toast(`文件大小不能超过${fileSize}M`);
                        continue;
                    }
                    fileList.push(file);
                }
            }
            if (fileList.length === 0) {
                return;
            }

            uploadMaterialList.value = fileList.map((file: any) => ({ ...file, progress: 0 }));
            showUploadProgress.value = true;
            const uploadedFilesData = [];
            for (const item of uploadMaterialList.value) {
                const coverRes: any = isVideo ? await uploadFile("image", { filePath: item.thumbTempFilePath }) : {};

                const fileRes: any = await uploadFile(
                    isAll ? "file" : fileType,
                    { filePath: item.tempFilePath },
                    (progress) => progressCallback(progress, item)
                );

                uploadedFilesData.push({
                    name: item.name,
                    url: fileRes.uri,
                    size: item.size,
                    type: fileType,
                    pic: isImage ? fileRes.uri : coverRes.uri,
                    duration: item.duration || 0,
                    width: item.width || 0,
                    height: item.height || 0,
                });
            }
            if (uploadMaterialList.value.every((item) => item.progress === 100)) {
                showUploadProgress.value = false;
                onSuccess?.(uploadedFilesData);
            }
        } catch (error: any) {
            if (!error?.errMsg?.includes("cancel")) {
                uni.$u.toast(error?.errMsg || error);
            }
            uploadMaterialList.value = [];
            showUploadProgress.value = false;
        }
    };

    /**
     * 上传进度回调函数
     * @param progress - 进度值 (0-100)
     * @param options - 上传选项，包含 filePath
     */
    const progressCallback = (progress: number, options: { tempFilePath: string }) => {
        const targetIndex = uploadMaterialList.value.findIndex(
            (material) => material.tempFilePath === options.tempFilePath
        );
        if (targetIndex !== -1) {
            const newList = [...uploadMaterialList.value];
            newList[targetIndex] = {
                ...newList[targetIndex],
                progress: progress,
            };
            uploadMaterialList.value = newList;
        }
    };

    return {
        uploadMaterialList,
        showUploadProgress,
        uploadAndProcessFiles,
    };
}
