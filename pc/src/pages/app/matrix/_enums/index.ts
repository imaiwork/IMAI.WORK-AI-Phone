export enum SidebarTypeEnum {
    // 快速开始
    QUICK_START = 1,
    // 去发布
    CREATE = 2,
    // 我的发布
    ME_PUBLISH = 3,
    // 发布图文任务
    PUBLISH_IMAGE_TASK = 3,
    // 发布混剪任务
    PUBLISH_MIX_TASK = 4,
    // 数字人创作
    DIGITAL_HUMAN_CREATION = 5,
    // 图文创作
    IMAGE_CREATION = 6,
    // 混剪任务创作
    MIX_TASK_CREATION = 7,
    // 素材库
    MATERIAL_LIBRARY = 8,
    // 生成视频
    GENERATE_VIDEO = 9,
    // 文案库
    COPYWRITING_LIBRARY = 10,
}

export enum PublishTaskTypeEnum {
    VIDEO = 1,
    IMAGE = 2,
}

export enum MaterialTypeEnum {
    VIDEO = 2,
    IMAGE = 1,
    MUSIC = 6,
}

export enum CopywritingTypeEnum {
    TITLE = 1,
    CONTENT = 2,
}

export enum ContentGenModeEnum {
    NEW = "new",
    OLD = "old",
}

export enum ContentTypeEnum {
    ALL = 0,
    TITLE = 1,
    SUBTITLE = 2,
    CONTENT = 3,
    TOPIC = 4,
}

// 文案类型文本映射
export const ContentTypeMap = {
    [ContentTypeEnum.TITLE]: "标题",
    [ContentTypeEnum.SUBTITLE]: "副标题",
    [ContentTypeEnum.CONTENT]: "口播文案",
};

export enum MaterialActionType {
    ADD = "add",
    REPLACE = "replace",
    DELETE = "delete",
    PREVIEW = "preview",
}
