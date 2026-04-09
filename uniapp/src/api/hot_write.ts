import request from "@/utils/request";

// 爆款仿写列表
export const getHotWriteList = (data: Record<string, any>) => {
    return request.get({ url: "/videoImitation.task/lists", data });
};

// 爆款仿写详情
export const getHotWriteDetail = (data: Record<string, any>) => {
    return request.get({ url: "/videoImitation.task/detail", data });
};

// 爆款仿写删除
export const deleteHotWrite = (data: Record<string, any>) => {
    return request.post({ url: "/videoImitation.task/delete", data });
};

// 爆款仿写创建
export const createHotWrite = (data: Record<string, any>) => {
    return request.post({ url: "/videoImitation.copywriting/video2text", data });
};

// 生成视频
export const generateVideo = (data: Record<string, any>) => {
    return request.post({ url: "/videoImitation.task/generate", data });
};

// 确认发布文案
export const confirmPublishText = (data: Record<string, any>) => {
    return request.post({ url: "/videoImitation.task/confirmPublishText", data });
};

// 预发布
export const createHotWritePublishTask = (data: Record<string, any>) => {
    return request.post({ url: "/videoImitation.publish/add", data });
};
