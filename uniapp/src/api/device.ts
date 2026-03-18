import request, { RequestEventStreamConfig } from "@/utils/request";

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
export const createMatrixTask = (data: any) => {
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
export const createPrivateChatTask = (data: any) => {
    return request.post({ url: "/device.takeOver/add", data });
};

// 私聊接管更新
export const updatePrivateChatTask = (data: any) => {
    return request.post({ url: "/device.takeOver/update", data });
};

// 私聊接管详情
export const getPrivateChatTaskDetail = (data: any) => {
    return request.get({ url: "/device.takeOver/detail", data });
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

// 截流任务新增
export const createClosureTask = (data: any) => {
    return request.post({ url: "/sv.leadScraping/add", data });
};

// 获取自动任务详情
export const getAutoTaskDetail = (data: any) => {
    return request.get({ url: "/auto.device/detail", data });
};

// 自动任务配置新增
export const createAutoTask = (data: any) => {
    return request.post({ url: "/auto.device/add", data });
};

// 自动任务线索词获客配置更新
export const createAutoTaskClueConfig = (data: any) => {
    return request.post({ url: "/auto.clue/update", data });
};

// 获取自动任务线索词获客配置
export const getAutoTaskClueConfigDetail = (data: any) => {
    return request.get({ url: "/auto.clue/detail", data });
};

// 自动任务截流配置更新
export const createAutoTaskClosureConfig = (data: any) => {
    return request.post({ url: "/auto.touch/update", data });
};

// 获取自动任务截流配置
export const getAutoTaskClosureConfigDetail = (data: any) => {
    return request.get({ url: "/auto.touch/detail", data });
};

// 自动任务加微配置更新
export const createAutoTaskAddWechatConfig = (data: any) => {
    return request.post({ url: "/auto.addWechat/update", data });
};

// 自动任务加微配置详情
export const getAutoTaskAddWechatConfigDetail = (data: any) => {
    return request.get({ url: "/auto.addWechat/detail", data });
};

// 自动任务发布配置新增
export const createAutoTaskPublishConfig = (data: any) => {
    return request.post({ url: "/auto.autoDeviceSetting/add", data });
};

// 自动任务发布配置详情
export const getAutoTaskPublishConfigDetail = (data: any) => {
    return request.get({ url: "/auto.autoDeviceSetting/detail", data });
};

// 自动任务私聊接管配置
export const createAutoTaskPrivateTakeConfig = (data: any) => {
    return request.post({ url: "/auto.takeOver/update", data });
};

// 获取自动任务私聊接管配置
export const getAutoTaskPrivateTakeConfigDetail = (data: any) => {
    return request.get({ url: "/auto.takeOver/detail", data });
};

// 获取截流行业历史记录
export const getClosureIndustryHistory = (data: any) => {
    return request.get({ url: "/sv.leadScraping/getIndustryLog", data });
};

// 删除截流行业历史记录
export const deleteClosureIndustryHistory = (data: any) => {
    return request.post({ url: "/sv.leadScraping/deleteIndustryLog", data });
};

// 朋友圈新增
export const createCircleTask = (data: any) => {
    return request.post({ url: "/wechat.circle/addTask", data }, { ignoreCancel: true });
};

// 朋友圈点赞评论任务新增
export const createCircleLikeTask = (data: any) => {
    return request.post({ url: "/device.likeReply/add", data });
};

// 个微接管任务新增
export const createWechatPrivateTask = (data: any) => {
    return request.post({ url: "/device.wechat/add", data });
};

// 截流获客任务新增
export const createInteractionTask = (data: any) => {
    return request.post({ url: "/sv.leadScraping/add", data });
};

// 朋友圈发布时间校验
export const checkCirclePublishTime = (data: any) => {
    return request.post({ url: "/wechat.circle/check", data });
};

// 设备任务数据看板
export const getDeviceTaskDashboard = (data: any) => {
    return request.get({ url: "/device.display/lists", data });
};

// 检查是不是有真实任务
export const checkRealTask = (data: any) => {
    return request.post({ url: "/auto.device/checkOpt", data });
};

// 创建演示任务
export const createDemoTask = (data: any) => {
    return request.post({ url: "/auto.device/opt", data });
};

// 24h任务营销对话聊天
export const marketingChat = (data: any, config: RequestEventStreamConfig) => {
    return request.eventStream({ url: "/auto.needsAnalysis/chat", data, method: "POST" }, config);
};

// 24h任务营销分析
export const marketingAnalysis = (data: any) => {
    return request.post({ url: "/auto.needsAnalysis/analysis", data });
};

// 24h任务营销数据添加
export const addMarketingAnalysisData = (data: any) => {
    return request.post({ url: "/auto.needsAnalysis/add", data });
};

// 24h任务营销数据更新
export const updateMarketingAnalysisData = (data: any) => {
    return request.post({ url: "/auto.needsAnalysis/update", data });
};

// 24h任务营销分析详情
export const marketingAnalysisDetail = (data: any) => {
    return request.get({ url: "/auto.needsAnalysis/detail", data });
};

// 24h任务营销分析报告
export const marketingAnalysisReport = (data: Record<string, any>) => {
    return request.post({ url: "/auto.needsAnalysis/report", data });
};

// 手动发布列表
export const getManualPublishList = (data: Record<string, any>) => {
    return request.get({ url: "/sv.mediaManualSetting/lists", data });
};

// 手动发布创建
export const createManualPublish = (data: Record<string, any>) => {
    return request.post({ url: "/sv.mediaManualSetting/add", data });
};

// 手动发布删除
export const deleteManualPublish = (data: Record<string, any>) => {
    return request.post({ url: "/sv.mediaManualSetting/delete", data });
};

// 手动发布编辑
export const editManualPublish = (data: Record<string, any>) => {
    return request.post({ url: "/sv.mediaManualSetting/update", data });
};

// 手动发布任务详情
export const getManualPublishTaskDetail = (data: Record<string, any>) => {
    return request.get({ url: "/sv.mediaManualSetting/detail", data });
};

// 手动发布任务列表
export const getManualPublishTaskList = (data: Record<string, any>) => {
    return request.get({ url: "/sv.mediaManualTask/lists", data });
};

// 手动发布任务删除
export const deleteManualPublishTask = (data: Record<string, any>) => {
    return request.post({ url: "/sv.mediaManualTask/delete", data });
};

// 手动发布任务发布
export const publishManualPublishTask = (data: Record<string, any>) => {
    return request.post({ url: "/sv.mediaManualTask/publish", data });
};

// 任务关键词列表
export const getTaskKeywordList = (data: Record<string, any>) => {
    return request.get({ url: "/device.display/cluesDetail", data });
};

// 任务线索词列表
export const getTaskClueList = (data: Record<string, any>) => {
    return request.get({ url: "/device.display/keywordDetail", data });
};

// 任务截流、留痕列表
export const getTaskClosureList = (data: Record<string, any>) => {
    return request.get({ url: "/device.display/touchDetail", data });
};

// 任务朋友圈点赞、评论
export const getTaskCircleLikeList = (data: Record<string, any>) => {
    return request.get({ url: "/device.display/wechatCircleThumbCommentDetail", data });
};

// 任务加微
export const getTaskAddWechatList = (data: Record<string, any>) => {
    return request.get({ url: "/device.display/cluesWechatDetail", data });
};

// 截流行业历史记录
export const getTaskClosureIndustryHistory = () => {
    return request.get({ url: "/sv.leadScraping/getFilterHistory" });
};

// 24h朋友圈发布配置更新
export const updateAutoCirclePublishTaskConfig = (data: Record<string, any>) => {
    return request.post({ url: "/auto.wechatCircleConfig/update", data });
};

// 24h朋友圈发布配置详情
export const getAutoCirclePublishTaskConfigDetail = (data: Record<string, any>) => {
    return request.get({ url: "/auto.wechatCircleConfig/detail", data });
};

// 24h朋友圈互动任务配置
export const updateAutoCircleInteractionTaskConfig = (data: Record<string, any>) => {
    return request.post({ url: "/auto.likeReply/update", data });
};

// 24h朋友圈互动任务配置详情
export const getAutoCircleInteractionTaskConfigDetail = (data: Record<string, any>) => {
    return request.get({ url: "/auto.likeReply/detail", data });
};
