import request from "@/utils/request";

// 知识库列表
export function knowledgeBaseLists(data: any) {
    return request.get({ url: "/knowledge/lists", data });
}

// 向量知识库列表
export function vectorKnowledgeBaseLists(data: any) {
    return request.get({ url: "/kb.know/lists", data });
}
