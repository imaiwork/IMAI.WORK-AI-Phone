/**
 * Chat 聊天上传规则（对齐 PC pc/src/components/chatting/upload-rules.ts）
 * 支持格式：TXT, DOCX, PDF, XLSX, EPUB, MOBI, MD, CSV, JSON + 图片 + 视频
 * 文件大小：图片 ≤ 20MB，其他（含视频）≤ 150MB
 */
export const CHAT_DOC_EXTS = ["txt", "docx", "pdf", "xlsx", "epub", "mobi", "md", "csv", "json"];
export const CHAT_IMAGE_EXTS = ["jpg", "jpeg", "png", "gif", "bmp", "webp"];
export const CHAT_VIDEO_EXTS = ["mp4", "avi", "mkv", "mov", "flv", "wmv"];

export const CHAT_UPLOAD_EXTS = [...CHAT_DOC_EXTS, ...CHAT_IMAGE_EXTS, ...CHAT_VIDEO_EXTS];

/** 图片上限 (MB) */
export const CHAT_IMAGE_MAX_SIZE = 20;
/** 非图片文件上限 (MB) */
export const CHAT_FILE_MAX_SIZE = 150;

export const isChatImageExt = (ext: string) => CHAT_IMAGE_EXTS.includes((ext || "").toLowerCase());

/** 按扩展名取大小上限 (MB) */
export const getChatSizeLimit = (ext: string) =>
    isChatImageExt(ext) ? CHAT_IMAGE_MAX_SIZE : CHAT_FILE_MAX_SIZE;
