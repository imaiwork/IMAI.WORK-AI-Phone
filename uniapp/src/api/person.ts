import request from "@/utils/request";

// 获取人设列表
export const getPersonList = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.aiPersona/lists", data });
};

// 新增人设
export const createPerson = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.aiPersona/add", data });
};

// 编辑人设
export const editPerson = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.aiPersona/edit", data });
};

// 更新人设
export const updatePerson = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.aiPersona/update", data });
};

// 删除人设
export const deletePerson = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.aiPersona/delete", data });
};

// 人设详情
export const getPersonDetail = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.aiPersona/detail", data });
};

// 人设关联的设备
export const getPersonDeviceList = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.aiPersona/getDevices", data });
};

// 创建人设分析
export const createPersonAnalysis = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.aiPersona/analysis", data });
};

// 生成人设分析报告
export const generatePersonAnalysisReport = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.aiPersona/report", data });
};

// 获取人设配置状态
export const getPersonConfigStatus = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.aiPersona/configStatus", data });
};

// 获客/截流详情
export const getTrafficConfig = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.clueTouch/detail", data });
};

// 获客/截流更新
export const updateTrafficConfig = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.clueTouch/update", data });
};

// 私域互动管家详情
export const getInteractionConfig = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.interactive/detail", data });
};

// 私域互动管家更新
export const updateInteractionConfig = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.interactive/update", data });
};

// 智能体详情
export const getAgentDetail = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.agentConfig/detail", data });
};

// 智能体更新
export const updateAgent = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.agentConfig/update", data });
};

// 素材库列表
export const getMaterialLibraryList = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.material/lists", data });
};

// 素材库添加
export const addMaterial = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.material/add", data });
};

// 素材库批量添加
export const batchAddMaterial = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.material/addBatch", data });
};

// 素材库删除
export const deleteMaterial = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.material/delete", data });
};

// 素材库详情
export const getMaterialDetail = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.material/detail", data });
};

// 素材库修改
export const updateMaterial = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.material/update", data });
};

// 素材库状态修改
export const updateMaterialStatus = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.material/updateStatus", data });
};

// 获取素材使用记录
export const getMaterialUsageRecord = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.materialUseLog/lists", data });
};

// 知识库配置更新
export const updateKnowledgeConfig = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.aiPersona/knowledgeUpdate", data });
};

// 形象列表
export const getAvatarList = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.digitalAvatar/lists", data });
};

// 形象添加
export const addAvatar = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.digitalAvatar/add", data });
};

// 形象删除
export const deleteAvatar = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.digitalAvatar/delete", data });
};

// 形象音色绑定
export const bindAvatarVoice = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.digitalAvatar/bindPersonaVoice", data });
};

// 音色列表
export const getVoiceList = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.digitalVoice/lists", data });
};

// 音色添加
export const addVoice = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.digitalVoice/add", data });
};

// 音色删除
export const deleteVoice = (data: Record<string, any>) => {
    return request.post({ url: "/aiPersona.digitalVoice/delete", data });
};

// 获取人设线索词
export const getPersonClueWords = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.aiPersona/clue", data });
};

// 获取人设私域互动话术
export const getPersonInteractionWords = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.aiPersona/wechat", data });
};

// 获取生成记录
export const getGenerateRecordList = (data: Record<string, any>) => {
    return request.get({ url: "/aiPersona.videoRecord/lists", data });
};
