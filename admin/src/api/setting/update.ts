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

// 执行 SQL 文件
export function sqlExecute(file: string) {
    return request.post({ url: "/update/sqlExecute", data: { file } });
}

// 获取远程版本信息
export function remoteVersion() {
    return request.get({ url: "/update/remoteVersion" });
}

// 更新版本号
export function versionUpdate() {
    return request.post({ url: "/update/versionUpdate" });
}

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
