import request from "@/utils/request";

// ---- 概览 ----
export function getGeoOverview(params?: any) {
    return request.get({ url: "/geo.overview/index", params });
}

// ---- 项目管理 ----
export function getGeoProjectList(params: any) {
    return request.get({ url: "/geo.project/lists", params });
}

export function getGeoProjectDetail(params: any) {
    return request.get({ url: "/geo.project/detail", params });
}

export function setGeoProjectAutoMonitor(data: any) {
    return request.post({ url: "/geo.project/setAutoMonitor", data });
}

export function deleteGeoProject(data: any) {
    return request.post({ url: "/geo.project/delete", data });
}

// ---- 媒体库 ----
export function getGeoMediaList(params: any) {
    return request.get({ url: "/geo.media/lists", params });
}

export function getGeoMediaOptions() {
    return request.get({ url: "/geo.media/options" });
}

export function addGeoMedia(data: any) {
    return request.post({ url: "/geo.media/add", data });
}

export function editGeoMedia(data: any) {
    return request.post({ url: "/geo.media/edit", data });
}

export function setGeoMediaStatus(data: any) {
    return request.post({ url: "/geo.media/status", data });
}

export function deleteGeoMedia(data: any) {
    return request.post({ url: "/geo.media/delete", data });
}

// ---- 发布记录 ----
export function getGeoPublishList(params: any) {
    return request.get({ url: "/geo.publish/lists", params });
}

export function deleteGeoPublish(data: any) {
    return request.post({ url: "/geo.publish/delete", data });
}
