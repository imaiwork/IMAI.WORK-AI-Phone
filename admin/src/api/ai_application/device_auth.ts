import request from '@/utils/request'

// ───────── 激活码 ─────────

// 激活码列表
export function deviceAuthCodeLists(params: any) {
    return request.get(
        { url: '/deviceauth.deviceAuthCode/lists', params },
        { ignoreCancelToken: true }
    )
}

// 激活码 Tab 数量统计
export function deviceAuthCodeStatistics(params?: any) {
    return request.get({ url: '/deviceauth.deviceAuthCode/statistics', params })
}

// 从中台同步激活码
export function deviceAuthCodeSyncFromPlatform(params?: {
    updated_since?: number
    status?: number
}) {
    return request.post({ url: '/deviceauth.deviceAuthCode/syncFromPlatform', params })
}

// 转移 CDK 给用户
export function deviceAuthCodeTransfer(data: { id: number | string; user_id: number | string }) {
    return request.post({ url: '/deviceauth.deviceAuthCode/transfer', data })
}

// ───────── 激活码套餐 ─────────

// 套餐列表
export function deviceAuthPlanLists(params: any) {
    return request.get(
        { url: '/deviceauth.deviceAuthPlan/lists', params },
        { ignoreCancelToken: true }
    )
}

// 添加套餐
export function deviceAuthPlanAdd(params: any) {
    return request.post({ url: '/deviceauth.deviceAuthPlan/add', params })
}

// 编辑套餐
export function deviceAuthPlanEdit(params: any) {
    return request.post({ url: '/deviceauth.deviceAuthPlan/edit', params })
}

// 套餐详情
export function deviceAuthPlanDetail(params: { id: number }) {
    return request.get({ url: '/deviceauth.deviceAuthPlan/detail', params })
}

// 删除套餐
export function deviceAuthPlanDelete(params: { id: number }) {
    return request.post({ url: '/deviceauth.deviceAuthPlan/delete', params })
}

// 启用/禁用套餐
export function deviceAuthPlanChangeStatus(params: { id: number; status: number }) {
    return request.post({ url: '/deviceauth.deviceAuthPlan/changeStatus', params })
}

// ───────── 购买记录（订单） ─────────

// 订单列表
export function deviceAuthOrderLists(params: any) {
    return request.get(
        { url: '/deviceauth.deviceAuthOrder/lists', params },
        { ignoreCancelToken: true }
    )
}

// 订单详情
export function deviceAuthOrderDetail(params: { id: number }) {
    return request.get({ url: '/deviceauth.deviceAuthOrder/detail', params })
}
