import DouyinIcon from '@/packages/static/icons/effect-statistics/douyin.svg'
import XhsIcon from '@/packages/static/icons/effect-statistics/xhs.svg'

export enum PlatformEnum {
    DOUYIN = 'douyin',
    XHS = 'xhs'
}

export enum PersonaStatusEnum {
    LINKED = 'linked',
    PENDING = 'pending',
    INACTIVE = 'inactive'
}

export enum PersonaTagEnum {
    PERSONAL = 'personal',
    CORP = 'corp',
    LOCAL = 'local',
    ECOM = 'ecom'
}

export interface PlatformConfig {
    key: PlatformEnum
    name: string
    short: string
    acctLabel: string
    workLabel: string
    viewLabel: string
    icon: string
}

export const PLATFORMS: PlatformConfig[] = [
    {
        key: PlatformEnum.DOUYIN,
        name: '抖音平台',
        short: '抖音',
        acctLabel: '抖音号',
        workLabel: '作品',
        viewLabel: '播放',
        icon: DouyinIcon
    },
    {
        key: PlatformEnum.XHS,
        name: '小红书平台',
        short: '小红书',
        acctLabel: '小红书号',
        workLabel: '笔记',
        viewLabel: '浏览',
        icon: XhsIcon
    }
]

export const PERSONA_STATUS: Record<string, { color: string; text: string }> = {
    [PersonaStatusEnum.LINKED]: { color: '#22C55E', text: '运行中' },
    [PersonaStatusEnum.PENDING]: { color: '#F59E0B', text: '未完善' },
    [PersonaStatusEnum.INACTIVE]: { color: '#9CA3AF', text: '已暂停' }
}

// 人设分类是一组并列的类别色，不跟随主题主色变化
export const PERSONA_TAG_STYLE: Record<string, string> = {
    [PersonaTagEnum.PERSONAL]: 'bg-[#EBF2FF] text-[#2680F7]',
    [PersonaTagEnum.CORP]: 'bg-[#F3EEFF] text-[#7B61FF]',
    [PersonaTagEnum.LOCAL]: 'bg-[#FFF3E0] text-[#F59E0B]',
    [PersonaTagEnum.ECOM]: 'bg-[#E8F8EF] text-[#16A34A]'
}

export const WINDOW_OPTIONS = [7, 30, 90]

// 播放/浏览是平台接口不给，触达客户是线索体系还没打通，两者都不是「没有数据」
export const NA_PLAY_TEXT = '暂不可用'
export const NA_REACH_TEXT = '未接入'
export const REACH_LABEL = '触达客户'

// 与后端 work_data.refresh_min_interval 对齐（默认 60s）：
// 在这个窗口内强制刷新，后端只会回缓存且不扣费，前端提前挡掉，免得白弹一次扣费确认
export const REFRESH_INTERVAL = 60000

export const PERSONA_CREATE_URL = '/ai_modules/person/pages/create/create?mode=add'

// 取数单价，后台「作品数据报表」场景（work_data_fetch）可调，停用时 score 为 0
export interface CostInfo {
    scene: string
    name: string
    score: number
    unit: string
}

export interface PersonaAccount {
    id: number
    account: string
    nickname: string
    avatar: string
    device_code: string
}

export interface Persona {
    id: number
    name: string
    tag: string
    tagClass: string
    status: string
    avatar: string
    // 后端返回的是「N 台手机」文案，未关联时为空串
    devices: string
    accounts: Record<string, PersonaAccount[]>
    auto_sync_enabled: boolean
}

export interface SeriesPoint {
    date: string
    value: number
}

export interface WindowStat {
    published: number
    play: number | null
    interaction: number
    series: SeriesPoint[]
    playSeries?: SeriesPoint[]
}

export interface AnalyticsAccount {
    avatar: string
    nickname: string
    uniqueId: string
    follower: number | null
    totalWorks: number | null
    totalLiked: number | null
}

export interface WorkItem {
    title: string
    cover: string
    publishedAt: string | number
    durationSec: number
    noteType: string
    play: number | null
    digg: number | null
    comment: number | null
    share: number | null
    collect: number | null
}

export interface AnalyticsData {
    platform: string
    account: AnalyticsAccount
    items: WorkItem[]
    windows: Record<string, WindowStat>
    truncated: boolean
    pageSize: number
    playCountAvailable: boolean
    playCountNote: string
    fetchedAt: string
    cached: boolean
    // 未绑定发布账号时后端只返回 bound=false
    bound?: boolean
    hasData?: boolean
}
