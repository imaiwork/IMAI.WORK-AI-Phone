/** 工作台对话区模式（UI 跟移动设计稿，业务显隐/上传跟 PC） */
export enum WorkbenchMode {
    Chat = "chat",
    Image = "image",
    Ppt = "ppt",
    Map = "map",
    Video = "video",
}

/** 对话工具栏入口（设计稿主入口：图像/地图；PPT/视频对齐 PC） */
export const WORKBENCH_MODE_OPTIONS = [
    { mode: WorkbenchMode.Image, label: "图像生成" },
    { mode: WorkbenchMode.Map, label: "地图获客" },
    { mode: WorkbenchMode.Ppt, label: "PPT生成" },
    { mode: WorkbenchMode.Video, label: "视频生成" },
] as const;

export const WORKBENCH_PLACEHOLDER: Record<WorkbenchMode, string> = {
    [WorkbenchMode.Chat]: "发送消息、输入@选择智能体",
    [WorkbenchMode.Image]: "输入图片生成的提示词，例如：浩瀚的银河中一艘宇宙飞船驶过",
    [WorkbenchMode.Ppt]: "输入演讲主题，例如：2026年Q1业务总结",
    [WorkbenchMode.Map]: "告诉我你想找什么商家，例如：帮我找北京东城区的咖啡店",
    [WorkbenchMode.Video]: "输入视频脚本或画面描述",
};

/** 对齐 PC RATIO_OPTIONS */
export const IMAGE_RATIO_OPTIONS = [
    { key: "smart", label: "智能", w: 18, h: 18 },
    { key: "21:9", label: "21:9", w: 28, h: 12 },
    { key: "16:9", label: "16:9", w: 26, h: 15 },
    { key: "3:2", label: "3:2", w: 24, h: 16 },
    { key: "4:3", label: "4:3", w: 22, h: 17 },
    { key: "1:1", label: "1:1", w: 18, h: 18 },
    { key: "3:4", label: "3:4", w: 16, h: 22 },
    { key: "2:3", label: "2:3", w: 14, h: 22 },
    { key: "9:16", label: "9:16", w: 12, h: 22 },
] as const;

/** 生图宽高须为 16 的倍数（对齐 PC） */
export const IMAGE_RATIO_PRESETS: Record<string, [number, number]> = {
    "21:9": [2464, 1056],
    "16:9": [2048, 1152],
    "3:2": [1536, 1024],
    "4:3": [1600, 1200],
    "1:1": [1088, 1088],
    "3:4": [1200, 1600],
    "2:3": [1024, 1536],
    "9:16": [1440, 2560],
    smart: [1024, 1024],
};

export const IMAGE_RESOLUTION_OPTIONS = [
    { key: "2k", label: "高清 2K", short: "高清2K" },
    { key: "4k", label: "超清 4K", short: "超清4K" },
] as const;

/** 对齐 PC：非 seedream 最大 9 张 */
export const IMAGE_COUNT_OPTIONS = [1, 2, 3, 4, 5, 6, 7, 8, 9] as const;
export const IMAGE_REF_MAX = 3;

/** 对齐 PC VIDEO_RATIO_OPTIONS */
export const VIDEO_RATIO_OPTIONS = [
    { key: "16:9", label: "16:9", w: 26, h: 15 },
    { key: "9:16", label: "9:16", w: 12, h: 22 },
    { key: "1:1", label: "1:1", w: 18, h: 18 },
    { key: "4:3", label: "4:3", w: 22, h: 17 },
    { key: "3:4", label: "3:4", w: 16, h: 22 },
    { key: "21:9", label: "21:9", w: 28, h: 12 },
] as const;

/** 中台视频分辨率档位：480p | 720p | 1080p */
export const VIDEO_RESOLUTION_OPTIONS = [
    { key: "480p", label: "480p" },
    { key: "720p", label: "720p" },
    { key: "1080p", label: "1080p" },
] as const;

/** 旧固定页数（兼容）；新 UI 用区间 */
export const PPT_PAGE_OPTIONS = [5, 8, 10, 12, 15] as const;

/** 对齐 PC PPT_PAGES */
export const PPT_PAGE_RANGE_OPTIONS = ["5-15页", "15-25页", "25-35页", "35页以上"] as const;

/** 对齐 PC PPT_SCENES */
export const PPT_SCENES = [
    "通用",
    "项目提案",
    "公众演讲",
    "工作汇报",
    "教学课件",
    "作业展示",
    "产品推广",
    "市场宣传",
    "论文答辩",
    "学术研讨",
    "总结计划",
    "商业洽谈",
] as const;

/** 区间/自定义页数文案 → 整数（对齐 PC：区间取中位；35页以上取 40；兜底 16） */
export function resolvePptPageCount(pagesLabel: string, override?: number): number {
    if (override && override > 0) return Math.floor(override);
    const label = String(pagesLabel || "").trim();
    if (label.includes("以上")) {
        const n = parseInt(label, 10);
        return Number.isFinite(n) && n > 0 ? n + 5 : 40;
    }
    const rangeMatch = label.match(/(\d+)\s*-\s*(\d+)/);
    if (rangeMatch) {
        return Math.round((parseInt(rangeMatch[1], 10) + parseInt(rangeMatch[2], 10)) / 2);
    }
    const singleMatch = label.match(/^(\d+)\s*页$/);
    if (singleMatch) return parseInt(singleMatch[1], 10);
    const n = parseInt(label, 10);
    return Number.isFinite(n) && n > 0 ? n : 16;
}

/** 各模式上传规则（对齐 PC welcome-hero） */
export const WORKBENCH_UPLOAD_RULE: Record<
    WorkbenchMode,
    { show: boolean; label: string; accept: "image" | "doc" | "chat" | "none" }
> = {
    [WorkbenchMode.Chat]: { show: true, label: "文件上传", accept: "chat" },
    [WorkbenchMode.Image]: { show: true, label: "参考图上传", accept: "image" },
    [WorkbenchMode.Video]: { show: true, label: "参考图上传", accept: "image" },
    [WorkbenchMode.Ppt]: { show: true, label: "上传文档", accept: "doc" },
    [WorkbenchMode.Map]: { show: false, label: "", accept: "none" },
};
