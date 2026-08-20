export enum TrafficConfigType {
    SPH = 1,
    Video = 2,
    GroupPurchase = 3,
    City = 4,
    Chat = 5,
    Guard = 6,
}

export enum GrouponTab {
    Favorite = 1,
    Search = 2,
}

export enum GrouponAction {
    Like = 1,
    Follow = 2,
    Comment = 3,
    Dm = 4,
}

export enum KeyWordsType {
    AcquisitionWords = "acquisitionWords",
    InterceptionSearchWords = "interceptionSearchWords",
    InterceptionMatchWords = "interceptionMatchWords",
    DmScripts = "dmScripts",
    GrouponNicknameFilters = "grouponNicknameFilters",
    GrouponCommentKeywords = "grouponCommentKeywords",
    CityCommentKeywords = "cityNicknameFilters",
}

export enum ConfigKeyType {
    AcquisitionLimit = "acquisitionLimit",
    AcquisitionStrategy = "acquisitionStrategy",
    InterceptionLimit = "interceptionLimit",
    InterceptionStrategy = "interceptionStrategy",
}

export interface TimeOption {
    label: string;
    value: number;
}
export interface StrategyOption {
    label: string;
    value: number;
}
export interface GenderOption {
    label: string;
    value: number;
}
export interface GrouponTabOption {
    label: string;
    value: GrouponTab;
}
export interface GrouponActionOption {
    label: string;
    value: GrouponAction;
    icon: string;
}
export interface DistanceOption {
    label: string;
    value: number;
}

export enum LikeType {
    Avatar = 1,
    Video = 2,
}

export interface LikeTypeOption {
    label: string;
    value: LikeType;
}

export interface GrouponSearchOption {
    icon: string;
    label: string;
}

export const TITLE_MAP: Partial<Record<KeyWordsType, string>> = {
    [KeyWordsType.AcquisitionWords]: "添加获客线索词",
    [KeyWordsType.InterceptionSearchWords]: "添加视频搜索词",
    [KeyWordsType.InterceptionMatchWords]: "添加评论匹配词",
    [KeyWordsType.DmScripts]: "添加私信转化话术",
    [KeyWordsType.GrouponNicknameFilters]: "编辑昵称过滤词",
    [KeyWordsType.GrouponCommentKeywords]: "编辑评论关键词",
};

export const DISTANCE_LIST: DistanceOption[] = [
    { label: "不限", value: 0 },
    { label: "1km内", value: 1 },
    { label: "3km内", value: 3 },
    { label: "5km内", value: 5 },
    { label: "10km内", value: 10 },
];

export const TIME_LIST: TimeOption[] = [
    { label: "当天", value: 1 },
    { label: "2天内", value: 2 },
    { label: "3天内", value: 3 },
    { label: "4天内", value: 4 },
    { label: "5天内", value: 5 },
    { label: "6天内", value: 6 },
    { label: "7天内", value: 7 },
    { label: "不限制", value: -1 },
];

export const STRATEGY_LIST: StrategyOption[] = [
    { label: "AI自动补充", value: 1 },
    { label: "循环使用", value: 2 },
    { label: "停止使用", value: 3 },
];

export const GENDER_LIST: GenderOption[] = [
    { label: "不限", value: 0 },
    { label: "男", value: 1 },
    { label: "女", value: 2 },
];

export const GROUPON_TAB_LIST: GrouponTabOption[] = [
    { label: "收藏团购", value: GrouponTab.Favorite },
    { label: "搜索团购", value: GrouponTab.Search },
];

export const GROUPON_FAVORITE_LIST: GrouponSearchOption[] = [
    { icon: "account", label: "主页" },
    { icon: "bookmark", label: "收藏夹" },
    { icon: "shopping-cart", label: "团购Tab" },
    { icon: "chat", label: "评论列表" },
];

export const GROUPON_SEARCH_LIST: GrouponSearchOption[] = [
    { icon: "home", label: "主页" },
    { icon: "search", label: "搜索类型" },
    { icon: "map", label: "筛选距离" },
    { icon: "chat", label: "评论列表" },
];

export const GROUPON_ACTION_LIST: GrouponActionOption[] = [
    { label: "点赞", value: GrouponAction.Like, icon: "heart" },
    { label: "关注", value: GrouponAction.Follow, icon: "star" },
    { label: "评论", value: GrouponAction.Comment, icon: "chat" },
    { label: "私信", value: GrouponAction.Dm, icon: "chat" },
];

// 点赞方式
export const LIKE_TYPE_LIST: LikeTypeOption[] = [
    { label: "点赞头像", value: LikeType.Avatar },
    { label: "点赞视频", value: LikeType.Video },
];

export interface ConfigData {
    acquisitionWords: string[];
    acquisitionLimit: number;
    acquisitionStrategy: number;
    interceptionSearchWords: string[];
    interceptionMatchWords: string[];
    interceptionLimit: number;
    interceptionStrategy: number;
    commentScripts: string[];
    dmScripts: string[];
    messageNumber: number;
    commentNumber: number;
    replyNumber: number;
    contentPublishTime: number;
    commentPublishTime: number;
    grouponTab: GrouponTab;
    grouponSearchKeywords: string;
    grouponTypeKeyword: string;
    grouponActions: GrouponAction[];
    grouponNicknameFilters: string[];
    grouponDistance: number;
    grouponPublishDay: number;
    grouponCommentNum: number;
    grouponCommentKeywords: string[];
    grouponLikeType: LikeType;
    grouponWatchSeconds: number;
    grouponReachInterval: number;
    grouponGenderFilter: number;
    grouponFilterIp: string;
    grouponFilterRegion: string;
    cityActions: GrouponAction[];
    cityDistance: number;
    cityGenderFilter: number;
    cityAgeMin: number | string;
    cityAgeMax: number | string;
    cityCommentKeywords: string[];
    cityWatchSeconds: number;
    cityReachInterval: number;
    cityNicknameFilters: string[];
    cityVideoMatchNum: number;
    cityVideoCommentNum: number;
    cityCommentFansMin: number;
    cityCommentFansMax: number;
    cityFollowMin: number;
    cityFollowMax: number;
    cityCommentNumber: number;
    videoMessageNumber: number;
    cityMessageNumber: number;
    grouponMessageNumber: number;
}

export const defaultConfigData: ConfigData = {
    acquisitionWords: [],
    acquisitionLimit: 50,
    acquisitionStrategy: 1,
    interceptionSearchWords: [],
    interceptionMatchWords: [],
    interceptionLimit: 30,
    interceptionStrategy: 1,
    commentScripts: [],
    dmScripts: [],
    messageNumber: 15,
    commentNumber: 15,
    replyNumber: 0,
    contentPublishTime: 0,
    commentPublishTime: 0,
    grouponTab: GrouponTab.Favorite,
    grouponSearchKeywords: "",
    grouponTypeKeyword: "",
    grouponActions: [],
    grouponNicknameFilters: [],
    grouponDistance: 0,
    grouponPublishDay: 1,
    grouponCommentNum: 1,
    grouponCommentKeywords: [],
    grouponLikeType: LikeType.Avatar,
    grouponWatchSeconds: 10,
    grouponReachInterval: 10,
    grouponGenderFilter: 0,
    grouponFilterIp: "",
    grouponFilterRegion: "",
    cityActions: [],
    cityDistance: 0,
    cityGenderFilter: 0,
    cityAgeMin: "",
    cityAgeMax: "",
    cityCommentKeywords: [],
    cityWatchSeconds: 10,
    cityReachInterval: 10,
    cityNicknameFilters: [],
    cityVideoMatchNum: 0,
    cityVideoCommentNum: 0,
    cityCommentFansMin: 0,
    cityCommentFansMax: 0,
    cityFollowMin: 0,
    cityFollowMax: 0,
    cityCommentNumber: 15,
    videoMessageNumber: 15,
    cityMessageNumber: 15,
    grouponMessageNumber: 15,
};
