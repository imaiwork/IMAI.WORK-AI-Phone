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
}

// 创建类型
export enum CreateTypeEnum {
    IMAGE_PUBLISH = "IMAGE_PUBLISH", // 发布图文
    VIDEO_PUBLISH = "VIDEO_PUBLISH", // 发布视频
    CLUE_AUTO = "CLUE_AUTO", // 自动获线索
    CHAT_MANAGE = "CHAT_MANAGE", // 私聊接管
    COMMENT_MARKETING = "COMMENT_MARKETING", // 评论获客
    DM_MARKETING = "DM_MARKETING", // 私信获客
    FRIEND_ADD = "FRIEND_ADD", // 自动加好友
    ACCOUNT_MAINTAIN = "ACCOUNT_MAINTAIN", // 自动养号
    PRIVATE_MESSAGE = "PRIVATE_MESSAGE", // 私信获客
    CIRCLE = "CIRCLE", // 发朋友圈
    CIRCLE_INTERACT = "CIRCLE_INTERACT", // 朋友圈互动
}
