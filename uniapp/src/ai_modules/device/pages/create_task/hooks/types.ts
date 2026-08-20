// ─── 枚举 ─────────────────────────────────────────────────────────────────────
export enum TaskType {
    VIDEO = 1,
    IMAGE = 2
}

// ─── 接口 ─────────────────────────────────────────────────────────────────────
export interface TimeSlot {
    start_time: string
    end_time: string
}

export interface TimeConfig {
    date: string
    times: TimeSlot[]
}

export interface MaterialItem {
    url: string[]
}

export interface CopywriterItem {
    is_title_show: 0 | 1
    title: string
    content: string
    topic: string[]
}

export interface FormData {
    name: string
    introduction: string
    copywriterList: CopywriterItem[]
    materialList: MaterialItem[]
    time_config: TimeConfig[]
    accounts: any[]
    publish_frep: number
    custom_date: string[]
    task_frep: number
    location: string
    task_exec_type: number
}

// ─── 常量 ─────────────────────────────────────────────────────────────────────
export const VIDEO_CONFIG = {
    limit: 9,
    size: 200,
    format: ['mp4', 'mov']
} as const

export const TIME_INTERVAL = 30 // 分钟

export const STEPS = [
    { step: 1, title: '选择素材' },
    { step: 2, title: '填写文案' },
    { step: 3, title: '设定时间' }
]

export const PUBLISH_FREQUENCY_OPTIONS = [1, 2, 3, 5, 10]
export const TASK_FREQUENCY_OPTIONS = [1, 3, 5, 10, 30]

export const TASK_EXEC_TYPE_OPTIONS = [
    { icon: 'arrow-upward', text: '即时执行', value: 1 },
    { icon: 'clock', text: '定时执行', value: 0 }
]
