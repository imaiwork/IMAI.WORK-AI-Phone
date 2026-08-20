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
