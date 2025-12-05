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
