import request from '@/utils/request'

// 通知设置列表
export function noticeLists(params: any) {
    return request.get({ url: '/notice.notice/settingLists', params })
}

// 通知设置详情
export function noticeDetail(params: any) {
    return request.get({ url: '/notice.notice/detail', params })
}

// 通知设置保存
export function setNoticeConfig(params: any) {
    return request.post({ url: '/notice.notice/set', params })
}

// 短信设置列表
export function smsLists() {
    return request.get({ url: '/notice.sms_config/getConfig' })
}

// 短信设置详情
export function smsDetail(params: any) {
    return request.get({ url: '/notice.sms_config/detail', params })
}

// 短信设置保存
export function setSmsConfig(params: any) {
    return request.post({ url: '/notice.sms_config/setConfig', params })
}

//获取邮箱设置
export function getEmailConfig(params: any) {
    return request.get({ url: '/notice.email/detail', params })
}

//设置邮箱设置
export function setEmailConfig(params: any) {
    return request.post({ url: '/notice.email/save', params })
}
