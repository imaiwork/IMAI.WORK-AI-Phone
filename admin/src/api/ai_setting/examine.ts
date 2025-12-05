import request from '@/utils/request'

// 获取内容审核配置
export function getConfig() {
    return request.get({ url: '/setting.contentCensor/detail' })
}

// 设置内容审核配置
export function setConfig(params: any) {
    return request.post({ url: '/setting.contentCensor/save', params })
}
