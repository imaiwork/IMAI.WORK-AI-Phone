import type { RequestEventStreamOptions } from "ofetch";

// 机器人聊天
export function chatRobotSendTextStream(params: any, config: RequestEventStreamOptions) {
    return $request.eventStream({ url: "/chat/sceneChat", params, method: "POST" }, config);
}

// 通用聊天
export function chatSendTextStream(params: any, config: RequestEventStreamOptions) {
    return $request.eventStream({ url: "/chat/commonChat", params, method: "POST" }, config);
}

// 发布聊天
export function publishChatSendTextStream(params: any, headers: any, config: RequestEventStreamOptions) {
    return $request.eventStream({ url: "/v1/chat/commonChat", params, method: "POST", headers }, config);
}

// 提示词生成
export function generateCueWord(params: any, config: RequestEventStreamOptions) {
    return $request.eventStream({ url: "/chat/hdChat", params, method: "POST" }, config);
}

// 获取消耗tokens
export function getConsumeTokens(params: any) {
    return $request.get({ url: "/accountLog/info", params });
}

// 聊天记录
export function getChatLog(params: any) {
    return $request.get({
        url: "/chat/chatLogs",
        params,
    });
}

// 创作记录
export function getChatRecord(params: any) {
    return $request.get({
        url: "/chat/chatLists",
        params,
    });
}

// 会话删除
export function deleteChatRecord(params: any) {
    return $request.post({ url: "/chat/deleteChat", params });
}

// 场景提示词
export function chatPrompt(params: any) {
    return $request.post({
        url: "/chat/promptChat",
        params,
    });
}

// 获取通用配置
export function getChatConfig() {
    return $request.post({ url: "/chat/commonChatInfo" });
}

// 获取用户聊天配置
export function getUserChatConfig(params: any) {
    return $request.get({ url: "/chat/getUserModelsSetting", params });
}

// 保存用户聊天配置
export function saveUserChatConfig(params: any) {
    return $request.post({ url: "/chat/editUserModelsSetting", params });
}
