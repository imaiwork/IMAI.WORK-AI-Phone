import type { RequestEventStreamOptions } from "ofetch";

// 机器人分类
export function robotCategory(params: any) {
	return $request.get({ url: "/chat/sceneLists", params });
}

// 机器人列表
export function robotLists(params?: any) {
	return $request.get({ url: "/chat/sceneAassistantLists", params });
}

// 机器人详情
export function robotDetail(params: any) {
	return $request.get({ url: "/chat/sceneChatInfo", params });
}

// 机器人新增
export function robotAdd(params: any) {
	return $request.post({ url: "/assistants/add", params });
}

// 机器人删除
export function robotDelete(params: any) {
	return $request.get({ url: "/assistants/delete", params });
}

// 机器人编辑
export function robotEdit(params: any) {
	return $request.post({ url: "/assistants/edit", params });
}
