import request from "@/utils/request";

// 获取积分用量配置
export function getCreditSet() {
	return request.get({ url: "/config/getModelConfig" });
}

// 设置积分用量配置
export function setCreditSet(data: any) {
	return request.post({ url: "/config/setModelConfig", data });
}
