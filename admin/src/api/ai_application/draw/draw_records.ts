import request from "@/utils/request";

// 绘图记录
export function getDrawRecordList(params: any) {
    return request.get({ url: "/hd.hdImage/lists", params });
}

// 绘图记录删除
export function delDrawRecord(params: any) {
    return request.post({ url: "/hd.hdImage/delete", params });
}

// 视频记录
export function getDrawRecordVideoList(params: any) {
    return request.get({ url: "/draw.videoRecord/lists", params });
}
// 删除视频记录
export function delDrawRecordVideo(params: any) {
    return request.post({ url: "/draw.videoRecord/del", params });
}
