import { defineStore } from "pinia";
import { getBaseUrl } from "@/utils/env";

interface Detail {
    logo: string;
    name: string;
}

// 定义单条聊天消息的接口，确保数据结构的类型安全。
export interface ChatMessage {
    type: 1 | 2; // 消息类型: 1 代表用户, 2 代表机器人
    message?: string; // 用户发送的消息内容
    reply?: string; // 机器人的回复内容
    loading?: boolean; // 是否正在加载（用于显示加载动画）
    form_avatar: string; // 发送者头像URL
    fileList?: any[]; // 附加的文件列表
    is_reasoning_finished?: boolean; // 是否完成思考过程（用于流式响应）
    reasoning_content?: string; // 思考过程的内容
    error?: string; // 消息处理过程中的错误信息
    consume_tokens?: any; // 消耗的token信息
    tokens_info?: any; // (历史记录) 消耗的token信息
    robot_id?: string | number; // 机器人ID
}

// 定义文件上传后的结构
export interface ChatFile {
    uid: number;
    id: string;
    url: string;
    name: string;
    type: string;
    size: number;
}

/**
 * @description 聊天状态管理中心 (Pinia Store)
 *
 * 管理所有与聊天相关的状态，包括会话ID、消息列表、智能体信息、加载状态等。
 */
export const useChatStore = defineStore("chat", () => {
    // --- State ---

    /**
     * @type {Record<string, {logo: string, name: string}>}
     * @description 记录详情
     * 包含记录的logo、name等信息
     */
    const detail = reactive<Detail>({
        logo: "",
        name: "",
    });
    /**
     * @description 当前会话的任务ID (task_id)。
     * 如果为空字符串，表示是一个新的会话。
     */
    const taskId = ref<string>("");

    /**
     * @description 当前选择的智能体信息。
     */
    const agentValue = ref<any>();

    /**
     * @description 聊天消息列表。
     */
    const chatContentList = ref<ChatMessage[]>([]);

    /**
     * @description 是否正在接收流式消息。
     * true 表示后端正在发送数据，此时应禁止用户再次发送消息。
     */
    const isReceiving = ref<boolean>(false);

    /**
     * @description 是否可以停止当前聊天。
     * 通常在 isReceiving 为 true 时，此值也为 true。
     */
    const isStopChat = ref<boolean>(false);

    /**
     * @description 页面是否处于加载状态（例如，获取历史记录时）。
     */
    const isLoading = ref<boolean>(false);

    /**
     * @description 是否开启深度思考模式。
     */
    const isDeep = ref<boolean>(false);

    /**
     * @description 是否开启联网搜索。
     */
    const isNetwork = ref<boolean>(false);

    /**
     * @description 当前待上传的文件列表。
     */
    const fileLists = ref<ChatFile[]>([]);

    /**
     * @description 发送消息额外参数。
     */
    const extraParams = ref<{
        model_id?: string | number;
        model_sub_id?: string | number;
        top_p?: number;
        temperature?: number;
        presence_penalty?: number;
        frequency_penalty?: number;
        context_num?: number;
    }>({
        model_id: undefined,
        model_sub_id: undefined,
        top_p: undefined,
        temperature: undefined,
        presence_penalty: undefined,
        frequency_penalty: undefined,
        context_num: undefined,
    });

    // --- Actions ---

    /**
     * @description 设置记录详情。
     * @param detail - 记录详情。
     */
    function setDetail(data: Detail) {
        Object.assign(detail, data);
    }

    /**
     * @description 设置当前会话的 task_id。
     * @param id - 会话ID。
     */
    function setTaskId(id: string) {
        taskId.value = id;
        replaceState({
            task_id: taskId.value,
        });
    }

    /**
     * @description 设置或取消当前智能体。
     * 如果传入的智能体与当前相同，则取消选择并清空聊天，实现"切换"效果。
     * @param agent - 智能体对象。
     */
    function setAgent(agent: any) {
        // if (agentValue.value?.id && agent?.id != agentValue.value?.id) {
        //     // 如果点击的是当前已选中的智能体，则取消选择并重置
        //     clearChat();
        //     // 重置URL，避免刷新后仍然是该智能体
        //     replaceState({
        //         task_id: "",
        //         agent_name: "",
        //         agent_id: "",
        //     });
        // }
        agentValue.value = agent;
    }

    /**
     * @description 向消息列表添加一条新消息。
     * @param message - 消息对象。
     */
    function addMessage(message: ChatMessage) {
        chatContentList.value.push(message);
    }

    /**
     * @description 清空整个聊天状态，用于开始新会话。
     * 重置 task_id, agent, 消息列表和各种状态标志。
     */
    function clearChat() {
        taskId.value = "";
        agentValue.value = undefined;
        chatContentList.value = [];
        isLoading.value = false;
        isDeep.value = false;
        isNetwork.value = false;
        fileLists.value = [];
        extraParams.value = {};
        stopReceiving();
    }

    /**
     * @description 开始接收流式响应。
     * 设置 isReceiving 和 isStopChat 状态为 true。
     */
    function startReceiving() {
        isReceiving.value = true;
        isStopChat.value = true;
    }

    /**
     * @description 停止接收流式响应。
     * 设置 isReceiving 和 isStopChat 状态为 false。
     */
    function stopReceiving() {
        isReceiving.value = false;
        isStopChat.value = false;
    }

    /**
     * @description 更新消息列表中的最后一条消息。
     * 主要用于流式响应中，持续更新机器人回复的内容。
     * @param payload - 需要更新的字段。
     */
    function updateLastMessage(payload: Partial<ChatMessage>) {
        if (chatContentList.value.length === 0) return;
        const lastMessage = chatContentList.value[chatContentList.value.length - 1];
        Object.assign(lastMessage, payload);
    }

    /**
     * @description 设置待上传的文件列表。
     * @param files - 文件列表。
     */
    function setFiles(files: ChatFile[]) {
        fileLists.value = files;
    }

    /**
     * @description 清空待上传的文件列表。
     */
    function clearFiles() {
        fileLists.value = [];
    }
    /**
     * @description 重置路由
     */
    function resetRoute() {
        history.replaceState("", "", `?`);
    }

    return {
        // State
        detail,
        taskId,
        agentValue,
        chatContentList,
        isReceiving,
        isStopChat,
        isLoading,
        isDeep,
        isNetwork,
        fileLists,
        extraParams,
        // Actions
        setDetail,
        setTaskId,
        setAgent,
        addMessage,
        clearChat,
        startReceiving,
        stopReceiving,
        updateLastMessage,
        setFiles,
        clearFiles,
        resetRoute,
    };
});
