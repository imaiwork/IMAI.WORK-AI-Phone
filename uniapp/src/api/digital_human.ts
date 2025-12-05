import request from "@/utils/request";

// 创建形象
export const createAnchor = (data: Record<string, any>) => {
    return request.post({ url: "/human/createAnchor", data });
};

// 形象列表
export const getAnchorList = (data: Record<string, any>) => {
    return request.get({ url: "/human/anchorLists", data });
};

// 删除形象
export const deleteAnchor = (data: Record<string, any>) => {
    return request.post({ url: "/human/anchorDelete", data });
};

// 获取数字人列表
export const digitalHumanLists = (data: Record<string, any>) => {
    return request.get({ url: "/human/videoLists", data });
};

// 删除数字人
export const deleteDigitalHuman = (data: Record<string, any>) => {
    return request.post({ url: "/human/videoDelete", data });
};

// 更新数字人
export const updateDigitalHuman = (data: Record<string, any>) => {
    return request.post({ url: "/human/videoUpdate", data });
};

// 语音克隆
export const voiceClone = (data: Record<string, any>) => {
    return request.post({ url: "/human/createVoice", data });
};

// 音色列表
export const getVoiceList = (data?: Record<string, any>) => {
    return request.get({ url: "/human/voiceLists", data });
};

// 删除音色
export const deleteVoice = (data: Record<string, any>) => {
    return request.post({ url: "/human/voiceDelete", data });
};

// 重新生成音色
export const retryVoice = (data: Record<string, any>) => {
    return request.post({ url: "/human/voiceRetry", data });
};

// 创建音频
export const createAudio = (data: Record<string, any>) => {
    return request.post({ url: "/human/createAudio", data });
};

// 音频列表
export const getAudioList = (data?: Record<string, any>) => {
    return request.get({ url: "/human/audioLists", data });
};

// 删除音频
export const deleteAudio = (data: Record<string, any>) => {
    return request.post({ url: "/human/audioDelete", data });
};

// 创建视频
export const createVideo = (data: Record<string, any>) => {
    return request.post({ url: "/human/createVideo", data });
};

// 重试视频
export const retryVideo = (data: Record<string, any>) => {
    return request.post({ url: "/human/videoRetry", data });
};

// 创建数字人任务
export const createTask = (data: Record<string, any>) => {
    return request.post({ url: "/human/videoTask", data });
};

// 抖音生成文案
export const createDouyinContent = (data: Record<string, any>) => {
    return request.post({ url: "/human/dyToText", data });
};

// 文案生成
export const generatePrompt = (data: Record<string, any>) => {
    return request.post({ url: "/human/copywriting", data });
};

// 闪剪形象创建
export const createShanjianAnchor = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.shanjianAnchor/add", data });
};

// 闪剪形象列表
export const getShanjianAnchorList = (data: Record<string, any>) => {
    return request.get({ url: "/shanjian.shanjianAnchor/lists", data });
};

// 闪剪形象删除
export const deleteShanjianAnchor = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.shanjianAnchor/delete", data });
};

// 闪剪形象详情
export const getShanjianAnchorDetail = (data: Record<string, any>) => {
    return request.get({ url: "/shanjian.shanjianAnchor/detail", data });
};

// 闪剪口播文案生成
export const generateShanjianPrompt = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.shanjianVideoTask/copywriting", data }, { ignoreCancel: true });
};

// 人设列表
export const getShanjianPersonList = (data: Record<string, any>) => {
    return request.get({ url: "/shanjian.shanjianCharacterDesign/lists", data });
};

// 人设新增
export const addShanjianPerson = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.shanjianCharacterDesign/add", data });
};

// 人设删除
export const deleteShanjianPerson = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.shanjianCharacterDesign/delete", data });
};

// 发布创建
export const createShanjianPublish = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.publish/add", data });
};

// 发布记录
export const getPublishRecord = (data: Record<string, any>) => {
    return request.get({ url: "/shanjian.publish/lists", data });
};

// 发布记录详情
export const getPublishRecordDetail = (data: Record<string, any>) => {
    return request.get({ url: "/shanjian.publish/detail", data });
};

// 发布记录删除
export const deletePublishRecord = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.publish/delete", data });
};

// 发布记录视频列表
export const getPublishRecordVideoList = (data: Record<string, any>) => {
    return request.get({ url: "/shanjian.publish/recordLists", data });
};

// 闪剪形象授权
export const shanjianAnchorAuthorizedList = (data: Record<string, any>) => {
    return request.get({ url: "/shanjian.shanjianAnchor/authorizedList", data });
};

// 闪剪任务创建
export const createShanjianTask = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.shanjianVideoSetting/add", data });
};

// 闪剪任务记录
export const getShanjianTaskRecord = (data: Record<string, any>) => {
    return request.get({ url: "/shanjian.shanjianVideoSetting/lists", data });
};

// 闪剪任务记录删除
export const deleteShanjianTaskRecord = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.shanjianVideoSetting/delete", data });
};

// 闪剪任务名称修改
export const updateShanjianTaskName = (data: Record<string, any>) => {
    return request.post({ url: "/shanjian.shanjianVideoSetting/updateName", data });
};
