/**
 *  添加设备和账号
 *  功能：
 *  1. 添加设备
 *  2. 添加多个平台账号
 *  3. 刷新账号
 */
import { addAccount as addAccountApi, updateAccount as updateAccountApi } from "@/api/service";
import { addDevice as addDeviceApi } from "@/api/device";
import { AppTypeEnum, DeviceCmdEnum, DeviceCmdCodeEnum } from "@/enums/appEnums";

export enum EventAction {
    AddDevice = "addDevice",
    // 添加账号
    AddAccount = "addAccount",
    // 更新账号
    UpdateAccount = "updateAccount",
}

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
    const { send, onEvent } = options;

    const showAddDevice = ref(false);
    const addDeviceLoading = ref(false);
    const addDeviceParams = ref<any>(null);

    const progressValue = ref(0);
    const progressInterval = ref<NodeJS.Timeout | null>(null);

    // 刷新账号数据
    const refreshAccount = ref<RefreshAccount[]>([]);

    // 事件动作
    const eventAction = ref<any>(null);

    // 发送获取用户信息指令
    const sendGetUserInfo = (deviceId: string, appType: AppTypeEnum) => {
        send({
            type: DeviceCmdEnum.GET_USER_INFO,
            content: { deviceId },
            deviceId,
            appType,
        });
    };

    // 事件监听
    onEvent("success", async (data: any) => {
        const { type, content, deviceId, appType } = data;
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
                // if (socialPlatformList.length > 0) {
                //     sendGetUserInfo(content.deviceId, socialPlatformList[0].type);
                // }
                // eventAction.value = EventAction.AddAccount;
                break;
            case DeviceCmdEnum.GET_USER_INFO:
                try {
                    const { account, account_no, extra, avatar, nickname } = content;
                    const params = {
                        account,
                        account_no,
                        avatar,
                        device_code: deviceId,
                        type: appType,
                        nickname,
                        extra: JSON.stringify(extra),
                    };

                    if (eventAction.value == EventAction.AddAccount) {
                        // 添加设备
                        if (addDeviceParams.value) {
                            await addDeviceApi(addDeviceParams.value);
                        }
                        try {
                            await addAccountApi(params);
                            showAddDevice.value = false;
                            eventAction.value = null;
                            progressValue.value = 100;
                            feedback.msgSuccess("添加账号成功");
                            options.onSuccess?.({
                                msg: "添加账号成功",
                                type,
                                data,
                            });
                        } catch (error) {
                            options.onError?.({
                                error,
                                type,
                                code: DeviceCmdCodeEnum.API_ERROR,
                            });
                        }
                    }
                    if (eventAction.value == EventAction.UpdateAccount) {
                        const currentAccount = refreshAccount.value
                            .filter((item: any) => item.type == params.type)
                            .find((item: any) => item.account == params.account);
                        try {
                            if (currentAccount) {
                                await updateAccountApi({
                                    id: currentAccount.id,
                                    ...params,
                                });
                            } else {
                                await addAccountApi(params);
                            }
                            eventAction.value = null;
                            progressValue.value = 100;
                            feedback.msgSuccess("更新成功");
                            options.onSuccess?.({
                                msg: "更新成功",
                                type,
                                data,
                            });
                        } catch (error) {
                            options.onError?.({
                                error,
                                type,
                                code: DeviceCmdCodeEnum.API_ERROR,
                            });
                        }
                    }
                } catch (error) {
                    options.onError?.({
                        error,
                        type,
                        code: DeviceCmdCodeEnum.API_ERROR,
                    });
                }
                refreshAccount.value = null;
                addDeviceLoading.value = false;
                clearInterval(progressInterval.value);
                break;
            default:
                options.onSuccess?.({ msg, type, data });
                break;
        }
    });

    onEvent("error", (error: any) => {
        addDeviceLoading.value = false;
        options.onError?.(error);
        if (progressInterval.value) {
            clearInterval(progressInterval.value);
        }
    });

    // 确认添加设备
    const handleAddDeviceConfirm = () => {};

    // 刷新账号
    const handleRefreshAccount = (deviceId: string, type: AppTypeEnum) => {
        eventAction.value = EventAction.UpdateAccount;
        completeProgress();
        sendGetUserInfo(deviceId, type);
    };

    // 添加账号
    const handleAddAccount = (params: any) => {
        eventAction.value = EventAction.AddAccount;
        completeProgress();
        sendGetUserInfo(params.device_code, params.type);
    };

    const completeProgress = () => {
        // 清除之前的定时器
        if (progressInterval.value) {
            clearInterval(progressInterval.value);
            progressInterval.value = null;
        }

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
                if (progressInterval.value) {
                    clearInterval(progressInterval.value);
                    progressInterval.value = null;
                }
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
        completeProgress,
    };
};
