<template>
    <div class="h-full flex flex-col bg-white border border-br rounded-[20px] overflow-hidden">
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_10px_rgba(0,101,251,0.4)]"></div>
                <h3 class="text-lg font-[900] text-[#0F172A]">终端设备管理</h3>
                <ElButton
                    type="primary"
                    class="!rounded-xl !h-10 !px-6 !font-black hover:scale-105 transition-transform ml-2"
                    @click="openAddDevicePopup">
                    <Icon name="el-icon-Plus" />
                    <span class="ml-1">新增设备绑定</span>
                </ElButton>
            </div>
            <div class="flex items-center gap-x-4">
                <ElRadioGroup v-model="queryParams.device_status" @change="getLists" class="modern-radio-group">
                    <ElRadioButton label="全部设备" value=""></ElRadioButton>
                    <ElRadioButton label="在线" :value="1"></ElRadioButton>
                    <ElRadioButton label="离线" :value="0"></ElRadioButton>
                </ElRadioGroup>
            </div>
        </div>
        <div class="grow min-h-0">
            <ElTable :data="pager.lists" v-loading="pager.loading" height="100%">
                <ElTableColumn label="终端设备型号" min-width="160">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-3">
                            <div
                                class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                                <Icon name="local-icon-device" :size="24" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[14px] font-black text-tx-primary">{{
                                    row.device_model || "未知型号"
                                }}</span>
                                <span class="text-[11px] text-tx-placeholder font-mono">{{ row.device_code }}</span>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="运行状态" width="120">
                    <template #default="{ row }">
                        <div :class="['status-badge', row.device_status === 1 ? 'is-online' : 'is-offline']">
                            <span class="pulse-dot"></span>
                            {{ row.device_status === 1 ? "在线" : "离线" }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="关联微信ID" min-width="180">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-2">
                            <Icon name="local-icon-wechat" color="var(--green-500)" />
                            <span class="text-[13px] font-medium text-tx-regular">{{ row.wechat_id || "未登录" }}</span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="sdk_version" label="SDK版本" width="140">
                    <template #default="{ row }">
                        <span class="px-2 py-0.5 rounded bg-slate-100 text-[11px] font-medium text-slate-500">{{
                            row.sdk_version
                        }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="create_time" label="接入时间" width="180">
                    <template #default="{ row }">
                        <span class="text-xs text-tx-placeholder">{{ row.create_time }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="操作" width="160" fixed="right" align="center">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center">
                            <ElButton
                                type="primary"
                                link
                                class="!text-primary !font-black !text-xs"
                                @click="handleClearCache(row)">
                                清除缓存
                            </ElButton>
                            <ElButton
                                type="primary"
                                link
                                class="!text-primary !font-black !text-xs"
                                @click="handleRemoveDevice(row)">
                                移除设备
                            </ElButton>
                        </div>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <ElEmpty :image-size="140" description="未发现绑定的终端设备" />
                </template>
            </ElTable>
        </div>
        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
            <span class="text-xs font-medium text-[#94A3B8]">共计 {{ pager.count }} 个终端设备</span>
            <pagination v-model="pager" @change="getLists" />
        </div>
        <popup
            v-if="showAddDevicePopup"
            ref="addDevicePopupRef"
            width="460px"
            async
            confirm-button-text="确认绑定设备"
            :confirm-loading="isAddingDevice"
            @close="showAddDevicePopup = false"
            @confirm="confirmAddDevice"
            custom-class="modern-popup">
            <div class="py-2">
                <div class="flex items-center gap-x-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                        <Icon name="el-icon-Cpu" :size="24" />
                    </div>
                    <div>
                        <h3 class="text-[16px] font-black text-tx-primary">绑定新终端</h3>
                        <p class="text-xs text-tx-placeholder mt-0.5">请输入移动端设备的授权码</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="text-[13px] font-medium text-tx-regular px-1">设备授权码 (Auth Code)</div>
                    <ElInput
                        v-model="deviceAuthCode"
                        placeholder="请输入您的设备授权码"
                        class="modern-auth-input"
                        clearable>
                        <template #prefix>
                            <Icon name="el-icon-Key" />
                        </template>
                    </ElInput>
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 flex gap-3">
                        <Icon name="el-icon-Warning" color="var(--amber-500)" />
                        <div class="text-xs text-amber-700 leading-relaxed">
                            请确保您的设备已安装最新版插件，并处于联网状态。绑定后系统将自动尝试建立 WebSocket
                            通讯连接。
                        </div>
                    </div>
                </div>
            </div>
        </popup>
    </div>
</template>
<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { getDeviceLists, deleteDevice, updateDevice } from "@/api/person_wechat";
import useWeChatWs from "../../../_hooks/useWeChatWs";
import { MsgTypeEnum, type TriggerTaskParams } from "../../../_enums";

// --- 1. 初始设置 & 依赖 --- //

const nuxtApp = useNuxtApp();

// --- 2. 状态定义 --- //

// 查询参数
const queryParams = reactive({
    device_status: "" as "" | 0 | 1, // 设备状态
});

// 分页逻辑
const { pager, getLists } = usePaging({
    fetchFun: getDeviceLists,
    params: queryParams,
});

// 新增设备弹窗
const showAddDevicePopup = ref(false);
const addDevicePopupRef = ref<InstanceType<typeof Popup>>();
const deviceAuthCode = ref(""); // 设备授权码

// WebSocket 通信
const { on, send, isConnected, addDeviceLoading: isAddingDevice, actionType } = useWeChatWs();

// --- 3. 数据获取与刷新 --- //

// --- 4. WebSocket 通信处理 --- //

// 监听普通消息
on("message", (data: any) => {
    const { MsgType } = data;
    if (MsgType === MsgTypeEnum.CleanCache) {
        feedback.msgSuccess("清除缓存成功");
    }
    if (MsgType == MsgTypeEnum.WeChatOfflineNotice || MsgType == MsgTypeEnum.WeChatOnlineNotice) {
        getLists();
    }
});

// 监听成功回调
on("success", (data: any) => {
    if (data.type === "add-device") {
        showAddDevicePopup.value = false;
        getLists(); // 刷新列表
    }
});

// 监听需要前置授权的动作
on("action", async (data: any) => {
    const { type, accessToken, deviceId, wechatId } = data;
    // 清除缓存等操作需要先通过 Auth 获取最新的 accessToken
    if (type === MsgTypeEnum.CleanCache) {
        triggerTask(MsgTypeEnum.CleanCache, { deviceId, accessToken, wechatId });
        actionType.value = undefined; // 重置动作类型
    }
});

// 触发 WebSocket 任务
function triggerTask(taskType: MsgTypeEnum, params: TriggerTaskParams = {}) {
    const allowedTasks = [MsgTypeEnum.AddDevice, MsgTypeEnum.Auth, MsgTypeEnum.CleanCache];

    if (!allowedTasks.includes(taskType)) return;

    const content: any = {
        DeviceId: params.deviceId,
        AccessToken: params.accessToken,
        WeChatId: params.wechatId,
        TaskId: params.TaskId || Date.now(),
    };

    send({
        MsgType: taskType,
        Content: content,
    });
}

// --- 5. UI 事件处理 --- //

// 打开新增设备弹窗
const openAddDevicePopup = async () => {
    deviceAuthCode.value = "";
    showAddDevicePopup.value = true;
    await nextTick();
    addDevicePopupRef.value?.open();
};

// 确认新增设备
const confirmAddDevice = async () => {
    if (!isConnected.value) {
        feedback.msgError("网络连接失败，请检查网络");
        return;
    }
    if (!deviceAuthCode.value) {
        return feedback.msgError("请输入您的设备授权码");
    }
    actionType.value = MsgTypeEnum.AddDevice;
    isAddingDevice.value = true;
    triggerTask(MsgTypeEnum.AddDevice, { deviceId: deviceAuthCode.value });
};

// 移除设备
const handleRemoveDevice = async (row: any) => {
    nuxtApp.$confirm({
        message: "确定要移除设备吗？此操作不可逆！",
        onConfirm: async () => {
            feedback.loading("移除中...");
            try {
                // 后端删除记录
                await deleteDevice({ id: row.id, device_code: row.device_code });
                // 更新设备状态为未使用
                await updateDevice({ device_code: row.device_code, is_used: false });
                feedback.msgSuccess("移除成功");
                getLists(); // 刷新列表
            } catch (error) {
                feedback.msgError(error || "移除失败");
            } finally {
                feedback.closeLoading();
            }
        },
    });
};

// 清除缓存
const handleClearCache = async (row: any) => {
    nuxtApp.$confirm({
        message: "确定要清除该设备的缓存吗？",
        onConfirm: async () => {
            // 清理缓存需要先进行授权
            actionType.value = MsgTypeEnum.CleanCache;
            triggerTask(MsgTypeEnum.Auth, { deviceId: row.device_code });
        },
    });
};
onMounted(() => {
    getLists();
});
</script>

<style scoped lang="scss">
.status-badge {
    @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium;
    .pulse-dot {
        @apply w-1.5 h-1.5 rounded-full mr-2;
    }

    &.is-online {
        @apply bg-emerald-50 text-emerald-600;
        .pulse-dot {
            @apply bg-emerald-500 animate-pulse;
        }
    }

    &.is-offline {
        @apply bg-slate-100 text-slate-500;
        .pulse-dot {
            @apply bg-slate-400;
        }
    }
}

.modern-radio-group {
    @apply bg-slate-100 p-1 rounded-xl border-[none] gap-3;
    :deep(.el-radio-button__inner) {
        border: none;
        @apply bg-[transparent] rounded-lg text-[13px] font-medium text-slate-500 h-8 flex items-center px-4 transition-all;
        &:hover {
            @apply text-primary;
        }
    }
    :deep(.el-radio-button__original-radio:checked + .el-radio-button__inner) {
        @apply bg-white text-primary shadow-light;
    }
}
</style>
