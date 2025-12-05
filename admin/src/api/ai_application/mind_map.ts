import request from "@/utils/request";

export function getMindMapConfig() {
	return request.get({ url: "/setting.mindmap/getConfig" });
}

export function setMindMapConfig(params: any) {
	return request.post({ url: "/setting.mindmap/setConfig", params });
}

export function getMindMapExample() {
	return request.get({ url: "/setting.mindmap/getExampleConfig" });
}

export function setMindMapExample(params: any) {
	return request.post({ url: "/setting.mindmap/setExampleConfig", params });
}

// 思维导图记录
export function getMindMapRecordLists(params: any) {
	return request.get({ url: "/mindMap.mindMap/lists", params });
}

// 思维导图记录删除
export function deleteMindMapRecord(params: any) {
	return request.post({ url: "/mindMap.mindMap/delete", params });
}
