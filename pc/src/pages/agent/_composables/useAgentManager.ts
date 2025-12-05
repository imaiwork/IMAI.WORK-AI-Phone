import { getAgentDetail, getCozeAgentDetail, cozeAgentChatRecord, cozeAgentChatRecordClear } from "@/api/agent";
import { getChatRecord, deleteChatRecord as clearChatRecord } from "@/api/chat";
import { AgentTypeEnum, CozeTypeEnum } from "../_enums";

// 统一聊天记录Item的接口
interface ChatRecordItem {
    id: string; // 统一使用 conversation_id 或 task_id
    title: string; // 统一使用 content 或 message
}

/**
 * @description 统一管理不同类型智能体的详情、聊天记录等通用逻辑
 */
export function useAgentManager() {
    const route = useRoute();
    const nuxtApp = useNuxtApp();

    // 从路由获取参数
    const agentId = route.query.agent_id as string;
    const cozeId = route.query.coze_id as string;
    const agentType = Number(route.query.type) as AgentTypeEnum;

    const detail = ref<any>({});
    const loading = ref(true);
    const currentRecordId = ref<string | null>(null);

    // --- 详情获取 ---
    const getDetail = async () => {
        const apis = {
            [AgentTypeEnum.AGENT]: getAgentDetail,
            [AgentTypeEnum.COZE_AGENT]: getCozeAgentDetail,
            [AgentTypeEnum.COZE_FLOW]: getCozeAgentDetail,
        };
        if (apis[agentType]) {
            detail.value = await apis[agentType]({ id: agentId });
        }
    };

    // --- 聊天记录 ---
    const chatRecordParams = reactive({ page_no: 1, page_size: 20 });
    const {
        pager: chatRecordPager,
        getLists: getChatRecordLists,
        resetPage: resetChatRecordPager,
    } = usePaging({
        fetchFun: async (params: any) => {
            let res;
            if (agentType === AgentTypeEnum.AGENT) {
                res = await getChatRecord({
                    ...params,
                    robot_id: agentId,
                    chat_type: 9006, // 智能体对话类型
                });
                // 格式化数据以匹配ChatRecordItem
                res.lists = res.lists.map((item: any) => ({
                    id: item.task_id,
                    title: item.message,
                    ...item,
                }));
            } else {
                res = await cozeAgentChatRecord({
                    ...params,
                    bot_id: cozeId,
                    type: agentType === AgentTypeEnum.COZE_AGENT ? CozeTypeEnum.AGENT : CozeTypeEnum.FLOW,
                });
                // 格式化数据
                res.lists = res.lists.map((item: any) => ({
                    id: item.conversation_id,
                    title: item.content || item.message,
                    ...item,
                }));
            }
            return res;
        },
        params: chatRecordParams,
        isScroll: true,
    });

    const loadRecordMore = () => {
        if (chatRecordPager.isLoad && !chatRecordPager.loading) {
            chatRecordParams.page_no++;
            getChatRecordLists();
        }
    };

    // --- 记录操作 ---
    const handleSelectRecord = (record: ChatRecordItem) => {
        const queryKey = agentType == AgentTypeEnum.AGENT ? "task_id" : "conversation_id";
        if (!record[queryKey] || currentRecordId.value == record[queryKey]) return;
        currentRecordId.value = record[queryKey];
    };

    const handleCreateRecord = () => {
        if (!currentRecordId.value) return;
        currentRecordId.value = null;
    };

    const handleDeleteRecord = (item: any) => {
        const { id, conversation_id } = item || {};
        const deleteId = agentType === AgentTypeEnum.AGENT ? id : conversation_id;
        nuxtApp.$confirm({
            message: id ? "确定要删除该对话吗？" : "确定要清除所有对话吗？",
            onConfirm: async () => {
                try {
                    if (agentType === AgentTypeEnum.AGENT) {
                        await clearChatRecord({ robot_id: agentId, task_id: id, chat_type: 9006 });
                    } else {
                        await cozeAgentChatRecordClear({ bot_id: cozeId, conversation_id: conversation_id || "0" });
                    }
                    feedback.msgSuccess("删除成功");
                    const index = chatRecordPager.lists.findIndex((item: any) =>
                        agentType === AgentTypeEnum.AGENT ? item.id == id : item.conversation_id == conversation_id
                    );
                    if (index !== -1) {
                        chatRecordPager.lists.splice(index, 1);
                    }
                    // 如果删除的是当前对话或全部对话，则重置
                    if (!deleteId || deleteId == currentRecordId.value) {
                        handleCreateRecord();
                        if (!deleteId) {
                            resetChatRecordPager();
                        }
                    }
                } catch (error) {
                    feedback.msgError((error as string) || "删除失败");
                }
            },
        });
    };

    // --- 初始化 ---
    const init = async () => {
        loading.value = true;
        try {
            await getDetail();
            await getChatRecordLists();
            // // 从路由设置当前选中的记录
            currentRecordId.value = (route.query.conversation_id as string) || (route.query.task_id as string) || null;
            if (!currentRecordId.value && chatRecordPager.lists.length > 0) {
                // 如果没有选中记录，默认选中第一个
                handleSelectRecord(chatRecordPager.lists[0]);
            }
        } finally {
            loading.value = false;
        }
    };

    onMounted(init);

    return {
        agentId,
        cozeId,
        agentType,
        detail,
        loading,
        chatRecordPager,
        currentRecordId,
        loadRecordMore,
        resetChatRecordPager,
        handleSelectRecord,
        handleCreateRecord,
        handleDeleteRecord,
    };
}
