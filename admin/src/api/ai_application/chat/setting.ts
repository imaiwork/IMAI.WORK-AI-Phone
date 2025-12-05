import request from "@/utils/request";

//获取通用聊天配置
export function getCommonChatConfig() {
	return request.post({ url: "/assistants.assistants/chat" });
}

// 更新通用聊天配置
export function updateCommonChatConfig(params: any) {
	return request.post({ url: "/assistants.assistants/updateChat", params });
}
