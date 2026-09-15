<template>
    <view class="dev-tab">
        <view class="dev-heading">
            <view class="dev-heading-copy">
                <text class="dev-heading-title">关联设备</text>
                <text class="dev-meta">
                    已绑定 {{ state.deviceTotal || state.deviceList.length }} 台
                </text>
            </view>
            <view class="dev-heading-actions">
                <view
                    class="dev-btn dev-btn--text dev-btn--compact active:opacity-70"
                    @click="actions.phoneManagement()"
                >
                    <text class="dev-control-content">AI 手机管理</text>
                </view>
                <view
                    class="dev-btn dev-btn--outline dev-btn--compact active:opacity-70"
                    @click="actions.selectDevice()"
                >
                    <text class="dev-control-content">添加设备</text>
                </view>
            </view>
        </view>

        <view v-if="isCloudMode" class="dev-choice">
            <view class="flex items-center gap-[20rpx]">
                <view class="dev-choice-icon">
                    <u-icon name="download" color="#1764CD" size="46"></u-icon>
                </view>
                <view class="flex-1 min-w-0 flex flex-col">
                    <text class="dev-item-title">当前为无手机模式</text>
                    <text class="dev-meta">云端出片 · 当前无需关联设备</text>
                </view>
            </view>
            <text class="dev-description">
                成片做好后，在内容记录里下载并手动发布。当前不会自动采集爆款。
            </text>
            <view class="dev-btn dev-btn--outline dev-btn--compact active:opacity-70" @click="actions.runMode()">
                <text class="dev-control-content">切换到手机模式</text>
            </view>
        </view>

        <view v-if="state.deviceList.length" class="flex flex-col gap-[24rpx]">
            <view
                v-for="device in state.deviceList"
                :key="device.id || device.device_code"
                class="dev-card"
            >
                <view class="dev-device-head">
                    <view class="flex-1 min-w-0">
                        <text class="dev-item-title line-clamp-1">
                            {{ device.device_name }}
                        </text>
                        <view class="flex items-center gap-[8rpx] mt-[8rpx]">
                            <view
                                class="w-[14rpx] h-[14rpx] rounded-full"
                                :style="{
                                    backgroundColor: state.getDeviceStatusStyle(device.status)
                                        .dotColor
                                }"
                            ></view>
                            <text
                                class="text-xs font-medium"
                                :style="{
                                    color: state.getDeviceStatusStyle(device.status).dotColor
                                }"
                            >
                                {{ state.getDeviceStatusStyle(device.status).label }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="dev-btn dev-btn--secondary dev-btn--compact dev-setting active:opacity-70"
                        @click.stop="actions.deviceSetting(device)"
                    >
                        <text class="dev-control-content">设置</text>
                    </view>
                </view>

                <view class="dev-accounts">
                    <template v-if="state.getDeviceAccounts(device).length">
                        <text
                            v-for="(account, index) in state.getDeviceAccounts(device)"
                            :key="account.id || index"
                            class="dev-account"
                            :class="state.getPlatformStyle(account).className"
                        >
                            {{ state.getPlatformStyle(account).label }}
                        </text>
                    </template>
                    <text v-else class="dev-account text-[#556A82]"> 暂无平台账号 </text>
                </view>

                <view class="dev-code-row">
                    <text class="dev-meta flex-1 min-w-0 break-all">
                        {{ device.device_code }}
                    </text>
                    <view class="dev-code-actions">
                        <view
                            class="dev-btn dev-btn--text dev-btn--compact active:opacity-70"
                            @click.stop="actions.copyDeviceCode(device)"
                        >
                            <text class="dev-control-content">复制设备码</text>
                        </view>
                        <view
                            class="dev-btn dev-btn--text dev-btn--compact active:opacity-70"
                            @click.stop="actions.unbind(device)"
                        >
                            <text class="dev-control-content">移除</text>
                        </view>
                    </view>
                </view>
            </view>

            <view v-if="state.deviceLoading" class="flex items-center justify-center py-[24rpx]">
                <u-loading mode="circle" color="#999999" size="28"></u-loading>
                <text class="text-[22rpx] text-[#999999] ml-[12rpx]">加载中...</text>
            </view>
            <view
                v-else-if="state.deviceFinished && state.deviceList.length"
                class="text-center text-xs text-content py-[24rpx]"
            >
                没有更多了
            </view>
        </view>

        <view
            v-else-if="state.deviceLoading"
            class="flex items-center justify-center gap-[12rpx] py-[32rpx]"
        >
            <u-loading mode="circle" color="#0065FB" size="28"></u-loading>
            <text class="text-xs text-content">加载设备中...</text>
        </view>

        <view v-else-if="!isCloudMode">
            <view class="dev-choice">
                <text class="dev-empty-title">绑定一台 AI 手机</text>
                <text class="dev-description">
                    通过设备采集选题，并把成片发布到绑定账号。需要常开、联网的安卓手机。
                </text>
                <view
                    class="dev-btn dev-btn--secondary active:opacity-85"
                    @click="actions.selectDevice()"
                >
                    <text>选择关联设备</text>
                </view>
            </view>
        </view>

        <!-- 成品直发没有运行模式这回事（规则弹窗里那一节只在视频剪辑下渲染），
             这里再给一个入口，用户点完就把 run_mode 改成 2，而发布 cron 会因此跳过这台设备，
             之后又没有任何界面能改回来 -->
        <view v-if="!isCloudMode && !isProductDirect" class="dev-choice">
            <text class="dev-item-title">也可以使用无手机模式</text>
            <text class="dev-description">云端出片，下载后自己发布，不占设备。</text>
            <view class="dev-btn dev-btn--outline dev-btn--compact active:opacity-70" @click="actions.runMode()">
                <text class="dev-control-content">切换为云端出片</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { isCloudRunMode } from '@/ai_modules/person/enums'
interface DeviceItem {
    id: string
    device_name: string
    status: number
    device_code: string
    accounts?: DeviceAccount[]
    platform_accounts?: DeviceAccount[]
    platformAccounts?: DeviceAccount[]
}

interface DeviceAccount {
    id?: string | number
    platform?: string
    platform_name?: string
    name?: string
    nickname?: string
    account_name?: string
    type?: string | number
    account_type?: string | number
    app_type?: string | number
}

const props = defineProps<{
    state: {
        deviceList: DeviceItem[]
        runMode: number
        deviceTotal: number
        deviceLoading: boolean
        deviceFinished: boolean
        getDeviceStatusStyle: (status: number) => { dotColor: string; label: string }
        getDeviceIcon: (status: number) => string
        getDeviceAccounts: (device: DeviceItem) => DeviceAccount[]
        getPlatformStyle: (account: DeviceAccount) => { label: string; className: string }
    }
    actions: {
        selectDevice: () => void
        phoneManagement: () => void
        deviceSetting: (device: DeviceItem) => void
        copyDeviceCode: (device: DeviceItem) => void
        unbind: (device: DeviceItem) => void
        runMode: () => void
    }
    /** 人设工作方式是成品库直发（publish_mode=2）：不经 AI 合成，也就没有运行模式可选 */
    isProductDirect?: boolean
}>()

// 无手机模式本来就不需要设备，这里不能再劝人去绑手机。
// 成品直发的人设没有运行模式这回事，历史上留下的 run_mode=2 不该让这一屏显示成无手机模式——
// 发布 cron 恰恰要求成品直发走手机模式，显示成云端只会让人以为不用绑设备
const isCloudMode = computed(() => isCloudRunMode(props.state.runMode) && !props.isProductDirect)
</script>

<style scoped lang="scss">
.dev-tab {
    @apply flex flex-col gap-[24rpx];
}

.dev-heading {
    @apply flex flex-wrap items-center justify-between gap-[16rpx];
}

.dev-heading-copy {
    @apply flex-1 min-w-[220rpx] flex flex-col gap-[8rpx];
}

.dev-heading-title {
    @apply text-base font-[650] leading-[1.55] text-[#202937];
}

.dev-heading-actions {
    @apply flex flex-wrap items-center gap-[8rpx];
}

.dev-meta {
    @apply text-xs leading-[1.65] text-[#647184];
}

.dev-choice,
.dev-card {
    @apply rounded-[36rpx] p-[32rpx] bg-white;
}

.dev-choice {
    @apply flex flex-col items-start gap-[32rpx];
}

.dev-choice-icon {
    @apply w-[96rpx] h-[96rpx] rounded-[28rpx] shrink-0 flex items-center justify-center bg-[#EDF3FF];
}

.dev-item-title {
    @apply block text-base font-[620] leading-[1.55] text-[#26364E];
}

.dev-description {
    @apply block text-base leading-[1.7] text-[#647184];
}

.dev-empty-title {
    @apply block text-lg font-bold leading-[1.55] text-[#202937];
}

.dev-btn {
    @apply box-border inline-flex items-center justify-center gap-[14rpx] min-h-[88rpx]
        px-[32rpx] py-[20rpx] rounded-[24rpx] border-[2rpx] border-solid border-[transparent]
        text-base font-semibold leading-[1.4];
}

.dev-btn--outline {
    @apply bg-white text-[#36516F] border-[#D8E2ED];
}

.dev-btn--secondary {
    @apply bg-[#EDF4FF] text-[#075ECF];
}

.dev-btn--text {
    @apply bg-[transparent] text-[#0761D9] px-[10rpx] py-[16rpx] font-medium;
}

.dev-btn--compact {
    @apply relative min-w-[88rpx] px-[16rpx] py-0 rounded-none border-[0] bg-[transparent] text-xs font-medium;
}

.dev-btn--compact.dev-btn--outline::before,
.dev-btn--compact.dev-btn--secondary::before {
    content: '';
    @apply absolute inset-x-0 top-[14rpx] bottom-[14rpx] rounded-[12rpx] border-[2rpx] border-solid border-[transparent] pointer-events-none;
}

.dev-btn--compact.dev-btn--outline::before {
    @apply bg-white border-[#D8E2ED];
}

.dev-btn--compact.dev-btn--secondary::before {
    @apply bg-[#EDF4FF];
}

.dev-control-content {
    @apply relative;
}

.dev-device-head {
    @apply flex items-center gap-[22rpx] py-[28rpx]
        border-[0] border-b-[2rpx] border-solid border-[#E7ECF2];
}

.dev-setting {
    @apply shrink-0;
}

.dev-accounts {
    @apply flex flex-wrap gap-[14rpx] my-[24rpx];
}

.dev-account {
    @apply box-border inline-flex items-center min-h-[88rpx] rounded-[16rpx] px-[20rpx] py-[12rpx]
        border-[2rpx] border-solid border-[#DFE6EF] text-xs leading-[1.55];
}

.dev-code-row {
    @apply flex flex-wrap items-center justify-between gap-[24rpx];
}

.dev-code-actions {
    @apply flex flex-wrap items-center gap-[8rpx];
}
</style>
