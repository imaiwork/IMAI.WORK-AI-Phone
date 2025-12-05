/**
 * 处理回复策略
 */
import { messageSend, getWeChatFriend } from "@/api/person_wechat";
import { ContentTypeEnum, TalkActionTypeEnum, EnumTalkType } from "../../_enums";
import useHandle from "../../_hooks/useHandle";
type TalkEvent = "post" | "get";

interface TalkReplyTaskParams {
    WeChatId: string;
    FriendId: string;
    NickName: string;
    Content: string;
    ContentType: ContentTypeEnum;
    DeviceId: string;
    MsgSvrId: string;
    MsgId: number;
    IsSend: boolean;
}

interface SendMessageParams {
    wechat_id: string;
    friend_id: string;
    message_type: number;
    message: string;
    message_logs: any[];
    message_id: string;
}

interface TalkReplyTaskData {
    taskId: string;
    wechatId: string;
    deviceId: string;
    friendId: string;
    content: string;
    historyMsgLists: any[];
    msgSvrId: string;
    messageId: string;
    isHistory: boolean;
    historyTaskId: string;
    firstMessageId: string;
}

// 合并、只回复定时时间
const MERGE_REPLY_TASK_TIME = 2 * 60 * 1000;

// 事件回调存储
const eventHandlers = new Map<TalkEvent, (data: any) => void>();

const { wechatLists, wechatAiInfo, replyStrategyInfo, getWeChatAiInfo, addFriend } = useHandle();

export default function useTalkReplyTask() {
    // 回复任务定时器
    const talkReplyTaskTimer = ref<Record<string, NodeJS.Timeout | null>>({});
    // 回复任务数据集
    const talkReplyTaskData = reactive<Record<string, TalkReplyTaskData>>({});
    // 合并回复任务数据集
    const mergeReplyTaskData = reactive<Record<string, SendMessageParams>>({});
    // 合并回复任务定时器
    const mergeReplyTaskTimer = ref<Record<string, NodeJS.Timeout | null>>({});

    // 获取当前微信的信息
    const getWechatInfo = (wechatId: string) => {
        const { accessToken, wechat_id } = wechatLists.value.find((item) => item.wechat_id === wechatId) || {};
        return { accessToken, wechat_id };
    };

    // 发送消息,删除任务
    const messageSendFn = async (params: SendMessageParams, taskId: any) => {
        const { multiple_type } = replyStrategyInfo.value || {};
        try {
            await messageSend(params);
        } finally {
            if (multiple_type == EnumTalkType.Single) {
                delete talkReplyTaskData[taskId];
            }
            if (multiple_type == EnumTalkType.Merge || multiple_type == EnumTalkType.Number) {
                delete mergeReplyTaskData[taskId];
            }
        }
    };

    // 创建一个任务队列
    const createTalkReplyTask = async (result: TalkReplyTaskParams) => {
        const { WeChatId, DeviceId, FriendId, NickName, Content, ContentType, MsgSvrId, MsgId, IsSend } = result;

        const {
            multiple_type,
            number_chat_rounds,
            voice_enable,
            image_enable,
            image_reply,
            stop_enable,
            stop_keywords,
        } = replyStrategyInfo.value || {};

        // 获取当前微信的AI信息

        const { open_ai: wechatOpenAi, takeover_mode: wechatTakeoverMode } = wechatAiInfo.value[WeChatId] || {};

        const getFriendInfo = async (): Promise<any> => {
            return new Promise(async (resolve) => {
                try {
                    const info = await getWeChatFriend({
                        wechat_id: WeChatId,
                        friend_id: FriendId,
                    });
                    resolve(info);
                } catch (error) {
                    const data: any = await addFriend({
                        wechat_id: WeChatId,
                        friend_id: FriendId,
                        nickname: NickName,
                        open_ai: 1,
                        takeover_mode: 1,
                    });
                    resolve(data);
                }
            });
        };

        const { open_ai, takeover_mode } = await getFriendInfo();

        // console.log("当前微信的takeover_mode", takeover_mode);
        // console.log("当前微信的open_ai", open_ai);
        // console.log("好友的takeover_mode", wechatTakeoverMode);
        // console.log("好友的open_ai", wechatOpenAi);
        // console.log("multiple_type", multiple_type);

        // 判断是否需要创建任务, 前置条件是当前微信的open_ai == 1 && takeover_mode == 1 和好友的open_ai == 1 && takeover_mode == 1
        if (wechatOpenAi == 1 && wechatTakeoverMode == 1 && open_ai == 1 && takeover_mode == 1) {
            // 获取当前微信的accessToken
            const { accessToken } = getWechatInfo(WeChatId);

            /**
             * 1. 先判断消息的内容是文本、语音、图片
             * 2. 如果是文本，则将文本推送给回复策略任务
             * 3. 如果是语音，则将语音转换为文字，然后推送给回复策略任务
             * 4. 如果是图片，则判断是否需要回复,如果需要回复直接发送图片设定的内容
             */

            const taskId = `${Date.now()}`;

            // 基础事件参数
            const baseEventParams = {
                taskId,
                accessToken,
                wechatId: WeChatId,
                deviceId: DeviceId,
                friendId: FriendId,
                content: Content,
                talkType: multiple_type,
            };

            // 历史记录参数
            const historyEventParams = {
                ...baseEventParams,
                type: TalkActionTypeEnum.PostHistory,
                count: number_chat_rounds + 1,
                messageId: MsgSvrId,
                firstMessageId: MsgSvrId,
            };

            // 判断消息的内容是文本、语音、图片
            if ([ContentTypeEnum.Text, ContentTypeEnum.Voice, ContentTypeEnum.QuoteMsg].includes(ContentType)) {
                // 追条回复
                if (multiple_type == EnumTalkType.Single) {
                    if (ContentType === ContentTypeEnum.Text || ContentType === ContentTypeEnum.QuoteMsg) {
                        // 判断是否需要回复,如果需要回复直接发送文本设定的内容
                        if (stop_keywords === Content) return;

                        if (ContentType === ContentTypeEnum.QuoteMsg) {
                            const result = JSON.parse(Content);
                            historyEventParams.content = result.title;
                        }
                        triggerTalkEvent("post", historyEventParams);
                    }
                    if (ContentType === ContentTypeEnum.Voice) {
                        if (voice_enable == 1) {
                            // 语音转文字
                            triggerTalkEvent("post", {
                                type: TalkActionTypeEnum.VoiceToText,
                                ...baseEventParams,
                                messageId: MsgSvrId,
                            });
                        }
                        // 推送历史记录
                        triggerTalkEvent("post", historyEventParams);
                    }
                }
                // 合并回复
                if (multiple_type == EnumTalkType.Merge || multiple_type == EnumTalkType.Number) {
                    /**
                     * 功能： 接收到用户最后一条消息时开始进入监听，>2分钟未有消息，则将开头信息到结束信息进行整合合并，并开始执行调用Ai回复
                     * 1. 接收到用户第一条消息时开始进入监听
                     * 2. 判断消息是不是文本，目前只处理文本消息
                     * 3. 监听消息是否超过2分钟未有消息，如果超过2分钟未有消息，则将开头信息到结束信息进行整合合并，并开始执行调用Ai回复
                     */

                    // 监听消息
                    const listenMessage = () => {
                        const key = `${WeChatId}${FriendId}`;

                        // 判断是否存在定时器,如果存在则清除
                        if (mergeReplyTaskTimer.value[key]) {
                            clearTimeout(mergeReplyTaskTimer.value[key]);
                        }

                        //  判断消息是否是文本，并且是用户发送的消息
                        if (ContentType === ContentTypeEnum.Text && !IsSend) {
                            // 判断是否存在任务数据
                            if (!mergeReplyTaskData[key]) {
                                mergeReplyTaskData[key] = {
                                    wechat_id: WeChatId,
                                    friend_id: FriendId,
                                    message_logs: [],
                                    message_id: "",
                                    message_type: 0,
                                    message: "",
                                };
                            }

                            mergeReplyTaskData[key].message_id = MsgSvrId;
                            mergeReplyTaskData[key].message = Content;
                            if (multiple_type == 1) {
                                const id = mergeReplyTaskData[key].message_logs.length + 1;
                                const taskId = `${Date.now()}${id}`;

                                mergeReplyTaskData[key].message_logs.push({
                                    content: Content,
                                    role: "user",
                                    id,
                                });
                            }
                            if (multiple_type == 2) {
                                mergeReplyTaskData[key].message_logs[0] = {
                                    content: Content,
                                    role: "user",
                                };
                            }

                            // 设置定时器
                            mergeReplyTaskTimer.value[key] = setTimeout(() => {
                                // 发送消息
                                messageSendFn(mergeReplyTaskData[key], key);
                            }, MERGE_REPLY_TASK_TIME);
                        }
                    };

                    // 监听消息
                    listenMessage();
                }
            } else if (ContentType === ContentTypeEnum.Picture) {
                if (!image_enable) return;
                triggerTalkEvent("post", {
                    type: TalkActionTypeEnum.PostPicture,
                    ...baseEventParams,
                    content: image_reply,
                });
            }
        }
    };
    // 删除所有任务
    const deleteAllTalkReplyTask = () => {
        talkReplyTaskTimer.value = null;
        mergeReplyTaskData.value = null;
        mergeReplyTaskTimer.value = null;
    };

    // 事件触发器
    const triggerTalkEvent = <D = unknown>(event: TalkEvent, data?: D) => {
        const handler = eventHandlers.get(event);
        if (handler) handler(data!);
    };

    const onTalkEvent = <D = unknown>(event: TalkEvent, callback: (data: D) => void) => {
        eventHandlers.set(event, callback);
    };

    // 获取历史消息
    onTalkEvent("get", (result: any) => {
        const { type, taskId, data } = result;
        const { friendId, wechatId, deviceId, messageId, content, firstMessageId, historyMsgLists } =
            talkReplyTaskData[taskId] || {};

        const { accessToken } = getWechatInfo(wechatId);
        const { voice_enable, multiple_type } = replyStrategyInfo.value || {};

        const handleVoiceMessages = () => {
            historyMsgLists.forEach((item: any, index: number) => {
                const isVoiceMessage = item.ContentType === ContentTypeEnum.Voice;
                item.loading = isVoiceMessage;

                if (isVoiceMessage) {
                    item.historyTaskId = index + 1;
                    triggerTalkEvent("post", {
                        type: TalkActionTypeEnum.VoiceToText,
                        talkType: multiple_type,
                        wechatId,
                        deviceId,
                        friendId,
                        messageId: item.MsgSvrId,
                        accessToken,
                        taskId: `${taskId}${item.historyTaskId}`,
                        historyTaskId: item.historyTaskId,
                    });
                }
            });
        };

        // 处理消息内容并生成消息日志
        const generateMessageLogs = (messages: any[]) => {
            return messages
                .map((item) => {
                    const { IsSend, ContentType, Content, content } = item;
                    const role = IsSend ? "assistant" : "user";
                    let messageContent = Content;

                    if (ContentType === ContentTypeEnum.QuoteMsg) {
                        messageContent = JSON.parse(Content)?.title;
                    } else if (ContentType === ContentTypeEnum.Voice) {
                        messageContent = content;
                    }

                    return {
                        role,
                        content: messageContent,
                    };
                })
                .reverse();
        };

        // 发送消息到策略
        const sendMessageToStrategy = (messages: any[]) => {
            const message_logs = generateMessageLogs(messages);
            messageSendFn(
                {
                    wechat_id: wechatId,
                    friend_id: friendId,
                    message_type: 0,
                    message_id: firstMessageId,
                    message: content,
                    message_logs,
                },
                taskId
            );
        };

        // 处理语音转文字结果
        const handleVoiceToTextResult = (lists: any[]) => {
            const isAllProcessed = lists?.every((item) => !item.loading);
            if (isAllProcessed) {
                const message_logs = generateMessageLogs(lists);
                messageSendFn(
                    {
                        wechat_id: wechatId,
                        friend_id: friendId,
                        message_type: 0,
                        message_id: firstMessageId,
                        message: content,
                        message_logs,
                    },
                    taskId
                );
            }
        };

        switch (type) {
            case TalkActionTypeEnum.GetHistory: {
                // 这里要根据 voice_enable  来判断是否需要处理语音消息, 如果 voice_enable == 1 则需要处理语音消息, 否则直接发送消息到策略
                const newMsgLists =
                    voice_enable === 1
                        ? historyMsgLists
                        : historyMsgLists.filter((item) => item.ContentType !== ContentTypeEnum.Voice);

                const hasVoiceMessages = newMsgLists.some((item) => item.ContentType === ContentTypeEnum.Voice);
                hasVoiceMessages ? handleVoiceMessages() : sendMessageToStrategy(newMsgLists);
                break;
            }

            case TalkActionTypeEnum.VoiceToTextResult:
                if (multiple_type == EnumTalkType.Single) {
                    const { TaskId, ErrMsg } = data;
                    // 处理历史消息和普通消息的语音转文字结果
                    const isHistoryTask = TaskId.length === 14;
                    const taskId = isHistoryTask ? TaskId.slice(0, -1) : TaskId;
                    const currentTask = talkReplyTaskData[taskId];
                    if (currentTask) {
                        if (!isHistoryTask) {
                            currentTask.content = ErrMsg;
                        }

                        if (isHistoryTask) {
                            const historyTaskId = TaskId.slice(-1);
                            const { historyMsgLists } = currentTask;

                            // 更新历史消息的加载状态
                            const targetMsg = historyMsgLists.find((item: any) => item.historyTaskId == historyTaskId);
                            if (targetMsg) {
                                targetMsg.loading = false;
                                targetMsg.content = ErrMsg;
                            }
                            handleVoiceToTextResult(historyMsgLists);
                        }
                    }
                } else if (multiple_type == EnumTalkType.Merge) {
                    const { TaskId, ErrMsg, WeChatId } = data;
                    const isHistoryTask = TaskId.length === 14;
                    const taskId = isHistoryTask ? TaskId.slice(0, -1) : TaskId;
                }
                break;
        }
    });

    return {
        talkReplyTaskData,
        createTalkReplyTask,
        deleteAllTalkReplyTask,
        triggerTalkEvent,
        onTalkEvent,
    };
}
