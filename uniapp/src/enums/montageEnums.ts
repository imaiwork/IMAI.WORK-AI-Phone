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
};
