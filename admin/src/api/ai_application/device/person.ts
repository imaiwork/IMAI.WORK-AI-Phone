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

// 更新智能客服配置（AI 员工 - 智能客服）
export function updateCustomerServiceConfig(data: any) {
    return request.post({ url: "/aiPersona.agentConfig/updateCustomerService", data });
}

// 更新人设员工开关（global_option 顶层 + 子项开关统一刷新）
export function updatePersonOption(data: { id: string | number; global_option: Record<string, any> }) {
    return request.post({ url: "/aiPersona.aiPersona/updateOption", data });
}

// 获取人设爆款追踪词
export function getPersonTrackingWords(params: any) {
    return request.get({ url: "/aiPersona.aiPersona/hotWords", params });
}

// 编辑人设爆款追踪词
export function updatePersonTrackingWords(data: any) {
    return request.post({ url: "/aiPersona.aiPersona/updateHotWords", data });
}

// 内容发布配置详情
export function getPublishConfigDetail(params: { id: string | number }) {
    return request.get({ url: "/aiPersona.aiPersona/publishConfigDetail", params });
}

// 更新内容发布配置
export function updatePublishConfig(data: any) {
    return request.post({ url: "/aiPersona.aiPersona/publishConfigUpdate", data });
}

// AI 合成规则（视频剪辑）详情
export function getAiSynthesisRuleDetail(params: any) {
    return request.get({ url: "/aiPersona.SynthesisConfig/getByPersonaId", params });
}

// AI 合成规则新增
export function addAiSynthesisRule(data: any) {
    return request.post({ url: "/aiPersona.SynthesisConfig/add", data });
}

// AI 合成规则修改
export function updateAiSynthesisRule(data: any) {
    return request.post({ url: "/aiPersona.SynthesisConfig/update", data });
}

// 闪剪视频模板列表（视频剪辑 · 模板选择）
export function getShanjianClipTemplateList(params: {
    scene?: string;
    auto_type?: number;
    page_no?: number;
    page_size?: number;
    name?: string;
}) {
    return request.get({ url: "/shanjian.shanjianClipTemplate/lists", params });
}

// 数字人形象列表（视频剪辑保存前的数字人检测）
export function getAvatarList(params: any) {
    return request.get({ url: "/aiPersona.digitalAvatar/lists", params });
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

// 触发生成人设线索话术（保存人设后随报告一并刷新，对齐 uniapp getPersonClueWords）
export function getPersonClueWords(params: any) {
    return request.get({ url: "/aiPersona.aiPersona/clue", params });
}

// 触发生成人设私信互动话术（保存人设后随报告一并刷新，对齐 uniapp getPersonInteractionWords）
export function getPersonInteractionWords(params: any) {
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

// 私域与运营更新
export function updateInteractionConfig(data: any) {
    return request.post({ url: "/aiPersona.interactive/update", data });
}

// 私域与运营详情
export function getInteractionConfig(params: any) {
    return request.get({ url: "/aiPersona.interactive/detail", params });
}
