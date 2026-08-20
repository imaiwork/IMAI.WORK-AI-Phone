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
