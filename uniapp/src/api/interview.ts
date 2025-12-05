import request from "@/utils/request";

// 面试岗位列表
export function getInterviewJobList(data: any) {
	return request.post({ url: "/interview/jobs", data });
}

// 面试岗位详情
export function getInterviewJobDetail(data: any) {
	return request.post({ url: "/interview/jobDetail", data });
}

// 简历识别
export function getResumeRecognition(data: any) {
	return request.post({ url: "/interview/extractCv", data });
}

// 简历保存
export function saveResume(data: any) {
	return request.post({ url: "/interview/saveCv", data });
}

// 简历新增
export function addResume(data: any) {
	return request.post({ url: "/interview/addCv", data });
}

// 开始面试
export function startInterview(data: any) {
	return request.post({ url: "/interview/start", data });
}

// 开始对话
export function startInterviewChat(data: any) {
	return request.post({ url: "/interview/start", data });
}

// 继续对话
export function continueInterviewChat(data: any) {
	return request.post({ url: "/interview/chat", data });
}

// 语音转文字
export function speechToText(data: any) {
	return request.post({ url: "/interview/stt", data });
}

// 面试聊天记录
export function getInterviewChatRecord(data: any) {
	return request.get({ url: "/interview/getDialog", data });
}

// 重新面试
export function interviewAgain(data: any) {
	return request.post({ url: "/interview.interviewDialog/exit", data });
}
// 面试反馈
export function interviewFeedback(data: any) {
	return request.post({ url: "/interview/feedback", data });
}

//  生成岗位的链接
export function generateJobLink(data: any) {
	return request.get({ url: "/interview.interview/jobLink", data });
}

// 面试校验
export function interviewCheck(data: any) {
	return request.post({ url: "/interview/checkInterview", data });
}
