import { storeToRefs } from "pinia";
import { chatSendTextStream, getChatLog } from "@/api/chat";
import { getAgentDetail } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { useChatStore, type ChatMessage } from "../stores/chat";
import { useChatEventBus } from "./useChatEventBus";
import { AGENT_UNAVAILABLE_TIP, canUseAgent } from "@/utils/agentPermission";
import dayjs from "dayjs";
import { cancelRequestsByUrl } from "@/utils/http/cancel";
import { handleSseFrames } from "@/utils/http/sse-frame";

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

    const { chattingRef } = storeToRefs(chatStore);

    const { triggerHistoryRefresh } = useChatEventBus();

    // --- 从 Store 中获取响应式状态 ---
    const { taskId, agentValue, chatContentList, isReceiving, isStopChat, isDeep, isNetwork, fileLists, extraParams } =
        storeToRefs(chatStore);
    const { userTokens, userInfo } = storeToRefs(userStore);
    const { getChatData: chatConfig } = storeToRefs(appStore);

    // --- 本地响应式状态 ---

    /**
     * @description 聊天组件的引用 (用于调用其内部方法，如滚动到底部)。
     */

    /**
     * @description 可读流的读取器，用于手动中断流。
     */
    const streamReader = shallowRef<ReadableStreamDefaultReader<Uint8Array> | null>(null);

    // --- 私有方法 ---

    /**
     * @description 处理流式响应的数据块。
     * @param value - 从流中读取的数据。
     */
    const _handleStreamMessage = (value: string) => {
        handleSseFrames(value, (dataJson) => {
            const {
                object,
                content,
                task_id: newTaskId,
                usage,
                reasoning_content,
                check_robot_id,
            } = dataJson;
            if (newTaskId && !taskId.value) {
                const firstMessage = chatContentList.value[0];
                if (firstMessage) {
                    triggerHistoryRefresh({
                        taskId: newTaskId,
                        message: firstMessage?.message,
                        createTime: dayjs().format("YYYY-MM-DD HH:mm:ss"),
                    });
                }
                chatStore.replaceTaskId(newTaskId);
                replaceState({
                    task_id: newTaskId,
                    agent_id: agentValue.value?.id,
                });
            } else if (newTaskId && newTaskId !== taskId.value) {
                return;
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
                if (check_robot_id) {
                    verifyAndBindCheckRobot(check_robot_id);
                }
                chatStore.updateLastMessage({ consume_tokens: usage });
            }
            chatScrollToBottom();
        });
    };

    /**
     * @description 校验流式返回的 check_robot_id 是否为当前用户可用智能体。
     * 仅会员可用的智能体若用户无权限，则提示并跳过绑定。
     */
    const verifyAndBindCheckRobot = async (robotId: string | number) => {
        try {
            const agentDetail = await getAgentDetail({ id: robotId });
            if (!canUseAgent(agentDetail, userInfo.value)) {
                feedback.msgWarning(AGENT_UNAVAILABLE_TIP);
                return;
            }
            chattingRef.value?.setSelectedAgent(robotId);
        } catch (error) {
            console.error("校验智能体权限失败:", error);
        }
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
                                  form_avatar: item.avatar || agentValue.value?.image || chatConfig.value?.logo,
                                  consume_tokens: item.tokens_info,
                              },
                ) ?? [];

            chatStore.setMessages(historyMessages);
            chatScrollToBottom();
        } finally {
            chatStore.isLoading = false;
        }
    };

    /**
     * @description 发送消息的核心函数。
     * @param userInput - 用户输入的文本。
     * @param isNewChatPrompt - 是否为新会话的预设提示语。
     */
    const sendMessage = async (
        userInput: string,
        isNewChatPrompt = false,
        cb?: () => void,
        chattingConfigOverride?: Record<string, any>,
        /** 欢迎页等场景直接传入附件，避免只依赖 store 时被清空/覆盖 */
        filesOverride?: any[],
    ) => {
        if (filesOverride?.length) {
            chatStore.setFiles(filesOverride);
        }
        // 快照附件，后续 clearFiles / 配置展开都不能丢掉
        const pendingFiles = [...(filesOverride?.length ? filesOverride : fileLists.value)];
        if (userTokens.value <= 1) return feedback.msgPowerInsufficient();
        if (isReceiving.value || (!userInput.trim() && pendingFiles.length === 0)) return;
        // 1. 准备用户消息和机器人占位消息
        if (!isNewChatPrompt) {
            chatStore.addMessage({
                type: 1,
                message: userInput,
                form_avatar: userInfo.value.avatar,
                fileList: pendingFiles,
                quotes: extraParams.value.quotes,
            });
        }
        const botMessage: ChatMessage = {
            type: 2,
            loading: true,
            form_avatar: agentValue.value?.image || chatStore.detail.logo || chatConfig.value?.logo,
            is_reasoning_finished: isDeep.value,
            error: "",
            reply: "",
            reasoning_content: "",
            consume_tokens: {},
        };
        chatStore.addMessage(botMessage);
        chatStore.startReceiving();
        chattingRef.value?.clearQuote();
        resetScroll();
        chatScrollToBottom();
        // 2. 发起API请求
        try {
            const chattingParams = chattingConfigOverride ?? chattingRef.value?.getChatConfig?.() ?? {};
            const fileInfo = pendingFiles.length ? pendingFiles[0] : undefined;
            await chatSendTextStream(
                {
                    message: userInput,
                    task_id: taskId.value,
                    open_reasoning: isDeep.value ? 1 : 0,
                    is_network_search: isNetwork.value ? 1 : 0,
                    ...chattingParams,
                    ...extraParams.value,
                    robot_id: chattingParams.robot_id || agentValue.value?.id,
                    // 必须放在展开之后，防止 getChatConfig / extraParams 冲掉附件
                    file_info: fileInfo,
                },
                {
                    onstart: (reader) => {
                        streamReader.value = reader;
                    },
                    onmessage: _handleStreamMessage,
                    onclose: () => {
                        streamReader.value = null;
                        chatStore.updateLastMessage({ loading: false });
                        chatStore.stopReceiving();
                        chatStore.clearFiles();
                        userStore.getUser(); // 刷新用户信息（例如，token消耗）
                        setTimeout(() => {
                            chatScrollToBottom();
                        }, 100);
                        cb?.();
                    },
                },
            );
        } catch (error: any) {
            chatStore.stopReceiving();
            if (error?.type == "cancel") return;
            const errorMessage = error?.type == "cancel" ? "用户已停止内容生成" : error || "消息发送失败";
            chatStore.updateLastMessage({ error: errorMessage, loading: false });
            // 错误气泡渲染后滚到底部，避免异常态停在半截
            setTimeout(() => {
                chatScrollToBottom();
            }, 100);
        }
    };

    /**
     * @description 开始一个全新的会话。
     */
    const startNewChat = () => {
        if (!taskId.value) return feedback.msgWarning("当前已是新会话");
        chatStore.clearChat();
        resetURLPath();
        chattingRef.value?.cleanInput?.(); // 清理输入框组件的内容
        chattingRef.value?.clearQuote?.(); // 清理引用内容
        // 如果有新会话的默认提示语，则自动发送
        if (chatConfig.value?.new_chat_prompt) {
            sendMessage(chatConfig.value.new_chat_prompt, true);
        }
    };

    /**
     * @description 滚动聊天窗口到底部。
     */
    let scrollFrame = 0;
    const chatScrollToBottom = () => {
        // SSR 阶段没有可滚动的 DOM
        if (typeof requestAnimationFrame === "undefined") return;
        // 流式回复每帧都会调这里，用 rAF 合并成一帧一次，避免反复读 scrollHeight 触发 reflow
        if (scrollFrame) return;
        scrollFrame = requestAnimationFrame(() => {
            scrollFrame = 0;
            chattingRef.value?.scrollToBottom();
        });
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
     * @description 手动停止正在进行的流式响应。
     */
    const stopStream = async () => {
        if (streamReader.value) {
            await streamReader.value.cancel();
            streamReader.value = null;
        } else {
            cancelRequestsByUrl("/chat/commonChat");
        }
        if (isReceiving.value) {
            const lastMessage = chatContentList.value[chatContentList.value.length - 1];
            chatStore.updateLastMessage({
                loading: false,
                // 只放提示语：stop_reply 在气泡里是纯文本渲染，
                // 把已生成的正文搬进来会让渲染好的 Markdown 变成一坨灰色斜体纯文本
                stop_reply: lastMessage?.reply ? "（已停止生成）" : "用户已停止内容生成",
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
            chatStore.replaceTaskId(routeTaskId as string);
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
