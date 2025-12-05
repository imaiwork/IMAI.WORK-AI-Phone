// 会议纪要列表
export function meetingMinutesLists(params: any) {
	return $request.get({ url: "/audio/lists", params });
}

// 会议纪要创建
export function meetingMinutesCreate(params: any) {
	return $request.post({ url: "/audio/task", params });
}

// 会议纪要批量创建
export function meetingMinutesBatchCreate(params: any) {
	return $request.post({ url: "/audio/batch", params });
}

// 会议纪要删除
export function meetingMinutesDelete(params: any) {
	return $request.post({ url: "/audio/delete", params });
}

// 会议纪要详情
export function meetingMinutesDetail(params: any) {
	return $request.get({ url: "/audio/detail", params });
}

// 会议纪要重试
export function meetingMinutesRetry(params: any) {
	return $request.post({ url: "/audio/retry", params });
}

// 会议纪要编辑富文本
export function meetingMinutesEditText(params: any) {
	return $request.post({ url: "/audio/text", params });
}
