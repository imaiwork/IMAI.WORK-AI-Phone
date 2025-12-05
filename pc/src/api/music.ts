// 音乐列表
export function musicLists(params: any) {
	return $request.get({ url: "/suno/lists", params });
}

// 音乐生成
export function musicGenerate(params: any) {
	return $request.post({ url: "/suno/add", params });
}

// 音乐编辑
export function musicEditChat(params: any) {
	return $request.post({ url: "/suno/edit", params });
}

// 音乐详情
export function musicDetail(params: any) {
	return $request.get({ url: "/suno/detail", params });
}

// 音乐删除
export function musicDelete(params: any) {
	return $request.get({ url: "/suno/delete", params });
}
