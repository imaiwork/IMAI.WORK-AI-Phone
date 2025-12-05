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

// 向量知识库切片详情
export function knowKnowledgeVectorFileDetail(params?: any) {
    return request.get({ url: "/kb.know/fileDatas", params });
}
