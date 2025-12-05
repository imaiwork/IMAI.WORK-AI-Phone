import request from "@/utils/request";

// 获取任务列表
export const getTaskList = (data: any) => {
    return request.get({ url: "/sv.crawlingTask/lists", data });
};

// 任务创建
export const createTask = (data: any) => {
    return request.post({ url: "/sv.crawlingTask/add", data });
};

// 任务重试
export const retryTask = (data: any) => {
    return request.post({ url: "/sv.crawlingTask/retry", data });
};

// 任务状态变更
export const changeTaskStatus = (data: any) => {
    return request.post({ url: "/sv.crawlingTask/changeStatus", data });
};

// 任务编辑
export const updateTask = (data: any) => {
    return request.post({ url: "/sv.crawlingTask/update", data });
};

// 任务详情
export const getTaskDetail = (data: any) => {
    return request.get({ url: "/sv.crawlingTask/detail", data });
};

// 获取任务线索词
export const getTaskClue = (data: any) => {
    return request.get({ url: "/sv.crawlingTask/listsRecords", data });
};

// 获取任务线索关键词
export const getTaskClueKeywords = (data: any) => {
    return request.get({ url: "/sv.crawlingTask/keywords", data });
};

// ai关键词生成
export const getAiKeywords = (data: any) => {
    return request.post({ url: "/sv.tools/getSearchTerms", data });
};
