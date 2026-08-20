<template>
    <view>
        <view class="flex items-center justify-between mb-[20rpx]">
            <text class="text-sm font-bold text-[#1F2937]">
                设备列表 ({{ state.deviceTotal || state.deviceList.length }})
            </text>
            <view class="flex items-center gap-[12rpx]">
                <view
                    class="flex items-center gap-[8rpx] text-[22rpx] font-semibold rounded-full px-[20rpx] py-[10rpx]"
                    style="color: #0065fb; background: #ebf2ff; border: 2rpx solid #bad4ff"
                    @click="actions.phoneManagement()">
                    <image
                        src="/static/images/icons/device_primary.svg"
                        class="w-[22rpx] h-[22rpx]"
                        mode="aspectFit"></image>
                    <text>AI手机管理</text>
                </view>
                <view
                    class="flex items-center gap-[8rpx] text-[22rpx] font-semibold rounded-full px-[20rpx] py-[10rpx]"
                    style="color: #0065fb; background: #ebf2ff; border: 2rpx solid #bad4ff"
                    @click="actions.selectDevice()">
                    <u-icon name="plus-circle" color="#0065FB" size="22"></u-icon>
                    <text>添加设备</text>
                </view>
            </view>
        </view>

        <view v-if="state.deviceList.length" class="flex flex-col gap-[18rpx]">
            <view
                v-for="device in state.deviceList"
                :key="device.id || device.device_code"
                class="bg-white rounded-[28rpx] p-[28rpx] detail-card-shadow">
                <view class="flex items-center gap-[20rpx] mb-[20rpx]">
                    <view
                        class="w-[88rpx] h-[88rpx] rounded-[24rpx] flex items-center justify-center shrink-0"
                        :class="
                            state.getDeviceStatusStyle(device.status).label === '离线待机'
                                ? 'bg-[#F3F7FC]'
                                : 'bg-[#EBF2FF]'
                        ">
                        <image
                            :src="state.getDeviceIcon(device.status)"
                            class="w-[44rpx] h-[44rpx]"
                            mode="aspectFit"></image>
                    </view>
                    <view class="flex-1 min-w-0">
                        <text class="text-sm font-bold text-[#1F2937] line-clamp-1">
                            {{ device.device_name }}
                        </text>
                        <view class="flex items-center gap-[8rpx] mt-[8rpx]">
                            <view
                                class="w-[14rpx] h-[14rpx] rounded-full"
                                :style="{
                                    backgroundColor: state.getDeviceStatusStyle(device.status).dotColor,
                                }"></view>
                            <text
                                class="text-xs font-medium"
                                :style="{
                                    color: state.getDeviceStatusStyle(device.status).dotColor,
                                }">
                                {{ state.getDeviceStatusStyle(device.status).label }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="px-[22rpx] py-[12rpx] rounded-full flex items-center gap-[6rpx]"
                        style="color: #0065fb; background: #ebf2ff; border: 2rpx solid #bad4ff"
                        @click.stop="actions.deviceSetting(device)">
                        <text class="text-xs font-semibold">设置</text>
                        <u-icon name="arrow-right" color="#0065FB" size="18"></u-icon>
                    </view>
                </view>

                <view class="flex flex-wrap gap-[10rpx] mb-[18rpx]">
                    <template v-if="state.getDeviceAccounts(device).length">
                        <text
                            v-for="(account, index) in state.getDeviceAccounts(device)"
                            :key="account.id || index"
                            class="text-[22rpx] font-semibold px-[18rpx] py-[8rpx] rounded-full"
                            :class="state.getPlatformStyle(account).className">
                            {{ state.getPlatformStyle(account).label }}
                        </text>
                    </template>
                    <text
                        v-else
                        class="text-[22rpx] font-medium text-[#9CA3AF] bg-[#F3F4F6] px-[18rpx] py-[8rpx] rounded-full">
                        暂无平台账号
                    </text>
                </view>

                <view class="flex items-center gap-[12rpx] bg-[#F9FAFB] rounded-[22rpx] px-[20rpx] py-[16rpx]">
                    <u-icon name="grid" color="#9CA3AF" size="22"></u-icon>
                    <text class="text-[20rpx] text-[#9CA3AF] shrink-0">设备码</text>
                    <text class="text-[20rpx] text-[#4B5563] font-mono flex-1 line-clamp-1">
                        {{ device.device_code }}
                    </text>
                    <view
                        class="w-[44rpx] h-[44rpx] bg-white rounded-[14rpx] border border-solid border-[#E5E7EB] flex items-center justify-center shrink-0"
                        @click.stop="actions.copyDeviceCode(device)">
                        <image src="/static/images/icons/copy.svg" class="w-[22rpx] h-[22rpx]" mode="aspectFit"></image>
                    </view>
                    <view class="w-[2rpx] h-[26rpx] bg-[#E5E7EB]"></view>
                    <text class="text-[22rpx] text-[#ef4444] font-medium" @click.stop="actions.unbind(device)">
                        移除
                    </text>
                </view>
            </view>

            <view v-if="state.deviceLoading" class="flex items-center justify-center py-[24rpx]">
                <u-loading mode="circle" color="#9CA3AF" size="28"></u-loading>
                <text class="text-[22rpx] text-[#9CA3AF] ml-[12rpx]">加载中...</text>
            </view>
            <view
                v-else-if="state.deviceFinished && state.deviceList.length"
                class="text-center text-[22rpx] text-[#C0C4CC] py-[24rpx]">
                没有更多了
            </view>
        </view>

        <empty v-else text="暂无关联设备" />
    </view>
</template>

<script setup lang="ts">
interface DeviceItem {
    id: string;
    device_name: string;
    status: number;
    device_code: string;
    accounts?: DeviceAccount[];
    platform_accounts?: DeviceAccount[];
    platformAccounts?: DeviceAccount[];
}

interface DeviceAccount {
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

const props = defineProps<{
    state: {
        deviceList: DeviceItem[];
        deviceTotal: number;
        deviceLoading: boolean;
        deviceFinished: boolean;
        getDeviceStatusStyle: (status: number) => { dotColor: string; label: string };
        getDeviceIcon: (status: number) => string;
        getDeviceAccounts: (device: DeviceItem) => DeviceAccount[];
        getPlatformStyle: (account: DeviceAccount) => { label: string; className: string };
    };
    actions: {
        selectDevice: () => void;
        phoneManagement: () => void;
        deviceSetting: (device: DeviceItem) => void;
        copyDeviceCode: (device: DeviceItem) => void;
        unbind: (device: DeviceItem) => void;
    };
}>();
</script>

<style scoped lang="scss">
.detail-card-shadow {
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}
</style>
