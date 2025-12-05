<template>
    <div class="h-full flex flex-col bg-white rounded-lg">
        <!-- 顶部操作区域 -->
        <div class="flex items-center justify-between flex-shrink-0 p-4">
            <ElButton type="primary" @click="openAddDevicePopup">新增设备</ElButton>
            <div class="flex items-center justify-end gap-2 grow">
                <ElRadioGroup v-model="queryParams.device_status" @change="getLists">
                    <ElRadioButton label="全部" value=""></ElRadioButton>
                    <ElRadioButton label="在线" :value="1"></ElRadioButton>
                    <ElRadioButton label="离线" :value="0"></ElRadioButton>
                </ElRadioGroup>
                <ElButton :icon="Refresh" @click="getLists" />
            </div>
        </div>
        <!-- 设备列表表格 -->
        <div class="grow min-h-0 flex flex-col overflow-hidden">
            <div class="grow min-h-0">
                <ElTable
                    :data="pager.lists"
                    stripe
                    height="100%"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '60px' }"
                    v-loading="pager.loading">
                    <ElTableColumn prop="device_model" label="设备型号" min-width="120"></ElTableColumn>
                    <ElTableColumn prop="status" label="状态" width="80">
                        <template #default="{ row }">
                            <div v-if="row.device_status === 1" class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-success rounded-full"></div>
                                在线
                            </div>
                            <div v-else class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-danger rounded-full"></div>
                                离线
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="device_code" label="设备码" min-width="160"></ElTableColumn>
                    <ElTableColumn prop="wechat_id" label="微信ID" width="180"></ElTableColumn>
                    <ElTableColumn prop="sdk_version" label="SDK版本" min-width="160"></ElTableColumn>
                    <ElTableColumn prop="create_time" label="创建时间" width="180"></ElTableColumn>
                    <ElTableColumn label="操作" width="100" fixed="right">
                        <template #default="{ row }">
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl">
                                <template #reference>
                                    <ElButton link>
                                        <Icon name="el-icon-MoreFilled"></Icon>
                                    </ElButton>
                                </template>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleRemoveDevice(row)">
                                        <ElButton link icon="el-icon-Close" class="w-full !justify-start"
                                            >移除设备</ElButton
                                        >
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleClearCache(row)">
                                        <ElButton link icon="el-icon-Delete" class="w-full !justify-start"
                                            >清除缓存</ElButton
                                        >
                                    </div>
                                </div>
                            </ElPopover>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <ElEmpty description="暂无数据"></ElEmpty>
                    </template>
                </ElTable>
            </div>
            <!-- 分页 -->
            <div class="flex justify-end p-4">
                <pagination v-model="pager" @change="getLists"></pagination>
            </div>
        </div>
    </div>

    <!-- 新增设备弹窗 -->
    <popup
        v-if="showAddDevicePopup"
        ref="addDevicePopupRef"
        title="添加设备"
        async
        confirm-button-text="确认绑定"
        :confirm-loading="isAddingDevice"
        @close="showAddDevicePopup = false"
        @confirm="confirmAddDevice">
        <div class="flex flex-col gap-y-4">
            <ElInput v-model="deviceAuthCode" placeholder="请输入您的设备授权码" clearable class="!h-[48px]"></ElInput>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { ref, reactive, nextTick, onMounted } from "vue";
import { Refresh } from "@element-plus/icons-vue";
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
:deep(.el-radio-group) {
    .el-radio-button__inner {
        padding: 8.5px 30px;
    }
}
:deep(.el-input-group__append) {
    background-color: transparent;
    border: none;
}
</style>
