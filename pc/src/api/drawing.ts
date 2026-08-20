// ============ 新 draw 统一接口（首页 welcome-hero 使用） ============

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

/** 兼容秒/毫秒/日期字符串 → 毫秒时间戳 */
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
    if (!raw || typeof raw !== "object") return null;
    const id = Number(raw.id) || 0;
    if (id <= 0) return null;
    const messages = Array.isArray(raw.messages) ? raw.messages : [];
    return {
        id,
        media_type: String(raw.media_type || ""),
        title: String(raw.title || "未命名会话"),
        messages: messages.map((m: any) => ({
            id: Number(m?.id) || 0,
            role: String(m?.role || ""),
            content: String(m?.content || ""),
            attachments: asStringList(m?.attachments),
            params: asParams(m?.params),
            create_time: m?.create_time,
            task: m?.task && typeof m.task === "object" ? (m.task as DrawTaskView) : null,
        })),
    };
}

// 生图
export function drawGenerateImage(params: any) {
    return $request.post({ url: "/draw.draw/generateImage", params });
}
// 生视频
export function drawGenerateVideo(params: any) {
    return $request.post({ url: "/draw.draw/generateVideo", params });
}
/** 图片提示词优化（中台 Coze，不计费） */
export function drawOptimizeImagePrompt(params: { keywords: string }) {
    return $request.post({ url: "/draw.draw/optimizeImagePrompt", params });
}
/** 视频提示词优化（中台 Coze，不计费） */
export function drawOptimizeVideoPrompt(params: { keywords: string }) {
    return $request.post({ url: "/draw.draw/optimizeVideoPrompt", params });
}
// 查询任务状态
export function drawTaskStatus(params: any) {
    return $request.post({ url: "/draw.draw/getTaskStatus", params });
}
// 会话列表
export function drawConversationLists(params: { media_type?: string }) {
    return $request.get({ url: "/draw.draw/conversationLists", params });
}
// 会话详情
export function drawConversationDetail(params: { id: number | string }) {
    return $request.get({ url: "/draw.draw/conversationDetail", params });
}
// 删除会话
export function drawConversationDelete(params: { id: number | string }) {
    return $request.post({ url: "/draw.draw/conversationDelete", params });
}

/** PPT 智能追问（中台 Coze，不计费） */
export function drawPptFollowup(params: { topic: string }) {
    return $request.post({ url: "/draw.draw/pptFollowup", params });
}

/** PPT 章节大纲（中台 Coze，不计费） */
export function drawPptChapters(params: {
    topic: string;
    page_count: number;
    ppt_scene?: string;
    summary?: Record<string, string>;
}) {
    return $request.post({ url: "/draw.draw/pptChapters", params });
}

/** PPT 单页提交生图（每次 1 页；有结果才扣费） */
export function drawPptSubmitSlides(params: {
    model: string;
    topic: string;
    slides: Array<{ page: number; title: string; content: string }>;
    total_pages?: number;
    is_cover?: boolean;
    ppt_type?: string;
    audience?: string;
    style?: string;
    conversation_id?: number;
    /** 同一会话内一轮生成的分组键 */
    turn_key?: string;
    /** 本轮首页时写一条 user 消息（对齐图片创作多轮） */
    write_user?: boolean;
}) {
    return $request.post({ url: "/draw.draw/pptSubmitSlides", params });
}

/** PPT 单页重生 */
export function drawPptRegenerateSlide(params: {
    model: string;
    topic: string;
    page?: number;
    title?: string;
    content?: string;
    ppt_type?: string;
    audience?: string;
    style?: string;
    conversation_id?: number;
}) {
    return $request.post({ url: "/draw.draw/pptRegenerateSlide", params });
}

// 绘制文生图
export function drawingTextToImage(params: any) {
    return $request.post({ url: "/hd/txt2img", params });
}
// 绘制文生图-即梦
export function drawingTextToImageVolc(params: any) {
    return $request.post({ url: "/hd/txt2volcimg", params });
}

// 绘制图生图
export function drawingImageToImage(params: any) {
    return $request.post({ url: "/hd/img2img", params });
}

// 绘制图生图-即梦
export function drawingImageToImageVolc(params: any) {
    return $request.post({ url: "/hd/img2volcimg", params });
}

// 生成商品图片
export function drawingGoods(params: any) {
    return $request.post({ url: "/hd/segmentImage", params });
}

// 生成AI试衣图片
export function drawingFitting(params: any) {
    return $request.post({ url: "/hd/vton", params });
}

// 即梦文生视频
export function drawingTextToVideo(params: any) {
    return $request.post({ url: "/volc/text2Video", params });
}

// 即梦图生视频
export function drawingImageToVideo(params: any) {
    return $request.post({ url: "/volc/image2Video", params });
}

// 豆包文生视频
export function drawingTextToVideoDoubao(params: any) {
    return $request.post({ url: "/hd.doubao/txt2video", params });
}

// 豆包图生视频
export function drawingImageToVideoDoubao(params: any) {
    return $request.post({ url: "/hd.doubao/img2video", params });
}
// 查询图片生成状态
export function drawingImageStatus(params: any) {
    return $request.post({
        url: "/hd/getTaskStatus",
        params,
    });
}

// 即梦查询视频生成状态
export function drawingVolcVideoStatus(params: any) {
    return $request.post({
        url: "/volc/getTaskStatus",
        params,
    });
}

// 豆包查询视频生成状态
export function drawingDoubaoVideoStatus(params: any) {
    return $request.post({
        url: "/hd.doubao/getTaskStatus",
        params,
    });
}

// 生成图片记录
export function drawingRecord(params: any) {
    return $request.get({
        url: "/hd/lists",
        params,
    });
}

// 生成视频记录
export function drawingVideoRecord(params: any) {
    return $request.get({
        url: "/volc/lists",
        params,
    });
}

// 删除
export function drawingDelete(params: any) {
    return $request.post({
        url: "/hd/deleteImage",
        params,
    });
}
// 删除视频
export function drawingVideoDelete(params: any) {
    return $request.post({
        url: "/volc/deleteVideo",
        params,
    });
}

// 获取模板列表
export function getTemplateList(params: any) {
    return $request.get({ url: "/hd/templates", params });
}

// 新增模板
export function templateAdd(params: any) {
    return $request.post({ url: "/hd/addTemplates", params });
}

// 编辑模板
export function templateEdit(params: any) {
    return $request.post({ url: "/hd/editTemplates", params });
}

// 删除模板
export function templateDelete(params: any) {
    return $request.post({ url: "/hd/deleteTemplates", params });
}

// 图片灵感分类
export function getImagePromptCategoryList(params: any) {
    return $request.get({ url: "/hd/cueImageCategory", params });
}

// 图片灵感列表
export function getImagePromptList(params: any) {
    return $request.post({ url: "/hd/cueImage", params });
}

// 快速组装分类
export function getQuickComposeCategoryList(params: any) {
    return $request.get({ url: "/hd.quickCompose/category", params });
}

// 快速组装列表
export function getQuickComposeList(params: any) {
    return $request.get({ url: "/hd/cueWord", params });
}

// 提示词生成
export function generateCueWord(params: any) {
    return $request.get({ url: "/assistants/sceneDetail", params });
}

// 优秀案例
export function getCaseLists(params: any) {
    return $request.get({ url: "/hd/caseLists", params });
}

// 添加模特
export function addModelCase(params: any) {
    return $request.post({ url: "/hd/addModelCase", params });
}

// 删除模特
export function deleteModelCase(params: any) {
    return $request.post({ url: "/hd/delModelCase", params });
}
