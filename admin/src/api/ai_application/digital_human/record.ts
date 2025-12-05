import request from "@/utils/request";

// 形象记录
export function getAnchorRecord(params?: any) {
    return request.get({ url: "/human.anchor/lists", params });
}

// 删除形象记录
export function deleteAnchorRecord(params?: any) {
    return request.post({ url: "/human.anchor/delete", params });
}

// 音色记录
export function getVoiceChatRecord(params?: any) {
    return request.get({ url: "/human.voice/lists", params });
}

// 删除音色记录
export function deleteVoiceChatRecord(params?: any) {
    return request.post({ url: "/human.voice/delete", params });
}

// 音频记录
export function getAudioRecord(params?: any) {
    return request.get({ url: "/human.audio/lists", params });
}

// 删除音频记录
export function deleteAudioRecord(params?: any) {
    return request.post({ url: "/human.audio/delete", params });
}

// 视频记录
export function getVideoRecord(params?: any) {
    return request.get({ url: "/human.video/lists", params });
}

// 删除视频记录
export function deleteVideoRecord(params?: any) {
    return request.post({ url: "/human.video/delete", params });
}
