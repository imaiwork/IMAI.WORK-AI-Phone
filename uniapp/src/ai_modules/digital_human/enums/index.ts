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
    // 畅镜版
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
    VIDEO = 1, // 视频
    ANCHOR = 2, // 形象
}

// 创建类型
export enum CreateTypeEnum {
    TEXT = 1, // 文本
    AUDIO = 2, // 音频
}

// 监听类型
export enum ListenerTypeEnum {
    // AI文案
    AI_COPYWRITER = "ai-copywriter",
    // 音色
    TONE = "tone",
    // 上传形象
    UPLOAD_ANCHOR = "upload-anchor",
    // 上传视频
    UPLOAD_VIDEO = "upload-video",
    // 选择剪辑风格
    CHOOSE_STYLES = "choose-styles",
    // 选择背景音乐
    CHOOSE_MUSIC = "choose-music",
    // 形象
    VIDEO_UPLOAD = "video-upload",
    // 选择混剪形象
    CREATE_ANCHOR = "create-anchor",
    // 混剪授权
    ANCHOR_AUTH = "anchor-auth",
    // 上传授权相机
    UPLOAD_AUTH_CAMERA = "upload-auth-camera",
    // 混剪口播文案
    MONTAGE_COPYWRITER = "montage-copywriter",
    // 混剪口播文案（AI）
    MONTAGE_AI_COPYWRITER = "montage-ai-copywriter",
    // 账号
    CHOOSE_ACCOUNT = "choose-account",
    // Sora生成文案
    SORA_COPYWRITER = "sora-ai-copywriter",
    // 素材图组
    MONTAGE_MATERIAL_GROUP = "montage-material-group",
}

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
