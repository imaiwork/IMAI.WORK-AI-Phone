import request from '@/utils/request'

// 知识库列表
export function knowledgeBaseLists(data: any) {
    return request.get({ url: '/knowledge/lists', data })
}

// 向量知识库列表
export function vectorKnowledgeBaseLists(data: any) {
    return request.get({ url: '/kb.know/lists', data })
}

// 向量知识库新增
export function vectorKnowledgeBaseAdd(data: any) {
    return request.post({ url: '/kb.know/add', data })
}

// 向量知识库编辑
export function vectorKnowledgeBaseEdit(data: any) {
    return request.post({ url: '/kb.know/edit', data })
}

// 向量知识库详情
export function vectorKnowledgeBaseDetail(data: any) {
    return request.get({ url: '/kb.know/detail', data })
}

// 向量知识库删除（仅创建者）
export function vectorKnowledgeBaseDelete(data: any) {
    return request.post({ url: '/kb.know/del', data })
}

// 向量知识库文件列表
export function vectorKnowledgeBaseFileLists(data: any) {
    return request.get({ url: '/kb.know/files', data })
}

// 向量知识库文件删除
export function vectorKnowledgeBaseFileDelete(data: any) {
    return request.post({ url: '/kb.know/fileRemove', data })
}

// 向量知识库文件导入（上传后将文件交给服务端解析入库）
export function vectorKnowledgeBaseFileImport(data: any) {
    return request.post({ url: '/kb.teach/import', data })
}

// 向量知识库网页解析
export function vectorKnowledgeBaseWebHtmlCapture(data: any) {
    return request.post({ url: '/kb.teach/capture', data })
}

// 向量知识库命中测试（搜索测试）
export function vectorKnowledgeBaseHitTest(data: any) {
    return request.post({ url: '/kb.teach/tests', data })
}

// 向量知识库命中测试记录列表
export function vectorKnowledgeBaseHitTestRecords(data: any) {
    return request.get({ url: '/kb.teach/testRecords', data })
}

// 向量知识库命中测试记录详情
export function vectorKnowledgeBaseHitTestRecordDetail(data: any) {
    return request.get({ url: '/kb.teach/testRecordDetail', data })
}

// 向量知识库文件分段列表
export function vectorKnowledgeBaseChunkLists(data: any) {
    return request.get({ url: '/kb.teach/datas', data })
}

// 向量知识库文件分段新增
export function vectorKnowledgeBaseChunkAdd(data: any) {
    return request.post({ url: '/kb.teach/insert', data })
}

// 向量知识库文件分段编辑
export function vectorKnowledgeBaseChunkEdit(data: any) {
    return request.post({ url: '/kb.teach/update', data })
}

// 向量知识库文件分段删除
export function vectorKnowledgeBaseChunkDelete(data: any) {
    return request.post({ url: '/kb.teach/delete', data })
}

// 向量知识库文件分段详情
export function vectorKnowledgeBaseChunkDetail(data: any) {
    return request.get({ url: '/kb.teach/detail', data })
}
