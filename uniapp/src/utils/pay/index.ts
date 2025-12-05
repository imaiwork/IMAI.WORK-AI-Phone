import { Alipay } from './alipay'
import { Balance } from './balance'
import { Pay } from './pay'
import { Wechat } from './wechat'
import { EWechat } from './e_wechat'
import { EAlipay } from './e_alipay'

// 支付方式
enum PayWayEnum {
    BALANCE = 1,
    WECHAT = 2,
    ALIPAY = 3,
    E_WECHAT = 4,
    E_ALIPAY = 5
}
const wechat = new Wechat()
// 注入微信支付
Pay.inject(PayWayEnum[2], wechat)

// 注入余额支付
const balance = new Balance()
Pay.inject(PayWayEnum[1], balance)

// 注入支付宝支付
const alipay = new Alipay()
Pay.inject(PayWayEnum[3], alipay)

// 易支付支付宝
const e_alipay = new EAlipay()
Pay.inject(PayWayEnum[5], e_alipay)

// 易支付微信
const e_wechat = new EWechat()
Pay.inject(PayWayEnum[4], e_wechat)

const pay = new Pay()
export { pay, PayWayEnum }
