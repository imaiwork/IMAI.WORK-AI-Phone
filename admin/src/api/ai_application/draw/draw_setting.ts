import request from "@/utils/request";

// SD绘画配置详情
export function getDeawConfig() {
    return request.get({ url: "/setting.ai.draw/detail" });
}

// SD绘画配置保存
export function setDeawConfig(params: any) {
    return request.post({ url: "/setting.ai.draw/save", params });
}
