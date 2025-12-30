import { ChooseResult, chooseFile } from "@/components/file-upload/choose-file";
import { uploadImage, uploadFile } from "@/api/app";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";

interface Options {
    size?: number; // 单位M
    widthResolution?: number[];
    heightResolution?: number[];
    duration?: number[];
    extension?: any[];
    maxDuration?: number; // 最大时长，单位秒
    sizeType?: Array<"compressed" | "original">;
    onSuccess?: (res: any) => void;
    onProgress?: (res: any) => void;
    onError?: (e: any) => void;
}

export const commonUploadLimit = {
    size: 300,
    // 最小分辨率
    minWidthResolution: 360,
    // 最大分辨率
    maxWidthResolution: 2000,
    minHeightResolution: 360,
    maxHeightResolution: 2000,
    // 最小时长
    videoMinDuration: 30,
    // 最大时长
    videoMaxDuration: 60,
};

// 上传限制
export const uploadLimit: any = {
    [DigitalHumanModelVersionEnum.STANDARD]: {
        size: 100,
        // 最小分辨率
        minResolution: 480,
        // 最大分辨率
        maxResolution: 1080,
        // 最小时长
        videoMinDuration: 15,
        // 最大时长
        videoMaxDuration: 60,
    },
    [DigitalHumanModelVersionEnum.SUPER]: {
        size: 500,
        // 最小分辨率
        minResolution: 640,
        // 最大分辨率
        maxResolution: 2048,
        // 最小时长
        videoMinDuration: 2,
        // 最大时长
        videoMaxDuration: 120,
    },
    [DigitalHumanModelVersionEnum.ADVANCED]: commonUploadLimit,
    [DigitalHumanModelVersionEnum.ELITE]: commonUploadLimit,
    [DigitalHumanModelVersionEnum.CHANJING]: {
        size: 300,
        minResolution: 2000,
        maxResolution: 2000,
        videoMinDuration: 30,
        videoMaxDuration: 300,
    },
    //闪剪
    [DigitalHumanModelVersionEnum.SHANJIAN]: {
        size: 300,
        minResolution: 2000,
        maxResolution: 2000,
        videoMinDuration: 10,
        videoMaxDuration: 30,
    },
};

export const useUpload = (options: Options) => {
    const {
        size = 100,
        widthResolution = [0, 2048],
        heightResolution = [0, 2048],
        duration = [15, 60],
        extension = ["mp4", "mov"],
        maxDuration = 10,
        sizeType = ["original", "compressed"],
        onSuccess,
        onError,
        onProgress,
    } = options;
    const uploadResult = reactive<Record<string, any>>({
        url: "",
        pic: "",
        seconds: 0,
        duration: "00:00",
        width: 0,
        height: 0,
    });

    const upload = async () => {
        try {
            const filesResult = await chooseFile({
                count: 1,
                type: "video",
                camera: "front",
                sourceType: ["album"],
                extension,
                maxDuration,
                sizeType,
            });
            chooseFileCallback(filesResult);
        } catch (error) {
            onError?.(error);
        }
    };

    const chooseFileCallback = async (filesResult: ChooseResult) => {
        const { tempFiles } = filesResult;
        const file = tempFiles[0];
        const fileSize = file.size;
        const fileWidth = file.width;
        const fileHeight = file.height;

        uploadResult.width = fileWidth;
        uploadResult.height = fileHeight;
        const fileDuration = file.duration;
        const isWidthResolutionValid = fileWidth >= widthResolution[0] && fileWidth <= widthResolution[1];
        const isHeightResolutionValid = fileHeight >= heightResolution[0] && fileHeight <= heightResolution[1];
        const isDurationValid = fileDuration >= duration[0] && fileDuration <= duration[1];

        if (fileSize > size * 1024 * 1024) {
            uni.showToast({
                title: `视频大小不能超过${size}M`,
                icon: "none",
                duration: 4000,
            });
            return;
        } else if (!isWidthResolutionValid || !isHeightResolutionValid) {
            uni.showToast({
                title: `上传视频分辨率不能满足${widthResolution[0]}-${widthResolution[1]}x${heightResolution[0]}-${heightResolution[1]}`,
                icon: "none",
                duration: 4000,
            });
            return;
        } else if (!isDurationValid) {
            uni.showToast({
                title: `上传视频时长不能小于${duration[0]}秒或大于${duration[1]}秒`,
                icon: "none",
                duration: 4000,
            });
            return;
        }
        try {
            await uploadImageFn(file.thumbTempFilePath);
            await uploadVideo(file.tempFilePath);
            onSuccess?.(uploadResult);
        } catch (error: any) {
            if (
                error.errMsg &&
                error.errMsg === "chooseMedia:fail api scope is not declared in the privacy agreement"
            ) {
                uni.$u.toast("请完善隐私协议，否则无法使用");
            }
            if (error.indexOf("abort") == -1) {
                uni.$u.toast("上传失败");
            }
            onError?.({
                type: "video",
                error: error,
            });
        }
    };

    const uploadVideo = async (file: string) => {
        const { uri }: any = await uploadFile(
            "video",
            {
                filePath: file,
            },
            (e) => {
                onProgress?.({ type: "video", progress: e });
            }
        );
        uploadResult.url = uri;
    };

    const uploadImageFn = async (file: string) => {
        const { uri }: any = await uploadImage(file, "", "", (e) => {
            onProgress?.({ type: "image", progress: e });
        });
        uploadResult.pic = uri;
    };
    return {
        upload,
        uploadResult,
    };
};
