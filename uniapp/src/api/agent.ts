import request, { RequestEventStreamConfig } from "@/utils/request";

// 机器人类目
export function robotCategory(data: any) {
    return request.get({ url: "/chat/sceneLists", data });
}

// 机器人列表
export function robotLists(data: any) {
    return request.get({ url: "/chat/sceneAassistantLists", data });
}

// 机器人详情
export function robotDetail(data: any) {
    return request.get({ url: "/chat/sceneChatInfo", data });
}

// 获取智能体列表
export function getAgentList(data: any) {
    return request.get({ url: "/kb.robot/lists", data });
}

// 获取公共智能体列表
export function getCommonAgentList(data: any) {
    return request.get({ url: "/kb.robot/commonLists", data });
}

// coze智能体列表
export function getCozeAgentList(data: any) {
    return request.get({ url: "/coze.cozeAgent/lists", data });
}

// 公共coze智能体列表
export function getCommonCozeAgentList(data: any) {
    return request.get({ url: "/coze.cozeAgent/commonLists", data });
}

// coze智能体详情
export function getCozeAgentDetail(data: any) {
    return request.get({ url: "/coze.cozeAgent/detail", data });
}

// coze智能体流式聊天
export function cozeAgentChatStream(data: any, config: RequestEventStreamConfig) {
    return request.eventStream({ url: "/coze.cozeChat/streamchat", data, method: "POST" }, config);
}

// coze工作流生成
export function cozeWorkflowGenerate(data: any) {
    return request.post({ url: "/coze.cozeWorkflow/run", data });
}

// coze智能体聊天
export function cozeAgentChat(data: any) {
    return request.post({ url: "/coze.cozeChat/chat", data });
}

// coze智能体消息查看
export function cozeAgentChatView(data: any) {
    return request.get({ url: "/coze.cozeChat/retrieve", data });
}

// coze智能体会话记录
export function cozeAgentChatRecord(data: any) {
    return request.get({ url: "/coze.cozeLog/lists", data });
}

// coze智能体会话记录清除
export function cozeAgentChatRecordClear(data: any) {
    return request.post({ url: "/coze.cozeLog/delete", data });
}

// coze智能体消息列表
export function cozeAgentChatMsgList(data: any) {
    return request.get({ url: "/coze.cozeChat/messagelist", data });
}

// 智能体分类
export function getAgentCategoryList(data: any) {
    return request.get({ url: "/agent.agentCate/lists", data });
}
