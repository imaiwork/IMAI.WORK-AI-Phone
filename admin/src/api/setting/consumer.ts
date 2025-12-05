import request from "@/utils/request";

//获取客服设置
export function getconsumerConfig(params?: any) {
	return request.get({ url: "/setting.consumer/detail", params });
}

//保存客服设置
export function setconsumerConfig(params?: any) {
	return request.post({ url: "/setting.consumer/save", params });
}
