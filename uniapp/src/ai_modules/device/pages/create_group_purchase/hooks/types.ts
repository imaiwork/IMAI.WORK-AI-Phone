import { ActionKey } from '@/ai_modules/device/enums'
// ─── 类型 ─────────────────────────────────────────────────────────────────────
export enum TaskType {
    FAVORITE = 1,
    SEARCH = 2
}

export enum LikeType {
    Avatar = 1,
    Video = 2
}

export enum KeyEditTarget {
    Keywords = 'filter',
    NicknameFilter = 'nickname_filter'
}

// ─── 常量 ─────────────────────────────────────────────────────────────────────
export const STEPS = [
    { step: 1, title: '基础设置' },
    { step: 2, title: '设定时间' }
]

export const TASK_TYPE_LIST = [
    { label: '收藏夹团购', value: 1 },
    { label: '搜索团购类型', value: 2 }
]

export const FAVORITE_PATH = [
    { icon: 'account', label: '主页' },
    { icon: 'bookmark', label: '收藏夹' },
    { icon: 'shopping-cart', label: '团购Tab' },
    { icon: 'chat', label: '评论列表' }
]

export const SEARCH_PATH = [
    { icon: 'home', label: '主页' },
    { icon: 'search', label: '搜索类型' },
    { icon: 'map', label: '筛选距离' },
    { icon: 'chat', label: '评论列表' }
]

export const DISTANCE_LIST = [
    { label: '不限', value: 0 },
    { label: '1km内', value: 1 },
    { label: '3km内', value: 3 },
    { label: '5km内', value: 5 },
    { label: '10km内', value: 10 }
]

export const ACTION_LIST = [
    { key: 1, label: '点赞', icon: 'heart' },
    { key: 2, label: '关注', icon: 'star' },
    { key: 3, label: '评论', icon: 'chat' },
    { key: 4, label: '私信', icon: 'chat' }
]

export const LIKE_TYPE_LIST = [
    { label: '点赞头像', value: LikeType.Avatar },
    { label: '点赞视频', value: LikeType.Video }
]

export const GENDER_LIST = [
    { label: '不限', value: '不限' },
    { label: '男', value: '男' },
    { label: '女', value: '女' }
]

export const COMMENT_USER_ACTION_KEYS: ActionKey[] = [ActionKey.Comment, ActionKey.Dm]

export const isCommentUserAction = (key: ActionKey | number): key is ActionKey.Comment | ActionKey.Dm =>
    COMMENT_USER_ACTION_KEYS.includes(Number(key) as ActionKey)

export const FREE_ACTION_LIST: typeof ACTION_LIST = []

export const MUTEX_ACTION_LIST = ACTION_LIST.filter((a) =>
    isCommentUserAction(a.key)
)

export interface GroupPurchaseFormData {
    name: string
    group_buy_type: TaskType
    send_num: number
    filter: string[]
    content_publish_day: number
    comment_offset: number
    marker_method: ActionKey[]
    like_type: LikeType
    persona_id: string
    watch_time: number
    interval_time: number
    nickname_filter: string[]
    gender: string
    city: string
    region: string
    radius: number | string
    group_type: string
    time_config: string[]
    task_exec_type: number
    task_frep: number
    accounts: any[]
    custom_date: string[]
    minutes: number
    task_ids: string[]
}

// ─── formData 工厂 ────────────────────────────────────────────────────────────
export const createDefaultFormData = (): GroupPurchaseFormData => ({
    name: `团购评论截流任务${uni.$u.timeFormat(new Date(), 'yyyymmddhhMM')}`,
    group_buy_type: TaskType.SEARCH,
    send_num: 50,
    filter: [],
    content_publish_day: 1,
    comment_offset: 1,
    marker_method: [ActionKey.Comment],
    like_type: LikeType.Avatar,
    persona_id: '',
    watch_time: 10,
    interval_time: 15,
    nickname_filter: [],
    gender: '不限',
    city: '',
    region: '',
    radius: 0,
    group_type: '',
    time_config: [
        uni.$u.timeFormat(new Date(), 'hh:MM'),
        uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), 'hh:MM')
    ],
    task_exec_type: 1,
    task_frep: 1,
    accounts: [],
    custom_date: [],
    minutes: 15,
    task_ids: []
})

export type PublishFormData = ReturnType<typeof createDefaultFormData>
