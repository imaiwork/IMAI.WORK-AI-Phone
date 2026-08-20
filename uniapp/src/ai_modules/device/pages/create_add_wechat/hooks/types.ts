// ============================================================
// types.ts  —  自动加好友任务 类型 / 常量 / 默认值
// ============================================================

export const STEPS = [
    { step: 1, title: "选择线索" },
    { step: 2, title: "调设置" },
    { step: 3, title: "设定时间" },
];

export const DAY_NUM_LIST = [1, 3, 5, 10, 15] as const;
export const TIME_INTERVAL_LIST = [5, 10, 15, 20] as const;

export interface AddFriendFormData {
    name: string;
    source: 1 | 2;
    fileurl: string;
    crawling_task_ids: string[];
    add_type: "0" | "1";
    add_number: number;
    add_interval_time: number;
    add_friends_prompt: string;
    add_remark_enable: 0 | 1;
    remarks: string[];
    wechat_id: string[];
    wechat_reg_type: 0 | 1 | 2;
    time_config: string[];
    task_frep: number;
    device_codes: string[];
    custom_date: string[];
    task_exec_type: number;
    minutes: number;
    task_ids: string[];
}

export const createDefaultFormData = (initialRemarks: string[] = []): AddFriendFormData => ({
    name: `自动加好友任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    crawling_task_ids: [],
    add_number: 1,
    add_interval_time: 5,
    remarks: initialRemarks,
    add_remark_enable: 1,
    add_friends_prompt: "",
    source: 1,
    fileurl: "",
    add_type: "1",
    wechat_id: [],
    wechat_reg_type: 0,
    time_config: [
        uni.$u.timeFormat(new Date(), "hh:MM"),
        uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), "hh:MM"),
    ],
    task_frep: 1,
    device_codes: [],
    custom_date: [],
    task_exec_type: 1,
    minutes: 30,
    task_ids: [],
});
