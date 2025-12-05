import request from "@/utils/request";

// 助手分类列表
export function getAssistantCategoryList(params: any) {
	return request.get({ url: "/assistants.scene/lists", params });
}

// 助手分类详情
export function getAssistantCategoryDetail(params: any) {
	return request.get({ url: "/assistants.scene/detail", params });
}

// 新增助手分类
export function assistantCategoryAdd(params: any) {
	return request.post({ url: "/assistants.scene/add", params });
}

// 编辑助手分类
export function assistantCategoryEdit(params: any) {
	return request.post({ url: "/assistants.scene/edit", params });
}

// 删除助手分类
export function assistantCategoryDelete(params: any) {
	return request.post({ url: "/assistants.scene/delete", params });
}

// 更新助手分类状态
export function assistantCategoryStatus(params: any) {
	return request.post({ url: "/assistants.scene/changeStatus", params });
}

// 导入助理分类
export function assistantCategoryImport() {
	return request.post({ url: "/assistants.scene/import" });
}

// 导入检查
export function assistantCategoryImportCheck() {
	return request.post({ url: "/assistants.scene/check" });
}
