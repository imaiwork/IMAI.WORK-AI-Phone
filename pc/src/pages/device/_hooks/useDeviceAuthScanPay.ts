import { prepayDeviceAuth, getDeviceAuthPayStatus, getDeviceAuthPayWay } from "@/api/device_auth";
import { DEVICE_AUTH_PAY_FROM } from "../_enums/deviceAuthEnums";
import { useUserStore } from "@/stores/user";

interface CreateOrderResult {
    order_id?: number | string;
    id?: number | string;
    [key: string]: any;
}

/**
 * 设备激活码微信扫码支付：
 * createOrder -> prepay -> 渲染二维码(config) -> 轮询 payStatus -> 支付成功回调
 * 与算力充值（data-package）保持一致的扫码 + 轮询逻辑。
 */
export function useDeviceAuthScanPay() {
    const payWayList = ref<any[]>([]);
    const payWay = ref<number>(-1);
    const perCode = ref(""); // 二维码内容
    const payLoading = ref(false);
    const payOrderId = ref<number | string>("");
    const userStore = useUserStore();

    // 支付成功后刷新用户信息（算力余额、套餐等），失败不阻断主流程
    const refreshUserInfo = () => userStore.getUser().catch(() => undefined);

    let onPaidCallback: (() => void) | null = null;

    const getPayWayList = async () => {
        try {
            const res = await getDeviceAuthPayWay({
                from: DEVICE_AUTH_PAY_FROM,
            });
            payWayList.value = res?.lists || [];
            payWay.value = payWayList.value.length ? payWayList.value[0].id : -1;
        } catch {
            payWayList.value = [];
        }
    };

    const check = async () => {
        if (!payOrderId.value) return;
        const { pay_status } = await getDeviceAuthPayStatus({
            from: DEVICE_AUTH_PAY_FROM,
            order_id: payOrderId.value,
        });
        if (pay_status == 1) {
            end();
            await refreshUserInfo();
            onPaidCallback?.();
        }
    };

    const onTimeout = async () => {
        end();
        feedback.msgError("支付超时，请重新发起");
    };

    const { start, end } = usePolling(check, {
        totalTime: 5 * 60 * 1000,
        callback: onTimeout,
    });

    // 创建订单 + 预支付，生成二维码并开始轮询
    const createAndPay = async (createOrder: () => Promise<CreateOrderResult>) => {
        if (payWay.value === -1) {
            feedback.msgError("暂无可用支付方式");
            return false;
        }
        payLoading.value = true;
        perCode.value = "";
        try {
            const order = await createOrder();
            const orderId = order?.order_id ?? order?.id ?? "";
            if (!orderId) throw "创建订单失败";
            payOrderId.value = orderId;
            const data = await prepayDeviceAuth({
                from: DEVICE_AUTH_PAY_FROM,
                order_id: orderId,
                pay_way: payWay.value,
                redirect: "",
            });
            perCode.value = data?.config || "";
            start();
            return true;
        } catch (error: any) {
            feedback.msgError(error || "支付失败");
            return false;
        } finally {
            payLoading.value = false;
        }
    };

    // 算力支付：直接调用业务接口（pay_type=算力）扣减算力，接口成功即视为支付成功，无需扫码
    const runComputePayment = async (submit: () => Promise<CreateOrderResult>): Promise<boolean> => {
        payLoading.value = true;
        try {
            await submit();
            await refreshUserInfo();
            return true;
        } catch (error: any) {
            feedback.msgError(error || "支付失败");
            return false;
        } finally {
            payLoading.value = false;
        }
    };

    const onPaid = (cb: () => void) => {
        onPaidCallback = cb;
    };

    const reset = () => {
        end();
        perCode.value = "";
        payOrderId.value = "";
        payLoading.value = false;
    };

    return {
        payWayList,
        payWay,
        perCode,
        payLoading,
        getPayWayList,
        createAndPay,
        runComputePayment,
        onPaid,
        reset,
    };
}
