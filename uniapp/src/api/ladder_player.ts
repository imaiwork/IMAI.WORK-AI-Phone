import request, { RequestEventStreamConfig } from "@/utils/request";

// 场景列表
export function lpSceneLists(data?: any) {
    return request.get({ url: "/lianlian/sceneLists", data });
}

// 场景添加
export function lpSceneAdd(data?: any) {
    return request.post({ url: "/lianlian/sceneAdd", data });
}

// 场景编辑
export function lpSceneEdit(data?: any) {
    return request.post({ url: "/lianlian/sceneEdit", data });
}

// 场景删除
export function lpSceneDelete(data?: any) {
    return request.post({ url: "/lianlian/sceneDelete", data });
}

// 场景详情
export function lpSceneDetail(data?: any) {
    return request.get({ url: "/lianlian/sceneDetail", data });
}

// 场景报告
export function lpSceneReport(data?: any) {
    return request.get({ url: "/lianlian/sceneReport", data });
}

// 场景开始对话
export function lpSceneChatStart(data?: any) {
    return request.post({ url: "/lianlian/startChat", data });
}

// 场景继续对话
export function lpSceneChatContinue(data?: any) {
    return request.post({ url: "/lianlian/continueChat", data });
}

// 场景结束对话
export function lpSceneChatEnd(data?: any) {
    return request.post({ url: "/lianlian/endChat", data });
}

// 话术提示
export function lpSceneChatTips(data?: any) {
    return request.post({ url: "/lianlian/speechcraftChat", data });
}

// 对话表现
export function lpScenePerformance(data?: any) {
    return request.post({ url: "/lianlian/performanceChat", data });
}

// 语音转文字
export function lpSceneSpeechToText(data?: any) {
    return request.post({ url: "/lianlian/chatSTT", data });
}

// 分析列表
export function lpAnalysisLists(data?: any) {
    return request.get({ url: "/lianlian/analysisLists", data });
}

// 分析删除
export function lpAnalysisDelete(data?: any) {
    return request.post({ url: "/lianlian/analysisDelete", data });
}

// 重新分析
export function lpAnalysisRetry(data?: any) {
    return request.post({ url: "/lianlian/analysisRetry", data });
}

// 分析详情
export function lpAnalysisDetail(data?: any) {
    return request.get({ url: "/lianlian/analysisDetail", data });
}

// 分析数据
export function lpAnalysisData() {
    return request.get({ url: "/lianlian/analysisWorkbench" });
}

// 聊天记录
export function lpRecordLists(data?: any) {
    return request.get({ url: "/lianlian/analysisChatLog", data });
}

// 知识库训练
export function lpKnbTrain(data?: any) {
    return request.post({ url: "/knowledge/ladderPlayerUpload", data });
}
