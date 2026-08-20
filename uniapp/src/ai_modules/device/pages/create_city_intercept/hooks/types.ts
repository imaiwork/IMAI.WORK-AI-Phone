// ==================== 类型定义 ====================

import { ActionKey } from "@/ai_modules/device/enums";

export enum KeyEditTarget {
    Keywords = "include_filter",
    NicknameFilter = "nickname_filter",
}

// ==================== 常量定义 ====================
export const STEPS = [
    { step: 1, title: "基础设置" },
    { step: 2, title: "设定时间" },
];

/** 互动动作列表 */
export const ACTION_LIST = [
    { key: 1, label: "点赞", icon: "heart" },
    { key: 2, label: "关注", icon: "star" },
    { key: 3, label: "评论", icon: "chat" },
    { key: 4, label: "私信", icon: "chat" },
];

export const FREE_ACTION_LIST = ACTION_LIST.filter((a) => ![ActionKey.Comment, ActionKey.Dm].includes(a.key));

export const MUTEX_ACTION_LIST = ACTION_LIST.filter((a) => [ActionKey.Comment, ActionKey.Dm].includes(a.key));

/** 距离筛选列表 */
export const DISTANCE_LIST = [
    { label: "不限", value: 0 },
    { label: "1km内", value: 1 },
    { label: "3km内", value: 3 },
    { label: "5km内", value: 5 },
    { label: "10km内", value: 10 },
];

export interface TaskForm {
    name: string;
    marker_method: ActionKey[];
    persona_id: string;
    watch_time: number;
    interval_time: number;
    radius: number | string;
    gender: string;
    like_num: number;
    age_min: number;
    age_max: number;
    comment_num: number;
    comment_fans_min_num: number;
    comment_fans_max_num: number;
    comment_follow_min_num: number;
    comment_follow_max_num: number;
    include_filter: string[];
    nickname_filter: string[];
    time_config: string[];
    task_exec_type: number;
    task_frep: number;
    accounts: any[];
    custom_date: string[];
    minutes: number;
    task_ids: string[];
}

/** 表单默认值 */
export const createDefaultFormData = (): TaskForm => ({
    name: `同城视频截流任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    marker_method: [ActionKey.Like, ActionKey.Follow, ActionKey.Comment],
    persona_id: "",
    watch_time: 10,
    interval_time: 15,
    radius: 0,
    gender: "不限",
    age_min: 18,
    age_max: 60,
    like_num: 10,
    comment_num: 10,
    comment_fans_min_num: 0,
    comment_fans_max_num: 1000,
    comment_follow_min_num: 0,
    comment_follow_max_num: 1000,
    include_filter: [],
    nickname_filter: [],
    time_config: [
        uni.$u.timeFormat(new Date(), "hh:MM"),
        uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), "hh:MM"),
    ],
    task_exec_type: 1,
    task_frep: 1,
    accounts: [],
    custom_date: [],
    minutes: 15,
    task_ids: [],
});

export type PublishFormData = ReturnType<typeof createDefaultFormData>;
