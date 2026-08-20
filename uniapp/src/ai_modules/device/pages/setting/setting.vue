<template>
    <view class="px-[32rpx] pt-[24rpx]">
        <template v-if="loading">
            <view
                class="bg-white rounded-[32rpx] p-[32rpx] animate-pulse flex flex-col gap-3 shadow-sm border border-gray-50">
                <view class="h-[22rpx] w-[120rpx] bg-[#EEF2FF] rounded-full" />
                <view class="h-[34rpx] w-[200rpx] bg-[#EEF2FF] rounded-full" />
                <view class="h-[1rpx] bg-[#F4F6FB]" />
                <view class="h-[22rpx] w-[280rpx] bg-[#EEF2FF] rounded-full" />
                <view class="h-[22rpx] w-[220rpx] bg-[#EEF2FF] rounded-full" />
            </view>

            <view class="mt-[32rpx] flex flex-col gap-[20rpx]">
                <view class="h-[22rpx] w-[100rpx] bg-[#EEF2FF] rounded-full animate-pulse" />
                <view
                    class="bg-white rounded-[32rpx] p-[32rpx] animate-pulse flex flex-col gap-4 shadow-sm border border-gray-50">
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
                <view class="bg-white rounded-[32rpx] px-[32rpx] animate-pulse shadow-sm border border-gray-50">
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
            <view class="bg-white rounded-[32rpx] p-[32rpx] shadow-sm border border-solid border-[#f9f9f9]">
                <view class="flex items-center justify-between mb-[20rpx]">
                    <view>
                        <text class="text-[22rpx] text-[#676767] block mb-[6rpx]">设备名称</text>
                        <view class="flex items-center gap-x-[12rpx]">
                            <text class="text-[34rpx] font-bold text-[#212121]">{{ detail.device_name }}</text>
                            <view
                                class="w-[48rpx] h-[48rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70"
                                @click="handleEditDevice">
                                <u-icon name="edit-pen" color="#94A3B8" size="22" />
                            </view>
                        </view>
                    </view>
                </view>

                <view class="h-[1rpx] bg-[#F4F6FB] mb-[20rpx]" />

                <view class="flex items-center gap-x-[12rpx] mb-[8rpx]">
                    <text class="text-[22rpx] text-[#676767]">设备码：{{ detail.device_code }}</text>
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70"
                        @click="copy(detail.device_code)">
                        <image
                            src="@/ai_modules/device/static/icons/copy.svg"
                            mode="widthFix"
                            class="w-[24rpx] h-[24rpx]" />
                    </view>
                </view>
                <text class="text-[22rpx] text-[#676767]">绑定时间：{{ detail.create_time }}</text>
            </view>

            <view class="mt-[32rpx]">
                <view class="flex items-center gap-x-[10rpx] mb-[20rpx]">
                    <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full"></view>
                    <text class="text-[26rpx] font-bold text-[#424242]">基本设置</text>
                </view>

                <view class="bg-white rounded-[32rpx] px-[32rpx] shadow-sm border border-solid border-[#f9f9f9]">
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
                                <image src="@/ai_modules/device/static/icons/switch.svg" class="w-[36rpx] h-[36rpx]" />
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
                                    :style="{ transform: `translateX(${taskModeIndex * 100}%)` }" />
                            </view>
                        </view>
                    </view>

                    <view class="py-[28rpx] flex items-center justify-between" @click="handleUnbindDevice">
                        <view class="flex items-center gap-x-[20rpx]">
                            <view
                                class="w-[72rpx] h-[72rpx] bg-[#FEF2F2] rounded-[20rpx] flex items-center justify-center">
                                <image src="@/ai_modules/device/static/icons/offline.svg" class="w-[36rpx] h-[36rpx]" />
                            </view>
                            <view>
                                <text class="text-[28rpx] font-semibold text-[#EF4444] block">解除设备绑定</text>
                                <text class="text-[22rpx] text-[#676767] mt-[6rpx] block">解绑后数据将删除</text>
                            </view>
                        </view>
                        <u-icon name="arrow-right" color="#CBD5E1" size="20" />
                    </view>
                </view>
            </view>

            <view class="mt-[32rpx] mb-[48rpx]">
                <view class="flex items-center gap-x-[10rpx] mb-[20rpx]">
                    <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full"></view>
                    <text class="text-[26rpx] font-bold text-[#424242]">平台账号设置</text>
                </view>

                <view class="bg-white rounded-[32rpx] px-[32rpx] shadow-sm border border-solid border-[#f9f9f9]">
                    <view
                        v-for="item in sortedPlatform"
                        :key="item.type"
                        class="py-[24rpx] border-b border-[#F4F6FB] last:border-b-0 flex items-center justify-between"
                        @click="handleAccount(item)">
                        <view class="flex items-center gap-x-[20rpx]">
                            <image :src="item.activeIcon" class="w-[56rpx] h-[56rpx] rounded-full" />
                            <view>
                                <text class="text-[28rpx] font-semibold text-[#212121] block">{{ item.name }}</text>
                                <view class="mt-[6rpx]">
                                    <text
                                        v-if="item.status === AccountStatus.NOT_LOGIN"
                                        class="text-[22rpx] text-[#676767]"
                                        >未登录</text
                                    >
                                    <text v-else class="text-[22rpx] text-primary">已登录：{{ item.nickname }}</text>
                                </view>
                            </view>
                        </view>

                        <view class="flex items-center gap-x-[12rpx]" @click.stop="handleUpdateAccount(item)">
                            <view
                                class="rounded-full px-[20rpx] py-[8rpx] font-medium text-[22rpx]"
                                :class="
                                    item.status === AccountStatus.NOT_LOGIN
                                        ? 'bg-[#FEF2F2] text-[#EF4444]'
                                        : 'bg-[#EEF4FF] text-primary'
                                ">
                                {{ item.status === AccountStatus.NOT_LOGIN ? "去登录" : "更新" }}
                            </view>
                            <u-icon name="arrow-right" color="#CBD5E1" size="20" />
                        </view>
                    </view>
                </view>
            </view>
        </template>
    </view>

    <keywords-edit
        v-model="showEditDevicePopup"
        ref="keywordsEditRef"
        title="编辑设备"
        @confirm="handleEditDeviceConfirm"
        @close="showEditDevicePopup = false" />

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
import { getDeviceDetail, unbindDevice, fetchDeviceAccount, updateDevice } from "@/api/device";
import { AppTypeEnum, DeviceCmdEnum, DeviceCmdCodeEnum } from "@/enums/appEnums";
import { DeviceEventAction } from "@/ai_modules/device/enums";
import { useCopy } from "@/hooks/useCopy";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
import { applyAccountFetchError } from "@/ai_modules/device/hooks/apply-account-fetch-error";
import AccountUpdateProgress from "@/ai_modules/device/components/account-update-progress/account-update-progress.vue";
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

// ─── 类型定义 ─────────────────────────────────────────────────────

interface DeviceDetail {
    device_name: string;
    device_code: string;
    create_time: string;
    auto_type: number;
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

const { copy } = useCopy();
const { sortedPlatform, initializePlatform } = useDevice();
const { onEvent, close } = useDeviceWs();

// ─── 任务模式 ─────────────────────────────────────────────────────

const taskModeList = ref<TaskMode[]>([
    { label: "24h自动", value: 1 },
    { label: "手动", value: 0 },
]);
const taskModeIndex = ref<number>(0);

/** 根据 detail.auto_type 同步 Tab 选中状态 */
const syncTaskModeIndex = (): void => {
    const idx = taskModeList.value.findIndex((m) => m.value === detail.value.auto_type);
    taskModeIndex.value = idx >= 0 ? idx : 0;
};

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

onLoad((options: any) => {
    deviceCode.value = options?.device_code ?? "";
    init();
});

onShow(() => {
    if (!initialized.value) return;
    getDetail();
});

onUnload(() => {
    close();
});
</script>

<style lang="scss" scoped>
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[5rpx] left-0 transition-all duration-500;
}
</style>
