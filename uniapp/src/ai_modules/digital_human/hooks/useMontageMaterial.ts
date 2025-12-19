import { montageConfig } from "../config";
import { useAppStore } from "@/stores/app";
import { chooseFile } from "@/components/file-upload/choose-file";
import { getVideoTranscodeResult, uploadFile, videoTranscode } from "@/api/app";
import { addMaterialLibrary, deleteMaterialLibrary } from "@/api/material";
import usePolling from "@/hooks/usePolling";

enum MaterialTypeEnum {
    VIDEO = 2,
    IMAGE = 1,
    MUSIC = 6,
}

export default function useMontageMaterial(options: {
    count?: number;
    imageAccept?: string[];
    imageSize?: number;
    imageResolution?: number[];
    videoAccept?: string[];
    videoSize?: number;
    videoDuration?: number[];
    videoResolution?: number[];
    fileAccept?: string[];
    onSuccess?: (materials: any[]) => void;
}) {
    const appStore = useAppStore();

    const {
        count = 9,
        imageAccept = montageConfig.imageAccept,
        imageSize = montageConfig.imageSize,
        imageResolution = montageConfig.imageResolution,
        videoAccept = montageConfig.videoAccept,
        videoSize = montageConfig.videoSize,
        videoDuration = montageConfig.videoDuration,
        videoResolution = montageConfig.videoResolution,
        fileAccept = montageConfig.fileAccept,
        onSuccess,
    } = options;
    const isOssTranscode = computed(() => appStore.config.is_oss_transcode);
    const uploadMaterialList = ref<any[]>([]);
    const showUploadProgress = ref(false);
    // 视频转码
    const handleVideoTranscode = async (url: string) => {
        return new Promise(async (resolve: any, reject: any) => {
            try {
                const data = await videoTranscode({
                    video_url: url,
                });
                const { start, end } = usePolling(async () => {
                    try {
                        const result = await getVideoTranscodeResult({
                            jobid: data.jobid,
                        });
                        if (result.state == "TranscodeSuccess") {
                            end();
                            resolve(true);
                        } else if (result.state == "TranscodeFail" || result.state == "TranscodeCancelled") {
                            end();
                            resolve(false);
                        }
                    } catch (error: any) {
                        end();
                        resolve(false);
                    }
                }, {});
                await start();
            } catch (error: any) {
                resolve(false);
            }
        });
    };

    /**
     * 统一处理文件选择和上传的函数
     * @param fileType - 文件类型 'image' 或 'video'
     */
    const uploadAndProcessFiles = async (fileType: "image" | "video" | "all") => {
        uploadMaterialList.value = [];
        try {
            const isImage = fileType === "image";
            const isVideo = fileType === "video";
            const isAll = fileType === "all";
            const extension = isAll ? fileAccept : isImage ? imageAccept : videoAccept;
            const { tempFiles } = await chooseFile({
                type: fileType,
                count,
                sourceType: ["album"],
                extension,
            });
            const fileList = [];
            for (const file of tempFiles) {
                const fileExt = file.name.split(".").pop();

                if (isImage || (isAll && fileExt && imageAccept.includes(fileExt))) {
                    try {
                        // 1. 获取图片宽高
                        const { width, height } = await uni.getImageInfo({
                            src: file.tempFilePath,
                        });
                        if (!imageAccept.includes(file.name.split(".").pop())) {
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
                        fileList.push({ ...file, materialType: "image" });
                    } catch (error) {
                        continue;
                    }
                } else if (isVideo || (isAll && fileExt && videoAccept.includes(fileExt))) {
                    const durationOk = file.duration >= videoDuration[0] && file.duration <= videoDuration[1];
                    if (!durationOk) {
                        uni.$u.toast(`视频时长不能超过${videoDuration[1]}秒`);
                        continue;
                    }
                    if (file.size > videoSize * 1024 * 1024) {
                        uni.$u.toast(`视频大小不能超过${videoSize}M`);
                        continue;
                    }
                    if (!videoAccept.includes(file.name.split(".").pop())) {
                        uni.$u.toast(`视频格式必须是${videoAccept.join("、")}`);
                        continue;
                    }
                    if (file.width > videoResolution[0] || file.height > videoResolution[1]) {
                        uni.$u.toast(
                            `视频单边分辨率不能超过宽：【${videoResolution[0]}】或高：【${videoResolution[1]}】`
                        );
                        continue;
                    }
                    fileList.push({ ...file, materialType: "video" });
                }
            }
            if (fileList.length === 0) {
                uni.$u.toast(`所选${isImage ? "图片" : "视频"}素材均不符合条件，重新上传`);
                return;
            }

            uploadMaterialList.value = fileList.map((file: any) => ({ ...file, progress: 0 }));
            showUploadProgress.value = true;

            const uploadedFilesData = [];
            for (const item of uploadMaterialList.value) {
                const coverRes: any = isImage ? null : await uploadFile("image", { filePath: item.thumbTempFilePath });
                const fileRes: any = await uploadFile(
                    isAll ? "file" : fileType,
                    { filePath: item.tempFilePath },
                    (progress) => progressCallback(progress, item)
                );
                uploadedFilesData.push({ item, coverRes: isImage ? fileRes : coverRes, fileRes });
            }

            const addedMaterials: any[] = [];
            async function handleAddMaterial(result: any) {
                try {
                    const { item, coverRes, fileRes } = result;

                    addedMaterials.push({
                        name: item.name,
                        url: fileRes.uri,
                        type: item.materialType,
                        pic: coverRes.uri,
                        duration: item.duration,
                    });
                    const addRes = await addMaterialLibrary({
                        name: item.name,
                        size: item.size,
                        type: 0,
                        sort: 0,
                        pic: coverRes?.uri,
                        m_type: item.materialType === "image" ? MaterialTypeEnum.IMAGE : MaterialTypeEnum.VIDEO,
                        content: fileRes.uri,
                        duration: item.duration || 0,
                    });

                    if (addRes.id) {
                        addedMaterials[addedMaterials.length - 1].id = addRes.id;
                    }
                } catch (error) {
                    console.error("添加素材失败:", error);
                }
            }

            if (fileType === "video" || fileType === "all") {
                for (const [index, data] of uploadedFilesData.entries()) {
                    const { fileRes } = data;
                    if (isOssTranscode.value) {
                        uni.showLoading({
                            title: `转码中(${index + 1}/${uploadedFilesData.length})...`,
                            mask: true,
                        });
                        try {
                            await handleVideoTranscode(fileRes.uri);
                            uni.hideLoading();
                        } catch (error) {
                            uni.hideLoading();
                            showUploadProgress.value = false;
                        }
                    }
                    handleAddMaterial(data);
                }
                uni.hideLoading();
            } else {
                for (const data of uploadedFilesData) {
                    handleAddMaterial(data);
                }
            }

            if (uploadMaterialList.value.every((item) => item.progress === 100)) {
                showUploadProgress.value = false;
                uni.$u.toast(`上传成功`);
                onSuccess?.(addedMaterials);
            }
        } catch (error) {
            uni.$u.toast(error);
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

    // 素材删除
    const handleDeleteMaterial = (id: number) => {
        deleteMaterialLibrary({ id });
    };

    return {
        uploadMaterialList,
        showUploadProgress,
        uploadAndProcessFiles,
        handleDeleteMaterial,
    };
}
