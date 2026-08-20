// $request 为全局自动导入的请求实例，无需 import
const B = '/geo.geo'

// 生成侧可选模型（读系统 la_models）
export const geoModels = () => $request.get({ url: `${B}/models` })

// AI 匹配品牌信息（向导第一步：品牌名→行业/别名/简介；model 传 la_models 别名可切换，默认 GPT 系）
export const geoAiMatchBrand = (name: string, model = '') => $request.post({ url: `${B}/aiMatchBrand`, params: { name, model } })
// 「AI匹配品牌信息」可切换的模型列表（{list:[{value,label,channel}], default}）
export const geoMatchModels = () => $request.get({ url: `${B}/matchModels` })

// 算力计费单价（[{scene,name,unit,score}]，演示模式 score 全为 0）
export const geoChargeConfig = () => $request.get({ url: `${B}/chargeConfig` })
// 知识库文档/网址导入(二期)
export const geoKnowledgeImportFile = (params: any) => $request.post({ url: `${B}/knowledgeImportFile`, params })
export const geoKnowledgeImportUrl = (params: any) => $request.post({ url: `${B}/knowledgeImportUrl`, params })

// 项目
export const geoProjects = () => $request.get({ url: `${B}/projects` })
export const geoProjectDetail = (id: number) => $request.get({ url: `${B}/projectDetail`, params: { id } }, { ignoreCancel: true })
export const geoProjectCreate = (params: any) => $request.post({ url: `${B}/projectCreate`, params })
export const geoProjectUpdate = (params: any) => $request.post({ url: `${B}/projectUpdate`, params })
export const geoProjectDelete = (id: number) => $request.post({ url: `${B}/projectDelete`, params: { id } })

// 知识
export const geoKnowledge = (project_id: number) => $request.get({ url: `${B}/knowledge`, params: { project_id } })
export const geoKnowledgeImport = (params: any) => $request.post({ url: `${B}/knowledgeImport`, params })
export const geoKnowledgeSave = (params: any) => $request.post({ url: `${B}/knowledgeSave`, params })
export const geoKnowledgeDelete = (id: number) => $request.post({ url: `${B}/knowledgeDelete`, params: { id } })

// 关键词
export const geoAnalyze = (project_id: number) => $request.post({ url: `${B}/analyze`, params: { project_id } })
export const geoKeywords = (project_id: number) => $request.get({ url: `${B}/keywords`, params: { project_id } })

// 内容
export const geoGenContent = (project_id: number, content_types: string[]) => $request.post({ url: `${B}/genContent`, params: { project_id, content_types } })
export const geoContents = (params: any) => $request.get({ url: `${B}/contents`, params })
export const geoContentUpdate = (params: any) => $request.post({ url: `${B}/contentUpdate`, params })
export const geoContentRegenerate = (id: number) => $request.post({ url: `${B}/contentRegenerate`, params: { id } })
export const geoContentDelete = (id: number) => $request.post({ url: `${B}/contentDelete`, params: { id } })
export const geoContentExport = (ids: number[], format: string) => $request.post({ url: `${B}/contentExport`, params: { ids, format } })

// 监测
export const geoMonitorEngines = () => $request.get({ url: `${B}/monitorEngines` })
export const geoMonitor = (project_id: number, query: string, engines: string[] = [], keyword_id = 0, topic_id = 0) => $request.post({ url: `${B}/monitor`, params: { project_id, query, engines, keyword_id, topic_id } })
export const geoMonitorList = (project_id: number) => $request.get({ url: `${B}/monitorList`, params: { project_id } })

// 建议
export const geoSuggestions = (project_id: number) => $request.post({ url: `${B}/suggestions`, params: { project_id } })

// 发布/分发
export const geoMedia = (params: any = {}) => $request.get({ url: `${B}/media`, params })
export const geoMediaFilters = () => $request.get({ url: `${B}/mediaFilters` })
export const geoPublishCreate = (project_id: number, content_id: number, media_ids: number[], media_type: string = 'article', video_id: number = 0) => $request.post({ url: `${B}/publishCreate`, params: { project_id, content_id, media_ids, media_type, video_id } })
export const geoPublishList = (project_id: number) => $request.get({ url: `${B}/publishList`, params: { project_id } })
export const geoPublishConfirm = (id: number, url: string) => $request.post({ url: `${B}/publishConfirm`, params: { id, url } })
export const geoPublishDelete = (id: number) => $request.post({ url: `${B}/publishDelete`, params: { id } })

// 任务
export const geoTask = (task_id: number) => $request.get({ url: `${B}/task`, params: { task_id } })
export const geoTasks = (project_id: number) => $request.get({ url: `${B}/tasks`, params: { project_id } })

// ========== 微盟星启功能移植 ==========

// 一键诊断：批量入队 + 轮询进度（替代逐 cell 串行调 geoMonitor）
export const geoMonitorBatch = (params: any) => $request.post({ url: `${B}/monitorBatch`, params })
export const geoMonitorProgress = (project_id: number, since: number, batch_task_id = 0) => $request.get({ url: `${B}/monitorProgress`, params: { project_id, since, batch_task_id } })

// 文章封面图(生成侧异步出图，前端轮询取回)
export const geoContentCover = (content_id: number) => $request.get({ url: `${B}/contentCover`, params: { content_id } })
export const geoContentCoverRetry = (content_id: number) => $request.post({ url: `${B}/contentCoverRetry`, params: { content_id } })

// AI手机媒体可用发布账号
export const geoPhoneAccounts = (project_id: number, media_id: number) => $request.get({ url: `${B}/phoneAccounts`, params: { project_id, media_id } })

// 初始化引导
export const geoInitState = (project_id: number) => $request.get({ url: `${B}/initState`, params: { project_id } }, { ignoreCancel: true })

// 话题
export const geoTopics = (project_id: number) => $request.get({ url: `${B}/topics`, params: { project_id } })
export const geoTopicSave = (params: any) => $request.post({ url: `${B}/topicSave`, params })
export const geoTopicToggle = (project_id: number, id: number) => $request.post({ url: `${B}/topicToggle`, params: { project_id, id } })
export const geoTopicDelete = (project_id: number, id: number) => $request.post({ url: `${B}/topicDelete`, params: { project_id, id } })

// 场景问题
export const geoQuestions = (params: any) => $request.get({ url: `${B}/questions`, params })
export const geoQuestionSave = (params: any) => $request.post({ url: `${B}/questionSave`, params })
export const geoQuestionBatch = (project_id: number, ids: number[], action: string) => $request.post({ url: `${B}/questionBatch`, params: { project_id, ids, action } })

// AI 推荐
export const geoAiTopics = (project_id: number, count = 3) => $request.post({ url: `${B}/aiTopics`, params: { project_id, count } })
export const geoAiQuestions = (project_id: number, topic_id: number, count = 10, extra_info = '') => $request.post({ url: `${B}/aiQuestions`, params: { project_id, topic_id, count, extra_info } })

// 监测洞察
export const geoInsightOverview = (params: any) => $request.get({ url: `${B}/insightOverview`, params })
export const geoInsightScene = (params: any) => $request.get({ url: `${B}/insightScene`, params })
export const geoInsightSnapshots = (params: any) => $request.get({ url: `${B}/insightSnapshots`, params })
export const geoInsightTrend = (params: any) => $request.get({ url: `${B}/insightTrend`, params })
export const geoInsightVisibilityTrend = (params: any) => $request.get({ url: `${B}/insightVisibilityTrend`, params })
export const geoInsightQuotes = (params: any) => $request.get({ url: `${B}/insightQuotes`, params })
export const geoInsightSentiment = (params: any) => $request.get({ url: `${B}/insightSentiment`, params })
export const geoInsightReport = (project_id: number) => $request.get({ url: `${B}/insightReport`, params: { project_id } })

// 聊天式内容生成
export const geoContentTemplates = () => $request.get({ url: `${B}/contentTemplates` })
export const geoChatGenerate = (params: any) => $request.post({ url: `${B}/chatGenerate`, params })

// 「文章转短视频」已下线(对齐 product):GEO 只产口播稿,由数字人模块出片。
// geoVideoList 暂留给 content-manage 的 AI 手机投稿素材列表兜底(后端接口已移除,
// 调用会失败并被调用方静默吞掉,待该处对齐 product 后一并删除)
export const geoVideoList = (project_id: number) => $request.get({ url: `${B}/videoList`, params: { project_id } })

// 自有发布登记
export const geoPublishRegister = (params: any) => $request.post({ url: `${B}/publishRegister`, params })

// AI手机投稿登记(设备发布任务下发成功后,把这次投稿挂进媒体库投稿台账)
export const geoPublishPhoneRegister = (params: any) => $request.post({ url: `${B}/publishPhoneRegister`, params })

// GEO 报告(落库:查看免费,生成/重新生成计费)
export const geoReportLatest = (project_id: number) => $request.get({ url: `${B}/reportLatest`, params: { project_id } })
export const geoReportGenerate = (project_id: number) => $request.post({ url: `${B}/reportGenerate`, params: { project_id } })

// 设置:授权账号(Web API 发布通道)
export const geoAuthPlatforms = () => $request.get({ url: `${B}/authPlatforms` })
export const geoAuthAccountSave = (params: any) => $request.post({ url: `${B}/authAccountSave`, params })
export const geoAuthAccountToggle = (id: number) => $request.post({ url: `${B}/authAccountToggle`, params: { id } })
export const geoAuthAccountDelete = (id: number) => $request.post({ url: `${B}/authAccountDelete`, params: { id } })
export const geoAuthAccountCheck = (id: number) => $request.post({ url: `${B}/authAccountCheck`, params: { id } })
