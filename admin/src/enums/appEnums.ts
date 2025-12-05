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

// 客户端类型
export enum ClientEnum {
    MP_WEIXIN = 1, // 微信-小程序
    OA_WEIXIN = 2, // 微信-公众号
    H5 = 3, // H5
    PC = 4, // PC
    IOS = 5, //苹果
    ANDROID = 6, //安卓
    ADMIN = 7, //后台
}

export const ClientMap = {
    [ClientEnum.MP_WEIXIN]: "微信小程序",
    // [ClientEnum.OA_WEIXIN]: '微信公众号',
    [ClientEnum.H5]: "手机H5",
    [ClientEnum.PC]: "电脑PC",
    // [ClientEnum.IOS]: '苹果APP',
    // [ClientEnum.ANDROID]: '安卓APP',
    [ClientEnum.ADMIN]: "后台创建",
};

export enum DatabaseEnum {
    Document = 1,
    QAndA = 2,
}

export const DatabaseMap = {
    [DatabaseEnum.Document]: "文档型",
    [DatabaseEnum.QAndA]: "问答型",
};

// 剪辑风格
export enum ClipStyleEnum {
    AI_RECOMMEND = 1,
    TECHNOLOGY = 2,
    LIFE = 3,
    MARKETING = 4,
    KNOWLEDGE = 5,
    VARIETY = 6,
}

// 剪辑风格枚举映射
export const ClipStyleMap: any = {
    [ClipStyleEnum.AI_RECOMMEND]: "Ai智能推荐",
    [ClipStyleEnum.TECHNOLOGY]: "科技风格",
    [ClipStyleEnum.LIFE]: "生活风格",
    [ClipStyleEnum.MARKETING]: "营销风格",
    [ClipStyleEnum.KNOWLEDGE]: "知识科普风格",
    [ClipStyleEnum.VARIETY]: "综艺风格",
};

// 知识库类型
export enum KnbTypeEnum {
    RAG = 1,
    VECTOR = 2,
}
