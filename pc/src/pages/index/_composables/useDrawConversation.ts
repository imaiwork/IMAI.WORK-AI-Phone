import {
    drawConversationDelete,
    drawConversationDetail,
    drawConversationLists,
    normalizeConversationDetail,
    normalizeConversationList,
    type DrawChatMessage,
    type DrawConversationDetail,
    type DrawConversationItem,
    type DrawTaskAsset,
    type DrawTaskView,
} from "@/api/drawing";

export interface DrawHistorySessionItem {
    id: string;
    title: string;
    timestamp: number;
}

export interface RestoredImageAssistant {
    prompt: string;
    ratio: string;
    resolution: string;
    width: number;
    height: number;
    count: number;
    taskStatus: number;
    taskNo: string;
    errorMsg: string;
    assets: DrawTaskAsset[];
}

export interface RestoredImageTurn {
    user: { text: string; attachments: string[] };
    assistant: RestoredImageAssistant | null;
}

export interface RestoredVideoAssistant {
    prompt: string;
    ratio: string;
    resolution: string;
    type: number;
    typeName: string;
    modelName: string;
    count: number;
    imageUrl: string;
    taskStatus: number;
    taskNo: string;
    errorMsg: string;
    assets: DrawTaskAsset[];
}

export interface RestoredVideoTurn {
    user: { text: string; attachments: string[] };
    assistant: RestoredVideoAssistant | null;
}

const PENDING_STATUSES = new Set([0, 1, 2]);
const FAIL_STATUSES = new Set([4, 5]);

function taskStatus(task: DrawTaskView | null | undefined): number {
    return Number(task?.status ?? 0);
}

function taskNoOf(task: DrawTaskView | null | undefined): string {
    return String(task?.task_no || "").trim();
}

function assetsOf(task: DrawTaskView | null | undefined, type: "image" | "video"): DrawTaskAsset[] {
    return (task?.assets ?? []).filter((a) => a.asset_type === type);
}

function numParam(params: Record<string, any>, key: string, fallback: number): number {
    const n = Number(params?.[key]);
    return Number.isFinite(n) && n > 0 ? n : fallback;
}

function strParam(params: Record<string, any>, key: string, fallback: string): string {
    const v = params?.[key];
    if (v == null || v === "") return fallback;
    return String(v);
}

/** 按 user → assistant 配对；孤立 assistant 也保留（user 为空文案） */
function pairTurns(
    messages: DrawChatMessage[],
): Array<{ user: DrawChatMessage | null; assistant: DrawChatMessage | null }> {
    const turns: Array<{ user: DrawChatMessage | null; assistant: DrawChatMessage | null }> = [];
    let pendingUser: DrawChatMessage | null = null;

    for (const msg of messages) {
        if (msg.role === "user") {
            if (pendingUser) {
                turns.push({ user: pendingUser, assistant: null });
            }
            pendingUser = msg;
            continue;
        }
        if (msg.role === "assistant") {
            turns.push({ user: pendingUser, assistant: msg });
            pendingUser = null;
        }
    }
    if (pendingUser) {
        turns.push({ user: pendingUser, assistant: null });
    }
    return turns;
}

export function toHistorySessionItems(list: DrawConversationItem[]): DrawHistorySessionItem[] {
    return list.map((c) => ({
        id: String(c.id),
        title: c.title || c.last_prompt || "未命名会话",
        timestamp: c.update_time || c.create_time || 0,
    }));
}

export async function loadDrawHistory(mediaType: "image" | "video" | "ppt"): Promise<DrawHistorySessionItem[]> {
    const raw = await drawConversationLists({ media_type: mediaType });
    return toHistorySessionItems(normalizeConversationList(raw));
}

export async function fetchDrawConversationDetail(id: number | string): Promise<DrawConversationDetail | null> {
    const raw = await drawConversationDetail({ id });
    return normalizeConversationDetail(raw);
}

export async function deleteDrawConversation(id: number | string): Promise<void> {
    await drawConversationDelete({ id });
}

export function buildImageTurnsFromDetail(detail: DrawConversationDetail): RestoredImageTurn[] {
    return pairTurns(detail.messages).map(({ user, assistant }) => {
        const params = {
            ...(assistant?.task?.params || {}),
            ...(user?.params || {}),
        };
        const attachments = user?.attachments ?? [];
        const task = assistant?.task ?? null;
        const status = taskStatus(task);
        const imageAssets = assetsOf(task, "image");
        const countFromAssets = imageAssets.length;
        const count = Math.max(1, Math.min(9, numParam(params, "n", countFromAssets || numParam(params, "count", 1))));

        const userView = {
            text: user?.content || "",
            attachments,
        };

        if (!assistant) {
            return { user: userView, assistant: null };
        }

        return {
            user: userView,
            assistant: {
                prompt: String(task?.prompt || user?.content || ""),
                ratio: strParam(params, "ratio", "1:1"),
                resolution: strParam(params, "resolution", ""),
                width: numParam(params, "width", 1024),
                height: numParam(params, "height", 1024),
                count,
                taskStatus: status,
                taskNo: taskNoOf(task),
                errorMsg: String(task?.error_msg || (FAIL_STATUSES.has(status) ? "生成失败" : "")),
                assets: imageAssets,
            },
        };
    });
}

export function buildVideoTurnsFromDetail(detail: DrawConversationDetail): RestoredVideoTurn[] {
    return pairTurns(detail.messages).map(({ user, assistant }) => {
        const params = {
            ...(assistant?.task?.params || {}),
            ...(user?.params || {}),
        };
        const attachments = user?.attachments ?? [];
        const imageFromParams = (() => {
            const single = strParam(params, "image", strParam(params, "image_url", ""));
            if (single) return single;
            if (Array.isArray(params.image) && params.image[0]) return String(params.image[0]);
            if (Array.isArray(params.images) && params.images[0]) return String(params.images[0]);
            return "";
        })();
        const imageUrl = attachments[0] || imageFromParams;
        const hasRef = !!imageUrl;
        const task = assistant?.task ?? null;
        const status = taskStatus(task);
        const videoAssets = assetsOf(task, "video");
        const count = Math.max(
            1,
            Math.min(4, videoAssets.length || numParam(params, "n", numParam(params, "count", 1))),
        );

        const userView = {
            text: user?.content || "",
            attachments,
        };

        if (!assistant) {
            return { user: userView, assistant: null };
        }

        return {
            user: userView,
            assistant: {
                prompt: String(task?.prompt || user?.content || ""),
                ratio: (() => {
                    const meta =
                        params.metadata && typeof params.metadata === "object"
                            ? (params.metadata as Record<string, any>)
                            : {};
                    return strParam(params, "aspect_ratio", strParam(params, "ratio", strParam(meta, "ratio", "16:9")));
                })(),
                resolution: (() => {
                    const meta =
                        params.metadata && typeof params.metadata === "object"
                            ? (params.metadata as Record<string, any>)
                            : {};
                    return strParam(params, "resolution", strParam(meta, "resolution", ""));
                })(),
                type: hasRef ? 1 : 0,
                typeName: hasRef ? "图生视频" : "文生视频",
                modelName: String(task?.model_name || task?.model || ""),
                count,
                imageUrl,
                taskStatus: status,
                taskNo: taskNoOf(task),
                errorMsg: String(task?.error_msg || (FAIL_STATUSES.has(status) ? "生成失败" : "")),
                assets: videoAssets,
            },
        };
    });
}

export interface RestoredPptSlide {
    page: number;
    title: string;
    content: string;
    taskStatus: number;
    taskNo: string;
    errorMsg: string;
    imageUrl: string;
}

export interface RestoredPptTurn {
    topic: string;
    pageCount: number;
    turnKey: string;
    slides: RestoredPptSlide[];
}

function slideFromAssistantMsg(msg: DrawChatMessage, idx: number): RestoredPptSlide {
    const params = {
        ...(msg.task?.params || {}),
        ...(msg.params || {}),
    };
    const task = msg.task ?? null;
    const status = taskStatus(task);
    const imageAssets = assetsOf(task, "image");
    const imageUrl = String(imageAssets[0]?.file_full_url || imageAssets[0]?.file_url || "").trim();
    return {
        page: numParam(params, "ppt_page", idx + 1),
        title: strParam(params, "title", msg.content || `第 ${idx + 1} 页`),
        content: strParam(params, "content", ""),
        taskStatus: status,
        taskNo: taskNoOf(task),
        errorMsg: String(task?.error_msg || (FAIL_STATUSES.has(status) ? "生成失败" : "")),
        imageUrl,
    };
}

function dedupeSlidesByPage(slides: RestoredPptSlide[]): RestoredPptSlide[] {
    // 同页多次生成（单页重生）保留最后一次
    const map = new Map<number, RestoredPptSlide>();
    for (const s of slides) {
        map.set(s.page, s);
    }
    return Array.from(map.values()).sort((a, b) => a.page - b.page);
}

/**
 * PPT：同一会话内多轮（对齐图片创作）。
 * 按 user 切轮，其后连续 assistant 归本轮；同页多次保留最后一次。
 */
export function buildPptTurnsFromDetail(detail: DrawConversationDetail): RestoredPptTurn[] {
    const turns: RestoredPptTurn[] = [];
    let pendingUser: DrawChatMessage | null = null;
    let pendingSlides: RestoredPptSlide[] = [];
    let fallbackKey = 0;

    const flush = () => {
        if (!pendingUser && !pendingSlides.length) return;
        const topic = String(pendingUser?.content || detail.title || "").trim() || "PPT";
        const slides = dedupeSlidesByPage(pendingSlides);
        const pageCount = Math.max(1, numParam(pendingUser?.params || {}, "pages", slides.length || 1));
        turns.push({
            topic,
            pageCount,
            turnKey: strParam(pendingUser?.params || {}, "turn_key", `legacy-${++fallbackKey}`),
            slides,
        });
        pendingUser = null;
        pendingSlides = [];
    };

    for (const msg of detail.messages || []) {
        if (msg.role === "user") {
            flush();
            pendingUser = msg;
            continue;
        }
        if (msg.role === "assistant") {
            pendingSlides.push(slideFromAssistantMsg(msg, pendingSlides.length));
        }
    }
    flush();
    return turns;
}

/** @deprecated 使用 buildPptTurnsFromDetail；保留兼容 */
export function buildPptSessionFromDetail(detail: DrawConversationDetail): RestoredPptTurn {
    const turns = buildPptTurnsFromDetail(detail);
    return (
        turns[turns.length - 1] || {
            topic: detail.title || "PPT",
            pageCount: 1,
            turnKey: "",
            slides: [],
        }
    );
}

export function isDrawTaskPending(status: number): boolean {
    return PENDING_STATUSES.has(status);
}

export function isDrawTaskFailed(status: number): boolean {
    return FAIL_STATUSES.has(status);
}

export function isDrawTaskSuccess(status: number): boolean {
    return status === 3;
}

export function lastUserTextFromDetail(detail: DrawConversationDetail): string {
    for (let i = detail.messages.length - 1; i >= 0; i--) {
        const m = detail.messages[i];
        if (m.role === "user" && m.content) return m.content;
    }
    return detail.title || "";
}

export default function useDrawConversation() {
    return {
        loadDrawHistory,
        fetchDrawConversationDetail,
        deleteDrawConversation,
        buildImageTurnsFromDetail,
        buildVideoTurnsFromDetail,
        buildPptTurnsFromDetail,
        buildPptSessionFromDetail,
        isDrawTaskPending,
        isDrawTaskFailed,
        isDrawTaskSuccess,
        lastUserTextFromDetail,
        toHistorySessionItems,
    };
}
