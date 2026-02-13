import { defineStore } from "pinia";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
import { AppTypeEnum, DeviceCmdEnum } from "@/enums/appEnums";
import { addDeviceAccount, updateDeviceAccount } from "@/api/device";
import wechatWechatIcon from "@/static/images/common/wechat.png";
import wechatWechatActiveIcon from "@/static/images/common/wechat_s.png";
import redbookIcon from "@/static/images/common/redbook.png";
import redbookActiveIcon from "@/static/images/common/redbook_s.png";
import douyinIcon from "@/static/images/common/douyin.png";
import douyinActiveIcon from "@/static/images/common/douyin_s.png";
import kuaishouIcon from "@/static/images/common/kuaishou.png";
import kuaishouActiveIcon from "@/static/images/common/kuaishou_s.png";
import sphIcon from "@/static/images/common/sph.png";
import sphActiveIcon from "@/static/images/common/sph_s.png";

interface PlatformInfo {
    icon: string;
    activeIcon: string;
    type: AppTypeEnum;
    name: string;
    active?: boolean;
    status?: number; // 0: 待处理, 1: 进行中, 2: 成功, 3: 失败
    account?: string;
    account_no?: string;
    avatar?: string;
    nickname?: string;
    extra?: any;
    error?: string;
}

interface DeviceOptions {
    detail?: any; // 外部传入的设备详情数据
    isAutoUpdateNextAccount?: boolean;
    onAccountsUpdated?: () => Promise<void>; // 账号更新后的回调
}

interface DeviceState {
    deviceList: any[];
    platform: Record<AppTypeEnum, PlatformInfo>;
    // 获取账号相关状态
    currentDeviceCode: string;
    platformsToUpdate: AppTypeEnum[];
    isGettingAccounts: boolean;

    // 配置选项
    options: DeviceOptions;

    //ws
    ws: any;
    onEvent: any;
    send: any;
    close: any;
    isConnected: boolean;

    // 等待队列 - 用于在连接建立前暂存操作
    pendingOperations: Array<() => void>;
}

export const useDeviceStore = defineStore("device", {
    state: (): DeviceState => ({
        deviceList: [],
        platform: {
            [AppTypeEnum.WECHAT]: {
                icon: wechatWechatIcon,
                activeIcon: wechatWechatActiveIcon,
                type: AppTypeEnum.WECHAT,
                name: "微信",
                active: false,
                status: 0,
            },
            [AppTypeEnum.XHS]: {
                icon: redbookIcon,
                activeIcon: redbookActiveIcon,
                type: AppTypeEnum.XHS,
                name: "小红书",
                active: false,
                status: 0,
            },
            [AppTypeEnum.DOUYIN]: {
                icon: douyinIcon,
                activeIcon: douyinActiveIcon,
                type: AppTypeEnum.DOUYIN,
                name: "抖音",
                active: false,
                status: 0,
            },
            [AppTypeEnum.KUAISHOU]: {
                icon: kuaishouIcon,
                activeIcon: kuaishouActiveIcon,
                type: AppTypeEnum.KUAISHOU,
                name: "快手",
                active: false,
                status: 0,
            },
        },
        currentDeviceCode: "",
        platformsToUpdate: [],
        isGettingAccounts: false,
        // 配置选项
        options: {
            isAutoUpdateNextAccount: true,
        },

        ws: null,
        onEvent: null,
        send: null,
        close: null,
        isConnected: false,
        // 等待队列
        pendingOperations: [],
    }),

    getters: {
        getDeviceList: (state: DeviceState) => state.deviceList,

        getSortedPlatform: (state: DeviceState) => {
            return Object.values(state.platform).map((item: PlatformInfo) => ({
                ...item,
                icon: item.active ? item.activeIcon : item.icon,
            }));
        },

        getPendingPlatforms: (state: DeviceState) => {
            return state.platformsToUpdate.filter(
                (type) => state.platform[type].status === 0 || state.platform[type].status === 1
            );
        },

        getNextPendingPlatform: (state: DeviceState) => {
            const pendingType = state.platformsToUpdate.find((type) => state.platform[type].status === 0);
            return pendingType ? state.platform[pendingType] : null;
        },
    },

    actions: {
        // ==================== 基础设置方法 ====================
        setDeviceList(deviceList: any[]) {
            this.deviceList = deviceList;
        },

        // 设置配置选项
        setOptions(options: DeviceOptions) {
            this.options = { ...this.options, ...options };
        },

        // 检查是否为有效的平台类型
        isValidPlatformType(type: any): type is AppTypeEnum {
            return Object.values(AppTypeEnum).includes(type);
        },

        // ==================== WebSocket 管理方法 ====================

        // 手动开启 WebSocket 连接
        async connectWebSocket() {
            if (this.ws && this.isConnected) {
                return;
            }

            // // 如果有实例但连接已断开，清理旧实例
            // if (this.ws && !this.isConnected) {
            //     this.cleanupWebSocket();
            // }

            try {
                const { send, close, onEvent, socket, isConnected } = useDeviceWs();

                this.ws = socket;
                this.onEvent = onEvent;
                this.send = send;
                this.close = close;
                this.isConnected = isConnected.value;

                // 监听连接状态变化
                this.watchConnectionState(isConnected.value);

                this.setupWebSocketListeners();
            } catch (error) {
                this.cleanupWebSocket();
                throw error;
            }
        },

        // 添加清理 WebSocket 的方法
        cleanupWebSocket() {
            if (this.close) {
                this.close();
            }
            this.ws = null;
            this.onEvent = null;
            this.send = null;
            this.close = null;
            this.isConnected = false;
        },

        watchConnectionState(isConnectedRef: boolean) {
            if (typeof watch !== "undefined") {
                watch(
                    () => isConnectedRef,
                    (newValue: boolean) => {
                        this.isConnected = newValue;
                        if (!newValue) {
                            console.log("WebSocket 连接已断开");
                        }
                    }
                );
            } else {
                // 如果没有 watch，使用定时器检查
                const checkConnection = () => {
                    if (this.ws && isConnectedRef !== this.isConnected) {
                        this.isConnected = isConnectedRef;
                        if (!this.isConnected) {
                            console.log("WebSocket 连接已断开");
                        }
                    }
                };

                // 每秒检查一次连接状态
                setInterval(checkConnection, 1000);
            }
        },

        // 设置 WebSocket 事件监听
        setupWebSocketListeners() {
            if (!this.onEvent) return;

            this.onEvent("error", (error: any) => {
                if (this.isGettingAccounts) {
                    this.handleWebSocketError(error);
                }
            });

            this.onEvent("success", async (data: any) => {
                await this.handleWebSocketSuccess(data);
            });
        },
        // 关闭 WebSocket 连接
        closeWebSocket() {
            this.close();
        },

        // 添加操作到等待队列
        addToPendingOperations(operation: () => void) {
            this.pendingOperations.push(operation);
        },

        // 执行等待队列中的操作
        executePendingOperations() {
            while (this.pendingOperations.length > 0) {
                const operation = this.pendingOperations.shift();
                if (operation) {
                    try {
                        operation();
                    } catch (error) {
                        console.error("执行等待操作失败:", error);
                    }
                }
            }
        },

        // ==================== 平台账号管理方法 ====================

        // 根据设备详情初始化平台信息
        initializePlatformFromDetail(accounts: any) {
            if (!accounts) {
                this.resetPlatformStatus();
                return;
            }

            const accountMap = new Map(accounts.map((acc: any) => [acc.type, acc]));

            Object.values(this.platform).forEach((platformItem) => {
                const account = accountMap.get(platformItem.type) as any;
                if (account) {
                    Object.assign(platformItem, {
                        active: true,
                        status: 2,
                        account: account.account,
                        account_no: account.account_no,
                        avatar: account.avatar,
                        nickname: account.nickname,
                        extra: account.extra ? JSON.parse(account.extra) : null,
                        error: undefined,
                    });
                } else {
                    Object.assign(platformItem, {
                        active: false,
                        status: 0,
                        account: undefined,
                        account_no: undefined,
                        avatar: undefined,
                        nickname: undefined,
                        extra: undefined,
                        error: undefined,
                    });
                }
            });
        },

        // 初始化平台账号信息（保留原有方法，兼容性考虑）
        initPlatformAccounts(detail: any) {
            this.initializePlatformFromDetail(detail);
        },

        // ==================== 账号获取流程控制方法 ====================

        // 开始获取账号流程 - 确保 WebSocket 连接后再开始
        async startGetAccounts(deviceCode: string, forceRefetch: boolean = false, options: DeviceOptions = {}) {
            // 合并配置选项
            this.setOptions(options);

            // 如果传入了 detail，先初始化平台账号信息
            if (this.options.detail) {
                this.initializePlatformFromDetail(this.options.detail);
            }

            this.currentDeviceCode = deviceCode;
            this.isGettingAccounts = true;

            let accountsToFetchTypes: AppTypeEnum[];
            if (forceRefetch) {
                accountsToFetchTypes = [AppTypeEnum.WECHAT, AppTypeEnum.XHS, AppTypeEnum.DOUYIN, AppTypeEnum.KUAISHOU];
            } else {
                accountsToFetchTypes = Object.values(this.platform)
                    .filter((item) => !item.active)
                    .map((item) => item.type);
            }

            this.platformsToUpdate = accountsToFetchTypes;

            // 重置状态
            Object.values(this.platform).forEach((p) => {
                if (this.platformsToUpdate.includes(p.type)) {
                    p.status = 0; // 待处理
                    p.error = undefined;
                    if (forceRefetch) {
                        p.active = false;
                    }
                }
            });

            // 如果启用自动更新，开始处理下一个账号
            if (this.options.isAutoUpdateNextAccount) {
                this.processNextAccount();
            }
        },

        // 处理下一个账号
        processNextAccount() {
            const nextPlatform = this.getNextPendingPlatform;

            if (nextPlatform) {
                this.setPlatformProcessing(nextPlatform.type);
                this.sendGetAccountCmd(nextPlatform.type);
            } else {
                this.finishGetAccounts();

                // 执行回调
                if (this.options.onAccountsUpdated) {
                    this.options.onAccountsUpdated();
                }
            }
        },

        // 发送获取账号命令
        async sendGetAccountCmd(type: AppTypeEnum) {
            const message = {
                type: DeviceCmdEnum.GET_USER_INFO,
                content: { deviceId: this.currentDeviceCode },
                deviceId: this.currentDeviceCode,
                appType: type,
            };
            this.send(message);
        },

        // 设置平台状态为进行中
        setPlatformProcessing(type: AppTypeEnum) {
            if (this.platform[type]) {
                this.platform[type].status = 1;
            }
        },

        // 设置平台账号信息（成功）
        setPlatformAccountSuccess(type: AppTypeEnum, accountInfo: any) {
            if (this.platform[type]) {
                this.platform[type] = {
                    ...this.platform[type],
                    status: 2,
                    active: true,
                    account: accountInfo.account,
                    account_no: accountInfo.account_no,
                    avatar: accountInfo.avatar,
                    nickname: accountInfo.nickname,
                    extra: accountInfo.extra,
                    error: undefined,
                };
            }
        },

        // 设置平台错误状态
        setPlatformError(type: AppTypeEnum, error: string) {
            if (this.platform[type]) {
                this.platform[type].status = 3;
                this.platform[type].error = error;
            }
        },

        // 完成获取账号流程
        finishGetAccounts() {
            this.isGettingAccounts = false;
            this.platformsToUpdate = [];
            this.currentDeviceCode = "";
        },

        // 停止获取账号
        stopGetAccount() {
            this.finishGetAccounts();
        },

        // 重置所有平台状态
        resetPlatformStatus() {
            Object.values(this.platform).forEach((p) => {
                p.active = false;
                p.status = 0;
                p.account = undefined;
                p.account_no = undefined;
                p.avatar = undefined;
                p.nickname = undefined;
                p.extra = undefined;
                p.error = undefined;
            });
        },

        // ==================== WebSocket 事件处理方法 ====================

        // 处理 WebSocket 成功事件
        async handleWebSocketSuccess(data: any) {
            const { type, content, deviceId, appType } = data;
            if (type !== DeviceCmdEnum.GET_USER_INFO) return;

            const platformInfo = this.platform[appType as AppTypeEnum];
            if (platformInfo && platformInfo.status === 1) {
                const { account, account_no, extra, avatar, nickname } = content;

                // 检查是否已存在账号
                const existingAccount = this.options.detail?.accounts?.find((acc: any) => acc.type === appType);

                const params = {
                    account,
                    account_no,
                    avatar,
                    device_code: deviceId,
                    type: appType,
                    nickname,
                    extra: JSON.stringify(extra),
                };

                try {
                    // 调用 API 保存或更新账号
                    if (existingAccount) {
                        await updateDeviceAccount({ ...params, id: existingAccount.id });
                    } else {
                        await addDeviceAccount(params);
                    }

                    // 更新 store 中的平台信息
                    this.setPlatformAccountSuccess(appType, {
                        account,
                        account_no,
                        avatar,
                        nickname,
                        extra,
                    });
                } catch (error) {
                    this.setPlatformError(appType, "保存账号信息失败");
                }
            }

            // 检查是否还有待处理的平台
            if (this.getPendingPlatforms.length > 0 && this.options.isAutoUpdateNextAccount) {
                this.processNextAccount();
            } else {
                this.finishGetAccounts();
                this.closeWebSocket();
                // 执行回调
                if (this.options.onAccountsUpdated) {
                    this.options.onAccountsUpdated();
                }
            }
        },

        // 处理 WebSocket 错误事件
        handleWebSocketError(error: any) {
            // 找到当前进行中的平台
            const platformInProgress = Object.values(this.platform).find((p) => p.status === 1);
            if (platformInProgress) {
                this.setPlatformError(platformInProgress.type, error.error || "获取账号失败");

                if (this.options.isAutoUpdateNextAccount) {
                    // 继续处理下一个平台
                    setTimeout(() => {
                        this.processNextAccount();
                    }, 1000);
                }
            }
        },

        // ==================== 兼容性方法 ====================

        // 提供与原 useDevice hook 相同的接口
        useDevice(options: DeviceOptions = {}) {
            this.setOptions(options);

            return {
                // 数据
                platform: this.platform,
                sortedPlatform: this.getSortedPlatform,
                isGettingAccounts: this.isGettingAccounts,
                currentDeviceCode: this.currentDeviceCode,

                // 方法
                connectWebSocket: this.connectWebSocket,
                handleGetAccount: (deviceCode: string, forceRefetch: boolean = false) => {
                    return this.startGetAccounts(deviceCode, forceRefetch, options);
                },
                stopGetAccount: this.stopGetAccount,
                resetPlatforms: this.resetPlatformStatus,
                initializePlatform: this.initializePlatformFromDetail,

                // WebSocket 相关
                close: this.closeWebSocket,
                isConnected: this.isConnected,
            };
        },
    },
});
