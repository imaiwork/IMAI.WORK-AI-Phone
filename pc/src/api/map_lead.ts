// ===== 类型 =====

export interface MapLeadCard {
    key: string;
    name: string;
    addr: string;
    phone: string;
    tag: string;
    rating: string | number;
}

// 后端会话消息原始结构
export interface RawMapLeadMessage {
    id: number;
    conversation_id: string;
    role: "user" | "assistant";
    content_type: "text" | "error" | "cards" | string;
    content: string;
    status: number; // 1 正常 / 2 错误
    extra:
        | {
              request?: Record<string, any>;
              response?: {
                  code: number;
                  message: string;
                  parsed?: any[];
                  lead_count?: number;
                  total_count?: number;
                  next_page?: number;
                  exhausted?: boolean;
              };
          }
        | any[];
    cards: any[];
    create_time: string;
}

interface RawMapLeadChatData {
    conversation_id: string;
    user_message: RawMapLeadMessage;
    assistant_message: RawMapLeadMessage;
}

// 归一化后的对话结果
export interface MapLeadChatResult {
    conversationId: string;
    /** 助手消息 id,导出 Excel 时作为 message_id */
    messageId: number | null;
    isError: boolean;
    errorMessage: string;
    query: string;
    cards: MapLeadCard[];
    leadCount: number;
    totalCount: number;
    nextPage: number;
    exhausted: boolean;
    assistantContent: string;
}

/** 一轮用户提问 + 助手回复(卡片或错误) */
export interface MapLeadTurn {
    userText: string;
    query: string;
    cards: MapLeadCard[];
    isError: boolean;
    errorMessage: string;
    totalCount: number;
    exhausted: boolean;
    /** 下一页页码,0 表示无下一页 */
    nextPage: number;
    /** 助手消息 id,导出用 */
    messageId: number | null;
}

export interface MapLeadChatParams {
    /** 自然语言搜索内容 */
    query: string;
    conversation_id?: string;
    /** 页码(继续获取时传上次返回的 next_page) */
    page?: number;
    /** 每页数量,范围 1-25 */
    page_size?: number;
    biz?: string;
    city?: string;
    target_count?: number;
}

export interface MapLeadConversationView {
    conversationId: string;
    turns: MapLeadTurn[];
}

// ===== 接口 =====

// 地图获客 - 发送对话消息
// query: 自然语言搜索；conversation_id 续接会话；page 翻页(传上次 next_page)
export function mapLeadChat(params: MapLeadChatParams) {
    return $request.post<RawMapLeadChatData>({ url: "/map.lead/chat", params });
}

// 地图获客 - 会话列表
export function getMapLeadConversations(params: { page_no?: number; page_size?: number; keyword?: string }) {
    return $request.get({ url: "/map.lead/conversations", params });
}

// 地图获客 - 会话详情（消息记录）
export function getMapLeadMessages(params: { conversation_id: string; page_no?: number; page_size?: number }) {
    return $request.get({ url: "/map.lead/messages", params });
}

// 地图获客 - 会话删除（接口待定，先保留位置）
export function deleteMapLeadConversation(params: { conversation_id: string }) {
    return $request.post({ url: "/map.lead/delete", params });
}

// 地图获客 - 按助手消息导出 Excel
export function mapLeadExport(params: { message_id: number | string }) {
    return $request.get(
        {
            url: "/map.lead/export",
            params,
            // 统一按 blob 取回,再按 Content-Type 区分 JSON(url) / 文件流
            responseType: "blob",
        } as any,
        { isReturnDefaultResponse: true, isTransformResponse: false },
    );
}

// ===== 归一化 =====

// 商家卡片字段兜底映射（后端字段名以真实返回为准，这里做多命名兼容）
function normalizeCard(raw: any, index: number): MapLeadCard {
    const name = raw?.name ?? raw?.title ?? "";
    const addr = raw?.address ?? raw?.addr ?? "";
    const phone = raw?.phone ?? raw?.tel ?? raw?.telephone ?? "";
    const tag = raw?.tag ?? raw?.type ?? raw?.category ?? raw?.biz ?? "";
    const rating = raw?.rating ?? raw?.score ?? raw?.star ?? "";
    return {
        key: String(raw?.id ?? raw?.key ?? `${name}-${index}`),
        name: String(name),
        addr: String(addr),
        phone: String(phone),
        tag: String(tag),
        rating,
    };
}

function normalizeAssistantResult(
    assistant: RawMapLeadMessage | undefined,
    fallbackQuery: string,
): Omit<MapLeadChatResult, "conversationId"> {
    // messages 接口: lead_count 等在 extra 顶层; chat 接口: 可能在 extra.response 里
    const extra: Record<string, any> =
        assistant?.extra && !Array.isArray(assistant.extra) ? (assistant.extra as Record<string, any>) : {};
    const nested = extra.response && typeof extra.response === "object" ? extra.response : {};
    const leadCount = Number(nested.lead_count ?? extra.lead_count ?? 0);
    const totalCount = Number(nested.total_count ?? extra.total_count ?? 0);
    const nextPage = Number(nested.next_page ?? extra.next_page ?? 0);
    const exhausted = !!(nested.exhausted ?? extra.exhausted);
    const request = (extra.request ?? nested.request ?? {}) as Record<string, any>;
    const bizCode = Number(nested.code ?? extra.code ?? 0);
    const messageId = Number(assistant?.id);
    const hasMessageId = Number.isFinite(messageId) && messageId > 0;

    const rawCards = Array.isArray(assistant?.cards) ? assistant!.cards : [];
    const isError =
        assistant?.content_type === "error" || assistant?.status === 2 || (bizCode >= 40000 && rawCards.length === 0);

    return {
        messageId: hasMessageId ? messageId : null,
        isError,
        errorMessage: isError ? assistant?.content || nested.message || extra.message || "抓取失败" : "",
        query: String(request.query ?? fallbackQuery ?? ""),
        cards: rawCards.map(normalizeCard),
        leadCount: leadCount || rawCards.length,
        totalCount: totalCount || rawCards.length,
        nextPage,
        exhausted,
        assistantContent: assistant?.content ?? "",
    };
}

function headerGet(headers: any, name: string): string {
    if (!headers) return "";
    if (typeof headers.get === "function") return String(headers.get(name) || "");
    const lower = name.toLowerCase();
    for (const key of Object.keys(headers)) {
        if (key.toLowerCase() === lower) return String(headers[key] ?? "");
    }
    return "";
}

function filenameFromDisposition(disposition: string): string {
    if (!disposition) return "";
    const utf8 = disposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8?.[1]) {
        try {
            return decodeURIComponent(utf8[1].trim());
        } catch {
            return utf8[1].trim();
        }
    }
    const plain = disposition.match(/filename="?([^";]+)"?/i);
    return plain?.[1]?.trim() || "";
}

function triggerBlobDownload(blob: Blob, filename: string) {
    const objectUrl = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = objectUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(objectUrl);
}

function resolveExportUrl(payload: any): string {
    if (!payload) return "";
    if (typeof payload === "string") return payload;
    return String(payload.url || payload.file_url || payload.download_url || payload.path || "");
}

/**
 * 按助手 message_id 导出并触发浏览器下载。
 * 兼容两种后端形态:
 * 1) JSON: { code:1, data:{ url } } / data 为 url 字符串
 * 2) 直接返回 xlsx 等文件流
 */
export async function downloadMapLeadExport(messageId: number | string): Promise<void> {
    if (!messageId) throw new Error("缺少 message_id，无法导出");

    const response: any = await mapLeadExport({ message_id: messageId });
    const raw = response?._data;
    const contentType = headerGet(response?.headers, "content-type").toLowerCase();
    const disposition = headerGet(response?.headers, "content-disposition");
    const fallbackName = `地图获客_${messageId}.xlsx`;

    // 文件流
    if (raw instanceof Blob) {
        if (contentType.includes("json") || raw.type.includes("json")) {
            const text = await raw.text();
            const json = JSON.parse(text);
            if (Number(json?.code) !== 1) {
                throw new Error(json?.msg || "导出失败");
            }
            const url = resolveExportUrl(json?.data);
            if (!url) throw new Error("未获取到导出文件地址");
            window.open(url, "_blank");
            return;
        }
        triggerBlobDownload(raw, filenameFromDisposition(disposition) || fallbackName);
        return;
    }

    // 常规 JSON 包装
    if (raw && typeof raw === "object" && "code" in raw) {
        if (Number(raw.code) !== 1) {
            throw new Error(raw.msg || "导出失败");
        }
        const url = resolveExportUrl(raw.data);
        if (!url) throw new Error("未获取到导出文件地址");
        window.open(url, "_blank");
        return;
    }

    if (typeof raw === "string" && /^https?:\/\//i.test(raw)) {
        window.open(raw, "_blank");
        return;
    }

    throw new Error("导出失败：无法识别的返回格式");
}

/** 兼容 lists / messages / 纯数组 等列表返回 */
function unwrapMessageList(data: any): RawMapLeadMessage[] {
    if (Array.isArray(data)) return data;
    if (Array.isArray(data?.lists)) return data.lists;
    if (Array.isArray(data?.messages)) return data.messages;
    if (Array.isArray(data?.data?.lists)) return data.data.lists;
    if (Array.isArray(data?.data)) return data.data;
    return [];
}

/**
 * 发送对话消息并归一化为前端可直接渲染的结构。
 * 后端已完成语义解析 + 抓取，返回 user_message / assistant_message。
 * 继续获取时传 page = 上次返回的 next_page。
 */
export async function sendMapLeadMessage(
    query: string,
    conversationId?: string,
    opts?: { page?: number; page_size?: number },
): Promise<MapLeadChatResult> {
    const params: MapLeadChatParams = {
        query,
        conversation_id: conversationId,
    };
    if (opts?.page && opts.page > 0) params.page = opts.page;
    if (opts?.page_size && opts.page_size > 0) params.page_size = opts.page_size;

    const data = await mapLeadChat(params);
    const assistant = data?.assistant_message;
    const normalized = normalizeAssistantResult(assistant, query);
    return {
        conversationId: data?.conversation_id ?? conversationId ?? "",
        ...normalized,
    };
}

/**
 * 按 conversation_id 拉取消息记录,配对成多轮 turns(user + assistant)。
 */
export async function loadMapLeadConversation(conversationId: string): Promise<MapLeadConversationView> {
    const data = await getMapLeadMessages({
        conversation_id: conversationId,
        page_no: 1,
        page_size: 999,
    });
    const list = unwrapMessageList(data)
        .slice()
        .sort((a, b) => {
            const ta = a?.create_time || "";
            const tb = b?.create_time || "";
            if (ta !== tb) return ta < tb ? -1 : 1;
            return (a?.id ?? 0) - (b?.id ?? 0);
        });

    const turns: MapLeadTurn[] = [];
    for (let i = 0; i < list.length; i++) {
        const msg = list[i];
        if (msg?.role !== "user") continue;

        let paired: RawMapLeadMessage | undefined;
        if (list[i + 1]?.role === "assistant") {
            paired = list[i + 1];
        } else {
            for (let j = i + 1; j < list.length; j++) {
                if (list[j].role === "user") break;
                if (list[j].role === "assistant") {
                    paired = list[j];
                    break;
                }
            }
        }

        const normalized = normalizeAssistantResult(paired, msg.content || "");
        turns.push({
            userText: msg.content || "",
            query: normalized.query,
            cards: normalized.cards,
            isError: normalized.isError,
            errorMessage: normalized.errorMessage,
            totalCount: normalized.totalCount,
            exhausted: normalized.exhausted || normalized.nextPage <= 0,
            nextPage: normalized.nextPage,
            messageId: normalized.messageId,
        });
    }

    return { conversationId, turns };
}
