// 知识库列表
export function knowledgeBaseLists(params?: any) {
    return $request.get({ url: "/knowledge/lists", params });
}
// 知识库新增
export function knowledgeBaseAdd(params: any) {
    return $request.post({ url: "/knowledge/add", params });
}

// 知识库删除
export function knowledgeBaseDelete(params: any) {
    return $request.post({ url: "/knowledge/delete", params });
}

// 知识库编辑
export function knowledgeBaseEdit(params: any) {
    return $request.post({ url: "/knowledge/edit", params });
}

// 知识库详情
export function knowledgeBaseDetail(params: any) {
    return $request.get({ url: "/knowledge/detail", params });
}

// 知识库文件追加
export function knowledgeBaseFileAdd(params: any) {
    return $request.post({ url: "/knowledge/fileAdd", params });
}

// 命中测试
export function knowledgeBaseHitTest(params: any) {
    return $request.post({ url: "/knowledge/retrieve", params });
}

// 命中测试历史列表
export function knowledgeBaseHitTestHistoryLists(params: any) {
    return $request.post({ url: "/knowledge/historyTest", params });
}

// 命中测试历史详情
export function knowledgeBaseHitTestHistoryDetail(params: any) {
    return $request.get({ url: "/knowledge/testDetail", params });
}

// 知识库文件列表
export function knowledgeBaseFileLists(params: any) {
    return $request.get({ url: "/knowledge/fileLists", params });
}

// 知识库文件删除
export function knowledgeBaseFileDelete(params: any) {
    return $request.post({ url: "/knowledge/fileDelete", params });
}

// 知识库文件详情
export function knowledgeBaseFileDetail(params: any) {
    return $request.get({ url: "/knowledge/fileDetail", params });
}

// 知识库文件切片列表
export function knowledgeBaseFileChunkLists(params: any) {
    return $request.get({ url: "/knowledge/fileChunkLists", params });
}

// 知识库文件上传
export function knowledgeBaseFileUpload(params: any) {
    return $request.post({ url: "/knowledge/fileUpload", params });
}

// 向量知识库列表
export function vectorKnowledgeBaseLists(params: any) {
    return $request.get({ url: "/kb.know/lists", params });
}

// 向量知识库新增
export function vectorKnowledgeBaseAdd(params: any) {
    return $request.post({ url: "/kb.know/add", params });
}

// 向量知识库编辑
export function vectorKnowledgeBaseEdit(params: any) {
    return $request.post({ url: "/kb.know/edit", params });
}

// 向量知识库删除
export function vectorKnowledgeBaseDelete(params: any) {
    return $request.post({ url: "/kb.know/del", params });
}

// 向量知识库详情
export function vectorKnowledgeBaseDetail(params: any) {
    return $request.get({ url: "/kb.know/detail", params });
}

// 向量知识库文件列表
export function vectorKnowledgeBaseFileLists(params: any) {
    return $request.get({ url: "/kb.know/files", params });
}

// 向量知识库文件删除
export function vectorKnowledgeBaseFileDelete(params: any) {
    return $request.post({ url: "/kb.know/fileRemove", params });
}

// 向量知识库文件添加
export function vectorKnowledgeBaseFileAdd(params: any) {
    return $request.post({ url: "/kb.teach/import", params });
}

// 向量知识库文件切片列表
export function vectorKnowledgeBaseFileChunkLists(params: any) {
    return $request.get({ url: "/kb.teach/datas", params });
}

// 向量知识库文件分段添加
export function vectorKnowledgeBaseFileChunkAdd(params: any) {
    return $request.post({ url: "/kb.teach/insert", params });
}

// 向量知识库文件分段删除
export function vectorKnowledgeBaseFileChunkDelete(params: any) {
    return $request.post({ url: "/kb.teach/delete", params });
}

// 向量知识库文件分段编辑
export function vectorKnowledgeBaseFileChunkEdit(params: any) {
    return $request.post({ url: "/kb.teach/update", params });
}

// 向量知识库文件分段详情
export function vectorKnowledgeBaseFileChunkDetail(params: any) {
    return $request.get({ url: "/kb.teach/detail", params });
}

// 向量知识库命中测试
export function vectorKnowledgeBaseHitTest(params: any) {
    return $request.post({ url: "/kb.teach/tests", params });
}

// 向量知识库命中测试历史列表
export function vectorKnowledgeBaseHitTestHistoryLists(params: any) {
    return $request.get({ url: "/kb.teach/testRecords", params });
}

//
export function vectorKnowledgeBaseHitTestHistoryDetail(params: any) {
    return $request.get({ url: "/kb.teach/testRecordDetail", params });
}

// 网页解析
export function webHtmlCapture(params: any) {
    return $request.post({ url: "/kb.teach/capture", params });
}
