import { storeToRefs } from "pinia";
import { chatSendTextStream, getChatLog } from "@/api/chat";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { useChatStore, type ChatMessage, type ChatFile } from "../stores/chat";

/**
 * @description useChatManager Composable
 *
 * 核心聊天逻辑的协调器。负责：
 * - 初始化聊天状态 (根据URL参数或新会话)。
 * - 处理用户消息的发送 (包括文本和文件)。
 * - 管理与后端API的流式通信。
 * - 拉取和显示历史聊天记录。
 * - 协调其他 store (user, app, chat) 和 UI 组件 (chattingRef)。
 */
export function useChatManager() {
    const route = useRoute();
    const chatStore = useChatStore();
    const userStore = useUserStore();
    const appStore = useAppStore();

    // --- 从 Store 中获取响应式状态 ---
    const { taskId, agentValue, chatContentList, isReceiving, isStopChat, isDeep, isNetwork, fileLists, extraParams } =
        storeToRefs(chatStore);
    const { userTokens, userInfo } = storeToRefs(userStore);
    // [FIX] 恢复到原始的 chatConfig 处理方式，以解决响应式系统错误
    const { chatConfig } = appStore;

    // --- 本地响应式状态 ---

    /**
     * @description 聊天组件的引用 (用于调用其内部方法，如滚动到底部)。
     */
    const chattingRef = shallowRef<any>(null);

    /**
     * @description 可读流的读取器，用于手动中断流。
     */
    const streamReader = shallowRef<ReadableStreamDefaultReader<Uint8Array> | null>(null);

    // --- 私有方法 ---

    /**
     * @description 滚动聊天窗口到底部。
     */
    const chatScrollToBottom = () => {
        nextTick(() => chattingRef.value?.scrollToBottom());
    };

    /**
     * @description 重置浏览器URL，清除查询参数。
     */
    const resetURLPath = () => {
        replaceState({
            task_id: undefined,
            agent_name: undefined,
            agent_id: undefined,
        });
    };

    /**
     * @description 处理流式响应的数据块。
     * @param value - 从流中读取的数据。
     */
    const _handleStreamMessage = (value: string) => {
        value
            .trim()
            .split("data:")
            .forEach((text) => {
                if (!text) return;
                try {
                    const { object, content, task_id: newTaskId, usage, reasoning_content } = JSON.parse(text);
                    if (!taskId.value) {
                        chatStore.setTaskId(newTaskId);
                        replaceState({
                            task_id: newTaskId,
                            agent_name: agentValue.value?.name,
                            agent_id: agentValue.value?.id,
                        });
                    }
                    const lastMessage = chatContentList.value[chatContentList.value.length - 1];
                    if (object === "loading") {
                        const update: Partial<ChatMessage> = {};
                        if (reasoning_content) {
                            update.is_reasoning_finished = false;
                            update.reasoning_content = (lastMessage.reasoning_content || "") + reasoning_content;
                        } else if (content) {
                            update.is_reasoning_finished = true;
                            update.reply = (lastMessage.reply || "") + content;
                        }
                        chatStore.updateLastMessage(update);
                    } else if (object === "finished") {
                        chatStore.updateLastMessage({ consume_tokens: usage });
                    }
                } catch (e) {}
                chatScrollToBottom();
            });
    };

    // --- 公开方法 ---

    /**
     * @description 根据 task_id 获取并显示历史聊天记录。
     */
    const fetchChatHistory = async () => {
        if (!taskId.value || taskId.value === "undefined") return;

        chatStore.isLoading = true;
        try {
            const data = await getChatLog({
                page_no: 1,
                page_size: 9999,
                task_id: taskId.value,
                assistant_id: 0,
            });
            const historyMessages: ChatMessage[] =
                data?.map(
                    (item: any): ChatMessage =>
                        item.type === 1
                            ? {
                                  ...item,
                                  form_avatar: userInfo.value.avatar,
                                  fileList: item?.file_info
                                      ? Array.isArray(item.file_info)
                                          ? item.file_info
                                          : [item.file_info]
                                      : [],
                              }
                            : {
                                  ...item,
                                  is_reasoning_finished: true,
                                  // [FIX] 恢复原始的属性访问方式
                                  form_avatar: chatStore.detail.logo,
                                  consume_tokens: item.tokens_info,
                              }
                ) ?? [];

            chatStore.chatContentList = historyMessages;
            chatScrollToBottom();
        } catch (err) {
            feedback.msgError("获取聊天记录失败");
        } finally {
            chatStore.isLoading = false;
        }
    };

    /**
     * @description 发送消息的核心函数。
     * @param userInput - 用户输入的文本。
     * @param isNewChatPrompt - 是否为新会话的预设提示语。
     */
    const sendMessage = async (userInput: string, isNewChatPrompt = false, cb?: () => void) => {
        if (userTokens.value <= 1) return feedback.msgPowerInsufficient();
        if (isReceiving.value || (!userInput.trim() && fileLists.value.length === 0)) return;
        // 1. 准备用户消息和机器人占位消息
        if (!isNewChatPrompt) {
            chatStore.addMessage({
                type: 1,
                message: userInput,
                form_avatar: userInfo.value.avatar,
                fileList: fileLists.value,
            });
        }

        const botMessage: ChatMessage = {
            type: 2,
            loading: true,
            form_avatar: chatStore.detail.logo,
            is_reasoning_finished: isDeep.value,
            error: "",
            reply: "",
            reasoning_content: "",
            consume_tokens: {},
        };
        chatStore.addMessage(botMessage);
        chatStore.startReceiving();
        chatScrollToBottom();

        // 2. 发起API请求
        try {
            await chatSendTextStream(
                {
                    message: userInput,
                    task_id: taskId.value,
                    robot_id: agentValue.value?.id,
                    open_reasoning: isDeep.value ? 1 : 0,
                    is_network_search: isNetwork.value ? 1 : 0,
                    file_info: fileLists.value.length ? fileLists.value[0] : undefined,
                    ...(chattingRef.value?.getChatConfig?.() || {}),
                    ...extraParams.value,
                },
                {
                    onstart: (reader) => {
                        streamReader.value = reader;
                    },
                    onmessage: _handleStreamMessage,
                    onclose: () => {
                        chatStore.updateLastMessage({ loading: false });
                        chatStore.stopReceiving();
                        chatStore.clearFiles();
                        userStore.getUser(); // 刷新用户信息（例如，token消耗）
                        chatScrollToBottom();
                        cb?.();
                    },
                }
            );
        } catch (error: any) {
            const errorMessage = error || "消息发送失败";
            chatStore.updateLastMessage({ error: errorMessage, loading: false });
            feedback.msgError(errorMessage);
            chatStore.stopReceiving();
        } finally {
            chatScrollToBottom();
        }
    };

    /**
     * @description 开始一个全新的会话。
     */
    const startNewChat = () => {
        if (!taskId.value) return feedback.msgError("当前已是新会话");
        chatStore.clearChat();
        resetURLPath();
        chattingRef.value?.cleanInput?.(); // 清理输入框组件的内容

        // 如果有新会话的默认提示语，则自动发送
        if (chatConfig.value?.new_chat_prompt) {
            sendMessage(chatConfig.value.new_chat_prompt, true);
        }
    };

    /**
     * @description 手动停止正在进行的流式响应。
     */
    const stopStream = () => {
        streamReader.value?.cancel();
        if (isReceiving.value) {
            const lastMessage = chatContentList.value[chatContentList.value.length - 1];
            chatStore.updateLastMessage({
                loading: false,
                reply: lastMessage.reply || "用户已停止内容生成",
            });
            chatStore.stopReceiving();
        }
    };

    /**
     * @description 重置滚动
     */
    const resetScroll = () => chattingRef.value?.resetScroll();

    /**
     * @description 初始化函数，在组件挂载时调用。
     * 根据URL中的查询参数决定加载历史记录还是发送新消息。
     */
    const initialize = async () => {
        chatStore.clearChat();

        const { content, task_id: routeTaskId } = route.query;

        if (content) {
            // 如果URL带有 content, 则直接发送
            await sendMessage(content as string);
            resetURLPath();
        } else if (routeTaskId && routeTaskId !== "undefined") {
            // 如果URL带有 task_id, 则加载历史记录
            chatStore.setTaskId(routeTaskId as string);
            await fetchChatHistory();
        }
        chatScrollToBottom();
    };

    return {
        // Refs
        chattingRef,

        // Store State (从 storeToRefs 获取)
        isDeep,
        isNetwork,
        fileLists,
        taskId,
        chatContentList,
        isReceiving,
        isStopChat,

        // Methods
        initialize,
        sendMessage,
        startNewChat,
        stopStream,
        chatScrollToBottom,
        resetScroll,
        fetchChatHistory,
        // 文件相关方法现在通过 chatStore 处理
        setFiles: chatStore.setFiles,
        clearFiles: chatStore.clearFiles,
    };
}
