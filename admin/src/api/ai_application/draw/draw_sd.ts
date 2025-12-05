import request from "@/utils/request";

//图片灵感分类
export function getModelCategoryList(params: any) {
	return request.get({ url: "/hd.hdCueImageCategory/lists", params });
}

//新增图片灵感分类
export function addModelCategory(params: any) {
	return request.post({ url: "/hd.hdCueImageCategory/add", params });
}

//编辑图片灵感分类
export function editModelCategory(params: any) {
	return request.post({ url: "/hd.hdCueImageCategory/edit", params });
}

//删除图片灵感分类
export function delModelCategory(params: any) {
	return request.post({ url: "/hd.hdCueImageCategory/delete", params });
}

//修改图片灵感分类状态
export function editModelCategoryStatus(params: any) {
	return request.get({ url: "/hd.hdCueImageCategory/updateStatus", params });
}

// 图片灵感列表
export function getModelList(params: any) {
	return request.get({ url: "/hd.hdCueImage/lists", params });
}

// 获取图片灵感详情
export function getModelDetail(params: any) {
	return request.get({ url: "/hd.hdCueImage/detail", params });
}

// 新增图片灵感

export function addModel(params: any) {
	return request.post({ url: "/hd.hdCueImage/add", params });
}

// 编辑图片灵感

export function editModel(params: any) {
	return request.post({ url: "/hd.hdCueImage/edit", params });
}

// 删除图片灵感

export function delModel(params: any) {
	return request.post({ url: "/hd.hdCueImage/delete", params });
}
// 修改图片灵感状态
export function editModelStatus(params: any) {
	return request.post({ url: "/hd.hdCueImage/status", params });
}

// 快速组装分类列表
export function getDrawCategoryList(params?: any) {
	return request.get({ url: "/hd.hdCueWordCategory/lists", params });
}

// 新增快速组装分类
export function addDrawCategory(params: any) {
	return request.post({ url: "/hd.hdCueWordCategory/add", params });
}

// 新增快速组装分类
export function editDrawCategory(params: any) {
	return request.post({ url: "/hd.hdCueWordCategory/edit", params });
}

// 删除快速组装分类
export function delDrawCategory(params: any) {
	return request.post({ url: "/hd.hdCueWordCategory/delete", params });
}

// 修改状态
export function editDrawCategoryStatus(params: any) {
	return request.post({ url: "/hd.hdCueWordCategory/status", params });
}

// 全部快速组装分类列表
export function allDrawCategoryList() {
	return request.get({ url: "/hd.hdCueWordCategory/all" });
}

// 快速组装分类列表
export function getPromptList(params: any) {
	return request.get(
		{ url: "/hd.hdCueWord/lists", params },
		{
			ignoreCancelToken: true,
		}
	);
}

//新增快速组装示例
export function addPrompt(params: any) {
	return request.post({ url: "/hd.hdCueWord/add", params });
}

//编辑快速组装示例
export function editPrompt(params: any) {
	return request.post({ url: "/hd.hdCueWord/edit", params });
}

//删除快速组装示例
export function delPrompt(params: any) {
	return request.post({ url: "/hd.hdCueWord/delete", params });
}
//修改状态
export function editPromptStatus(params: any) {
	return request.post({ url: "/hd.hdCueWord/status", params });
}
