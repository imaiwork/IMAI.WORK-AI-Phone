import request from "@/utils/request";

// 敏感词列表
export function getSensitiveLists(params: any) {
    return request.get({ url: "/setting.sensitiveWord/lists", params });
}

// 添加敏感词
export function addSensitive(data: any) {
    return request.post({ url: "/setting.sensitiveWord/add", data });
}

// 编辑敏感词
export function editSensitive(data: any) {
    return request.post({ url: "/setting.sensitiveWord/edit", data });
}

// 删除敏感词
export function delSensitive(data: any) {
    return request.post({ url: "/setting.sensitiveWord/del", data });
}

// 设置敏感词配置
export function setSensitiveConfig(data: any) {
    return request.post({ url: "/setting.sensitiveWord/setConfig", data });
}

// 获取敏感词配置
export function getSensitiveConfig() {
    return request.get({ url: "/setting.sensitiveWord/getConfig" });
}
