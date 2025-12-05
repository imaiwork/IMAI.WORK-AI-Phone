import request, { RequestEventStreamConfig } from "@/utils/request";

// 机器人聊天
export function chatRobotSendTextStream(data: any, config: RequestEventStreamConfig) {
    return request.eventStream({ url: "/chat/sceneChat", data, method: "POST" }, config);
}

// 通用聊天
export function chatSendTextStream(data: any, config: RequestEventStreamConfig) {
    return request.eventStream({ url: "/chat/commonChat", data, method: "POST" }, config);
}

// 场景提示词
export function chatPrompt(data: any) {
    return request.post({
        url: "/chat/promptChat",
        data,
    });
}

// 聊天记录
export function getChatLog(data: any) {
    return request.get({
        url: "/chat/chatLogs",
        data,
    });
}

// 获取通用配置
export function getChatConfig() {
    return request.post({ url: "/chat/commonChatInfo" });
}

// 会话记录
export function chatSessionLog(data: any) {
    return request.get({
        url: "/conversation/lists",
        data,
    });
}

// 创作记录
export function getCreativeRecord(data: any) {
    return request.get({ url: "/chat/chatLists", data });
}

// 记录删除
export function deleteCreativeRecord(data: any) {
    return request.post({ url: "/chat/deleteChat", data });
}

// 获取用户聊天配置
export function getUserChatConfig(data: any) {
    return request.get({ url: "/chat/getUserModelsSetting", data });
}

// 保存用户聊天配置
export function saveUserChatConfig(data: any) {
    return request.post({ url: "/chat/editUserModelsSetting", data });
}
