// 思维导图列表
export function mindMapLists(params: any) {
	return $request.get({ url: "/mind_map/lists", params });
}

// 思维导图聊天
export function mindMapChat(params: any) {
	return $request.post({ url: "/mind_map/chat", params });
}

// 思维导图编辑
export function mindMapEditChat(params: any) {
	return $request.post({ url: "/mind_map/edit", params });
}

// 思维导图详情
export function mindMapDetail(params: any) {
	return $request.get({ url: "/mind_map/detail", params });
}

// 思维导图删除
export function mindMapDelete(params: any) {
	return $request.post({ url: "/mind_map/delete", params });
}
