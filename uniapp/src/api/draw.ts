import request from "@/utils/request";

/** 会话列表项 */
export interface DrawConversationItem {
    id: number;
    media_type: "image" | "video" | string;
    title: string;
    last_prompt: string;
    message_count: number;
    update_time: number;
    create_time: number;
}

export interface DrawTaskAsset {
    asset_type: string;
    file_url?: string;
    file_full_url?: string;
    sort?: number;
}

export interface DrawTaskView {
    id?: number;
    task_no?: string;
    prompt?: string;
    status?: number;
    progress?: number;
    error_msg?: string;
    model?: string;
    model_name?: string;
    params?: Record<string, any>;
    assets?: DrawTaskAsset[];
}

export interface DrawChatMessage {
    id: number;
    role: "user" | "assistant" | string;
    content: string;
    attachments: string[];
    params: Record<string, any>;
    create_time?: number | string;
    task: DrawTaskView | null;
}

export interface DrawConversationDetail {
    id: number;
    media_type: "image" | "video" | string;
    title: string;
    messages: DrawChatMessage[];
}

export function parseDrawTimestamp(raw: unknown): number {
    if (raw == null || raw === "") return 0;
    if (typeof raw === "number" && Number.isFinite(raw)) {
        return raw < 1e12 ? raw * 1000 : raw;
    }
    const s = String(raw).trim();
    if (!s) return 0;
    if (/^\d+$/.test(s)) {
        const n = Number(s);
        return n < 1e12 ? n * 1000 : n;
    }
    const ms = Date.parse(s.replace(/-/g, "/"));
    return Number.isFinite(ms) ? ms : 0;
}

function asStringList(raw: unknown): string[] {
    if (!Array.isArray(raw)) return [];
    return raw.map((u) => String(u || "").trim()).filter(Boolean);
}

function asParams(raw: unknown): Record<string, any> {
    if (raw && typeof raw === "object" && !Array.isArray(raw)) {
        return raw as Record<string, any>;
    }
    if (typeof raw === "string" && raw) {
        try {
            const parsed = JSON.parse(raw);
            if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
                return parsed;
            }
        } catch {
            /* ignore */
        }
    }
    return {};
}

export function normalizeConversationList(raw: any): DrawConversationItem[] {
    const lists = Array.isArray(raw?.lists) ? raw.lists : Array.isArray(raw) ? raw : [];
    return lists
        .map((c: any) => ({
            id: Number(c?.id) || 0,
            media_type: String(c?.media_type || ""),
            title: String(c?.title || c?.last_prompt || "未命名会话"),
            last_prompt: String(c?.last_prompt || ""),
            message_count: Number(c?.message_count) || 0,
            update_time: parseDrawTimestamp(c?.update_time),
            create_time: parseDrawTimestamp(c?.create_time),
        }))
        .filter((c: DrawConversationItem) => c.id > 0);
}

export function normalizeConversationDetail(raw: any): DrawConversationDetail | null {
    if (!raw || typeof raw !== "object" || Array.isArray(raw)) return null;
    // 兼容偶发未解包：{ data: { id, messages } }
    const payload = raw.messages != null || raw.id != null ? raw : raw.data;
    if (!payload || typeof payload !== "object" || Array.isArray(payload)) return null;
    const id = Number(payload.id) || 0;
    if (id <= 0) return null;
    const messages = Array.isArray(payload.messages) ? payload.messages : [];
    return {
        id,
        media_type: String(payload.media_type || ""),
        title: String(payload.title || "未命名会话"),
        messages: messages.map((m: any) => ({
            id: Number(m?.id) || 0,
            role: String(m?.role || "").toLowerCase(),
            content: String(m?.content || ""),
            attachments: asStringList(m?.attachments),
            params: asParams(m?.params),
            create_time: m?.create_time,
            task: m?.task && typeof m.task === "object" ? (m.task as DrawTaskView) : null,
        })),
    };
}

export const isDrawTaskPending = (status?: number) => status === 1 || status === 2;
export const isDrawTaskSuccess = (status?: number) => status === 3;
export const isDrawTaskFailed = (status?: number) => status === 4 || status === 5;

/** 取任务资源 URL；可按 asset_type 过滤（视频任务需排除封面 image） */
export function getDrawAssetUrls(task?: DrawTaskView | null, assetType?: string): string[] {
    const assets = Array.isArray(task?.assets) ? task!.assets : [];
    return assets
        .filter((a: any) => {
            if (!assetType) return true;
            return String(a?.asset_type || "").toLowerCase() === assetType.toLowerCase();
        })
        .map((a: any) =>
            String(a?.file_full_url || a?.file_url || a?.url || a?.uri || "").trim(),
        )
        .filter(Boolean);
}

/** 仅视频资源（对齐 PC：过滤掉封面图） */
export function getDrawVideoUrls(task?: DrawTaskView | null): string[] {
    const videos = getDrawAssetUrls(task, "video");
    if (videos.length) return videos;
    // 兼容未带 asset_type 的旧数据：按后缀兜底，避免再把封面图塞进来
    const assets = Array.isArray(task?.assets) ? task!.assets : [];
    return assets
        .map((a: any) =>
            String(a?.file_full_url || a?.file_url || a?.url || a?.uri || "").trim(),
        )
        .filter((url) => !!url && /\.(mp4|webm|mov|m3u8)(\?|$)/i.test(url));
}

export function drawGenerateImage(data: any) {
    return request.post({ url: "/draw.draw/generateImage", data });
}

export function drawGenerateVideo(data: any) {
    return request.post({ url: "/draw.draw/generateVideo", data });
}

export function drawOptimizeImagePrompt(data: { keywords: string }) {
    return request.post({ url: "/draw.draw/optimizeImagePrompt", data });
}

export function drawOptimizeVideoPrompt(data: { keywords: string }) {
    return request.post({ url: "/draw.draw/optimizeVideoPrompt", data });
}

export function drawTaskStatus(data: any) {
    return request.post({ url: "/draw.draw/getTaskStatus", data });
}

export function drawConversationLists(data: { media_type?: string }) {
    return request.get({ url: "/draw.draw/conversationLists", data });
}

export function drawConversationDetail(data: { id: number | string }) {
    return request.get({ url: "/draw.draw/conversationDetail", data });
}

export function drawConversationDelete(data: { id: number | string }) {
    return request.post({ url: "/draw.draw/conversationDelete", data });
}

export function drawPptFollowup(data: { topic: string }) {
    return request.post({ url: "/draw.draw/pptFollowup", data });
}

export function drawPptChapters(data: {
    topic: string;
    page_count: number;
    ppt_scene?: string;
    summary?: Record<string, string>;
}) {
    return request.post({ url: "/draw.draw/pptChapters", data });
}

export function drawPptSubmitSlides(data: Record<string, any>) {
    return request.post({ url: "/draw.draw/pptSubmitSlides", data });
}

export function drawPptRegenerateSlide(data: Record<string, any>) {
    return request.post({ url: "/draw.draw/pptRegenerateSlide", data });
}

/** 图片灵感/优秀案例列表（对齐 PC getImagePromptList） */
export function getImagePromptList(data: {
    cid?: number | string;
    page_no?: number;
    page_size?: number;
}) {
    return request.post({ url: "/hd/cueImage", data });
}
