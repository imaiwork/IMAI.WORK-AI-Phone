import { PayStatusEnum } from '@/enums/appEnums'
import { handleClientEvent } from '../client'
//#ifdef H5
import wechatOa from '../wechat'
//#endif
export class Wechat {
    init(name: string, pay: any) {
        pay[name] = this
    }

    async run(options: any) {
        try {
            const res = await handleClientEvent({
                MP_WEIXIN: () => {
                    return new Promise((resolve) => {
                        uni.requestPayment({
                            provider: 'wxpay',
                            ...options,
                            success() {
                                resolve(PayStatusEnum.SUCCESS)
                            },
                            fail() {
                                resolve(PayStatusEnum.FAIL)
                            }
                        })
                    })
                },
                OA_WEIXIN: () => {
                    return new Promise((resolve) => {
                        wechatOa
                            .pay(options)
                            .then(() => {
                                resolve(PayStatusEnum.SUCCESS)
                            })
                            .catch(() => {
                                resolve(PayStatusEnum.FAIL)
                            })
                    })
                },
                H5: () => {
                    return new Promise((resolve) => {
                        window.open(options, '_self')
                        resolve(PayStatusEnum.PENDING)
                    })
                },
                ANDROID: () => {
                    return new Promise((resolve, reject) => {
                        uni.requestPayment({
                            provider: 'wxpay',
                            orderInfo: options,
                            success() {
                                resolve(PayStatusEnum.SUCCESS)
                            },
                            fail(error) {
                                console.log(error)
                                resolve(PayStatusEnum.FAIL)
                            }
                        })
                    })
                },
                IOS: () => {
                    return new Promise((resolve, reject) => {
                        options.partnerid = String(options.partnerid)
                        uni.requestPayment({
                            provider: 'wxpay',
                            orderInfo: options,
                            success() {
                                resolve(PayStatusEnum.SUCCESS)
                            },
                            fail(err) {
                                console.log(err)
                                resolve(PayStatusEnum.FAIL)
                            }
                        })
                    })
                }
            })
            return res
        } catch (error) {
            return Promise.reject(error)
        }
    }
}
