import { AppTypeEnum } from "@/enums/appEnums";
import IconMusicGray from "@/ai_modules/hot_write/static/icons/platform_music_gray.svg";
import IconMusicWhite from "@/ai_modules/hot_write/static/icons/platform_music_white.svg";
import IconBookGray from "@/ai_modules/hot_write/static/icons/platform_book_gray.svg";
import IconBookWhite from "@/ai_modules/hot_write/static/icons/platform_book_white.svg";

/** 爆款复刻平台 */
export enum HotWritePlatform {
    DOUYIN = "douyin",
    XHS = "xhs",
}

/** 媒体类型：1 视频 2 图文 */
export enum HotWriteMediaType {
    VIDEO = 1,
    IMAGE_TEXT = 2,
}

/** 图文复刻算力模型（config.draw_model.channel） */
export const HOT_WRITE_IMAGE_MODEL_NAME = "image-2";
export const HOT_WRITE_IMAGE_MODEL_ALIAS = "gpt-image-2";

/** 图文改写状态 */
export enum ImageRewriteStatus {
    NONE = 0,
    WAIT = 1,
    PROCESSING = 2,
    SUCCESS = 3,
    FAIL = 4,
    SELECTING = 5,
}

/** 任务主状态 */
export enum HotWriteTaskStatus {
    PARSING = 0,
    WAIT_CONFIRM = 1,
    GENERATING = 2,
    SUCCESS = 3,
    FAIL = 4,
}

/** 文案模式：1 人设复刻（默认） 2 洗稿（不选人设，形象/音色自选） */
export enum HotWriteRewriteMode {
    PERSONA = 1,
    WASH = 2,
}

/** 洗稿视频类型（generation_type）：0 未选 1 数字人口播 2 素材口播 3 新闻体 */
export enum WashGenerationType {
    NONE = 0,
    DIGITAL_HUMAN = 1,
    MATERIAL = 2,
    NEWS = 3,
}

/**
 * 仿写文案目标字数档位。成片时长按「字数/3」估算，字数≈秒数×3。
 * 与后端 VideoImitationTask::COPYWRITING_LENGTH_OPTIONS 对齐，改动需两边同步。
 */
export const HOT_WRITE_COPY_LENGTH_OPTIONS = [
    { val: 100, label: "短", desc: "约100字 · 30秒" },
    { val: 200, label: "中", desc: "约200字 · 1分钟" },
    { val: 350, label: "长", desc: "约350字 · 2分钟" },
    { val: 600, label: "超长", desc: "约600字 · 3分钟" },
] as const;

/** 洗稿背景音乐来源：1 系统随机（默认） 2 用户自传 3 指定系统音乐 */
export enum WashMusicSource {
    SYSTEM = 1,
    UPLOAD = 2,
    PICK = 3,
}

export const HOT_WRITE_COPY_LENGTH_DEFAULT = 200;

/** 闪剪成片时长换算：每秒 3 字，与后端 duration = 字数/3 同一口径 */
export const SPEECH_CHARS_PER_SECOND = 3;

/** 按文案字数估算成片时长文案，如「1分12秒」 */
export function formatEstimatedDuration(text: string): string {
    const chars = String(text || "").trim().length;
    if (!chars) return "";
    const seconds = Math.max(1, Math.round(chars / SPEECH_CHARS_PER_SECOND));
    if (seconds < 60) return `${seconds}秒`;
    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;
    return rest ? `${minutes}分${rest}秒` : `${minutes}分钟`;
}

/** 「形象原音」音色哨兵：没有音色库记录，服务端按所选形象克隆出的原音下发 */
export const WASH_ORIGIN_VOICE_ID = -1;

export const WASH_GENERATION_TYPE_OPTIONS = [
    {
        val: WashGenerationType.DIGITAL_HUMAN,
        label: "数字人口播混剪",
        desc: "数字人出镜口播\n需选形象和音色",
        icon: "account",
        needAvatar: true,
        needVoice: true,
    },
    {
        val: WashGenerationType.MATERIAL,
        label: "素材混剪",
        desc: "纯素材配旁白\n需选音色",
        icon: "photo",
        needAvatar: false,
        needVoice: true,
    },
    {
        val: WashGenerationType.NEWS,
        label: "新闻体",
        desc: "新闻播报风格\n免选形象音色",
        icon: "file-text",
        needAvatar: false,
        needVoice: false,
    },
] as const;

export const WASH_GENERATION_TYPE_LABEL: Record<number, string> = {
    [WashGenerationType.DIGITAL_HUMAN]: "数字人口播混剪",
    [WashGenerationType.MATERIAL]: "素材混剪",
    [WashGenerationType.NEWS]: "新闻体",
};

export function isWashTask(task: { rewrite_mode?: number | string }): boolean {
    return Number(task?.rewrite_mode) === HotWriteRewriteMode.WASH;
}

export const HOT_WRITE_PLATFORM_OPTIONS = [
    {
        key: HotWritePlatform.DOUYIN,
        label: "抖音",
        platformType: AppTypeEnum.DOUYIN,
        activeBg: "#111827",
        placeholder: "粘贴抖音作品链接",
        iconGray: IconMusicGray,
        iconWhite: IconMusicWhite,
    },
    {
        key: HotWritePlatform.XHS,
        label: "小红书",
        platformType: AppTypeEnum.XHS,
        activeBg: "#FF2442",
        placeholder: "粘贴小红书图文链接",
        iconGray: IconBookGray,
        iconWhite: IconBookWhite,
    },
] as const;

export const HOT_WRITE_PLATFORM_META: Record<
    number,
    { label: string; bg: string; icon: string }
> = {
    [AppTypeEnum.DOUYIN]: { label: "抖音", bg: "#111827", icon: IconMusicWhite },
    [AppTypeEnum.XHS]: { label: "小红书", bg: "#FF2442", icon: IconBookWhite },
};

export function isImageTextTask(task: { media_type?: number | string }): boolean {
    return Number(task?.media_type) === HotWriteMediaType.IMAGE_TEXT;
}

export function getTaskPreviewImages(task: {
    rewritten_images?: string[];
    selected_images?: string[];
    original_images?: string[];
    thumbnail?: string;
}): string[] {
    const rewritten = Array.isArray(task?.rewritten_images) ? task.rewritten_images.filter(Boolean) : [];
    if (rewritten.length) return rewritten;
    // 确认选图后、改写完成前：只展示用户保留的图
    const selected = Array.isArray(task?.selected_images) ? task.selected_images.filter(Boolean) : [];
    if (selected.length) return selected;
    const original = Array.isArray(task?.original_images) ? task.original_images.filter(Boolean) : [];
    if (original.length) return original;
    return task?.thumbnail ? [task.thumbnail] : [];
}
