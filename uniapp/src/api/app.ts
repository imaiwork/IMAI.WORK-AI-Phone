import request, { RequestEventStreamConfig, RequestOptions } from '@/utils/request'

//发送短信
export function smsSend(data: any) {
    return request.post({ url: '/sms/sendCode', data: data })
}

export function getConfig(data: any) {
    return request.get({ url: '/index/config', data })
}

/** 检查当前请求域名/小程序 appid 是否 OEM 站点(与 PC /oem.oem/check 一致) */
export function checkOem() {
    const data: Record<string, string> = {}
    // #ifdef MP-WEIXIN
    try {
        const { miniProgram } = uni.getAccountInfoSync()
        if (miniProgram?.appId) data.appid = miniProgram.appId
    } catch {
        // ignore
    }
    // #endif
    return request.get({ url: '/oem.oem/check', data })
}

export function getPolicy(data: any) {
    return request.get({ url: '/index/policy', data: data })
}

export function getMnpQrCode(data: any) {
    return request.post({ url: '/share/getMnpQrCode', data: data })
}

export function uploadImage(file: any, data?: any, token?: string, onProgress?: (e: any) => void) {
    return request.uploadFile(
        {
            url: '/upload/image',
            filePath: file,
            name: 'file',
            header: {
                token
            },
            formData: data,
            fileType: 'image'
        },
        {
            onProgress,
            ignoreCancel: true
        }
    )
}

export function uploadFile(
    type: 'image' | 'file' | 'video' | 'audio' | 'llAudio',
    options: Omit<UniApp.UploadFileOption, 'url'>,
    onProgress?: (progress: number, options: RequestOptions) => void
) {
    return request.uploadFile(
        { ...options, url: `/upload/${type}`, name: 'file' },
        {
            ignoreCancel: true,
            onProgress
        }
    )
}

export function wxJsConfig(data: any) {
    return request.get({ url: '/wechat/jsConfig', data })
}

// 获取场景提示词
export function getScenePrompt() {
    return request.post({ url: '/tools/getPrompt' })
}

// 获取剪辑配置
export function getClipConfig() {
    return request.get({ url: '/tools/clip' })
}

// 获取视频创作记录
export function getVideoCreationRecord(data?: any) {
    return request.get({ url: '/video/creationRecord', data })
}

// 删除视频创作记录
export function deleteVideoCreationRecord(data: any) {
    return request.post({ url: '/video/creationRecordDelete', data })
}

// 更新视频创作记录
export function updateVideoCreationRecord(data: any) {
    return request.post({ url: '/video/creationRecordUpdate', data })
}

// 视频转码
export function videoTranscoding(url: string) {
    return request.post({ url: '/file/videoTranscoding', data: { uri: url } })
}

// 获取视频信息
export function getVideoInfoByUrl(data: any) {
    return request.post({ url: '/videoInfo/getInfo', data })
}

// 批量获取视频信息
export function batchGetVideoInfoByUrl(data: any) {
    return request.post({ url: '/videoInfo/batchGetInfo', data })
}

// 获取视频缩略图
export function getVideoThumbnail(data: any) {
    return request.post({ url: '/videoInfo/thumbnail', data })
}

// 获取小程序通知模板列表
export function getMnpNoticeTemplateList(data: any) {
    return request.get({ url: '/notice.notice/settingMnpLists', data })
}

// 首页统计
export function getHomeDisplay() {
    return request.get({ url: '/display/display' })
}

// 首页 AI 自动干活流水线
export function getAutoPipeline(data: { persona_id: string | number }) {
    return request.get({ url: '/display/autoPipeline', data })
}

// 使用教程列表
export function getTutorialList(data: any) {
    return request.get({ url: '/tutorial.tutorial/lists', data })
}

// 使用教程类目列表
export function getTutorialCategoryList(data: any) {
    return request.get({ url: '/tutorial.tutorialCategory/lists', data })
}

// 运营案例列表
export function getOperateCaseList(data: any) {
    return request.get({ url: '/catering.cateringFranchise/lists', data })
}

// 运营案例详情
export function getOperateCaseDetail(data: any) {
    return request.get({ url: '/catering.cateringFranchise/detail', data })
}

// 获取AI模型列表
export function getAiModelsLists() {
    return request.get({ url: '/aiModels/lists' })
}

// 获取效果统计
export function getEffectStatistics(data: any) {
    return request.get({ url: '/display/statistics', data })
}

// 客资线索顶部统计
export function getIntentionStatistics() {
    return request.get({ url: '/display/intentionStatistics' })
}

// 客资线索列表
export function getIntentionCustomerLists(data: any) {
    return request.get({ url: '/display/intentionCustomerLists', data }, { ignoreCancel: true })
}

// 客资线索私信聊天记录
export function getIntentionCustomerChatRecord(data: any) {
    return request.get({ url: '/display/privateMessageRecord', data })
}

// 客资线索跟进记录
export function getIntentionFollowRecord(data: any) {
    return request.get({ url: '/display/followRecord', data })
}

// 客资线索朋友圈互动详情
export function getIntentionCircleInteractionDetail(data: any) {
    return request.get({ url: '/display/circleInteractionDetail', data })
}
