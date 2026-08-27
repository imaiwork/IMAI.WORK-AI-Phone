import request from "@/utils/request";

// 旧版更新接口（/update/lists、/update/check、/update/exec）已在后端停用，统一走下方热更新流程

// 获取文件差异列表
export function fileCompare() {
    return request.get({ url: "/update/fileCompare" });
}

export function fileDiff(file: string) {
    return request.post({ url: "/update/fileDiff", data: { file } });
}

// 同步单个文件
export function fileSync(file: string) {
    return request.post({ url: "/update/fileSync", data: { file } });
}

// 静默同步单个文件
export function fileSyncSilent() {
    return request.post({ url: "/update/fileSyncSilent" });
}

// 获取数据库结构差异
export function dbCompare() {
    return request.get({ url: "/update/dbCompare" });
}

// 执行第 N 条升级 SQL
export function dbExecute(cacheKey: string, index: number) {
    return request.post({
        url: "/update/dbExecute",
        data: { cache_key: cacheKey, index },
    });
}

// 执行 SQL 文件
export function sqlCompare() {
    return request.post({ url: "/update/sqlCompare" });
}

/**
 * 执行 SQL 文件
 * 返回原始响应 { code, msg, data }：失败时 data 里带断点信息（done / total / failed_at / statement），
 * 默认拦截器会把 data 丢掉，所以这里关闭 transform，由页面自行处理
 */
export function sqlExecute(file: string): Promise<RawResponse> {
    return request.post({ url: "/update/sqlExecute", data: { file } }, { isTransformResponse: false });
}

export interface RawResponse<T = any> {
    code: number;
    msg: string;
    data: T;
    show?: number;
}

// 获取远程版本信息
export function remoteVersion() {
    return request.get({ url: "/update/remoteVersion" });
}

/**
 * 更新版本号
 * 返回原始响应：失败时 data.pending 为未执行的 SQL 文件列表；force=1 强制写入
 */
export function versionUpdate(force = false): Promise<RawResponse> {
    return request.post(
        { url: "/update/versionUpdate", data: force ? { force: 1 } : {} },
        { isTransformResponse: false },
    );
}

// 清理 OPcache（批量 fileSync 后调用一次）
export const opcacheReset = () => request.post({ url: "/update/opcacheReset" });

// 手动备份数据库
export const dbBackup = (tag?: string) =>
    request.post({ url: "/update/dbBackup", data: tag ? { tag } : {} });

// 备份列表
export const dbBackupList = () => request.get({ url: "/update/dbBackupList" });

// 检查版本号
export function checkVersion() {
    return request.get({ url: "/update/checkVersion" });
}

export const getNotice = () => request.get({ url: "/update/notice" });

// 完整差异比对（普通文件 + 覆盖目录变更摘要）
export const fullCompare = () => request.get({ url: "/update/fullCompare" });

export const overwriteAllByZip = (dirs?: string[]) =>
    request.post({
        url: "/update/overwriteAllByZip",
        data: dirs && dirs.length > 0 ? { dirs } : {},
    });

export const overwriteDir = (dir: string) => overwriteAllByZip([dir]);
