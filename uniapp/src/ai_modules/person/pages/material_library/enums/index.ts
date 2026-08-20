/** 生成类型 */
export enum SynthTypeEnum {
    DigitalHuman = "digital_human",
    News = "news",
    Material = "material",
}

/** 画面素材来源 */
export enum MaterialSourceEnum {
    PureAI = "pure_ai",
    AILib = "ai_lib",
    PureLib = "pure_lib",
}

/** 文案来源 */
export enum CopySourceEnum {
    Rewrite = "rewrite",
    AIGen = "ai_gen",
    None = "none",
}

/** 文案风格 */
export enum CopyStyleEnum {
    Spoken = "spoken",
    Marketing = "marketing",
    Tutorial = "tutorial",
    Professional = "professional",
    Story = "story",
    Empathy = "empathy",
}

/** 视频封面来源 */
export enum CoverSourceEnum {
    Default = "default",
    AI = "ai",
    Manual = "manual",
}

/** 视频模板使用方式：1=自动随机，2=自定义多选 */
export enum TemplateModeEnum {
    Auto = 1,
    Custom = 2,
}

/** 生成类型 API 数字（与合成规则 generation_types 一致） */
export enum SynthTypeApiEnum {
    DigitalHuman = 1,
    Material = 3,
    News = 4,
}

/** 闪剪模板 scene：按生成类型映射 */
export const SYNTH_TYPE_SCENE: Record<number, string> = {
    [SynthTypeApiEnum.DigitalHuman]: "virtualman",
    [SynthTypeApiEnum.Material]: "oralMixCutting",
    [SynthTypeApiEnum.News]: "newsMixCutting",
};

export interface TemplateConfigItem {
    mode: TemplateModeEnum;
    template_ids: string[];
    scene: string;
    selected_count: number;
}

export type TemplateConfigMap = Record<string, TemplateConfigItem>;

/** 选择页 ↔ 规则弹窗回传事件 */
export const VIDEO_TEMPLATE_CONFIRM_EVENT = "person-video-template-confirm";

/** 选择页入参草稿（避免 URL 过长） */
export const VIDEO_TEMPLATE_DRAFT_KEY = "person_video_template_draft";
