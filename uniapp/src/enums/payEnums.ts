/** 小程序算力充值支付模式（接口 recharge.mnp_pay_type） */
export enum MnpPayType {
    /** 普通微信支付 wx.requestPayment */
    WECHAT = 1,
    /** 虚拟支付 wx.requestVirtualPayment */
    VIRTUAL = 2
}

/** 虚拟支付平台标识 */
export enum MnpVirtualPlatform {
    ANDROID = 'android',
    IOS = 'ios'
}

/** 算力礼包支付 from */
export const GIFT_PACKAGE_PAY_FROM = 'tokens'

/** 订单已支付 */
export const PAY_STATUS_PAID = 1
