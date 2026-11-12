import request from '@/utils/request'

// 作品数据 · 人设列表（含每个平台已绑定的发布账号）
export const getWorkDataPersonas = () => {
    return request.get({ url: '/workData.WorkData/personas' })
}

export const setWorkDataSyncSettings = (data: { persona_id: number; auto_sync_enabled: 0 | 1 }) => {
    return request.post({ url: '/workData.WorkData/syncSettings', data })
}

// 作品数据 · 平台账号与作品数据（refresh=1 跳过缓存重新取数，会计费）
export const getWorkDataAnalytics = (data: {
    persona_id: number | string
    platform: string
    account?: string
    refresh?: 0 | 1
}) => {
    return request.get({ url: '/workData.WorkData/analytics', data })
}

// 作品数据 · 历史回填（自动翻页拉更多作品并合并入库，整段只计 1 次费用）
// max_pages 后端会夹到 1~5，传 5 表示最多拉 5 页（约 100 条）
export const getWorkDataBackfill = (data: {
    persona_id: number | string
    platform: string
    account?: string
    max_pages?: number
}) => {
    return request.post({ url: '/workData.WorkData/backfill', data })
}
