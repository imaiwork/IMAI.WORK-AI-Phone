<template>
    <view>
        <view class="flex items-center gap-x-[12rpx] mb-[14rpx]">
            <view class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center">
                <text class="text-white text-[22rpx] font-bold">{{ step }}</text>
            </view>
            <text class="text-[28rpx] font-bold text-[#1A1A1A]">{{ title }}</text>
        </view>

        <view
            class="rounded-[24rpx] bg-[#EBF2FF] border border-solid border-[#C7DEFF] px-[24rpx] py-[24rpx] flex items-center gap-x-[20rpx]">
            <view class="w-[64rpx] h-[64rpx] rounded-[18rpx] bg-white flex items-center justify-center shrink-0">
                <image :src="selectedDeviceIcon" mode="aspectFit" class="w-[34rpx] h-[34rpx]" />
            </view>
            <view class="flex-1 min-w-0">
                <text class="block text-[28rpx] font-bold text-[#1A1A1A] line-clamp-1">
                    {{ selectedDeviceName }}
                </text>
                <text class="block text-[22rpx] text-[#888888] mt-[6rpx] line-clamp-1 break-all">
                    设备码 {{ selectedDeviceCodeText }}
                </text>
            </view>
            <view
                v-if="showSwitchButton"
                class="h-[56rpx] px-[24rpx] rounded-full bg-white border border-solid border-[#C7DEFF] flex items-center justify-center shrink-0 active:opacity-70"
                @click="showDevicePickerPopup = true">
                <text class="text-[24rpx] font-semibold text-primary">
                    {{ selectedDevice ? "切换" : "选择" }}
                </text>
            </view>
        </view>

        <u-popup v-model="showDevicePickerPopup" mode="bottom" border-radius="32" height="62%">
            <view class="h-full bg-white rounded-t-[32rpx] flex flex-col">
                <view class="px-[32rpx] pt-[28rpx] pb-[22rpx] border-b border-solid border-[#F0F0F0]">
                    <view class="w-[72rpx] h-[8rpx] bg-[#E5EAF3] rounded-full mx-auto mb-[28rpx]"></view>
                    <view class="flex items-center justify-between">
                        <view>
                            <text class="block text-[30rpx] font-bold text-[#1A1A1A]">{{ pickerTitle }}</text>
                            <text class="block text-[22rpx] text-[#888888] mt-[6rpx]">
                                可选 {{ selectableDevices.length }} 台
                            </text>
                        </view>
                        <view
                            class="w-[56rpx] h-[56rpx] bg-[#F4F6FA] rounded-full flex items-center justify-center active:opacity-80"
                            @click="showDevicePickerPopup = false">
                            <u-icon name="close" color="#888888" size="24" />
                        </view>
                    </view>
                </view>

                <scroll-view scroll-y class="flex-1 min-h-0">
                    <view class="px-[24rpx] py-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                        <view
                            v-if="selectableDevices.length > 0"
                            class="rounded-[28rpx] overflow-hidden border border-solid border-[#F0F0F0]">
                            <view
                                v-for="device in selectableDevices"
                                :key="getDeviceCode(device)"
                                class="device-row"
                                @click="handleSelectDevice(device)">
                                <view
                                    class="w-[72rpx] h-[72rpx] rounded-[20rpx] bg-[#F4F6FA] flex items-center justify-center shrink-0">
                                    <image :src="getDeviceIcon(device)" mode="aspectFit" class="w-[34rpx] h-[34rpx]" />
                                </view>
                                <view class="flex-1 min-w-0">
                                    <text class="block text-[28rpx] font-semibold text-[#1A1A1A] line-clamp-1">
                                        {{ getDeviceName(device) }}
                                    </text>
                                    <text class="block text-[22rpx] text-[#888888] mt-[4rpx] line-clamp-1 break-all">
                                        {{ getDeviceCode(device) || "-" }}
                                    </text>
                                </view>
                                <view class="px-[16rpx] py-[4rpx] rounded-[8rpx] bg-[#F0F0F0] shrink-0">
                                    <text class="text-[20rpx] font-semibold text-[#BBBBBB]">
                                        {{ getDeviceStatusText(device) }}
                                    </text>
                                </view>
                            </view>
                        </view>
                        <text
                            v-if="selectableDevices.length > 0"
                            class="block text-[22rpx] text-[#BBBBBB] text-center mt-[22rpx]">
                            {{ pickerHint }}
                        </text>

                        <view v-else class="pt-[100rpx] flex flex-col items-center">
                            <image
                                src="/static/images/icons/device_gray.svg"
                                mode="aspectFit"
                                class="w-[110rpx] h-[110rpx] opacity-60" />
                            <text class="text-[28rpx] font-semibold text-[#1A1A1A] mt-[24rpx]"> 暂无可选设备 </text>
                            <text class="text-[22rpx] text-[#888888] mt-[8rpx]"> 请先绑定设备后再继续 </text>
                            <view
                                v-if="showAddButton"
                                class="mt-[28rpx] h-[76rpx] px-[36rpx] rounded-full bg-primary flex items-center justify-center"
                                @click="handleAddDevice">
                                <text class="text-white text-[24rpx] font-semibold">去绑定设备</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import { isDeviceActivated, isDevicePermanent } from "./billing-plans";

type DeviceSelectMode = "activation" | "renew";

interface BillingDeviceItem {
    id?: number | string;
    device_code?: string;
    device_name?: string;
    status?: number;
    is_active?: boolean | number | string;
    is_activated?: boolean | number | string;
    activate_status?: boolean | number | string;
    activation_status?: boolean | number | string;
    active_status?: boolean | number | string;
    is_permanent?: boolean | number;
    plan_type?: string | number;
    card_type?: string | number;
    package_type?: string | number;
    package_name?: string;
    [key: string]: any;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        devices?: BillingDeviceItem[];
        fallbackDevice?: BillingDeviceItem;
        mode?: DeviceSelectMode;
        title?: string;
        step?: number | string;
        showAddButton?: boolean;
    }>(),
    {
        modelValue: "",
        devices: () => [],
        fallbackDevice: () => ({}),
        mode: "activation",
        title: "选择 / 确认设备",
        step: 1,
        showAddButton: true,
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: string): void;
    (event: "change", value: BillingDeviceItem): void;
    (event: "add-device"): void;
}>();

const showDevicePickerPopup = ref(false);

const getDeviceCode = (device?: BillingDeviceItem) => device?.device_code || "";

const getDeviceName = (device?: BillingDeviceItem) => device?.device_name || "未命名设备";

const isPermanentPlan = isDevicePermanent;

const selectedDevice = computed(() => {
    const deviceCode = props.modelValue || getDeviceCode(props.fallbackDevice);
    return (
        props.devices.find((item) => getDeviceCode(item) === deviceCode) ||
        (getDeviceCode(props.fallbackDevice) === deviceCode ? props.fallbackDevice : undefined)
    );
});

const selectableDevices = computed(() =>
    props.devices.filter((device) => {
        if (props.mode === "renew") {
            return isDeviceActivated(device) && !isPermanentPlan(device);
        }
        return !isDeviceActivated(device);
    }),
);

const selectedDeviceIcon = computed(() =>
    selectedDevice.value && isDeviceActivated(selectedDevice.value)
        ? "/static/images/icons/device_primary.svg"
        : "/static/images/icons/device_gray.svg",
);

const selectedDeviceName = computed(() => (selectedDevice.value ? getDeviceName(selectedDevice.value) : "请选择设备"));

const selectedDeviceCodeText = computed(() => getDeviceCode(selectedDevice.value) || "-");

const showSwitchButton = computed(() => props.devices.length > 0 || props.showAddButton);

const pickerTitle = computed(() => (props.mode === "renew" ? "选择续费设备" : "选择未激活设备"));

const pickerHint = computed(() => (props.mode === "renew" ? "仅显示可续费的设备" : "仅显示尚未激活的设备"));

const getDeviceIcon = (device: BillingDeviceItem) =>
    isDeviceActivated(device) ? "/static/images/icons/device_primary.svg" : "/static/images/icons/device_gray.svg";

const getDeviceStatusText = (device: BillingDeviceItem) => (isDeviceActivated(device) ? "已激活" : "未激活");

const handleSelectDevice = (device: BillingDeviceItem) => {
    const deviceCode = getDeviceCode(device);
    emit("update:modelValue", deviceCode);
    emit("change", device);
    showDevicePickerPopup.value = false;
};

const handleAddDevice = () => {
    showDevicePickerPopup.value = false;
    emit("add-device");
};
</script>

<style scoped lang="scss">
.device-row {
    @apply flex items-center gap-x-[20rpx] px-[24rpx] py-[24rpx] bg-white border-[0] border-b border-solid border-[#F0F0F0];

    &:active {
        background-color: #f0f5ff;
    }

    &:last-child {
        border-bottom: 0;
    }
}
</style>
