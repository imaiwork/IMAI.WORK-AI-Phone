//菜单主题类型
export enum ThemeEnum {
    LIGHT = "light",
    DARK = "dark",
}

// 菜单类型
export enum MenuEnum {
    CATALOGUE = "M",
    MENU = "C",
    BUTTON = "A",
}

// 屏幕
export enum ScreenEnum {
    SM = 640,
    MD = 768,
    LG = 1024,
    XL = 1280,
    "2XL" = 1536,
}

export enum SMSEnum {
    LOGIN = "YZMDL",
    MOBILE_LOGIN = "YZMDL",
    BIND_MOBILE = "BDSJHM",
    CHANGE_MOBILE = "BGSJHM",
    FIND_PASSWORD = "ZHDLMM",
    REGISTER = "YZMZC",
}

export enum PolicyAgreementEnum {
    SERVICE = "service",
    PRIVACY = "privacy",
    PAY = "pay",
    DISTRIBUTION = "distribution",
}

/* 消息类型 */
export const E_Msg = {
    TEXT: 1,
    IMAGE: 2,
    GOODS: 3,
};

export enum LoginPopupTypeEnum {
    LOGIN,
    MOBILE_LOGIN,
    REGISTER,
    FORGOT_PWD_MOBILE,
    FORGOT_PWD_MAILBOX,
    BIND_MOBILE,
    WECHAT_LOGIN,
}

export enum ClientEnum {
    PC = "pc",
    MOBILE = "mobile",
    QQ = "qq",
    WECHAT = "wechat",
    ALIPAY = "alipay",
}

export enum ToolEnum {
    HOME = 0,
    CHAT = 1,
    AID = 2,
    STAFF = 3,
    DATABASE = 4,
    CREATIVE_RECORD = 5,
    MORE = 6,
    DEVICE = 7,
    AGENT = 8,
}

export const ToolEnumMap = {
    [ToolEnum.CHAT]: "工作台",
    [ToolEnum.STAFF]: "AI员工",
    [ToolEnum.AID]: "岗位员工",
    [ToolEnum.DATABASE]: "AI知识库",
    [ToolEnum.DEVICE]: "AI终端",
    [ToolEnum.AGENT]: "AI智能体",
};

export enum TokensSceneEnum {
    CHAT = "chat",
    SCENE_CHAT = "scene_chat",
    TEXT_TO_IMAGE = "text_to_image",
    IMAGE_TO_IMAGE = "image_to_image",
    GOODS_IMAGE = "goods_image",
    MODEL_IMAGE = "model_image",
    TEXT_TO_POSTERIMG = "txt_to_posterimg",
    VOLC_TEXT_TO_IMAGE = "volc_txt_to_img",
    VOLC_TEXT_TO_IMAGE_V2 = "volc_txt_to_img_v2",
    VOLC_IMAGE_TO_IMAGE_V2 = "volc_img_to_img_v2",
    VOLC_TEXT_TO_POSTERIMG = "volc_txt_to_posterimg",
    VOLC_TEXT_TO_POSTERIMG_V2 = "volc_txt_to_posterimg_v2",
    VOLC_TEXT_TO_VIDEO = "volc_text_to_video",
    VOLC_IMAGE_TO_VIDEO = "volc_image_to_video",
    VOLC_TXT_TO_POSTERIMG = "volc_txt_to_posterimg",
    DOUBAO_TEXT_TO_VIDEO = "doubao_txt_to_video",
    DOUBAO_IMAGE_TO_VIDEO = "doubao_img_to_video",
    MATTING = "matting",
    MEETING = "meeting",
    MIND_MAP = "mind_map",
    MUSIC = "music",
    AI_DRAW_TEXT_PROMPT = "ai_draw_text_prompt",
    AI_DRAW_PIC_PROMPT = "ai_draw_pic_prompt",
    AI_DRAW_VIDEO_PROMPT = "ai_draw_video_prompt",
    AUDIO_TO_TEXT = "audio_to_text",
    HUMAN_AVATAR = "human_avatar",
    HUMAN_AUDIO = "human_audio",
    HUMAN_VOICE = "human_voice",
    HUMAN_VIDEO = "human_video",
    HUMAN_AVATAR_PRO = "human_avatar_pro",
    HUMAN_AUDIO_PRO = "human_audio_pro",
    HUMAN_VOICE_PRO = "human_voice_pro",
    HUMAN_VIDEO_PRO = "human_video_pro",
    HUMAN_VIDEO_ADVANCED = "human_video_ym",
    HUMAN_AUDIO_ADVANCED = "human_audio_ym",
    HUMAN_VOICE_ADVANCED = "human_voice_ym",
    HUMAN_AVATAR_ADVANCED = "human_avatar_ym",
    HUMAN_VIDEO_ELITE = "human_video_ymt",
    HUMAN_AUDIO_ELITE = "human_audio_ymt",
    HUMAN_VOICE_ELITE = "human_voice_ymt",
    HUMAN_AVATAR_ELITE = "human_avatar_ymt",
    HUMAN_VIDEO_CHANJING = "human_video_chanjing",
    HUMAN_AUDIO_CHANJING = "human_audio_chanjing",
    HUMAN_VOICE_CHANJING = "human_voice_chanjing",
    HUMAN_AVATAR_CHANJING = "human_avatar_chanjing",
    KNOWLEDGE_CREATE = "knowledge_create",
    AI_XHS = "ai_xhs",
    SPH_OCR = "sph_ocr",
    SPH_LOCAL_OCR = "sph_local_ocr",
    SPH_AI_CLUE = "sph_search_terms",
    KEYWORD_TO_COPYWRITING = "keyword_to_copywriting",
}

// 应用类型
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
    // 设备在线
    DEVICE_ONLINE = "deviceOnline",
    // 设备离线
    DEVICE_OFFLINE = "deviceOffline",
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
    DEVICE_OFFLINE = 4005,
    // 设备已上线
    DEVICE_ONLINE = 2006,
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
}

// ws错误消息
export const DeviceWsMessage = {
    [DeviceCmdCodeEnum.CONNECT_ERROR]: "连接失败，请检查网络连接",
    [DeviceCmdCodeEnum.PUSH_MESSAGE_ERROR]: "推送消息异常，请检查网络连接",
    [DeviceCmdCodeEnum.DEVICE_OFFLINE]: "设备已断开，请重新启动设备",
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

export enum AppKeyEnum {
    LADDER_PLAYER = "ladder_player",
    INTERVIEW = "interview",
    MEETING_MINUTES = "meeting_minutes",
    MIND_MAP = "mind_map",
    PERSON_WECHAT = "person_wechat",
    DIGITAL_HUMAN = "digital_human",
    DRAWING = "drawing",
    TELEMARKETING = "telemarketing",
    SERVICE = "service",
    TAX = "tax",
    LAW = "law",
    WORD = "word",
    PPT = "ppt",
    COMPANY_WECHAT = "company_wechat",
    STATEMENT = "statement",
    POSTER = "poster",
    CONTRACT = "contract",
    REDBOOK = "redbook",
    DOUBYIN = "douyin",
    KUAISHOU = "kuaishou",
    SPH = "sph",
    LIVE = "live",
    AI_MIX = "ai_mix",
    DRAW_GOODS = "draw_goods",
    DRAW_POSTER = "draw_poster",
    DRAW_FASHION = "draw_fashion",
    DRAW_VIDEO = "draw_video",
    DRAW_IMAGE = "draw_image",
    MATRIX = "matrix",
    OFFICE = "office",
    MATRIX_PUBLISH = "matrix_publish",
}

export const appKeyNameMap = {
    [AppKeyEnum.LADDER_PLAYER]: "AI员工陪练",
    [AppKeyEnum.INTERVIEW]: "AI面试",
    [AppKeyEnum.MEETING_MINUTES]: "AI智能会议",
    [AppKeyEnum.MIND_MAP]: "AI思维导图",
    [AppKeyEnum.DOUBYIN]: "智能抖音",
    [AppKeyEnum.KUAISHOU]: "智能快手",
    [AppKeyEnum.SPH]: "AI视频号获客",
    [AppKeyEnum.REDBOOK]: "AI被动获客",
    [AppKeyEnum.PERSON_WECHAT]: "AI金牌销售",
    [AppKeyEnum.TELEMARKETING]: "AI电销",
    [AppKeyEnum.SERVICE]: "AI自动客服",
    [AppKeyEnum.DIGITAL_HUMAN]: "AI数字人",
    [AppKeyEnum.DRAWING]: "AI美工",
    [AppKeyEnum.TAX]: "自动报税",
    [AppKeyEnum.LAW]: "AI法律咨询",
    [AppKeyEnum.WORD]: "AI-WORD",
    [AppKeyEnum.PPT]: "AI-PPT",
    [AppKeyEnum.COMPANY_WECHAT]: "智能企微",
    [AppKeyEnum.STATEMENT]: "AI-BI报表",
    [AppKeyEnum.POSTER]: "智能海报",
    [AppKeyEnum.CONTRACT]: "AI合同审查",
    [AppKeyEnum.LIVE]: "AI无人直播",
    [AppKeyEnum.AI_MIX]: "AI混剪",
    [AppKeyEnum.DRAW_GOODS]: "AI商品图",
    [AppKeyEnum.DRAW_POSTER]: "AI海报图",
    [AppKeyEnum.DRAW_FASHION]: "AI服饰图",
    [AppKeyEnum.DRAW_VIDEO]: "AI视频",
    [AppKeyEnum.DRAW_IMAGE]: "AI智能生图",
    [AppKeyEnum.MATRIX]: "矩阵发布",
    [AppKeyEnum.OFFICE]: "AI office",
};

// 模型ID
export enum ModelIdEnum {
    DEEPSEEK_R1 = 4,
    GPT_4O = 2,
    GEMINI_2_5_PRO = 11,
}
