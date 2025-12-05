import request from "@/utils/request";

// 模特列表
export function getModelList(params: any) {
	return request.get({ url: "/hd.hdCueImage/model/lists", params });
}
