import request from '@/utils/request'

// 手机列表（按 tab 服务端筛选）
export const getDeviceAuthPhoneList = (data?: Record<string, any>) => {
    return request.get({ url: '/device_auth/phoneList', data })
}

// 我的激活码
export const getDeviceAuthMyCodes = (data?: Record<string, any>) => {
    return request.get({ url: '/device_auth/myCodes', data })
}

// 可购买套餐列表
export const getDeviceAuthPlanList = (data?: Record<string, any>) => {
    return request.get({ url: '/device_auth/planList', data })
}

// 购买激活码
export const purchaseDeviceAuthCode = (data: {
    plan_id: number | string
    quantity: number
    pay_type: number
}) => {
    return request.post({ url: '/device_auth/purchaseCode', data })
}

// 设备续费
export const renewDeviceAuth = (data: {
    plan_id: number | string
    pay_type: number
    device_id: number | string
    device_code: string
}) => {
    return request.post({ url: '/device_auth/renewDevice', data })
}

// 激活授权码
export const activateDeviceAuthCode = (data: { code: string; device_code: string }) => {
    return request.post({ url: '/device_auth/activate', data })
}

// 添加手机（绑定设备）
export const addDeviceAuthPhone = (data: {
    device_code: string
    device_name?: string
    device_model?: string
    sdk_version?: string
}) => {
    return request.post({ url: '/device_auth/addPhone', data })
}

// 预支付（设备授权码）
export const prepayDeviceAuth = (data: {
    from: string
    order_id: number | string
    pay_way: number
    redirect?: string
}) => {
    return request.post({ url: '/pay/prepay', data })
}

// 支付状态（设备授权码）
export const getDeviceAuthPayStatus = (data: { from: string; order_id: number | string }) => {
    return request.get({ url: '/pay/payStatus', data })
}

/** 小程序虚拟支付预下单（CDK） */
export const mnpVirtualPrepayDeviceAuth = (data: {
    from?: string
    biz_type?: number
    plan_id: number | string
    quantity?: number
    device_id?: number | string
    device_code?: string
    product_id?: string
    code: string
    platform?: string
}) => {
    return request.post({ url: '/pay/mnpVirtualPrepay', data })
}

/** 小程序虚拟支付确认（CDK） */
export const mnpVirtualConfirmDeviceAuth = (data: {
    order_id: number | string
    from?: string
}) => {
    return request.post({ url: '/pay/mnpVirtualConfirm', data })
}
