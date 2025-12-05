import { PayStatusEnum } from '@/enums/appEnums'
import { handleClientEvent } from '../client'

export class EWechat {
    init(name: string, pay: any) {
        pay[name] = this
    }

    openNewPage(options: any) {
        const alipayPage = window.open('', '_self')!
        alipayPage.document.body.innerHTML = options
        alipayPage.document.forms[0].submit()
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
                        this.openNewPage(options)
                        resolve(PayStatusEnum.PENDING)
                    })
                },
                H5: () => {
                    return new Promise((resolve) => {
                        this.openNewPage(options)
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
