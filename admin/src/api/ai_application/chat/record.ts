import request from "@/utils/request";

// 获取对话记录
export function getDialogueRecord(params: any) {
	return request.get({ url: "/assistants.chatLog/lists", params });
}

// 删除对话记录
export function deleteDialogueRecord(params: any) {
	return request.post({ url: "/assistants.chatLog/delete", params });
}
