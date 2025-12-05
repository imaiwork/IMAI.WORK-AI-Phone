import request from "@/utils/request";

// 获取设备统计
export const getDeviceStatistics = () => {
    return request.get({ url: "/device.display/display" });
};

// 获取设备列表
export const getDeviceList = (data: any) => {
    return request.get({ url: "/device.device/lists", data });
};

// 获取设备详情
export const getDeviceDetail = (data: any) => {
    return request.get({ url: "/device.device/detail", data });
};

// 更新设备
export const updateDevice = (data: any) => {
    return request.post({ url: "/device.device/update", data });
};

// 解除设备绑定
export const unbindDevice = (data: any) => {
    return request.post({ url: "/device.device/remove", data });
};

/// 添加设备账号
export const addDeviceAccount = (data: any) => {
    return request.post({ url: "/sv.account/add", data });
};

// 更新设备账号
export const updateDeviceAccount = (data: any) => {
    return request.post({ url: "/sv.account/update", data });
};

// 设备账号移除
export function deleteDeviceAccount(data: any) {
    return request.post({ url: "/sv.account/delete", data });
}

// 账号接管状态修改
export const changeAccountStatus = (data: any) => {
    return request.post({ url: "/sv.account/ai", data });
};

// 设备账号列表
export const getDeviceAccountList = (data: any) => {
    return request.get({ url: "/device.account/lists", data });
};

// 获取设备任务列表
export const getDeviceTaskList = (data: any) => {
    return request.get({ url: "/device.task/lists", data });
};

// 发布账号列表
export const getPublishAccountList = (data?: Record<string, any>) => {
    return request.get({ url: "/sv.account/alllists", data });
};

// 矩阵任务新增
export const addMatrixTask = (data: any) => {
    return request.post({ url: "/sv.matrixMediaSetting/add", data });
};

// 矩阵任务详情
export const getMatrixTaskDetail = (data: any) => {
    return request.get({ url: "/sv.matrixMediaSetting/detail", data });
};

// 矩阵文案生成
export const generateMatrixPrompt = (data: any) => {
    return request.post({ url: "/sv.tools/getMatrixCopywriting", data }, { ignoreCancel: true });
};

// 设备任务发布
export const publishDeviceTask = (data: any) => {
    return request.post({ url: "/device.publish/add", data });
};

// 设备任务详情
export const getDeviceTaskDetail = (data: any) => {
    return request.get({ url: "/device.publish/detail", data });
};

// 设备任务列表
export const getDevicePublishList = (data: any) => {
    return request.get({ url: "/device.publish/lists", data });
};

// 设备任务发布记录
export const getDevicePublishRecordList = (data: any) => {
    return request.get({ url: "/device.publish/recordLists", data });
};

// 设备任务私信记录
export const getDevicePrivateChatRecordList = (data: any) => {
    return request.get({ url: "/device.message/lists", data });
};

// 设备任务删除
export const deleteDeviceTask = (data: any) => {
    return request.post({ url: "/device.publish/delete", data });
};

// 更新设备账号任务
export function updateDeviceAccountTask(data: any) {
    return request.post({ url: "/device.publish/accountUpdate", data });
}

// 设备任务日历列表
export const getDeviceTaskCalendarList = (data: any) => {
    return request.get({ url: "/device.calendarTask/lists", data });
};

// 设备任务日历统计
export const getDeviceTaskCalendarStatistics = (data: any) => {
    return request.get({ url: "/device.calendarTask/statistics", data });
};

// 设备任务详情
export const getDeviceTaskSubtasks = (data: any) => {
    return request.post({ url: "/device.calendarTask/subtasks", data });
};

// 设备任务更新名称
export const updateDeviceTaskName = (data: any) => {
    return request.post({ url: "/device.calendarTask/updateName", data });
};

// 设备任务日历删除
export const deleteDeviceTaskCalendar = (data: any) => {
    return request.post({ url: "/device.calendarTask/delete", data });
};

// 私聊接管添加
export const addPrivateChatTask = (data: any) => {
    return request.post({ url: "/device.takeOver/add", data });
};

// 私聊接管详情
export const getPrivateChatTaskDetail = (data: any) => {
    return request.get({ url: "/device.privateChatTask/detail", data });
};

// 私聊接管删除
export const deletePrivateChatTask = (data: any) => {
    return request.post({ url: "/device.takeOver/delete", data });
};

// 养号任务新增
export const addGrowthAccountTask = (data: any) => {
    return request.post({ url: "/device.active/add", data });
};

// 养号任务详情
export const getGrowthAccountTaskDetail = (data: any) => {
    return request.get({ url: "/device.activeAccount/detail", data });
};

// 养号任务删除
export const deleteGrowthAccountTask = (data: any) => {
    return request.post({ url: "/device.active/delete", data });
};

// 手动加微任务新增
export const createManualAddWechat = (data: any) => {
    return request.post({ url: "/sv.crawlingManual/add", data });
};
