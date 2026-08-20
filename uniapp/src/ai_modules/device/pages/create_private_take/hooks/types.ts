// ── 类型 ─────────────────────────────────────────────────────────────────────
export enum TakeoverTypeEnum {
    COMMENT = 1,
    PM = 2
}

export enum StrategyTypeEnum {
    AI = 1,
    FIXED = 2
}

export enum CommentRuleEnum {
    AI = 1,
    FIXED = 2,
    LIKE_REPLY = 3
}

export enum ScriptTargetEnum {
    FIXED = 'fixed_scripts',
    COMMENT = 'comment_scripts'
}

export type TakeoverType = (typeof TakeoverTypeEnum)[keyof typeof TakeoverTypeEnum]
export type StrategyType = (typeof StrategyTypeEnum)[keyof typeof StrategyTypeEnum]
export type CommentRule = (typeof CommentRuleEnum)[keyof typeof CommentRuleEnum]
export type ScriptTarget = (typeof ScriptTargetEnum)[keyof typeof ScriptTargetEnum]

export const TAKEOVER_TABS = [
    { value: TakeoverTypeEnum.COMMENT, label: '回复评论' },
    { value: TakeoverTypeEnum.PM, label: '回复私信' }
]
export const STRATEGY_TABS = [
    { value: StrategyTypeEnum.AI, label: 'AI 拟人接管' },
    { value: StrategyTypeEnum.FIXED, label: '固定话术随机' }
] as const

export interface PrivateChatFormData {
    name: string
    accounts: { id: string; account: string; type: number }[]
    task_frep: number
    time_config: string[]
    task_exec_type: number
    minutes: number
    task_ids: string[]
    custom_date: string[]
    fixed_scripts: string[]
    comment_scripts: string[]
}

// ── 常量 ─────────────────────────────────────────────────────────────────────

export const STEPS = [
    { step: 1, title: '接管设置' },
    { step: 2, title: '基础设置' }
]

export const SCRIPT_EDIT_TITLE_MAP: Record<ScriptTarget, string> = {
    fixed_scripts: '编辑私信话术',
    comment_scripts: '编辑评论话术'
}

// ── 工厂函数 ──────────────────────────────────────────────────────────────────
export const createDefaultFormData = (): PrivateChatFormData => ({
    name: `私聊接管任务${uni.$u.timeFormat(new Date(), 'yyyymmddhhMM')}`,
    accounts: [],
    task_frep: 1,
    time_config: [
        uni.$u.timeFormat(new Date(), 'hh:MM'),
        uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), 'hh:MM')
    ],
    custom_date: [],
    task_exec_type: 1,
    minutes: 30,
    task_ids: [],
    fixed_scripts: [],
    comment_scripts: []
})
