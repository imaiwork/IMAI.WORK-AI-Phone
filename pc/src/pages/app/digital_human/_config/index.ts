import { DigitalHumanModelVersionEnum, MontageStylesChooseType } from "../_enums";
// 高级版和尊享版公共上传限制
export const commonUploadLimit = {
    size: 300,
    // 最小分辨率
    minResolution: 360,
    // 最大分辨率
    maxResolution: 2000,
    // 最小时长
    videoMinDuration: 5,
    // 最大时长
    videoMaxDuration: 600,
};

// 口播文案字数限制
export const copywritingLimit = {
    minLength: 3,
    maxLength: 1000,
};

// 上传限制
export const uploadLimit: Partial<Record<DigitalHumanModelVersionEnum, any>> = {
    [DigitalHumanModelVersionEnum.STANDARD]: {
        size: 100,
        videoResolution: [
            [360, 2000],
            [360, 2000],
        ],
        // 最小分辨率
        minResolution: 360,
        // 最大分辨率
        maxResolution: 2000,
        // 最小时长
        videoMinDuration: 15,
        // 最大时长
        videoMaxDuration: 60,
    },
    [DigitalHumanModelVersionEnum.SUPER]: {
        size: 500,
        videoResolution: [
            [640, 2048],
            [640, 2048],
        ],
        // 最小时长
        videoMinDuration: 2,
        // 最大时长
        videoMaxDuration: 120,
    },
    [DigitalHumanModelVersionEnum.ADVANCED]: commonUploadLimit,
    [DigitalHumanModelVersionEnum.ELITE]: commonUploadLimit,
    [DigitalHumanModelVersionEnum.SHANJIAN]: commonUploadLimit,
    [DigitalHumanModelVersionEnum.CHANJING]: {
        size: 2000,
        videoResolution: [
            [360, 2000],
            [360, 2000],
        ],
        videoMinDuration: 30,
        videoMaxDuration: 300,
    },
};

const imageAccept = [".webp", ".jpg", ".png"];
const videoAccept = [".mp4", ".mov"];
export const montageUploadConfig = {
    isTranscode: false,
    count: 9,
    imageAccept,
    videoAccept,
    imageSize: 20,
    imageResolution: [
        [1, 99999],
        [1, 99999],
    ],
    videoSize: 200,
    videoDuration: [1, 59],
    videoResolution: [
        [1, 99999],
        [1, 99999],
    ],
    fileAccept: [...imageAccept, ...videoAccept],
    fileSize: 100,
    imageDuration: 2,
    materialTotalDuration: 5,
    /** 素材库 duration 是整秒（真实 3.6s 存成 3），累加会低估，每条视频补 1s 兜住截断误差 */
    videoDurationSafetyMargin: 1,
};

/**
 * 单条素材计入总时长时的预算值：图片按固定展示时长，视频按存储时长 + 截断误差。
 * 与后端 ShanjianVideoSettingLogic::SAFETY_PER_VIDEO 同一口径——素材条数多时，
 * 按整秒累加会整体低估，闪剪按真实时长判超 5 分钟会把整单打回。
 */
export const calcMaterialDurationBudget = (isImage: boolean, duration?: number): number =>
    isImage ? montageUploadConfig.imageDuration : (duration ?? 0) + montageUploadConfig.videoDurationSafetyMargin;
