import request from "@/utils/request";

// 助手模型列表
export function getAssistantModelList(params: any) {
    return request.get({ url: "/assistants.assistants/lists", params });
}

// 助手模型列表
export function getAssistantModelDetail(params: any) {
    return request.get({ url: "/assistants.assistants/detail", params });
}

// 新增助手模型
export function assistantModelAdd(params: any) {
    return request.post({ url: "/assistants.assistants/add", params });
}

// 编辑助手模型
export function assistantModelEdit(params: any) {
    return request.post({ url: "/assistants.assistants/edit", params });
}

// 编辑助手详情
export function assistantModelDetail(params: any) {
    return request.get({ url: "/assistants.assistants/detail", params });
}

// 删除助手模型
export function assistantModelDelete(params: any) {
    return request.post({ url: "/assistants.assistants/delete", params });
}

// 更新助手模型状态
export function assistantModelStatus(params: any) {
    return request.post({ url: "/assistants.assistants/changeStatus", params });
}

// 助手模型导入
export function assistantModelImport() {
    return request.post({ url: "/assistants.assistants/import" });
}

// 助手模型导入检查
export function assistantModelImportCheck() {
    return request.get({ url: "/assistants.assistants/check" });
}
