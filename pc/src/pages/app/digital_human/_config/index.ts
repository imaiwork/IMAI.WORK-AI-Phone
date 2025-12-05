import { DigitalHumanModelVersionEnum } from "../_enums";
// 高级版和尊享版公共上传限制
export const commonUploadLimit = {
    size: 300,
    // 最小分辨率
    minResolution: 360,
    // 最大分辨率
    maxResolution: 1080,
    // 最小时长
    videoMinDuration: 5,
    // 最大时长
    videoMaxDuration: 600,
};

// 上传限制
export const uploadLimit: Record<DigitalHumanModelVersionEnum, any> = {
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
        size: 2000,
        minResolution: 360,
        maxResolution: 2048,
        videoMinDuration: 30,
        videoMaxDuration: 300,
    },
};
