import request from "@/utils/request";

// 优秀案例列表
export function getDrawCaseList(params: any) {
	return request.get({ url: "/hd.HdImageCase/lists", params });
}

// 优秀案例添加
export function addDrawCase(params: any) {
	return request.post({ url: "/hd.HdImageCase/add", params });
}

// 优秀案例删除
export function delDrawCase(params: any) {
	return request.post({ url: "/hd.HdImageCase/delete", params });
}

// 优秀案例编辑
export function editDrawCase(params: any) {
	return request.post({ url: "/hd.HdImageCase/edit", params });
}
