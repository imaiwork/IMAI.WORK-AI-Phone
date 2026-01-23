import { useDeviceStore } from "@/ai_modules/device/stores/device";

interface UseDeviceOptions {
    detail?: Ref<any>; // 外部传入的设备详情数据
    isAutoUpdateNextAccount?: boolean;
    onAccountsUpdated?: () => Promise<void>; // 账号更新后的回调
}

export function useDevice(options: UseDeviceOptions = {}) {
    const deviceStore = useDeviceStore();

    // 设置配置选项
    deviceStore.setOptions(options);

    // 从 store 获取相关数据
    const {
        platform,
        currentDeviceCode,
        isGettingAccounts,
        getSortedPlatform,
        getNextPendingPlatform,
        getPendingPlatforms,
    } = storeToRefs(deviceStore);

    return {
        // 数据
        platform,
        sortedPlatform: getSortedPlatform,
        isGettingAccounts,
        currentDeviceCode,

        // WebSocket 状态

        // 方法
        connectWebSocket: () => deviceStore.connectWebSocket(),
        handleGetAccount: (deviceCode: string, forceRefetch: boolean = false) => {
            return deviceStore.startGetAccounts(deviceCode, forceRefetch, options);
        },
        stopGetAccount: () => deviceStore.stopGetAccount(),
        resetPlatforms: () => deviceStore.resetPlatformStatus(),
        initializePlatform: (accounts: any) => deviceStore.initializePlatformFromDetail(accounts),

        // WebSocket 相关
        close: () => deviceStore.closeWebSocket(),
    };
}
