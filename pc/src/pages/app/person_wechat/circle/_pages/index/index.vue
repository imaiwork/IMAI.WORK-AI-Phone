<template>
    <div class="w-full h-full">
        <div class="h-full flex gap-x-4">
            <div class="flex-1 flex rounded-xl overflow-hidden" ref="containerRef">
                <div class="w-[94px] flex-shrink-0">
                    <SidebarPanel
                        ref="sidebarPanelRef"
                        :current-wechat="currentWechat"
                        :showAddWeChat="false"
                        :wechat-list="wechatLists"
                        @update:current-wechat="handleSelectWeChat" />
                </div>
                <div class="flex-1 flex flex-col">
                    <div class="flex justify-between gap-x-4 h-[48px] items-center bg-[#EDEDED] px-4 flex-shrink-0">
                        <ElAlert type="warning" :closable="false">
                            <div>朋友圈更新存在一定延迟，如有数据不同步，请多刷新</div>
                        </ElAlert>
                        <ElButton
                            link
                            class="!p-2"
                            color="#878787"
                            :loading="circleListsLoading"
                            @click="refreshCircle">
                            <Icon name="el-icon-Refresh" />
                            <span class="ml-2">刷新</span>
                        </ElButton>
                    </div>
                    <div class="grow min-h-0 bg-white relative">
                        <div
                            v-if="circleListsLoading"
                            class="absolute w-full h-full z-[888] bg-white flex justify-center items-center">
                            <loader />
                        </div>
                        <div
                            v-if="currentWechat && currentWechat.wechat_status == 2"
                            class="absolute w-full h-full flex items-center justify-center z-[999] bg-black/5">
                            <div class="flex flex-col items-center">
                                <Icon name="local-icon-wifi_off" :size="50" color="#ffffff"></Icon>
                                <span class="text-white text-2xl mt-2">设备离线</span>
                            </div>
                        </div>
                        <CircleLists
                            ref="circleListsRef"
                            :circle-list="circleList"
                            @bottom="circleScrollEnd"
                            @preview-video="handlePreviewVideo" />
                        <div
                            v-if="circleListsLoad"
                            class="absolute bottom-0 h-[50px] bg-white z-30 w-full flex justify-center items-center">
                            <div class="chat-loader"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-[462px] rounded-xl overflow-hidden">
                <CircleSend v-model="circleSendForm" :is-show-we-chat="false" @success="refreshCircle()" />
            </div>
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
    content: "",
    task_type: 0,
    attachment_type: -1,
    attachment_content: null,
    comment: "",
    send_time: dayjs().add(30, "minute").format("YYYY-MM-DD HH:mm:ss"),
    wechat_ids: [],
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

<style scoped></style>
