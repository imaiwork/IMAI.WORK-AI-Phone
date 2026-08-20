// 客资线索页：跨文件共享的类型集中地（主文件 + 弹窗组件共用）

export type Domain = 'public' | 'private'
export type Platform = 'all' | 'douyin' | 'xhs' | 'kuaishou' | 'shipinhao'
export type CustomerPlatform = Exclude<Platform, 'all'>
export type ActivityType = 'chat' | 'moments'

export interface SourceStat {
    key: string
    label: string
    value: number
    unit: string
}

export interface StatisticsData {
    summaryText: string
    totalTouchCustomerCount: number
    clueCount: number
    addFriendCount: number
    createGroupCount: number
    sourceStats: SourceStat[]
}

export interface DetailRow {
    icon: string
    label: string
    value: string
}

export interface ChatMessage {
    from: 'customer' | 'agent'
    text: string
    time: string
}

export interface MomentLog {
    time: string
    content: string
    liked: boolean
    comment?: string
    image?: string
}

export interface TrackItem {
    stage: string
    time?: string
    desc: string
    done: boolean
    active?: boolean
    chat?: string[]
}

export interface Customer {
    id: string
    name: string
    accountName: string
    account: string
    avatar: string
    wechat: string
    wxStatus?: 'added' | 'pending'
    wxStatusText?: string
    domain: Domain
    platform: CustomerPlatform
    platformValue: string | number
    platformDesc: string
    source: string
    sourceName: string
    sourceKey: string
    avatarClass: string
    privateMessages: number
    momentActions: number
    publicReplies: number
    platformMessages: number
    firstChatTime?: string
    groupName?: string
    latestMessage: string
    momentSummary: string
    rows: DetailRow[]
    chatLog: ChatMessage[]
    chatScreenshots: string[]
    momentsLog: MomentLog[]
    track: TrackItem[]
    chatLoaded: boolean
    momentsLoaded: boolean
    trackLoaded: boolean
}
