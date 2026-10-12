/** 分析记录本地缓存：点开过的热点把搜索/分析/文案存下来，再进直接还原不重跑 */

export const FLOW_CACHE_KEY = "hotspot_flow_cache_v1";
export const FLOW_CACHE_LIMIT = 15;
/** 与后端 RecordService::normalizeTopic 同口径 */
export const HOT_TOPIC_MAX_LEN = 120;

export const normalizeHotTitle = (title: unknown) =>
    String(title || "")
        .trim()
        .slice(0, HOT_TOPIC_MAX_LEN);

export const flowCacheKey = (platform: unknown, title: unknown) => {
    const p = String(platform || "").trim();
    const t = normalizeHotTitle(title);
    return p && t ? `${p}|${t}` : "";
};

export const readFlowCacheAll = (): Record<string, any> => {
    try {
        const all = uni.getStorageSync(FLOW_CACHE_KEY);
        return all && typeof all === "object" ? all : {};
    } catch {
        return {};
    }
};

/** 热榜接口未回填时，用本地已搜索/分析过的记录点亮「已分析」 */
export const applyLocalAnalyzedMarks = (
    topics: Record<string, any>[],
    fallbackPlatform: string,
): Record<string, any>[] => {
    const cache = readFlowCacheAll();
    return topics.map((topic) => {
        if (topic.analyzed) return topic;
        const key = flowCacheKey(topic.platform || fallbackPlatform, topic.title);
        if (key && cache[key]?.research) {
            return { ...topic, analyzed: true };
        }
        return topic;
    });
};
