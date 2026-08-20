import { prepayDeviceAuth, getDeviceAuthPayStatus, mnpVirtualPrepayDeviceAuth, mnpVirtualConfirmDeviceAuth } from "@/api/device_auth";
import { DEVICE_AUTH_PAY_FROM, DeviceAuthBizType } from "@/ai_modules/device/enums";
import { pay, PayWayEnum } from "@/utils/pay";
import { requestMnpVirtualPayment } from "@/utils/pay/mnp_virtual";
import { PayStatusEnum } from "@/enums/appEnums";
import { MnpPayType, MnpVirtualPlatform } from "@/enums/payEnums";
import { isIOS } from "@/utils/client";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";

interface CreateOrderResult {
    order_id?: number | string;
    id?: number | string;
    [key: string]: any;
}

export interface DeviceAuthPayResult {
    status: PayStatusEnum;
    orderId: number | string;
}

export interface DeviceAuthVirtualPayPayload {
    biz_type?: number;
    plan_id: number | string;
    quantity?: number;
    device_id?: number | string;
    device_code?: string;
    product_id?: string;
}

const getWxLoginCode = (): Promise<string> => {
    return new Promise((resolve, reject) => {
        uni.login({
            provider: "weixin",
            success: (res) => {
                if (res.code) {
                    resolve(res.code);
                    return;
                }
                reject("获取微信登录态失败，请重试");
            },
            fail: (err) => reject(err?.errMsg || "获取微信登录态失败，请重试"),
        });
    });
};

const isInvalidWxCodeError = (error: unknown) => {
    const text = String(error ?? "");
    return (
        text.includes("40029") ||
        text.includes("40163") ||
        text.includes("invalid code") ||
        text.includes("登录凭证已失效")
    );
};

/**
 * 设备激活码支付：
 * - 普通微信：create order -> prepay -> 拉起支付
 * - 小程序虚拟支付：mnpVirtualPrepay -> requestVirtualPayment -> confirm
 * - 算力支付：业务接口直接扣算力
 */
export function useDeviceAuthPay() {
    const isPaying = ref(false);
    const userStore = useUserStore();
    const appStore = useAppStore();

    const resolveOrderId = (order: CreateOrderResult) => order?.order_id ?? order?.id ?? "";

    const refreshUserInfo = () => userStore.getUser().catch(() => undefined);

    const isVirtualPayEnabled = () => {
        // #ifdef MP-WEIXIN
        return Number(appStore.getRechargeConfig?.mnp_pay_type ?? MnpPayType.WECHAT) === MnpPayType.VIRTUAL;
        // #endif
        // #ifndef MP-WEIXIN
        return false;
        // #endif
    };

    const runComputePayment = async (submit: () => Promise<CreateOrderResult>): Promise<DeviceAuthPayResult> => {
        if (isPaying.value) return Promise.reject("支付进行中");
        isPaying.value = true;
        uni.showLoading({ title: "支付中" });
        try {
            const res = await submit();
            await refreshUserInfo();
            return { status: PayStatusEnum.SUCCESS, orderId: resolveOrderId(res) };
        } finally {
            uni.hideLoading();
            isPaying.value = false;
        }
    };

    const requestVirtualPrepay = async (payload: DeviceAuthVirtualPayPayload) => {
        const buildPayload = async () => ({
            from: DEVICE_AUTH_PAY_FROM,
            biz_type: payload.biz_type ?? DeviceAuthBizType.PURCHASE,
            plan_id: payload.plan_id,
            quantity: payload.quantity ?? 1,
            device_id: payload.device_id,
            device_code: payload.device_code,
            product_id: payload.product_id || undefined,
            code: await getWxLoginCode(),
            platform: isIOS() ? MnpVirtualPlatform.IOS : MnpVirtualPlatform.ANDROID,
        });

        try {
            return await mnpVirtualPrepayDeviceAuth(await buildPayload());
        } catch (error) {
            if (!isInvalidWxCodeError(error)) {
                throw error;
            }
            return mnpVirtualPrepayDeviceAuth(await buildPayload());
        }
    };

    /** 小程序虚拟支付：预下单 → 拉起支付 → confirm */
    const runVirtualPayment = async (payload: DeviceAuthVirtualPayPayload): Promise<DeviceAuthPayResult> => {
        // #ifndef MP-WEIXIN
        return Promise.reject("虚拟支付仅支持微信小程序");
        // #endif

        // #ifdef MP-WEIXIN
        if (isPaying.value) return Promise.reject("支付进行中");
        isPaying.value = true;
        uni.showLoading({ title: "正在支付中" });
        try {
            const prepay = await requestVirtualPrepay(payload);
            const orderId = prepay?.order_id;
            if (!orderId) {
                throw "创建订单失败";
            }
            if (!prepay?.config?.signData) {
                throw "虚拟支付参数异常";
            }

            const status = await requestMnpVirtualPayment(prepay.config);
            if (status === PayStatusEnum.SUCCESS) {
                for (let i = 0; i < 3; i++) {
                    try {
                        const confirmRes = await mnpVirtualConfirmDeviceAuth({
                            order_id: orderId,
                            from: DEVICE_AUTH_PAY_FROM,
                        });
                        if (Number(confirmRes?.pay_status) === 1) {
                            break;
                        }
                    } catch {
                        // 继续重试
                    }
                    if (i < 2) {
                        await new Promise((r) => setTimeout(r, 1200));
                    }
                }
                await refreshUserInfo();
            }
            return { status, orderId };
        } finally {
            uni.hideLoading();
            isPaying.value = false;
        }
        // #endif
    };

    const runPayment = async (
        createOrder: () => Promise<CreateOrderResult>,
        payWay: PayWayEnum = PayWayEnum.WECHAT,
    ): Promise<DeviceAuthPayResult> => {
        if (isPaying.value) return Promise.reject("支付进行中");
        isPaying.value = true;
        uni.showLoading({ title: "创建订单中" });
        try {
            const order = await createOrder();
            const orderId = resolveOrderId(order);
            if (!orderId) {
                throw "创建订单失败";
            }

            uni.showLoading({ title: "正在支付中" });
            const prepay = await prepayDeviceAuth({
                from: DEVICE_AUTH_PAY_FROM,
                order_id: orderId,
                pay_way: payWay,
                redirect: "",
            });

            const status: PayStatusEnum = await pay.payment(prepay.pay_way ?? payWay, prepay.config);
            uni.hideLoading();

            if (status === PayStatusEnum.SUCCESS) {
                await getDeviceAuthPayStatus({
                    from: DEVICE_AUTH_PAY_FROM,
                    order_id: orderId,
                }).catch(() => undefined);
                await refreshUserInfo();
            }

            return { status, orderId };
        } finally {
            uni.hideLoading();
            isPaying.value = false;
        }
    };

    /**
     * 在线支付：小程序虚拟支付开启时走虚拟支付，否则普通微信
     */
    const runOnlinePayment = async (options: {
        createOrder: () => Promise<CreateOrderResult>;
        virtualPayload: DeviceAuthVirtualPayPayload;
        payWay?: PayWayEnum;
    }): Promise<DeviceAuthPayResult> => {
        if (isVirtualPayEnabled()) {
            return runVirtualPayment(options.virtualPayload);
        }
        return runPayment(options.createOrder, options.payWay ?? PayWayEnum.WECHAT);
    };

    return {
        isPaying,
        isVirtualPayEnabled,
        runPayment,
        runComputePayment,
        runVirtualPayment,
        runOnlinePayment,
    };
}
