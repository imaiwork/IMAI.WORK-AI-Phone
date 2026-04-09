import request from "@/utils/request";

// 获取爆款仿写记录
export const getHotWriteRecord = (params: Record<string, any>) => {
    return request.get({ url: "/videoImitation.VideoImitationTask/lists", params });
};

// 删除爆款仿写记录
export const deleteHotWriteRecord = (params: Record<string, any>) => {
    return request.post({ url: "/videoImitation.VideoImitationTask/delete", params });
};
