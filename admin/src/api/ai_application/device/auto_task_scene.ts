import request from "@/utils/request";

/** 获取自动任务场景配置 */
export function getAutoTaskSceneConfig() {
    return request.get({ url: "/setting.autoTaskScene/getConfig" });
}

/** 保存自动任务场景配置 */
export function setAutoTaskSceneConfig(data: { items: Array<Record<string, any>> }) {
    return request.post({ url: "/setting.autoTaskScene/setConfig", data });
}
