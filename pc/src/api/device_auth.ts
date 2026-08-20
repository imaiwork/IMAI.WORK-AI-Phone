// 手机列表（按 tab 服务端筛选）
export function getDeviceAuthPhoneList(params?: any) {
    return $request.get({ url: '/device_auth/phoneList', params })
}

// 我的激活码
export function getDeviceAuthMyCodes(params?: any) {
    return $request.get({ url: '/device_auth/myCodes', params })
}

// 可购买套餐列表
export function getDeviceAuthPlanList(params?: any) {
    return $request.get({ url: '/device_auth/planList', params })
}

// 购买激活码
export function purchaseDeviceAuthCode(params: {
    plan_id: number | string
    quantity: number
    pay_type: number
}) {
    return $request.post({ url: '/device_auth/purchaseCode', params })
}

// 设备续费
export function renewDeviceAuth(params: {
    plan_id: number | string
    pay_type: number
    device_id: number | string
    device_code: string
}) {
    return $request.post({ url: '/device_auth/renewDevice', params })
}

// 激活授权码
export function activateDeviceAuthCode(params: {
    code: string
    device_code: string
}) {
    return $request.post({ url: '/device_auth/activate', params })
}

// 添加手机（绑定设备）
export function addDeviceAuthPhone(params: {
    device_code: string
    device_name?: string
    device_model?: string
    sdk_version?: string
}) {
    return $request.post({ url: '/device_auth/addPhone', params })
}

// 预支付（设备授权码）
export function prepayDeviceAuth(params: {
    from: string
    order_id: number | string
    pay_way: number
    redirect?: string
}) {
    return $request.post({ url: '/pay/prepay', params })
}

// 支付状态（设备授权码）
export function getDeviceAuthPayStatus(params: {
    from: string
    order_id: number | string
}) {
    return $request.get({ url: '/pay/payStatus', params })
}

// 支付方式列表
export function getDeviceAuthPayWay(params?: any) {
    return $request.post({ url: '/pay/payWay', params })
}
