import { computed, ref } from "vue";
import {
    getDeviceViralRecordList,
    setDeviceViralRecordInterest,
    clearDeviceViralRecordUninterested,
    saveDeviceViralRecordCopywriting,
} from "@/api/person";
import { AppTypeEnum } from "@/enums/appEnums";

// 明天日期字符串，作为爆款库默认查询日期
const getTomorrowDateString = (): string => {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    return uni.$u.timeFormat(date, "yyyy-mm-dd");
};

// 把后端返回的日期 / 时间戳统一转成毫秒时间戳
const normalizeTimestamp = (raw: any): number => {
    if (raw == null || raw === "") return NaN;
    if (typeof raw === "number") {
        return raw.toString().length === 10 ? raw * 1000 : raw;
    }
    // 字符串日期：兼容 iOS / 小程序，将 '-' 替换为 '/'
    return new Date(String(raw).replace(/-/g, "/")).getTime();
};

// 把后端返回的日期 / 时间戳归一化成 "MM月DD日"
const formatViralDate = (raw: any): string => {
    const timestamp = normalizeTimestamp(raw);
    if (Number.isNaN(timestamp)) return "";
    return uni.$u.timeFormat(timestamp, "mm月dd日") || "";
};

export type ViralPlatformKey = "all" | "douyin" | "xiaohongshu" | "kuaishou" | "shipinhao";
export type ViralRealPlatformKey = Exclude<ViralPlatformKey, "all">;

/** 爆款记录 status */
export enum ViralRecordStatusEnum {
    START = 0,
    NO_COPY_VIDEO = 1,
    COPY_MISMATCH = 2,
    COZE_AI = 3,
    MATCHED = 4,
    ABNORMAL = 5,
    FALLBACK = 6,
    ERROR = 7,
}

export const VIRAL_RECORD_STATUS_LABEL: Record<number, string> = {
    [ViralRecordStatusEnum.START]: "开始",
    [ViralRecordStatusEnum.NO_COPY_VIDEO]: "无文案视频",
    [ViralRecordStatusEnum.COPY_MISMATCH]: "文案不符合",
    [ViralRecordStatusEnum.COZE_AI]: "直接由coze纯ai生成",
    [ViralRecordStatusEnum.MATCHED]: "符合条件",
    [ViralRecordStatusEnum.ABNORMAL]: "异常",
    [ViralRecordStatusEnum.FALLBACK]: "兜底",
    [ViralRecordStatusEnum.ERROR]: "错误记录",
};

/** 未能仿写成功的状态 */
export const VIRAL_RECORD_STATUS_FAIL = new Set<number>([
    ViralRecordStatusEnum.NO_COPY_VIDEO,
    ViralRecordStatusEnum.COPY_MISMATCH,
    ViralRecordStatusEnum.ABNORMAL,
    ViralRecordStatusEnum.ERROR,
]);

export const getViralRecordStatusLabel = (status: number): string =>
    VIRAL_RECORD_STATUS_LABEL[status] || "未知状态";

export const isViralRecordFailed = (status: number): boolean => VIRAL_RECORD_STATUS_FAIL.has(status);

export interface ViralPlatformTab {
    key: ViralPlatformKey;
    label: string;
    short: string;
    color: string;
    type: AppTypeEnum | "";
}

export interface ViralItem {
    id: string;
    /** 列表渲染 key；手动排队条目后端 id 可能重复为 -1 */
    listKey: string;
    title: string;
    keyword: string;
    date: string;
    cover: string;
    platforms: ViralRealPlatformKey[];
    likes: string;
    comments: string;
    link: string;
    copywriting: string;
    /** 原始返回，用于保存仿写时回传 */
    rewritten_text?: string;
    account_type?: number;
    /** 手动入库记录 ID（排队中条目后端 id 可能为 -1，用此字段区分） */
    manualImportId?: number;
    isManualImport?: boolean;
    /** 后端状态说明，如排队解析提示 */
    remark?: string;
    /** 爆款处理状态：0开始 1无文案视频 2文案不符合 3纯ai 4符合 5异常 6兜底 7错误 */
    status?: number;
    /** 状态文案：优先手动入库文案，其次 status 映射 */
    statusText?: string;
    sourceText?: string;
    /** 列表 source，手动入库为 manual；不感兴趣接口需回传 */
    source?: string;
    /** 图生图状态：0无需 1待提交 2处理中 3成功 4失败 */
    image_rewrite_status?: number;
}

export interface DismissedViralItem extends ViralItem {
    dismissedAt: string;
}

// 平台 ↔ 后端 account_type 映射（0 未知 1 视频号 3 小红书 4 抖音 5 快手）
const ACCOUNT_TYPE_MAP: Record<ViralRealPlatformKey, number> = {
    shipinhao: AppTypeEnum.SPH,
    xiaohongshu: AppTypeEnum.XHS,
    douyin: AppTypeEnum.DOUYIN,
    kuaishou: AppTypeEnum.KUAISHOU,
};

const ACCOUNT_TYPE_TO_PLATFORM: Record<number, ViralRealPlatformKey> = {
    1: "shipinhao",
    3: "xiaohongshu",
    4: "douyin",
    5: "kuaishou",
};

const viralPlatformTabs: ViralPlatformTab[] = [
    { key: "all", label: "全部", short: "", color: "#2B6EFF", type: "" },
    { key: "douyin", label: "抖音", short: "抖", color: "#111827", type: AppTypeEnum.DOUYIN },
    { key: "xiaohongshu", label: "小红书", short: "红", color: "#EF4444", type: AppTypeEnum.XHS },
    // { key: 'kuaishou', label: '快手', short: '快', color: '#FF6800', type: AppTypeEnum.KUAISHOU },
    // { key: 'shipinhao', label: '视频号', short: '视', color: '#22C55E' }
];

const createEmptyPlatformCounts = (): Record<ViralPlatformKey, number> => ({
    all: 0,
    douyin: 0,
    xiaohongshu: 0,
    kuaishou: 0,
    shipinhao: 0,
});

const formatDismissedTime = (raw?: string | number): string => {
    if (!raw) return uni.$u.timeFormat(new Date(), "mm月dd日 hh:MM") || "";
    const timestamp = normalizeTimestamp(raw);
    if (Number.isNaN(timestamp)) return "";
    return uni.$u.timeFormat(timestamp, "mm月dd日 hh:MM") || "";
};

const toViralItem = (item: DismissedViralItem): ViralItem => ({
    id: item.id,
    listKey: item.listKey,
    title: item.title,
    keyword: item.keyword,
    date: item.date,
    cover: item.cover,
    platforms: item.platforms,
    likes: item.likes,
    comments: item.comments,
    link: item.link,
    copywriting: item.copywriting,
    rewritten_text: item.rewritten_text,
    account_type: item.account_type,
    manualImportId: item.manualImportId,
    isManualImport: item.isManualImport,
    remark: item.remark,
    status: item.status,
    statusText: item.statusText,
    sourceText: item.sourceText,
    source: item.source,
    image_rewrite_status: item.image_rewrite_status,
});

const pickText = (value: unknown): string => {
    if (value == null) return "";
    if (typeof value === "string") return value.trim();
    if (typeof value === "number") return String(value);
    return "";
};

/** copywriting 可能是对象 / JSON 字符串，仿写正文取其中的 rewritten_text */
const resolveCopywritingPayload = (raw: unknown): Record<string, any> | null => {
    if (!raw) return null;
    if (typeof raw === "string") {
        const text = raw.trim();
        if (!text) return null;
        if (text.startsWith("{") || text.startsWith("[")) {
            try {
                const parsed = JSON.parse(text);
                if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) return parsed;
            } catch {
                return null;
            }
        }
        return null;
    }
    if (typeof raw === "object" && !Array.isArray(raw)) return raw as Record<string, any>;
    return null;
};

const resolveRewrittenText = (record: Record<string, any>): string => {
    const fromTop = pickText(record.rewritten_text);
    if (fromTop) return fromTop;

    const payload = resolveCopywritingPayload(record.copywriting);
    // 优先 rewritten_text；图文/旧数据可能只有 content / text
    const fromPayload = pickText(payload?.rewritten_text);
    if (fromPayload) return fromPayload;

    // copywriting 本身就是纯文案字符串时兼容
    if (typeof record.copywriting === "string" && !payload) {
        const plain = pickText(record.copywriting);
        if (plain) return plain;
    }
    return pickText(record.copy);
};

// 后端返回 → 前端 ViralItem，字段名兼容多种命名以减少耦合
const mapRecordToViralItem = (record: Record<string, any>): ViralItem => {
    const accountType = Number(record.account_type ?? 0);
    const platform = ACCOUNT_TYPE_TO_PLATFORM[accountType];
    const rawDate = record.day || record.create_time || record.updated_at || "";
    const dateText = formatViralDate(rawDate);
    const copyPayload = resolveCopywritingPayload(record.copywriting);
    const copywriting = resolveRewrittenText(record);
    const manualImportId = Number(record.manual_import_id ?? 0) || 0;
    const sourceText = String(record.source_text ?? "").trim();
    const source = String(record.source ?? "").trim();
    const isManualImport =
        manualImportId > 0 ||
        source === "manual" ||
        sourceText.includes("手动") ||
        String(record.keyword ?? "") === "手动入库";
    const rawTitle = pickText(record.title ?? record.name);
    const normalizedTitle = pickText(record.title_normalized);
    const copyTitle = pickText(copyPayload?.title);
    // 手动入库未解析完时 title 常为「手动入库」，优先展示归一化标题 / 仿写标题
    const title =
        normalizedTitle && (!rawTitle || rawTitle === "手动入库")
            ? normalizedTitle
            : rawTitle || copyTitle || normalizedTitle;
    const id = String(record.id ?? record.record_id ?? "");
    const listKey =
        manualImportId > 0 && (!id || Number(id) <= 0) ? `manual_${manualImportId}` : id || `manual_${manualImportId}`;

    return {
        id,
        listKey,
        title,
        keyword: String(record.keyword ?? record.tracking_word ?? record.hot_word ?? ""),
        date: dateText,
        cover: String(record.cover ?? record.cover_url ?? record.thumb ?? record.image ?? "").trim(),
        platforms: platform ? [platform] : [],
        likes: String(record.likes ?? record.like_num ?? record.digg_count ?? ""),
        comments: String(record.comments ?? record.comment_num ?? record.comment_count ?? ""),
        link: String(record.link ?? record.share_url ?? record.url ?? ""),
        copywriting,
        rewritten_text: copywriting || undefined,
        account_type: accountType,
        manualImportId: manualImportId || undefined,
        isManualImport,
        remark: String(record.remark ?? "").trim(),
        status: Number(record.status ?? 0),
        statusText: (() => {
            const status = Number(record.status ?? 0);
            const manualText = String(record.manual_import_status_text ?? "").trim();
            // 已出明确结果：以 status 为准，避免 manual_import_status_text 仍残留「排队中」
            if (
                status === ViralRecordStatusEnum.MATCHED ||
                status === ViralRecordStatusEnum.COZE_AI ||
                status === ViralRecordStatusEnum.FALLBACK
            ) {
                return "";
            }
            if (isViralRecordFailed(status)) {
                return getViralRecordStatusLabel(status);
            }
            // 仅开始/排队中才用手动入库文案（如「排队中」）
            if (status === ViralRecordStatusEnum.START) {
                return manualText || getViralRecordStatusLabel(status);
            }
            return isManualImport ? manualText : "";
        })(),
        sourceText,
        source: source || (isManualImport ? "manual" : undefined),
        image_rewrite_status: Number(record.image_rewrite_status ?? 0),
    };
};

const mapRecordToDismissed = (record: Record<string, any>): DismissedViralItem => ({
    ...mapRecordToViralItem(record),
    dismissedAt: formatDismissedTime(record.update_time || record.updated_at || record.dismissed_at),
});

// ===== 模块级单例：detail 页 / dismissed_viral 页共享同一份状态 =====
const personaId = ref<string>("");
const activeViralPlatform = ref<ViralPlatformKey>("all");
const expandedViralIds = ref<string[]>([]);
const viralKeywords = ref<string[]>([]);
const viralList = ref<ViralItem[]>([]);
const viralPlatformCounts = ref<Record<ViralPlatformKey, number>>(createEmptyPlatformCounts());
const dismissedViralList = ref<DismissedViralItem[]>([]);
const listLoading = ref(false);
const dismissedLoading = ref(false);
const dayParam = ref<string>(getTomorrowDateString());
const hasCustomDayParam = ref(false);

const applyExtendTabsCount = (tabs: Array<Record<string, any>> | undefined | null): void => {
    if (!Array.isArray(tabs) || !tabs.length) return;
    const next = createEmptyPlatformCounts();
    tabs.forEach((tab) => {
        const accountType = Number(tab?.account_type);
        const count = Number(tab?.count) || 0;
        if (accountType === 0) {
            next.all = count;
            return;
        }
        const key = ACCOUNT_TYPE_TO_PLATFORM[accountType];
        if (key) next[key] = count;
    });
    viralPlatformCounts.value = next;
};

const adjustViralPlatformCount = (item: ViralItem, delta: number): void => {
    const next = { ...viralPlatformCounts.value };
    next.all = Math.max(0, (next.all || 0) + delta);
    item.platforms.forEach((platform) => {
        next[platform] = Math.max(0, (next[platform] || 0) + delta);
    });
    viralPlatformCounts.value = next;
};

const refreshDefaultDayParam = (): void => {
    if (hasCustomDayParam.value) return;
    const tomorrow = getTomorrowDateString();
    if (dayParam.value === tomorrow) return;
    dayParam.value = tomorrow;
    viralList.value = [];
    dismissedViralList.value = [];
    expandedViralIds.value = [];
    viralPlatformCounts.value = createEmptyPlatformCounts();
};

const resolveAccountType = (key: ViralPlatformKey): number | undefined => {
    if (key === "all") return undefined;
    return ACCOUNT_TYPE_MAP[key as ViralRealPlatformKey];
};

const buildListParams = (overrides: Record<string, any> = {}): Record<string, any> => {
    refreshDefaultDayParam();
    const accountType = resolveAccountType(activeViralPlatform.value);
    const params: Record<string, any> = {
        day: dayParam.value,
        page_no: 1,
        page_size: 50,
        ...overrides,
    };
    if (accountType !== undefined) params.account_type = accountType;
    if (personaId.value) params.persona_id = personaId.value;
    return params;
};

const fetchViralList = async (): Promise<void> => {
    listLoading.value = true;
    try {
        const res = await getDeviceViralRecordList(buildListParams({ is_interested: 1 }));
        const lists = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : [];
        viralList.value = lists.map(mapRecordToViralItem);
        applyExtendTabsCount(res?.extend?.tabs);
        // 重新拉取后清掉过期的展开态（手动排队条目用 listKey）
        expandedViralIds.value = expandedViralIds.value.filter((key) =>
            viralList.value.some((v) => v.listKey === key),
        );
    } catch (error: any) {
        if (error) uni.$u?.toast?.(error || "爆款列表加载失败");
    } finally {
        listLoading.value = false;
    }
};

const fetchDismissedList = async (): Promise<void> => {
    refreshDefaultDayParam();
    dismissedLoading.value = true;
    try {
        // 不感兴趣列表不按平台过滤，展示全部，避免与 detail 页的平台 tab 联动产生误解
        const params: Record<string, any> = {
            day: dayParam.value,
            page_no: 1,
            page_size: 50,
            is_interested: 0,
        };
        if (personaId.value) params.persona_id = personaId.value;
        const res = await getDeviceViralRecordList(params);
        const lists = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : [];
        dismissedViralList.value = lists.map(mapRecordToDismissed);
    } catch (error: any) {
        if (error) uni.$u?.toast?.(error || "记录加载失败");
    } finally {
        dismissedLoading.value = false;
    }
};

const filteredViralList = computed(() => {
    if (activeViralPlatform.value === "all") return viralList.value;
    const platform = activeViralPlatform.value as ViralRealPlatformKey;
    return viralList.value.filter((item) => item.platforms.includes(platform));
});

export interface UseViralTabOptions {
    /** 当前人设 ID（detail 页传入，dismissed 页可缺省读共享状态） */
    personaId?: string;
    /** 列表日期，默认明天 */
    day?: string;
}

export const useViralTab = (options?: UseViralTabOptions) => {
    if (options?.personaId !== undefined) personaId.value = options.personaId;
    if (options?.day) {
        dayParam.value = options.day;
        hasCustomDayParam.value = true;
    } else {
        hasCustomDayParam.value = false;
        refreshDefaultDayParam();
    }

    const handleViralPlatform = async (key: ViralPlatformKey) => {
        if (activeViralPlatform.value === key) return;
        activeViralPlatform.value = key;
        await fetchViralList();
    };

    const handleToggleViralCopy = (item: ViralItem) => {
        const key = item.listKey || item.id;
        if (expandedViralIds.value.includes(key)) {
            expandedViralIds.value = expandedViralIds.value.filter((id) => id !== key);
            return;
        }
        expandedViralIds.value = [...expandedViralIds.value, key];
    };

    const handleUpdateViralCopy = (item: ViralItem, value: string) => {
        item.copywriting = value;
    };

    // 手动入库等条目需回传列表 source（如 manual）
    const buildInterestPayload = (item: ViralItem, is_interested: 0 | 1) => {
        const payload: {
            ids: Array<string | number>;
            is_interested: 0 | 1;
            source?: string;
        } = {
            ids: [item.id],
            is_interested,
        };
        if (item.source) payload.source = item.source;
        return payload;
    };

    // 不感兴趣：先调接口，成功后再从主列表移除并入 dismissed 列表
    const handleDismissViral = async (item: ViralItem) => {
        if (!item?.id) return;
        try {
            await setDeviceViralRecordInterest(buildInterestPayload(item, 0));
            dismissedViralList.value = [
                { ...item, dismissedAt: formatDismissedTime() },
                ...dismissedViralList.value.filter((viral) => viral.listKey !== item.listKey),
            ];
            viralList.value = viralList.value.filter((viral) => viral.listKey !== item.listKey);
            expandedViralIds.value = expandedViralIds.value.filter((id) => id !== item.listKey);
            adjustViralPlatformCount(item, -1);
            uni.showToast({ title: "已移除，不再推荐此类内容", icon: "none", duration: 2000 });
        } catch (error: any) {
            uni.showToast({ title: error || "操作失败", icon: "none", duration: 2000 });
        }
    };

    // 保存仿写文案
    const handleSaveViralCopy = async (item?: ViralItem) => {
        if (!item?.id) {
            uni.showToast({ title: "文案已保存", icon: "none", duration: 2000 });
            return;
        }
        try {
            await saveDeviceViralRecordCopywriting({
                id: item.id,
                rewritten_text: item.copywriting || "",
            });
            item.rewritten_text = item.copywriting || "";
            uni.showToast({ title: "文案已保存", icon: "none", duration: 2000 });
        } catch (error: any) {
            uni.showToast({ title: error || "保存失败", icon: "none", duration: 2000 });
        }
    };

    const handleCopyText = (data: string, title: string) => {
        if (!data) return;
        uni.setClipboardData({
            data,
            success: () => {
                uni.showToast({ title, icon: "none", duration: 2000 });
            },
        });
    };

    const handleViewDismissedViral = () => {
        uni.navigateTo({
            url: "/ai_modules/person/pages/dismissed_viral/dismissed_viral",
        });
    };

    // 撤回：从不感兴趣列表恢复到主列表（手动入库同样需传 source）
    const handleRestoreDismissedViral = async (item: DismissedViralItem) => {
        if (!item?.id) return;
        try {
            await setDeviceViralRecordInterest(buildInterestPayload(item, 1));
            if (!viralList.value.some((viral) => viral.listKey === item.listKey)) {
                viralList.value = [toViralItem(item), ...viralList.value];
                adjustViralPlatformCount(item, 1);
            }
            dismissedViralList.value = dismissedViralList.value.filter((viral) => viral.listKey !== item.listKey);
            uni.showToast({ title: "已撤回，AI 将重新推荐同类内容", icon: "none", duration: 2000 });
        } catch (error: any) {
            uni.showToast({ title: error || "撤回失败", icon: "none", duration: 2000 });
        }
    };

    // 清空不感兴趣（按当前 day 维度清空）
    const handleClearDismissedViral = async () => {
        if (!dismissedViralList.value.length) return;
        refreshDefaultDayParam();
        const payload = {
            day: dayParam.value,
        };
        try {
            await clearDeviceViralRecordUninterested(payload);
            dismissedViralList.value = [];
            uni.showToast({ title: "已全部清除", icon: "none", duration: 2000 });
        } catch (error: any) {
            uni.showToast({ title: error || "清除失败", icon: "none", duration: 2000 });
        }
    };

    return {
        activeViralPlatform,
        dayParam,
        dismissedLoading,
        dismissedViralList,
        expandedViralIds,
        filteredViralList,
        listLoading,
        personaId,
        viralKeywords,
        viralList,
        viralPlatformCounts,
        viralPlatformTabs,
        fetchViralList,
        fetchDismissedList,
        handleClearDismissedViral,
        handleCopyText,
        handleDismissViral,
        handleRestoreDismissedViral,
        handleSaveViralCopy,
        handleToggleViralCopy,
        handleUpdateViralCopy,
        handleViewDismissedViral,
        handleViralPlatform,
    };
};
