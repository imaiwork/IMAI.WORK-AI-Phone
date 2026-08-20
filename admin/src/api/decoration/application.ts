import request from "@/utils/request";

// 应用列表
export function getApplicationLists(params: any) {
	return request.get({ url: "/staff.staff/lists", params });
}

// 应用状态
export function changeApplicationStatus(params: any) {
	return request.post({
		url: "/staff.staff/changeStatus",
		params,
	});
}
