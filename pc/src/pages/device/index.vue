<template>
    <div class="h-full p-4 flex flex-col">
        <div
            class="rounded-[20px] flex items-center gap-3 px-[30px]"
            style="
                background: linear-gradient(152deg, rgba(0, 101, 251, 0.88) -42.44%, rgba(255, 255, 255, 0) 12.19%)
                    rgb(255, 255, 255);
            ">
            <img src="@/assets/images/device.svg" class="w-11 mt-7" />
            <div>
                <div class="text-[#000000cc]">
                    {{ ToolEnumMap[ToolEnum.DEVICE] }}
                </div>
                <div class="text-[#00000080]">
                    一键绑定跨平台设备，激活智能流程引擎，全链路接管部门任务，让团队拥有数字化中枢管家。
                </div>
            </div>
        </div>
        <div class="grow min-h-0 bg-white rounded-[20px] mt-4 flex flex-col">
            <div class="h-[88px] px-5 flex items-center justify-between">
                <div class="flex items-center gap-x-2">
                    <span class="text-lg font-bold">设备总数量：{{ pager.count }}台</span>
                    <ElButton text @click="getLists()">
                        <Icon name="el-icon-Refresh"></Icon>
                        <span class="text-[#86909C]">刷新</span>
                    </ElButton>
                </div>
                <div>
                    <ElButton type="primary" class="!rounded-full !h-10" @click="handleAddDevice">
                        <Icon name="local-icon-add_circle" />
                        <span class="ml-2">添加设备</span>
                    </ElButton>
                </div>
            </div>
            <div class="grow min-h-0">
                <ElTable
                    v-loading="pager.loading"
                    :data="pager.lists"
                    height="100%"
                    stripe
                    :row-style="{ height: '60px' }"
                    :header-cell-style="{ height: '63px' }">
                    <ElTableColumn prop="device_model" label="设备型号" show-overflow-tooltip>
                        <template #default="{ row }">
                            <ElButton type="primary" link @click="handleAccountDetail(row)">{{
                                row.device_model
                            }}</ElButton>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="sdk_version" label="当前SDK版本" show-overflow-tooltip />
                    <ElTableColumn prop="device_code" label="设备码" show-overflow-tooltip />
                    <ElTableColumn label="设备状态">
                        <template #default="{ row }">
                            <ElTag v-if="row.status == 1" type="success" :disable-transitions="true">在线</ElTag>
                            <ElTag v-else-if="row.status == 2" type="primary" :disable-transitions="true">工作中</ElTag>
                            <ElTag v-else type="danger" :disable-transitions="true">离线</ElTag>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="create_time" label="绑定时间" width="180" show-overflow-tooltip />
                    <ElTableColumn prop="name" label="操作" width="100" fixed="right">
                        <template #default="{ row }">
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!w-[130px] !min-w-[130px] !p-[6px] !rounded-xl">
                                <template #reference>
                                    <ElButton link>
                                        <Icon name="el-icon-MoreFilled"></Icon>
                                    </ElButton>
                                </template>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleAccountDetail(row)">
                                        <Icon name="el-icon-User"></Icon>
                                        <span>账号详情</span>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleRefreshData(row, AppTypeEnum.XHS)">
                                        <span class="flex items-center justify-center">
                                            <Icon name="el-icon-Refresh"></Icon>
                                        </span>
                                        <span>更新小红书</span>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleRefreshData(row, AppTypeEnum.DOUYIN)">
                                        <span class="flex items-center justify-center">
                                            <Icon name="el-icon-Refresh"></Icon>
                                        </span>
                                        <span>更新抖音</span>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleRefreshData(row, AppTypeEnum.KUAISHOU)">
                                        <span class="flex items-center justify-center">
                                            <Icon name="el-icon-Refresh"></Icon>
                                        </span>
                                        <span>更新快手</span>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleDelete(row)">
                                        <Icon name="el-icon-Delete"></Icon>
                                        <span>删除</span>
                                    </div>
                                </div>
                            </ElPopover>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <div class="flex items-center justify-center h-full">
                            还没有设备，<ElButton type="primary" link @click="handleAddDevice">点击添加设备</ElButton>
                        </div>
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-end p-4">
                <pagination v-model="pager" @change="getLists"></pagination>
            </div>
        </div>
    </div>
    <device-add
        ref="addDeviceRef"
        v-if="showAddDevice"
        :bind-loading="addDeviceLoading"
        @close="showAddDevice = false"
        @confirm="getLists" />
    <device-progress
        v-if="showProgress"
        :progress-value="progressValue"
        :progress-error="progressError"
        :step="deviceStep"
        @close="showProgress = false"
        @retry="retryAddDevice" />
</template>

<script setup lang="ts">
import { getDeviceList, deleteDevice } from "@/api/device";
import { AppTypeEnum, DeviceCmdCodeEnum, DeviceCmdEnum, ToolEnumMap, ToolEnum } from "@/enums/appEnums";
import DeviceAdd from "./_components/device-add.vue";
import DeviceProgress from "./_components/device-progress.vue";

const router = useRouter();
const nuxtApp = useNuxtApp();
const { pager, getLists } = usePaging({
    fetchFun: getDeviceList,
});

const showProgress = ref(false);
const progressError = ref(false);
const deviceStep = ref("");

const { isConnected, onEvent, send } = useDeviceWs();

const { showAddDevice, addDeviceLoading, progressValue, refreshAccount, handleAddDeviceConfirm, handleRefreshAccount } =
    useAddDeviceAccount({
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
                    addDeviceId.value = "";
                    progressError.value = false;
                    currDevice.value = null;
                    showProgress.value = false;
                    getLists();
                    break;
                case DeviceCmdEnum.APP_EXEC:
                case DeviceCmdEnum.OPEN_APP:
                case DeviceCmdEnum.OPEN_PERSON_CENTER:
                case DeviceCmdEnum.GET_ACCOUNT_INFO:
                case DeviceCmdEnum.DATA_SEND:
                case DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE:
                    deviceStep.value = msg;
                    break;
            }
        },
        onError: (err) => {
            progressError.value = true;
            progressValue.value = 0;
            const { code, error, type } = err;
            if (code == DeviceCmdCodeEnum.DEVICE_OFFLINE) {
                getLists();
            }
        },
    });

const addDeviceId = ref("");
const currAppType = ref();

const retryAddDevice = () => {
    progressError.value = false;
    handleRefreshAccount(currDevice.value, currAppType.value);
};

const currDevice = ref(null);
const handleRefreshData = (row: any, appType: AppTypeEnum) => {
    currDevice.value = row.device_code;
    refreshAccount.value = row.accounts;
    showProgress.value = true;
    currAppType.value = appType;
    handleRefreshAccount(currDevice.value, appType);
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

const addDeviceRef = ref<InstanceType<typeof DeviceAdd>>();
const handleAddDevice = async () => {
    if (!isConnected.value) {
        feedback.msgError("连接失败，请检查网络连接");
        return;
    }
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

getLists();
</script>

<style scoped></style>
