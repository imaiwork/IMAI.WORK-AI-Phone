// 混剪/成片素材上传限制。digital_human 与 hotspot 等多个分包共用，
// 必须放主包，分包之间不能互相 require。
const imageAccept = ["webp", "jpg", "png"];
const videoAccept = ["mp4", "mov"];

export const montageConfig = {
    count: 9,
    imageAccept,
    videoAccept,
    imageSize: 20,
    imageResolution: [2000, 2000],
    videoSize: 200,
    videoDuration: [1, 59],
    videoResolution: [2000, 2000],
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
 * 只用于「能不能再加 / 能不能下一步」这类校验，展示给用户的时长仍用原值。
 */
export const calcMaterialDurationBudget = (isImage: boolean, duration?: number | string): number =>
    isImage ? montageConfig.imageDuration : (Number(duration) || 0) + montageConfig.videoDurationSafetyMargin;
