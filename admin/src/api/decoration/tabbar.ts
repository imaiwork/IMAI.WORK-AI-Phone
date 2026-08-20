import request from "@/utils/request";

// 底部导航详情
export function getTabbarDetail() {
	return request.get({ url: "/decorate.tabbar/detail" });
}

// 底部导航保存
export function saveTabbar(params: any) {
	return request.post({ url: "/decorate.tabbar/save", params });
}
