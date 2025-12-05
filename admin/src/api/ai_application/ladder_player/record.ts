import request from "@/utils/request";

// 记录列表
export function getLpRecordLists(params?: any) {
	return request.get({ url: "/lianlian.LlAnalysis/lists", params });
}

// 聊天记录
export function getLpChatRecordLists(params?: any) {
	return request.get({ url: "/lianlian.LlAnalysis/chatLog", params });
}

// 分析详情
export function getLpAnalysisDetail(params?: any) {
	return request.get({ url: "/lianlian.LlAnalysis/detail", params });
}

// 重新分析
export function lpAnalysisReanalysis(params?: any) {
	return request.post({ url: "/lianlian.LlAnalysis/retry", params });
}

// 删除记录
export function lpRecordDelete(params?: any) {
	return request.post({ url: "/lianlian.LlAnalysis/delete", params });
}
