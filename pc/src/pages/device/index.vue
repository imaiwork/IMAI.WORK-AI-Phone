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
            <ElButton
                type="primary"
                @click="handleAddDevice"
                class="!rounded-2xl !h-12 !font-black text-[14px] transition-all hover:translate-y-[-2px]">
                <Icon name="local-icon-add_circle" :size="18" />
                <span class="ml-2">添加新设备</span>
            </ElButton>
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
                                    class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center border border-br cursor-pointer"
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
                            <div class="text-[13px] font-medium text-[#475569]">{{ row.device_model }}</div>
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
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-pulse"></span> 在线
                                </div>
                                <div
                                    v-else-if="row.status == 2"
                                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#0065fb]/10 text-primary text-xs font-black">
                                    <Icon name="el-icon-Loading" /> 工作中
                                </div>
                                <div
                                    v-else
                                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#FEF2F2] text-[#EF4444] text-xs font-black">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#EF4444]"></span> 离线
                                </div>
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn prop="create_time" label="绑定周期" width="180">
                        <template #default="{ row }">
                            <span class="text-[13px] font-medium text-[#64748B]">{{ row.create_time }}</span>
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
                                            <Icon name="el-icon-User" /> 账号详情
                                        </div>
                                        <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                        <div class="table-action-item" @click="handleRefreshData(row, AppTypeEnum.XHS)">
                                            <Icon name="el-icon-Refresh" /> 更新小红书
                                        </div>
                                        <div
                                            class="table-action-item"
                                            @click="handleRefreshData(row, AppTypeEnum.DOUYIN)">
                                            <Icon name="el-icon-Refresh" /> 更新抖音
                                        </div>
                                        <div
                                            class="table-action-item"
                                            @click="handleRefreshData(row, AppTypeEnum.KUAISHOU)">
                                            <Icon name="el-icon-Refresh" /> 更新快手
                                        </div>
                                        <div
                                            class="table-action-item text-primary"
                                            @click="handleUpdateAccount(row.device_code)">
                                            <Icon name="el-icon-CircleCheck" /> 一键同步全部
                                        </div>
                                        <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                        <div
                                            class="table-action-item !text-red-500 hover:!bg-red-50"
                                            @click="handleDelete(row)">
                                            <Icon name="el-icon-Delete" /> 删除设备
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
</template>

<script setup lang="ts">
import { getDeviceList, deleteDevice, updateDevice } from "@/api/device";
import { AppTypeEnum, DeviceCmdCodeEnum, DeviceCmdEnum, ToolEnumMap, ToolEnum } from "@/enums/appEnums";
import DeviceAdd from "./_components/device-add.vue";
import DeviceProgress from "./_components/device-progress.vue";

const router = useRouter();
const nuxtApp = useNuxtApp();
const { pager, getLists } = usePaging({
    fetchFun: getDeviceList,
});

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
                        (p) => platformsToUpdate.value.includes(p.type) && (p.status === 0 || p.status === 1)
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
    renamePopupRef.value?.setFormData({ name: row.device_name, device_code: row.device_code });
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
        (p) => platformsToUpdate.value.includes(p.type) && p.status === 0
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
    // 默认跳转小红书
    const accountData = accounts.find((item: any) => item.type == AppTypeEnum.XHS);
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
    if (!handleCheckConnected()) return;
    showAddDevice.value = true;
    await nextTick();
    addDeviceRef.value?.open();
};

const handleDelete = (row: any) => {
    nuxtApp.$confirm({
        message: "确定删除该设备吗？",
        onConfirm: async () => {
            try {
                await deleteDevice({
                    id: row.id,
                    device_code: row.device_code,
                });
                feedback.msgSuccess("删除设备成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
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
