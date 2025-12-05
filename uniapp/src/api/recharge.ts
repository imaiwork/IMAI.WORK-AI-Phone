import request from "@/utils/request";

// 充值列表
export function getRechargeList(data: any) {
    return request.get({ url: "/GiftPackage/lists", data });
}

// 支付方式
export function getPaymentList(data: any) {
    return request.post({ url: "/pay/payWay", data });
}

// 创建支付订单
export function createRechargeOrder(data: any) {
    return request.post({ url: "/GiftPackage/recharge", data });
}
// 预支付
export function prePay(data: any) {
    return request.post({ url: "/pay/prePay", data });
}

// 查询支付结果
export function getPayResult(data: any) {
    return request.get({ url: "/pay/payStatus", data });
}

export function checkRedeemCode(data: { sn: number | string }) {
    return request.get({ url: "/cardCode/checkCard", data });
}

// 兑换卡密
export function useRedeemCode(data: any) {
    return request.post({ url: "/cardCode/useCard", data });
}
