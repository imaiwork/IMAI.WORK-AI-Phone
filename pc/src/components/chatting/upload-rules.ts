/**
 * Chat 聊天上传规则（文件解析）
 * 支持格式：TXT, DOCX, PDF, XLSX, EPUB, MOBI, MD, CSV, JSON + 图片 + 视频
 * 文件大小：图片 ≤ 20MB，其他（含视频）≤ 150MB
 */
export const CHAT_DOC_EXTS = ["txt", "docx", "pdf", "xlsx", "epub", "mobi", "md", "csv", "json"];
export const CHAT_IMAGE_EXTS = ["jpg", "jpeg", "png", "gif", "bmp", "webp"];
export const CHAT_VIDEO_EXTS = ["mp4", "avi", "mkv", "mov", "flv", "wmv"];

export const CHAT_UPLOAD_EXTS = [...CHAT_DOC_EXTS, ...CHAT_IMAGE_EXTS, ...CHAT_VIDEO_EXTS];

/** 传给 <input accept> 的字符串 */
export const CHAT_UPLOAD_ACCEPT = CHAT_UPLOAD_EXTS.map((ext) => `.${ext}`).join(",");

/** 图片上限 (MB) */
export const CHAT_IMAGE_MAX_SIZE = 20;
/** 非图片文件上限 (MB) */
export const CHAT_FILE_MAX_SIZE = 150;

export const getFileExt = (name: string): string => name.split(".").pop()?.toLowerCase() ?? "";

export const isImageFile = (file: File): boolean =>
    file.type.startsWith("image/") || CHAT_IMAGE_EXTS.includes(getFileExt(file.name));
