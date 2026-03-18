<template>
    <div class="h-full flex flex-col bg-white border border-br rounded-[20px] overflow-hidden">
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-4">
                <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_10px_rgba(0,101,251,0.4)]"></div>
                <h3 class="text-lg font-[900] text-[#0F172A]">微信管理</h3>
            </div>

            <div class="flex items-center gap-x-4">
                <ElRadioGroup v-model="queryParams.takeover_mode" @change="getLists" class="modern-radio-group">
                    <ElRadioButton label="全部模式" value=""></ElRadioButton>
                    <ElRadioButton label="人工介入" :value="0"></ElRadioButton>
                    <ElRadioButton label="AI接管" :value="1"></ElRadioButton>
                </ElRadioGroup>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable :data="pager.lists" v-loading="pager.loading" height="100%">
                <ElTableColumn label="微信账号信息" min-width="240">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-3 pl-2">
                            <div
                                class="shrink-0 w-10 h-10 rounded-xl bg-[#22c55e]/5 flex items-center justify-center text-green-500 border border-[#22c55e]/10">
                                <Icon name="local-icon-wechat" :size="24" />
                            </div>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[14px] font-[900] text-[#1E293B]">{{ row.wechat_nickname }}</span>
                                    <span
                                        v-if="row.remark"
                                        class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded font-medium">
                                        {{ row.remark }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-400 font-mono mt-0.5">{{ row.wechat_id }}</span>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="接管配置" width="160">
                    <template #default="{ row }">
                        <div class="flex flex-col gap-1.5">
                            <div>
                                <div :class="['mode-tag', row.takeover_mode === 1 ? 'is-ai' : 'is-manual']">
                                    <Icon :name="row.takeover_mode === 1 ? 'el-icon-Cpu' : 'el-icon-User'" :size="12" />
                                    {{ row.takeover_mode === 1 ? "AI 接管中" : "人工介入" }}
                                </div>
                            </div>
                            <div class="text-[10px] text-slate-400 font-medium pl-1">
                                类型：{{ ["全部", "私聊", "群聊"][row.takeover_type] }}
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="关联机器人" min-width="150">
                    <template #default="{ row }">
                        <div
                            v-if="row.robot_name"
                            class="flex items-center justify-center gap-2 text-primary font-medium">
                            <div class="w-2 h-2 rounded-full bg-primary/40 animate-pulse"></div>
                            <span class="text-[13px]">{{ row.robot_name }}</span>
                        </div>
                        <span v-else class="text-slate-300 text-xs italic">暂未关联</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="AI 功能开关" width="130" align="center">
                    <template #default="{ row }">
                        <div class="flex flex-col items-center gap-1">
                            <ElSwitch
                                v-model="row.open_ai"
                                :active-value="1"
                                :inactive-value="0"
                                inline-prompt
                                active-text="ON"
                                inactive-text="OFF"
                                class="custom-switch"
                                @change="handleToggleAiSwitch(row)" />
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="通讯状态" width="120">
                    <template #default="{ row }">
                        <div :class="['status-badge', row.wechat_status === 1 ? 'is-online' : 'is-offline']">
                            <span class="pulse-dot"></span>
                            {{ row.wechat_status === 1 ? "在线" : "离线" }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="创建时间" width="180">
                    <template #default="{ row }">
                        <span class="text-xs text-slate-400 font-medium">{{ row.create_time }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="操作" width="160" fixed="right" align="center">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center">
                            <ElButton type="primary" link class="!font-black !text-xs" @click="openEditPopup(row)">
                                编辑
                            </ElButton>
                            <ElButton
                                type="primary"
                                link
                                class="!font-black !text-xs"
                                @click="handleUpdateFriends(row)">
                                更新好友
                            </ElButton>
                            <ElButton
                                v-if="row.wechat_status === 1"
                                type="danger"
                                link
                                class="!font-black !text-xs"
                                @click="handleOffline(row)">
                                下线
                            </ElButton>
                        </div>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <ElEmpty :image-size="140" description="暂无接管账号数据" />
                </template>
            </ElTable>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
            <span class="text-xs font-medium text-[#94A3B8]"
                >显示 {{ pager.lists.length }} 条，共 {{ pager.count }} 条微信账号数据</span
            >
            <pagination v-model="pager" @change="getLists" />
        </div>

        <edit-pop v-if="showEditPopup" ref="editPopupRef" @close="showEditPopup = false" @success="getLists" />
    </div>
</template>
<script setup lang="ts">
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
.mode-tag {
    @apply inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-[900] border;
    &.is-ai {
        @apply bg-[#0065fb]/5 text-primary border-[#0065fb]/20;
    }
    &.is-manual {
        @apply bg-amber-50 text-amber-600 border-amber-200;
    }
}

/* 状态标签样式 */
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
