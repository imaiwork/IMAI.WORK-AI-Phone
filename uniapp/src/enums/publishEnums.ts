// AI 帮我做内容 - 内容来源（后端 source 字段）
export enum PublishSourceEnum {
    SOCIAL = "sv", // 社媒
    CIRCLE = "circle", // 朋友圈
    CLOUD = "cloud", // 无手机模式云端成片
}

/** 矩阵手动发布页 onLoad 的 source，禁止在跳转处再散落字面量 */
export enum ManualPublishSourceEnum {
    CREATION_VIDEO = "creation_video",
    PUZZLE = "puzzle",
    HOT_WRITE = "hot_write",
    HOTSPOT = "hotspot",
    CLOUD_VIDEO = "cloud_video",
}
