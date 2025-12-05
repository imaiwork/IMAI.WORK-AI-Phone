// 岗位列表
export function getJobList(params: any) {
    return $request.get({ url: "/interview.interviewJob/lists", params });
}

// 新增岗位
export function addJob(params: any) {
    return $request.post({ url: "/interview.interviewJob/add", params });
}

// 编辑岗位
export function editJob(params: any) {
    return $request.post({ url: "/interview.interviewJob/edit", params });
}

// 编辑岗位RPA
export function editJobRpa(params: any) {
    return $request.post({ url: "/interview.interviewConfig/edit", params });
}

// 岗位详情
export function getJobDetail(params: any) {
    return $request.post({ url: "/interview.interviewJob/detail", params });
}

// 删除岗位
export function deleteJob(params: any) {
    return $request.get({ url: "/interview.interviewJob/delete", params });
}

// 生成所有面试岗位的链接
export function generateJobAllLink() {
    return $request.post({ url: "/interview.interview/myJobLink" });
}

// 生成岗位的链接
export function generateJobLink(params: any) {
    return $request.get({ url: "/interview.interview/jobLink", params });
}

// 面试记录
export function getInterviewRecord(params: any) {
    return $request.get({ url: "/interview.InterviewRecord/lists", params });
}

// 重新分析面试记录
export function reanalyzeInterviewRecord(params: any) {
    return $request.get({ url: "/interview.InterviewRecord/updateStatus", params });
}

// 面试详情
export function getInterviewRecordDetail(params: any) {
    return $request.get({ url: "/interview.interview/detail", params });
}

// 删除面试记录
export function deleteInterviewRecord(params: any) {
    return $request.post({
        url: "/interview.InterviewRecord/batchDelete",
        params,
    });
}

// 面试统计数据
export function getInterviewStatistics() {
    return $request.get({ url: "/interview.interview/stat" });
}
