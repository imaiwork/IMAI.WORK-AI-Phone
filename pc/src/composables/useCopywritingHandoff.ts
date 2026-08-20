/**
 * 跨模块「文案交接」信箱。
 *
 * 用途:A 模块产出一段文案,跳转到 B 模块的创建页并自动填进输入框。
 * 目前的使用方:GEO 内容 → 数字人纯口播视频。
 *
 * 为什么不用 URL query 直接带文案:
 *   纯口播文案上限 4000 字,中文 encodeURIComponent 后每字约 9 字节,
 *   URL 最长会到 36KB —— 浏览器能扛,但 nginx/CDN 的 request line 限制会 400,
 *   而且文案会明文留在浏览器历史里。所以 URL 只带一个短 key,正文走 sessionStorage。
 *
 * 取用即销毁:避免用户刷新创建页时被重复灌入,也避免文案长期残留。
 */

const PREFIX = "copywriting_handoff:";

/** 只用于生成一次性 key,不需要密码学强度 */
const randomKey = () => Math.random().toString(36).slice(2, 10) + Date.now().toString(36);

export interface HandoffPayload {
    /** 正文,填进口播输入框 */
    content: string;
    /** 可选标题,给需要 {title, content} 结构的页面用(如口播混剪) */
    title?: string;
    /** 来源标记,便于创建页提示用户「文案来自 XXX」 */
    from?: string;
}

export function useCopywritingHandoff() {
    /** 存一份文案,返回要挂到 URL 上的 key */
    const put = (payload: HandoffPayload): string => {
        const key = randomKey();
        try {
            sessionStorage.setItem(PREFIX + key, JSON.stringify(payload));
        } catch (e) {
            // 隐私模式/配额满:降级成不预填,调用方跳转后用户手动粘贴即可
            return "";
        }
        return key;
    };

    /** 按 key 取出并立即删除;取不到返回 null */
    const takeOnce = (key: string | null | undefined): HandoffPayload | null => {
        if (!key) return null;
        try {
            const raw = sessionStorage.getItem(PREFIX + key);
            if (!raw) return null;
            sessionStorage.removeItem(PREFIX + key);
            const data = JSON.parse(raw);
            return data && typeof data.content === "string" ? data : null;
        } catch (e) {
            return null;
        }
    };

    return { put, takeOnce };
}
