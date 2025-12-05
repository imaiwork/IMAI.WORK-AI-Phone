import request from "@/utils/request";

// 获取创作列表
export const getCreationList = (params: any) => {
    return request.get({ url: "/sv.videoSetting/lists", params });
};

// 作品视频列表
export const getCreationVideoList = (params: any) => {
    return request.get({ url: "/sv.videoTask/lists", params });
};

// 删除创作
export const deleteCreation = (params: any) => {
    return request.post({ url: "/sv.videoSetting/delete", data: params });
};

// 获取发布列表
export const getPublishList = (params: any) => {
    return request.get({ url: "/sv.publish/lists", params });
};

// 删除发布
export const deletePublish = (params: any) => {
    return request.post({ url: "/sv.publish/delete", data: params });
};

// 发布记录列表
export const getPublishRecordList = (params: any) => {
    return request.get({ url: "/sv.publish/recordLists", params });
};

// 数字人列表
export const getDigitalHumanList = (params: any) => {
    return request.get({ url: "/sv.videoSetting/lists", params });
};

// 删除数字人
export const deleteDigitalHuman = (params: any) => {
    return request.post({ url: "/sv.videoSetting/delete", data: params });
};

// 数字人创作任务列表
export const getDigitalHumanTaskList = (params: any) => {
    return request.get({ url: "/sv.videoTask/lists", params });
};

// 删除数字人创作任务
export const deleteDigitalHumanTask = (params: any) => {
    return request.post({ url: "/sv.videoTask/delete", data: params });
};
