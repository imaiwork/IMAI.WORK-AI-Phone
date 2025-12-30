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
}

// 创建类型
export enum CreateTypeEnum {
    IMG = "img",
    VIDEO = "video",
    CLUE = "clue",
    MSG = "msg",
    FRIEND = "friend",
    YH = "yh",
    COMMENT = "comment",
    PRIVATE_MESSAGE = "private_message",
}
