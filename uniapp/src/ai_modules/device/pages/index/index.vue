<template>
    <view class="h-screen flex flex-col bg-[#F4F6FA] font-sans">
        <u-navbar title="AI手机管理" title-bold :border-bottom="false" :background="{ background: '#F4F6FA' }">
        </u-navbar>
        <view class="px-[24rpx] pt-[16rpx]">
            <view
                class="mt-[24rpx] rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center gap-x-[24rpx] shadow-[0_8rpx_40rpx_rgba(43,110,255,0.25)] purchase-card"
                @click="handleOpenPurchasePopup">
                <view
                    class="w-[72rpx] h-[72rpx] rounded-[20rpx] bg-[rgba(255,255,255,0.18)] flex items-center justify-center shrink-0">
                    <image
                        src="@/ai_modules/device/static/icons/key.svg"
                        mode="aspectFit"
                        class="w-[34rpx] h-[34rpx]" />
                </view>
                <view class="flex-1 min-w-0">
                    <text class="block text-[26rpx] font-bold text-white">购买CDK</text>
                    <text class="block text-[20rpx] mt-[4rpx] text-[rgba(255,255,255,0.7)] line-clamp-1">
                        CDK可随时用于设备激活 · 长期有效
                    </text>
                </view>
                <u-icon name="arrow-right" color="rgba(255,255,255,0.72)" size="26" />
            </view>

            <view
                class="mt-[16rpx] bg-white rounded-[28rpx] px-[28rpx] py-[22rpx] flex items-center gap-x-[24rpx] shadow-[0_4rpx_20rpx_rgba(15,23,42,0.05)] active:opacity-80"
                @click="handleOpenMyCodesPopup">
                <view
                    class="w-[72rpx] h-[72rpx] rounded-[20rpx] bg-[#EBF2FF] flex items-center justify-center shrink-0">
                    <image
                        src="@/ai_modules/device/static/icons/ticket.svg"
                        mode="aspectFit"
                        class="w-[34rpx] h-[34rpx]" />
                </view>
                <view class="flex-1 min-w-0">
                    <text class="block text-[26rpx] font-semibold text-[#1A1A1A]">我的CDK</text>
                    <text class="block text-[20rpx] text-[#888888] mt-[4rpx] line-clamp-1">
                        查看已购买的CDK及使用状态
                    </text>
                </view>
                <view
                    v-if="activationCodesLoaded"
                    class="px-[16rpx] py-[4rpx] rounded-full bg-[#EBF2FF] text-primary text-[20rpx] font-bold">
                    {{ activationCodes.length }} 张
                </view>
                <u-icon name="arrow-right" color="#BBBBBB" size="24" />
            </view>

            <view
                class="mt-[16rpx] rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center justify-center gap-x-[12rpx] bg-white border-[2rpx] border-dashed border-[#BAD4FF] active:opacity-70"
                @click="handleOpenAddPhonePopup">
                <u-icon name="plus" color="#2B6EFF" size="30" />
                <text class="text-[26rpx] font-semibold text-primary">添加手机</text>
            </view>
        </view>

        <view class="grow min-h-0 mt-[24rpx]">
            <z-paging
                ref="pagingRef"
                v-model="deviceList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[24rpx]">
                    <view class="flex flex-col gap-y-[24rpx]">
                        <template v-if="deviceList.length > 0">
                            <view
                                v-for="item in deviceList"
                                :key="item.device_code || item.id"
                                class="phone-card bg-white rounded-[28rpx] p-[28rpx] shadow-[0_8rpx_32rpx_rgba(15,23,42,0.06)]"
                                :class="isDeviceActivated(item) ? '' : 'opacity-70'"
                                @click="goDeviceDetail(item)">
                                <view class="flex items-start gap-x-[24rpx]">
                                    <view
                                        class="w-[88rpx] h-[88rpx] rounded-[22rpx] flex items-center justify-center shrink-0 mt-[4rpx]"
                                        :style="{ background: getDeviceIconBg(item) }">
                                        <image
                                            :src="getDeviceIcon(item)"
                                            mode="aspectFit"
                                            class="w-[42rpx] h-[42rpx]" />
                                    </view>

                                    <view class="flex-1 min-w-0">
                                        <view class="flex items-start justify-between gap-x-[16rpx]">
                                            <view class="flex-1 min-w-0">
                                                <view class="flex items-center gap-x-[12rpx] flex-wrap">
                                                    <text
                                                        class="text-[30rpx] leading-[42rpx] font-semibold text-[#1A1A1A] line-clamp-1 break-all">
                                                        {{ item.device_name || "未命名设备" }}
                                                    </text>
                                                    <view
                                                        class="px-[12rpx] py-[4rpx] rounded-[8rpx] text-[18rpx] font-bold"
                                                        :class="getPlanBadgeClass(item)">
                                                        {{ getPlanBadgeText(item) }}
                                                    </view>
                                                </view>
                                            </view>
                                            <view class="shrink-0 flex items-center gap-x-[10rpx]">
                                                <view
                                                    v-if="item.device_code"
                                                    class="px-[20rpx] h-[48rpx] rounded-full border flex items-center justify-center active:opacity-70"
                                                    :class="[
                                                        getUsedButtonClass(item),
                                                        isDeviceUsedChanging(item) ? 'opacity-60' : '',
                                                    ]"
                                                    @click.stop="handleToggleDeviceUsed(item)">
                                                    <text class="text-[22rpx] font-semibold">
                                                        {{ getUsedButtonText(item) }}
                                                    </text>
                                                </view>
                                                <view
                                                    v-if="isDeviceActivated(item) && !isPermanentPlan(item)"
                                                    class="px-[20rpx] h-[48rpx] rounded-full border border-[#C7DEFF] bg-[#EBF2FF] flex items-center justify-center active:opacity-70"
                                                    @click.stop="handleOpenRenewPopup(item)">
                                                    <text class="text-[20rpx] font-semibold text-primary">续费</text>
                                                </view>
                                            </view>
                                        </view>

                                        <view class="flex items-center gap-x-[8rpx] mt-[12rpx]">
                                            <text class="text-[22rpx] text-[#888888] line-clamp-1 break-all">
                                                设备码 {{ item.device_code || "-" }}
                                            </text>
                                            <view
                                                v-if="item.device_code"
                                                class="w-[40rpx] h-[40rpx] rounded-[12rpx] bg-[#F4F6FA] border border-[#E8ECF0] flex items-center justify-center shrink-0 active:opacity-70"
                                                @click.stop="copy(item.device_code)">
                                                <image
                                                    src="/static/images/icons/copy.svg"
                                                    mode="aspectFit"
                                                    class="w-[22rpx] h-[22rpx]" />
                                            </view>
                                        </view>
                                        <text class="block text-[22rpx] text-[#BBBBBB] mt-[4rpx]">
                                            绑定于 {{ getBindTime(item) }}
                                        </text>

                                        <view v-if="isDeviceActivated(item)" class="flex items-center mt-[14rpx]">
                                            <view class="flex min-w-0 flex-1 items-center">
                                                <view class="flex items-center gap-x-[8rpx]">
                                                    <view
                                                        class="w-[12rpx] h-[12rpx] rounded-full"
                                                        :class="getRunningStatusDotClass(item)" />
                                                    <text
                                                        class="text-[24rpx] font-semibold"
                                                        :class="getRunningStatusTextClass(item)">
                                                        {{ getRunningStatusText(item) }}
                                                    </text>
                                                </view>
                                            </view>
                                        </view>

                                        <view
                                            v-if="!isDeviceActivated(item)"
                                            class="mt-[20rpx] flex items-center gap-x-[14rpx]">
                                            <view
                                                class="flex-1 h-[82rpx] rounded-[16rpx] bg-primary text-white flex items-center justify-center gap-x-[10rpx] shadow-[0_6rpx_20rpx_rgba(43,110,255,0.25)] active:opacity-80"
                                                @click.stop="handleOpenRenewPopup(item)">
                                                <image
                                                    src="@/ai_modules/device/static/icons/zap_white.svg"
                                                    mode="aspectFit"
                                                    class="w-[26rpx] h-[26rpx]" />
                                                <text class="text-[24rpx] font-semibold">立即激活</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view
                                    v-if="isDeviceActivated(item)"
                                    class="mt-[24rpx] pt-[22rpx] border-t border-[#F0F0F0] flex items-center gap-x-[10rpx]">
                                    <template v-if="item.accounts?.length">
                                        <view
                                            v-for="(account, accountIndex) in item.accounts"
                                            :key="accountIndex"
                                            class="h-[40rpx] px-[16rpx] rounded-[8rpx] flex items-center"
                                            :style="getPlatformTagStyle(account)">
                                            <text class="text-[20rpx] font-bold">
                                                {{ getPlatformLabel(getAccountType(account)) }}
                                            </text>
                                        </view>
                                    </template>
                                    <view
                                        v-else
                                        class="h-[40rpx] px-[14rpx] rounded-[8rpx] bg-[#F4F6FA] flex items-center">
                                        <text class="text-[20rpx] text-[#888888]">暂无平台账号</text>
                                    </view>
                                    <view class="ml-auto flex items-center gap-x-[18rpx]">
                                        <view
                                            class="flex items-center gap-x-[4rpx] active:opacity-70"
                                            @click.stop="goDeviceDetail(item)">
                                            <text class="text-[20rpx] font-semibold text-primary">管理</text>
                                            <u-icon name="arrow-right" color="#0065FB" size="20" />
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </template>
                    </view>
                </view>

                <template #empty>
                    <view class="w-full h-full pt-10 flex flex-col items-center">
                        <image
                            :src="`${config.baseUrl}static/images/device_empty.png`"
                            mode="aspectFit"
                            class="w-[442rpx] h-[492rpx] mx-auto" />
                        <view
                            class="mx-auto mt-4 w-[300rpx] h-[84rpx] rounded-full bg-[#212121] flex items-center justify-center gap-x-1 text-white font-medium text-[30rpx] shadow-md"
                            @click="handleOpenAddPhonePopup">
                            即刻新增设备
                            <u-icon name="arrow-right" color="#ffffff" size="16"></u-icon>
                        </view>
                    </view>
                </template>
            </z-paging>
        </view>

        <dragon-button :x-edge="-20" :y-edge="100" v-if="deviceList.length > 0">
            <view
                class="w-[100rpx] h-[100rpx] rounded-full flex items-center justify-center shadow-lg bg-primary"
                @click="toPage('/ai_modules/device/pages/choose_task_type/choose_task_type')">
                <u-icon name="plus" color="#ffffff" size="34"></u-icon>
            </view>
        </dragon-button>
    </view>

    <renew-popup
        v-model="showRenewPopup"
        :device="currentBillingDevice"
        :devices="deviceList"
        :plans="planItems"
        :activation-codes="activationCodes"
        @purchase-code="handleOpenPurchasePopup"
        @add-device="handleOpenAddPhonePopup"
        @success="handleBillingSuccess" />
    <purchase-code-popup v-model="showPurchasePopup" :plans="planItems" @success="handlePurchaseSuccess" />
    <add-phone-popup
        v-model="showAddPhonePopup"
        :before-load-qrcode="handleBeforeLoadAddPhoneQrcode"
        @bound="handleDeviceBound" />
    <activation-codes-popup
        v-model="showMyCodesPopup"
        :codes="activationCodes"
        :loading="activationCodesLoading"
        @purchase="handleOpenPurchasePopup" />
    <popup-bottom
        v-model="showDeviceQrcodePopup"
        height="58%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true">
        <template #header>
            <view class="px-[32rpx] pt-[24rpx]">
                <view class="w-[72rpx] h-[8rpx] bg-[#E5EAF3] rounded-full mx-auto mb-[32rpx]"></view>
                <view class="flex items-center justify-between mb-[28rpx]">
                    <view class="min-w-0 flex-1 pr-[24rpx]">
                        <text class="block text-[34rpx] font-bold text-[#1A1A1A]">设备二维码</text>
                        <text class="block text-[22rpx] text-[#888888] mt-[6rpx] line-clamp-1">
                            {{ currentQrcodeDevice.device_name || currentQrcodeDevice.device_code || "-" }}
                        </text>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] bg-[#F4F6FA] rounded-full flex items-center justify-center active:opacity-80"
                        @click="showDeviceQrcodePopup = false">
                        <u-icon name="close" color="#888888" size="24" />
                    </view>
                </view>
            </view>
        </template>

        <template #content>
            <view class="px-[40rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                <view class="flex flex-col items-center">
                    <view
                        class="w-full rounded-[28rpx] bg-[#F7F9FF] border border-[#E8ECF0] px-[24rpx] py-[20rpx] flex items-center gap-x-[16rpx]">
                        <image
                            src="@/ai_modules/device/static/icons/device.svg"
                            mode="aspectFit"
                            class="w-[34rpx] h-[34rpx] shrink-0" />
                        <view class="min-w-0 flex-1">
                            <text class="block text-[22rpx] text-[#888888]">设备码</text>
                            <text class="block text-[24rpx] font-mono font-semibold text-[#1A1A1A] line-clamp-1">
                                {{ currentQrcodeDevice.device_code || "-" }}
                            </text>
                        </view>
                        <view
                            v-if="currentQrcodeDevice.device_code"
                            class="w-[52rpx] h-[52rpx] rounded-[14rpx] bg-white border border-[#E8ECF0] flex items-center justify-center shrink-0 active:opacity-70"
                            @click="copy(currentQrcodeDevice.device_code)">
                            <image src="/static/images/icons/copy.svg" mode="aspectFit" class="w-[24rpx] h-[24rpx]" />
                        </view>
                    </view>

                    <view
                        class="mt-[32rpx] w-[400rpx] h-[400rpx] rounded-[32rpx] bg-[#F8FAFF] border border-[#E2E8F0] flex items-center justify-center overflow-hidden p-[24rpx]">
                        <image
                            v-if="deviceQrcodeUrl"
                            :src="deviceQrcodeUrl"
                            show-menu-by-longpress
                            mode="aspectFit"
                            class="w-full h-full rounded-[20rpx]" />
                        <view v-else class="flex flex-col items-center">
                            <image
                                src="@/ai_modules/device/static/icons/qr_code.svg"
                                mode="aspectFit"
                                class="w-[120rpx] h-[120rpx] opacity-60" />
                            <text class="text-[22rpx] text-[#94A3B8] mt-[16rpx]">
                                {{ deviceQrcodeLoading ? "二维码加载中" : "二维码获取失败" }}
                            </text>
                        </view>
                    </view>

                    <text class="mt-[24rpx] text-[24rpx] font-semibold text-[#1A1A1A]">使用微信扫码查看设备信息</text>
                    <text class="mt-[10rpx] text-[22rpx] leading-[36rpx] text-[#888888] text-center">
                        可长按二维码保存或识别
                    </text>

                    <view
                        class="mt-[32rpx] w-full h-[88rpx] rounded-[24rpx] bg-primary flex items-center justify-center active:opacity-85"
                        @click="handleRefreshDeviceQrcode">
                        <text class="text-[28rpx] font-semibold text-white">刷新二维码</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getDeviceAuthMyCodes, getDeviceAuthPlanList } from "@/api/device_auth";
import { getDeviceDetail, getDeviceList, getDeviceQrcode, unbindDevice, updateDeviceUsed } from "@/api/device";
import {
    normalizePlanList,
    normalizeActivationCodeList,
    isDeviceActivated,
    isDeviceExpired,
    isDevicePermanent as isPermanentPlan,
    type PlanItem,
    type ActivationCodeItem,
} from "@/ai_modules/device/components/billing-package/billing-plans";
import { DEVICE_AUTH_TAB_PARAM, type DeviceAuthTabKey } from "@/ai_modules/device/enums";
import config from "@/config";
import { AppTypeEnum } from "@/enums/appEnums";
import { useCopy } from "@/hooks/useCopy";
import AddPhonePopup from "@/components/add-phone-popup/add-phone-popup.vue";
import { useDeviceStore } from "@/ai_modules/device/stores/device";
import ActivationCodesPopup from "./components/activation-codes-popup.vue";
import PurchaseCodePopup from "./components/purchase-code-popup.vue";
import RenewPopup from "./components/renew-popup.vue";

enum TaskStatusEnum {
    OFFLINE = 0,
}

enum DeviceUsedStatusEnum {
    RESET = 0,
    USED = 1,
}

type DeviceTabKey = DeviceAuthTabKey;

interface DeviceItem {
    id?: number | string;
    device_code?: string;
    device_name?: string;
    status?: number;
    auto_type?: number;
    accounts?: any[];
    tasks?: any[];
    create_time?: string;
    bind_time?: string;
    update_time?: string;
    touch_number?: number;
    crawling_number?: number;
    publish_number?: number;
    is_used?: number;
    [key: string]: any;
}

const PLATFORM_MAP: Record<string | number, { label: string; bg: string; textColor: string }> = {
    [AppTypeEnum.WECHAT]: {
        label: "微信",
        bg: "#ECFDF3",
        textColor: "#16A34A",
    },
    [AppTypeEnum.XHS]: {
        label: "小红书",
        bg: "#FFF1F2",
        textColor: "#E11D48",
    },
    [AppTypeEnum.DOUYIN]: {
        label: "抖音",
        bg: "#111111",
        textColor: "#FFFFFF",
    },
    [AppTypeEnum.KUAISHOU]: {
        label: "快手",
        bg: "#FFF7ED",
        textColor: "#EA580C",
    },
    sph: {
        label: "视频号",
        bg: "#F4F6FA",
        textColor: "#888888",
    },
};

const { copy } = useCopy();
const deviceStore = useDeviceStore();

const deviceList = ref<DeviceItem[]>([]);
const activationCodes = ref<ActivationCodeItem[]>([]);
const activationCodesLoaded = ref(false);
const activationCodesLoading = ref(false);
const planItems = ref<PlanItem[]>([]);
const tabCounts = ref<Record<DeviceTabKey, number>>({ all: 0, active: 0, inactive: 0 });
const activeDeviceTab = ref<DeviceTabKey>("all");
const currentBillingDevice = ref<DeviceItem>({});
const pagingRef = shallowRef();
const showRenewPopup = ref(false);
const showPurchasePopup = ref(false);
const showMyCodesPopup = ref(false);
const showAddPhonePopup = ref(false);
const showDeviceQrcodePopup = ref(false);
const currentQrcodeDevice = ref<DeviceItem>({});
const deviceQrcodeUrl = ref("");
const deviceQrcodeLoading = ref(false);
const changingUsedDeviceCode = ref("");
let deviceQrcodePollTimer: ReturnType<typeof setInterval> | null = null;
let deviceQrcodePolling = false;

const deviceTabs = computed(() => [
    {
        key: "all" as DeviceTabKey,
        label: "全部",
        count: tabCounts.value.all,
        countColor: "#1A1A1A",
    },
    {
        key: "active" as DeviceTabKey,
        label: "已激活",
        count: tabCounts.value.active,
        countColor: "#52C41A",
    },
    {
        key: "inactive" as DeviceTabKey,
        label: "未激活",
        count: tabCounts.value.inactive,
        countColor: "#BBBBBB",
    },
]);

const getPlanBadgeText = (item: DeviceItem) => {
    if (isDeviceExpired(item)) return "已过期";
    if (!isDeviceActivated(item)) return "未激活";
    if (isPermanentPlan(item)) return "永久卡";
    return getExpireRemainText(item.auth_expire_time);
};

const getPlanBadgeClass = (item: DeviceItem) => {
    if (isDeviceExpired(item)) return "bg-[#FFF1F0] text-[#FF4D4F]";
    if (!isDeviceActivated(item)) return "bg-[#F0F0F0] text-[#BBBBBB]";
    if (isPermanentPlan(item)) return "bg-[#FF8C00] text-white";
    const remainMs = getExpireRemainMs(item.auth_expire_time);
    if (remainMs !== null && remainMs < 0) return "bg-[#FFF1F0] text-[#FF4D4F]";
    const remainDays = remainMs === null ? null : Math.floor(remainMs / 86400000);
    if (remainDays !== null && remainDays <= 7) return "bg-[#FFF1F0] text-[#FF4D4F]";
    if (remainDays !== null && remainDays <= 30) return "bg-[#FFF7E6] text-[#FA8C16]";
    return "bg-[#EBF2FF] text-primary";
};

const getExpireRemainMs = (expireTime?: string) => {
    if (!expireTime) return null;
    const normalizedExpireTime = String(expireTime).replace(/年|-/g, "/").replace(/月/g, "/").replace(/日/g, "").trim();
    const expireMs = Date.parse(normalizedExpireTime);
    if (Number.isNaN(expireMs)) return null;
    return expireMs - Date.now();
};

const getExpireRemainText = (expireTime?: string) => {
    const remainMs = getExpireRemainMs(expireTime);
    if (remainMs === null) return "已激活";
    if (remainMs < 0) return "已过期";
    if (remainMs >= 86400000) return `剩余 ${Math.floor(remainMs / 86400000)} 天`;
    if (remainMs >= 3600000) return `剩余 ${Math.ceil(remainMs / 3600000)} 小时`;
    return `剩余 ${Math.max(1, Math.ceil(remainMs / 60000))} 分钟`;
};

const getDeviceIcon = (item: DeviceItem) =>
    item.status === TaskStatusEnum.OFFLINE
        ? "/static/images/icons/device_gray.svg"
        : "/static/images/icons/device_primary.svg";

const getDeviceIconBg = (item: DeviceItem) => {
    if (!isDeviceActivated(item)) return "#F4F6FA";
    if (!isDeviceOnline(item)) return "#F4F6FA";
    if (isPermanentPlan(item)) return "#F4F6FA";
    return "#EBF2FF";
};

const getBindTime = (item: DeviceItem) => item.bind_time || item.create_time || item.update_time || "-";

const isDeviceOnline = (item: DeviceItem) => item.status !== TaskStatusEnum.OFFLINE;

const isDeviceUsed = (item: DeviceItem) => Number(item.is_used) === DeviceUsedStatusEnum.USED;

const isDeviceUsedChanging = (item: DeviceItem) =>
    !!item.device_code && changingUsedDeviceCode.value === item.device_code;

const getUsedButtonText = (item: DeviceItem) => {
    if (isDeviceUsedChanging(item)) return isDeviceUsed(item) ? "重置中" : "使用中";
    return isDeviceUsed(item) ? "重置" : "使用";
};

const getUsedButtonClass = (item: DeviceItem) =>
    isDeviceUsed(item) ? "border-[#E8ECF0] bg-[#F4F6FA] text-[#666666]" : "border-[#C7DEFF] bg-[#EBF2FF] text-primary";

const getRunningStatusText = (item: DeviceItem) => {
    const statusMap: Record<number, string> = {
        0: "离线",
        1: "空闲",
        2: "运行中",
    };
    return statusMap[item.status ?? 2] || "运行中";
};

const getRunningStatusDotClass = (item: DeviceItem) => (isDeviceOnline(item) ? "bg-[#52C41A]" : "bg-[#BBBBBB]");

const getRunningStatusTextClass = (item: DeviceItem) => (isDeviceOnline(item) ? "text-[#52C41A]" : "text-[#BBBBBB]");

const getAccountType = (account: any) => account.account_type ?? account.type ?? account.app_type ?? AppTypeEnum.WECHAT;

const getPlatformLabel = (platform: string | number) => PLATFORM_MAP[platform]?.label ?? "平台";

const getPlatformTagStyle = (account: any) => {
    const platform = PLATFORM_MAP[getAccountType(account)];
    return {
        background: platform?.bg ?? "#F4F6FA",
        color: platform?.textColor ?? "#888888",
    };
};

const handleChangeDeviceTab = (tab: DeviceTabKey) => {
    if (activeDeviceTab.value === tab) return;
    activeDeviceTab.value = tab;
    pagingRef.value?.reload();
};

const pickCount = (...values: any[]) => {
    for (const value of values) {
        if (value !== undefined && value !== null && value !== "") {
            const num = Number(value);
            if (!Number.isNaN(num)) return num;
        }
    }
    return undefined;
};

// tab 计数优先取接口返回；接口未提供时在全部 tab 下按当前列表兜底
const updateTabCounts = (res: Record<string, any>, lists: DeviceItem[]) => {
    const stats = res?.counts ?? res?.stats ?? res?.tab_counts ?? {};
    const all = pickCount(stats.all, res?.total_count, res?.count_all);
    const active = pickCount(stats.active, res?.active_count, res?.activated_count);
    const inactive = pickCount(stats.inactive, res?.inactive_count, res?.unactivated_count);

    if (all !== undefined || active !== undefined || inactive !== undefined) {
        tabCounts.value = {
            all: all ?? tabCounts.value.all,
            active: active ?? tabCounts.value.active,
            inactive: inactive ?? tabCounts.value.inactive,
        };
        return;
    }

    if (activeDeviceTab.value === "all") {
        const activeCount = lists.filter((item) => isDeviceActivated(item)).length;
        tabCounts.value = {
            all: lists.length,
            active: activeCount,
            inactive: lists.length - activeCount,
        };
    }
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const res = await getDeviceList({
            page_no,
            page_size,
            auth_status: DEVICE_AUTH_TAB_PARAM[activeDeviceTab.value],
        });
        const lists: DeviceItem[] = res?.lists ?? [];
        updateTabCounts(res ?? {}, lists);
        pagingRef.value?.complete(lists);
    } catch {
        pagingRef.value?.complete([]);
    }
};

const queryActivationCodes = async () => {
    if (activationCodesLoading.value) return;
    activationCodesLoading.value = true;
    try {
        const res = await getDeviceAuthMyCodes();
        activationCodes.value = normalizeActivationCodeList(res?.lists ?? res ?? []);
        activationCodesLoaded.value = true;
    } catch {
        activationCodes.value = [];
        activationCodesLoaded.value = true;
    } finally {
        activationCodesLoading.value = false;
    }
};

const queryPlanList = async () => {
    try {
        const res = await getDeviceAuthPlanList();
        planItems.value = normalizePlanList(res?.lists ?? res ?? []);
    } catch {
        planItems.value = [];
    }
};

const refreshBillingData = () => {
    pagingRef.value?.reload();
    queryActivationCodes();
};

const handlePurchaseSuccess = () => {
    queryActivationCodes();
};

const handleBillingSuccess = () => {
    refreshBillingData();
};

const goDeviceDetail = (item: DeviceItem) => {
    if (!item.device_code) {
        uni.showToast({ title: "设备码不存在", icon: "none" });
        return;
    }
    toPage("/ai_modules/device/pages/detail/detail", { device_code: item.device_code });
};

const handleOpenRenewPopup = (item: DeviceItem) => {
    currentBillingDevice.value = item;
    showRenewPopup.value = true;
    queryPlanList();
    queryActivationCodes();
};

const handleOpenMyCodesPopup = () => {
    showMyCodesPopup.value = true;
    queryActivationCodes();
};

const handleOpenPurchasePopup = () => {
    showMyCodesPopup.value = false;
    showPurchasePopup.value = true;
    queryPlanList();
};

const handleOpenAddPhonePopup = () => {
    showAddPhonePopup.value = true;
};

const handleBeforeLoadAddPhoneQrcode = () => deviceStore.connectWebSocket();

const normalizeQrcodeUrl = (res: Record<string, any> = {}) =>
    res?.url || res?.qr_code || res?.qrcode || res?.image || res?.path || "";

const queryDeviceQrcode = async (device: DeviceItem) => {
    if (!device.device_code) {
        uni.showToast({ title: "设备码不存在", icon: "none" });
        return;
    }
    deviceQrcodeLoading.value = true;
    deviceQrcodeUrl.value = "";
    try {
        const res = await getDeviceQrcode({ device_code: device.device_code });
        deviceQrcodeUrl.value = normalizeQrcodeUrl(res);
        if (!deviceQrcodeUrl.value) {
            uni.showToast({ title: "二维码获取失败", icon: "none" });
        }
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "二维码获取失败";
        uni.showToast({ title: msg, icon: "none" });
    } finally {
        deviceQrcodeLoading.value = false;
    }
};

const handleOpenDeviceQrcode = (item: DeviceItem) => {
    currentQrcodeDevice.value = item;
    showDeviceQrcodePopup.value = true;
    queryDeviceQrcode(item);
    startDeviceQrcodePolling(item);
};

const handleRefreshDeviceQrcode = () => {
    queryDeviceQrcode(currentQrcodeDevice.value);
};

const stopDeviceQrcodePolling = () => {
    if (!deviceQrcodePollTimer) return;
    clearInterval(deviceQrcodePollTimer);
    deviceQrcodePollTimer = null;
    deviceQrcodePolling = false;
};

const syncDeviceUsedStatus = (latest: DeviceItem) => {
    const index = deviceList.value.findIndex((item) => item.device_code === latest.device_code);
    if (index >= 0) {
        deviceList.value.splice(index, 1, {
            ...deviceList.value[index],
            ...latest,
        });
    }
    currentQrcodeDevice.value = {
        ...currentQrcodeDevice.value,
        ...latest,
    };
};

const pollDeviceQrcodeUsedStatus = async (deviceCode: string) => {
    if (deviceQrcodePolling) return;
    deviceQrcodePolling = true;
    try {
        const res = await getDeviceDetail({ device_code: deviceCode });
        const latest = res?.detail ?? res;
        if (!latest?.device_code) return;
        syncDeviceUsedStatus(latest);
        if (isDeviceUsed(latest)) {
            stopDeviceQrcodePolling();
        }
    } finally {
        deviceQrcodePolling = false;
    }
};

const startDeviceQrcodePolling = (item: DeviceItem) => {
    stopDeviceQrcodePolling();
    if (!item.device_code || isDeviceUsed(item)) return;
    deviceQrcodePollTimer = setInterval(() => {
        pollDeviceQrcodeUsedStatus(item.device_code);
    }, 3000);
};

const submitDeviceUsed = async (item: DeviceItem, nextUsedStatus: DeviceUsedStatusEnum) => {
    uni.showLoading({
        title: "重置中...",
        mask: true,
    });
    changingUsedDeviceCode.value = item.device_code;
    try {
        await updateDeviceUsed({
            device_code: item.device_code,
            is_used: nextUsedStatus,
        });
        uni.showToast({
            title: "重置成功",
            icon: "none",
        });
        pagingRef.value?.reload();
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "操作失败，请重试";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        changingUsedDeviceCode.value = "";
        uni.hideLoading();
    }
};

const handleToggleDeviceUsed = async (item: DeviceItem) => {
    if (!item.device_code) {
        uni.showToast({ title: "设备码不存在", icon: "none" });
        return;
    }
    if (changingUsedDeviceCode.value) return;
    const nextUsedStatus = isDeviceUsed(item) ? DeviceUsedStatusEnum.RESET : DeviceUsedStatusEnum.USED;
    if (nextUsedStatus === DeviceUsedStatusEnum.USED) {
        handleOpenDeviceQrcode(item);
        return;
    }

    uni.showModal({
        title: "确认重置这台 AI 手机？",
        content:
            "重置后，这台 AI 手机会停止工作并退出当前认领状态，正在运行的任务可能中断。之后你可以使用任意 AI 手机重新扫码认领。",
        cancelText: "暂不重置",
        confirmText: "确认重置",
        confirmColor: "#FF2442",
        success: ({ confirm }) => {
            if (!confirm) return;
            submitDeviceUsed(item, nextUsedStatus);
        },
    });
};

const handleUnbindDevice = (item: DeviceItem) => {
    uni.showModal({
        title: "确认移除这台设备？",
        content: "移除后，该设备的激活和绑定信息会从当前小程序中移除。之后你可以使用该小程序下的任意账号重新绑定设备。",
        cancelText: "保留设备",
        confirmText: "确认移除",
        confirmColor: "#FF2442",
        success: ({ confirm }) => {
            if (!confirm) return;
            (async () => {
                uni.showLoading({ title: "解除中...", mask: true });
                try {
                    await unbindDevice({ device_code: item.device_code });
                    uni.showToast({ title: "解除成功", icon: "none", duration: 2000 });
                    refreshBillingData();
                } catch (error: unknown) {
                    const msg = typeof error === "string" ? error : "解绑失败，请重试";
                    uni.showToast({ title: msg, icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            })();
        },
    });
};

const handleDeviceBound = () => {
    pagingRef.value?.reload();
};

const toPage = (url?: string, params?: Record<string, any>) => {
    if (!url) {
        uni.showToast({ title: "敬请期待~" });
        return;
    }
    uni.$u.route({ url, params });
};

watch(showDeviceQrcodePopup, (visible) => {
    if (!visible) stopDeviceQrcodePolling();
});

onUnmounted(() => {
    stopDeviceQrcodePolling();
});
</script>

<style scoped lang="scss">
.purchase-card {
    background: linear-gradient(135deg, #2b6eff 0%, #1a50d9 100%);
}

.add-phone-fixed {
    position: fixed;
    left: 24rpx;
    right: 24rpx;
    bottom: calc(120rpx + env(safe-area-inset-bottom));
    z-index: 20;
}

.phone-card {
    transition: transform 0.15s ease;

    &:active {
        transform: scale(0.985);
    }
}
</style>
