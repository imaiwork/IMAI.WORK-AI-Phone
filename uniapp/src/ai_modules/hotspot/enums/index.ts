import IconMusicGray from "@/ai_modules/hotspot/static/icons/platform_music_gray.svg";
import IconMusicWhite from "@/ai_modules/hotspot/static/icons/platform_music_white.svg";
import IconZapGray from "@/ai_modules/hotspot/static/icons/platform_zap_gray.svg";
import IconZapWhite from "@/ai_modules/hotspot/static/icons/platform_zap_white.svg";
import IconBookGray from "@/ai_modules/hotspot/static/icons/platform_book_gray.svg";
import IconBookWhite from "@/ai_modules/hotspot/static/icons/platform_book_white.svg";
import IconHashGray from "@/ai_modules/hotspot/static/icons/platform_hash_gray.svg";
import IconHashWhite from "@/ai_modules/hotspot/static/icons/platform_hash_white.svg";

/** 热榜平台 */
export enum HotspotPlatform {
    DOUYIN = "douyin",
    KUAISHOU = "kuaishou",
    XHS = "xiaohongshu",
    WEIBO = "weibo",
}

/** 热榜周期 */
export enum HotspotPeriod {
    DAY = "day",
    WEEK = "week",
    RISE = "rise",
}

/** 任务状态（后端 HotspotTask.status 字符串） */
export enum HotspotTaskStatus {
    RUNNING = "running",
    DONE = "done",
    FAIL = "fail",
    WAIT = "wait",
}

/** 视频类型 */
export enum HotspotVideoType {
    DIGITAL = "digital",
    CLIPS = "clips",
}

export const HOTSPOT_PLATFORM_OPTIONS = [
    {
        key: HotspotPlatform.DOUYIN,
        label: "抖音",
        activeBg: "#111827",
        iconGray: IconMusicGray,
        iconWhite: IconMusicWhite,
    },
    {
        key: HotspotPlatform.KUAISHOU,
        label: "快手",
        activeBg: "#FF6600",
        iconGray: IconZapGray,
        iconWhite: IconZapWhite,
    },
    {
        key: HotspotPlatform.XHS,
        label: "小红书",
        activeBg: "#FF2442",
        iconGray: IconBookGray,
        iconWhite: IconBookWhite,
    },
    {
        key: HotspotPlatform.WEIBO,
        label: "微博",
        activeBg: "#E6162D",
        iconGray: IconHashGray,
        iconWhite: IconHashWhite,
    },
] as const;

/** 当前产品开放的平台（与后端 HotspotValidate/HotListService 白名单同步）；
 *  HOTSPOT_PLATFORM_OPTIONS 保留全量映射，历史任务的平台标签/图标仍可解析 */
export const HOTSPOT_VISIBLE_PLATFORMS = HOTSPOT_PLATFORM_OPTIONS.filter((p) => p.key === HotspotPlatform.DOUYIN);

export const HOTSPOT_PERIOD_OPTIONS = [
    { key: HotspotPeriod.DAY, label: "日榜" },
    { key: HotspotPeriod.WEEK, label: "周榜" },
    // { key: HotspotPeriod.RISE, label: "飙升" },
] as const;

export const HOTSPOT_STATUS_TEXT: Record<string, string> = {
    [HotspotTaskStatus.DONE]: "已完成",
    [HotspotTaskStatus.RUNNING]: "执行中",
    [HotspotTaskStatus.WAIT]: "待确认",
    [HotspotTaskStatus.FAIL]: "失败",
};

export const HOTSPOT_STATUS_CLASS: Record<string, string> = {
    [HotspotTaskStatus.DONE]: "bg-[#F0FDF4] text-[#16A34A]",
    [HotspotTaskStatus.RUNNING]: "bg-[#EFF6FF] text-primary",
    [HotspotTaskStatus.WAIT]: "bg-[#FFFBEB] text-[#D97706]",
    [HotspotTaskStatus.FAIL]: "bg-[#FEF2F2] text-[#EF4444]",
};

/** 详情页步骤（与后端 step_status_json 的 key 一致） */
export const HOTSPOT_TASK_STEPS = [
    { key: "select", label: "选定热点" },
    { key: "search", label: "AI 联网搜索" },
    { key: "analyze", label: "热点 × 人设 结合分析" },
    { key: "script", label: "生成口播文案" },
    { key: "video", label: "视频合成" },
] as const;

/** 队列卡片步骤短标签 */
export const HOTSPOT_TASK_STEPS_SHORT = [
    { key: "select", label: "选热点" },
    { key: "search", label: "搜索" },
    { key: "analyze", label: "分析" },
    { key: "script", label: "文案" },
    { key: "video", label: "合成" },
] as const;

export const platformLabel = (key: string): string => HOTSPOT_PLATFORM_OPTIONS.find((p) => p.key === key)?.label || key;

export const platformActiveBg = (key: string): string =>
    HOTSPOT_PLATFORM_OPTIONS.find((p) => p.key === key)?.activeBg || "#111827";

export const platformWhiteIcon = (key: string): string =>
    HOTSPOT_PLATFORM_OPTIONS.find((p) => p.key === key)?.iconWhite || IconMusicWhite;

/** options 接口离线兜底（与后端 ScriptService::options 同构） */
export const HOTSPOT_DEFAULT_OPTIONS = {
    goals: [
        { key: "sell", label: "卖产品" },
        { key: "leads", label: "私域获客" },
        { key: "traffic", label: "涨粉引流" },
        { key: "brand", label: "品牌种草" },
        { key: "engage", label: "点击播放" },
    ],
    directions: ["观点输出", "干货科普", "故事讲述", "争议讨论", "产品植入"],
    materials: [
        { key: "ai", label: "AI找素材" },
        { key: "ai_persona", label: "AI+人设素材" },
        { key: "persona", label: "纯人设素材" },
    ],
    durations: [30, 60, 90],
    video_types: [
        { key: HotspotVideoType.DIGITAL, label: "数字人口播混剪" },
        { key: HotspotVideoType.CLIPS, label: "素材混剪" },
    ],
    costs: {} as Record<string, { scene: string; score: number; unit: string }>,
};

export type HotspotOptionsData = typeof HOTSPOT_DEFAULT_OPTIONS;
