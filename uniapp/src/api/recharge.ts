import request from '@/utils/request'

// 充值列表
export function getRechargeList(data: any) {
    return request.get({ url: '/GiftPackage/lists', data })
}

// 支付方式
export function getPaymentList(data: any) {
    return request.post({ url: '/pay/payWay', data })
}

// 创建支付订单
export function createRechargeOrder(data: any) {
    return request.post({ url: '/GiftPackage/recharge', data })
}
// 预支付
export function prePay(data: any) {
    return request.post({ url: '/pay/prePay', data })
}

// 查询支付结果
export function getPayResult(data: any) {
    return request.get({ url: '/pay/payStatus', data })
}

/** 小程序虚拟支付预下单 */
export function mnpVirtualPrepay(data: {
    package_id: number | string
    code: string
    platform?: string
    buy_quantity?: number
    product_id?: string
}) {
    return request.post({ url: '/pay/mnpVirtualPrepay', data })
}

/** 小程序虚拟支付结果查询 */
export function mnpVirtualConfirm(data: { order_id: number | string }) {
    return request.post({ url: '/pay/mnpVirtualConfirm', data })
}

export function checkRedeemCode(data: { sn: number | string; scene?: string }) {
    return request.get({ url: '/cardCode/checkCard', data })
}

// 兑换卡密；scene=tokens 时后端仅允许算力卡
export function useRedeemCode(data: { sn: number | string; scene?: string } | any) {
    return request.post({ url: '/cardCode/useCard', data })
}
