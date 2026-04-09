import request from "@/utils/request";

// 获取人设列表
export function getPersonList(params: any) {
    return request.get({ url: "/aiPersona.aiPersona/lists", params });
}

// 删除人设
export function deletePerson(params: any) {
    return request.post({ url: "/aiPersona.aiPersona/delete", data: params });
}

// 更新人设
export function updatePersona(data: any) {
    return request.post({ url: "/aiPersona.aiPersona/update", data });
}

// 编辑人设
export function editPersona(data: any) {
    return request.post({ url: "/aiPersona.aiPersona/edit", data });
}

// 获取人设详情
export function getPersonDetail(params: any) {
    return request.get({ url: "/aiPersona.aiPersona/detail", params });
}

// 获取获客与截流设置
export function getTrafficConfig(params: any) {
    return request.get({ url: "/aiPersona.clueTouch/detail", params });
}

// 更新获客与截流设置
export function updateTrafficConfig(data: any) {
    return request.post({ url: "/aiPersona.clueTouch/update", data });
}

// 获取知识库配置
export function getKnowledgeConfig(params: any) {
    return request.get({ url: "/aiPersona.knowledge/detail", params });
}

// 更新知识库配置
export function updateKnowledgeConfig(data: any) {
    return request.post({ url: "/aiPersona.aiPersona/knowledgeUpdate", data });
}

// 获取人设智能体
export function getPersonAgent(params: any) {
    return request.get({ url: "/aiPersona.agentConfig/detail", params });
}

// 更新人设智能体
export function updatePersonAgent(data: any) {
    return request.post({ url: "/aiPersona.agentConfig/update", data });
}

// 素材列表
export function getMaterialList(params: any) {
    return request.get({ url: "/aiPersona.material/lists", params });
}

// 素材删除
export function deleteMaterial(params: any) {
    return request.post({ url: "/aiPersona.material/delete", data: params });
}

// 素材更新
export function updateMaterial(data: any) {
    return request.post({ url: "/aiPersona.material/update", data });
}

// 请求获客截流设置
export function getClueTouch(params: any) {
    return request.get({ url: "/aiPersona.aiPersona/clue", params });
}

// 请求微信话术配置
export function getWechatConfig(params: any) {
    return request.get({ url: "/aiPersona.aiPersona/wechat", params });
}

// 获取人设分析报告
export function getPersonAnalysisReport(params: any) {
    return request.get({ url: "/aiPersona.aiPersona/report", params });
}

// 生成人设分析报告
export function generatePersonAnalysisReport(data: any) {
    return request.post({ url: "/aiPersona.aiPersona/report", data });
}
