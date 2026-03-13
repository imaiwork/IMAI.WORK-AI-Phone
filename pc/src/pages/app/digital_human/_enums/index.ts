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
    // 闪剪
    SHANJIAN = 8,
}

// 数字人模型版本枚举映射
export const DigitalHumanModelVersionEnumMap = {
    [DigitalHumanModelVersionEnum.STANDARD]: "标准",
    [DigitalHumanModelVersionEnum.SUPER]: "极速",
    [DigitalHumanModelVersionEnum.ADVANCED]: "高级",
    [DigitalHumanModelVersionEnum.ELITE]: "尊享",
    [DigitalHumanModelVersionEnum.CHANJING]: "蝉镜",
    [DigitalHumanModelVersionEnum.SHANJIAN]: "闪剪",
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

// 侧边栏类型
export enum SidebarTypeEnum {
    // 数字人纯口播视频
    DIGITAL_HUMAN_PURE_BOUQUET = 1,
    // 数字人口播混剪
    BOUQUET_MIXING = 2,
    // 真人口播视频混剪
    REAL_PERSON_MIXING = 3,
    // 素材混剪神器
    MATERIAL_MIXING = 4,
    // 新闻体视频
    NEWS_VIDEO = 5,
    // 一句话生成视频
    ONE_WORD_VIDEO = 6,
    // 形象克隆
    ANCHOR_CLONE = 7,
    // 声音克隆
    VOICE_CLONE = 8,
    // 我的作品
    MY_WORKS = 9,
    // 我的形象
    MY_ANCHOR = 10,
}

// 创作视频类型
export enum CreateVideoTypeEnum {
    ALL = 0,
    DIGITAL_HUMAN = 1,
    ORAL_MIX = 2,
    REAL_PERSON_MIXING = 3,
    MATERIAL_MIX = 4,
    NEWS = 5,
    SENTENCE = 6,
    STORYBOARD = 7,
}

// 混剪发布类型
export enum MontageTypeEnum {
    // 真人口播混剪
    REAL_PERSON_MIX = 1,
    // 真人口播智剪
    REAL_PERSON_AI = 2,
    // 素材混剪
    MATERIAL_MIX = 3,
    // 新闻体
    NEWS_BODY = 4,
    // Sora生成视频
    SORA_VIDEO = 5,
}

export enum MontageStylesType {
    DIGITAL_PERSON = 1,
    REAL_PERSON = 2,
    NEWS = 3,
    MATERIAL = 4,
}

export enum MontageStylesChooseType {
    ALL = 0,
    HIGH = 1,
    VARIETY = 2,
    HOT = 3,
    SIMPLE = 4,
    LOCAL = 5,
}
