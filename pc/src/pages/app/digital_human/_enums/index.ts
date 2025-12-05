// 数字人模型版本
export enum DigitalHumanModelVersionEnum {
    // 标准版
    STANDARD = 1,
    // 极速版
    SUPER = 2,
    // 高级版
    ADVANCED = 4,
    // 尊享版
    ELITE = 6,
    // 蝉镜
    CHANJING = 7,
}

// 数字人模型版本枚举映射
export const DigitalHumanModelVersionEnumMap = {
    [DigitalHumanModelVersionEnum.STANDARD]: "标准版",
    [DigitalHumanModelVersionEnum.SUPER]: "极速版",
    [DigitalHumanModelVersionEnum.ADVANCED]: "高级版",
    [DigitalHumanModelVersionEnum.ELITE]: "尊享版",
    [DigitalHumanModelVersionEnum.CHANJING]: "蝉镜",
};

// 模型类型
export enum ModeTypeEnum {
    VIDEO = 1,
    FIGURE = 2,
}

// 创建类型
export enum CreateTypeEnum {
    TEXT = 1, // 文本
    AUDIO = 2, // 音频
}

// 音色类型
export enum ToneTypeEnum {
    BUILTIN = 0, // 系统音色
    USER = 1, // 用户音色
    ALL = 3, // 全部音色
}
