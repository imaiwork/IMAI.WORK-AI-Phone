import request, { RequestEventStreamConfig } from '@/utils/request';
// 练练分类列表
export function getVoiceChatCategory() {
	return request.get({ url: '/llCategory/lists' });
}

// 练练列表
export function getVoiceChatLists(data?: any) {
	return request.get({ url: '/llCategory/LlCategoryInfoLists', data });
}

//  练练详情
export function voiceChatDetail(data?: any) {
	return request.get({ url: '/llCategory/detail', data });
}

// 创建会话
export function voiceChatSessionCreate(data?: any) {
	return request.post({ url: '/llCategory/createChat', data });
}

// 文字转语音
export function voiceChatTextToAudio(data?: any) {
	return request.post({ url: '/llCategory/toText', data });
}

// 开场白
export function voiceChatPrologue(data?: any) {
	return request.post({ url: '/llCategory/startChat', data });
}

// 发送聊天
export function voiceChatSendTextStream(data: any, config: RequestEventStreamConfig) {
	return request.eventStream({ url: '/llCategory/toText', data }, config);
}

// 发送聊天
export function voiceChatSendText(data: any) {
	return request.post({ url: '/llCategory/chat', data });
}

// 报告详情
export function voiceChatReportDetail(data: any) {
	return request.get({ url: '/llCategory/analyseDetail', data });
}

// 报告关键词
export function voiceChatReportWords(data: any) {
	return request.get({ url: '/llCategory/analyseKeyWords', data });
}

// 报告分析
export function voiceChatReportAnalysis(data: any) {
	return request.post({ url: '/llCategory/analyse', data });
}

// 聊天记录
export function voiceChatRecord(data: any) {
	return request.get({ url: '/llCategory/LlchatLists', data });
}

// 历史分析
export function voiceChatAnalysisRecord(data: any) {
	return request.get({ url: '/llCategory/conversationList', data });
}

// 获取语音wss链接
export function getVoiceChatWssUrl() {
	return request.get({ url: '/llCategory/getSign' });
}
