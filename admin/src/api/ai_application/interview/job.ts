import request from "@/utils/request";

// 获取面试岗位列表
export const getInterviewJobList = (params: any) => {
	return request.get({ url: "/interview.interviewJob/lists", params });
};

// 岗位详情
export const getInterviewJobDetail = (params: any) => {
	return request.get({ url: "/interview.interviewJob/detail", params });
};

// 岗位删除
export const interviewJobDelete = (params: any) => {
	return request.post({
		url: "/interview.interviewJob/delete",
		data: params,
	});
};

// 岗位状态
export const interviewJobStatus = (params: any) => {
	return request.post({
		url: "/interview.interviewJob/changeStatus",
		data: params,
	});
};
