import request from "@/utils/request";

// 发布记录
export function getMontageRecord(params: any) {
    return request.get({ url: "/shanjian.publish/lists", params });
}

// 删除发布记录
export function deleteMontageRecord(params: any) {
    return request.post({ url: "/shanjian.publish/delete", params });
}

// 发出记录状态修改
export function changeMontageRecordStatus(params: any) {
    return request.post({ url: "/shanjian.publish/change", params });
}

// 获取发布记录详情
export function getMontageRecordDetail(params: any) {
    return request.get({ url: "/shanjian.publish/detail", params });
}

// 发布记录详情列表
export function getMontageRecordDetailList(params: any) {
    return request.get({ url: "/shanjian.publish/detail/lists", params });
}

// 发布记录详情删除
export function deleteMontageRecordDetail(params: any) {
    return request.post({ url: "/shanjian.publish/detail/delete", params });
}

// 获取任务记录
export function getMontageTaskRecord(params: any) {
    return request.get({ url: "/shanjian.publish/recordLists", params });
}

// 删除任务记录
export function deleteMontageTaskRecord(params: any) {
    return request.post({ url: "/shanjian.publish/recordDelete", params });
}

// 获取创作记录
export function getMontageCreateRecord(params: any) {
    return request.get({ url: "/shanjian.shanjianVideoSetting/lists", params });
}

// 删除创作记录
export function deleteMontageCreateRecord(params: any) {
    return request.post({ url: "/shanjian.shanjianVideoSetting/delete", params });
}

// 获取创作视频记录
export function getMontageCreateVideoRecord(params: any) {
    return request.get({ url: "/shanjian.shanjianVideoTask/lists", params });
}

// 删除创作视频记录
export function deleteMontageCreateVideoRecord(params: any) {
    return request.post({ url: "/shanjian.shanjianVideoTask/delete", params });
}

// 形象列表
export function getMontageAnchorList(params: any) {
    return request.get({ url: "/shanjian.shanjianAnchor/lists", params });
}

// 删除形象
export function deleteMontageAnchor(params: any) {
    return request.post({ url: "/shanjian.shanjianAnchor/delete", params });
}

// 发布详情
export function getMontagePublishDetail(params: any) {
    return request.get({ url: "/shanjian.publish/detail", params });
}

// 发布详情记录
export function getMontagePublishDetailRecord(params: any) {
    return request.get({ url: "/shanjian.publish/recordLists", params });
}

// 发布详情记录删除
export function deleteMontagePublishDetailRecord(params: any) {
    return request.post({ url: "/shanjian.publish/recordDelete", params });
}

// sora任务列表
export function getMontageSoraTaskList(params: any) {
    return request.get({ url: "/sora.soraVideoSetting/lists", params });
}

// 删除sora任务
export function deleteMontageSoraTask(params: any) {
    return request.post({ url: "/sora.soraVideoSetting/delete", params });
}

// sora任务记录
export function getMontageSoraTaskRecord(params: any) {
    return request.get({ url: "/sora.soraVideoTask/lists", params });
}

// 删除sora任务记录
export function deleteMontageSoraTaskDetail(params: any) {
    return request.post({ url: "/sora.soraVideoTask/delete", params });
}

// 获取sora角色列表
export function getMontageSoraRoleList(params: any) {
    return request.get({ url: "/sora.SoraAnchor/lists", params });
}

// 删除sora角色
export function deleteMontageSoraRole(params: any) {
    return request.post({ url: "/sora.SoraAnchor/delete", params });
}

// 更新sora角色
export function updateMontageSoraRole(params: any) {
    return request.post({ url: "/sora.SoraAnchor/update", params });
}
