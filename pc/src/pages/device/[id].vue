<template>
    <div class="h-full px-4 pb-4 flex flex-col min-w-[1000px]">
        <div class="mb-6 px-2">
            <ElBreadcrumb separator="/">
                <ElBreadcrumbItem>
                    <span
                        class="text-[13px] font-black text-[#94A3B8] hover:text-primary cursor-pointer transition-colors"
                        @click="$router.back()"
                        >AI 终端控制台</span
                    >
                </ElBreadcrumbItem>
                <ElBreadcrumbItem>
                    <span class="text-[13px] font-[900] text-[#1E293B]">账号矩阵设置</span>
                </ElBreadcrumbItem>
            </ElBreadcrumb>
        </div>
        <div
            class="grow min-h-0 bg-white rounded-[32px] flex flex-col overflow-hidden border border-[var(--el-border-color)]"
            ref="containerRef">
            <div class="h-[88px] px-8 flex items-center justify-between bg-slate-50/30">
                <div class="flex items-center gap-4">
                    <ElPopover
                        trigger="click"
                        width="250px"
                        :show-arrow="false"
                        popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light">
                        <template #reference>
                            <div
                                class="flex items-center gap-3 px-4 py-2 bg-white border border-br rounded-2xl cursor-pointer hover:border-primary transition-all group">
                                <span class="group-hover:text-primary transition-colors leading-[0]">
                                    <Icon name="el-icon-Monitor" :size="18" />
                                </span>
                                <span
                                    class="text-[15px] font-[900] text-[#1E293B] group-hover:text-primary transition-colors"
                                    >{{ getCurrentDevice?.device_model || "选择设备" }}</span
                                >
                                <span class="text-[#CBD5E1] group-hover:text-primary transition-colors leading-[0]">
                                    <Icon name="el-icon-ArrowDown" />
                                </span>
                            </div>
                        </template>

                        <div class="p-2 w-[240px]">
                            <div class="text-[11px] font-black text-[#94A3B8] px-3 mb-2 uppercase tracking-widest">
                                可用设备列表
                            </div>
                            <div class="space-y-1 max-h-[300px] overflow-y-auto custom-scrollbar">
                                <div
                                    v-for="(item, index) in deviceLists"
                                    :key="index"
                                    class="flex items-center justify-between px-3 py-2.5 rounded-xl cursor-pointer transition-all"
                                    :class="
                                        queryParams.device_code === item.device_code
                                            ? 'bg-[#0065FB]/5 text-primary'
                                            : 'hover:bg-slate-50 text-[#475569]'
                                    "
                                    @click="changeDevice(item.device_code)">
                                    <span class="text-[13px] font-medium truncate pr-2">{{ item.device_model }}</span>
                                    <div
                                        v-if="queryParams.device_code === item.device_code"
                                        class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                </div>
                            </div>
                        </div>
                    </ElPopover>

                    <div
                        v-if="queryParams.device_code"
                        class="px-3 py-1 bg-[#F1F5F9] border border-br rounded-lg text-[11px] font-mono font-medium text-[#64748B]">
                        ID: {{ queryParams.device_code }}
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <ElInput
                        v-model="queryParams.account"
                        placeholder="搜索账号或昵称..."
                        class="custom-input"
                        clearable
                        @clear="resetParams()"
                        @keyup.enter="getLists()">
                        <template #prefix><Icon name="el-icon-Search" /></template>
                    </ElInput>
                </div>
            </div>

            <div class="grow min-h-0 flex w-full">
                <div class="w-[200px] border-r border-t border-br bg-slate-50/30 p-4 space-y-2">
                    <div class="text-[11px] font-black text-[#94A3B8] px-4 mb-4 uppercase tracking-widest">
                        社交平台
                    </div>
                    <div
                        v-for="(item, index) in getSocialPlatformList"
                        :key="index"
                        class="group flex items-center gap-3 px-4 py-3 cursor-pointer rounded-2xl transition-all border border-[transparent]"
                        :class="
                            currentSocialPlatform === item.type
                                ? 'bg-white border-br shadow-light'
                                : 'hover:bg-[#ffffff]/50'
                        "
                        @click="handleChangeSocialPlatform(item.type)">
                        <img
                            :src="item.icon"
                            class="w-6 h-6 grayscale group-hover:grayscale-0 transition-all"
                            :class="{ 'grayscale-0': currentSocialPlatform === item.type }" />
                        <span
                            class="text-[14px] font-medium"
                            :class="currentSocialPlatform === item.type ? 'text-primary' : 'text-[#64748B]'"
                            >{{ item.name }}</span
                        >
                    </div>
                </div>

                <div class="flex-1 overflow-hidden flex flex-col">
                    <div class="grow min-h-0">
                        <ElTable v-loading="pager.loading" :data="pager.lists" height="100%">
                            <ElTableColumn label="账号信息" min-width="240">
                                <template #default="{ row }">
                                    <div class="flex items-center gap-4">
                                        <div class="relative">
                                            <ElAvatar
                                                :size="48"
                                                :src="row.avatar"
                                                class="border-2 border-white shadow-sm" />
                                            <div
                                                v-if="row.status == 1"
                                                class="absolute -bottom-1 -right-1 w-5 h-5 bg-[#10B981] rounded-full border-2 border-white flex items-center justify-center">
                                                <Icon name="el-icon-Check" color="white" :size="10" />
                                            </div>
                                        </div>
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[14px] font-[900] text-[#1E293B]">{{
                                                    row.nickname
                                                }}</span>
                                                <div
                                                    v-if="row.status == 1"
                                                    class="px-2 py-0.5 bg-[#ECFDF5] text-[#10B981] text-[10px] font-black rounded-md">
                                                    当前账号
                                                </div>
                                            </div>
                                            <span class="text-[11px] font-medium text-[#94A3B8] tracking-tight"
                                                >ID: {{ row.account }}</span
                                            >
                                        </div>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="数据统计" width="200">
                                <template #default="{ row }">
                                    <div class="flex items-center justify-center gap-6 text-[#475569]">
                                        <div class="flex flex-col">
                                            <span class="text-[14px] font-black">{{ row.fans || "0" }}</span>
                                            <span class="text-[10px] font-medium text-[#94A3B8]">粉丝</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[14px] font-black">{{ row.thumbup_collect || "0" }}</span>
                                            <span class="text-[10px] font-medium text-[#94A3B8]">获赞</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[14px] font-black">{{ row.followers || "0" }}</span>
                                            <span class="text-[10px] font-medium text-[#94A3B8]">关注</span>
                                        </div>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="接管状态" width="100">
                                <template #default="{ row }">
                                    <div>{{ row.takeover_mode == 1 ? "AI" : "人工" }}</div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="接管机器人" min-width="120">
                                <template #default="{ row }">
                                    <div>{{ row.takeover_mode == 1 ? row.robot_name : "-" }}</div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn prop="name" label="接管状态">
                                <template #default="{ row }">
                                    <ElSwitch
                                        v-model="row.open_ai"
                                        :active-value="1"
                                        :inactive-value="0"
                                        @change="changeOpenAi(row)" />
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="最后更新" width="160">
                                <template #default="{ row }">
                                    <span class="text-xs font-medium text-[#64748B]">{{ row.create_time }}</span>
                                </template>
                            </ElTableColumn>

                            <ElTableColumn label="管理" width="50" fixed="right" align="right">
                                <template #default="{ row }">
                                    <div class="flex items-center justify-end">
                                        <ElPopover
                                            trigger="hover"
                                            placement="left"
                                            :show-arrow="false"
                                            popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light">
                                            <template #reference>
                                                <div
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-[#F1F5F9] cursor-pointer transition-all">
                                                    <Icon name="el-icon-MoreFilled" color="#CBD5E1" />
                                                </div>
                                            </template>
                                            <div class="flex flex-col gap-1 p-1">
                                                <button @click.stop="handleConfig(row)" class="action-btn">
                                                    <Icon name="el-icon-Setting" /> 配置
                                                </button>
                                                <button @click.stop="handleRefreshData(row)" class="action-btn">
                                                    <Icon name="el-icon-Refresh" /> 刷新数据
                                                </button>
                                                <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                                <button
                                                    @click.stop="handleDelete(row)"
                                                    class="action-btn !text-red-500 hover:!bg-red-50">
                                                    <Icon name="el-icon-Delete" /> 账号移除
                                                </button>
                                            </div>
                                        </ElPopover>
                                    </div>
                                </template>
                            </ElTableColumn>

                            <template #empty>
                                <div class="py-20 flex flex-col items-center justify-center opacity-40 grayscale">
                                    <ElEmpty :image-size="100" description="该终端尚未同步账号" />
                                </div>
                            </template>
                        </ElTable>
                    </div>
                    <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
                        <span class="text-xs font-medium text-[#94A3B8]"
                            >显示 {{ pager.lists.length }} 条，共 {{ pager.count }} 条账号数据</span
                        >
                        <pagination v-model="pager" @change="getLists" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <account-setting
        v-if="showAccountSetting"
        ref="accountSettingRef"
        @close="showAccountSetting = false"
        @success="getLists()"></account-setting>
</template>
<script setup lang="ts">
import { getAccountList, deleteAccount, changeAccountStatus } from "@/api/service";
import { getDeviceList as getDeviceListApi } from "@/api/device";
import { AppTypeEnum, DeviceCmdEnum } from "@/enums/appEnums";
import { ElTableColumn } from "element-plus";
import AccountSetting from "./_components/account-setting.vue";

const route = useRoute();
const nuxtApp = useNuxtApp();
const { socialPlatformList, currentSocialPlatform } = useSocialPlatform();

const deviceLists = ref<any[]>([]);
const showProgress = ref(false);
const progressError = ref(false);
const deviceStep = ref("");

const showAccountSetting = ref(false);
const accountSettingRef = shallowRef<typeof AccountSetting>();

// 获取当前设备信息
const getCurrentDevice = computed(() => {
    return deviceLists.value.find((item) => item.device_code === queryParams.device_code);
});

const getSocialPlatformList = computed(() => {
    return socialPlatformList.filter((item) => item.show);
});

const { onEvent, send, isConnected } = useDeviceWs();

const { refreshAccount, handleRefreshAccount } = useAddDeviceAccount({
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

const getDeviceList = async () => {
    const { lists } = await getDeviceListApi({
        page_size: 999,
    });
    deviceLists.value = [{ device_model: "全部", device_code: "" }, ...lists];
};

const changeOpenAi = async (row: any) => {
    try {
        await changeAccountStatus({
            account: row.account,
            open_ai: row.open_ai,
            account_type: row.type,
        });
        feedback.msgSuccess("操作成功");
    } catch (error) {
        feedback.msgError("操作失败");
    }
    getLists();
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

const handleConfig = async (row: any) => {
    showAccountSetting.value = true;
    await nextTick();
    accountSettingRef.value?.open();
    accountSettingRef.value?.setFormData({
        ...row,
        account_type: queryParams.type,
    });
};

const containerRef = ref<HTMLElement>();

const handleRefreshData = (row: any) => {
    if (!isConnected.value) {
        feedback.msgError("设备未连接");
        return;
    }
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

<style scoped lang="scss">
.refresh-btn-shadow {
    box-shadow: 0 8px 16px -4px rgba(var(--el-color-primary), 0.4);
}

.action-btn {
    @apply flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-[#64748B] hover:bg-[#F1F5F9] hover:text-primary transition-all;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-[#E2E8F0] rounded-full;
}
</style>
