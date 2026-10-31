import request from "@/utils/request";

// 闪剪剪辑模板（内容由模板中心每 5 分钟同步到本站，后台只维护显隐与本站排序）
export function getClipTemplateLists(params: any) {
    return request.get({ url: "/shanjian.shanjianClipTemplate/lists", params: { ...params, manage: 1 } });
}

// 本站显隐：1 前台显示，0 隐藏（隐藏后用户端列表、手动创作、24 小时创作都不再使用）
export function changeClipTemplateStatus(params: { id: string; status: number }) {
    return request.post({ url: "/shanjian.shanjianClipTemplate/changeStatus", params });
}

// 本站排序：sort 传空串表示恢复跟随模板中心
export function updateClipTemplateSort(params: { id: string; sort: number | "" }) {
    return request.post({ url: "/shanjian.shanjianClipTemplate/updateSort", params });
}
