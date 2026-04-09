<template>
    <view class="px-[26rpx] pt-4">
        <template v-if="loading">
            <view class="bg-white rounded-[24rpx] p-[32rpx] animate-pulse flex flex-col gap-3">
                <view class="h-[22rpx] w-[120rpx] bg-[#F0F0F0] rounded-full" />
                <view class="h-[34rpx] w-[200rpx] bg-[#F0F0F0] rounded-full" />
                <view class="h-[1rpx] bg-[#F7F7F7]" />
                <view class="h-[22rpx] w-[280rpx] bg-[#F0F0F0] rounded-full" />
                <view class="h-[22rpx] w-[220rpx] bg-[#F0F0F0] rounded-full" />
            </view>

            <view class="mt-[30rpx] flex flex-col gap-[20rpx]">
                <view class="h-[22rpx] w-[100rpx] bg-[#F0F0F0] rounded-full animate-pulse" />
                <view class="bg-white rounded-[24rpx] p-[32rpx] animate-pulse flex flex-col gap-4">
                    <view v-for="i in 3" :key="i" class="flex items-center justify-between">
                        <view class="flex items-center gap-x-[24rpx]">
                            <view class="w-[72rpx] h-[72rpx] bg-[#F0F0F0] rounded-[20rpx]" />
                            <view class="flex flex-col gap-2">
                                <view class="h-[28rpx] w-[120rpx] bg-[#F0F0F0] rounded-full" />
                                <view class="h-[20rpx] w-[160rpx] bg-[#F0F0F0] rounded-full" />
                            </view>
                        </view>
                        <view class="h-[40rpx] w-[80rpx] bg-[#F0F0F0] rounded-full" />
                    </view>
                </view>

                <view class="h-[22rpx] w-[140rpx] bg-[#F0F0F0] rounded-full animate-pulse mt-[10rpx]" />
                <view class="bg-white rounded-[24rpx] px-[32rpx] animate-pulse">
                    <view
                        v-for="i in 3"
                        :key="i"
                        class="py-3 flex items-center justify-between border-b border-[#F7F7F7] last:border-b-0">
                        <view class="flex items-center gap-x-[24rpx]">
                            <view class="w-[60rpx] h-[60rpx] bg-[#F0F0F0] rounded-full" />
                            <view class="flex flex-col gap-2">
                                <view class="h-[28rpx] w-[80rpx] bg-[#F0F0F0] rounded-full" />
                                <view class="h-[22rpx] w-[120rpx] bg-[#F0F0F0] rounded-full" />
                            </view>
                        </view>
                        <view class="h-[48rpx] w-[100rpx] bg-[#F0F0F0] rounded-full" />
                    </view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="bg-white rounded-[24rpx] p-[32rpx]">
                <view class="flex items-center justify-between">
                    <view>
                        <view class="text-[22rpx] text-[#000000]/30">设备名称</view>
                        <view class="mt-1 flex items-center gap-2">
                            <text class="text-[34rpx] font-medium">{{ detail.device_name }}</text>
                            <view @click="handleEditDevice">
                                <u-icon name="edit-pen" color="#CCCCCC" />
                            </view>
                        </view>
                    </view>
                </view>
                <view class="h-[1rpx] bg-[#F7F7F7] my-2" />
                <view>
                    <view class="text-xs text-[#000000]/50 flex items-center font-medium">
                        设备码：{{ detail.device_code }}
                        <view class="ml-2" @click="copy(detail.device_code)">
                            <image
                                src="@/ai_modules/device/static/icons/copy.svg"
                                mode="widthFix"
                                class="w-[24rpx] h-[24rpx]" />
                        </view>
                    </view>
                    <view class="text-xs text-[#000000]/50 mt-[10rpx] font-medium">
                        绑定时间：{{ detail.create_time }}
                    </view>
                </view>
            </view>

            <view class="mt-[30rpx]">
                <view class="text-[22rpx] font-medium">基本设置</view>
                <view class="bg-white rounded-[24rpx] p-[32rpx] mt-[20rpx]">
                    <view class="flex items-center justify-between gap-x-2">
                        <view class="flex items-center gap-x-[24rpx] shrink-0">
                            <view
                                class="w-[72rpx] h-[72rpx] bg-[#F6F6F6] rounded-[20rpx] flex items-center justify-center">
                                <image
                                    src="@/ai_modules/device/static/icons/user_edit.svg"
                                    class="w-[36rpx] h-[36rpx]" />
                            </view>
                            <view>
                                <view class="text-[28rpx] font-medium">IP人设</view>
                                <view class="text-xs text-[#000000]/30 mt-[6rpx]">24h任务素材设置</view>
                            </view>
                        </view>
                        <view class="flex items-center gap-x-[12rpx]" @click="handleSetPersona">
                            <view
                                v-if="detail.persona_info?.persona_name"
                                class="text-[#00C08E] text-xs bg-[#F2FCF9] font-medium rounded-[100rpx] px-[16rpx] py-[8rpx]">
                                <text class="line-clamp-1">{{ detail.persona_info.persona_name }}</text>
                            </view>
                            <view
                                v-else
                                class="text-[#FF2442] text-xs bg-[#FFF4F5] font-medium rounded-[100rpx] px-[16rpx] py-[8rpx]"
                                >去设置</view
                            >
                            <u-icon name="arrow-right" color="#B2B2B2" size="20" />
                        </view>
                    </view>

                    <view class="h-[1rpx] bg-[#F7F7F7] my-3" />

                    <view class="flex items-center justify-between">
                        <view class="flex items-center gap-x-[24rpx]">
                            <view
                                class="w-[72rpx] h-[72rpx] bg-[#F6F6F6] rounded-[20rpx] flex items-center justify-center">
                                <image src="@/ai_modules/device/static/icons/switch.svg" class="w-[36rpx] h-[36rpx]" />
                            </view>
                            <view>
                                <view class="text-[28rpx] font-medium">任务模式</view>
                                <view class="text-xs text-[#000000]/30 mt-[6rpx]">
                                    当前：{{ detail.auto_type === 0 ? "手动" : "24h自动" }}
                                </view>
                            </view>
                        </view>
                        <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx]">
                            <view class="grid grid-cols-2 gap-x-[12rpx] h-[60rpx] relative w-[248rpx]">
                                <view
                                    v-for="(item, index) in taskModeList"
                                    :key="item.value"
                                    class="rounded-[12rpx] font-medium flex items-center justify-center z-10 transition-colors duration-500 text-xs"
                                    :class="{ 'text-primary': taskModeIndex === index }"
                                    @click="handleTaskModeClick(index)">
                                    {{ item.label }}
                                </view>
                                <view
                                    class="tab-slider"
                                    :style="{ transform: `translateX(${taskModeIndex * 100}%)` }" />
                            </view>
                        </view>
                    </view>

                    <view class="h-[1rpx] bg-[#F7F7F7] my-3" />

                    <view class="flex items-center justify-between" @click="handleUnbindDevice">
                        <view class="flex items-center gap-x-[24rpx]">
                            <view
                                class="w-[72rpx] h-[72rpx] bg-[#F6F6F6] rounded-[20rpx] flex items-center justify-center">
                                <image src="@/ai_modules/device/static/icons/offline.svg" class="w-[36rpx] h-[36rpx]" />
                            </view>
                            <view>
                                <view class="text-[28rpx] font-medium">解除设备绑定</view>
                                <view class="text-xs text-[#000000]/30 mt-[6rpx]">解绑后数据将删除</view>
                            </view>
                        </view>
                        <u-icon name="arrow-right" color="#B2B2B2" size="20" />
                    </view>
                </view>
                <view class="text-[22rpx] font-medium mt-[30rpx]">平台账号设置</view>
                <view class="bg-white rounded-[24rpx] px-[32rpx] mt-[20rpx]">
                    <view
                        v-for="item in sortedPlatform"
                        :key="item.type"
                        class="py-3 border-[0] border-b border-solid border-[#F7F7F7] last:border-b-0 flex items-center justify-between"
                        @click="handleAccount(item)">
                        <view class="flex items-center gap-x-[24rpx]">
                            <image :src="item.activeIcon" class="w-[60rpx] h-[60rpx]" />
                            <view>
                                <view>{{ item.name }}</view>
                                <view class="text-[22rpx] font-medium mt-[6rpx]">
                                    <text v-if="item.status === AccountStatus.NOT_LOGIN" class="text-[#000000]/30"
                                        >未登录</text
                                    >
                                    <text v-else class="text-[#00C08E]">已登录：{{ item.nickname }}</text>
                                </view>
                            </view>
                        </view>
                        <view class="flex items-center gap-x-[12rpx]" @click.stop="handleUpdateAccount(item)">
                            <view
                                class="rounded-[100rpx] px-[16rpx] py-[8rpx] font-medium text-xs"
                                :class="
                                    item.status === AccountStatus.NOT_LOGIN
                                        ? 'bg-[#FFF4F5] text-[#FF2442]'
                                        : 'bg-primary text-white'
                                ">
                                {{ item.status === AccountStatus.NOT_LOGIN ? "去登录" : "更新" }}
                            </view>
                            <u-icon name="arrow-right" color="#B2B2B2" size="20" />
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

    <u-popup v-model="showUpdate" mode="center" border-radius="20" width="80%" @close="showUpdate = false">
        <view class="rounded-[20rpx] bg-white p-5">
            <view class="text-[30rpx] font-medium text-center">提示</view>
            <view class="text-xs text-[#00000080] mt-[32rpx] text-center">
                当前如果有任务执行中，该任务会中断并且不再执行，手机将等待下一时间段任务再开始执行，确认是否还要继续？
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-medium"
                    @click="showUpdate = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-medium text-white"
                    @click="handleAccountConfirm">
                    确定
                </view>
            </view>
        </view>
    </u-popup>

    <u-popup
        v-model="showUpdateProgress"
        mode="center"
        border-radius="20"
        width="80%"
        :mask-close-able="false"
        @close="showUpdateProgress = false">
        <view class="rounded-[20rpx] bg-white px-5 py-[78rpx]">
            <view class="flex flex-col gap-y-3 w-[70%] mx-auto">
                <view v-for="(item, index) in updateAccountSteps" :key="index" class="flex gap-x-[28rpx]">
                    <view class="flex-shrink-0 mt-[4rpx] relative">
                        <view class="w-[28rpx] h-[28rpx]">
                            <view
                                v-if="item.status === StepStatus.PENDING"
                                class="w-full h-full rounded-full border border-solid border-[#0000001a]" />
                            <view
                                v-else-if="item.status === StepStatus.RUNNING"
                                class="w-full h-full rounded-full border border-solid border-primary-light-8 flex items-center justify-center">
                                <view class="w-[12rpx] h-[12rpx] rounded-full bg-primary" />
                            </view>
                            <view
                                v-else-if="item.status === StepStatus.DONE"
                                class="w-full h-full rounded-full flex items-center justify-center border border-solid border-primary">
                                <u-icon name="checkmark" color="#0065FB" size="16" />
                            </view>
                            <view
                                v-else
                                class="w-full h-full rounded-full flex items-center justify-center border border-solid border-[#FF2442]">
                                <u-icon name="close" color="#FF2442" size="16" />
                            </view>
                        </view>
                        <view
                            v-if="index !== updateAccountSteps.length - 1"
                            class="absolute top-[60%] left-[14rpx] w-[2rpx] h-[60%]"
                            :class="item.status === StepStatus.DONE ? 'bg-primary' : 'bg-[#0000001a]'" />
                    </view>
                    <view class="h-[80rpx]">
                        <view class="font-medium" :class="{ 'text-[#0000004d]': item.status === StepStatus.PENDING }">
                            {{ item.title }}
                        </view>
                        <view class="mt-1">
                            <text v-if="item.status === StepStatus.RUNNING" class="text-primary font-medium text-xs"
                                >获取中...</text
                            >
                            <text
                                v-else-if="item.status === StepStatus.FAILED"
                                class="text-[#FF2442] font-medium text-xs"
                                >获取失败</text
                            >
                        </view>
                    </view>
                </view>
            </view>
            <view class="mt-2 flex flex-col gap-y-2">
                <u-button
                    v-if="isExecuteComplete"
                    type="primary"
                    :custom-style="{ height: '90rpx', width: '100%', fontWeight: 'bold', borderRadius: '20rpx' }"
                    @click="showUpdateProgress = false">
                    确认
                </u-button>
                <u-button
                    :custom-style="{ height: '90rpx', fontWeight: 'bold', borderRadius: '20rpx' }"
                    @click="showUpdateProgress = false">
                    取消
                </u-button>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { getDeviceDetail, unbindDevice, addDeviceAccount, updateDeviceAccount, updateDevice } from "@/api/device";
import { AppTypeEnum, DeviceCmdEnum, DeviceCmdCodeEnum } from "@/enums/appEnums";
import { DeviceEventAction } from "@/ai_modules/device/enums";
import { useCopy } from "@/hooks/useCopy";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
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
const { send, onEvent, close } = useDeviceWs();

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
const currentStep = ref<number>(0);
const currentPlatform = ref<AppTypeEnum>(AppTypeEnum.WECHAT);
const eventAction = ref<DeviceEventAction | null>(null);

const currentPlatformItem = computed(() => sortedPlatform.value.find((item) => item.type === currentPlatform.value));

const isExecuteComplete = computed<boolean>(() =>
    updateAccountSteps.value.every((item) => item.status === StepStatus.DONE)
);

const updateAccountSteps = ref<UpdateStep[]>([
    { title: "正在发送指令", status: StepStatus.PENDING, type: "send", errorCode: DeviceCmdCodeEnum.OPEN_APP_ERROR },
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
    { title: "已完成", status: StepStatus.PENDING, type: DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE },
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

const handleAccountConfirm = (): void => {
    showUpdate.value = false;
    if (currentPlatform.value !== AppTypeEnum.WECHAT) {
        showUpdateProgress.value = true;
    } else {
        uni.showLoading({ title: "更新中...", mask: true });
    }
    updateAccountSteps.value[0].status = StepStatus.RUNNING;
    send({
        type: DeviceCmdEnum.GET_USER_INFO,
        content: { deviceId: deviceCode.value },
        deviceId: deviceCode.value,
        appType: currentPlatform.value,
    });
};

onEvent("success", async (data: any) => {
    const { type, content, deviceId, appType } = data;

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

    if (type === DeviceCmdEnum.GET_USER_INFO) {
        const { account, account_no, extra, avatar, nickname } = content;
        const params = {
            account,
            account_no,
            avatar,
            nickname,
            device_code: deviceId,
            type: appType,
            extra: JSON.stringify(extra),
        };
        try {
            if (eventAction.value === DeviceEventAction.ADD_ACCOUNT) {
                await addDeviceAccount(params);
            } else if (eventAction.value === DeviceEventAction.UPDATE_ACCOUNT) {
                await updateDeviceAccount({ ...params, id: currentPlatformItem.value?.id });
            }
            eventAction.value = null;
            showUpdate.value = false;
            getDetail();
        } catch (error: unknown) {
            const msg = typeof error === "string" ? error : "账号更新失败";
            uni.showToast({ title: msg, icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    }
});

onEvent("error", (error: any) => {
    const { type, code } = error;
    uni.hideLoading();
    for (const item of updateAccountSteps.value) {
        if (item.type === type && code === item.errorCode) {
            item.status = StepStatus.FAILED;
            break;
        }
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
