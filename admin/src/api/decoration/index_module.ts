import request from "@/utils/request";

// 模块列表
export function getModuleLists(params: any) {
	return request.get({ url: "/decoration/index_module/lists", params });
}
