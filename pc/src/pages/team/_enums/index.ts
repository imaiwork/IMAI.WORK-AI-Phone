// 团队控制台:导航、授权功能、品牌页签、分区键等静态常量

export interface TeamNavItem {
    key: string;
    type: number;
    label: string;
    icon: string;
}

export interface TeamFeatureApp {
    key: string;
    label: string;
    icon: string;
}

// 左侧导航(团队主可见全部;管理员见前三项+我的卡密;成员见组织信息+我的卡密)
export const NAV_OWNER: TeamNavItem[] = [
    {
        key: "org",
        type: 1,
        label: "组织信息",
        icon: "M3.75 21h16.5M4.5 3h15l.75 18H3.75L4.5 3zM9 7.5h6M9 12h6M9 16.5h3",
    },
    {
        key: "members",
        type: 2,
        label: "成员管理",
        icon: "M15 19.5a3 3 0 00-6 0M12 12.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5zM4.5 19.5a2.25 2.25 0 013.75-1.68M19.5 19.5a2.25 2.25 0 00-3.75-1.68",
    },
    {
        key: "consume",
        type: 3,
        label: "消耗明细",
        icon: "M3.75 3v11.25A2.25 2.25 0 006 16.5h12M3.75 3h-1.5m1.5 0h16.5M7.5 12l3-3 2.25 2.25L16.5 8.25M16.5 8.25h-2.25m2.25 0v2.25",
    },
    {
        key: "brand",
        type: 4,
        label: "品牌管理",
        icon: "M9.813 15.9L9 18.75l-.813-2.85a4.5 4.5 0 00-3.09-3.09L2.25 12l2.847-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.847a4.5 4.5 0 003.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 00-3.09 3.09z",
    },
];

/** 非团队主：查看分配/转移给自己的卡密 */
export const NAV_MY_CARDS: TeamNavItem = {
    key: "cards",
    type: 5,
    label: "我的卡密",
    icon: "M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z",
};

// 授权功能应用
export const FEATURE_APPS: TeamFeatureApp[] = [
    {
        key: "digital_human",
        label: "数字人",
        icon: "M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0",
    },
    {
        key: "video_mix",
        label: "数字人混剪",
        icon: "M3.75 5.25h16.5v13.5H3.75zM3.75 9.75h16.5M8.25 5.25v13.5M15.75 5.25v13.5",
    },
    {
        key: "gaode_lead",
        label: "高德获客",
        icon: "M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 6.5-7.5 11-7.5 11s-7.5-4.5-7.5-11a7.5 7.5 0 1115 0z",
    },
    {
        key: "ai_phone",
        label: "AI手机",
        icon: "M8.25 3h7.5a1.5 1.5 0 011.5 1.5v15a1.5 1.5 0 01-1.5 1.5h-7.5a1.5 1.5 0 01-1.5-1.5v-15A1.5 1.5 0 018.25 3zM11.25 17.25h1.5",
    },
    {
        key: "ai_draw",
        label: "AI作图",
        icon: "M3.75 5.25h16.5v13.5H3.75zM8.25 10.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM3.75 16.5l4.5-4.5 3 3 3.75-3.75 5.25 5.25",
    },
    { key: "ai_ppt", label: "AI PPT", icon: "M3 4.5h18M5.25 4.5v10.5h13.5V4.5M12 15v3M9 21l3-2.25L15 21" },
    {
        key: "sph_lead",
        label: "视频号获客",
        icon: "M15.75 10.5l4.72-2.36a.75.75 0 011.03.67v6.38a.75.75 0 01-1.03.67l-4.72-2.36M4.5 6.75h9.75a1.5 1.5 0 011.5 1.5v7.5a1.5 1.5 0 01-1.5 1.5H4.5A1.5 1.5 0 013 15.75v-7.5a1.5 1.5 0 011.5-1.5z",
    },
    {
        key: "ai_agent",
        label: "AI智能体",
        icon: "M8.25 8.25h7.5v7.5h-7.5zM5.25 5.25h13.5v13.5H5.25zM12 2.25v3M12 18.75v3M2.25 12h3M18.75 12h3",
    },
    {
        key: "llm_chat",
        label: "大模型对话",
        icon: "M8.25 12h.008M12 12h.008M15.75 12h.008M21 12c0 4.14-4.03 7.5-9 7.5-.98 0-1.92-.13-2.8-.37L4.5 20.25l1.02-3.06C4.05 15.9 3 14.05 3 12c0-4.14 4.03-7.5 9-7.5s9 3.36 9 7.5z",
    },
];

// 品牌管理二级页签(按开通流程排序)
export const BRAND_TABS = [
    { key: "site", label: "1. 站点外观", short: "站点外观", step: 1 },
    { key: "mnp", label: "2. 微信小程序", short: "微信小程序", step: 2 },
    { key: "card", label: "充值卡密", short: "充值卡密", step: 0 },
    { key: "users", label: "站点用户", short: "站点用户", step: 0 },
] as const;

// 分区键(与 useSidebar 的 type=1..4 一一对应)
export const SECTION_KEYS = ["org", "members", "consume", "brand", "cards"] as const;
export type TeamSectionKey = (typeof SECTION_KEYS)[number];

/** 成员列表角色筛选(与小程序一致:全部/创始人/管理员/成员) */
export const ROLE_FILTERS = [
    { key: "all", label: "全部", role: 0 },
    { key: "owner", label: "创始人", role: 2 },
    { key: "admin", label: "管理员", role: 3 },
    { key: "member", label: "成员", role: 1 },
] as const;
export type RoleFilterKey = (typeof ROLE_FILTERS)[number]["key"];

/** 消耗明细快捷时间筛选(与小程序一致:合计文案走 sumLabel) */
export const CONSUME_RANGE_FILTERS = [
    { key: "all", label: "全部", range: "", sumLabel: "筛选范围内团队消耗（算力）" },
    { key: "today", label: "今日", range: "today", sumLabel: "今日团队消耗（算力）" },
    { key: "7d", label: "近7天", range: "7d", sumLabel: "近7天团队消耗（算力）" },
    { key: "30d", label: "近30天", range: "30d", sumLabel: "近30天团队消耗（算力）" },
] as const;
export type ConsumeRangeKey = (typeof CONSUME_RANGE_FILTERS)[number]["key"];
