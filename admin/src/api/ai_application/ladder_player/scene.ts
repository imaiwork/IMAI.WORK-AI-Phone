import request from "@/utils/request";

// 场景列表
export function getLpSceneLists(params?: any) {
	return request.get({ url: "/lianlian.LlScene/lists", params });
}

// 场景新增
export function lpSceneAdd(params?: any) {
	return request.post({ url: "/lianlian.LlScene/add", params });
}

// 场景编辑
export function lpSceneEdit(params?: any) {
	return request.post({ url: "/lianlian.LlScene/edit", params });
}

// 场景删除
export function lpSceneDelete(params?: any) {
	return request.post({ url: "/lianlian.LlScene/delete", params });
}

// 场景详情
export function lpSceneDetail(params?: any) {
	return request.get({ url: "/lianlian.LlScene/detail", params });
}

// 场景状态
export function lpSceneChangeStatus(params?: any) {
	return request.post({ url: "/lianlian.LlScene/changeStatus", params });
}
