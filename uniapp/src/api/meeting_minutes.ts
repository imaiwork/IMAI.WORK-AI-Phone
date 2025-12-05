import request from "@/utils/request";

// 获取会议纪要列表
export function meetingMinutesLists(data: any) {
	return request.get({ url: "/audio/lists", data });
}

// 会议纪要创建
export function meetingMinutesCreate(data: any) {
	return request.post({ url: "/audio/task", data });
}

// 会议纪要批量创建
export function meetingMinutesBatchCreate(data: any) {
	return request.post({ url: "/audio/batch", data });
}

// 会议纪要删除
export function meetingMinutesDelete(data: any) {
	return request.post({ url: "/audio/delete", data });
}

// 会议纪要详情
export function meetingMinutesDetail(data: any) {
	return request.get({ url: "/audio/detail", data });
}

// 会议纪要重试
export function meetingMinutesRetry(data: any) {
	return request.post({ url: "/audio/retry", data });
}

// 会议纪要编辑富文本
export function meetingMinutesEditText(data: any) {
	return request.post({ url: "/audio/text", data });
}
