// 剪辑风格
export enum ClipStyleEnum {
    AI_RECOMMEND = 1,
    TECHNOLOGY = 2,
    LIFE = 3,
    MARKETING = 4,
    KNOWLEDGE = 5,
    VARIETY = 6,
}

// 剪辑风格枚举映射
export const ClipStyleMap = {
    [ClipStyleEnum.AI_RECOMMEND]: "Ai智能推荐",
    [ClipStyleEnum.TECHNOLOGY]: "科技风格",
    [ClipStyleEnum.LIFE]: "生活风格",
    [ClipStyleEnum.MARKETING]: "营销风格",
    [ClipStyleEnum.KNOWLEDGE]: "知识科普风格",
    [ClipStyleEnum.VARIETY]: "综艺风格",
};
