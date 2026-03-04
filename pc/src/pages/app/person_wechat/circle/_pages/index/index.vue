<template>
    <div class="w-full h-full">
        <div class="h-full flex gap-x-3">
            <div class="flex-1 flex bg-white rounded-[20px] border border-br overflow-hidden" ref="containerRef">
                <div class="w-[84px] flex-shrink-0 border-r border-[#F1F5F9] bg-slate-50">
                    <SidebarPanel
                        ref="sidebarPanelRef"
                        :current-wechat="currentWechat"
                        :showAddWeChat="false"
                        :wechat-list="wechatLists"
                        @update:current-wechat="handleSelectWeChat" />
                </div>

                <div class="flex-1 flex flex-col min-w-0">
                    <div
                        class="flex-shrink-0 flex items-center justify-between h-[64px] px-6 bg-white border-b border-[#F1F5F9]">
                        <div class="flex-1 mr-4">
                            <div
                                class="flex items-center text-[13px] text-amber-600 bg-amber-50 px-4 py-2 rounded-xl border border-amber-100">
                                <Icon name="el-icon-InfoFilled" />
                                <span class="ml-2">朋友圈更新存在延迟，若数据不同步请点击手动刷新</span>
                            </div>
                        </div>
                        <ElButton
                            class="!rounded-xl !h-10 hover:!bg-gray-50 transition-all font-medium border-none bg-gray-100"
                            :loading="circleListsLoading"
                            @click="refreshCircle">
                            <Icon name="el-icon-Refresh" />
                            <span class="ml-2">刷新动态</span>
                        </ElButton>
                    </div>

                    <div class="grow min-h-0 relative bg-white">
                        <div
                            v-if="circleListsLoading && !circleList.length"
                            class="absolute inset-0 z-[888] bg-[#ffffff]/80 backdrop-blur-sm flex flex-col justify-center items-center">
                            <loader />
                            <span class="mt-4 text-[13px] text-tx-placeholder font-medium">正在拉取朋友圈动态...</span>
                        </div>

                        <div
                            v-if="currentWechat && currentWechat.wechat_status == 2"
                            class="absolute inset-0 z-[999] bg-[#0f172a]/40 backdrop-blur-[2px] flex items-center justify-center p-6">
                            <div
                                class="bg-white rounded-[32px] p-10 flex flex-col items-center max-w-[340px] text-center border border-white">
                                <div class="w-20 h-20 rounded-full bg-red-50 flex items-center justify-center mb-6">
                                    <Icon name="local-icon-wifi_off" :size="40" class="text-error" />
                                </div>
                                <h3 class="text-[20px] font-black text-tx-primary mb-2">当前设备已离线</h3>
                                <p class="text-[13px] text-tx-placeholder leading-relaxed mb-6">
                                    该微信设备通信已中断，无法获取最新动态。请在侧边栏检查登录状态。
                                </p>
                            </div>
                        </div>

                        <CircleLists
                            ref="circleListsRef"
                            :circle-list="circleList"
                            @bottom="circleScrollEnd"
                            @preview-video="handlePreviewVideo" />

                        <div
                            v-if="circleListsLoad"
                            class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-[#ffffff]/90 backdrop-blur px-6 py-2 rounded-full shadow-light border border-primary-light-9 z-30 flex items-center gap-3">
                            <div class="chat-loader !scale-75"></div>
                            <span class="text-xs font-medium text-primary">正在载入更多动态...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- <div class="w-[462px] flex-shrink-0 bg-white rounded-[24px] shadow-sm border border-br overflow-hidden">
                <CircleSend v-model="circleSendForm" :is-show-we-chat="false" @success="refreshCircle()" />
            </div> -->
        </div>
    </div>

    <ElImageViewer
        v-if="showImageViewer"
        :url-list="imageViewerUrlList"
        :initial-index="imageViewerIndex"
        @close="showImageViewer = false" />
    <preview-video ref="previewVideoRef" v-if="showVideoViewer" @close="showVideoViewer = false" />
</template>
<script setup lang="ts">
import { dayjs } from "element-plus";
import { getWeChatLists } from "@/api/person_wechat";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import CircleLists from "./circle-lists.vue";
import CircleSend from "../../_components/circle-send.vue";
import SidebarPanel from "../../../chat/_components/sidebar-panel.vue";
import useWeChatWs from "../../../_hooks/useWeChatWs";
import useHandle from "../../../_hooks/useHandle";
import { HandleEventEnum, MsgErrorCodeEnum, MsgTypeEnum } from "../../../_enums";

// =================================================================================================
// Refs 和 状态
// =================================================================================================

const sidebarPanelRef = ref<InstanceType<typeof SidebarPanel> | null>(null);
const circleListsRef = ref<InstanceType<typeof CircleLists> | null>(null);
const containerRef = ref<HTMLElement | null>(null);

const circleSendForm = reactive<any>({
    name: `朋友圈任务${dayjs().format("YYYYMMDDHHmm")}`,
    content: "",
    attachment_type: MaterialTypeEnum.IMAGE,
    attachment_content: [],
    comment: "",
    date: dayjs().format("YYYY-MM-DD"),
    wechat_ids: [],
    time_config: ["00:00", "00:30"],
});

const circleList = ref<any[]>([]);
const circleParams = reactive({
    RefSnsId: 0,
    Count: 10,
});

const circleListsLoading = ref(false);
const circleListsFinished = ref(false);
const circleListsLoad = ref(false);
const circleId = ref<string | null>(null);

const showImageViewer = ref(false);
const imageViewerUrlList = ref<string[]>([]);
const imageViewerIndex = ref(0);

const showVideoViewer = ref(false);
const previewVideoRef = shallowRef(null);

const isLiked = ref(false);

// =================================================================================================
// Hooks
// =================================================================================================

const { wechatLists, currentWechat, actionType, onHandleEvent } = useHandle();
const { on, send } = useWeChatWs();

on("open", () => {
    getWeChatListsFn();
});

// =================================================================================================
// 工具函数
// =================================================================================================

// 计算发布时间
const calculatePublishTime = (date: number) => {
    const publishTime = dayjs(date * 1000);
    const now = dayjs();
    const diffSeconds = now.diff(publishTime, "second");
    const diffMinutes = now.diff(publishTime, "minute");
    const diffHours = now.diff(publishTime, "hour");

    if (publishTime.isSame(now, "day")) {
        if (diffSeconds < 60) {
            return `${diffSeconds}秒前`;
        } else if (diffMinutes < 60) {
            return `${diffMinutes}分钟前`;
        } else {
            return `${diffHours}小时前`;
        }
    } else {
        return publishTime.format("YYYY/MM/DD");
    }
};

// =================================================================================================
// WebSocket 任务触发器
// =================================================================================================

const triggerTask = (taskType: MsgTypeEnum, params?: Record<string, any>) => {
    let content: any = {
        DeviceId: params?.deviceId || currentWechat.value?.device_code,
        AccessToken: params?.accessToken || currentWechat.value?.accessToken,
        WeChatId: params?.wechatId || currentWechat.value?.wechat_id,
        TaskId: params?.taskId || `${Date.now()}`,
    };
    switch (taskType) {
        case MsgTypeEnum.Auth:
            break;
        case MsgTypeEnum.PullFriendCircleTask:
            content = {
                ...content,
                RefSnsId: circleParams.RefSnsId,
            };
            break;
        case MsgTypeEnum.CircleCommentReplyTask:
        case MsgTypeEnum.PullCircleDetailTask:
        case MsgTypeEnum.CircleLikeTask:
        case MsgTypeEnum.DeleteSNSNewsTask:
        case MsgTypeEnum.CircleCommentDeleteTask:
            content = {
                ...content,
                ...params,
            };
            break;
    }
    send({
        MsgType: taskType,
        Content: content,
    });
};

function PullFriendCircleTask(Content?: any) {
    const { DeviceId, AccessToken, WeChatId } = Content || {};
    triggerTask(MsgTypeEnum.PullFriendCircleTask, {
        accessToken: AccessToken,
        wechatId: WeChatId,
        deviceId: DeviceId,
    });
}

// =================================================================================================
// WebSocket 事件处理程序
// =================================================================================================

// 处理微信离线、在线状态
function handleWeChatStatusNotice(Content: any) {
    const { DeviceId, WeChatId, Status } = Content;
    wechatLists.value.forEach((item: any) => {
        if (item.device_code === DeviceId && item.wechat_id === WeChatId) {
            item.wechat_status = Status == "offline" ? 2 : 1;
        }
    });
    if (currentWechat.value?.device_code && Status == "offline") {
        feedback.msgError("设备离线，请重新登录");
    }
}

// 处理朋友圈数据推送
function handleCirclePushNotice(Content: any) {
    const { Circles } = Content;
    if (Circles && Circles.length) {
        // 判断分页之后 circleList 最后一条和 Circles 第一条是否相同
        if (circleList.value.length) {
            if (circleList.value[circleList.value.length - 1].CircleId == Circles[0].CircleId) {
                Circles.shift();
            }
        }

        circleList.value = circleList.value.concat(
            Circles.map((item: any) => ({
                ...item,
                publishTime: calculatePublishTime(item.PublishTime),
            }))
        );
    } else {
        circleListsFinished.value = true;
    }
    circleListsLoading.value = false;
    circleListsLoad.value = false;
}

// 处理认证消息
function handleAuth(Content: any) {
    const { DeviceId, AccessToken, WeChatId } = Content;
    circleListsLoading.value = true;
    wechatLists.value.forEach((item: any) => {
        if (item.device_code === DeviceId && item.wechat_id === WeChatId) {
            item.accessToken = AccessToken;
            item.loading = false;
            // currentWechat.value = item;
        }
    });

    if (currentWechat.value.wechat_id == WeChatId) {
        PullFriendCircleTask({
            DeviceId,
            AccessToken,
            WeChatId,
        });
    }
}

// 监听ws消息
on("message", async (data: any) => {
    feedback.closeLoading();

    const { MsgType, Content } = data;
    // @ts-ignore
    const handlers: Record<MsgTypeEnum, Function> = {
        [MsgTypeEnum.Auth]: handleAuth,
        [MsgTypeEnum.CirclePushNotice]: handleCirclePushNotice,
        [MsgTypeEnum.WeChatOnlineNotice]: handleWeChatStatusNotice,
        [MsgTypeEnum.CircleNewPublishNotice]: async () => {
            circleList.value.unshift(Content.Circle);
        },
        [MsgTypeEnum.CircleDetailNotice]: async () => {
            const { CircleId } = Content;
            if (CircleId.Content && actionType.value == HandleEventEnum.PreviewImage) {
                const { Images } = CircleId.Content;
                imageViewerUrlList.value = Images.map((item: any) => item.Url);
                showImageViewer.value = true;
            }
            if (CircleId.Content && actionType.value == HandleEventEnum.PreviewVideo) {
                const { Video } = CircleId.Content;
                handlePreviewVideo(Video.Url);
            }
            if (CircleId.Content && actionType.value == HandleEventEnum.PullCircleDetail) {
                const { Comments } = CircleId;
                circleList.value.forEach((item) => {
                    if (item.CircleId == CircleId.CircleId) {
                        item.Comments = Comments;
                    }
                });
            }
            actionType.value = null;
        },
        [MsgTypeEnum.DeleteSNSNewsTask]: () => {
            circleList.value = circleList.value.filter((item) => item.CircleId != circleId.value);
            circleId.value = null;
        },
        [MsgTypeEnum.CircleLikeTask]: () => {
            circleList.value.forEach((item) => {
                if (item.CircleId == circleId.value) {
                    if (isLiked.value) {
                        item.Likes = item.Likes.filter((like) => like.FriendId != Content.WeChatId);
                    } else {
                        item.Likes.push({
                            CircleId: "0",
                            FriendId: currentWechat.value?.wechat_id,
                            NickName: currentWechat.value?.wechat_nickname,
                        });
                    }
                }
            });
            circleId.value = null;
        },
        [MsgTypeEnum.CircleCommentReplyTaskResultNotice]: () => {
            feedback.loading("获取详情中...", containerRef.value);
            triggerTask(MsgTypeEnum.PullCircleDetailTask, {
                CircleId: circleId.value,
            });
            actionType.value = HandleEventEnum.PullCircleDetail;
        },
        [MsgTypeEnum.CircleCommentDeleteTaskResultNotice]: () => {
            circleList.value.forEach((item) => {
                if (item.CircleId == circleId.value) {
                    item.Comments = item.Comments.filter((comment) => comment.CommentId != Content.CommentId);
                }
            });
        },
        [MsgTypeEnum.CircleDelNotice]: () => {
            circleList.value = circleList.value.filter((item) => item.CircleId != circleId.value);
            circleId.value = null;
        },
        [MsgTypeEnum.CircleCommentNotice]: () => {
            circleList.value.forEach((item) => {
                if (item.CircleId == circleId.value) {
                    item.Comments.push(Content.Comment);
                }
            });
        },
    };
    if (handlers[MsgType]) {
        await handlers[MsgType](Content);
    }
});

// 监听ws错误
on("error", async (data: any) => {
    const { Code, MsgType, Message, Content } = data;
    if (
        [
            MsgTypeEnum.CircleCommentReplyTaskResultNotice,
            MsgTypeEnum.CircleLikeTask,
            MsgTypeEnum.DeleteSNSNewsTask,
            MsgTypeEnum.PullFriendCircleTask,
        ].includes(MsgType)
    ) {
        circleListsLoading.value = false;
        feedback.msgError(Message);
    } else if (Code == MsgErrorCodeEnum.DeviceOffline) {
        wechatLists.value.forEach((item) => {
            if (item.device_code == Content.DeviceId) {
                item.wechat_status = 0;
                item.loading = false;
            }
        });
    }
    if (MsgType == MsgTypeEnum.CircleCommentReplyTaskResultNotice || MsgType == MsgTypeEnum.CircleCommentReplyTask) {
        const { CircleId } = Content;
        circleList.value.forEach((item) => {
            if (item.CircleId == CircleId) {
                item.Comments = item.Comments.filter((comment) => comment.SendType != "web");
            }
        });
    }
    feedback.closeLoading();
});

// 监听动作事件
onHandleEvent("action", async (data: any) => {
    const { data: item, type } = data || {};
    const CircleId = `${item?.CircleId}`;
    circleId.value = CircleId;
    switch (type) {
        case HandleEventEnum.PreviewImage:
        case HandleEventEnum.PreviewVideo:
            if (actionType.value == HandleEventEnum.PreviewImage) {
                imageViewerIndex.value = item.imageIdx;
            }
            feedback.loading("获取详情中...", containerRef.value);
            triggerTask(MsgTypeEnum.PullCircleDetailTask, {
                CircleId,
                GetBigMap: true,
            });
            break;
        case HandleEventEnum.SendComment:
            const { reply } = item;
            feedback.loading("发送中...", containerRef.value);
            circleList.value.forEach((val) => {
                if (val.CircleId == CircleId) {
                    val.Comments.push({
                        Content: item.msg,
                        FromName: currentWechat.value?.wechat_nickname,
                        FromWeChatId: currentWechat.value?.wechat_id,
                        ToWeChatId: reply?.FromWeChatId,
                        CommentId: `${new Date().getTime()}`,
                        SendType: "web",
                    });
                }
            });

            triggerTask(MsgTypeEnum.CircleCommentReplyTask, {
                CircleId,
                ToWeChatId: reply?.FromWeChatId || item.WeChatId,
                Content: item.msg,
                IsResend: false,
                ReplyCommentId: reply?.CommentId,
            });
            break;
        case HandleEventEnum.Like:
            feedback.loading("点赞中...");
            isLiked.value = item.isLike;
            triggerTask(MsgTypeEnum.CircleLikeTask, {
                CircleId,
                IsCancel: item.isLike,
            });
            break;
        case HandleEventEnum.DeleteCircle:
            feedback.loading("删除中...", containerRef.value);
            triggerTask(MsgTypeEnum.DeleteSNSNewsTask, {
                CircleId,
                WeChatId: item.WeChatId,
            });
            break;
        case HandleEventEnum.DeleteComment:
            const { deleteComment } = item;
            feedback.loading("删除中...", containerRef.value);
            triggerTask(MsgTypeEnum.CircleCommentDeleteTask, {
                CircleId,
                CommentId: deleteComment.CommentId,
                PublishTime: deleteComment.PublishTime,
            });
            break;
    }
});

// =================================================================================================
// 组件特定逻辑和事件处理程序
// =================================================================================================

const handleSelectWeChat = (data: any) => {
    if (currentWechat.value?.device_code === data.device_code) return;
    currentWechat.value = data;
    circleList.value = [];
    circleParams.RefSnsId = 0;
    circleListsFinished.value = false;
    circleListsLoading.value = true;
    circleListsLoad.value = true;
    circleSendForm.wechat_ids = [data.wechat_id];
    PullFriendCircleTask();
};

const refreshCircle = () => {
    circleList.value = [];
    circleParams.RefSnsId = 0;
    circleListsFinished.value = false;
    circleListsLoading.value = true;
    circleListsLoad.value = true;
    PullFriendCircleTask();
};

const circleScrollEnd = () => {
    if (circleListsFinished.value || circleListsLoad.value) return;
    circleListsLoad.value = true;
    circleParams.RefSnsId = circleList.value[circleList.value.length - 1].CircleId;
    PullFriendCircleTask();
};

const handlePreviewVideo = async (url: string) => {
    showVideoViewer.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value.setUrl(url);
};

// =================================================================================================
// 初始化
// =================================================================================================

const getWeChatListsFn = async () => {
    const { lists } = await getWeChatLists({ page_type: 0 });
    wechatLists.value = lists;
    wechatAutoLogin();
};

const wechatAutoLogin = async () => {
    if (wechatLists.value && wechatLists.value.length > 0) {
        currentWechat.value = wechatLists.value.find((item) => item.wechat_status == 1);
        if (currentWechat.value) {
            circleSendForm.wechat_ids = [currentWechat.value?.wechat_id];
        }
        wechatLists.value.forEach((item) => {
            item.loading = true;
            if (item.wechat_status == 1) {
                triggerTask(MsgTypeEnum.Auth, {
                    deviceId: item.device_code,
                });
            } else {
                item.loading = false;
            }
        });
    }
};

onUnmounted(() => {
    wechatLists.value = [];
    currentWechat.value = {} as any;
});
</script>
<style scoped lang="scss">
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* 滚动条美化 */
.custom-scrollbar {
    &::-webkit-scrollbar {
        width: 4px;
    }
    &::-webkit-scrollbar-thumb {
        @apply bg-gray-200 rounded-full hover:bg-gray-300 transition-all;
    }
}

/* 覆盖组件样式 */
:deep(.sidebar-panel) {
    @apply h-full border-[none] bg-[transparent];
}

/* Loader 样式对齐 */
:deep(.loader) {
    transform: scale(0.85);
}
</style>
