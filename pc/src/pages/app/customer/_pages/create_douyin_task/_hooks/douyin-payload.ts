import {
    ContactTypeEnum,
    MissActionEnum,
    ScanPositionEnum,
    SearchModeEnum,
    DEFAULT_MISS_DM_LIST,
    DEFAULT_REMARK_LIST,
    DOUYIN_TASK_NAME_PREFIX,
} from "../_enums";
import dayjs from "dayjs";

export interface DouyinFormData {
    name: string;
    device_codes: string[];
    search_mode: SearchModeEnum;
    keywords: string[];
    target_accounts: string[];
    per_kw: number;
    pre_filter: boolean;
    fans_min: number;
    works_min: number;
    active_days: number;
    only_biz: boolean;
    scan_positions: ScanPositionEnum[];
    cover_count: number;
    contact_types: ContactTypeEnum[];
    phone_as_wechat: boolean;
    miss_action: MissActionEnum;
    miss_scripts: string[];
    miss_once: boolean;
    add_type: "0" | "1";
    wechat_id: string[];
    add_rule: string;
    add_number: number;
    add_interval_time: number;
    remarks: string[];
    task_exec_type: number;
    minutes: number;
    task_frep: number;
    custom_date: string[];
    time_config: string[];
    day_cap: number;
    task_ids: string[];
}

// 扫描位置掩码默认值（置顶作品封面暂不露出，仍按默认提交）
const DEFAULT_COVER_COUNT = 3;
const DEFAULT_STITCH = "h";

export function buildDouyinPayload(
    formData: DouyinFormData,
    removedKeywords: string[] = []
) {
    const isKeywordMode = formData.search_mode === SearchModeEnum.KEYWORD;
    const preFilter = isKeywordMode && formData.pre_filter;
    const scanCover = formData.scan_positions.includes(ScanPositionEnum.COVER);
    const contactPhone = formData.contact_types.includes(ContactTypeEnum.PHONE);
    const isMissDm = formData.miss_action === MissActionEnum.DM;

    return {
        name: formData.name,
        device_codes: formData.device_codes,
        search_mode: formData.search_mode,
        // 过滤空行，避免把空字符串当线索词提交
        keywords: (isKeywordMode ? formData.keywords : formData.target_accounts)
            .map((item) => item.trim())
            .filter((item) => item),
        // 本次会话在表单里删掉的关键词，后端从历史同步移除；指定账号模式不产生关键词历史
        removed_keywords: isKeywordMode
            ? removedKeywords.map((item) => item.trim()).filter((item) => item)
            : [],
        per_kw: isKeywordMode ? formData.per_kw : 0,
        scan_config: {
            // 初筛关闭时过滤值必须清零，否则后端仍会按上次填的值过滤
            pre_filter: preFilter,
            fans_min: preFilter ? formData.fans_min : 0,
            works_min: preFilter ? formData.works_min : 0,
            active_days: preFilter ? formData.active_days : 0,
            only_biz: preFilter && formData.only_biz,
            scan_bio: formData.scan_positions.includes(ScanPositionEnum.BIO),
            scan_dyid: formData.scan_positions.includes(ScanPositionEnum.DOUYIN_ID),
            scan_cover: scanCover,
            cover_count: formData.cover_count,
            stitch: scanCover && formData.cover_count > 1 ? DEFAULT_STITCH : "",
            contact_phone: contactPhone,
            contact_wechat: formData.contact_types.includes(ContactTypeEnum.WECHAT),
            contact_qq: formData.contact_types.includes(ContactTypeEnum.QQ),
            // 没认手机号就谈不上「拿手机号当微信加」
            phone_as_wechat: contactPhone && formData.phone_as_wechat,
            miss_action: formData.miss_action,
            miss_scripts: isMissDm ? formData.miss_scripts.filter((item) => item.trim()) : [],
            miss_once: isMissDm && formData.miss_once,
            dedupe: true,
            skip_existing: true,
        },
        add_type: Number(formData.add_type),
        wechat_id: formData.add_type === "1" ? formData.wechat_id.join(",") : "",
        add_rule: formData.add_rule,
        add_number: formData.add_number,
        add_interval_time: formData.add_interval_time,
        // 抖音契约没有单一内容字段，有备注就按随机库提交
        add_remark_enable: formData.add_type === "1" && formData.remarks.length > 0 ? 1 : 0,
        remarks: formData.add_type === "1" && formData.remarks.length > 0 ? formData.remarks : [],
        task_exec_type: formData.task_exec_type,
        minutes: formData.minutes,
        task_ids: formData.task_ids,
        task_frep: String(formData.task_frep),
        time_config: formData.task_exec_type === 1 ? [] : [`${formData.time_config[0]}-${formData.time_config[1]}`],
        custom_date: formData.task_exec_type === 1 ? [] : formData.custom_date,
        day_cap: formData.day_cap,
        exclude_default_id: 1,
    };
}

// keywords / remarks 后端可能返回 JSON 字符串，进页面状态前统一归一化成数组
const parseStringList = (value: any) => {
    if (Array.isArray(value)) return value;
    if (typeof value == "string" && value) {
        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [value];
        } catch {
            return [value];
        }
    }
    return [];
};

/**
 * 抖音获客任务和视频号各自返回字段名不同：clue_total 对应视频号的 crawl_number，
 * added_count 是已加微数。这里统一归一化成视频号列表的字段口径，列表模板可以复用同一套展示。
 */
export function normalizeDouyinTaskRow(row: any) {
    const clueNumber = Number(row.clue_total ?? row.crawl_number ?? 0);
    const addedCount = Number(row.added_count ?? row.completed_add_count ?? 0);
    const manual =
        row.task_exec_type != null ? Number(row.task_exec_type) === 1 : Number(row.auto_type ?? 0) === 0;

    return {
        ...row,
        keywords: parseStringList(row.keywords),
        remarks: parseStringList(row.remarks),
        auto_type: Number(row.auto_type ?? 0),
        status: Number(row.status ?? 0),
        crawl_number: clueNumber,
        added_count: addedCount,
        manual,
        // 复用视频号进度条展示：默认没有托管进度字段时用 已加微/已获客资
        number_of_implemented_keywords: Number(row.number_of_implemented_keywords ?? addedCount),
        implementation_keywords_number: Number(row.implementation_keywords_number ?? clueNumber),
    };
}

export function createDefaultDouyinForm(): DouyinFormData {
    return {
        name: `${DOUYIN_TASK_NAME_PREFIX}${dayjs().format("YYYYMMDDHHmmss")}`,
        device_codes: [],
        search_mode: SearchModeEnum.KEYWORD,
        keywords: [""],
        target_accounts: [""],
        per_kw: 30,
        pre_filter: true,
        fans_min: 500,
        works_min: 5,
        active_days: 90,
        only_biz: false,
        scan_positions: [ScanPositionEnum.BIO, ScanPositionEnum.DOUYIN_ID, ScanPositionEnum.COVER],
        cover_count: DEFAULT_COVER_COUNT,
        contact_types: [ContactTypeEnum.PHONE, ContactTypeEnum.WECHAT],
        phone_as_wechat: true,
        miss_action: MissActionEnum.DM,
        miss_scripts: [...DEFAULT_MISS_DM_LIST],
        miss_once: true,
        add_type: "0",
        wechat_id: [],
        add_rule: "all",
        add_number: 15,
        add_interval_time: 10,
        remarks: [...DEFAULT_REMARK_LIST],
        task_exec_type: 1,
        minutes: 15,
        task_frep: 1,
        custom_date: [],
        time_config: [dayjs().format("HH:mm"), dayjs().add(30, "minute").format("HH:mm")],
        day_cap: 50,
        task_ids: [],
    };
}
