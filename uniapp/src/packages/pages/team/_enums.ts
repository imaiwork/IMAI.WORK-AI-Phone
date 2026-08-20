/** 团队角色 */
export const TeamRole = {
    Member: 1,
    Owner: 2,
    Admin: 3
} as const

export type TeamRoleValue = (typeof TeamRole)[keyof typeof TeamRole]

export const ROLE_META: Record<
    number,
    { label: string; color: string; bg: string; filterKey: string }
> = {
    [TeamRole.Owner]: { label: '创始人', color: '#1D4ED8', bg: '#DBEAFE', filterKey: 'owner' },
    [TeamRole.Admin]: { label: '管理员', color: '#B45309', bg: '#FEF3C7', filterKey: 'admin' },
    [TeamRole.Member]: { label: '成员', color: '#64748B', bg: '#F1F5F9', filterKey: 'member' }
}

export const AVATAR_COLORS = [
    '#2563EB',
    '#7C3AED',
    '#DB2777',
    '#EA580C',
    '#0D9488',
    '#4F46E5',
    '#CA8A04',
    '#DC2626'
]

export const ROLE_FILTERS = [
    { key: 'all', label: '全部', role: 0 },
    { key: 'owner', label: '创始人', role: TeamRole.Owner },
    { key: 'admin', label: '管理员', role: TeamRole.Admin },
    { key: 'member', label: '成员', role: TeamRole.Member }
] as const

/** 消耗明细快捷时间筛选(与 PC 一致:含「全部」,range 为空=不限时间) */
export const CONSUME_RANGE_FILTERS = [
    { key: 'all', label: '全部', range: '', sumLabel: '全部团队消耗（算力）' },
    { key: 'today', label: '今日', range: 'today', sumLabel: '今日团队消耗（算力）' },
    { key: '7d', label: '近7天', range: '7d', sumLabel: '近7天团队消耗（算力）' },
    { key: '30d', label: '近30天', range: '30d', sumLabel: '近30天团队消耗（算力）' },
    { key: 'month', label: '本月', range: 'month', sumLabel: '本月团队消耗（算力）' }
] as const

export type ConsumeRangeKey = (typeof CONSUME_RANGE_FILTERS)[number]['key']

/** 业务类型标签色 */
export const BIZ_TAG_META: Record<string, { color: string; bg: string }> = {
    chat: { color: '#0D9488', bg: '#ECFDF8' },
    kb_chat: { color: '#EA580C', bg: '#FFF7ED' },
    kb_retrieve: { color: '#EA580C', bg: '#FFF7ED' },
    kb_create: { color: '#EA580C', bg: '#FFF7ED' },
    ai_image: { color: '#2563EB', bg: '#EFF6FF' },
    ai_draw: { color: '#2563EB', bg: '#EFF6FF' },
    draw: { color: '#2563EB', bg: '#EFF6FF' },
    ai_video: { color: '#7C3AED', bg: '#F5F0FF' },
    sora: { color: '#7C3AED', bg: '#F5F0FF' },
    video: { color: '#7C3AED', bg: '#F5F0FF' },
    storyboard: { color: '#7C3AED', bg: '#F5F0FF' },
    human: { color: '#DB2777', bg: '#FDF2F8' },
    voice: { color: '#DB2777', bg: '#FDF2F8' },
    map_lead: { color: '#2563EB', bg: '#EFF6FF' },
    meeting: { color: '#0D9488', bg: '#ECFDF8' },
    mind: { color: '#0D9488', bg: '#ECFDF8' },
    team_allocate: { color: '#64748B', bg: '#F1F5F9' },
    team_allocate_refund: { color: '#16A34A', bg: '#F0FDF4' },
    other: { color: '#64748B', bg: '#F1F5F9' }
}
