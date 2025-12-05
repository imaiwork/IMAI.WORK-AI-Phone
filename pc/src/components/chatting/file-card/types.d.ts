import { UPLOAD_STATUS } from "@/composables/usePasteImage";
import type { CSSProperties } from "vue";

export type FilesType =
    | "word"
    | "excel"
    | "ppt"
    | "pdf"
    | "txt"
    | "mark"
    | "image"
    | "audio"
    | "video"
    | "three"
    | "code"
    | "database"
    | "link"
    | "zip"
    | "file"
    | "unknown";

export interface FilesCardProps {
    uid?: string | number;
    name?: string;
    fileSize?: number;
    fileType?: FilesType;
    description?: string;
    url?: string;
    thumbUrl?: string;
    imgFile?: File | Blob;
    iconSize?: string;
    iconColor?: string;
    showDelIcon?: boolean;
    maxWidth?: string;
    noStyle?: boolean;
    style?: CSSProperties;
    hoverStyle?: CSSProperties;
    // 图片文件变体
    imgVariant?: "rectangle" | "square";
    // 图片是否开启预览
    imgPreview?: boolean;
    // 是否显示预览遮罩
    imgPreviewMask?: boolean;
    // 文件状态
    status?: UPLOAD_STATUS;
    // 上传进度
    percent?: number;
    // 失败提示信息
    errorTip?: string;
}

export interface FilesCardEmits {
    (event: "delete", fileProps: FilesCardProps): void;
    (event: "imagePreview", fileProps: FilesCardProps): void;
}

// 文档暂时还没有更新，介绍能获取到这些
export interface FilesCardInstance {
    namePrefix: globalThis.ComputedRef<string>;
    nameSuffix: globalThis.ComputedRef<string>;
    colorMap: Record<FilesType, string>;
}
