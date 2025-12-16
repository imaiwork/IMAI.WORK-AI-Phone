const imageAccept = ["webp", "jpg", "png"];
const videoAccept = ["mp4", "mov"];

export const montageConfig = {
    count: 9,
    imageAccept,
    videoAccept,
    imageSize: 20,
    imageResolution: [2000, 2000],
    videoSize: 200,
    videoDuration: [1, 60],
    videoResolution: [2000, 2000],
    fileAccept: [...imageAccept, ...videoAccept],
    fileSize: 100,
    imageDuration: 2,
    materialTotalDuration: 5,
};
