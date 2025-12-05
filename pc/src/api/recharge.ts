// 充值列表
export function getRechargeList(params: any) {
    return $request.get({ url: "/GiftPackage/lists", params });
}

// 支付方式
export function getPaymentList(params: any) {
    return $request.post({ url: "/pay/payWay", params });
}

// 创建支付订单
export function createRechargeOrder(params: any) {
    return $request.post({ url: "/GiftPackage/recharge", params });
}
// 预支付
export function prePay(params: any) {
    return $request.post({ url: "/pay/prePay", params });
}

// 查询支付结果
export function getPayResult(params: any) {
    return $request.get({ url: "/pay/payStatus", params });
}

export function checkRedeemCode(params: { sn: number | string }) {
    return $request.get({ url: "/cardCode/checkCard", params });
}

// 兑换卡密
export function useRedeemCode(params: any) {
    return $request.post({ url: "/cardCode/useCard", params });
}
