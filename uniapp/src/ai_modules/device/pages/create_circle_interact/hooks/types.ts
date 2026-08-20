import { AppTypeEnum } from "@/enums/appEnums";

export { AppTypeEnum };

export const STEPS = [
    { step: 1, title: "调设置" },
    { step: 2, title: "设定时间" },
];

export interface CircleFormData {
    name: string;
    interaction_action: 1 | 2 | 3;
    interaction_count: number;
    interaction_time: number;
    interaction_time_type: 1 | 2 | 3;
    comment_type: 1 | 2 | 3;
    comment_content: string;
    accounts: any[];
    task_frep: number;
    time_config: string[];
    robot_id: number | string;
    custom_date: string[];
    robot_name: string;
    task_exec_type: number;
    minutes: number;
    task_ids: string[];
}

export const createDefaultFormData = (): CircleFormData => ({
    name: `朋友圈互动任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    interaction_action: 1,
    interaction_count: 1,
    interaction_time: 10,
    interaction_time_type: 1,
    comment_type: 1,
    comment_content: "",
    accounts: [],
    task_frep: 1,
    time_config: [
        uni.$u.timeFormat(new Date(), "hh:MM"),
        uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), "hh:MM"),
    ],
    custom_date: [],
    robot_id: "",
    robot_name: "",
    task_exec_type: 1,
    minutes: 30,
    task_ids: [],
});
