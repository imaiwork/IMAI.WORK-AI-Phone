/**
 *  添加设备和账号
 *  功能：
 *  1. 添加设备
 *  2. 添加多个平台账号
 *  3. 刷新账号
 */
import { addDevice as addDeviceApi, fetchDeviceAccount } from "@/api/device";
import { AppTypeEnum, DeviceCmdEnum, DeviceCmdCodeEnum } from "@/enums/appEnums";

export enum EventAction {
    AddDevice = "addDevice",
    // 添加账号
    AddAccount = "addAccount",
    // 更新账号
    UpdateAccount = "updateAccount",
    // 批量更新账号
    BatchUpdateAccount = "batchUpdateAccount",
}

/** 等服务端落库后再通知页面刷新（appCompleted 可能早于落库） */
const ACCOUNT_FETCH_REFRESH_DELAY = 1500;

interface SuccessMsg {
    msg: string;
    type: DeviceCmdEnum;
    data: any;
}

interface UseAddDeviceAccountOptions {
    send: (data: any) => void;
    onEvent: (event: string, callback: (data: any) => void) => void;
    onSuccess?: (msg: SuccessMsg) => void;
    onError?: (err: any) => void;
}

interface RefreshAccount {
    id: number;
    account: string;
    type: AppTypeEnum;
    device_code?: string;
}

export const useAddDeviceAccount = (options: UseAddDeviceAccountOptions) => {
    const { onEvent } = options;

    const showAddDevice = ref(false);
    const addDeviceLoading = ref(false);
    const addDeviceParams = ref<any>(null);

    const progressValue = ref(0);
    const progressInterval = ref<NodeJS.Timeout | null>(null);
    const finishTimer = ref<NodeJS.Timeout | null>(null);

    // 刷新账号数据
    const refreshAccount = ref<RefreshAccount[]>([]);

    // 事件动作
    const eventAction = ref<any>(null);

    const clearProgressTimer = () => {
        if (progressInterval.value) {
            clearInterval(progressInterval.value);
            progressInterval.value = null;
        }
    };

    const clearFinishTimer = () => {
        if (finishTimer.value) {
            clearTimeout(finishTimer.value);
            finishTimer.value = null;
        }
    };

    const isAccountFetchAction = (action: any) =>
        action === EventAction.AddAccount ||
        action === EventAction.UpdateAccount ||
        action === EventAction.BatchUpdateAccount;

    /** HTTP 触发 RPA 拉号，进度仍走 WS */
    const fetchAccountInfo = async (deviceId: string, appType: AppTypeEnum) => {
        try {
            await fetchDeviceAccount({
                device_code: deviceId,
                type: appType,
            });
        } catch (error) {
            clearProgressTimer();
            clearFinishTimer();
            addDeviceLoading.value = false;
            options.onError?.({
                error,
                type: DeviceCmdEnum.GET_USER_INFO,
                code: DeviceCmdCodeEnum.API_ERROR,
            });
        }
    };

    const finishAccountFetch = (data: any) => {
        clearFinishTimer();
        finishTimer.value = setTimeout(() => {
            const action = eventAction.value;
            clearProgressTimer();
            progressValue.value = 100;
            addDeviceLoading.value = false;

            if (action === EventAction.AddAccount) {
                showAddDevice.value = false;
                feedback.msgSuccess("添加账号成功");
                options.onSuccess?.({ msg: "添加账号成功", type: DeviceCmdEnum.GET_USER_INFO, data });
            } else if (action === EventAction.UpdateAccount) {
                feedback.msgSuccess("更新成功");
                options.onSuccess?.({ msg: "更新成功", type: DeviceCmdEnum.GET_USER_INFO, data });
            } else if (action === EventAction.BatchUpdateAccount) {
                options.onSuccess?.({ msg: "批量更新成功", type: DeviceCmdEnum.GET_USER_INFO, data });
            }
            finishTimer.value = null;
        }, ACCOUNT_FETCH_REFRESH_DELAY);
    };

    // 事件监听
    onEvent("success", async (data: any) => {
        const { type, content } = data;
        const msg = content.msg || "";
        switch (type) {
            case DeviceCmdEnum.ADD_DEVICE:
                addDeviceParams.value = {
                    status: 1,
                    device_code: content.deviceId,
                    device_model: content.deviceModel,
                    sdk_version: content.sdkVersion,
                };
                try {
                    feedback.loading("添加中...");
                    await addDeviceApi(addDeviceParams.value);
                    feedback.msgSuccess("添加设备成功");
                    options.onSuccess?.({ msg: "添加成功", type, data });
                } catch (error) {
                    options.onError?.({
                        error,
                        type,
                        code: DeviceCmdCodeEnum.API_ERROR,
                    });
                } finally {
                    addDeviceLoading.value = false;
                    showAddDevice.value = false;
                    feedback.closeLoading();
                }
                break;
            case DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE:
                // 进度文案先更新，再延迟收尾（避免落库未完成就刷新）
                options.onSuccess?.({ msg, type, data });
                if (isAccountFetchAction(eventAction.value)) {
                    finishAccountFetch(data);
                }
                break;
            case DeviceCmdEnum.GET_USER_INFO:
                // 账号已由服务端落库，前端不再 add/update
                break;
            default:
                options.onSuccess?.({ msg, type, data });
                break;
        }
    });

    onEvent("error", (error: any) => {
        addDeviceLoading.value = false;
        clearFinishTimer();
        options.onError?.(error);
        clearProgressTimer();
    });

    // 确认添加设备
    const handleAddDeviceConfirm = () => {};

    // 刷新账号
    const handleRefreshAccount = (deviceId: string, type: AppTypeEnum) => {
        eventAction.value = EventAction.UpdateAccount;
        completeProgress();
        fetchAccountInfo(deviceId, type);
    };

    // 批量更新账号
    const handleBatchUpdateAccount = (params: any) => {
        eventAction.value = EventAction.BatchUpdateAccount;
        fetchAccountInfo(params.device_code, params.type);
    };

    // 添加账号
    const handleAddAccount = (params: any) => {
        eventAction.value = EventAction.AddAccount;
        completeProgress();
        fetchAccountInfo(params.device_code, params.type);
    };

    const completeProgress = () => {
        clearProgressTimer();

        const startTime = Date.now();
        const duration = 15 * 1000;
        const updateInterval = 300;
        const maxIncrementPerInterval = 2; // 限制每次最大增量

        // 重置进度值
        progressValue.value = 0;

        progressInterval.value = setInterval(() => {
            const elapsedTime = Date.now() - startTime;
            const randomIncrement = Math.min(maxIncrementPerInterval, Math.random() * (99 - progressValue.value) * 0.1);
            progressValue.value = Math.floor(Math.min(99, progressValue.value + randomIncrement));

            if (progressValue.value >= 99 || elapsedTime >= duration) {
                clearProgressTimer();
                progressValue.value = 99;
            }
        }, updateInterval);
    };

    return {
        showAddDevice,
        addDeviceLoading,
        progressValue,
        eventAction,
        refreshAccount,

        handleAddDeviceConfirm,
        handleAddAccount,
        handleRefreshAccount,
        handleBatchUpdateAccount,
        completeProgress,
    };
};
