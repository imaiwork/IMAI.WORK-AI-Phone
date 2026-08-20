import { PayStatusEnum } from "@/enums/appEnums";

export interface MnpVirtualPayConfig {
    mode?: string;
    signData: string;
    paySig: string;
    signature: string;
}

/** 官方 errCode → 用户可读文案 */
const VIRTUAL_PAY_ERRCODE_MAP: Record<number, string> = {
    1001: "支付参数有误，请稍后重试",
    [-1]: "支付失败，请稍后重试",
    [-2]: "已取消支付",
    [-4]: "当前操作存在风险，暂无法支付",
    [-5]: "签约结果未知，请稍后在订单中确认",
    [-15001]: "支付参数有误，请稍后重试",
    [-15002]: "订单已失效，请重新下单",
    [-15003]: "支付系统繁忙，请稍后重试",
    [-15004]: "币种不支持，请联系客服",
    [-15005]: "登录状态已失效，请重新登录后再试",
    [-15006]: "支付校验失败，请稍后重试",
    [-15007]: "登录状态已过期，请重新登录后再试",
    [-15008]: "商户支付能力未开通完成，请联系客服",
    [-15009]: "代币商品未发布，暂无法购买",
    [-15010]: "商品未发布，暂无法购买",
    [-15011]: "支付环境配置有误，请联系客服",
    [-15012]: "订单已关闭，请重新下单",
    [-15013]: "商品价格与微信后台不一致，请联系客服处理",
    [-15014]: "商品刚发布尚未生效，请约10分钟后再试",
    [-15016]: "支付参数格式有误，请稍后重试",
    [-15017]: "商户收款功能受限，暂无法支付",
    [-15018]: "商品审核未通过，暂无法购买",
    [-15019]: "商户收款功能受限，暂无法支付",
    [-15020]: "操作过快，请稍后再试",
    [-15021]: "当前交易繁忙，请稍后再试",
};

/** 官方 errMsg 关键字 → 用户可读文案（兼容无 errCode 场景） */
const VIRTUAL_PAY_ERRMSG_MAP: Array<{ match: RegExp; message: string }> = [
    { match: /GOODS_PRICE_INVALID|goodsPrice|道具价格/i, message: "商品价格与微信后台不一致，请联系客服处理" },
    { match: /SIGNATURE_INVALID|signature/i, message: "登录状态已失效，请重新登录后再试" },
    { match: /PAY_SIG|paySig/i, message: "支付校验失败，请稍后重试" },
    { match: /PRODUCT_ID|productId|未发布/i, message: "商品未发布或未生效，暂无法购买" },
    { match: /OUT_TRADE_NO|outTradeNo|重复/i, message: "订单已失效，请重新下单" },
    { match: /SESSION_KEY|session_key|过期/i, message: "登录状态已过期，请重新登录后再试" },
    { match: /ENV|沙箱|沙盒/i, message: "支付环境配置有误，请联系客服" },
    { match: /signData|参数错误|parameter/i, message: "支付参数有误，请稍后重试" },
    { match: /限频|操作过快|too frequent/i, message: "操作过快，请稍后再试" },
    { match: /违规|受限|限制/i, message: "商户收款功能受限，暂无法支付" },
    { match: /cancel|取消/i, message: "已取消支付" },
];

/**
 * 将微信虚拟支付官方错误映射为用户可读提示
 */
export function mapMnpVirtualPayError(err: any): string {
    const errCode = Number(err?.errCode);
    if (!Number.isNaN(errCode) && VIRTUAL_PAY_ERRCODE_MAP[errCode]) {
        return VIRTUAL_PAY_ERRCODE_MAP[errCode];
    }

    const errMsg = String(err?.errMsg || err || "");
    for (const item of VIRTUAL_PAY_ERRMSG_MAP) {
        if (item.match.test(errMsg)) {
            return item.message;
        }
    }

    // 去掉 requestVirtualPayment:fail 前缀后的裸错误也不直接暴露
    const cleaned = errMsg
        .replace(/^requestVirtualPayment:fail\s*/i, "")
        .replace(/^fail\s*/i, "")
        .trim();
    if (!cleaned || /^[A-Z0-9_\-:\s]+$/i.test(cleaned)) {
        return "支付失败，请稍后重试";
    }
    return "支付失败，请稍后重试";
}

/**
 * 微信小程序虚拟支付拉起
 * 仅 MP-WEIXIN 可用；其他端直接失败
 */
export function requestMnpVirtualPayment(config: MnpVirtualPayConfig): Promise<PayStatusEnum> {
    // #ifndef MP-WEIXIN
    return Promise.reject("当前端不支持虚拟支付");
    // #endif

    // #ifdef MP-WEIXIN
    return new Promise((resolve, reject) => {
        // 微信小程序全局 wx；部分基础库也可挂到 uni
        const wxApi = typeof wx !== "undefined" ? wx : (uni as any);
        if (typeof wxApi.requestVirtualPayment !== "function") {
            reject("当前微信版本过低，不支持虚拟支付");
            return;
        }
        wxApi.requestVirtualPayment({
            mode: config.mode || "short_series_goods",
            signData: config.signData,
            paySig: config.paySig,
            signature: config.signature,
            success() {
                resolve(PayStatusEnum.SUCCESS);
            },
            fail(err: any) {
                const errMsg = String(err?.errMsg || "");
                if (errMsg.includes("cancel") || errMsg.includes("取消") || Number(err?.errCode) === -2) {
                    resolve(PayStatusEnum.FAIL);
                    return;
                }
                reject(mapMnpVirtualPayError(err));
            },
        });
    });
    // #endif
}
