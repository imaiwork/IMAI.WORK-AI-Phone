import request from "@/utils/request";

// ---- 概览 ----
export function getHotspotOverview(params?: any) {
    return request.get({ url: "/hotspot.overview/index", params });
}

// ---- 热榜快照 ----
export function getHotspotHotList(params: any) {
    return request.get({ url: "/hotspot.hot/lists", params });
}

export function getHotspotHistoryDates(params: any) {
    return request.get({ url: "/hotspot.hot/historyDates", params });
}

// ---- 分析记录 ----
export function getHotspotAnalysisList(params: any) {
    return request.get({ url: "/hotspot.analysis/lists", params });
}

export function getHotspotAnalysisDetail(params: any) {
    return request.get({ url: "/hotspot.analysis/detail", params });
}

export function deleteHotspotAnalysis(data: any) {
    return request.post({ url: "/hotspot.analysis/delete", data });
}

// ---- 创作记录 ----
export function getHotspotCreationList(params: any) {
    return request.get({ url: "/hotspot.creation/lists", params });
}

export function getHotspotCreationDetail(params: any) {
    return request.get({ url: "/hotspot.creation/detail", params });
}

export function deleteHotspotCreation(data: any) {
    return request.post({ url: "/hotspot.creation/delete", data });
}

// ---- 视频任务 ----
export function getHotspotTaskList(params: any) {
    return request.get({ url: "/hotspot.task/lists", params });
}

export function getHotspotTaskDetail(params: any) {
    return request.get({ url: "/hotspot.task/detail", params });
}

export function retryHotspotTask(data: any) {
    return request.post({ url: "/hotspot.task/retry", data });
}

export function deleteHotspotTask(data: any) {
    return request.post({ url: "/hotspot.task/delete", data });
}
