// 全网发布监控 - 接口数据结构（getPublishTaskList）
export interface PublishLogItem {
    id?: number
    task_id?: number
    message?: string
    image?: string
    create_time?: string
}

export interface PublishItem {
    task_id: number
    detail_id?: number
    device_code?: string
    platform: number
    platform_name: string
    account: string
    nickname: string
    avatar?: string
    media_type: number
    media_type_text: string
    material_title: string
    material_subtitle: string
    material_tag?: string[] | string
    material_url: string
    pic: string
    publish_time: string
    task_status: number
    task_status_text: string
    remark: string
    can_resend?: boolean
    has_generated_video?: boolean
    generated_disabled_reason?: string
    device_running?: boolean
    resend_tip?: string
    latest_log: PublishLogItem | unknown[]
    logs: PublishLogItem[]
}

/** checkPublishResend 返回的已生成视频 */
export interface ResendGeneratedVideo {
    video_task_id: number
    video_url: string
    pic: string
}

/** checkPublishResend 返回结构（节选前端用到的字段） */
export interface ResendCheckData {
    tip: string
    can_resend: boolean
    /** 旧后端可能不返回，仅显式 false 视为离线 */
    device_online?: boolean
    device_offline_reason?: string
    device_running: boolean
    publish_kind: 'content' | 'circle'
    publish_kind_desc: string
    task_id: number
    detail_id: number
    material_title: string
    material_subtitle: string
    material_tag: string
    poi: string
    has_generated_video: boolean
    generated_disabled_reason: string
    generated_video: ResendGeneratedVideo | null
}

export interface PublishDevice {
    device_code: string
    account: string
    nickname: string
    avatar: string
    items: PublishItem[]
}

export interface PublishSlot {
    slot_key: string
    time_config: string
    time_range: string
    status: number
    status_text: string
    total_count: number
    success_count: number
    failed_count: number
    running_count: number
    waiting_count: number
    devices: PublishDevice[]
}

export interface PublishTab {
    slot_key: string
    time_range: string
    status: number
    status_text: string
}
