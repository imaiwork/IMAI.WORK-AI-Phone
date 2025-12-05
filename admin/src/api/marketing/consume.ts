import request from "@/utils/request";

// 消费记录
export function getConsumeLists(params: any) {
	return request.get({ url: "/recharge.tokens_log/lists", params });
}
