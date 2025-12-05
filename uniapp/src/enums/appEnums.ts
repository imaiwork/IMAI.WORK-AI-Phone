//菜单主题类型
export enum ThemeEnum {
    LIGHT = "light",
    DARK = "dark",
}

// 客户端
export enum ClientEnum {
    MP_WEIXIN = 1, // 微信-小程序
    OA_WEIXIN = 2, // 微信-公众号
    H5 = 3, // H5
    IOS = 5, //苹果
    ANDROID = 6, //安卓
}

export enum SMSEnum {
    LOGIN = "YZMDL",
    BIND_MOBILE = "BDSJHM",
    CHANGE_MOBILE = "BGSJHM",
    FIND_PASSWORD = "ZHDLMM",
    REGISTER = "YZMZC",
}

export enum SearchTypeEnum {
    HISTORY = "history",
}

// 用户资料
export enum FieldType {
    NONE = "",
    AVATAR = "avatar",
    USERNAME = "account",
    NICKNAME = "nickname",
    SEX = "sex",
}

// 支付结果
export enum PayStatusEnum {
    SUCCESS = "success",
    FAIL = "fail",
    PENDING = "pending",
}

// 页面状态
export enum PageStatusEnum {
    LOADING = "loading", // 加载中
    NORMAL = "normal", // 正常
    ERROR = "error", // 异常
    EMPTY = "empty", // 为空
}

export enum ToolEnum {
    CHAT = 0,
    DRAWING = 1,
    MIND_MAP = 2,
    MEETING_MINUTES = 3,
    MUSIC = 4,
    VOICE = 5,
}

export const ToolEnumMap = {
    [ToolEnum.CHAT]: "通用聊天",
    [ToolEnum.DRAWING]: "绘画机器人",
    [ToolEnum.MIND_MAP]: "思维导图机器人",
    [ToolEnum.MEETING_MINUTES]: "会议纪要机器人",
    [ToolEnum.MUSIC]: "音乐机器人",
    [ToolEnum.VOICE]: "练练机器人",
};

export enum TokensSceneEnum {
    CHAT = "chat",
    SCENE_CHAT = "scene_chat",
    TEXT_TO_IMAGE = "text_to_image",
    IMAGE_TO_IMAGE = "image_to_image",
    GOODS_IMAGE = "goods_image",
    MODEL_IMAGE = "model_image",
    MATTING = "matting",
    MEETING = "meeting",
    MIND_MAP = "mind_map",
    MUSIC = "music",
    AI_DRAW_TEXT_PROMPT = "ai_draw_text_prompt",
    AI_DRAW_PIC_PROMPT = "ai_draw_pic_prompt",
    AUDIO_TO_TEXT = "audio_to_text",
    HUMAN_AVATAR = "human_avatar",
    HUMAN_AUDIO = "human_audio",
    HUMAN_VOICE = "human_voice",
    HUMAN_VIDEO = "human_video",
    HUMAN_AVATAR_PRO = "human_avatar_pro",
    HUMAN_AUDIO_PRO = "human_audio_pro",
    HUMAN_VOICE_PRO = "human_voice_pro",
    HUMAN_VIDEO_PRO = "human_video_pro",
    HUMAN_PROMPT = "human_prompt",
    HUMAN_VIDEO_ADVANCED = "human_video_ym",
    HUMAN_AUDIO_ADVANCED = "human_audio_ym",
    HUMAN_VOICE_ADVANCED = "human_voice_ym",
    HUMAN_AVATAR_ADVANCED = "human_avatar_ym",
    HUMAN_VIDEO_ELITE = "human_video_ymt",
    HUMAN_AUDIO_ELITE = "human_audio_ymt",
    HUMAN_VOICE_ELITE = "human_voice_ymt",
    HUMAN_AVATAR_ELITE = "human_avatar_ymt",
    HUMAN_AVATAR_CHANJING = "human_avatar_chanjing",
    HUMAN_VIDEO_CHANJING = "human_video_chanjing",
    HUMAN_AUDIO_CHANJING = "human_audio_chanjing",
    HUMAN_VOICE_CHANJING = "human_voice_chanjing",
    HUMAN_AVATAR_SHANJIAN = "human_avatar_shanjian",
    HUMAN_VIDEO_SHANJIAN = "human_video_shanjian",
    HUMAN_VOICE_SHANJIAN = "human_voice_shanjian",
    HUMAN_COPYWRITING_CREATE = "human_copywriting",
    AI_LADDER_PLAYER = "lianlian",
    SHANJIAN_COPYWRITING_CREATE = "shanjian_copywriting_create",
    SPH_OCR = "sph_ocr",
    SPH_LOCAL_OCR = "sph_local_ocr",
    MATRIX_COPYWRITER = "matrix_copywriting",
    SPH_AI_CLUE = "sph_search_terms",
}

export enum KnbTypeEnum {
    VECTOR = "vector",
    RAG = "rag",
}

export enum ModelIdEnum {
    DEEPSEEK_R1 = 4,
    GPT_4O = 2,
}

// 1微信视频号 3小红书 4抖音 5快手
export enum AppTypeEnum {
    SPH = 1,
    WECHAT = 1,
    XHS = 3,
    DOUYIN = 4,
    KUAISHOU = 5,
}

// 设备指令类型
export enum DeviceCmdEnum {
    // 绑定Ws
    BIND_WS = "bindSocket",
    // 检查初始化
    CHECK_INIT = "initCheck",
    // 初始化完成
    INIT_COMPLETE = "initComplete",
    // 添加设备
    ADD_DEVICE = "addDevice",
    // 获取用户信息
    GET_USER_INFO = "getUserInfo",
    // 获取名片
    GET_BUSINESS_CARD = "getCards",
    // 处理指令
    APP_EXEC = "appExec",
    // 打开app
    OPEN_APP = "appOpen",
    // 打开个人中心
    OPEN_PERSON_CENTER = "appPersonalCenter",
    // 获取账号信息
    GET_ACCOUNT_INFO = "appInfo",
    // 数据发送
    DATA_SEND = "appDataSend",
    // 获取账号信息完成
    GET_ACCOUNT_INFO_COMPLETE = "appCompleted",
}

// 设备指令返回码
export enum DeviceCmdCodeEnum {
    // 成功
    SUCCESS = 200,
    // 失败
    FAIL = 400,
    // ws连接失败
    CONNECT_ERROR = 1000,
    // ws推送消息异常
    PUSH_MESSAGE_ERROR = 1001,
    // 消息解析失败
    PARSE_ERROR = 1002,
    // 初始化完成
    INIT_COMPLETE = 2001,
    // 初始化检查
    CHECK_INIT = 2002,
    // 接口异常
    API_ERROR = 3000,
    // 无效请求
    INVALID_REQUEST = 4004,
    // 设备已断开，请重新启动设备
    DEVICE_DISCONNECTED = 4005,
    // 获取卡片错误
    GET_CARD_ERROR = 4006,
    // 缺少用户信息
    MISSING_USER_INFO = 4007,
    // 无效请求，设备参数不存在
    INVALID_REQUEST_DEVICE_PARAM = 4010,
    // 设备初始化未完成
    DEVICE_INIT_NOT_COMPLETE = 4011,
    // 设备指令异常
    DEVICE_CMD_ERROR = 4012,
    // 设备不存在
    DEVICE_NOT_EXIST = 4013,
    // 设备已绑定
    DEVICE_ALREADY_BOUND = 4015,
    OPEN_APP_ERROR = 4046,
    OPEN_PERSON_CENTER_ERROR = 4047,
    GET_ACCOUNT_INFO_ERROR = 4052,
    DATA_SEND_ERROR = 4050,
    GET_ACCOUNT_INFO_COMPLETE_ERROR = 4049,
}

// ws错误消息
export const DeviceWsMessage = {
    [DeviceCmdCodeEnum.CONNECT_ERROR]: "连接失败，请检查网络连接",
    [DeviceCmdCodeEnum.PUSH_MESSAGE_ERROR]: "推送消息异常，请检查网络连接",
    [DeviceCmdCodeEnum.DEVICE_DISCONNECTED]: "设备已断开，请重新启动设备",
    [DeviceCmdCodeEnum.PARSE_ERROR]: "消息解析失败",
    [DeviceCmdCodeEnum.API_ERROR]: "接口异常",
    [DeviceCmdCodeEnum.INVALID_REQUEST]: "无效请求",
    [DeviceCmdCodeEnum.GET_CARD_ERROR]: "获取卡片错误",
    [DeviceCmdCodeEnum.MISSING_USER_INFO]: "缺少用户信息",
    [DeviceCmdCodeEnum.INVALID_REQUEST_DEVICE_PARAM]: "无效请求，设备参数不存在",
    [DeviceCmdCodeEnum.DEVICE_INIT_NOT_COMPLETE]: "设备初始化未完成",
    [DeviceCmdCodeEnum.DEVICE_CMD_ERROR]: "设备指令异常",
    [DeviceCmdCodeEnum.DEVICE_NOT_EXIST]: "设备不存在",
    [DeviceCmdCodeEnum.DEVICE_ALREADY_BOUND]: "设备已绑定",
};
