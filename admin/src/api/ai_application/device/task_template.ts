import request from "@/utils/request";

// 获取分类列表
export function getTaskTemplateCateList(params: any) {
    return request.get({ url: "/marketing.category/lists", params });
}

// 新增分类
export function addTaskTemplateCate(data: any) {
    return request.post({ url: "/marketing.category/add", data });
}

// 编辑分类
export function editTaskTemplateCate(data: any) {
    return request.post({ url: "/marketing.category/edit", data });
}

// 删除分类
export function deleteTaskTemplateCate(data: any) {
    return request.post({ url: "/marketing.category/delete", data });
}

// 获取模板列表
export function getTaskTemplateList(params: any) {
    return request.get({ url: "/marketing.template/lists", params });
}

// 新增模板
export function addTaskTemplate(data: any) {
    return request.post({ url: "/marketing.template/add", data });
}

// 编辑模板
export function editTaskTemplate(data: any) {
    return request.post({ url: "/marketing.template/edit", data });
}

// 删除模板
export function deleteTaskTemplate(data: any) {
    return request.post({ url: "/marketing.template/delete", data });
}

// 修改模板状态
export function updateTaskTemplateStatus(data: any) {
    return request.post({ url: "/marketing.template/updateStatus", data });
}

// 获取模板详情
export function getTaskTemplateDetail(params: any) {
    return request.get({ url: "/marketing.template/detail", params });
}
