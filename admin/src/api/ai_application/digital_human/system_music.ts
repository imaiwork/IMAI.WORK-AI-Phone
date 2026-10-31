import request from "@/utils/request";

// 公共音乐库：两个来源共用一个菜单
// - center：模板中心每 5 分钟同步到本站，内容只读，后台只维护显隐与本站排序
// - local ：站长后台上传，可改名/删除，同步任务不会覆盖
export function getSystemMusicLists(params: any) {
    return request.get({ url: "/shanjian.systemMusic/lists", params });
}

// 上传音频到本站曲库目录（只落文件，曲目信息由 saveSystemMusic 落库）
export function uploadSystemMusic(file: File) {
    return request.uploadFile({ url: "/shanjian.systemMusic/upload" }, { file, name: "file" });
}

// 新增本站曲目（不传 id）或修改曲目名称（传 id）
export function saveSystemMusic(params: {
    id?: string;
    name: string;
    audio_url?: string;
    duration_sec?: number;
}) {
    return request.post({ url: "/shanjian.systemMusic/save", params });
}

// 删除本站上传的曲目（模板中心同步的曲目不可删，只能隐藏）
export function deleteSystemMusic(params: { id: string }) {
    return request.post({ url: "/shanjian.systemMusic/delete", params });
}

// 本站显隐：1 前台显示，0 隐藏（隐藏后前台列表、随机选曲、指定曲目都不可用）
export function changeSystemMusicStatus(params: { id: string; status: number }) {
    return request.post({ url: "/shanjian.systemMusic/changeStatus", params });
}

// 本站排序：sort 传空串表示清除本站覆盖（模板中心曲目即「跟随中心」）
export function updateSystemMusicSort(params: { id: string; sort: number | "" }) {
    return request.post({ url: "/shanjian.systemMusic/updateSort", params });
}

// 改曲目所属分组：group_id 传 0 表示「无分组」（模板中心同步的曲目也允许改，同步不会覆盖）
export function changeSystemMusicGroup(params: { id: string; group_id: number }) {
    return request.post({ url: "/shanjian.systemMusic/changeGroup", params });
}

// ── 分组 ────────────────────────────────────────────────────────────
// 分组只决定用户端选曲页的 tab 划分，不参与模板中心同步

export function getSystemMusicGroupLists(params: any) {
    return request.get({ url: "/shanjian.systemMusicGroup/lists", params });
}

// 下拉用：全部分组（不分页）
export function getAllSystemMusicGroups() {
    return request.get({ url: "/shanjian.systemMusicGroup/all" });
}

// 新增分组（不传 id）或改名（传 id）
export function saveSystemMusicGroup(params: { id?: number; name: string }) {
    return request.post({ url: "/shanjian.systemMusicGroup/save", params });
}

// 删除分组：组内曲目会退回「无分组」，不会跟着删
export function deleteSystemMusicGroup(params: { id: number }) {
    return request.post({ url: "/shanjian.systemMusicGroup/delete", params });
}

// 分组排序：小的在前，决定用户端 tab 顺序
export function updateSystemMusicGroupSort(params: { id: number; sort: number }) {
    return request.post({ url: "/shanjian.systemMusicGroup/updateSort", params });
}
