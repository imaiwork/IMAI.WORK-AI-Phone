import request from "@/utils/request";

// 微信聊天记录列表
export function getRecordList(params: any) {
	return request.get({ url: "/wechat.chat/lists", params });
}
