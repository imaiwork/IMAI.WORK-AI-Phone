export enum DeviceEventAction {
    ADD_DEVICE = "addDevice",
    // 添加账号
    ADD_ACCOUNT = "addAccount",
    // 更新账号
    UPDATE_ACCOUNT = "updateAccount",
}

// 监听类型
export enum ListenerTypeEnum {
    // 账号
    CHOOSE_ACCOUNT = "choose-account",
    // 选择图片
    CHOOSE_IMG = "choose-img",
    // 任务文案
    TASK_COPYWRITER = "task-copywriter",
    // 任务AI文案
    TASK_AI_COPYWRITER = "task-ai-copywriter",
    // 选择线索
    WECHAT_CLUE = "wechat-clue",
    // 选择设备
    CHOOSE_DEVICE = "choose-device",
    // 选择时间
    CHOOSE_DATE = "choose-date",
    // 选择数字人形象
    CHOOSE_ANCHOR_MATERIAL = "choose-anchor-material",
    // 线索词组
    CLUE_LIST = "clue-list",
    // 朋友圈
    CIRCLE_INTERACT = "circle-interact",
    CIRCLE_INTERACT_PROMPT = "circle-interact-prompt",
}

// 创建类型
export enum CreateTypeEnum {
    IMAGE_PUBLISH = "IMAGE_PUBLISH", // 发布图文
    VIDEO_PUBLISH = "VIDEO_PUBLISH", // 发布视频
    CLUE_AUTO = "CLUE_AUTO", // 自动获线索
    CHAT_MANAGE = "CHAT_MANAGE", // 私聊接管
    COMMENT_MARKETING = "COMMENT_MARKETING", // 评论获客
    COLLECT_MARKETING = "COLLECT_MARKETING", // 留痕获客
    FRIEND_ADD = "FRIEND_ADD", // 自动加好友
    ACCOUNT_MAINTAIN = "ACCOUNT_MAINTAIN", // 自动养号
    PRIVATE_MESSAGE = "PRIVATE_MESSAGE", // 私信获客
    CIRCLE = "CIRCLE", // 发朋友圈
    CIRCLE_INTERACT = "CIRCLE_INTERACT", // 朋友圈互动
    WECHAT_MSG = "WECHAT_MSG", // 个微接管
    GROUP_PURCHASE = "GROUP_PURCHASE", // 团购评论截流
    CITY_EXPOSURE = "CITY_EXPOSURE", // 同城曝光
    CITY_INTERCEPT = "CITY_INTERCEPT", // 同城截流
    ACCOUNT_SEARCH = "ACCOUNT_SEARCH", // 抖音账号搜寻获客
}

// 加微任务线索来源，对应 sv.crawlingManual 的 source 字段
export enum ClueSourceEnum {
    // 表格导入
    FILE = 1,
    // 获客任务引用（视频号自动获线索）
    AUTO_CLUE = 2,
    // 抖音获客任务引用（抖音账号搜寻获客抓到的手机号/微信号）
    DY_SEARCH = 3,
}

// 线索卡片上显示的来源文案
export const CLUE_SOURCE_TEXT: Record<ClueSourceEnum, string> = {
    [ClueSourceEnum.FILE]: "文件表格",
    [ClueSourceEnum.AUTO_CLUE]: "获客线索",
    [ClueSourceEnum.DY_SEARCH]: "抖音获客线索",
};

// 抖音获客任务只有已完成/已结束才取得到号，见 CrawlingManualLogic::_getRecordByDySearchClue
export const DY_SEARCH_PICKABLE_STATUS = [3, 4];

export enum ActionKey {
    Like = 1,
    Follow = 2,
    Comment = 3,
    Dm = 4,
}

export const GENDER_LIST = [
    { label: "不限", value: "不限" },
    { label: "男", value: "男" },
    { label: "女", value: "女" },
];

// 设备激活码业务来源标识（预支付 / 支付状态使用）
export const DEVICE_AUTH_PAY_FROM = "device_auth";

// 手机列表筛选 tab
export type DeviceAuthTabKey = "all" | "active" | "inactive";

// 前端 tab → 接口 tab 参数（全部传空字符串）
export const DEVICE_AUTH_TAB_PARAM: Record<DeviceAuthTabKey, number | string> = {
    all: "",
    active: 1,
    inactive: 0,
};

/** 业务类型：1购买CDK 2设备续费 */
export enum DeviceAuthBizType {
    PURCHASE = 1,
    RENEW = 2,
}

// 支付方式（接口 pay_type）：1 微信支付，2 算力支付
export enum DeviceAuthPayType {
    CASH = 1,
    COMPUTE = 2,
}
