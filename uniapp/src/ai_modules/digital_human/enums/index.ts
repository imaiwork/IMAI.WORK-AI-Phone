// 模型类型
export enum ModeTypeEnum {
    VIDEO = 1, // 视频
    ANCHOR = 2, // 形象
    TONE = 3, // 音色
}

// 形象克隆模式：2 一克二(极速) / 3 一克三(专业)
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

// 创建类型
export enum CreateTypeEnum {
    TEXT = 1, // 文本
    AUDIO = 2, // 音频
}

/** 数字人纯口播 type5 无包装引擎 */
export enum SpeechEngineTypeEnum {
    /** 闪剪 */
    SHANJIAN = 1,
    /** 蝉镜 */
    CHANJING = 2,
}

// 监听类型
export enum ListenerTypeEnum {
    // 数字人口播文案
    SZR_COPYWRITER = "szr-copywriter",
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
    SORA_AI_COPYWRITER = "sora-ai-copywriter",
    // 素材图组
    MONTAGE_MATERIAL_GROUP = "montage-material-group",
    // 选择视频风格
    CHOOSE_VIDEO_STYLES = "choose-video-styles",
    // 平台发布文案
    PLATFORM_PUBLISH_COPYWRITER = "platform-publish-copywriter",
    // 平台发布文案（AI）
    PLATFORM_PUBLISH_AI_COPYWRITER = "platform-publish-ai-copywriter",
    // 视频顶部标题
    MONTAGE_TOP_TITLE = "montage-top-title",
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

/** 闪剪成片下载状态 */
export enum VideoDownloadStatusEnum {
    PENDING = 0,
    DOWNLOADING = 1,
    SUCCESS = 2,
    FAILED = 3,
}
