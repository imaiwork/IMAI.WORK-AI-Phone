import { getChatRecord, deleteChatRecord } from "@/api/chat";
import { useUserStore } from "@/stores/user";
import { useChatStore } from "../stores/chat";
import { useChatManager } from "./useChatManager";
import { useChatEventBus } from "./useChatEventBus";

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
const { onHistoryRefresh, triggerEnterChatSession } = useChatEventBus();
// --- State ---
/**
 * @description 分页参数
 */
const pagination = reactive({ page_no: 1, page_size: 40 });
/**
 * @description 是否正在刷新中
 */
const isRefreshing = ref<boolean>(true);
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
let historyRefreshRegistered = false;

const mergeUniqueByTaskId = (base: ChatHistoryItem[], incoming: ChatHistoryItem[]) => {
    const taskIds = new Set<string>();
    return [...base, ...incoming].filter((item) => {
        if (!item.task_id || taskIds.has(item.task_id)) return false;
        taskIds.add(item.task_id);
        return true;
    });
};

export function useChatHistory() {
    const chatStore = useChatStore();
    const userStore = useUserStore();
    const { taskId, fetchChatHistory: loadChatHistory, resetScroll, stopStream, chatScrollToBottom } = useChatManager();

    /**
     * @description 当前选中的会话ID
     */
    const currentSessionId = computed(() => taskId.value);

    // --- Public Methods ---

    /**
     * @description 获取聊天历史记录列表
     */
    const fetchChatRecord = async () => {
        // 未登录时不请求历史记录，直接结束骨架屏并展示空状态
        if (!userStore.isLogin) {
            chatHistory.value = [];
            isFinished.value = true;
            isLoading.value = false;
            isRefreshing.value = false;
            return;
        }
        isLoading.value = true;
        try {
            // 这里应该调用实际的API获取历史记录
            const { lists = [], count } = await getChatRecord(pagination);
            isFinished.value = !(lists.length < (pagination.page_size || count));
            chatHistory.value =
                pagination.page_no === 1 ? mergeUniqueByTaskId([], lists) : mergeUniqueByTaskId(chatHistory.value, lists);
        } finally {
            isLoading.value = false;
            isRefreshing.value = false;
        }
    };

    /**
     * @description 创建新的会话记录
     * @param initialMessage - 初始消息内容（可选）
     */
    const createNewSession = (initialMessage?: string) => {
        // 清空当前聊天状态
        stopStream();
        chatStore.clearChat();
        chatStore.resetRoute();
    };

    /**
     * @description 切换到指定的会话
     * @param sessionId - 会话ID
     */
    const switchToSession = async (sessionId: string) => {
        if (currentSessionId.value === sessionId) return;
        await stopStream();
        // 先进入「会话加载」上下文，避免 clear 后空消息触发欢迎页闪烁
        triggerEnterChatSession();
        chatStore.isLoading = true;
        chatStore.replaceTaskId(sessionId);
        chatStore.clearChatMessages();

        // 加载该会话的详细聊天记录
        try {
            await loadChatHistory();
            resetScroll();
            chatScrollToBottom();
        } finally {
            chatStore.isLoading = false;
        }
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
        await fetchChatRecord();
    };

    /**
     * @description 重置
     */
    const reset = () => {
        pagination.page_no = 1;
        chatHistory.value = [];
        isFinished.value = false;
        isLoading.value = false;
    };

    if (!historyRefreshRegistered) {
        historyRefreshRegistered = true;
        onHistoryRefresh((payload: any) => {
            if (!payload?.taskId) return;
            const refreshedSession = {
                message: payload.message,
                create_time: payload.createTime,
                task_id: payload.taskId,
                update_time: payload.createTime,
            };
            const existingIndex = chatHistory.value.findIndex((item) => item.task_id === payload.taskId);
            if (existingIndex >= 0) {
                chatHistory.value.splice(existingIndex, 1);
            }
            chatHistory.value.unshift(refreshedSession);
        });
    }

    return {
        // State
        chatHistory: chatHistory,
        currentSessionId,
        isRefreshing,
        isLoading,
        isFinished,

        // Methods
        fetchChatRecord,
        createNewSession,
        switchToSession,
        deleteSession,
        saveCurrentSession,
        loadHistory,
        reset,
    };
}
