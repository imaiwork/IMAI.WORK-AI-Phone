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
    latest_log: PublishLogItem | unknown[]
    logs: PublishLogItem[]
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
