import request from "@/utils/request";

// 智能体列表
export function getAgentLists(params: any) {
    return request.get({ url: "/kb.robot/lists", params });
}

// 删除智能体
export function deleteAgent(params: any) {
    return request.post({ url: "/kb.robot/del", data: params });
}

// 添加智能体
export function addAgent(params: any) {
    return request.post({ url: "/kb.robot/add", params });
}

// 更新智能体
export function updateAgent(params: any) {
    return request.post({ url: "/kb.robot/edit", params });
}

// 获取智能体详情
export function getAgentDetail(params: any) {
    return request.get({ url: "/kb.robot/detail", params });
}

// 获取智能体聊天记录
export function getAgentChatRecord(params: any) {
    return request.get({ url: "/kb.robot/chatLists", params });
}

// 删除智能体聊天记录
export function deleteAgentChatRecord(params: any) {
    return request.post({ url: "/kb.robot/deleteChatLog", params });
}

// 关键词话术列表
export function robotKeywordsLists(params: any) {
    return request.get({ url: "/sv.robotKeyword/lists", params });
}

// 删除关键词话术
export function deleteRobotKeywords(params: any) {
    return request.post({ url: "/sv.robotKeyword/delete", params });
}

// 新增关键词话术
export function addRobotKeywords(params: any) {
    return request.post({ url: "/sv.robotKeyword/add", params });
}

// 更新关键词话术
export function updateRobotKeywords(params: any) {
    return request.post({ url: "/sv.robotKeyword/update", params });
}

// 获取回复策略
export function getReplyStrategy(params: any) {
    return request.get({ url: "/sv.strategy/replyInfo", params });
}

// 保存回复策略
export function saveReplyStrategy(params: any) {
    return request.post({ url: "/sv.strategy/reply", params });
}
