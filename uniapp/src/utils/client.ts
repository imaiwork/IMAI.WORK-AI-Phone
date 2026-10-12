import { ClientEnum } from '@/enums/appEnums'

/**
 * @description 判断是否为微信环境
 * @return { Boolean }
 */
export const isWeixinClient = () => {
    // #ifdef H5
    return /MicroMessenger/i.test(navigator.userAgent)
    // #endif
}

/**
 * @description 判断是否为安卓环境
 * 华为 Pura 80 等纯血鸿蒙不含 Android：H5 UA 为 OpenHarmony / ArkWeb，微信小程序 platform 为 ohos
 * @return { Boolean }
 */
export function isAndroid() {
    // #ifdef H5
    const u = navigator.userAgent
    return /android|adr|openharmony|harmonyos|arkweb/i.test(u)
    // #endif
    // #ifdef MP-WEIXIN
    const systemInfo = uni.getSystemInfoSync()
    const fingerprint = [systemInfo.osName, systemInfo.platform, systemInfo.system].filter(Boolean).join(' ')
    return /android|harmony|openharmony|ohos/i.test(fingerprint)
    // #endif
}

/**
 * @description 判断是否为IOS环境
 * @return { Boolean }
 */
export function isIOS() {
    // #ifdef H5
    const u = navigator.userAgent
    return u.indexOf('iPhone') > -1 || u.indexOf('iPad') > -1
    // #endif

    // #ifdef MP-WEIXIN
    return uni.getSystemInfoSync().osName == 'ios'
    // #endif
}

/**
 * @description 获取当前是什么端
 * @return { Object }
 */

export const getClient = () => {
    //@ts-ignore
    return handleClientEvent({
        // 微信小程序
        MP_WEIXIN: () => ClientEnum['MP_WEIXIN'],
        // 微信公众号
        OA_WEIXIN: () => ClientEnum['OA_WEIXIN'],
        // H5
        H5: () => ClientEnum['H5'],
        // APP
        IOS: () => ClientEnum['IOS'],
        ANDROID: () => ClientEnum['ANDROID'],
        // 其它
        OTHER: () => null
    })
}

/**
 * @description 获取当前是什么端
 * @return { String }
 */

export const getClientString = () => {
    //@ts-ignore
    return handleClientEvent({
        // 微信小程序
        MP_WEIXIN: () => '',
        // 微信公众号
        OA_WEIXIN: () => 'wechat',
        // H5
        H5: () => 'jump',
        // APP
        IOS: () => '',
        ANDROID: () => '',
        // 其它
        OTHER: () => null
    })
}

// 根据端处理事件
//@ts-ignore
export const handleClientEvent = ({ MP_WEIXIN, OA_WEIXIN, H5, IOS, ANDROID, OTHER }: any) => {
    // #ifdef MP-WEIXIN
    return MP_WEIXIN()
    // #endif

    // #ifdef H5
    return isWeixinClient() ? OA_WEIXIN() : H5()
    // #endif

    // #ifdef APP-PLUS
    const system = uni.getSystemInfoSync()
    if (system.platform == 'ios') {
        return IOS()
    } else {
        return ANDROID()
    }
    // #endif
    return OTHER()
}

export const client = getClient()
