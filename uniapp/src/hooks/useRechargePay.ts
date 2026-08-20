import {
    createRechargeOrder,
    mnpVirtualConfirm,
    mnpVirtualPrepay,
    prePay
} from '@/api/recharge'
import { PayStatusEnum } from '@/enums/appEnums'
import { GIFT_PACKAGE_PAY_FROM, MnpPayType, MnpVirtualPlatform } from '@/enums/payEnums'
import { isIOS } from '@/utils/client'
import { pay, PayWayEnum } from '@/utils/pay'
import { requestMnpVirtualPayment } from '@/utils/pay/mnp_virtual'
import { useUserStore } from '@/stores/user'

interface RechargePackage {
    id: number | string
    product_id?: string
    [key: string]: any
}

const getWxLoginCode = (): Promise<string> => {
    return new Promise((resolve, reject) => {
        uni.login({
            provider: 'weixin',
            success: (res) => {
                if (res.code) {
                    resolve(res.code)
                    return
                }
                reject('获取微信登录态失败，请重试')
            },
            fail: (err) => reject(err?.errMsg || '获取微信登录态失败，请重试')
        })
    })
}

const getVirtualPlatform = () => {
    return isIOS() ? MnpVirtualPlatform.IOS : MnpVirtualPlatform.ANDROID
}

/** 微信 code 无效/已使用（40029 / 40163） */
const isInvalidWxCodeError = (error: unknown) => {
    const text = String(error ?? '')
    return (
        text.includes('40029') ||
        text.includes('40163') ||
        text.includes('invalid code') ||
        text.includes('登录凭证已失效')
    )
}

/**
 * 算力包充值支付：支持普通微信 / 小程序虚拟支付
 */
export function useRechargePay() {
    const userStore = useUserStore()
    const isPaying = ref(false)

    const refreshUserInfo = () => userStore.getUser().catch(() => undefined)

    /** 普通微信支付：建单 → prePay → requestPayment */
    const runWechatPayment = async (
        pkg: RechargePackage,
        payWay: number | string
    ): Promise<PayStatusEnum> => {
        if (isPaying.value) return Promise.reject('支付进行中')
        isPaying.value = true
        uni.showLoading({ title: '创建订单中' })
        try {
            const order = await createRechargeOrder({
                type: 1,
                package_id: pkg.id
            })
            const orderId = order?.order_id ?? order?.id
            if (!orderId) {
                throw '创建订单失败'
            }

            uni.showLoading({ title: '正在支付中' })
            const prepay = await prePay({
                order_id: orderId,
                from: GIFT_PACKAGE_PAY_FROM,
                pay_way: payWay
            })
            const status = await pay.payment(
                (prepay.pay_way ?? payWay) as PayWayEnum,
                prepay.config
            )
            if (status === PayStatusEnum.SUCCESS) {
                await refreshUserInfo()
            }
            return status
        } finally {
            uni.hideLoading()
            isPaying.value = false
        }
    }

    /** 预下单：每次使用全新 uni.login code；code 失效时自动换新重试一次 */
    const requestVirtualPrepay = async (pkg: RechargePackage) => {
        const buildPayload = async () => ({
            package_id: pkg.id,
            code: await getWxLoginCode(),
            platform: getVirtualPlatform(),
            product_id: pkg.product_id || undefined
        })

        try {
            return await mnpVirtualPrepay(await buildPayload())
        } catch (error) {
            if (!isInvalidWxCodeError(error)) {
                throw error
            }
            // code 一次性且易失效，换新 code 再请求一次
            return mnpVirtualPrepay(await buildPayload())
        }
    }

    /** 小程序虚拟支付：login → mnpVirtualPrepay → requestVirtualPayment → confirm */
    const runVirtualPayment = async (pkg: RechargePackage): Promise<PayStatusEnum> => {
        // #ifndef MP-WEIXIN
        return Promise.reject('虚拟支付仅支持微信小程序')
        // #endif

        // #ifdef MP-WEIXIN
        if (isPaying.value) return Promise.reject('支付进行中')
        isPaying.value = true
        uni.showLoading({ title: '正在支付中' })
        try {
            const prepay = await requestVirtualPrepay(pkg)
            const orderId = prepay?.order_id
            if (!orderId) {
                throw '创建订单失败'
            }
            if (!prepay?.config?.signData) {
                throw '虚拟支付参数异常'
            }

            const status = await requestMnpVirtualPayment(prepay.config)
            if (status === PayStatusEnum.SUCCESS) {
                // 到账依赖服务端 query_order；微信侧偶发延迟，失败时短重试
                for (let i = 0; i < 3; i++) {
                    try {
                        const confirmRes = await mnpVirtualConfirm({ order_id: orderId })
                        if (Number(confirmRes?.pay_status) === 1) {
                            break
                        }
                    } catch {
                        // 继续重试
                    }
                    if (i < 2) {
                        await new Promise((r) => setTimeout(r, 1200))
                    }
                }
                await refreshUserInfo()
            }
            return status
        } finally {
            uni.hideLoading()
            isPaying.value = false
        }
        // #endif
    }

    const runPayment = async (options: {
        pkg: RechargePackage
        mnpPayType: number
        payWay: number | string
    }): Promise<PayStatusEnum> => {
        const { pkg, mnpPayType, payWay } = options
        // #ifdef MP-WEIXIN
        if (Number(mnpPayType) === MnpPayType.VIRTUAL) {
            return runVirtualPayment(pkg)
        }
        // #endif
        return runWechatPayment(pkg, payWay)
    }

    return {
        isPaying,
        runPayment,
        runWechatPayment,
        runVirtualPayment
    }
}
