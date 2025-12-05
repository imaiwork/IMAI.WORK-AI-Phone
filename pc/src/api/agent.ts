import type { RequestEventStreamOptions } from "ofetch";

// 获取智能体列表
export function getAgentList(params: any) {
    return $request.get({ url: "/kb.robot/lists", params });
}

// 获取公共智能体列表
export function getCommonAgentList(params: any) {
    return $request.get({ url: "/kb.robot/commonLists", params });
}

// 添加智能体
export function addAgent(params: any) {
    return $request.post({ url: "/kb.robot/add", params });
}

// 添加默认智能体
export function addDefaultAgent() {
    return $request.post({ url: "/kb.robot/defaultAdd" });
}

// 更新智能体
export function updateAgent(params: any) {
    return $request.post({ url: "/kb.robot/edit", params });
}

// 删除智能体
export function deleteAgent(params: any) {
    return $request.post({ url: "/kb.robot/del", params });
}

// 获取智能体详情
export function getAgentDetail(params: any) {
    return $request.get({ url: "/kb.robot/detail", params });
}

// 获取回复策略
export function getReplyStrategy(params: any) {
    return $request.get({ url: "/sv.strategy/replyInfo", params });
}

// 保存回复策略
export function saveReplyStrategy(params: any) {
    return $request.post({ url: "/sv.strategy/reply", params });
}

// 关键词话术列表
export function robotKeywordsLists(params: any) {
    return $request.get({ url: "/sv.robotKeyword/lists", params });
}

// 新增关键词话术
export function addRobotKeywords(params: any) {
    return $request.post({ url: "/sv.robotKeyword/add", params });
}

// 更新关键词话术
export function updateRobotKeywords(params: any) {
    return $request.post({ url: "/sv.robotKeyword/update", params });
}

// 删除关键词话术
export function deleteRobotKeywords(params: any) {
    return $request.post({ url: "/sv.robotKeyword/delete", params });
}

// 批量新增关键词话术
export function batchAddRobotKeywords(params: any) {
    return $request.post({ url: "/sv.robotKeyword/import", params });
}

// coze配置添加
export function cozeConfigAdd(params: any) {
    return $request.post({ url: "/coze.cozeConfig/add", params });
}

// coze配置更新
export function cozeConfigUpdate(params: any) {
    return $request.post({ url: "/coze.cozeConfig/update", params });
}

// coze配置详情
export function cozeConfigDetail() {
    return $request.get({ url: "/coze.cozeConfig/detail" });
}

// coze智能体列表
export function getCozeAgentList(params: any) {
    return $request.get({ url: "/coze.cozeAgent/lists", params });
}

// 公共coze智能体列表
export function getCommonCozeAgentList(params: any) {
    return $request.get({ url: "/coze.cozeAgent/commonLists", params });
}

// coze智能体详情
export function getCozeAgentDetail(params: any) {
    return $request.get({ url: "/coze.cozeAgent/detail", params });
}

// coze智能体新增
export function cozeAgentAdd(params: any) {
    return $request.post({ url: "/coze.cozeAgent/add", params });
}

// coze智能体更新
export function cozeAgentUpdate(params: any) {
    return $request.post({ url: "/coze.cozeAgent/update", params });
}

// coze智能体删除
export function cozeAgentDelete(params: any) {
    return $request.post({ url: "/coze.cozeAgent/delete", params });
}

// coze智能体聊天记录
export function cozeAgentChatLog(params: any) {
    return $request.get({ url: "/coze.cozeChat/messagelist", params });
}

// coze智能体聊天记录消息
export function cozeAgentChatLogDetail(params: any) {
    return $request.get({ url: "/coze.cozeChat/retrieve", params });
}

// coze智能体聊天
export function cozeAgentChat(params: any) {
    return $request.post({ url: "/coze.cozeChat/chat", params });
}

// coze智能体消息查看
export function cozeAgentChatView(params: any) {
    return $request.get({ url: "/coze.cozeChat/retrieve", params });
}

// coze智能体会话记录
export function cozeAgentChatRecord(params: any) {
    return $request.get({ url: "/coze.cozeLog/lists", params });
}

// coze智能体会话记录清除
export function cozeAgentChatRecordClear(params: any) {
    return $request.post({ url: "/coze.cozeLog/delete", params });
}

// coze智能体消息列表
export function cozeAgentChatMsgList(params: any) {
    return $request.get({ url: "/coze.cozeChat/messagelist", params });
}

// coze智能体流式聊天
export function cozeAgentChatStream(params: any, config: RequestEventStreamOptions) {
    return $request.eventStream({ url: "/coze.cozeChat/streamchat", params, method: "POST" }, config);
}

// coze工作流生成
export function cozeWorkflowGenerate(params: any) {
    return $request.post({ url: "/coze.cozeWorkflow/run", params });
}

// 获取发布列表
export function getPublishList(params: any) {
    return $request.get({ url: "/kb.share/lists", params });
}

// 发布新增
export function addPublish(params: any) {
    return $request.post({ url: "/kb.share/add", params });
}

// 发布更新
export function updatePublish(params: any) {
    return $request.post({ url: "/kb.share/update", params });
}

// 发布删除
export function deletePublish(params: any) {
    return $request.post({ url: "/kb.share/del", params });
}

// 发布用量设置
export function pushUsageSetting(params: any) {
    return $request.post({ url: "/kb.share/edit", params });
}

// 发布详情
export function getPublishDetail(params: any, headers?: any) {
    return $request.get({ url: "/kb.share/detail", params, headers });
}

// 发布海报设置
export function publishPosterSetting(params: any) {
    return $request.post({ url: "/kb.share/setBg", params });
}

// 智能体对话数据
export function getAgentChatStat(params: any) {
    return $request.get({ url: "/kb.chat/dataCount", params });
}

// 获取机器人唯一ID
export function getAgentChatUniqueId(params: any, headers?: any) {
    return $request.post({ url: "/kb.chat/getUniqueId", params, headers });
}

// 获取对话记录
export function getPublishAgentChatRecord(params: any, headers?: any) {
    return $request.get({ url: "/kb.chat/chatRecord", params, headers });
}

// 清除对话记录
export function clearChatRecord(params: any, headers?: any) {
    return $request.post({ url: "/kb.chat/chatClean", params, headers });
}

// 智能体分类
export function getAgentCategoryList(params: any) {
    return $request.get({ url: "/agent.agentCate/lists", params });
}
