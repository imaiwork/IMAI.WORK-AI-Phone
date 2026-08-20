<template>
    <div class="h-full flex flex-col min-w-[1000px] pb-4 px-4">
        <div
            class="h-[120px] rounded-[20px] bg-white border border-br px-10 flex items-center justify-between relative overflow-hidden">
            <div class="flex items-center gap-6">
                <img src="@/assets/images/device.svg" class="w-20 h-20 mt-10" />
                <div>
                    <div class="text-[20px] font-[900] text-[#1E293B] mb-1">
                        {{ ToolEnumMap[ToolEnum.DEVICE] }}管理中枢
                    </div>
                    <div class="text-base font-medium text-[#64748B]">
                        一键绑定跨平台设备，激活智能流程引擎。在这里您可以监控设备实时状态并同步各个平台的账号信息。
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <ElButton
                    @click="handleOpenPurchase"
                    class="!rounded-2xl !h-12 !font-black text-[14px] transition-all hover:translate-y-[-2px]">
                    <Icon name="el-icon-Money" :size="18" />
                    <span class="ml-2">购买CDK</span>
                </ElButton>
                <ElButton
                    @click="handleOpenMyCodes"
                    class="!rounded-2xl !h-12 !font-black text-[14px] transition-all hover:translate-y-[-2px]">
                    <Icon name="el-icon-Tickets" :size="18" />
                    <span class="ml-2">我的CDK</span>
                </ElButton>
                <ElButton
                    type="primary"
                    @click="handleAddDevice"
                    class="!rounded-2xl !h-12 !font-black text-[14px] transition-all hover:translate-y-[-2px]">
                    <Icon name="local-icon-add_circle" :size="18" />
                    <span class="ml-2">添加新设备</span>
                </ElButton>
            </div>
        </div>

        <div class="grow min-h-0 bg-white rounded-[20px] mt-4 flex flex-col border border-br overflow-hidden">
            <div class="h-[80px] px-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-[#94A3B8] uppercase tracking-wider">Device Assets</span>
                        <span class="text-[16px] font-[900] text-[#1E293B]">当前设备：{{ pager.count }}</span>
                    </div>
                    <div class="w-[1px] h-8 bg-[#F1F5F9] mx-2"></div>
                    <button
                        @click="getLists()"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-[#F1F5F9] transition-all text-[#64748B] hover:text-primary">
                        <Icon name="el-icon-Refresh" :size="16" />
                        <span class="text-[13px] font-medium">同步状态</span>
                    </button>
                </div>
            </div>

            <div class="grow min-h-0">
                <ElTable
                    v-loading="pager.loading"
                    :data="pager.lists"
                    height="100%"
                    :row-style="{ cursor: 'pointer' }"
                    @row-click="handleAccountDetail">
                    <ElTableColumn label="设备识别" min-width="220">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center border border-br cursor-pointer shrink-0"
                                    @click.stop="handleEditName(row)">
                                    <Icon name="local-icon-edit" color="#64748B" />
                                </div>
                                <div class="flex flex-col group">
                                    <div class="flex items-center gap-1">
                                        <span
                                            class="text-[14px] font-[900] text-[#1E293B] cursor-pointer hover:text-primary"
                                            @click="handleAccountDetail(row)">
                                            {{ row.device_name }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] font-medium text-[#94A3B8]">{{ row.device_code }}</span>
                                </div>
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn prop="device_model" label="型号/系统" width="160">
                        <template #default="{ row }">
                            <div class="text-[13px] font-medium text-[#475569]">
                                {{ row.device_model }}
                            </div>
                            <div class="text-[11px] text-[#94A3B8]">SDK: {{ row.sdk_version }}</div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="AI自动/人工" width="160">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-2" @click.stop>
                                <ElSwitch
                                    v-model="row.auto_type"
                                    :active-value="1"
                                    :inactive-value="0"
                                    @change="handleChangeAutoType(row)" />
                                <span class="text-xs font-medium text-[#475569]">{{
                                    row.auto_type == 1 ? "AI自动" : "人工"
                                }}</span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="实时状态" width="120">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-1.5">
                                <div
                                    v-if="row.status == 1"
                                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#ECFDF5] text-[#10B981] text-xs font-black">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-pulse"></span>
                                    在线
                                </div>
                                <div
                                    v-else-if="row.status == 2"
                                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#0065fb]/10 text-primary text-xs font-black">
                                    <Icon name="el-icon-Loading" /> 工作中
                                </div>
                                <div
                                    v-else
                                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#FEF2F2] text-[#EF4444] text-xs font-black">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#EF4444]"></span>
                                    离线
                                </div>
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn prop="create_time" label="绑定周期" width="180">
                        <template #default="{ row }">
                            <span class="text-[13px] font-medium text-[#64748B]">{{ row.create_time }}</span>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="授权状态" width="260">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-2" @click.stop>
                                <template v-if="isDeviceActivated(row) || isDeviceExpired(row)">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-black"
                                        :class="authBadgeClass(row)">
                                        {{ authStatusText(row) }}
                                    </span>
                                    <ElButton
                                        v-if="!isDevicePermanent(row)"
                                        link
                                        type="primary"
                                        class="!font-bold"
                                        @click="handleRenew(row)">
                                        续费
                                    </ElButton>
                                </template>
                                <template v-else>
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-black bg-[#FEF2F2] text-[#EF4444]">
                                        未激活
                                    </span>
                                </template>
                                <ElButton
                                    v-if="!isDeviceActivated(row) && !isDeviceExpired(row)"
                                    type="primary"
                                    size="small"
                                    class="!rounded-lg !font-bold"
                                    @click="handleRenew(row)">
                                    立即激活
                                </ElButton>
                                <ElButton
                                    v-if="row.device_code"
                                    :type="isDeviceUsed(row) ? '' : 'primary'"
                                    size="small"
                                    class="!rounded-lg !font-bold"
                                    :loading="isDeviceUsedChanging(row)"
                                    @click="handleToggleDeviceUsed(row)">
                                    {{ getUsedButtonText(row) }}
                                </ElButton>
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="操作" width="60" fixed="right" align="right">
                        <template #default="{ row }">
                            <div class="flex items-center justify-end">
                                <ElPopover
                                    popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light"
                                    :show-arrow="false">
                                    <template #reference>
                                        <div
                                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-[#F1F5F9] cursor-pointer transition-all">
                                            <Icon name="el-icon-MoreFilled" color="#94A3B8" />
                                        </div>
                                    </template>
                                    <div class="p-1 space-y-1">
                                        <div class="table-action-item" @click="handleAccountDetail(row)">
                                            <Icon name="el-icon-User" />
                                            账号详情
                                        </div>
                                        <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                        <div class="table-action-item" @click="handleRefreshData(row, AppTypeEnum.XHS)">
                                            <Icon name="el-icon-Refresh" />
                                            更新小红书
                                        </div>
                                        <div
                                            class="table-action-item"
                                            @click="handleRefreshData(row, AppTypeEnum.DOUYIN)">
                                            <Icon name="el-icon-Refresh" />
                                            更新抖音
                                        </div>
                                        <div
                                            class="table-action-item"
                                            @click="handleRefreshData(row, AppTypeEnum.KUAISHOU)">
                                            <Icon name="el-icon-Refresh" />
                                            更新快手
                                        </div>
                                        <div
                                            class="table-action-item text-primary"
                                            @click="handleUpdateAccount(row.device_code)">
                                            <Icon name="el-icon-CircleCheck" />
                                            一键同步全部
                                        </div>
                                        <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                        <div
                                            class="table-action-item !text-red-500 hover:!bg-red-50"
                                            @click="handleDelete(row)">
                                            <Icon name="el-icon-Delete" />
                                            解除设备绑定
                                        </div>
                                    </div>
                                </ElPopover>
                            </div>
                        </template>
                    </ElTableColumn>
                </ElTable>
            </div>

            <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
                <span class="text-xs font-medium text-[#94A3B8]"
                    >显示 {{ pager.lists.length }} 条，共 {{ pager.count }} 条设备数据</span
                >
                <pagination v-model="pager" @change="getLists"></pagination>
            </div>
        </div>
    </div>
    <device-add
        ref="addDeviceRef"
        v-if="showAddDevice"
        :bind-loading="addDeviceLoading"
        @close="showAddDevice = false"
        @confirm="getLists"
        @update:account="handleUpdateAccount" />
    <device-progress
        v-if="showProgress"
        :progress-value="progressValue"
        :progress-error="progressError"
        :progress-error-msg="progressErrorMsg"
        :step="deviceStep"
        @close="handleCloseProgress"
        @retry="retryRefreshAccount" />
    <rename-pop
        v-if="showRenamePopup"
        ref="renamePopupRef"
        name-key="device_name"
        :fetch-fn="updateDevice"
        @close="showRenamePopup = false"
        @success="getLists"></rename-pop>

    <device-purchase-code v-if="showPurchase" @close="showPurchase = false" @success="getLists" />
    <device-codes v-if="showMyCodes" @close="showMyCodes = false" @purchase="handleOpenPurchase" />
    <device-renew v-if="showRenew" :device="currentDevice" @close="showRenew = false" @success="getLists" />
    <ElDialog
        v-model="showDeviceQrcode"
        width="420px"
        append-to-body
        :show-close="false"
        style="border-radius: 16px; overflow: hidden; padding: 0">
        <div class="px-7 pt-6 pb-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-[18px] font-[900] text-[#1E293B]">设备二维码</div>
                    <div class="text-[13px] text-[#94A3B8] mt-1 max-w-[300px] truncate">
                        {{ qrcodeDevice.device_name || qrcodeDevice.device_code || "-" }}
                    </div>
                </div>
                <div class="w-8 h-8" @click="showDeviceQrcode = false">
                    <close-btn />
                </div>
            </div>

            <div class="mt-5 rounded-2xl bg-[#F8FAFC] border border-br px-4 py-3 flex items-center gap-3">
                <Icon name="el-icon-Monitor" color="#64748B" :size="18" />
                <div class="min-w-0 flex-1">
                    <div class="text-[12px] text-[#94A3B8]">设备码</div>
                    <div class="text-[13px] font-mono font-bold text-[#1E293B] truncate">
                        {{ qrcodeDevice.device_code || "-" }}
                    </div>
                </div>
            </div>

            <div
                class="mt-5 w-[260px] h-[260px] mx-auto rounded-[18px] bg-[#F8FAFC] border border-br p-3 flex items-center justify-center"
                v-loading="deviceQrcodeLoading">
                <img
                    v-if="deviceQrcodeUrl"
                    :src="deviceQrcodeUrl"
                    class="w-full h-full rounded-xl object-contain"
                    alt="设备二维码" />
                <div v-else class="flex flex-col items-center text-[#94A3B8]">
                    <Icon name="el-icon-FullScreen" :size="56" />
                    <div class="text-[13px] mt-3">
                        {{ deviceQrcodeLoading ? "二维码加载中" : "二维码获取失败" }}
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center text-[13px] text-[#64748B]">使用微信扫码查看设备信息</div>
            <ElButton
                type="primary"
                class="w-full !h-11 !rounded-xl mt-5 !font-bold"
                @click="handleRefreshDeviceQrcode">
                刷新二维码
            </ElButton>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import { deleteDevice, updateDevice, getDeviceList, getDeviceQrcode, updateDeviceUsed } from "@/api/device";
import { AppTypeEnum, DeviceCmdCodeEnum, DeviceCmdEnum, ToolEnumMap, ToolEnum } from "@/enums/appEnums";
import {
    isDeviceActivated,
    isDeviceExpired,
    isDevicePermanent,
    DEVICE_AUTH_TAB_PARAM,
    type DeviceAuthTabKey,
} from "./_enums/deviceAuthEnums";
import DeviceAdd from "./_components/device-add.vue";
import DeviceProgress from "./_components/device-progress.vue";
import DevicePurchaseCode from "./_components/device-purchase-code.vue";
import DeviceCodes from "./_components/device-codes.vue";
import DeviceRenew from "./_components/device-renew.vue";

const router = useRouter();
const nuxtApp = useNuxtApp();

const queryParams = reactive({
    tab: "",
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getDeviceList,
    params: queryParams,
});

// 顶部授权状态筛选 tab
const activeTab = ref<DeviceAuthTabKey>("all");
const deviceTabs: { key: DeviceAuthTabKey; label: string }[] = [
    { key: "all", label: "全部" },
    { key: "active", label: "已激活" },
    { key: "inactive", label: "未激活" },
];
const handleChangeTab = (key: DeviceAuthTabKey) => {
    if (activeTab.value === key) return;
    activeTab.value = key;
    queryParams.tab = DEVICE_AUTH_TAB_PARAM[key];
    resetPage();
};

// 激活码相关弹窗
const showPurchase = ref(false);
const showMyCodes = ref(false);
const showRenew = ref(false);
const currentDevice = ref<Record<string, any>>({});
const showDeviceQrcode = ref(false);
const qrcodeDevice = ref<Record<string, any>>({});
const deviceQrcodeUrl = ref("");
const deviceQrcodeLoading = ref(false);
const changingUsedDeviceCode = ref("");
let deviceQrcodePollTimer: ReturnType<typeof setInterval> | null = null;
let deviceQrcodePolling = false;

enum DeviceUsedStatusEnum {
    RESET = 0,
    USED = 1,
}

const handleOpenPurchase = () => {
    showMyCodes.value = false;
    showPurchase.value = true;
};
const handleOpenMyCodes = () => {
    showMyCodes.value = true;
};
const handleRenew = (row: any) => {
    currentDevice.value = row;
    showRenew.value = true;
};

const normalizeQrcodeUrl = (res: Record<string, any> = {}) =>
    res?.url || res?.qr_code || res?.qrcode || res?.image || res?.path || "";

const queryDeviceQrcode = async (row: Record<string, any>) => {
    if (!row.device_code) {
        feedback.msgError("设备码不存在");
        return;
    }
    deviceQrcodeLoading.value = true;
    deviceQrcodeUrl.value = "";
    try {
        const res = await getDeviceQrcode({ device_code: row.device_code });
        deviceQrcodeUrl.value = normalizeQrcodeUrl(res);
        if (!deviceQrcodeUrl.value) {
            feedback.msgError("二维码获取失败");
        }
    } catch (error: any) {
        feedback.msgError(error || "二维码获取失败");
    } finally {
        deviceQrcodeLoading.value = false;
    }
};

const handleOpenDeviceQrcode = (row: Record<string, any>) => {
    qrcodeDevice.value = row;
    showDeviceQrcode.value = true;
    queryDeviceQrcode(row);
    startDeviceQrcodePolling(row);
};

const handleRefreshDeviceQrcode = () => {
    queryDeviceQrcode(qrcodeDevice.value);
};

const isDeviceUsed = (row: Record<string, any>) => Number(row.is_used) === DeviceUsedStatusEnum.USED;

const isDeviceUsedChanging = (row: Record<string, any>) =>
    !!row.device_code && changingUsedDeviceCode.value === row.device_code;

const getUsedButtonText = (row: Record<string, any>) => {
    if (isDeviceUsedChanging(row)) return isDeviceUsed(row) ? "重置中" : "使用中";
    return isDeviceUsed(row) ? "重置" : "使用";
};

const stopDeviceQrcodePolling = () => {
    if (!deviceQrcodePollTimer) return;
    clearInterval(deviceQrcodePollTimer);
    deviceQrcodePollTimer = null;
    deviceQrcodePolling = false;
};

const pollDeviceQrcodeUsedStatus = async (deviceCode: string) => {
    if (deviceQrcodePolling) return;
    deviceQrcodePolling = true;
    try {
        await getLists(undefined, false);
        const latest = pager.lists.find((item: any) => item.device_code === deviceCode);
        if (!latest) return;
        qrcodeDevice.value = latest;
        if (isDeviceUsed(latest)) {
            stopDeviceQrcodePolling();
        }
    } finally {
        deviceQrcodePolling = false;
    }
};

const startDeviceQrcodePolling = (row: Record<string, any>) => {
    stopDeviceQrcodePolling();
    if (!row.device_code || isDeviceUsed(row)) return;
    deviceQrcodePollTimer = setInterval(() => {
        pollDeviceQrcodeUsedStatus(row.device_code);
    }, 3000);
};

const submitDeviceUsed = async (row: Record<string, any>, nextUsedStatus: DeviceUsedStatusEnum) => {
    changingUsedDeviceCode.value = row.device_code;
    feedback.loading("重置中...");
    try {
        await updateDeviceUsed({
            device_code: row.device_code,
            is_used: nextUsedStatus,
        });
        feedback.msgSuccess("重置成功");
        getLists();
    } catch (error) {
        feedback.msgError(error || "操作失败，请重试");
    } finally {
        changingUsedDeviceCode.value = "";
        feedback.closeLoading();
    }
};

const handleToggleDeviceUsed = async (row: Record<string, any>) => {
    if (!row.device_code) {
        feedback.msgError("设备码不存在");
        return;
    }
    if (changingUsedDeviceCode.value) return;
    const nextUsedStatus = isDeviceUsed(row) ? DeviceUsedStatusEnum.RESET : DeviceUsedStatusEnum.USED;
    if (nextUsedStatus === DeviceUsedStatusEnum.USED) {
        handleOpenDeviceQrcode(row);
        return;
    }

    nuxtApp.$confirm({
        message: "您的Ai手机将会停止工作并退出登陆，随后您可使用任意Ai手机再次进行认领，是否确定登出",
        onConfirm: async () => {
            await submitDeviceUsed(row, nextUsedStatus);
        },
    });
};

const authStatusText = (row: any) => {
    if (isDeviceExpired(row)) return "已过期";
    if (isDevicePermanent(row)) return "永久有效";
    return getExpireRemainText(row.auth_expire_time);
};
const authBadgeClass = (row: any) => {
    if (isDeviceExpired(row)) return "bg-[#FEF2F2] text-[#EF4444]";
    if (isDevicePermanent(row)) return "bg-[#FFF7ED] text-[#FF8C00]";
    const remainMs = getExpireRemainMs(row.auth_expire_time);
    if (remainMs !== null && remainMs < 0) return "bg-[#FEF2F2] text-[#EF4444]";
    const days = remainMs === null ? null : Math.floor(remainMs / 86400000);
    if (days !== null && days <= 7) return "bg-[#FEF2F2] text-[#EF4444]";
    if (days !== null && days <= 30) return "bg-[#FFF7ED] text-[#FA8C16]";
    return "bg-[#ECFDF5] text-[#10B981]";
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

const sortedPlatformLogo = ref<any[]>([
    { name: "微信", type: AppTypeEnum.WECHAT, status: 0 },
    { name: "小红书", type: AppTypeEnum.XHS, status: 0 },
    { name: "抖音", type: AppTypeEnum.DOUYIN, status: 0 },
    { name: "快手", type: AppTypeEnum.KUAISHOU, status: 0 },
]);
const platformsToUpdate = ref<any[]>([]);
const addDeviceRef = ref<InstanceType<typeof DeviceAdd>>();
const renamePopupRef = shallowRef();
const showRenamePopup = ref(false);
const showProgress = ref(false);
const progressError = ref(false);
const progressErrorMsg = ref("");
const deviceStep = ref("");

const { isConnected, onEvent, send } = useDeviceWs();

const {
    showAddDevice,
    addDeviceLoading,
    progressValue,
    refreshAccount,
    eventAction,
    handleRefreshAccount,
    handleBatchUpdateAccount,
} = useAddDeviceAccount({
    send,
    onEvent,
    onSuccess: (res) => {
        const { msg, type } = res;
        switch (type) {
            case DeviceCmdEnum.ADD_DEVICE:
            case DeviceCmdEnum.DEVICE_ONLINE:
                getLists();
                break;
            case DeviceCmdEnum.GET_USER_INFO:
                if (eventAction.value === "batchUpdateAccount") {
                    const completedPlatform = sortedPlatformLogo.value.find((p) => p.status === 1);
                    if (completedPlatform) {
                        completedPlatform.status = 2;
                    }

                    const isFinished = !sortedPlatformLogo.value.some(
                        (p) => platformsToUpdate.value.includes(p.type) && (p.status === 0 || p.status === 1),
                    );

                    if (!isFinished) {
                        processNextAccount();
                    } else {
                        deviceId.value = "";
                        progressError.value = false;
                        currDevice.value = null;
                        showProgress.value = false;
                        getLists();
                    }
                } else {
                    progressError.value = false;
                    showProgress.value = false;
                    getLists();
                }
                break;
            case DeviceCmdEnum.APP_EXEC:
            case DeviceCmdEnum.OPEN_APP:
            case DeviceCmdEnum.OPEN_PERSON_CENTER:
            case DeviceCmdEnum.GET_ACCOUNT_INFO:
            case DeviceCmdEnum.DATA_SEND:
            case DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE:
                if (eventAction.value == EventAction.BatchUpdateAccount) {
                    const platformName = sortedPlatformLogo.value.find((p) => p.status == 1)?.name;
                    deviceStep.value = `${platformName} ${msg}`;
                } else {
                    deviceStep.value = `${
                        sortedPlatformLogo.value.find((p) => p.type == currAppType.value)?.name
                    } ${msg}`;
                }
                break;
        }
    },
    onError: (err) => {
        const { code, error, content, type } = err;
        if (content?.code == DeviceCmdCodeEnum.DEVICE_OFFLINE) {
            feedback.msgError(error);
            getLists();
        }
        if (
            eventAction.value === EventAction.UpdateAccount ||
            eventAction.value === EventAction.AddAccount ||
            eventAction.value === EventAction.AddDevice ||
            content?.code == DeviceCmdCodeEnum.DEVICE_OFFLINE
        ) {
            progressError.value = true;
            progressErrorMsg.value = error;
            progressValue.value = 0;
        }
        if (eventAction.value === EventAction.BatchUpdateAccount) {
            const platformToReset = sortedPlatformLogo.value.find((p) => p.status === 1);
            if (platformToReset) {
                deviceStep.value = error;
                feedback.msgError(error);
                platformToReset.status = 3;
                processNextAccount();
            }
        }
    },
});

const deviceId = ref("");
const currAppType = ref();

const handleEditName = async (row: any) => {
    showRenamePopup.value = true;
    await nextTick();
    renamePopupRef.value?.open();
    renamePopupRef.value?.setFormData({
        name: row.device_name,
        device_code: row.device_code,
    });
};

const handleCheckConnected = () => {
    if (!isConnected.value) {
        feedback.msgError("连接失败，请检查网络连接");
        return false;
    }
    return true;
};

const handleCloseProgress = () => {
    progressError.value = false;
    progressValue.value = 0;
    deviceStep.value = "";
    showProgress.value = false;
};

const retryRefreshAccount = () => {
    if (!handleCheckConnected()) return;
    progressError.value = false;
    if (eventAction.value == EventAction.BatchUpdateAccount) {
        processNextAccount();
    } else {
        handleRefreshAccount(currDevice.value, currAppType.value);
    }
};

const currDevice = ref(null);
const handleRefreshData = (row: any, appType: AppTypeEnum) => {
    if (!handleCheckConnected()) return;
    currDevice.value = row.device_code;
    refreshAccount.value = row.accounts;
    showProgress.value = true;
    currAppType.value = appType;
    handleRefreshAccount(currDevice.value, appType);
};

const handleUpdateAccount = (deviceCode: string) => {
    if (!handleCheckConnected()) return;
    deviceId.value = deviceCode;
    refreshAccount.value = pager.lists.find((item: any) => item.device_code == deviceCode)?.accounts || [];
    const forceRefetch = refreshAccount.value.length == 0;
    if (forceRefetch) {
        platformsToUpdate.value = sortedPlatformLogo.value.map((item) => item.type);
    } else {
        platformsToUpdate.value = sortedPlatformLogo.value.filter((item) => item.status == 0).map((item) => item.type);
    }

    // 重置状态
    sortedPlatformLogo.value.forEach((p) => {
        if (platformsToUpdate.value.includes(p.type)) {
            p.status = 0; // 待处理
        }
    });

    showProgress.value = true;
    processNextAccount();
};

const processNextAccount = () => {
    const platformToProcess = sortedPlatformLogo.value.find(
        (p) => platformsToUpdate.value.includes(p.type) && p.status === 0,
    );
    if (platformToProcess) {
        platformToProcess.status = 1; // 进行中
        sendGetAccountCmd(platformToProcess.type);
    }
};

const sendGetAccountCmd = (type: AppTypeEnum) => {
    handleBatchUpdateAccount({
        device_code: deviceId.value,
        type,
    });
};

const handleAccountDetail = (row: any) => {
    const { accounts, device_code, device_model, id } = row;
    // 默认跳转小红书（phoneList 可能不含 accounts，做空值兜底）
    const accountData = (accounts || []).find((item: any) => item.type == AppTypeEnum.XHS);
    if (accountData) {
        router.push({
            path: `/device/${id}`,
            query: {
                account: accountData.account,
                device_code,
                device_model,
            },
        });
    } else {
        handleRefreshData(row, AppTypeEnum.XHS);
    }
};

const handleAddDevice = async () => {
    showAddDevice.value = true;
    await nextTick();
    addDeviceRef.value?.open();
};

const handleDelete = (row: any) => {
    nuxtApp.$confirm({
        message: "您的激活信息将会从该小程序中移除，随后您可使用该小程序下的任意账号再次进行绑定，是否确认移除",
        onConfirm: async () => {
            try {
                await deleteDevice({
                    id: row.id,
                    device_code: row.device_code,
                });
                feedback.msgSuccess("解除设备绑定成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "解除设备绑定失败");
            }
        },
    });
};

const handleChangeAutoType = async (row: any) => {
    try {
        await updateDevice({
            device_code: row.device_code,
            auto_type: row.auto_type,
        });
        feedback.msgSuccess("更新成功");
        getLists();
    } catch (error) {
        feedback.msgError(error || "更新失败");
    }
};

watch(showDeviceQrcode, (visible) => {
    if (!visible) stopDeviceQrcodePolling();
});

onUnmounted(() => {
    stopDeviceQrcodePolling();
});

getLists();
</script>

<style scoped lang="scss">
.add-device-shadow {
    box-shadow: 0 8px 20px -6px rgba(var(--el-primary-color), 0.4);
}

.custom-save-btn {
    @apply bg-primary;
    box-shadow: 0 8px 16px -4px rgba(var(--el-primary-color), 0.3);
}
</style>
