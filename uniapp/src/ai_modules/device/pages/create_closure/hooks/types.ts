// ================================================================
// types.ts  —  枚举 / 接口 / 常量 / 默认值
// ================================================================

export const STEPS = [
    { step: 1, title: '选择行业' },
    { step: 2, title: '设定评论' },
    { step: 3, title: '高级设置' },
    { step: 4, title: '设定时间' }
]

export const INDUSTRY_NUM_LIST = [1, 3, 5, 10, 20]
export const COMMENT_NUM_LIST = [1, 3, 5, 10, 20]

export const COMMENT_TIME_LIST = [
    { value: 0, label: '不限' },
    { value: 1, label: '一天内' },
    { value: 7, label: '一周内' },
    { value: 180, label: '半年内' }
]

export const normalizeCommentTimeValue = (value: unknown): 0 | 1 | 7 | 180 => {
    const num = Number(value)
    if (num === 1) return 1
    if (num >= 2 && num <= 7) return 7
    if (num >= 8 && num <= 180) return 180
    return 0
}

export const getCommentTimeIndex = (value: unknown): number[] => {
    const normalized = normalizeCommentTimeValue(value)
    const index = COMMENT_TIME_LIST.findIndex((item) => item.value === normalized)
    return [index >= 0 ? index : 0]
}

export const TOUCH_TYPE_LIST_DEFAULT = [
    { name: 1, label: '点赞评论', checked: false },
    { name: 2, label: '关注', checked: false },
    { name: 3, label: '点赞作品', checked: false },
    { name: 4, label: '评论作品', checked: false },
    { name: 5, label: '收藏作品', checked: false }
]

export const COMMENT_FILTER_DEFAULT_SHOW = 2
export const COMMENT_CONTENT_DEFAULT_SHOW = 2
export const FIXED_COMMENT_DEFAULT_SHOW = 2

export interface CommentFilterItem {
    id: number
    value: string
    checked: boolean
}

export interface ClosureFormData {
    name: string
    customer_type: number
    region: string
    industry: string[]
    industryNum: number
    commentNum: number
    comment_filter_list: CommentFilterItem[]
    comment_content_list: string[]
    fixed_comment_list: string[]
    skip_author: 0 | 1
    filter_executed_customer: 0 | 1
    comment_like: string
    comment_follow: string
    comment_time_index: number[]
    comment_region: string[]
    comment_gender: string
    comment_age: string
    comment_account_feature: string
    comment_time: number
    content_time_index: number[]
    content_time: number
    accounts: string[]
    task_frep: number
    custom_date: string[]
    time_config: string[]
    comment_type: number
    task_exec_type: number
    minutes: number
    task_ids: string[]
}

export function createDefaultFormData(): ClosureFormData {
    return {
        name: '',
        region: '',
        customer_type: 0,
        industry: [],
        industryNum: 1,
        commentNum: 1,
        comment_filter_list: [],
        comment_content_list: [],
        fixed_comment_list: [],
        skip_author: 1,
        filter_executed_customer: 1,
        comment_like: '1',
        comment_follow: '1',
        comment_time_index: [0],
        content_time_index: [0],
        content_time: 0,
        comment_region: [],
        comment_gender: '不限',
        comment_age: '不限',
        comment_account_feature: '0',
        comment_time: 0,
        accounts: [],
        task_frep: 1,
        custom_date: [],
        time_config: [
            uni.$u.timeFormat(new Date(), 'hh:MM'),
            uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), 'hh:MM')
        ],
        comment_type: 1,
        task_exec_type: 1,
        minutes: 30,
        task_ids: []
    }
}
