<template>
    <div class="h-full flex flex-col p-4">
        <ElCard class="!border-none !rounded-xl" shadow="never">
            <ElBreadcrumb>
                <ElBreadcrumbItem>
                    <span class="cursor-pointer text-[#8A8C99] hover:text-primary" @click="$router.back()">
                        AI终端
                    </span>
                </ElBreadcrumbItem>
                <ElBreadcrumbItem>账号设置</ElBreadcrumbItem>
            </ElBreadcrumb>
        </ElCard>
        <div class="grow min-h-0 bg-white rounded-lg mt-4 flex flex-col overflow-hidden" ref="containerRef">
            <div class="h-[71px] px-5 flex items-center justify-between border-b border-b-[#E8E8E8]">
                <div>
                    <div class="flex items-center gap-2">
                        <ElPopover
                            :show-arrow="false"
                            popper-class="!p-0 !min-w-[200px]  !border-none !rounded-lg !shadow-[0_0_10px_1px_rgba(0,0,0,0.1)]">
                            <template #reference>
                                <div class="min-w-[170px] flex items-center gap-2 cursor-pointer">
                                    <Icon name="el-icon-ArrowDownBold"></Icon>
                                    <span class="text-[#36393F] text-lg font-bold">{{
                                        getCurrentDevice?.device_model
                                    }}</span>
                                </div>
                            </template>
                            <div class="flex flex-col">
                                <div class="flex flex-col gap-y-1 p-2 max-h-[300px] overflow-y-auto">
                                    <div
                                        v-for="(item, index) in deviceLists"
                                        class="break-all hover:bg-primary-light-9 rounded-lg p-2 cursor-pointer"
                                        :class="{
                                            'bg-primary-light-9 text-primary':
                                                queryParams.device_code === item.device_code,
                                        }"
                                        :key="index"
                                        @click="changeDevice(item.device_code)">
                                        {{ item.device_model }}
                                    </div>
                                </div>
                                <ElDivider class="!m-0" />
                                <div class="flex justify-center py-3">
                                    <ElButton type="primary" link @click="openAddDevice">
                                        <Icon name="el-icon-Plus"></Icon>
                                        <span>添加设备</span>
                                    </ElButton>
                                </div>
                            </div>
                        </ElPopover>
                        <span class="bg-[#F2F3F8] text-xs py-1 px-2 rounded-lg" v-if="queryParams.device_code"
                            >设备ID：{{ queryParams.device_code }}</span
                        >
                    </div>
                </div>
            </div>
            <div class="grow min-h-0 flex w-full">
                <div class="w-[178px] bg-white border-r border-r-[#E8E8E8] flex-shrink-0">
                    <div class="flex flex-col gap-y-4 p-4">
                        <div
                            v-for="(item, index) in getSocialPlatformList"
                            :key="index"
                            class="flex items-center gap-x-3 px-4 hover:bg-primary-light-9 py-1.5 cursor-pointer"
                            :class="{
                                'bg-primary-light-9': currentSocialPlatform === item.type,
                            }"
                            @click="handleChangeSocialPlatform(item.type)">
                            <img :src="item.icon" class="w-6 h-6" />
                            <div>{{ item.name }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex-1 overflow-auto flex flex-col mt-4">
                    <div class="flex justify-between items-center px-4">
                        <div>
                            <ElButton link @click="refreshData" :loading="isRefreshData">
                                <Icon name="el-icon-Refresh"></Icon>
                                <span>刷新数据</span>
                            </ElButton>
                        </div>
                        <ElInput
                            v-model="queryParams.account"
                            placeholder="搜索账号信息"
                            class="!w-[200px]"
                            prefix-icon="el-icon-Search"
                            clearable
                            @clear="resetParams()"
                            @keyup.enter="getLists()" />
                    </div>
                    <div class="grow min-h-0 mt-4 w-full">
                        <ElTable
                            ref="tableRef"
                            v-loading="pager.loading"
                            :data="pager.lists"
                            height="100%"
                            stripe
                            :row-style="{ height: '60px', cursor: 'pointer' }"
                            :header-cell-style="{ height: '63px' }"
                            @row-click="handleRowClick">
                            <ElTableColumn label="头像" width="80">
                                <template #default="{ row }">
                                    <ElAvatar :src="row.avatar"></ElAvatar>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="账号/昵称" prop="account" min-width="180">
                                <template #default="{ row }">
                                    <div class="flex items-center justify-center gap-x-2">
                                        <div>
                                            <div>{{ row.account }}</div>
                                            <div>({{ row.nickname }})</div>
                                        </div>
                                        <ElTag v-if="row.status == 1" type="success">当前账号</ElTag>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="粉丝数量" prop="fans" min-width="100">
                                <template #default="{ row }">
                                    <div>{{ row.fans || "-" }}</div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="点赞数量" prop="thumbup_collect" min-width="100">
                                <template #default="{ row }">
                                    <div>{{ row.thumbup_collect || "-" }}</div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="关注数量" prop="followers" min-width="100">
                                <template #default="{ row }">
                                    <div>{{ row.followers || "-" }}</div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="更新时间" prop="create_time" width="180" />
                            <ElTableColumn label="操作" width="120px" fixed="right">
                                <template #default="{ row }">
                                    <div class="flex flex-col gap-2">
                                        <div
                                            v-if="row.status == 1"
                                            class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                            @click.stop="handleRefreshData(row)">
                                            <span class="flex items-center justify-center">
                                                <Icon name="el-icon-Refresh"></Icon>
                                            </span>
                                            <span>刷新数据</span>
                                        </div>
                                        <div
                                            v-if="row.account_type == 1 && row.status == 1"
                                            class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                            @click.stop="handleGetBusinessCard(row)">
                                            <Icon name="el-icon-Postcard"></Icon>
                                            <span>名片获取</span>
                                        </div>
                                        <div
                                            class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                            @click.stop="handleDelete(row)">
                                            <Icon name="el-icon-Delete"></Icon>
                                            <span>账号移除</span>
                                        </div>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <template #empty>
                                <div class="flex items-center justify-center h-full" v-if="queryParams.device_code">
                                    还没有账号，<ElButton type="primary" link @click="handleAddAccount"
                                        >点击获取设备账号信息</ElButton
                                    >
                                </div>
                                <div class="flex items-center justify-center h-full" v-else>
                                    <ElEmpty description="暂无账号信息"></ElEmpty>
                                </div>
                            </template>
                        </ElTable>
                    </div>
                    <div class="flex justify-end p-4">
                        <pagination v-model="pager" @change="getLists"></pagination>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <device-add
        ref="addDeviceRef"
        v-if="showAddDevice"
        :bind-loading="addDeviceLoading"
        @close="showAddDevice = false"
        @confirm="handleAddDeviceConfirm" />
    <device-progress
        v-if="showProgress"
        :progress-value="progressValue"
        :progress-error="progressError"
        :step="deviceStep"
        @close="showProgress = false"
        @retry="retryAddAccount" />
</template>

<script setup lang="ts">
import { getAccountList, addMaterial, deleteAccount } from "@/api/service";
import { getDeviceList as getDeviceListApi } from "@/api/device";
import { AppTypeEnum, DeviceCmdEnum } from "@/enums/appEnums";
import DeviceAdd from "./_components/device-add.vue";
import DeviceProgress from "./_components/device-progress.vue";
import { ElTableColumn } from "element-plus";

const route = useRoute();
const router = useRouter();
const nuxtApp = useNuxtApp();
const { socialPlatformList, currentSocialPlatform } = useSocialPlatform();

const deviceLists = ref<any[]>([]);

const addDeviceRef = ref<any>(null);
const showProgress = ref(false);
const progressError = ref(false);
const deviceStep = ref("");

// 获取当前设备信息
const getCurrentDevice = computed(() => {
    return deviceLists.value.find((item) => item.device_code === queryParams.device_code);
});

const getSocialPlatformList = computed(() => {
    return socialPlatformList.filter((item) => item.show);
});

const { onEvent, send } = useDeviceWs();

const {
    showAddDevice,
    addDeviceLoading,
    progressValue,
    handleAddAccount: addAccount,
    refreshAccount,
    handleRefreshAccount,
} = useAddDeviceAccount({
    send,
    onEvent,
    onSuccess: (res) => {
        const { msg, type, data } = res;
        switch (type) {
            case DeviceCmdEnum.GET_USER_INFO:
                showProgress.value = false;
                getLists();
                break;

            case DeviceCmdEnum.OPEN_APP:
            case DeviceCmdEnum.OPEN_PERSON_CENTER:
            case DeviceCmdEnum.GET_ACCOUNT_INFO:
            case DeviceCmdEnum.DATA_SEND:
            case DeviceCmdEnum.GET_ACCOUNT_INFO_COMPLETE:
                deviceStep.value = msg;
                break;
            default:
                progressError.value = false;
                feedback.closeLoading();
                break;
        }
    },
    onError: (err) => {
        progressError.value = true;
        feedback.closeLoading();
        feedback.msgError(err.error);
    },
});

const handleAddDeviceConfirm = async () => {
    await getDeviceList();
    getLists();
};

const isRefreshData = ref(false);
const refreshData = async () => {
    if (isRefreshData.value) return;
    try {
        isRefreshData.value = true;
        const { lists } = await getAccountList({ status: 1, device_code: queryParams.device_code });
        if (lists.length == 0) {
            feedback.msgError("暂无在线账号");
            return;
        }
        handleRefreshData(lists[0]);
    } catch (error) {
        feedback.msgError(error);
    } finally {
        isRefreshData.value = false;
    }
};

const tableRef = ref<any>(null);

const getDeviceList = async () => {
    const { lists } = await getDeviceListApi({
        page_size: 999,
    });
    deviceLists.value = [{ device_model: "全部", device_code: "" }, ...lists];
};

const openAddDevice = async () => {
    showAddDevice.value = true;
    await nextTick();
    addDeviceRef.value.open();
};

const handleChangeSocialPlatform = (type: AppTypeEnum) => {
    progressError.value = false;
    currentSocialPlatform.value = type;
    queryParams.type = type;
    resetPage();
};

const changeDevice = (deviceCode: string) => {
    queryParams.device_code = deviceCode;
    getLists();
};

const queryParams = reactive({
    type: AppTypeEnum.XHS,
    name: "",
    account: "",
    device_code: "",
});

const { pager, getLists, resetParams, resetPage } = usePaging({
    fetchFun: getAccountList,
    params: queryParams,
});

const handleRowClick = (row: any) => {
    router.push({
        path: "/app/service",
        query: {
            type: AppTypeEnum.XHS,
        },
    });
};

const containerRef = ref<HTMLElement>();
const currAccount = ref<any>(null);
const handleAddAccount = () => {
    showProgress.value = true;
    addAccount({
        device_code: queryParams.device_code,
        type: queryParams.type,
    });
};

const handleRefreshData = (row: any) => {
    showProgress.value = true;
    refreshAccount.value = [
        {
            id: row.id,
            account: row.account,
            type: row.type,
        },
    ];
    handleRefreshAccount(row.device_code, row.type);
};

const retryAddAccount = () => {
    if (currAccount.value) {
        handleRefreshData(currAccount.value);
    } else {
        handleAddAccount();
    }
};

const handleGetBusinessCard = async (row: any) => {
    currAccount.value = row;
    feedback.loading("获取名片中...", containerRef.value);
    send({
        type: DeviceCmdEnum.GET_BUSINESS_CARD,
        deviceId: row.device_code,
        appType: row.type,
    });
};

const handleDelete = (row: any) => {
    nuxtApp.$confirm({
        message: "删除账号时，当前执行的任务将中断并无法继续，确定要删除该账号吗？",
        onConfirm: async () => {
            feedback.loading("删除中...", containerRef.value);
            try {
                await deleteAccount({ id: row.id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error);
            } finally {
                feedback.closeLoading();
            }
        },
    });
};

onMounted(async () => {
    queryParams.device_code = route.query.device_code as string;
    getLists();
});

getDeviceList();
</script>

<style scoped></style>
