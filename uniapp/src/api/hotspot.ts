import request from '@/utils/request'

// 热点追踪 · 平台列表
export const getHotspotPlatforms = () => {
    return request.get({ url: '/hotspot.hotspot/platforms' })
}

// 热点追踪 · 热榜（period: day/week/rise；day 传历史日期 YYYY-MM-DD）
export const getHotspotHot = (data: {
    platform: string
    period?: string
    day?: string
    limit?: number
}) => {
    return request.get({ url: '/hotspot.hotspot/hot', data })
}

// 热点追踪 · 历史快照日期
export const getHotspotHistoryDates = (data: { platform: string }) => {
    return request.get({ url: '/hotspot.hotspot/historyDates', data })
}

// 热点追踪 · 平台热度洞察（仅抖音话题）
export const getHotspotInsight = (data: { topic: string }) => {
    return request.get({ url: '/hotspot.hotspot/insight', data })
}

// 热点追踪 · 高级设置选项与算力
export const getHotspotOptions = () => {
    return request.get({ url: '/hotspot.hotspot/options' })
}

// 热点追踪 · 可用人设列表
export const getHotspotPersonas = () => {
    return request.get({ url: '/hotspot.hotspot/personas' })
}

// 热点追踪 · 人设数字人形象列表
export const getHotspotAvatars = (data: { persona_id: number | string }) => {
    return request.get({ url: '/hotspot.hotspot/avatars', data })
}

// 热点追踪 · 人设混剪素材列表
export const getHotspotClipMaterials = (data: {
    persona_id: number | string
    page_no?: number
    page_size?: number
}) => {
    return request.get({ url: '/hotspot.hotspot/clipMaterials', data })
}

// 热点追踪 · AI 联网搜索热点
export const hotspotResearch = (data: { topic: string; platform: string; category?: string }) => {
    return request.post({ url: '/hotspot.hotspot/research', data })
}

// 热点追踪 · 热点×人设结合分析
export const hotspotAnalyze = (data: Record<string, any>) => {
    return request.post({ url: '/hotspot.hotspot/analyze', data })
}

// 热点追踪 · 生成口播文案
export const hotspotScript = (data: Record<string, any>) => {
    return request.post({ url: '/hotspot.hotspot/script', data })
}

// 热点追踪 · 按话题取最近一次任务的完整现场（已分析热点还原用，避免重复扣费）
export const getHotspotLastFlow = (data: { topic: string; platform: string }) => {
    return request.get({ url: '/hotspot.hotspot/lastFlow', data })
}

// 热点追踪 · 创作队列
export const getHotspotTasks = (data: { page_no?: number; page_size?: number; status?: string }) => {
    return request.get({ url: '/hotspot.hotspot/tasks', data })
}

// 热点追踪 · 创建视频合成任务
export const addHotspotTask = (data: Record<string, any>) => {
    return request.post({ url: '/hotspot.hotspot/add', data })
}

// 热点追踪 · 任务详情
export const getHotspotTaskDetail = (data: { id: string }) => {
    return request.get({ url: '/hotspot.hotspot/detail', data })
}

// 热点追踪 · 删除任务
export const deleteHotspotTask = (data: { id: string }) => {
    return request.post({ url: '/hotspot.hotspot/delete', data })
}

// 热点追踪 · 失败任务重试
export const retryHotspotTask = (data: { id: string }) => {
    return request.post({ url: '/hotspot.hotspot/retry', data })
}
