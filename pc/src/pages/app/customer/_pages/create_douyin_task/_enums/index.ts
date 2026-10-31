// 抖音获客任务表单枚举与常量，契约对齐移动端 device.dySearchTask（create_account_search）
export enum SearchModeEnum {
    // 关键词搜寻：用行业泛词搜一批同类账号
    KEYWORD = "keyword",
    // 指定账号：已有名单，逐个进去查
    ACCOUNT = "account",
}

export enum ScanPositionEnum {
    // 简介
    BIO = "bio",
    // 抖音号
    DOUYIN_ID = "dyid",
    // 置顶作品封面（暂不露出，仍按默认值提交）
    COVER = "cover",
}

export const SCAN_POSITION_OPTIONS = [
    { value: ScanPositionEnum.BIO, label: "简介", desc: "「微信同号」「V：xxx」这类写法都认" },
    { value: ScanPositionEnum.DOUYIN_ID, label: "抖音号", desc: "很多商家直接把抖音号改成手机号或微信号" },
];

export enum ContactTypeEnum {
    PHONE = "phone",
    WECHAT = "wechat",
    QQ = "qq",
}

export const CONTACT_TYPE_OPTIONS = [
    { value: ContactTypeEnum.PHONE, label: "手机号" },
    { value: ContactTypeEnum.WECHAT, label: "微信号" },
    { value: ContactTypeEnum.QQ, label: "QQ号" },
];

export enum MissActionEnum {
    // 私信主动问
    DM = "dm",
    // 直接跳过
    SKIP = "skip",
}

export const MISS_ACTION_OPTIONS = [
    { value: MissActionEnum.DM, label: "私信询问" },
    { value: MissActionEnum.SKIP, label: "直接跳过" },
];

// 识别到多种联系方式时拿哪一种加好友（接口码值）
export const ADD_RULE_OPTIONS = [
    { value: "all", label: "全部方式" },
    { value: "phone", label: "仅手机号" },
    { value: "wechat", label: "仅微信号" },
    { value: "phone_first", label: "手机号优先" },
];

// 每个线索词最多取多少个账号，0 = 不限
export const PER_KEYWORD_OPTIONS = [
    { value: 10, label: "10个" },
    { value: 30, label: "30个" },
    { value: 50, label: "50个" },
    { value: 100, label: "100个" },
    { value: 0, label: "不限" },
];

// 一天最多新增多少条客资，0 = 不限
export const DAY_CAP_OPTIONS = [
    { value: 20, label: "20个" },
    { value: 50, label: "50个" },
    { value: 100, label: "100个" },
    { value: 0, label: "不限" },
];

export const DEFAULT_MISS_DM_LIST = [
    "老板你好，看到您做这块，方便留个微信吗？",
    "请问怎么联系您？想咨询下产品",
    "有微信吗～想问下价格",
    "您好，能加个微信详聊吗",
];

export const DEFAULT_REMARK_LIST = [
    "抖音看到的~ 咨询一下~",
    "老板，你好，抖音~",
    "抖音刷到的，通过一下~",
    "你好～通过一下～",
    "hello～想咨询一下～",
];

export const DOUYIN_TASK_NAME_PREFIX = "抖音获客任务";

// 走访记录 scan_source 是逗号拼接的识别位置（bio,dyid,cover）
export const SCAN_SOURCE_TEXT: Record<string, string> = {
    bio: "简介",
    dyid: "抖音号",
    cover: "作品封面",
};

// 走访记录 result 码值 → 颜色语义（result_name 文案由后端返回）
export const RECORD_RESULT_CLASS: Record<number, string> = {
    1: "valid",
    3: "partial",
};
