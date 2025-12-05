// 渠道发布列表
export function channelPublishLists(params: any) {
	return $request.get({ url: "/assistantsChannel/lists", params });
}

// 渠道发布删除
export function channelPublishDelete(params: any) {
	return $request.get({ url: "/assistantsChannel/delete", params });
}

// 渠道发布添加
export function channelPublishAdd(params: any) {
	return $request.post({ url: "/assistantsChannel/add", params });
}

// 渠道发布添加
export function channelPublishEdit(params: any) {
	return $request.post({ url: "/assistantsChannel/edit", params });
}

// 渠道发布详情
export function channelPublishDetail(params: any) {
	return $request.post({ url: "/assistantsChannel/detail", params });
}
