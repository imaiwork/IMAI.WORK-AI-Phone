<template>
    <div class="h-full flex flex-col bg-white rounded-lg">
        <!-- 顶部过滤区域 -->
        <div class="flex items-center justify-end flex-shrink-0 p-4">
            <div class="flex items-center justify-end gap-2 grow">
                <ElRadioGroup v-model="queryParams.takeover_mode" @change="getLists">
                    <ElRadioButton label="全部" value=""></ElRadioButton>
                    <ElRadioButton label="人工介入" :value="0"></ElRadioButton>
                    <ElRadioButton label="AI接管" :value="1"></ElRadioButton>
                </ElRadioGroup>
                <ElButton :icon="Refresh" @click="getLists" />
            </div>
        </div>
        <!-- 微信账号列表 -->
        <div class="grow min-h-0 flex flex-col overflow-hidden">
            <div class="grow min-h-0">
                <ElTable
                    :data="pager.lists"
                    stripe
                    height="100%"
                    :row-style="{ height: '60px' }"
                    :header-row-style="{ height: '62px' }"
                    v-loading="pager.loading">
                    <ElTableColumn label="账号信息" min-width="160">
                        <template #default="{ row }">
                            {{ row.wechat_nickname }}<template v-if="row.remark">({{ row.remark }})</template>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="wechat_id" label="微信ID" width="180"></ElTableColumn>
                    <ElTableColumn label="接管模式" width="100">
                        <template #default="{ row }">
                            <span v-if="row.takeover_mode === 1">AI接管</span>
                            <span v-else>人工介入</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="robot_name" label="关联机器人" min-width="160">
                        <template #default="{ row }">
                            <span>{{ row.robot_name || "-" }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="接管类型" width="120">
                        <template #default="{ row }">
                            <span v-if="row.takeover_type === 0">全部</span>
                            <span v-else-if="row.takeover_type === 1">私聊</span>
                            <span v-else-if="row.takeover_type === 2">群聊</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="AI总功能开关" width="120">
                        <template #default="{ row }">
                            <ElSwitch
                                v-model="row.open_ai"
                                :active-value="1"
                                :inactive-value="0"
                                @change="handleToggleAiSwitch(row)" />
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="状态" width="120">
                        <template #default="{ row }">
                            <div class="flex justify-center">
                                <div v-if="row.wechat_status === 1" class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-success rounded-full"></div>
                                    在线
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-danger rounded-full"></div>
                                    离线
                                </div>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="create_time" label="创建时间" width="180"></ElTableColumn>
                    <ElTableColumn label="操作" width="120" fixed="right">
                        <template #default="{ row }">
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl">
                                <template #reference>
                                    <ElButton link><Icon name="el-icon-MoreFilled" /></ElButton>
                                </template>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="openEditPopup(row)">
                                        <Icon name="el-icon-EditPen" />
                                        <span>编辑</span>
                                    </div>
                                    <div
                                        v-if="row.wechat_status === 1"
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleOffline(row)">
                                        <Icon name="el-icon-SwitchButton" />
                                        <span>下线</span>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="handleUpdateFriends(row)">
                                        <Icon name="el-icon-Refresh" />
                                        <span>更新好友</span>
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
            <div class="flex justify-end p-4">
                <pagination v-model="pager" @change="getLists"></pagination>
            </div>
        </div>
    </div>

    <!-- 编辑弹窗 -->
    <edit-pop v-if="showEditPopup" ref="editPopupRef" @close="showEditPopup = false" @success="getLists" />
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, nextTick } from "vue";
import { Refresh } from "@element-plus/icons-vue";
import { getWeChatLists, saveWeChatAi, reportWeChatFriends } from "@/api/person_wechat";
import EditPop from "./edit.vue";
import useWeChatWs from "../../../_hooks/useWeChatWs";
import { MsgTypeEnum, type TriggerTaskParams } from "../../../_enums";

// --- 1. 初始化 & 依赖注入 ---
const nuxtApp = useNuxtApp();
// --- 2. 状态定义 ---

// 查询参数
const queryParams = reactive({
    takeover_mode: "" as "" | 0 | 1,
});

// 分页逻辑
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getWeChatLists,
    params: queryParams,
});

// 编辑弹窗
const showEditPopup = ref(false);
const editPopupRef = ref<InstanceType<typeof EditPop>>();

// WebSocket 通信
const { on, send, actionType } = useWeChatWs();

// 当前操作的微信实例，用于更新好友等场景
const currentWechat = ref<any>({});
const friendList = ref<any[]>([]);

// --- 3. 数据获取 ---

// --- 4. WebSocket 通信处理 ---

// 监听错误
on("error", () => {
    feedback.closeLoading();
});

// 监听消息：处理认证成功和好友推送通知
on("message", async (data: any) => {
    const { MsgType, Content } = data;

    // 认证成功后，自动触发后续任务（如更新好友）
    if (MsgType === MsgTypeEnum.Auth) {
        actionType.value = null; // 清除前置动作
        currentWechat.value.accessToken = Content.AccessToken;
        feedback.loading("更新好友中，请稍候...");
        triggerTask(MsgTypeEnum.TriggerFriendPushTask);
    }

    // 处理好友分批推送
    if (MsgType === MsgTypeEnum.FriendPushNotice) {
        await handleFriendPush(Content);
    }
    if (MsgType == MsgTypeEnum.WeChatOfflineNotice || MsgType == MsgTypeEnum.WeChatOnlineNotice) {
        getLists();
    }
});

// 监听需要前置授权的动作回调
on("action", async (data: any) => {
    const { type, accessToken, deviceId, wechatId } = data;
    // 收到授权成功的回调后，执行真正的下线任务
    if (type === MsgTypeEnum.WechatLogoutTask) {
        triggerTask(MsgTypeEnum.WechatLogoutTask, { deviceId, accessToken, wechatId });
        actionType.value = undefined; // 重置动作
    }
});

// 触发 WebSocket 任务
function triggerTask(taskType: MsgTypeEnum, params: TriggerTaskParams = {}) {
    const content: any = {
        DeviceId: params.deviceId || currentWechat.value.device_code,
        AccessToken: params.accessToken || currentWechat.value.accessToken,
        WeChatId: params.wechatId || currentWechat.value.wechat_id,
        TaskId: params.TaskId || Date.now(),
    };

    send({ MsgType: taskType, Content: content });
}

// --- 5. UI 事件处理 ---

// 打开编辑弹窗
const openEditPopup = async (row: any) => {
    showEditPopup.value = true;
    await nextTick();
    editPopupRef.value?.open();
    editPopupRef.value?.setFormData(row);
};

// 账号下线
const handleOffline = async (row: any) => {
    nuxtApp.$confirm({
        message: "确定要下线该账号吗？",
        onConfirm: async () => {
            // 下线操作需要先进行授权
            actionType.value = MsgTypeEnum.WechatLogoutTask;
            triggerTask(MsgTypeEnum.Auth, { deviceId: row.device_code });
            // 乐观更新UI
            row.wechat_status = 0;
            feedback.msgSuccess("下线指令已发送");
        },
    });
};

// 更新好友
const handleUpdateFriends = async (row: any) => {
    nuxtApp.$confirm({
        message: "确定要更新好友吗？如果好友数量较多，请耐心等待。",
        onConfirm: async () => {
            currentWechat.value = row;
            // 更新好友需要先进行授权
            triggerTask(MsgTypeEnum.Auth, { deviceId: row.device_code });
        },
    });
};

// 切换AI总开关
const handleToggleAiSwitch = async (row: any) => {
    try {
        await saveWeChatAi({
            wechat_id: row.wechat_id,
            open_ai: row.open_ai,
            remark: row.remark,
            takeover_mode: row.takeover_mode,
            takeover_type: row.takeover_type,
            robot_id: row.robot_id,
            sort: row.sort,
        });
        feedback.msgSuccess("设置成功");
        getLists();
    } catch (error) {
        feedback.msgError("设置失败");
        // 失败时恢复原状
        row.open_ai = row.open_ai === 1 ? 0 : 1;
    }
};

// --- 6. 好友数据处理 ---

// 处理好友分批推送
async function handleFriendPush(Content: any) {
    const { Friends = [], Page, Size, Count } = Content;
    if (Friends.length === 0 && Count === 0) {
        feedback.closeLoading();
        return feedback.msgSuccess("该账号下没有好友");
    }

    if (Friends.length > 0) {
        const isFirstPage = Page === 0;
        friendList.value = isFirstPage ? Friends : [...friendList.value, ...Friends];

        // 批量上报好友信息
        await reportWeChatFriends({
            wechat_id: currentWechat.value.wechat_id,
            friends: Friends.map((friend: any) => formatFriendForApi(currentWechat.value.wechat_id, friend)),
        });

        // 检查是否所有好友都已接收完毕
        if (Size * Page + Friends.length >= Count) {
            feedback.closeLoading();
            feedback.msgSuccess("好友更新成功");
        }
    }
}

// 将WebSocket推送的好友数据格式化为API需要的格式
function formatFriendForApi(wechatId: string, friendInfo: any) {
    const {
        FriendId,
        FriendNo,
        FriendNick,
        Memo,
        Avatar,
        Gender,
        Country,
        Province,
        City,
        Phone,
        Desc,
        Source,
        SourceExt,
        CreateTime,
        IsUnusual,
        Type,
    } = friendInfo;
    const source = parseInt(Source);
    // 兼容旧数据，确保 source 值为7位数
    const finalSource = source < 1000000 ? source + 1000000 : source;

    return {
        wechat_id: wechatId,
        friend_id: FriendId,
        friend_no: FriendNo,
        nickname: FriendNick,
        remark: Memo,
        gender: Gender,
        country: Country,
        province: Province,
        city: City,
        avatar: Avatar,
        type: Type,
        label_ids: [],
        phone: Phone,
        desc: Desc,
        source: finalSource,
        source_ext: SourceExt,
        create_time: CreateTime,
        is_unusual: IsUnusual,
        open_ai: 1, // 默认为1，可根据业务调整
        takeover_mode: 1, // 默认为1，可根据业务调整
    };
}
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
