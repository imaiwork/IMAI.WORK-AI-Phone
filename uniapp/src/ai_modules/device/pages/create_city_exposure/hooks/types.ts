// ─── 步骤定义 ────────────────────────────────────────────────
export const STEPS = [
    { step: 1, title: "基本设置" },
    { step: 2, title: "设定时间" },
];

// ─── 任务频率 ────────────────────────────────────────────────
export const FREQUENCY_LIST = [
    { label: "1天", value: 1 },
    { label: "3天", value: 3 },
    { label: "5天", value: 5 },
    { label: "10天", value: 10 },
    { label: "30天", value: 30 },
    { label: "自定义", value: 0 },
];

// ─── 同城范围滑块 ─────────────────────────────────────────────
export const DISTANCE_MIN = 1;
export const DISTANCE_MAX = 50;

// ─── 默认表单 ────────────────────────────────────────────────
export interface SameCityFormData {
    name: string;
    radius: number;
    visit_num: number;
    interval_time: number;
    time_config: string[];
    task_exec_type: number;
    task_frep: number;
    minutes: number;
    accounts: { id: string; account: string; type: number }[];
    custom_date: string[];
    task_ids: string[];
}

export function createDefaultFormData(): SameCityFormData {
    return {
        name: `同城急速访问任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
        accounts: [],
        radius: 5,
        visit_num: 100,
        interval_time: 10,
        time_config: [
            uni.$u.timeFormat(new Date(), "hh:MM"),
            uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), "hh:MM"),
        ],
        task_exec_type: 1,
        task_frep: 1,
        minutes: 15,
        custom_date: [],
        task_ids: [],
    };
}

export type PublishFormData = ReturnType<typeof createDefaultFormData>;
