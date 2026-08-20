import request from "@/utils/request";

export type StorageEngine = "local" | "qiniu" | "aliyun" | "qcloud";
export type MediaProcessMode = "local" | "oss";

export interface StorageSetupParams {
    engine: StorageEngine | string;
    status: number;
    bucket?: string;
    access_key?: string;
    secret_key?: string;
    domain?: string;
    region?: string;
    /** 仅阿里云：local | oss，默认 local。七牛/腾讯不传，服务端固定 local */
    media_process?: MediaProcessMode;
    Location?: string;
    PipelineId?: string;
    TemplateId?: string;
}

export interface StorageDetailParams {
    engine: StorageEngine | string;
}

export interface StorageMigrationParams {
    engine: StorageEngine | string;
    status: number;
    migration: number;
}

// 获取存储引擎列表
export function storageLists() {
    return request.get({ url: "/setting.storage/lists" });
}

// 切换存储引擎
export function storageChange(params: { engine: string }) {
    return request.post({ url: "/setting.storage/change", params });
}

// 设置存储引擎信息
export function storageSetup(params: StorageSetupParams) {
    return request.post({ url: "/setting.storage/setup", params });
}

// 获取存储配置信息
export function storageDetail(params: StorageDetailParams) {
    return request.get({ url: "/setting.storage/detail", params });
}

// 上传本地文件
export function storageMigration(params: StorageMigrationParams) {
    return request.post({ url: "/setting.storage/migration", params });
}
