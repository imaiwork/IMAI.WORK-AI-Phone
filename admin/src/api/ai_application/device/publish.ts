import request from "@/utils/request";

// 获取发布列表
export function getPublishList(params: any) {
    return request.get({ url: "/device.publish/lists", params });
}

// 删除发布
export function deletePublish(params: any) {
    return request.post({ url: "/device.publish/delete", data: params });
}

// 获取发布记录详情
export function getPublishRecordDetail(params: any) {
    return request.get({ url: "/device.publish/recordLists", params });
}

// 获取发布记录详情
export function getPublishRecordList(params: any) {
    return request.get({ url: "/device.publish/recordLists", params });
}

// 删除发布记录详情
export function deletePublishRecord(params: any) {
    return request.post({ url: "/device.publish/recordDelete", data: params });
}
