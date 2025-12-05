// 企业微信列表
export function companyWechatLists(params: any) {
	return $request.get({ url: "/WorkWeChat/lists", params });
}

// 添加企业微信
export function addCompanyWechat(params: any) {
	return $request.post({ url: "/WorkWeChat/add", params });
}

// 修改企业微信
export function updateCompanyWechat(params: any) {
	return $request.post({ url: "/WorkWeChat/updateUser", params });
}

// 编辑企业微信
export function editCompanyWechat(params: any) {
	return $request.post({ url: "/WorkWeChat/edit", params });
}

// 删除企业微信
export function deleteCompanyWechat(params: any) {
	return $request.get({ url: "/WorkWeChat/delete", params });
}

// 客户列表
export function consumerLists(params: any) {
	return $request.get({ url: "/WorkWeChat/phoneLists", params });
}

// 导入客户
export function importconsumer(params: any) {
	return $request.post({ url: "/WorkWeChat/importData", params });
}

// 导入记录
export function importRecord(params: any) {
	return $request.get({ url: "/WorkWeChat/importLists", params });
}

// 规则设置
export function ruleSetting(params: any) {
	return $request.post({ url: "/WorkConfig/edit", params });
}

// 规则获取
export function ruleGet() {
	return $request.get({ url: "/WorkConfig/detail" });
}

// 检查登录状态
export function checkWeChatLogin(params: any) {
	return $request.get({ url: "/WorkWeChat/checkUserLogin", params });
}
