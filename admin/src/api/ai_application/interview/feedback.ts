import request from "@/utils/request";

// 获取面试反馈列表
export const getInterviewFeedbackList = (params: any) => {
	return request.get({ url: "/interview.feedback/lists", params });
};

// 删除面试反馈
export const deleteInterviewFeedback = (params: any) => {
	return request.post({ url: "/interview.feedback/delete", data: params });
};
