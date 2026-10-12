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
    Square = "square",
}

/** 文案来源 */
export enum CopySourceEnum {
    Rewrite = "rewrite",
    AIGen = "ai_gen",
    None = "none",
    Hotspot = "hotspot",
}

/** 热点生成 · 取用规则，对齐 la_ai_persona_synthesis_config.hotspot_pick_mode */
export enum HotspotTakeModeEnum {
    Latest = 1,
    Once = 2,
}

/** 热点库条目是否已带文案，对齐 /hotspot.hotspot/stock 的 has_script。
 *  不从 hotspot 分包 import：微信小程序禁止跨分包 require，否则整包 pages 无法注册。 */
export enum HotspotStockHasScript {
    TitleOnly = 0,
    WithScript = 1,
}

/** 热点库占用标识，对齐 /hotspot.hotspot/stock 的 use_status */
export enum HotspotStockUseStatus {
    Pending = 0,
    Used = 1,
}

export const HOTSPOT_STOCK_USE_STATUS_LABEL: Record<HotspotStockUseStatus, string> = {
    [HotspotStockUseStatus.Pending]: "待用",
    [HotspotStockUseStatus.Used]: "已使用",
}

export const HOTSPOT_STOCK_PENDING_COUNT_SUFFIX = "条待用"
export const HOTSPOT_STOCK_USED_COUNT_SUFFIX = "条已使用"

export const HOTSPOT_PLATFORM_LABELS: Record<string, string> = {
    douyin: "抖音",
    kuaishou: "快手",
    xiaohongshu: "小红书",
    weibo: "微博",
}

export const HOTSPOT_STOCK_DELETE_CONFIRM = "确定将这条热点移出热点库吗？"

export const normalizeHotspotTakeMode = (value: unknown): HotspotTakeModeEnum =>
    Number(value) === HotspotTakeModeEnum.Once ? HotspotTakeModeEnum.Once : HotspotTakeModeEnum.Latest

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

/** 运行模式：对应人设 run_mode，1 手机模式 = 绑定 AI 手机制作发布；2 无手机模式 = 云端制作、小程序审核发布 */
export enum RunModeEnum {
    Phone = "phone",
    Cloud = "cloud",
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
