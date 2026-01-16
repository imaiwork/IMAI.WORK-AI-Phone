// 获取任务列表
export const getTaskList = (params: any) => {
    return $request.get({ url: "/sv.crawlingTask/lists", params });
};

// 删除任务
export const deleteTask = (params: any) => {
    return $request.post({ url: "/sv.crawlingTask/delete", params });
};

// 创建任务
export const createTask = (params: any) => {
    return $request.post({ url: "/sv.crawlingTask/add", params });
};

// 重试任务
export const retryTask = (params: any) => {
    return $request.post({ url: "/sv.crawlingTask/retry", params });
};

// 任务状态变更
export const changeTaskStatus = (params: any) => {
    return $request.post({ url: "/sv.crawlingTask/changeStatus", params });
};

// 任务编辑
export const updateTask = (params: any) => {
    return $request.post({ url: "/sv.crawlingTask/update", params });
};

// 任务详情
export const getTaskDetail = (params: any) => {
    return $request.get({ url: "/sv.crawlingTask/detail", params });
};

// 获取任务线索词
export const getTaskClue = (params: any) => {
    return $request.get({ url: "/sv.crawlingTask/listsRecords", params });
};

// ai关键词生成
export const getAiKeywords = (params: any) => {
    return $request.post({ url: "/sv.tools/getSearchTerms", params });
};

// 获取手动加微任务列表
export const getManualAddWechatList = (params: any) => {
    return $request.get({ url: "/sv.crawlingManual/lists", params });
};

// 删除手动加微任务
export const deleteManualAddWechat = (params: any) => {
    return $request.post({ url: "/sv.crawlingManual/delete", params });
};

// 手动加微任务状态修改
export const updateManualAddWechatStatus = (params: any) => {
    return $request.post({ url: "/sv.crawlingManual/change", params });
};

// 手动加微任务详情
export const getManualAddWechatDetail = (params: any) => {
    return $request.get({ url: "/sv.crawlingManual/detail", params });
};

// 手动加微任务创建
export const createManualAddWechat = (params: any) => {
    return $request.post({ url: "/sv.crawlingManual/add", params });
};

// 手动加微任务记录
export const getManualAddWechatRecord = (params: any) => {
    return $request.get({ url: "/sv.crawlingManual/recordLists", params });
};

// 手动加微任务记录删除
export const deleteManualAddWechatRecord = (params: any) => {
    return $request.post({ url: "/sv.crawlingManual/recordDelete", params });
};
