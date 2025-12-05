export enum SidebarEnum {
    IMAGE_GENERATION = 1,
    GOODS_IMAGE = 2,
    FASHION_IMAGE = 3,
    POSTER_IMAGE = 4,
    VIDEO_GENERATION = 5,
}

export enum ModelEnum {
    HIDREAMAI = 1,
    GENERAL = 2,
    SEEDREAM = 3,
    SEEDANCE = 4,
}

export enum DrawTypeEnum {
    GOODS = "goods",
    MODEL = "model",
    TXT2IMAGE = "txt2image",
    IMAGE2IMAGE = "image2image",
    IMAGE_GENERATION = "image-generation",
    GOODS_IMAGE = "goods-image",
    POSTER_IMAGE = "poster-image",
    FASHION_IMAGE = "fashion-image",
    VIDEO_GENERATION = "video-generation",
}

export const drawTypeEnumMap = {
    [DrawTypeEnum.GOODS_IMAGE]: 1,
    [DrawTypeEnum.FASHION_IMAGE]: 2,
    [DrawTypeEnum.TXT2IMAGE]: 3,
    [DrawTypeEnum.IMAGE2IMAGE]: 4,
    [DrawTypeEnum.POSTER_IMAGE]: 5,
    [DrawTypeEnum.IMAGE_GENERATION]: 6,
    [DrawTypeEnum.VIDEO_GENERATION]: 9,
};

// 生成视频类型
export enum GenerateVideoTypeEnum {
    TXT2VIDEO = 0,
    IMG2VIDEO = 1,
}

export enum FashionImageTypeEnum {
    // 上下装
    UPPER_LOWER_CLOTHES = 1,
    // 连衣裙
    DRESS = 2,
}

// 图片分辨率
export const resolutionOptions = [
    { label: "1:1", value: "512*512" },
    { label: "4:3", value: "512*384" },
    { label: "3:4", value: "384*512" },
    { label: "3:2", value: "512*341" },
    { label: "2:3", value: "341*512" },
    { label: "16:9", value: "512*288" },
    { label: "9:16", value: "288*512" },
];

//  SEEDREAM 分辨率
export const seedreamResolutionOptions = [
    { label: "1:1", value: "1328*1328" },
    { label: "4:3", value: "1472*1104" },
    { label: "3:2", value: "1584*1056" },
    { label: "2:3", value: "1104*1472" },
    { label: "16:9", value: "1664*936" },
    { label: "9:16", value: "936*1664" },
    { label: "21:9", value: "2016*864" },
];

// 视频分辨率
export const videoResolutionOptions = [
    { label: "16:9", value: "1280*720" },
    { label: "9:16", value: "720*1280" },
    { label: "1:1", value: "960*960" },
    { label: "4:3", value: "960*720" },
    { label: "3:4", value: "720*960" },
    { label: "21:9", value: "1680*720" },
];

// 商品图分辨率
export const goodsResolutionOptions = [
    { label: "300*300", value: [300, 300] },
    { label: "800*600", value: [800, 600] },
    { label: "970*600", value: [970, 600] },
    { label: "1080*1080", value: [1080, 1080] },
    { label: "1600*1600", value: [1600, 1600] },
    { label: "1601*1601", value: [1601, 1601] },
    { label: "1600*2000", value: [1600, 2000] },
    { label: "2000*1800", value: [2000, 1800] },
    { label: "2000*2000", value: [2000, 2000] },
    { label: "2048*2048", value: [2048, 2048] },
];

// 生成风格
export enum GenerateEnum {
    AMOZON = "amozon",
    DEFAULT = "default",
}
