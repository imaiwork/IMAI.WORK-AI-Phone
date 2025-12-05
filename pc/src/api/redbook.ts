// 发布任务列表
export function getPublishTaskList(params: any) {
	return $request.get({ url: "/sv.publish/lists", params });
}

// 发布任务新增
export function addPublishTask(params: any) {
	return $request.post({ url: "/sv.publish/add", params });
}

// 发布任务更新
export function updatePublishTask(params: any) {
	return $request.post({ url: "/sv.publish/update", params });
}

// 发布任务删除
export function deletePublishTask(params: any) {
	return $request.post({ url: "/sv.publish/delete", params });
}

// 发布任务详情
export function getPublishTaskDetail(params: any) {
	return $request.get({ url: "/sv.publish/detail", params });
}

// 发布任务记录详情
export function getPublishRecordDetail(params: any) {
	return $request.get({ url: "/sv.publish/recordDetail", params });
}

// 发布任务状态修改
export function changePublishTaskStatus(params: any) {
	return $request.post({ url: "/sv.publish/change", params });
}

// 发布记录列表
export function getPublishRecordList(params: any) {
	return $request.get({ url: "/sv.publish/recordLists", params });
}

// 发布记录重试
export function retryPublishRecord(params: any) {
	return $request.post({ url: "/sv.publish/recordRetry", params });
}

// 发布记录删除
export function deletePublishRecord(params: any) {
	return $request.post({ url: "/sv.publish/recordDelete", params });
}

// 模拟发布视频任务
export function mockPublishTask(params: any) {
	return $request.post({ url: "/sv.publish/testAdd", params });
}

// 文案素材添加
export function addCopywritingMaterial(params: any) {
	return $request.post({ url: "/sv.mediaSetting/add", params });
}

// 文案素材详情
export function getCopywritingMaterialDetail(params: any) {
	return $request.get({ url: "/sv.mediaSetting/detail", params });
}

// 内容素材更新
export function updateCopywritingMaterial(params: any) {
	return $request.post({ url: "/sv.mediaSetting/update", params });
}

// 内容素材删除
export function deleteCopywritingMaterial(params: any) {
	return $request.post({ url: "/sv.mediaSetting/delete", params });
}

// 素材库列表
export function getMaterialLibraryList(params: any) {
	return $request.get({ url: "/sv.mediaMaterial/lists", params });
}

// 素材库新增
export function addMaterialLibrary(params: any) {
	return $request.post({ url: "/sv.mediaMaterial/add", params });
}

// 素材库更新
export function updateMaterialLibrary(params: any) {
	return $request.post({ url: "/sv.mediaMaterial/update", params });
}

// 素材库删除
export function deleteMaterialLibrary(params: any) {
	return $request.post({ url: "/sv.mediaMaterial/delete", params });
}

// 数字人新增
export function addDigitalHuman(params: any) {
	return $request.post({ url: "/sv.videoSetting/add", params });
}

// 数字人列表
export function getDigitalHumanList(params: any) {
	return $request.get({ url: "/sv.videoSetting/lists", params });
}

// 数字人更新
export function updateDigitalHuman(params: any) {
	return $request.post({ url: "/sv.videoSetting/update", params });
}

// 数字人删除
export function deleteDigitalHuman(params: any) {
	return $request.post({ url: "/sv.videoSetting/delete", params });
}

// 数字人详情
export function getDigitalHumanDetail(params: any) {
	return $request.get({ url: "/sv.videoSetting/detail", params });
}

// 数字人查看生成视频
export function getDigitalHumanVideo(params: any) {
	return $request.get({ url: "/sv.videoTask/lists", params });
}

// 数字人删除生成视频
export function deleteDigitalHumanVideo(params: any) {
	return $request.post({ url: "/sv.videoTask/delete", params });
}

// 数字人生成视频修改
export function getDigitalHumanVideoEdit(params: any) {
	return $request.post({ url: "/sv.videoTask/update", params });
}

// 文案库列表
export function getCopywritingLibraryList(params: any) {
	return $request.get({ url: "/sv.copywritingLibrary/lists", params });
}

// 文案库新增
export function addCopywritingLibrary(params: any) {
	return $request.post({ url: "/sv.copywritingLibrary/add", params });
}

// 文案库更新
export function updateCopywritingLibrary(params: any) {
	return $request.post({ url: "/sv.copywritingLibrary/update", params });
}

// 文案库删除
export function deleteCopywritingLibrary(params: any) {
	return $request.post({ url: "/sv.copywritingLibrary/del", params });
}

// 文案库详情
export function getCopywritingLibraryDetail(params: any) {
	return $request.get({ url: "/sv.copywritingLibrary/detail", params });
}

// 文案库内容AI生成
export function getCopywritingLibraryContentAi(params: any) {
	return $request.post({ url: "/sv.copywritingLibrary/addAi", params });
}
