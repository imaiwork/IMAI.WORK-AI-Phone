import request from '@/utils/request'
import { PublishSourceEnum } from '@/enums/publishEnums'

// 获取人设列表
export const getPersonList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.aiPersona/lists', data })
}

// 新增人设
export const createPerson = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/add', data })
}

// 编辑人设
export const editPerson = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/edit', data })
}

// 更新人设
export const updatePerson = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/update', data })
}

// 删除人设
export const deletePerson = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/delete', data })
}

// 人设详情
export const getPersonDetail = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.aiPersona/detail', data })
}

// 人设关联的设备
export const getPersonDeviceList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.aiPersona/getDevices', data })
}

// 创建人设分析
export const createPersonAnalysis = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/analysis', data })
}

// 生成人设分析报告
export const generatePersonAnalysisReport = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/report', data })
}

// 获取人设配置状态
export const getPersonConfigStatus = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.aiPersona/configStatus', data })
}

// 爆款复刻：校验人设是否具备形象/音色资产
export const checkViralAssets = (data: { id: string | number }) => {
    return request.get<{ has_avatar: number; has_voice: number }>({
        url: '/aiPersona.aiPersona/checkViralAssets',
        data
    })
}

// 更新人设员工开关（全局 + 子项开关）
export const updatePersonOption = (data: {
    id: string | number
    global_option: Record<string, any>
}) => {
    return request.post({ url: '/aiPersona.aiPersona/updateOption', data })
}

// 获客/截流详情
export const getTrafficConfig = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.clueTouch/detail', data })
}

// 获客/截流更新
export const updateTrafficConfig = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.clueTouch/update', data })
}

// 私域互动管家详情
export const getInteractionConfig = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.interactive/detail', data })
}

// 私域互动管家更新
export const updateInteractionConfig = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.interactive/update', data })
}

// 智能体详情
export const getAgentDetail = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.agentConfig/detail', data })
}

// 智能体更新
export const updateAgent = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.agentConfig/update', data })
}

// 智能客服配置更新（AI 员工 - 智能客服）
export const updateCustomerServiceConfig = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.agentConfig/updateCustomerService', data })
}

// 素材库列表
export const getMaterialLibraryList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.material/lists', data })
}

// 素材库添加
export const addMaterial = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.material/add', data })
}

// 素材库批量添加
export const batchAddMaterial = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.material/addBatch', data })
}

// 素材库删除
export const deleteMaterial = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.material/delete', data })
}

// 素材库批量删除
export const batchDeleteMaterial = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.material/batchDelete', data })
}

// 一键删除切割失败的视频素材
export const deleteFailedSlices = (data: { persona_id: number | string }) => {
    return request.post({ url: '/aiPersona.material/deleteFailedSlices', data })
}

// 素材库详情
export const getMaterialDetail = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.material/detail', data })
}

// 素材库修改
export const updateMaterial = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.material/update', data })
}

// 素材库状态修改
export const updateMaterialStatus = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.material/updateStatus', data })
}

// 获取素材使用记录
export const getMaterialUsageRecord = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.materialUseLog/lists', data })
}

// 视频切割统计
export const getVideoSliceStatistics = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.videoSlice/statistics', data })
}

// 知识库配置更新
export const updateKnowledgeConfig = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/knowledgeUpdate', data })
}

// 形象列表
export const getAvatarList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.digitalAvatar/lists', data })
}

// 形象添加
export const addAvatar = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.digitalAvatar/add', data })
}

// 形象删除
export const deleteAvatar = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.digitalAvatar/delete', data })
}

// 形象音色绑定
export const bindAvatarVoice = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.digitalAvatar/bindPersonaVoice', data })
}

// 音色列表
export const getVoiceList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.digitalVoice/lists', data })
}

// 音色添加
export const addVoice = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.digitalVoice/add', data })
}

// 音色删除
export const deleteVoice = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.digitalVoice/delete', data })
}

// 获取人设线索词
export const getPersonClueWords = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.aiPersona/clue', data })
}

// 获取人设私域互动话术
export const getPersonInteractionWords = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.aiPersona/wechat', data })
}

// 获取生成记录
export const getGenerateRecordList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.videoRecord/lists', data })
}

// 人设内容记录 · 视频生成失败重试
export const retryGenerateRecord = (data: { id: number | string }) => {
    return request.post({ url: '/aiPersona.videoRecord/retry', data })
}

// 发布失败重发 · 前置校验（返回可重发状态、已生成视频、原文案等）
export const checkPublishResend = (data: { task_id?: number; detail_id?: number }) => {
    return request.post({ url: '/aiPersona.task/checkPublishResend', data })
}

// 发布失败重发 · 确认重新发送（video_source: generated=用已生成视频 upload=换视频上传）
export const publishResend = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.task/publishResend', data })
}

/** 人设内容记录 · 自动生成的图片（图文仿写） */
export const getImageRecordList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.imageRecord/lists', data })
}

/** 人设内容记录 · 删除自动生成的图片 */
export const deleteImageRecord = (data: { ids: Array<number | string> }) => {
    return request.post({ url: '/aiPersona.imageRecord/delete', data })
}

// 朋友圈发布模式更新
export const updateCirclePublishMode = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/updateWechatPublishMode', data })
}

// ai合成规则新增
export const addAiSynthesisRule = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.SynthesisConfig/add', data })
}

// ai合成规则修改
export const updateAiSynthesisRule = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.SynthesisConfig/update', data })
}

// ai合成规则删除
export const deleteAiSynthesisRule = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.SynthesisConfig/delete', data })
}

// ai合成规则详情
export const getAiSynthesisRuleDetail = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.SynthesisConfig/getByPersonaId', data })
}

// 任务工作模版列表
export const getTaskWorkTemplateList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.workflow/lists', data })
}

// 任务工作模版新增
export const addTaskWorkTemplate = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.workflow/add', data })
}

// 任务工作模版修改
export const updateTaskWorkTemplate = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.workflow/update', data })
}

// 任务工作模版删除
export const deleteTaskWorkTemplate = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.workflow/delete', data })
}

// 任务工作模版详情
export const getTaskWorkTemplateDetail = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.workflow/detailTemplate', data })
}

// 人设使用的任务工作模版详情
export const getPersonaUsedTaskWorkTemplateDetail = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.workflow/detail', data })
}

// 任务工作模版节点添加
export const addTaskWorkTemplateNode = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.workflow/addNode', data })
}

// 任务工作模版节点修改
export const updateTaskWorkTemplateNode = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.workflow/updateNode', data })
}

// 任务工作模版节点状态更新
export const updateTaskWorkTemplateNodeStatus = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.workflow/changeStatusNode', data })
}

// 任务工作模版重置
export const resetTaskWorkTemplate = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.workflow/reset', data })
}

// 任务工作模版使用
export const useTaskWorkTemplate = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.workflow/use', data })
}

// 任务模版类目列表
export const getTaskWorkTemplateCategoryList = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.workflow/category', data })
}

// 可添加的任务场景列表（添加任务节点选择器）
export const getWorkflowSceneLists = () => {
    return request.get({ url: '/aiPersona.workflow/sceneLists' })
}

// 获取人设爆款追踪词
export const getPersonTrackingWords = (data: Record<string, any>) => {
    return request.get({ url: '/aiPersona.aiPersona/hotWords', data })
}

// 编辑人设爆款追踪词
export const updatePersonTrackingWords = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/updateHotWords', data })
}

// ===== 爆款库 =====
export const getDeviceViralRecordList = (data: Record<string, any>) => {
    return request.get({ url: '/sv.deviceViralRecord/lists', data })
}

// 爆款库 标记感兴趣 / 不感兴趣（撤回 = is_interested:1，不感兴趣 = 0）
// 手动入库条目需额外传 source（取自列表 source，如 manual）
export const setDeviceViralRecordInterest = (data: {
    ids: Array<string | number>
    is_interested: 0 | 1
    source?: string
}) => {
    return request.post({ url: '/sv.deviceViralRecord/interest', data })
}

// 清空不感兴趣
export const clearDeviceViralRecordUninterested = (data: { day: string }) => {
    return request.post({ url: '/sv.deviceViralRecord/clearUninterested', data })
}

// 保存爆款仿写文案
export const saveDeviceViralRecordCopywriting = (data: {
    id: string | number
    rewritten_text: string
}) => {
    return request.post({ url: '/sv.deviceViralRecord/saveCopywriting', data })
}

// 爆款库手动入库（粘贴分享链接）
export const manualImportDeviceViralRecord = (data: {
    persona_id: string | number
    share_content: string
}) => {
    return request.post({ url: '/sv.deviceViralRecord/manualImport', data })
}

// ===== 内容发布配置 =====
// 获取内容发布配置详情（id = 人设 id）
export const getPublishConfigDetail = (data: { id: string | number }) => {
    return request.get({ url: '/aiPersona.aiPersona/publishConfigDetail', data })
}

// 更新内容发布配置
export const updatePublishConfig = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.aiPersona/publishConfigUpdate', data })
}

// 首页流水线 - 全网自动发布列表
export const getPublishTaskList = (data: {
    persona_id: string | number
    date?: string
    time_config?: string
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/publish', data })
}

// 首页流水线 - 社媒私信/评论任务列表（message_task_type: 1=评论 2=私信）
export const getMessageTaskList = (data: {
    persona_id: string | number
    message_task_type: 1 | 2
    platform_type?: number | string
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/message', data })
}

// 首页流水线 - 社媒私信/评论统计
export const getMessageTaskStatistics = (data: {
    persona_id: string | number
    message_task_type: 1 | 2
}) => {
    return request.get({ url: '/aiPersona.task/messageStatistics', data })
}

// 首页流水线 - 帮我管理微信客户 概览（标题/卡片/tabs）
export const getWechatStatistics = (data: { persona_id: string | number }) => {
    return request.get({ url: '/aiPersona.task/wechatStatistics', data })
}

// 帮我管理微信客户 - 私信回复列表
export const getWechatMessageReply = (data: {
    persona_id: string | number
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/wechatMessageReply', data })
}

// 帮我管理微信客户 - 新好友列表
export const getWechatCustomer = (data: {
    persona_id: string | number
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/wechatCustomer', data })
}

// 帮我管理微信客户 - 拉群记录列表
export const getWechatCreateGroup = (data: {
    persona_id: string | number
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/wechatCreateGroup', data })
}

// 帮我发朋友圈 - 互动列表
export const getWechatCircleInteraction = (data: {
    persona_id: string | number
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/wechatCircleInteraction', data })
}

// 找全网同行的客户 - 列表
export const getLeadScrapingReport = (data: {
    persona_id: string | number
    platform_type?: number | string
    date?: string
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/leadScrapingReport', data })
}

// 找附近的客户 - 列表
export const getSameCityTouch = (data: {
    persona_id: string | number
    platform_type?: number | string
    date?: string
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/sameCityTouch', data })
}

// 去同行门店附近找客户 - 列表
export const getGroupBuyReport = (data: {
    persona_id: string | number
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/groupBuyReport', data })
}

// B端招商获客 - 列表
export const getClueCustomer = (data: {
    persona_id: string | number
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.task/clueCustomer', data })
}

// AI 帮我做内容 - 列表（platform=sv 社媒 / circle 朋友圈）
export const getPublishContentList = (data: {
    persona_id?: string | number
    platform?: PublishSourceEnum | string
    date?: string
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/sv.publish_content/lists', data })
}

// AI 帮我做内容 - 保存编辑（title/content/topic；source=sv|circle）
export const updatePublishContent = (data: {
    id: number | string
    source: PublishSourceEnum
    title?: string
    content?: string
    topic?: string
}) => {
    return request.post({ url: '/sv.publish_content/update', data })
}

// AI 帮我做内容 - 视频重新生成
export const regeneratePublishContentVideo = (data: {
    id: number | string
    shanjian_video_task_id?: number | string
    date?: string
}) => {
    return request.post({ url: '/sv.publish_content/regenerate', data })
}

// ===== 文案库 =====
// 文案库列表（library_type：1 视频驱动文案 2 发布文案；driver_type：0 发布文案 1 新闻体 2 口播文案 3 素材混剪口播）
export const getCopywritingLibraryList = (data: {
    persona_id: string | number
    library_type: number
    driver_type?: number
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/aiPersona.copywritingLibrary/lists', data })
}

// 文案库新增
export const addCopywritingLibrary = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.copywritingLibrary/add', data })
}

// 文案库批量新增（items 内每项含 title/content/topic，可选 sort/status）
export const batchAddCopywritingLibrary = (data: {
    persona_id: string | number
    library_type: number
    driver_type: number
    items: Array<Record<string, any>>
}) => {
    return request.post({ url: '/aiPersona.copywritingLibrary/batchAdd', data })
}

// 文案库更新
export const updateCopywritingLibrary = (data: Record<string, any>) => {
    return request.post({ url: '/aiPersona.copywritingLibrary/update', data })
}

// 文案库删除（支持批量）
export const deleteCopywritingLibrary = (data: { ids: Array<string | number> }) => {
    return request.post({ url: '/aiPersona.copywritingLibrary/delete', data })
}

// 文案库导入（file 为上传接口返回的文件链接）
export const importCopywritingLibrary = (data: {
    file: string
    persona_id: string | number
    library_type: number
    driver_type: number
}) => {
    return request.post(
        { url: '/aiPersona.copywritingLibrary/import', data },
        { ignoreCancel: true }
    )
}
