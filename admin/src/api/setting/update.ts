import request from "@/utils/request";

export function getUpgradeLists(params: any) {
    return request.get({ url: "/update/lists", params });
}

// 检查更新
export function upgradeCheck(params: any) {
    return request.post({ url: "/update/check", params });
}

// 一键更新
export function upgrade(params: any) {
    return request.post({
        url: "/update/exec",
        params,
    });
}
