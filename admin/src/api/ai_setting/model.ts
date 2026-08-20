import request from "@/utils/request";

export const getAiModel = () => {
    return request.get({ url: "/setting.ai.models/lists" });
};

// 获取模型详情
export const getAiModelDetail = (params: any) => {
    return request.get({ url: "/setting.ai.models/detail", params });
};

// 编辑模型
export const editModel = (data: any) => {
    return request.post({ url: "/setting.ai.models/edit", data });
};

// 从中台同步对话模型
export const syncAiModel = () => {
    return request.post({ url: "/setting.ai.models/sync" });
};

// 从中台同步生图/生视频模型
export const syncMediaAiModel = () => {
    return request.post({ url: "/setting.ai.models/syncMedia" });
};

// 批量开关国外语言模型（便于小程序提审）：is_enable 0关闭 1开启
export const switchChatModels = (data: { is_enable: 0 | 1 }) => {
    return request.post({ url: "/setting.ai.models/switchChatModels", data });
};
