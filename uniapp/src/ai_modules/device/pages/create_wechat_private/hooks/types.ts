export const STEPS = [
    { step: 1, title: '回复设置' },
    { step: 2, title: '设定时间' }
]

export interface WechatPrivateFormData {
    name: string
    interaction_action_switch: 0 | 1
    interaction_action: 0 | 1 | 2
    interaction_content: string
    stage_reply_switch: 0 | 1
    multi_message_type: 0 | 1 | 2
    image_reply_type: 1 | 2 | 3
    image_reply_content: string
    sensitive_word_switch: 0 | 1
    sensitive_word: string[]
    voice_reply_type: 1 | 2 | 3
    voice_reply_content: string
    accounts: any[]
    task_frep: number
    time_type: 0 | 1
    time_config: string[]
    custom_date: string[]
    task_exec_type: number
    minutes: number
    task_ids: string[]
    // 自动加群
    is_auto_group: 0 | 1
    group_trigger_mode: 1 | 2
    group_trigger_keywords: string[]
    sales_wechat: string[]
    group_name_template: string
    is_greeting: 0 | 1
    greeting_text: string
    is_share_chats: 0 | 1
}

export const DEFAULT_FORM_DATA = (): WechatPrivateFormData => ({
    name: `个微接管任务${uni.$u.timeFormat(new Date(), 'yyyymmddhhMM')}`,
    interaction_action_switch: 1,
    interaction_action: 0,
    interaction_content: '',
    stage_reply_switch: 1,
    multi_message_type: 0,
    image_reply_type: 1,
    image_reply_content: '',
    sensitive_word_switch: 1,
    sensitive_word: [],
    voice_reply_type: 1,
    voice_reply_content: '',
    accounts: [],
    task_frep: 1,
    time_type: 0,
    time_config: ['09:00', '09:30'],
    custom_date: [],
    task_exec_type: 1,
    minutes: 30,
    task_ids: [],
    is_auto_group: 1,
    group_trigger_mode: 1,
    group_trigger_keywords: [],
    sales_wechat: [],
    group_name_template: '{客户名}的专属VIP服务群',
    is_greeting: 1,
    greeting_text:
        '哈喽{客户名}，欢迎！我是您的专属销售顾问，以后有任何问题都可以直接在这个群里找我哦~',
    is_share_chats: 0
})
