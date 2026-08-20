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
    // minimax-hd
    MINIMAX_HD = 10,
    // minimax-turbo
    MINIMAX_TURBO = 11,
}

/** 数字人纯口播可选驱动模型 */
export const DIGITAL_HUMAN_DRIVE_MODEL_VERSIONS: number[] = [
    DigitalHumanModelVersionEnum.CHANJING,
    DigitalHumanModelVersionEnum.SHANJIAN,
];

/** 蝉镜「视频原音」占位 voice_id，提交时不传 voice_id，由服务端从形象视频克隆原音 */
export const CHANJING_ORIGINAL_VOICE_ID = "-1";

/** 数字人纯口播 type5 无包装引擎 */
export enum SpeechEngineTypeEnum {
    /** 闪剪 */
    SHANJIAN = 1,
    /** 蝉镜 */
    CHANJING = 2,
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

// 形象克隆模式：2 一克二(极速/标准) / 3 一克三(专业/优质)
export enum CloneModeEnum {
    FAST = 2,
    PRO = 3,
}

/** 闪剪形象 clone_type：1极速（普通） / 2专业 */
export enum ShanjianCloneTypeEnum {
    FAST = 1,
    PRO = 2,
}

// 形象列表筛选 is_pro：0全部 / 1普通 / 2专业
export enum AnchorListProFilterEnum {
    ALL = 0,
    NORMAL = 1,
    PRO = 2,
}

/** 克隆模式 → 列表 is_pro：标准/极速→普通(1)，优质/专业→专业(2) */
export const cloneModeToIsPro = (mode: CloneModeEnum): AnchorListProFilterEnum => {
    return mode === CloneModeEnum.PRO ? AnchorListProFilterEnum.PRO : AnchorListProFilterEnum.NORMAL;
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
    // 分镜混剪
    STORYBOARD_MIX = 7,
    // 形象克隆
    ANCHOR_CLONE = 8,
    // 声音克隆
    VOICE_CLONE = 9,
    // 我的作品
    MY_WORKS = 10,
    // 我的形象
    MY_ANCHOR = 11,
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
    HOT_WRITE = 8,
    /** 闪剪数字人纯口播（展示仍归「数字人口播」） */
    DIGITAL_HUMAN_SHANJIAN = 9,
}

/** 闪剪成片下载状态 */
export enum VideoDownloadStatusEnum {
    PENDING = 0,
    DOWNLOADING = 1,
    SUCCESS = 2,
    FAILED = 3,
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
    // 一句话生成视频
    ONE_SENTENCE_VIDEO = 5,
    // 分镜混剪
    STORYBOARD_MIX = 6,
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
