import { getChatRecord, deleteChatRecord } from "@/api/chat";
import { useChatStore } from "../stores/chat";
import { useChatManager } from "./useChatManager";

/**
 * @description 聊天历史记录项的接口
 */
export interface ChatHistoryItem {
    task_id: string;
    message: string;
    create_time: string;
    update_time: string;
}

/**
 * @description useChatHistory Composable
 *
 * 管理聊天会话历史记录的功能，包括：
 * - 获取会话历史列表
 * - 创建新的会话记录
 * - 删除会话记录
 * - 切换会话
 */
export function useChatHistory() {
    const chatStore = useChatStore();
    const { taskId, fetchChatHistory: loadChatHistory, resetScroll, chatScrollToBottom } = useChatManager();

    // --- State ---
    /**
     * @description 分页参数
     */
    const pagination = reactive({ page_no: 1, page_size: 40 });
    /**
     * @description 是否正在加载中
     */
    const isLoading = ref<boolean>(false);

    /**
     * @description 是否加载完
     */
    const isFinished = ref<boolean>(false);

    /**
     * @description 聊天历史记录列表
     */
    const chatHistory = ref<ChatHistoryItem[]>([]);

    /**
     * @description 当前选中的会话ID
     */
    const currentSessionId = computed(() => taskId.value);

    // --- Public Methods ---

    /**
     * @description 获取聊天历史记录列表
     */
    const fetchChatHistory = async () => {
        isLoading.value = true;
        try {
            // 这里应该调用实际的API获取历史记录
            const { lists, count } = await getChatRecord(pagination);
            isFinished.value = !(lists.length < (pagination.page_size || count));
            chatHistory.value = chatHistory.value.concat(lists);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * @description 创建新的会话记录
     * @param initialMessage - 初始消息内容（可选）
     */
    const createNewSession = (initialMessage?: string) => {
        // 清空当前聊天状态
        chatStore.clearChat();
        chatStore.resetRoute();
    };

    /**
     * @description 切换到指定的会话
     * @param sessionId - 会话ID
     */
    const switchToSession = async (sessionId: string) => {
        if (currentSessionId.value === sessionId) return;

        // 设置当前会话ID
        chatStore.setTaskId(sessionId);

        // 加载该会话的详细聊天记录
        await loadChatHistory();
        resetScroll();
        chatScrollToBottom();
    };

    /**
     * @description 删除指定的会话记录
     * @param sessionId - 会话ID
     */
    const deleteSession = async (sessionId: string) => {
        try {
            // 这里应该调用实际的API删除会话
            // 暂时只是从本地状态中移除
            chatHistory.value = chatHistory.value.filter((item) => item.task_id !== sessionId);
            await deleteChatRecord({ task_id: sessionId });
            feedback.msgSuccess("删除成功");
            // 如果删除的是当前会话，切换到新会话
            if (currentSessionId.value === sessionId) {
                createNewSession();
            }
        } catch (error) {
            feedback.msgError(error);
        }
    };

    /**
     * @description 基于当前聊天内容生成新的会话记录
     */
    const saveCurrentSession = () => {
        const messages = chatStore.chatContentList;
        if (messages.length === 0) return;

        // const newSession: ChatHistoryItem = {
        //     task_id: chatStore.taskId || `task_${Date.now()}`,
        //     message: _generateTitleFromMessages(messages),
        //     created_at: new Date().toISOString(),
        //     updated_at: new Date().toISOString(),
        //     message_count: messages.length,
        // };

        // // 如果当前会话已存在，更新它；否则添加新会话
        // const existingIndex = chatHistory.value.findIndex((item) => item.task_id === newSession.task_id);
        // if (existingIndex >= 0) {
        //     chatHistory.value[existingIndex] = {
        //         ...newSession,
        //         updated_at: new Date().toISOString(),
        //     };
        // } else {
        //     chatHistory.value.unshift(newSession);
        // }
    };

    /**
     * @description 加载聊天历史记录
     * @param params - 加载参数
     */
    const loadHistory = async () => {
        if (!isFinished.value || isLoading.value) return;
        pagination.page_no++;
        fetchChatHistory();
    };

    return {
        // State
        chatHistory: chatHistory,
        currentSessionId,
        isLoading,
        isFinished,

        // Methods
        fetchChatHistory,
        createNewSession,
        switchToSession,
        deleteSession,
        saveCurrentSession,
        loadHistory,
    };
}
