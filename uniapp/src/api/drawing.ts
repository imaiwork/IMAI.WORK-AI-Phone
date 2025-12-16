import request from "@/utils/request";

// 生成图片记录
export function drawingRecord(data: any) {
    return request.get({
        url: "/hd/lists",
        data,
    });
}

// 生成视频记录
export function drawingVideoRecord(data: any) {
    return request.get({
        url: "/volc/lists",
        data,
    });
}

// 删除
export function drawingDelete(data: any) {
    return request.post({
        url: "/hd/deleteImage",
        data,
    });
}
// 删除视频
export function drawingVideoDelete(data: any) {
    return request.post({
        url: "/volc/deleteVideo",
        data,
    });
}
// 删除记录
export function drawingDeleteRecord(data: any) {
    return request.post({
        url: "/hd/drawDelete",
        data,
    });
}

// 生成拼图文案
export function generatePuzzlePrompt(data: any) {
    return request.post(
        {
            url: "/sv.tools/getTitle",
            data,
        },
        {
            ignoreCancel: true,
        }
    );
}

// 拼图任务创建
export function createPuzzleTask(data: any) {
    return request.post({
        url: "/hd.puzzleSetting/add",
        data,
    });
}

// 拼图任务列表
export function getPuzzleTaskList(data: any) {
    return request.get({
        url: "/hd.puzzleSetting/lists",
        data,
    });
}

// 拼图任务删除
export function deletePuzzleTask(data: any) {
    return request.post({
        url: "/hd.puzzleSetting/delete",
        data,
    });
}

// 拼图任务详情
export function getPuzzleTaskDetail(data: any) {
    return request.get({
        url: "/hd.puzzleSetting/detail",
        data,
    });
}

// 拼图任务详情结果列表
export function getPuzzleTaskResultList(data: any) {
    return request.get({
        url: "/hd.puzzle/lists",
        data,
    });
}
