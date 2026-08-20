import request from "@/utils/request";

export interface MapLeadCard {
    key: string;
    name: string;
    addr: string;
    phone: string;
    tag: string;
    rating: string | number;
}

export interface MapLeadChatParams {
    query: string;
    conversation_id?: string;
    page?: number;
    page_size?: number;
    biz?: string;
    city?: string;
    target_count?: number;
}

export interface MapLeadChatResult {
    conversationId: string;
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

function normalizeCard(raw: any, index: number): MapLeadCard {
    return {
        key: String(raw?.id ?? raw?.key ?? `${raw?.name || ""}-${index}`),
        name: String(raw?.name ?? raw?.title ?? ""),
        addr: String(raw?.address ?? raw?.addr ?? ""),
        phone: String(raw?.phone ?? raw?.tel ?? raw?.telephone ?? ""),
        tag: String(raw?.tag ?? raw?.type ?? raw?.category ?? raw?.biz ?? ""),
        rating: raw?.rating ?? raw?.score ?? raw?.star ?? "",
    };
}

export function normalizeMapLeadChatResult(raw: any, fallbackQuery = ""): MapLeadChatResult {
    const data = raw?.data && typeof raw.data === "object" ? raw.data : raw;
    const assistant = data?.assistant_message || data?.assistant || {};
    const extra: Record<string, any> =
        assistant?.extra && !Array.isArray(assistant.extra) ? assistant.extra : {};
    const nested = extra.response && typeof extra.response === "object" ? extra.response : {};
    const rawCards = Array.isArray(assistant?.cards) ? assistant.cards : [];
    const bizCode = Number(nested.code ?? extra.code ?? 0);
    const messageId = Number(assistant?.id);
    const isError =
        assistant?.content_type === "error" ||
        assistant?.status === 2 ||
        (bizCode >= 40000 && rawCards.length === 0);

    return {
        conversationId: String(data?.conversation_id || ""),
        messageId: Number.isFinite(messageId) && messageId > 0 ? messageId : null,
        isError,
        errorMessage: isError
            ? assistant?.content || nested.message || extra.message || "抓取失败"
            : "",
        query: String(extra?.request?.query ?? nested?.request?.query ?? fallbackQuery),
        cards: rawCards.map(normalizeCard),
        leadCount: Number(nested.lead_count ?? extra.lead_count ?? rawCards.length) || rawCards.length,
        totalCount: Number(nested.total_count ?? extra.total_count ?? rawCards.length) || rawCards.length,
        nextPage: Number(nested.next_page ?? extra.next_page ?? 0),
        exhausted: !!(nested.exhausted ?? extra.exhausted),
        assistantContent: String(assistant?.content || ""),
    };
}

export function mapLeadChat(data: MapLeadChatParams) {
    return request.post({ url: "/map.lead/chat", data });
}

export function getMapLeadConversations(data: {
    page_no?: number;
    page_size?: number;
    keyword?: string;
}) {
    return request.get({ url: "/map.lead/conversations", data });
}

export function getMapLeadMessages(data: {
    conversation_id: string;
    page_no?: number;
    page_size?: number;
}) {
    return request.get({ url: "/map.lead/messages", data });
}

export function deleteMapLeadConversation(data: { conversation_id: string }) {
    return request.post({ url: "/map.lead/delete", data });
}

/** 导出：优先取返回里的 url；小程序端用复制/打开链接降级 */
export function mapLeadExport(data: { message_id: number | string }) {
    return request.get({ url: "/map.lead/export", data });
}
