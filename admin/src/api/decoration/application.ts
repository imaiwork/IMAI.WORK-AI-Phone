import request from "@/utils/request";

// 应用列表
export function getApplicationLists(params: any) {
	return request.get({ url: "/staff.staff/lists", params });
}

// 应用详情
export function getApplicationDetail(params: any) {
	return request.get({ url: "/staff.staff/detail", params });
}

// 应用编辑
export function editApplication(params: any) {
	return request.post({ url: "/staff.staff/edit", params });
}

// 应用状态
export function changeApplicationStatus(params: any) {
	return request.post({
		url: "/staff.staff/changeStatus",
		params,
	});
}
