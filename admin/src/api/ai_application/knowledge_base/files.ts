import request from "@/utils/request";

// 知识库文件列表
export function getKnowledgeTrainingFiles(params: any) {
    return request.get({ url: "/knowledge.file/lists", params });
}

// 知识库文件删除
export function deleteKnowledgeTrainingFile(params: any) {
    return request.post({ url: "/knowledge.file/delete", params });
}

// 知识库切片详情
export function knowKnowledgeChunkDetail(params?: any) {
    return request.get({ url: "/knowledge.file/chunkLists", params });
}

// 向量知识库文件列表
export function knowKnowledgeVectorFileList(params?: any) {
    return request.get({ url: "/kb.know/files", params });
}

// 向量知识库文件删除
export function knowKnowledgeVectorFileDelete(params?: any) {
    return request.post({ url: "/kb.know/fileRemove", params });
}

// 向量知识库文件导入
export function knowKnowledgeVectorFileAdd(params?: any) {
    return request.post({ url: "/kb.teach/import", params });
}

// 向量知识库切片详情
export function knowKnowledgeVectorFileDetail(params?: any) {
    return request.get({ url: "/kb.know/fileDatas", params });
}

// 向量知识库文件分段列表
export function knowKnowledgeVectorFileChunkList(params?: any) {
    return request.get({ url: "/kb.teach/datas", params });
}

// 向量知识库文件分段添加
export function knowKnowledgeVectorFileChunkAdd(params?: any) {
    return request.post({ url: "/kb.teach/insert", params });
}

// 向量知识库文件分段删除
export function knowKnowledgeVectorFileChunkDelete(params?: any) {
    return request.post({ url: "/kb.teach/delete", params });
}

// 向量知识库文件分段编辑
export function knowKnowledgeVectorFileChunkEdit(params?: any) {
    return request.post({ url: "/kb.teach/update", params });
}

// 向量知识库文件分段详情
export function knowKnowledgeVectorFileChunkDetail(params?: any) {
    return request.get({ url: "/kb.teach/detail", params });
}

// 向量知识库命中测试
export function knowKnowledgeVectorHitTest(params?: any) {
    return request.post({ url: "/kb.teach/tests", params });
}

// 向量知识库命中测试历史列表
export function knowKnowledgeVectorHitTestHistoryList(params?: any) {
    return request.get({ url: "/kb.teach/testRecords", params });
}

// 向量知识库命中测试历史详情
export function knowKnowledgeVectorHitTestHistoryDetail(params?: any) {
    return request.get({ url: "/kb.teach/testRecordDetail", params });
}

// 网页解析
export function knowKnowledgeWebHtmlCapture(params?: any) {
    return request.post({ url: "/kb.teach/capture", params });
}
