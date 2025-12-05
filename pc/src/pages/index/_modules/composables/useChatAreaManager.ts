import { getAgentList as getAgentListApi } from "@/api/agent";
import ChatArea from "chatarea";
import "chatarea/lib/ChatArea.css";
/**
 * @description 智能体的数据结构。
 */
interface Agent {
    name?: string;
    id: string;
}

/**
 * @description useChatAreaManager 的配置选项。
 */
interface UseChatAreaOptions {
    onEnter: (text: string) => void; // 用户按下 Enter 键时的回调
    onInputChange: (text: string, isEmpty: boolean) => void; // 输入内容变化时的回调
}

/**
 * @description useChatAreaManager Composable
 *
 * 封装和管理 `chatarea` 第三方输入框组件。
 * 负责处理 @ 智能体选择、输入事件监听、内容获取和实例生命周期管理。
 *
 * @param options - 聊天区域的配置选项。
 */
export function useChatAreaManager(options: UseChatAreaOptions) {
    // --- State ---

    /**
     * @description 聊天输入框的 DOM 元素引用。
     */
    const chatAreaRef = ref<HTMLElement | null>(null);

    /**
     * @description `chatarea` 组件的实例。
     */
    let chatAreaInstance: any = null;

    /**
     * @description 当前通过 @ 选择的智能体。
     */
    const currentAgent = ref<Agent | null>(null);

    /**
     * @description 从API获取的可用智能体列表。
     */
    const agentList = ref<Agent[]>([]);

    // --- Private Methods ---

    /**
     * @description 从输入文本中移除智能体前缀（例如 "@智能体A "）。
     * @param text - 原始输入文本。
     * @param prefix - @ 符号。
     * @returns - 清理后的文本。
     */
    const replaceInputText = (text: string, prefix: string = "@"): string => {
        if (!currentAgent.value?.name) return text;
        return text.replace(`${prefix}${currentAgent.value.name} `, "");
    };

    // --- Public Methods ---

    /**
     * @description 初始化 `chatarea` 实例并绑定事件监听器。
     * 这是一个异步函数，因为它返回一个Promise。
     */
    const setup = async () => {
        return new Promise((resolve) => {
            if (!chatAreaRef.value || chatAreaInstance) {
                resolve(false);
                return;
            }

            chatAreaInstance = new ChatArea({
                elm: chatAreaRef.value,
                autoFocus: true,
                needCallSpace: true, // 输入 @ 后需要空格触发
                placeholder: "发送消息、输入 @ 选择智能体",
                needCallEvery: false, // 是否每次 @ 都触发
                userList: agentList.value, // 智能体列表
                device: "pc",
            });

            // 监听选择智能体事件
            chatAreaInstance.addEventListener("afterAtCheck", (selectTag: Agent[]) => {
                if (currentAgent.value?.id && currentAgent.value.id !== selectTag[0].id) {
                    chatAreaInstance.delUserTags([currentAgent.value.id]);
                }
                currentAgent.value = selectTag[0];
                const newText = replaceInputText(chatAreaInstance!.getText());
                options.onInputChange(newText, chatAreaInstance!.isEmpty());
            });

            // 监听输入操作事件 (输入、删除等)
            chatAreaInstance.addEventListener("operate", () => {
                const isEmpty = chatAreaInstance!.isEmpty();
                if (isEmpty) {
                    currentAgent.value = null; // 如果输入框为空，则清除当前智能体
                }
                const newText = replaceInputText(chatAreaInstance!.getText());
                options.onInputChange(newText, isEmpty);
            });

            // 监听特殊操作，如剪切
            chatAreaInstance.addEventListener("defaultAction", (type: string) => {
                if (type === "CUT") {
                    currentAgent.value = null;
                    options.onInputChange("", true);
                }
            });

            // 监听键盘事件，用于处理 Enter 发送
            chatAreaInstance.richText.addEventListener("keydown", (event: KeyboardEvent) => {
                // 当用户按下 Enter 且没有按 Shift (换行)
                if (event.key === "Enter" && !event.shiftKey) {
                    // 检查 @ 智能体选择框是否显示
                    const isShow =
                        chatAreaInstance.chatElement.pcElms.pointDialogElm.classList.contains("chat-view-show");
                    if (!isShow) {
                        event.preventDefault(); // 阻止默认换行行为
                        options.onEnter(getText()); // 触发发送回调
                    }
                }
            });

            resolve(true);
        });
    };

    /**
     * @description 清空输入框内容。
     * 如果当前有选中的智能体，会保留智能体的 Tag。
     */
    const clear = () => {
        chatAreaInstance?.clear();
        if (isAgent()) {
            chatAreaInstance.setUserTag(currentAgent.value);
        }
    };

    /**
     * @description 销毁 `chatarea` 实例，释放资源。
     */
    const dispose = () => {
        chatAreaInstance?.dispose();
        chatAreaInstance = null;
    };

    /**
     * @description 获取当前输入框的纯文本内容。
     * 如果有选中的智能体，会自动移除智能体前缀。
     * @returns - 纯文本内容。
     */
    const getText = (): string => {
        const rawText = chatAreaInstance?.getText() ?? "";
        return currentAgent.value ? replaceInputText(rawText) : rawText;
    };

    /**
     * @description 检查当前是否已选择智能体。
     * @returns - 如果已选择智能体，则返回 true。
     */
    const isAgent = (): boolean => {
        return !!currentAgent.value?.id;
    };

    /**
     * @description 手动设置一个智能体。
     * @param agent - 要设置的智能体对象。
     */
    const setAgent = async (agent: Agent) => {
        await nextTick(); // 等待DOM更新
        currentAgent.value = agent;
        chatAreaInstance.setUserTag(agent);
    };

    /**
     * @description 从API获取智能体列表。
     */
    const getAgentList = async () => {
        try {
            const { lists } = await getAgentListApi({ page_size: 1500, source: 1 });
            agentList.value = lists.map((item) => ({ name: item.name, id: item.id }));
        } catch (error) {
            console.error("获取智能体列表失败:", error);
        }
    };

    return {
        chatAreaRef,
        agent: currentAgent,
        setup,
        clear,
        dispose,
        getText,
        getAgentList,
        isAgent,
        setAgent,
    };
}
