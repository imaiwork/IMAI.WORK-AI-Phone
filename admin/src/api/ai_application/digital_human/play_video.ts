import request from "@/utils/request";

// 爆款玩法创作记录
export function getPlayVideoTaskLists(params: any) {
    return request.get({ url: "/playVideo.playVideoTask/lists", params });
}

// 爆款玩法模板（只读，模板中心每小时同步到本站）
export function getPlayVideoTemplateLists(params: any) {
    return request.get({ url: "/playVideo.playVideoTemplate/lists", params });
}

// 爆款玩法模板本地显隐（不影响模板中心）
export function changePlayVideoTemplateStatus(params: { id: string; status: number }) {
    return request.post({ url: "/playVideo.playVideoTemplate/changeStatus", params });
}
