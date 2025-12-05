export enum SidebarTypeEnum {
    TASK = 1,
    AUTO_SOP = 2,
    FLOW = 3,
    BOARD = 4,
    SETTING = 5,
}

export enum PushTypeEnum {
    TASK = 0,
    AUTO_SOP = 1,
}

export enum SendWayEnum {
    NO_SETTING = -1,
    GROUP_SEND = 0,
    SPECIFIED_PROCESS = 1,
    SPECIFIED_STAGE = 2,
    BIRTHDAY_CUSTOMER = 3,
    FESTIVAL_ACTIVITY = 4,
}

export const SendWayMap = {
    [SendWayEnum.NO_SETTING]: "未设置",
    [SendWayEnum.GROUP_SEND]: "群发",
    [SendWayEnum.SPECIFIED_PROCESS]: "指定的流程",
    [SendWayEnum.SPECIFIED_STAGE]: "流程中的特定阶段",
    [SendWayEnum.BIRTHDAY_CUSTOMER]: "生日客户",
    [SendWayEnum.FESTIVAL_ACTIVITY]: "节日活动",
};

export enum BoardHandleTypeEnum {
    CLEAR = 1,
    TRANSFER = 2,
    TRANSFER_TO_CYCLE = 3,
    SEND_MESSAGE = 4,
}
