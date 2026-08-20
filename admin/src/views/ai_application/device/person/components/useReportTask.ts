// composables/useReportTask.ts
import { computed, ref, type Ref } from "vue";
import { AppTypeEnum } from "@/enums/appEnums";

// ===================== 类型定义 =====================

export interface TemplatePlatformItem {
    order: string;
    account_type: string;
}

export interface TemplateDataItem {
    id: number;
    user_id: number;
    persona_id: number;
    template_id: number;
    start_time: string;
    end_time: string;
    task_category: string;
    scene: number;
    platform: TemplatePlatformItem[];
    remark: string | null;
    create_time: string;
    update_time: string | null;
    status: number;
}

export interface TaskDisplayItem {
    time: string;
    platform: string;
    title: string;
    why: string;
    do: string;
    effect: string;
}

// ===================== 枚举 =====================

export enum TaskType {
    MORNING = "morning",
    MORNING_GROWTH = "morning_growth",
    AFTERNOON_PEAK = "afternoon_peak",
    AFTERNOON_REST = "afternoon_rest",
    EVENING_PEAK = "evening_peak",
}

// ===================== 常量 =====================

/** account_type → 平台显示名称 */
export const accountTypeNameMap: Record<string, string> = {
    [String(AppTypeEnum.DOUYIN)]: "抖音",
    [String(AppTypeEnum.XHS)]: "小红书",
    [String(AppTypeEnum.KUAISHOU)]: "快手",
    [String(AppTypeEnum.SPH)]: "视频号",
    [String(AppTypeEnum.WECHAT)]: "微信",
};

/** scene → 文案描述 */
export const sceneDescMap: Record<number, { why: string; do: string; effect: string }> = {
    1: {
        why: "精准截流同行评论区的高意向用户",
        do: "搜索关键词，对评论区意向用户执行点赞评论+关注",
        effect: "高概率获得目标客户的回关和主动咨询",
    },
    2: {
        why: "主动出击，直接触达潜在客户",
        do: "搜索关键词找同行视频，对符合预设词的用户发送私信",
        effect: "跳过养号周期，直接获取精准线索",
    },
    3: {
        why: "增加在精准人群中的曝光度",
        do: "对符合画像的作者执行：关注+点赞+评论",
        effect: "吸引潜在客户关注，建立初步连接",
    },
    4: {
        why: "借助视频号公域流量获取客资",
        do: "在视频号发布内容并与微信好友互动",
        effect: "打通公私域，扩大获客渠道",
    },
    5: {
        why: "抢占平台流量高峰期",
        do: "矩阵同步分发视频",
        effect: "全面提升内容曝光，增加自然流量",
    },
    6: {
        why: "及时响应用户留言，避免线索流失",
        do: "自动处理平台私信消息",
        effect: "提升客户好感度，防止线索流失",
    },
    7: {
        why: "打造个人IP人设，增强信任感",
        do: "发布视频展示个人IP日常或专业干货",
        effect: "增强私域客户的信任感",
    },
    8: {
        why: "唤醒沉睡客户，提升私域活跃度",
        do: "自动给微信好友的朋友圈点赞/评论",
        effect: "提升私域活跃度，增加个人IP存在感",
    },
    9: {
        why: "将公域获取的意向客户沉淀到私域",
        do: "发送好友请求，附带专属话术",
        effect: "安全扩大私域流量池",
    },
    10: {
        why: "提升账号活跃度与真实性",
        do: "浏览行业相关图文/视频，模拟正常用户行为",
        effect: "提高账号权重，规避营销号风险",
    },
    11: {
        why: "维护内容热度，提升互动率",
        do: "自动回复评论",
        effect: "提升账号活跃度与互动率",
    },
    12: {
        why: "提升同城用户曝光",
        do: "在同城页面发布和互动",
        effect: "精准触达本地潜在客户",
    },
    13: {
        why: "截流同城高意向用户",
        do: "在同城内容评论区精准触达用户",
        effect: "高效获取同城本地精准客资",
    },
    14: {
        why: "截流团购意向用户",
        do: "在团购相关内容评论区触达用户",
        effect: "获取高消费意向的精准线索",
    },
    15: {
        why: "提升账号互动数据",
        do: "对目标评论执行点赞操作",
        effect: "提升账号权重与内容热度",
    },
};

// ===================== Hook 入参 =====================

export interface UseReportTaskOptions {
    /** 各业务线自定义的 Tab 名称，按 TaskType 顺序传入 */
    taskTypeNames?: Partial<Record<TaskType, string>>;
    /** 各业务线自定义的任务图标映射，会与默认值合并 */
    extraIconMap?: Record<string, string>;
    /** 各业务线需要覆盖的 sceneDesc，会与默认值合并 */
    extraSceneDescMap?: typeof sceneDescMap;
}

// ===================== 默认 Tab 名称 =====================

const defaultTaskTypeNames: Record<TaskType, string> = {
    [TaskType.MORNING]: "早间时段",
    [TaskType.MORNING_GROWTH]: "上午时段",
    [TaskType.AFTERNOON_PEAK]: "午间时段",
    [TaskType.AFTERNOON_REST]: "下午时段",
    [TaskType.EVENING_PEAK]: "晚间时段",
};

/** 默认任务图标映射 */
const defaultIconMap: Record<string, string> = {
    自动养号: "🌱",
    视频发布: "📹",
    私信接管: "💬",
    "私信接管（拉群）": "💬",
    评论接管: "🗨️",
    截流获客: "🎯",
    留痕获客: "👀",
    自动加好友: "➕",
    朋友圈互动: "❤️",
    朋友圈发布: "📱",
    同城曝光: "📡",
    同城触达: "📍",
    同城截流: "🎯",
    团购截流: "🛒",
    评论点赞: "👍",
    视频号自动获客: "🎥",
    视频号获客: "🎥",
};

// ===================== 工具函数（纯函数，可单独导出测试） =====================

export const timeToMinutes = (time: string): number => {
    const [h, m] = time.split(":").map(Number);
    return h * 60 + m;
};

export const getTaskType = (startTime: string): TaskType => {
    const minutes = timeToMinutes(startTime);
    if (minutes < 9 * 60) return TaskType.MORNING;
    if (minutes < 12 * 60) return TaskType.MORNING_GROWTH;
    if (minutes < 14 * 60) return TaskType.AFTERNOON_PEAK;
    if (minutes < 18 * 60) return TaskType.AFTERNOON_REST;
    return TaskType.EVENING_PEAK;
};

export const getPlatformName = (platforms: TemplatePlatformItem[]): string => {
    if (!platforms || platforms.length === 0) return "未知平台";
    return platforms.map((p) => accountTypeNameMap[p.account_type] ?? `平台${p.account_type}`).join("、");
};

// ===================== Composable =====================

export function useReportTask(templateData: Ref<TemplateDataItem[]>, options: UseReportTaskOptions = {}) {
    const { taskTypeNames = {}, extraIconMap = {}, extraSceneDescMap = {} } = options;

    // 合并自定义配置
    const mergedTaskTypeNames = { ...defaultTaskTypeNames, ...taskTypeNames };
    const mergedIconMap = { ...defaultIconMap, ...extraIconMap };
    const mergedSceneDescMap = { ...sceneDescMap, ...extraSceneDescMap };

    // ---- 内部工具 ----

    const convertToDisplayTask = (item: TemplateDataItem): TaskDisplayItem => {
        const sceneDesc = mergedSceneDescMap[item.scene] ?? {
            why: "执行自动化任务",
            do: "按系统配置执行",
            effect: "提升账号运营效率",
        };
        return {
            time: `${item.start_time} - ${item.end_time}`,
            platform: getPlatformName(item.platform),
            title: item.task_category,
            why: sceneDesc.why,
            do: sceneDesc.do,
            effect: sceneDesc.effect,
        };
    };

    const getTaskIcon = (title: string): string => mergedIconMap[title] ?? "⚙️";

    // ---- 响应式状态 ----

    const currentTaskType = ref<TaskType>(TaskType.MORNING);

    const switchTaskType = (type: TaskType) => {
        currentTaskType.value = type;
    };

    // ---- 计算属性 ----

    const dynamicTaskTable = computed<Record<TaskType, TaskDisplayItem[]>>(() => {
        const table: Record<TaskType, TaskDisplayItem[]> = {
            [TaskType.MORNING]: [],
            [TaskType.MORNING_GROWTH]: [],
            [TaskType.AFTERNOON_PEAK]: [],
            [TaskType.AFTERNOON_REST]: [],
            [TaskType.EVENING_PEAK]: [],
        };
        if (!templateData.value?.length) return table;

        const sorted = [...templateData.value].sort(
            (a, b) => timeToMinutes(a.start_time) - timeToMinutes(b.start_time),
        );
        for (const item of sorted) {
            table[getTaskType(item.start_time)].push(convertToDisplayTask(item));
        }
        return table;
    });

    const availableTaskTypes = computed(() =>
        (Object.keys(mergedTaskTypeNames) as TaskType[])
            .filter((key) => dynamicTaskTable.value[key]?.length > 0)
            .map((key) => ({ key, name: mergedTaskTypeNames[key] })),
    );

    const totalTaskCount = computed(() => templateData.value?.length ?? 0);

    const currentTasks = computed<TaskDisplayItem[]>(() => dynamicTaskTable.value[currentTaskType.value] ?? []);

    // 当可用 Tab 变化时，若当前选中 Tab 无数据则自动跳到第一个有数据的 Tab
    watch(
        availableTaskTypes,
        (types) => {
            if (types.length && !types.find((t) => t.key === currentTaskType.value)) {
                currentTaskType.value = types[0].key;
            }
        },
        { immediate: true },
    );

    return {
        currentTaskType,
        availableTaskTypes,
        totalTaskCount,
        currentTasks,
        switchTaskType,
        getTaskIcon,
    };
}
