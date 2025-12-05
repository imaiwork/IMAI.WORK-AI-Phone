import request from "@/utils/request";
import type { FileParams } from "@/utils/request/type";

// 配置
export function getConfig() {
    return request.get({ url: "/config/getConfig" });
}

// 保存配置
export function saveConfig(params: any) {
    return request.post({ url: "/config/setConfig", params });
}

// 平台版权名称修改
export function saveAuthByName(params: any) {
    return request.post({ url: "/config/updateByname", params });
}

// 工作台主页
export function getWorkbench() {
    return request.get({ url: "/workbench/index" });
}

//字典数据
export function getDictData(params: any) {
    return request.get({ url: "/config/dict", params });
}

// 上传文件
type type = "image" | "video" | "file";
export function uploadFile(type: type, params: FileParams, onProgress?: ((progressEvent: any) => void) | undefined) {
    return request.uploadFile(
        {
            url: `/upload/${type}`,
            headers: params.header,
            onUploadProgress(progressEvent) {
                onProgress && onProgress(progressEvent);
            },
        },
        params
    );
}
