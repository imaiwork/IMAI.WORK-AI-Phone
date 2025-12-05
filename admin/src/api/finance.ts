import request from "@/utils/request";

// 财务汇总
export function accountLog(params?: any) {
	return request.get({ url: "/recharge.GiftPackageOrder/lists", params });
}

// 充值记录
export function rechargeLists(params?: any) {
	return request.get(
		{ url: "/recharge.recharge/lists", params },
		{ ignoreCancelToken: true }
	);
}
