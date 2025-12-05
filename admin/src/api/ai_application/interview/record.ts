import request from "@/utils/request";

// 获取面试记录列表
export const getInterviewRecordList = (params: any) => {
    return request.get({ url: "/interview.interviewRecord/lists", params });
};

// 面试记录详情
export const getInterviewRecordDetail = (params: any) => {
    return request.get({ url: "/interview.interview/detail", params });
};

// 删除面试记录
export const deleteInterviewRecord = (params: any) => {
    return request.post({ url: "/interview.interviewRecord/delete", data: params });
};
