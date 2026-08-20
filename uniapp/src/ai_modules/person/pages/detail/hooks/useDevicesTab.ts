import { getDeviceList as getDeviceListApi, removeDevicePersona, updateDevice } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { useCopy } from "@/hooks/useCopy";

const DEVICE_PAGE_SIZE = 10;

export enum TaskStatusEnum {
    OFFLINE = 0,
    IDLE = 1,
    WORKING = 2,
}

export interface DeviceItem {
    id: string;
    device_name: string;
    status: TaskStatusEnum;
    device_code: string;
    accounts?: DeviceAccount[];
    platform_accounts?: DeviceAccount[];
    platformAccounts?: DeviceAccount[];
}

export interface DeviceAccount {
    id?: string | number;
    platform?: string;
    platform_name?: string;
    name?: string;
    nickname?: string;
    account_name?: string;
    type?: string | number;
    account_type?: string | number;
    app_type?: string | number;
}

interface ChooseDeviceRef {
    setDisabledLists: (list: any[]) => void;
}

const STATUS_STYLE_MAP: Record<TaskStatusEnum, { dotColor: string; label: string }> = {
    [TaskStatusEnum.OFFLINE]: { dotColor: "#FAAD14", label: "离线待机" },
    [TaskStatusEnum.IDLE]: { dotColor: "#52C41A", label: "在线运行中" },
    [TaskStatusEnum.WORKING]: { dotColor: "#0065FB", label: "执行中" },
};

const ONLINE_STATUS = [TaskStatusEnum.IDLE, TaskStatusEnum.WORKING];

// 平台标签：按账号平台类型展示品牌色标签，与设备列表页保持一致
const PLATFORM_STYLE_MAP: Record<string | number, { label: string; className: string }> = {
    [AppTypeEnum.DOUYIN]: { label: "抖音", className: "bg-[#000000] text-white" },
    [AppTypeEnum.XHS]: { label: "小红书", className: "bg-[#EF4444] text-white" },
    [AppTypeEnum.WECHAT]: { label: "微信", className: "bg-[#22C55E] text-white" },
    [AppTypeEnum.KUAISHOU]: { label: "快手", className: "bg-[#F97316] text-white" },
    sph: { label: "视频号", className: "bg-[#F3F4F6] text-[#6B7280]" },
};

const DEFAULT_PLATFORM_STYLE = { label: "平台账号", className: "bg-[#F3F4F6] text-[#6B7280]" };

export const useDevicesTab = (
    personId: Ref<string>,
    showChooseDevice: Ref<boolean>,
    chooseDeviceRef: Ref<ChooseDeviceRef | undefined>,
) => {
    const deviceList = ref<DeviceItem[]>([]);
    const deviceTotal = ref(0);
    const deviceLoading = ref(false);
    const deviceFinished = ref(false);
    const deviceParams = reactive({ page_no: 1, page_size: DEVICE_PAGE_SIZE });

    const getDeviceStatusStyle = (status: TaskStatusEnum) =>
        STATUS_STYLE_MAP[status] ?? STATUS_STYLE_MAP[TaskStatusEnum.IDLE];

    const getDeviceIcon = (status: TaskStatusEnum) =>
        ONLINE_STATUS.includes(status)
            ? "/static/images/icons/device_primary.svg"
            : "/static/images/icons/device_gray.svg";

    const getAccountType = (account: DeviceAccount): string | number =>
        account.account_type ?? account.type ?? account.app_type ?? AppTypeEnum.WECHAT;

    const getPlatformStyle = (account: DeviceAccount) =>
        PLATFORM_STYLE_MAP[getAccountType(account)] ?? DEFAULT_PLATFORM_STYLE;

    const getDeviceAccounts = (device: DeviceItem) =>
        device.accounts ?? device.platform_accounts ?? device.platformAccounts ?? [];

    const queryDeviceList = async () => {
        if (deviceLoading.value || deviceFinished.value) return;
        try {
            deviceLoading.value = true;
            const res = await getDeviceListApi({
                persona_id: personId.value,
                page_no: deviceParams.page_no,
                page_size: deviceParams.page_size,
            });
            const lists: DeviceItem[] = res?.lists ?? [];
            deviceList.value = deviceParams.page_no === 1 ? lists : deviceList.value.concat(lists);
            deviceTotal.value = res?.count ?? deviceList.value.length;
            if (!lists.length || deviceList.value.length >= deviceTotal.value) {
                deviceFinished.value = true;
            }
        } catch {
            deviceFinished.value = true;
        } finally {
            deviceLoading.value = false;
        }
    };

    // 切换到关联设备 tab / 绑定解绑后重新拉取第一页
    const getDeviceList = async () => {
        deviceParams.page_no = 1;
        deviceFinished.value = false;
        await queryDeviceList();
    };

    const loadNextDevicePage = (): void => {
        if (deviceLoading.value || deviceFinished.value) return;
        deviceParams.page_no += 1;
        queryDeviceList();
    };

    const handleSelectDevice = (): void => {
        showChooseDevice.value = true;
        chooseDeviceRef.value?.setDisabledLists(deviceList.value);
    };

    const handleChooseDeviceConfirm = async (device: any): Promise<void> => {
        uni.showLoading({ title: "绑定中...", mask: true });
        try {
            await updateDevice({ device_code: device.device_code, persona_id: personId.value });
            uni.showToast({ title: "设备已绑定", icon: "none", duration: 3000 });
            getDeviceList();
        } catch (error: any) {
            uni.showToast({ title: error || "绑定失败", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleDeviceSetting = (device: DeviceItem): void => {
        uni.navigateTo({
            url: `/ai_modules/device/pages/setting/setting?device_code=${device.device_code}`,
        });
    };

    const handlePhoneManagement = (): void => {
        uni.navigateTo({
            url: "/ai_modules/device/pages/index/index",
        });
    };

    const handleCopyDeviceCode = (device: DeviceItem): void => {
        if (!device.device_code) return;
        const { copy } = useCopy();
        copy(device.device_code);
    };

    const handleUnbindDevice = (device: DeviceItem): void => {
        uni.showModal({
            title: "解除绑定",
            content: `确定要解除绑定「${device.device_name}」吗？`,
            confirmColor: "#FF4D4F",
            success: async ({ confirm }) => {
                if (!confirm) return;
                try {
                    await removeDevicePersona({ device_code: device.device_code });
                    deviceList.value = deviceList.value.filter((item) => item.id !== device.id);
                    deviceTotal.value = Math.max(0, deviceTotal.value - 1);
                    uni.showToast({ title: "已解绑", icon: "success" });
                } catch (error: any) {
                    uni.showToast({ title: error || "解绑失败", icon: "none", duration: 3000 });
                }
            },
        });
    };

    return {
        deviceList,
        deviceTotal,
        deviceLoading,
        deviceFinished,
        getDeviceList,
        loadNextDevicePage,
        getDeviceAccounts,
        getDeviceIcon,
        getDeviceStatusStyle,
        getPlatformStyle,
        handleChooseDeviceConfirm,
        handleCopyDeviceCode,
        handleDeviceSetting,
        handlePhoneManagement,
        handleSelectDevice,
        handleUnbindDevice,
    };
};
