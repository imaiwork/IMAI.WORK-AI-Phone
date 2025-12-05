import request from '@/utils/request'

// 获取公告配置
export function getNoticeSet(params?: any) {
    return request.get({ url: '/setting.bulletin/detail', params })
}

// 设置公告配置
export function setNoticeSet(params?: any) {
    return request.post({ url: '/setting.bulletin/save', params })
}
