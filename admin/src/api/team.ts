import request from '@/utils/request'

// 团队列表(分页)
export function teamLists(params: any) {
    return request.get({ url: '/team.team/lists', params })
}

// OEM剩余名额信息
export function teamGetInfo(params?: any) {
    return request.get({ url: '/team.team/getInfo', params })
}

// 创建团队
export function teamCreate(params: any) {
    return request.post({ url: '/team.team/create', params })
}

// 团队详情
export function teamDetail(params: any) {
    return request.get({ url: '/team.team/detail', params })
}

// 设置坐席上限
export function teamSetSeat(params: any) {
    return request.post({ url: '/team.team/setSeat', params })
}

// 启用/禁用团队
export function teamChangeStatus(params: any) {
    return request.post({ url: '/team.team/changeStatus', params })
}

// 审核 OEM 升级申请(approve: 1=通过 0=拒绝并退款)
export function teamOemReview(params: { id: number; approve: number }) {
    return request.post({ url: '/team.team/oemReview', params })
}

// 获取团队租户配置
export function teamTenant(params: any) {
    return request.get({ url: '/team.team/tenant', params })
}

// 保存团队租户配置
export function teamSetTenant(params: any) {
    return request.post({ url: '/team.team/setTenant', params })
}

// 团队成员列表
export function teamMembers(params: any) {
    return request.get({ url: '/team.team/members', params })
}

// OEM 收费配置：读取
export function teamOemPricing() {
    return request.get({ url: '/team.team/oemPricing' })
}

// OEM 收费配置：保存
export function teamSaveOemPricing(params: { oem_cost_price: number; oem_upgrade_price: number; oem_charge_cost: number }) {
    return request.post({ url: '/team.team/saveOemPricing', params })
}

// 强制取消团队 OEM(清状态+清域名,可选退预缴)
export function teamCancelOem(params: { id: number; refund?: number }) {
    return request.post({ url: '/team.team/cancelOem', params })
}

/** 站长后台直接开通 OEM(免费版→已开通,不扣算力) */
export function teamOpenOem(params: { id: number }) {
    return request.post({ url: '/team.team/openOem', params })
}

// 查看团队算力钱包
export function teamWallet(params: { id: number }) {
    return request.get({ url: '/team.team/wallet', params })
}

// 删除(解散)团队
export function teamDelete(params: { id: number }) {
    return request.post({ url: '/team.team/delete', params })
}
