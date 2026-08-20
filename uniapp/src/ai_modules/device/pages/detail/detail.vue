<template>
    <view class="min-h-screen bg-[#F4F6FA] pb-[200rpx]">
        <view class="px-[32rpx] pt-[24rpx]">
            <template v-if="loading">
                <view class="bg-white rounded-[32rpx] p-[32rpx] animate-pulse flex flex-col gap-3 shadow-sm">
                    <view class="h-[22rpx] w-[120rpx] bg-[#EEF2FF] rounded-full" />
                    <view class="h-[34rpx] w-[200rpx] bg-[#EEF2FF] rounded-full" />
                    <view class="h-[1rpx] bg-[#F4F6FB]" />
                    <view class="h-[22rpx] w-[280rpx] bg-[#EEF2FF] rounded-full" />
                    <view class="h-[22rpx] w-[220rpx] bg-[#EEF2FF] rounded-full" />
                </view>

                <view class="mt-[32rpx] flex flex-col gap-[20rpx]">
                    <view class="h-[22rpx] w-[100rpx] bg-[#EEF2FF] rounded-full animate-pulse" />
                    <view class="bg-white rounded-[32rpx] p-[32rpx] animate-pulse flex flex-col gap-4 shadow-sm">
                        <view v-for="i in 3" :key="i" class="flex items-center justify-between">
                            <view class="flex items-center gap-x-[24rpx]">
                                <view class="w-[72rpx] h-[72rpx] bg-[#EEF2FF] rounded-[20rpx]" />
                                <view class="flex flex-col gap-2">
                                    <view class="h-[28rpx] w-[120rpx] bg-[#EEF2FF] rounded-full" />
                                    <view class="h-[20rpx] w-[160rpx] bg-[#EEF2FF] rounded-full" />
                                </view>
                            </view>
                            <view class="h-[40rpx] w-[80rpx] bg-[#EEF2FF] rounded-full" />
                        </view>
                    </view>

                    <view class="h-[22rpx] w-[140rpx] bg-[#EEF2FF] rounded-full animate-pulse mt-[10rpx]" />
                    <view class="bg-white rounded-[32rpx] px-[32rpx] animate-pulse shadow-sm">
                        <view
                            v-for="i in 3"
                            :key="i"
                            class="py-[24rpx] flex items-center justify-between border-b border-[#F4F6FB] last:border-b-0">
                            <view class="flex items-center gap-x-[24rpx]">
                                <view class="w-[60rpx] h-[60rpx] bg-[#EEF2FF] rounded-full" />
                                <view class="flex flex-col gap-2">
                                    <view class="h-[28rpx] w-[80rpx] bg-[#EEF2FF] rounded-full" />
                                    <view class="h-[22rpx] w-[120rpx] bg-[#EEF2FF] rounded-full" />
                                </view>
                            </view>
                            <view class="h-[48rpx] w-[100rpx] bg-[#EEF2FF] rounded-full" />
                        </view>
                    </view>
                </view>
            </template>

            <template v-else>
                <view class="info-card">
                    <view class="px-[32rpx] pt-[32rpx] pb-[28rpx] flex items-start gap-x-[24rpx]">
                        <view class="flex-1 min-w-0">
                            <view class="flex items-center gap-x-[12rpx]">
                                <text class="text-[40rpx] leading-tight font-bold text-[#1A1A1A] line-clamp-1">
                                    {{ detail.device_name || "-" }}
                                </text>
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full bg-[#F9FAFB] border border-[#E8ECF0] flex items-center justify-center shrink-0 active:opacity-70"
                                    @click="handleEditDevice">
                                    <u-icon name="edit-pen" color="#888888" size="22" />
                                </view>
                            </view>
                            <view class="mt-[16rpx]">
                                <text
                                    class="inline-flex text-[22rpx] font-bold px-[16rpx] py-[4rpx] rounded-[12rpx]"
                                    :class="getPlanBadgeClass(detail)">
                                    {{ planBadgeText }}
                                </text>
                            </view>
                            <text class="block text-[22rpx] text-[#BBBBBB] mt-[12rpx]">
                                绑定于 {{ detail.create_time || "-" }}
                            </text>
                        </view>
                        <view class="shrink-0 flex flex-col gap-y-[12rpx]">
                            <view
                                v-if="detail.device_code"
                                class="h-[56rpx] px-[22rpx] rounded-full border flex items-center justify-center active:opacity-70"
                                :class="[getUsedButtonClass(detail), isDeviceUsedChanging(detail) ? 'opacity-60' : '']"
                                @click="handleToggleDeviceUsed(detail)">
                                <text class="text-[22rpx] font-semibold">{{ getUsedButtonText(detail) }}</text>
                            </view>
                            <view
                                v-if="showRenewButton"
                                class="h-[56rpx] px-[22rpx] rounded-full border border-[#C7DEFF] bg-[#EBF2FF] flex items-center justify-center active:opacity-70"
                                @click="handleRenewDevice">
                                <text class="text-[22rpx] font-semibold text-primary">{{ renewButtonText }}</text>
                            </view>
                        </view>
                    </view>

                    <view class="mx-[32rpx] h-[1rpx] bg-[#F0F0F0]"></view>

                    <view class="px-[32rpx] py-[24rpx] flex items-center gap-x-[16rpx]">
                        <text class="text-[24rpx] text-[#888888] shrink-0 w-[96rpx]">设备码</text>
                        <text class="text-[24rpx] text-[#1A1A1A] font-mono flex-1 min-w-0 line-clamp-1 break-all">
                            {{ detail.device_code || "-" }}
                        </text>
                        <view
                            v-if="detail.device_code"
                            class="w-[56rpx] h-[56rpx] rounded-[16rpx] bg-[#EBF2FF] border border-[#C7DEFF] flex items-center justify-center shrink-0 active:opacity-70"
                            @click="copy(detail.device_code)">
                            <image
                                src="@/ai_modules/device/static/icons/copy.svg"
                                mode="widthFix"
                                class="w-[26rpx] h-[26rpx]" />
                        </view>
                    </view>
                </view>

                <view
                    v-if="showActivationPrompt"
                    class="mt-[24rpx] rounded-[32rpx] px-[32rpx] py-[30rpx] overflow-hidden relative"
                    style="
                        background: linear-gradient(135deg, #0065fb 0%, #2b7fff 100%);
                        box-shadow: 0 10rpx 28rpx rgba(0, 101, 251, 0.22);
                    "
                    @click="handleRenewDevice">
                    <view
                        class="absolute right-[-48rpx] top-[-68rpx] w-[240rpx] h-[240rpx] rounded-full"
                        style="background: rgba(255, 255, 255, 0.12)" />
                    <view class="relative z-[1] flex items-center gap-x-[24rpx]">
                        <view
                            class="w-[88rpx] h-[88rpx] rounded-[28rpx] bg-[#ffffff]/20 flex items-center justify-center shrink-0">
                            <image
                                src="@/ai_modules/device/static/icons/zap_white.svg"
                                mode="aspectFit"
                                class="w-[42rpx] h-[42rpx]" />
                        </view>
                        <view class="flex-1 min-w-0">
                            <text class="block text-[32rpx] font-extrabold text-white">{{
                                activationPromptTitle
                            }}</text>
                            <text class="block text-[23rpx] leading-[36rpx] text-[#ffffff]/80 mt-[8rpx]">
                                {{ activationPromptDesc }}
                            </text>
                        </view>
                        <view
                            class="h-[64rpx] px-[26rpx] rounded-full bg-white flex items-center justify-center shrink-0 active:opacity-80">
                            <text class="text-[24rpx] font-bold text-primary">{{ activationPromptButtonText }}</text>
                        </view>
                    </view>
                </view>

                <view class="mt-[32rpx]">
                    <text class="section-title">基本设置</text>

                    <view class="info-card">
                        <view class="px-[32rpx] shadow-sm border border-solid border-[#f9f9f9]">
                            <view
                                class="py-[28rpx] flex items-center justify-between border-b border-[#F4F6FB]"
                                @click="handleSetPersona">
                                <view class="flex items-center gap-x-[20rpx]">
                                    <view
                                        class="w-[72rpx] h-[72rpx] bg-[#F4F6FB] rounded-[20rpx] flex items-center justify-center">
                                        <image
                                            src="@/ai_modules/device/static/icons/user_edit.svg"
                                            class="w-[36rpx] h-[36rpx]" />
                                    </view>
                                    <view>
                                        <text class="text-[28rpx] font-semibold text-[#212121] block">IP人设</text>
                                        <text class="text-[22rpx] text-[#676767] mt-[6rpx] block">24h任务素材设置</text>
                                    </view>
                                </view>
                                <view class="flex items-center gap-x-[12rpx]">
                                    <view
                                        v-if="detail.persona_info?.persona_name"
                                        class="text-[#00C08E] text-xs bg-[#F2FCF9] font-medium rounded-[100rpx] px-[16rpx] py-[8rpx]">
                                        <text class="line-clamp-1">{{ detail.persona_info.persona_name }}</text>
                                    </view>
                                    <view v-else class="bg-[#FEF2F2] px-[16rpx] py-[8rpx] rounded-full">
                                        <text class="text-[22rpx] text-error font-medium">去设置</text>
                                    </view>
                                    <u-icon name="arrow-right" color="#CBD5E1" size="20" />
                                </view>
                            </view>

                            <view class="py-[28rpx] flex items-center justify-between border-b border-[#F4F6FB]">
                                <view class="flex items-center gap-x-[20rpx]">
                                    <view
                                        class="w-[72rpx] h-[72rpx] bg-[#F4F6FB] rounded-[20rpx] flex items-center justify-center">
                                        <image
                                            src="@/ai_modules/device/static/icons/switch.svg"
                                            class="w-[36rpx] h-[36rpx]" />
                                    </view>
                                    <view>
                                        <text class="text-[28rpx] font-semibold text-[#212121] block">任务模式</text>
                                        <text class="text-[22rpx] text-[#676767] mt-[6rpx] block">
                                            当前：{{ detail.auto_type === 0 ? "手动" : "24h自动" }}
                                        </text>
                                    </view>
                                </view>
                                <view class="bg-[#F4F6FB] rounded-[16rpx] px-[6rpx]">
                                    <view class="grid grid-cols-2 h-[60rpx] relative w-[248rpx]">
                                        <view
                                            v-for="(item, index) in taskModeList"
                                            :key="item.value"
                                            class="rounded-[16rpx] font-medium flex items-center justify-center z-10 text-[22rpx]"
                                            :class="taskModeIndex === index ? 'text-primary' : 'text-[#676767]'"
                                            @click="handleTaskModeClick(index)">
                                            {{ item.label }}
                                        </view>
                                        <view
                                            class="tab-slider"
                                            :style="{
                                                transform: `translateX(${taskModeIndex * 100}%)`,
                                            }" />
                                    </view>
                                </view>
                            </view>

                            <view class="py-[28rpx] flex items-center justify-between" @click="handleUnbindDevice">
                                <view class="flex items-center gap-x-[20rpx]">
                                    <view
                                        class="w-[72rpx] h-[72rpx] bg-[#FEF2F2] rounded-[20rpx] flex items-center justify-center">
                                        <image
                                            src="@/ai_modules/device/static/icons/offline.svg"
                                            class="w-[36rpx] h-[36rpx]" />
                                    </view>
                                    <view>
                                        <text class="text-[28rpx] font-semibold text-[#EF4444] block"
                                            >解除设备绑定</text
                                        >
                                        <text class="text-[22rpx] text-[#676767] mt-[6rpx] block"
                                            >解绑后数据将删除</text
                                        >
                                    </view>
                                </view>
                                <u-icon name="arrow-right" color="#CBD5E1" size="20" />
                            </view>
                        </view>
                    </view>
                </view>

                <view class="mt-[32rpx]">
                    <text class="section-title">平台账号设置</text>

                    <view class="info-card">
                        <view
                            v-for="item in sortedPlatform"
                            :key="item.type"
                            class="row-item"
                            @click="handleAccount(item)">
                            <view class="flex items-center gap-x-[20rpx] flex-1 min-w-0">
                                <image :src="item.activeIcon" class="platform-icon" />
                                <view class="flex-1 min-w-0">
                                    <text class="text-[30rpx] font-semibold text-[#1A1A1A] block">{{ item.name }}</text>
                                    <text
                                        v-if="item.status === AccountStatus.NOT_LOGIN"
                                        class="block text-[22rpx] text-[#888888] mt-[4rpx]">
                                        未登录
                                    </text>
                                    <text v-else class="block text-[22rpx] text-[#888888] mt-[4rpx] line-clamp-1">
                                        已登录：{{ getAccountDisplayName(item) }}
                                    </text>
                                </view>
                            </view>

                            <view class="flex items-center gap-x-[14rpx] shrink-0">
                                <view
                                    class="h-[56rpx] px-[22rpx] rounded-[14rpx] border border-solid border-[#C7DEFF] bg-[#EBF2FF] flex items-center justify-center active:opacity-70"
                                    @click.stop="handleUpdateAccount(item)">
                                    <text class="text-[24rpx] font-semibold text-primary">
                                        {{ item.status === AccountStatus.NOT_LOGIN ? "去登录" : "更新" }}
                                    </text>
                                </view>
                                <u-icon name="arrow-right" color="#CBD5E1" size="20" />
                            </view>
                        </view>
                    </view>
                </view>

                <view class="mt-[32rpx]">
                    <text class="section-title">更多</text>
                    <view class="info-card">
                        <view class="row-item active:bg-[#F7F9FF]" @click="handleOpenRunningLog">
                            <view
                                class="w-[80rpx] h-[80rpx] bg-[#EBF2FF] rounded-[24rpx] flex items-center justify-center shrink-0">
                                <image
                                    src="@/ai_modules/device/static/icons/statement.svg"
                                    mode="aspectFit"
                                    class="w-[40rpx] h-[40rpx]" />
                            </view>
                            <view class="flex-1 min-w-0">
                                <text class="text-[30rpx] font-semibold text-[#1A1A1A] block">运行日志</text>
                                <text class="text-[22rpx] text-[#888888] mt-[4rpx] block">查看设备今日执行记录</text>
                            </view>
                            <u-icon name="arrow-right" color="#BBBBBB" size="20" />
                        </view>
                    </view>
                </view>
            </template>
        </view>
    </view>

    <keywords-edit
        v-model="showEditDevicePopup"
        ref="keywordsEditRef"
        title="编辑设备"
        @confirm="handleEditDeviceConfirm"
        @close="showEditDevicePopup = false" />

    <renew-popup
        v-model="showRenewPopup"
        :device="detail"
        :plans="planItems"
        :activation-codes="activationCodes"
        @purchase-code="handleOpenPurchasePopup"
        @success="handleRenewSuccess" />

    <purchase-code-popup v-model="showPurchasePopup" :plans="planItems" @success="handlePurchaseSuccess" />

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

    <u-popup v-model="showUpdate" mode="center" border-radius="32" width="82%" @close="showUpdate = false">
        <view class="bg-white rounded-[32rpx] p-[40rpx]">
            <view class="flex items-center gap-x-[12rpx] mb-[16rpx]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full"></view>
                <text class="text-[30rpx] font-bold text-[#212121]">提示</text>
            </view>
            <text class="text-[24rpx] text-[#676767] block leading-relaxed mb-[40rpx]">
                当前如果有任务执行中，该任务会中断并且不再执行，手机将等待下一时间段任务再开始执行，确认是否还要继续？
            </text>
            <view class="flex items-center gap-[16rpx]">
                <view
                    class="flex-1 h-[88rpx] flex items-center justify-center rounded-full bg-[#F4F6FB] active:opacity-70"
                    @click="showUpdate = false">
                    <text class="text-[28rpx] font-semibold text-[#676767]">取消</text>
                </view>
                <view
                    class="flex-1 h-[88rpx] flex items-center justify-center rounded-full bg-primary shadow-sm active:opacity-90"
                    @click="handleAccountConfirm">
                    <text class="text-[28rpx] font-semibold text-white">确定</text>
                </view>
            </view>
        </view>
    </u-popup>

    <account-update-progress
        v-model="showUpdateProgress"
        :steps="updateAccountSteps"
        :error="progressError"
        :error-msg="progressErrorMsg"
        @close="handleAccountProgressClose"
        @retry="handleAccountRetry" />
</template>

<script setup lang="ts">
import {
    getDeviceDetail,
    getDeviceQrcode,
    unbindDevice,
    fetchDeviceAccount,
    updateDevice,
    updateDeviceUsed,
} from "@/api/device";
import { getDeviceAuthMyCodes, getDeviceAuthPlanList } from "@/api/device_auth";
import {
    normalizePlanList,
    normalizeActivationCodeList,
    isDeviceActivated,
    isDeviceExpired,
    isDevicePermanent as isPermanentPlan,
    type ActivationCodeItem,
    type PlanItem,
} from "@/ai_modules/device/components/billing-package/billing-plans";
import { AppTypeEnum, DeviceCmdEnum, DeviceCmdCodeEnum } from "@/enums/appEnums";
import { DeviceEventAction } from "@/ai_modules/device/enums";
import { useCopy } from "@/hooks/useCopy";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
import { applyAccountFetchError } from "@/ai_modules/device/hooks/apply-account-fetch-error";
import AccountUpdateProgress from "@/ai_modules/device/components/account-update-progress/account-update-progress.vue";
import RenewPopup from "@/ai_modules/device/pages/index/components/renew-popup.vue";
import PurchaseCodePopup from "@/ai_modules/device/pages/index/components/purchase-code-popup.vue";
import keywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";

// ─── 枚举 ─────────────────────────────────────────────────────────

/** 步骤状态枚举，替代魔法数字 0/1/2/3 */
const enum StepStatus {
    PENDING = 0,
    RUNNING = 1,
    DONE = 2,
    FAILED = 3,
}

/** 账号登录状态枚举 */
const enum AccountStatus {
    NOT_LOGIN = 0,
    LOGGED_IN = 1,
}

/** 设备使用状态 */
const enum DeviceUsedStatusEnum {
    RESET = 0,
    USED = 1,
}

// ─── 类型定义 ─────────────────────────────────────────────────────

interface DeviceDetail {
    device_name: string;
    device_code: string;
    create_time: string;
    auto_type: number;
    auth_type?: number; // 套餐类型码：0无 1永久卡 …7自定义
    auth_status?: number; // 授权状态：0未激活 1已激活 2已过期
    auth_start_time?: string; // 激活时间
    auth_expire_time?: string; // 过期时间（永久卡为 "永久"）
    auth_type_name?: string; // 套餐名称
    auth_code?: string; // 激活码
    remain_days?: number | string; // 兼容后端直接下发剩余天数
    is_permanent?: boolean | number;
    is_used?: number;
    persona_id?: number;
    persona_info?: { persona_name: string };
    accounts?: any[];
}

interface TaskMode {
    label: string;
    value: number;
}

interface UpdateStep {
    title: string;
    status: StepStatus;
    type: string;
    errorCode?: DeviceCmdCodeEnum;
}

// ─── 页面状态 ─────────────────────────────────────────────────────

const loading = ref<boolean>(true);
const taskSaving = ref<boolean>(false); // 防止任务模式重复提交
const initialized = ref<boolean>(false); // 标记首次初始化完成

const detail = ref<DeviceDetail>({} as DeviceDetail);
const deviceCode = ref<string>("");
const showRenewPopup = ref<boolean>(false);
const showPurchasePopup = ref<boolean>(false);
const planItems = ref<PlanItem[]>([]);
const activationCodes = ref<ActivationCodeItem[]>([]);
const showDeviceQrcodePopup = ref<boolean>(false);
const currentQrcodeDevice = ref<DeviceDetail>({} as DeviceDetail);
const deviceQrcodeUrl = ref<string>("");
const deviceQrcodeLoading = ref<boolean>(false);
const changingUsedDeviceCode = ref<string>("");
let deviceQrcodePollTimer: ReturnType<typeof setInterval> | null = null;
let deviceQrcodePolling = false;

const { copy } = useCopy();
const { sortedPlatform, initializePlatform } = useDevice();
const { onEvent, close } = useDeviceWs();

// ─── 任务模式 ─────────────────────────────────────────────────────

const taskModeList = ref<TaskMode[]>([
    { label: "24h自动", value: 1 },
    { label: "手动", value: 0 },
]);
const taskModeIndex = ref<number>(0);

const planBadgeText = computed(() => {
    if (isDeviceExpired(detail.value)) return "已过期";
    if (!isDeviceActivated(detail.value)) return "未激活";
    if (isPermanentPlan(detail.value)) return "永久卡";
    return getExpireRemainText(detail.value.auth_expire_time);
});

const showRenewButton = computed(() => !isPermanentPlan(detail.value));

const renewButtonText = computed(() => (isDeviceActivated(detail.value) ? "续费" : "立即激活"));

const showActivationPrompt = computed(() => !!detail.value.device_code && !isDeviceActivated(detail.value));

const activationPromptTitle = computed(() => (isDeviceExpired(detail.value) ? "设备授权已过期" : "设备尚未激活"));

const activationPromptDesc = computed(() =>
    isDeviceExpired(detail.value) ? "续费后即可恢复 AI 手机任务执行能力" : "激活后即可使用 AI 手机任务执行能力",
);

const activationPromptButtonText = computed(() => (isDeviceExpired(detail.value) ? "立即续费" : "立即激活"));

/** 根据 detail.auto_type 同步 Tab 选中状态 */
const syncTaskModeIndex = (): void => {
    const idx = taskModeList.value.findIndex((m) => m.value === detail.value.auto_type);
    taskModeIndex.value = idx >= 0 ? idx : 0;
};

const getPlanBadgeClass = (item: DeviceDetail) => {
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

const isDeviceUsed = (item: DeviceDetail) => Number(item.is_used) === DeviceUsedStatusEnum.USED;

const isDeviceUsedChanging = (item: DeviceDetail) =>
    !!item.device_code && changingUsedDeviceCode.value === item.device_code;

const getUsedButtonText = (item: DeviceDetail) => {
    if (isDeviceUsedChanging(item)) return isDeviceUsed(item) ? "重置中" : "使用中";
    return isDeviceUsed(item) ? "重置" : "使用";
};

const getUsedButtonClass = (item: DeviceDetail) =>
    isDeviceUsed(item) ? "border-[#E8ECF0] bg-[#F4F6FA] text-[#666666]" : "border-[#C7DEFF] bg-[#EBF2FF] text-primary";

// ─── 编辑设备名称 ─────────────────────────────────────────────────

const showEditDevicePopup = ref<boolean>(false);
const keywordsEditRef = shallowRef();

const handleEditDevice = async (): Promise<void> => {
    showEditDevicePopup.value = true;
    await nextTick();
    keywordsEditRef.value?.setFormData(detail.value.device_name);
};

const handleEditDeviceConfirm = async (name: string): Promise<void> => {
    uni.showLoading({ title: "修改中...", mask: true });
    try {
        await updateDevice({ device_code: deviceCode.value, device_name: name });
        detail.value.device_name = name;
        showEditDevicePopup.value = false;
        uni.showToast({ title: "修改成功", icon: "none", duration: 3000 });
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "修改失败，请重试";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

// ─── IP 人设 ──────────────────────────────────────────────────────

const handleSetPersona = (): void => {
    uni.$u.route({
        url: "/ai_modules/device/pages/setting_person/setting_person",
        type: "redirectTo",
        params: {
            device_code: deviceCode.value,
            person_id: detail.value.persona_id ?? "",
        },
    });
};

// ─── 任务模式切换 ─────────────────────────────────────────────────

const handleTaskModeClick = async (index: number): Promise<void> => {
    // 防重：已选中 或 正在保存 时忽略
    if (index === taskModeIndex.value || taskSaving.value) return;
    taskSaving.value = true;
    const taskMode = taskModeList.value[index];
    uni.showLoading({ title: "修改中...", mask: true });
    try {
        await updateDevice({ device_code: deviceCode.value, auto_type: taskMode.value });
        taskModeIndex.value = index;
        detail.value.auto_type = taskMode.value;
        uni.showToast({ title: "修改成功", icon: "none", duration: 3000 });
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "修改失败";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
        taskSaving.value = false;
    }
};

// ─── 解绑设备 ─────────────────────────────────────────────────────

const handleUnbindDevice = (): void => {
    uni.showModal({
        title: "解除设备绑定",
        content: "解除设备绑定后，设备将无法使用",
        confirmColor: "#FF2442",
        success: ({ confirm }) => {
            if (!confirm) return;
            (async () => {
                uni.showLoading({ title: "解除中...", mask: true });
                try {
                    await unbindDevice({ device_code: deviceCode.value });
                    uni.showToast({ title: "解除成功", icon: "none", duration: 3000 });
                    uni.$u.route({
                        url: "/ai_modules/device/pages/index/index",
                        type: "reLaunch",
                    });
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

// ─── 账号更新 ─────────────────────────────────────────────────────

const showUpdate = ref<boolean>(false);
const showUpdateProgress = ref<boolean>(false);
const progressError = ref(false);
const progressErrorMsg = ref("");
const currentStep = ref<number>(0);
const currentPlatform = ref<AppTypeEnum>(AppTypeEnum.WECHAT);
const eventAction = ref<DeviceEventAction | null>(null);
/** 已下发 fetch，等待 appCompleted 后刷新账号列表 */
const isAccountFetching = ref(false);
const isAccountRefreshing = ref(false);

const currentPlatformItem = computed(() => sortedPlatform.value.find((item) => item.type === currentPlatform.value));

const updateAccountSteps = ref<UpdateStep[]>([
    {
        title: "正在发送指令",
        status: StepStatus.PENDING,
        type: "send",
        errorCode: DeviceCmdCodeEnum.OPEN_APP_ERROR,
    },
    {
        title: "手机正在处理指令",
        status: StepStatus.PENDING,
        type: DeviceCmdEnum.APP_EXEC,
        errorCode: DeviceCmdCodeEnum.OPEN_APP_ERROR,
    },
    {
        title: "正在打开目标应用",
        status: StepStatus.PENDING,
        type: DeviceCmdEnum.OPEN_APP,
        errorCode: DeviceCmdCodeEnum.OPEN_APP_ERROR,
    },
    {
        title: "正在切换到个人中心",
        status: StepStatus.PENDING,
        type: DeviceCmdEnum.OPEN_PERSON_CENTER,
        errorCode: DeviceCmdCodeEnum.OPEN_PERSON_CENTER_ERROR,
    },
    {
        title: "正在获取账号信息",
        status: StepStatus.PENDING,
        type: DeviceCmdEnum.GET_ACCOUNT_INFO,
        errorCode: DeviceCmdCodeEnum.GET_ACCOUNT_INFO_ERROR,
    },
    {
        title: "正在等待数据返回",
        status: StepStatus.PENDING,
        type: DeviceCmdEnum.DATA_SEND,
        errorCode: DeviceCmdCodeEnum.DATA_SEND_ERROR,
    },
    {
        title: "已完成",
        status: StepStatus.PENDING,
        type: DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE,
        errorCode: DeviceCmdCodeEnum.GET_ACCOUNT_INFO_COMPLETE_ERROR,
    },
]);

const resetSteps = (): void => {
    updateAccountSteps.value.forEach((item) => {
        item.status = StepStatus.PENDING;
    });
    currentStep.value = 0;
};

const handleAccount = (item: any): void => {
    uni.$u.route({
        url: "/ai_modules/device/pages/platform_detail/platform_detail",
        params: {
            device_code: deviceCode.value,
            app_type: item.type,
        },
    });
};

const getAccountDisplayName = (item: any) => item.nickname || item.account || item.account_no || "-";

const queryPlanList = async (): Promise<void> => {
    try {
        const res = await getDeviceAuthPlanList();
        planItems.value = normalizePlanList(res?.lists ?? res ?? []);
    } catch {
        planItems.value = [];
    }
};

const queryActivationCodes = async (): Promise<void> => {
    try {
        const res = await getDeviceAuthMyCodes();
        activationCodes.value = normalizeActivationCodeList(res?.lists ?? res ?? []);
    } catch {
        activationCodes.value = [];
    }
};

const handleRenewDevice = (): void => {
    showRenewPopup.value = true;
    queryPlanList();
    queryActivationCodes();
};

const handleRenewSuccess = (): void => {
    getDetail();
    queryActivationCodes();
};

const handleOpenPurchasePopup = (): void => {
    showPurchasePopup.value = true;
    queryPlanList();
};

const handlePurchaseSuccess = (): void => {
    queryActivationCodes();
};

const normalizeQrcodeUrl = (res: Record<string, any> = {}) =>
    res?.url || res?.qr_code || res?.qrcode || res?.image || res?.path || "";

const queryDeviceQrcode = async (device: DeviceDetail): Promise<void> => {
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
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        deviceQrcodeLoading.value = false;
    }
};

const syncDeviceUsedStatus = (latest: DeviceDetail): void => {
    detail.value = {
        ...detail.value,
        ...latest,
    };
    currentQrcodeDevice.value = {
        ...currentQrcodeDevice.value,
        ...latest,
    };
};

const stopDeviceQrcodePolling = (): void => {
    if (!deviceQrcodePollTimer) return;
    clearInterval(deviceQrcodePollTimer);
    deviceQrcodePollTimer = null;
    deviceQrcodePolling = false;
};

const pollDeviceQrcodeUsedStatus = async (code: string): Promise<void> => {
    if (deviceQrcodePolling) return;
    deviceQrcodePolling = true;
    try {
        const res = await getDeviceDetail({ device_code: code });
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

const startDeviceQrcodePolling = (device: DeviceDetail): void => {
    stopDeviceQrcodePolling();
    if (!device.device_code || isDeviceUsed(device)) return;
    const code = device.device_code;
    deviceQrcodePollTimer = setInterval(() => {
        pollDeviceQrcodeUsedStatus(code);
    }, 3000);
};

const handleOpenDeviceQrcode = (item: DeviceDetail): void => {
    currentQrcodeDevice.value = item;
    showDeviceQrcodePopup.value = true;
    queryDeviceQrcode(item);
    startDeviceQrcodePolling(item);
};

const handleRefreshDeviceQrcode = (): void => {
    queryDeviceQrcode(currentQrcodeDevice.value);
};

const submitDeviceUsed = async (item: DeviceDetail, nextUsedStatus: DeviceUsedStatusEnum): Promise<void> => {
    uni.showLoading({
        title: "重置中...",
        mask: true,
    });
    const code = item.device_code || "";
    changingUsedDeviceCode.value = code;
    try {
        await updateDeviceUsed({
            device_code: code,
            is_used: nextUsedStatus,
        });
        uni.showToast({
            title: "重置成功",
            icon: "none",
        });
        await getDetail();
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "操作失败，请重试";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        changingUsedDeviceCode.value = "";
        uni.hideLoading();
    }
};

const handleToggleDeviceUsed = (item: DeviceDetail): void => {
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

const handleOpenRunningLog = (): void => {
    uni.$u.route({
        url: "/ai_modules/device/pages/running_log/running_log",
        params: {
            device_code: deviceCode.value,
        },
    });
};

const handleUpdateAccount = (item: any): void => {
    const { type, status } = item;
    const isAdd = status === AccountStatus.NOT_LOGIN;
    currentPlatform.value = type;
    eventAction.value = isAdd ? DeviceEventAction.ADD_ACCOUNT : DeviceEventAction.UPDATE_ACCOUNT;
    resetSteps();
    if (isAdd) {
        handleAccountConfirm();
    } else {
        showUpdate.value = true;
    }
};

/** appCompleted 后轮询详情，等待服务端落库 */
const ACCOUNT_FETCH_REFRESH_DELAY = 800;
const ACCOUNT_FETCH_REFRESH_MAX_RETRY = 5;

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

const handleAccountProgressClose = (): void => {
    progressError.value = false;
    progressErrorMsg.value = "";
    isAccountFetching.value = false;
    isAccountRefreshing.value = false;
    showUpdateProgress.value = false;
};

const handleAccountRetry = (): void => {
    progressError.value = false;
    progressErrorMsg.value = "";
    resetSteps();
    handleAccountConfirm();
};

const handleAccountConfirm = async (): Promise<void> => {
    showUpdate.value = false;
    progressError.value = false;
    progressErrorMsg.value = "";
    if (currentPlatform.value !== AppTypeEnum.WECHAT || showUpdateProgress.value) {
        showUpdateProgress.value = true;
    } else {
        uni.showLoading({ title: "更新中...", mask: true });
    }
    updateAccountSteps.value[0].status = StepStatus.RUNNING;
    try {
        await fetchDeviceAccount({
            device_code: deviceCode.value,
            type: currentPlatform.value,
        });
        isAccountFetching.value = true;
    } catch (error: unknown) {
        isAccountFetching.value = false;
        showUpdateProgress.value = false;
        uni.hideLoading();
        const msg = typeof error === "string" ? error : "下发获取账号指令失败";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    }
};

const finishAccountFetch = async () => {
    if (isAccountRefreshing.value) return;
    isAccountRefreshing.value = true;
    const expectNewLogin = eventAction.value === DeviceEventAction.ADD_ACCOUNT;
    try {
        for (let i = 0; i < ACCOUNT_FETCH_REFRESH_MAX_RETRY; i++) {
            await sleep(ACCOUNT_FETCH_REFRESH_DELAY);
            await getDetail();
            const item = sortedPlatform.value.find((p) => p.type === currentPlatform.value);
            const loggedIn = item && item.status !== AccountStatus.NOT_LOGIN;
            if (!expectNewLogin || loggedIn) break;
        }
    } finally {
        isAccountFetching.value = false;
        isAccountRefreshing.value = false;
        eventAction.value = null;
        showUpdate.value = false;
        uni.hideLoading();
    }
};

onEvent("success", async (data: any) => {
    const { type, appType } = data;

    if (currentPlatform.value !== AppTypeEnum.WECHAT) {
        const isStep = updateAccountSteps.value.find((item) => item.type === type);
        if (isStep) {
            for (let index = 0; index < updateAccountSteps.value.length; index++) {
                const item = updateAccountSteps.value[index];
                if (type === DeviceCmdEnum.APP_EXEC) {
                    updateAccountSteps.value[0].status = StepStatus.DONE;
                }
                if (item.type === type) {
                    currentStep.value = index;
                    item.status = StepStatus.RUNNING;
                    if (type === DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE) {
                        updateAccountSteps.value[updateAccountSteps.value.length - 1].status = StepStatus.DONE;
                    }
                    break;
                } else {
                    item.status = currentStep.value >= index ? StepStatus.DONE : StepStatus.PENDING;
                }
            }
        }
    }

    // 以 appCompleted 为准刷新账号展示（可匹配当前拉号平台）
    if (
        type === DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE &&
        isAccountFetching.value &&
        (appType == null || appType === currentPlatform.value)
    ) {
        await finishAccountFetch();
    }
});

onEvent("error", (error: any) => {
    const { type } = error;
    uni.hideLoading();
    const msg = applyAccountFetchError(updateAccountSteps.value, error, StepStatus.FAILED);
    const isCurrentPlatform = error.appType == null || error.appType === currentPlatform.value;
    if ((isAccountFetching.value || showUpdateProgress.value) && isCurrentPlatform) {
        isAccountFetching.value = false;
        isAccountRefreshing.value = false;
        progressError.value = true;
        progressErrorMsg.value = msg;
        showUpdateProgress.value = true;
        return;
    }
    if (type === DeviceCmdEnum.GET_USER_INFO) {
        uni.showToast({ title: error.error, icon: "none", duration: 3000 });
    }
});

// ─── 数据获取 ─────────────────────────────────────────────────────

const getDetail = async (): Promise<void> => {
    const res = await getDeviceDetail({ device_code: deviceCode.value });
    detail.value = res;
    initializePlatform(res.accounts);
    syncTaskModeIndex();
};

const init = async (): Promise<void> => {
    loading.value = true;
    try {
        await getDetail();
    } catch {
        uni.showToast({ title: "数据加载失败，请返回重试", icon: "none", duration: 3000 });
    } finally {
        loading.value = false;
        initialized.value = true;
    }
};

// ─── 生命周期 ─────────────────────────────────────────────────────

onLoad(async (options: any) => {
    deviceCode.value = options?.device_code ?? "";
    await init();
    if (options?.activate == 1 && !isDeviceActivated(detail.value)) {
        await nextTick();
        handleRenewDevice();
    }
});

onShow(() => {
    if (!initialized.value) return;
    getDetail();
});

watch(showDeviceQrcodePopup, (visible) => {
    if (!visible) stopDeviceQrcodePolling();
});

onUnload(() => {
    stopDeviceQrcodePolling();
    close();
});
</script>

<style lang="scss" scoped>
.info-card {
    @apply bg-white rounded-[32rpx] overflow-hidden;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}

.section-title {
    @apply block text-[24rpx] font-bold text-[#888888] px-[8rpx] mb-[12rpx];
}

.row-item {
    @apply min-h-[120rpx] bg-white flex items-center gap-x-[24rpx] px-[32rpx] py-[28rpx] border-b border-[#F0F0F0];

    &:last-child {
        @apply border-b-0;
    }
}

.platform-icon {
    @apply w-[80rpx] h-[80rpx] rounded-full shrink-0;
}

.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[5rpx] left-0 transition-all duration-500;
}
</style>
