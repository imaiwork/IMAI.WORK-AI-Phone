import {
    mapLeadChat,
    mapLeadExport,
    normalizeMapLeadChatResult,
    type MapLeadCard,
} from "@/api/map_lead";
import { DRAW_POLL_ABORTED } from "./useDrawTaskPoll";

export function useWorkbenchMap() {
    const conversationId = ref("");
    const lastMessageId = ref<number | null>(null);
    const nextPage = ref(0);
    const exhausted = ref(false);
    const lastQuery = ref("");
    const currentPage = ref(1);
    /** 取消令牌：cancelPending 后进行中的 submit 应丢弃结果 */
    let submitEpoch = 0;

    const cancelPending = () => {
        submitEpoch += 1;
    };

    const submit = async (query: string, page?: number) => {
        const epoch = submitEpoch;
        const q = query.trim();
        if (!q && !page) throw new Error("请输入获客需求");
        const useQuery = q || lastQuery.value;
        if (!useQuery) throw new Error("请输入获客需求");

        const pageNo = page || 1;
        const raw = await mapLeadChat({
            query: useQuery,
            conversation_id: conversationId.value || undefined,
            page: page || undefined,
            page_size: 10,
        });
        if (epoch !== submitEpoch) {
            throw new Error(DRAW_POLL_ABORTED);
        }
        const result = normalizeMapLeadChatResult(raw, useQuery);
        if (result.conversationId) conversationId.value = result.conversationId;
        lastMessageId.value = result.messageId;
        nextPage.value = result.nextPage;
        exhausted.value = result.exhausted;
        lastQuery.value = result.query || useQuery;
        currentPage.value = pageNo;
        return {
            ...result,
            page: pageNo,
            pageLabel: result.exhausted
                ? `${pageNo}/${pageNo}`
                : result.nextPage
                  ? `${pageNo}/…`
                  : `${pageNo}/${pageNo}`,
        };
    };

    const loadMore = async () => {
        if (!nextPage.value || exhausted.value) {
            throw new Error("没有更多结果了");
        }
        return submit(lastQuery.value, nextPage.value);
    };

    /** 导出：复制下载地址（小程序不支持落盘下载） */
    const exportExcel = async () => {
        if (!lastMessageId.value) throw new Error("暂无可导出的结果");
        const res: any = await mapLeadExport({ message_id: lastMessageId.value });
        const url = String(res?.url || res?.file_url || res?.data?.url || "").trim();
        if (!url) throw new Error("导出失败，未返回下载地址");

        await new Promise<void>((resolve, reject) => {
            uni.setClipboardData({
                data: url,
                success: () => {
                    uni.$u.toast("下载链接已复制，请在浏览器打开");
                    resolve();
                },
                fail: reject,
            });
        });
        return url;
    };

    const resetConversation = () => {
        cancelPending();
        conversationId.value = "";
        lastMessageId.value = null;
        nextPage.value = 0;
        exhausted.value = false;
        lastQuery.value = "";
        currentPage.value = 1;
    };

    const formatCardsMarkdown = (cards: MapLeadCard[]) => {
        if (!cards.length) return "未找到匹配商家";
        return cards
            .map(
                (c, i) =>
                    `**${i + 1}. ${c.name || "未命名"}**\n- 地址：${c.addr || "-"}\n- 电话：${c.phone || "-"}\n- 标签：${c.tag || "-"}\n- 评分：${c.rating || "-"}`,
            )
            .join("\n\n");
    };

    return {
        conversationId,
        lastMessageId,
        nextPage,
        exhausted,
        lastQuery,
        currentPage,
        submit,
        loadMore,
        exportExcel,
        resetConversation,
        cancelPending,
        formatCardsMarkdown,
    };
}
