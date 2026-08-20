export enum CrawlType {
    ACCOUNT = 1,
    VIDEO = 0,
}

export enum GreetingContentSettingTypeEnum {
    ADD_FRIEND = "add_friends_prompt",
    PRIVATE_CHAT = "private_message_prompt",
}

export interface SphFormData {
    name: string;
    crawl_type: CrawlType;
    chat_type: string;
    chat_number: number;
    chat_interval_time: number;
    greeting_content: string;
    add_type: number;
    remark: string;
    add_number: number;
    add_interval_time: number;
    private_message_prompt: string;
    add_friends_prompt: string;
    wechat_id: string;
    wechat_reg_type: number;
    add_remark_enable: number;
    remarks: string[];
    keywords: string[];
    device_codes: string[];
    ocr_type: 1 | 2;
    task_frep: number;
    time_config: [string, string];
    custom_date: string[];
    wechat_time_type: 0 | 1;
    wechat_task_frep: number;
    wechat_time_config: [string, string];
    wechat_custom_date: string[];
    task_exec_type: number;
    minutes: number;
    task_ids: string[];
}

export const STEPS = [
    { step: 1, title: "选择类型" },
    { step: 2, title: "设置线索" },
    { step: 3, title: "填设置" },
    { step: 4, title: "设定时间" },
];

export const TASK_EXEC_TYPE_OPTIONS = [
    { icon: "arrow-upward", text: "即时执行", value: 1 },
    { icon: "clock", text: "定时执行", value: 0 },
];

export const FREQUENCY_OPTIONS = [1, 3, 5, 10, 30];

/** 开始/结束时间最小间隔（分钟） */
export const TIME_INTERVAL = 15;
