import { cozeAgentChat, cozeAgentChatStream, cozeAgentChatView, cozeAgentChatMsgList } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import feedback from "@/utils/feedback";

// Coze聊天状态枚举
enum CozeChattingStatus {
    CREATED = "created",
    IN_PROGRESS = "in_progress",
    COMPLETED = "completed",
    FAILED = "failed",
    REQUIRES_ACTION = "requires_action",
    CANCELED = "canceled",
}

/**
 * @description Coze智能体聊天核心逻辑
 * @param detail 智能体详情
 * @param agentId 智能体ID
 */
export function useCozeChat(detail: Ref<any>, agentId: any) {
    const userStore = useUserStore();
    const { userInfo } = toRefs(userStore);

    const chatContentList = ref<any[]>([]);
    const isReceiving = ref(false);
    const isStopChat = ref(false);
    const streamReader = shallowRef<ReadableStreamDefaultReader<Uint8Array> | null>(null);
    const conversationId = ref("");

    const isStream = computed(() => detail.value?.stream == 1);

    // 发送消息
    const sendMessage = async (
        content: string,
        onConversationIdChange: (id: string) => void,
        onNewConversation: (conv: any) => void
    ) => {
        chatContentList.value.push({ type: 1, message: content, form_avatar: userInfo.value.avatar });
        const result: any = reactive({
            type: 2,
            loading: true,
            form_avatar: detail.value?.avatar,
            reply: "",
            consume_tokens: {},
        });
        chatContentList.value.push(result);
        isReceiving.value = true;

        const cozeParams = { id: agentId, content, conversation_id: conversationId.value };
        const isNewConversation = !conversationId.value;

        if (isStream.value) {
            // 流式处理
            try {
                await cozeAgentChatStream(cozeParams, {
                    onstart: (reader) => {
                        streamReader.value = reader;
                        isStopChat.value = true;
                    },
                    onmessage: (value) => {
                        value
                            .trim()
                            .split("data:")
                            .forEach((text) => {
                                if (!text) return;
                                try {
                                    const { object, content, usage, conversation_id: newConvId } = JSON.parse(text);
                                    if (content && object === "loading") result.reply += content;
                                    if (object === "finished") {
                                        result.loading = false;
                                        result.consume_tokens = { total_tokens: usage.token_count };
                                        if (newConvId && conversationId.value !== newConvId) {
                                            conversationId.value = newConvId;
                                            onConversationIdChange(newConvId);
                                        }
                                    }
                                } catch {}
                            });
                    },
                    onclose: () => {
                        result.loading = false;
                        userStore.getUser();
                        isReceiving.value = false;
                        isStopChat.value = false;

                        if (isNewConversation) {
                            onNewConversation({
                                id: conversationId.value,
                                title: content,
                                conversation_id: conversationId.value,
                                content: content,
                            });
                        }
                    },
                });
            } catch (error) {
                result.loading = false;
                result.reply = (error as string) || "发生错误";
                feedback.msgError((error as string) || "发生错误");
                isReceiving.value = false;
                isStopChat.value = false;
            }
        } else {
            // 非流式（轮询）处理
            try {
                isStopChat.value = true;
                const { conversation_id: newConvId, id: chatId } = await cozeAgentChat(cozeParams);
                if (newConvId && conversationId.value !== newConvId) {
                    conversationId.value = newConvId;
                    onConversationIdChange(newConvId);
                }

                const pollParams = { id: agentId, conversation_id: conversationId.value };
                const { start, end } = usePolling(async () => {
                    try {
                        const { status, id: chatDetailId } = await cozeAgentChatView({
                            chat_id: chatId,
                            ...pollParams,
                        });
                        if (status === CozeChattingStatus.COMPLETED) {
                            end();
                            const data = await cozeAgentChatMsgList({ chat_id: chatDetailId, ...pollParams });
                            if (data && data.length) {
                                data.forEach((item: any) => (result.reply += item.content));
                            }
                            result.loading = false;
                            resetChat();

                            if (isNewConversation) {
                                onNewConversation({
                                    id: conversationId.value,
                                    title: content,
                                    conversation_id: conversationId.value,
                                    content: content,
                                });
                            }
                        } else if (status === CozeChattingStatus.FAILED) {
                            end();
                            throw new Error("聊天失败");
                        }
                    } catch (error) {
                        end();
                        result.loading = false;
                        result.reply = "聊天失败，请稍后重试";
                        throw new Error("聊天失败");
                    }
                }, {});
                start();
            } catch (error) {
                result.loading = false;
                result.reply = (error as string) || "发生错误";
                feedback.msgError((error as string) || "发生错误");
                resetChat();
            }
        }
    };

    const resetChat = () => {
        isReceiving.value = false;
        isStopChat.value = false;
    };

    // 停止聊天
    const stopChat = () => {
        if (isStream.value) {
            streamReader.value?.cancel();
            isReceiving.value = false;
            isStopChat.value = false;
        } else {
            feedback.msgWarning("直接返回类型，聊天不可取消~");
        }
    };

    // 设置对话ID
    const setConversationId = (id: string) => {
        conversationId.value = id;
    };

    return {
        chatContentList,
        isReceiving,
        isStopChat,
        sendMessage,
        stopChat,
        setConversationId,
    };
}
