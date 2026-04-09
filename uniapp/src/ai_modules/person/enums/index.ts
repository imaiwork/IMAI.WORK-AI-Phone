// 人设类型
export enum PersonTypeEnum {
    PERSONAL_IP = 1,
    BUSINESS_SERVICE = 2,
    LOCAL_BUSINESS = 3,
}

export const PersonTypeMap = {
    [PersonTypeEnum.PERSONAL_IP]: "个人IP",
    [PersonTypeEnum.BUSINESS_SERVICE]: "企业服务",
    [PersonTypeEnum.LOCAL_BUSINESS]: "本地商家",
};

// 监听类型
export enum ListenerTypeEnum {
    CIRCLE_INTERACT_PROMPT = "circle-interact-prompt",
}
