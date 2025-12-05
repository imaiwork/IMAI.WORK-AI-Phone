import request from "@/utils/request";

// 会议记录列表
export function getMeetingRecordList(params: any) {
	return request.get({ url: "/audio.audio/lists", params });
}

// 会议记录删除
export function deleteMeetingRecord(params: any) {
	return request.post({ url: "/audio.audio/delete", params });
}

// 会议记录详情
export function getMeetingRecordDetail(params: any) {
	return request.get({ url: "/audio.audio/detail", params });
}
