import request from "@/utils/request";

// 设备列表
export function getDeviceList(params: any) {
	return request.get({ url: "/wechat.device/lists", params });
}
