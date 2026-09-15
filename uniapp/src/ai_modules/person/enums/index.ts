import { AppTypeEnum, DigitalHumanModelVersionEnum } from "@/enums/appEnums";

/** 人设素材库「添加音色」可选模型：闪剪 + MiniMax */
export const PERSONA_MATERIAL_VOICE_MODEL_VERSIONS = [
    DigitalHumanModelVersionEnum.SHANJIAN,
    DigitalHumanModelVersionEnum.MINIMAX_HD,
    DigitalHumanModelVersionEnum.MINIMAX_TURBO,
].join(",");

// 人设类型
export enum PersonTypeEnum {
    PERSONAL_IP = 1,
    BUSINESS_SERVICE = 2,
    LOCAL_BUSINESS = 3,
}

export const PersonTypeMap = {
    [PersonTypeEnum.PERSONAL_IP]: "个人IP",
    [PersonTypeEnum.BUSINESS_SERVICE]: "企业服务",
    [PersonTypeEnum.LOCAL_BUSINESS]: "本地商家",
};

// 监听类型
export enum ListenerTypeEnum {
    CIRCLE_INTERACT_PROMPT = "circle-interact-prompt",
}

// 内容发布
export enum PublishPlatformEnum {
    SHIPINHAO = AppTypeEnum.SPH,
    XIAOHONGSHU = AppTypeEnum.XHS,
    DOUYIN = AppTypeEnum.DOUYIN,
    KUAISHOU = AppTypeEnum.KUAISHOU,
}

// 内容发布 · 平台展示顺序与文案（挂载购物车 / 商家定位为抖音专属）
export const PUBLISH_PLATFORM_LIST = [
    { platform: PublishPlatformEnum.XIAOHONGSHU, label: "小红书" },
    { platform: PublishPlatformEnum.DOUYIN, label: "抖音" },
    { platform: PublishPlatformEnum.KUAISHOU, label: "快手" },
    { platform: PublishPlatformEnum.SHIPINHAO, label: "视频号" },
] as const;

// 内容发布 · 发布内容类型
export enum PublishMediaTypeEnum {
    VIDEO = 1,
    IMAGE = 2,
}

// 内容发布 · 发布文案生成方式（语义字段 publish_copywriting_source）
export enum PublishCopySourceEnum {
    AUTO = 1,
    LIBRARY = 2,
}

// 内容发布 · 生成方式（generate_mode：1 自动 2 自定义 3 素材库引用）
export enum PublishGenerateModeEnum {
    AUTO = 1,
    CUSTOM = 2,
    LIBRARY = 3,
}

// 内容发布 · AI 自动生成依据
export enum PublishBasisEnum {
    PERSONA = 1,
    CUSTOM = 2,
}

// 内容发布 · 素材库使用方式
export enum PublishLibraryUseModeEnum {
    RANDOM = 1,
    SEQUENCE = 2,
}

// 内容发布 · 素材库随机规则
export enum PublishLibraryReuseModeEnum {
    ONCE = 1,
    REPEAT = 2,
}

export interface PlatformPublishConfig {
    publish_media_type: PublishMediaTypeEnum;
    publish_copywriting_source: PublishCopySourceEnum;
    generate_basis: PublishBasisEnum;
    custom_direction: string;
    library_use_mode: PublishLibraryUseModeEnum;
    library_reuse_mode: PublishLibraryReuseModeEnum;
    is_content_location: 0 | 1;
    content_location: string;
}

// 各平台默认配置（参考设计稿：抖音/快手/视频号默认视频，小红书默认图文；均默认开启内容定位）
export const getPublishPlatformDefault = (platform: PublishPlatformEnum): PlatformPublishConfig => ({
    publish_media_type:
        platform === PublishPlatformEnum.XIAOHONGSHU ? PublishMediaTypeEnum.IMAGE : PublishMediaTypeEnum.VIDEO,
    publish_copywriting_source: PublishCopySourceEnum.AUTO,
    generate_basis: PublishBasisEnum.PERSONA,
    custom_direction: "",
    library_use_mode: PublishLibraryUseModeEnum.RANDOM,
    library_reuse_mode: PublishLibraryReuseModeEnum.ONCE,
    is_content_location: 1,
    content_location: "",
});

/**
 * 人设素材模式（仅剪辑原片库）
 * 自动适配：走原有转码 / 切片链路，来者不拒；
 * 原图：不压缩不适配，上传带 material_kind=original + ffmpeg=0，服务端按硬规格（OriginalMaterialSpec）拦截后直接入库
 */
export enum MaterialKindEnum {
    AUTO = "auto",
    ORIGINAL = "original",
}

/** 人设接口 material_use_mode：1=自动适配 2=原图（人设表为准，合成配置表双写） */
export enum MaterialUseModeApiEnum {
    AUTO = 1,
    ORIGINAL = 2,
}

export const MATERIAL_KIND_TO_USE_MODE: Record<MaterialKindEnum, MaterialUseModeApiEnum> = {
    [MaterialKindEnum.AUTO]: MaterialUseModeApiEnum.AUTO,
    [MaterialKindEnum.ORIGINAL]: MaterialUseModeApiEnum.ORIGINAL,
};

/** 接口值回显成素材模式：只有 2 算原图，其余（空 / 脏值）一律自动适配 */
export const normalizeMaterialUseMode = (value: unknown): MaterialKindEnum =>
    Number(value) === MaterialUseModeApiEnum.ORIGINAL ? MaterialKindEnum.ORIGINAL : MaterialKindEnum.AUTO;

/** 人设接口 run_mode：1=手机模式（绑定 AI 手机制作发布） 2=无手机模式（云端制作，用户自己发） */
export enum RunModeApiEnum {
    PHONE = 1,
    CLOUD = 2,
}

/** 只有 2 算无手机模式，空值与脏值一律按手机模式，保证存量人设行为不变 */
export const isCloudRunMode = (value: unknown): boolean => Number(value) === RunModeApiEnum.CLOUD;

/** 成品直发库使用方式，与服务端 AiPersonaSynthesisConfig::PRODUCT_USE_MODE_* 对齐 */
export enum ProductUseModeEnum {
    RANDOM = 1,
    SEQUENCE = 2,
}

/** 成品直发库取用规则，与服务端 AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_* 对齐 */
export enum ProductReuseModeEnum {
    /** 每条成品只用一次，用完即跳过 */
    ONCE = 1,
    /** 可重复使用，成品不会耗尽 */
    REPEAT = 2,
}

/** 闪剪成片下载状态（跨分包引用受限，person 模块本地副本，定义须与 digital_human/enums 保持一致） */
export enum VideoDownloadStatusEnum {
    PENDING = 0,
    DOWNLOADING = 1,
    SUCCESS = 2,
    FAILED = 3,
}

/** 开播准备的必须项，缺一条人设就跑不出内容；数组顺序即引导顺序 */
export enum SetupStepEnum {
    /** 追踪词：决定 AI 每晚去各平台抓什么 */
    KEYWORD = "keyword",
    /** 剪辑原片库素材：决定拿什么剪 */
    MATERIAL = "material",
    /** 数字人形象 + 音色：决定谁出镜、什么声音 */
    AVATAR_VOICE = "avatar_voice",
    /** 发布方式：绑 AI 手机 或 切无手机模式 */
    PUBLISH_MODE = "publish_mode",
    /** 发布时段与账号：决定什么时候发、发去哪 */
    PUBLISH_PLAN = "publish_plan",
}

/** 必须项达标门槛。服务端 setupProgress 用同一套判定，改这里要同步改服务端 */
export const SETUP_THRESHOLD = {
    /** 追踪词下限，低于这个数抓不到足够选题 */
    KEYWORD_MIN: 3,
    /** 剪辑原片库素材下限，低于这个数当晚合成会被跳过 */
    MATERIAL_MIN: 5,
    /** 成品直发库下限：成品不参与合成，每条只能用一次，有一条就能发 */
    DIRECT_MATERIAL_MIN: 1,
} as const;

/** 加分项：不做也能跑，做了内容更好。数组顺序即「按影响排序」的顺序 */
export enum EnhanceItemEnum {
    KEYWORD = "keyword",
    AVATAR = "avatar",
    MUSIC = "music",
    ACCOUNT = "account",
    COPYWRITING = "copywriting",
    VOICE = "voice",
    MATERIAL = "material",
}

/** 加分项达标门槛。纯产品口径，后端不做任何拦截 */
export const ENHANCE_THRESHOLD: Record<EnhanceItemEnum, number> = {
    [EnhanceItemEnum.KEYWORD]: 8,
    [EnhanceItemEnum.AVATAR]: 2,
    [EnhanceItemEnum.MUSIC]: 1,
    [EnhanceItemEnum.ACCOUNT]: 2,
    [EnhanceItemEnum.COPYWRITING]: 20,
    [EnhanceItemEnum.VOICE]: 2,
    [EnhanceItemEnum.MATERIAL]: 20,
};

/** 会让 24h 自动产内容静默停摆的异常，数组顺序即严重程度 */
export enum PersonaIssueEnum {
    /** 手机模式下一台设备都没绑 */
    NO_DEVICE = "no_device",
    /** 绑了但离线 */
    DEVICE_OFFLINE = "device_offline",
    /** 音色失效，成片会没有人声 */
    VOICE_MISSING = "voice_missing",
    /** 数字人形象失效 */
    AVATAR_MISSING = "avatar_missing",
    /** 当天有生成失败 */
    GENERATE_FAILED = "generate_failed",
    /** 人设被会员到期冻结：两条 24h 合成 cron 只捞启用中的人设，这条不亮就完全看不出为什么停了 */
    PERSONA_DISABLED = "persona_disabled",
}

/** 文案来源，与服务端 AiPersonaSynthesisConfig::COPYWRITING_SOURCE_* 对齐 */
export enum CopywritingSourceEnum {
    /** 找爆款仿写：靠绑定手机抓取，无手机模式下没有来源 */
    IMITATE = 1,
    AI = 2,
    NONE = 3,
    LIBRARY = 4,
}
