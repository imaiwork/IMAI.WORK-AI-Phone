import { defineStore } from "pinia";
import {
    getTaskWorkTemplateDetail as getTaskWorkTemplateDetailApi,
    getPersonaUsedTaskWorkTemplateDetail as getPersonaUsedTaskWorkTemplateDetailApi,
    addTaskWorkTemplate as addTaskWorkTemplateApi,
    deleteTaskWorkTemplate as deleteTaskWorkTemplateApi,
    updateTaskWorkTemplateNode as updateTaskWorkTemplateNodeApi,
    resetTaskWorkTemplate as resetTaskWorkTemplateApi,
    useTaskWorkTemplate as useTaskWorkTemplateApi,
    updateTaskWorkTemplateNodeStatus as updateTaskWorkTemplateNodeStatusApi,
    getWorkflowSceneLists as getWorkflowSceneListsApi,
} from "@/api/person";
import { AppTypeEnum } from "@/enums/appEnums";

// ─── 枚举定义 ──────────────────────────────────────────────────

export enum TemplateTypeEnum {
    FIXED = 1,
    SYSTEM = 3,
    EDITABLE = 2,
}

export enum IndustryEnum {
    EXCLUSIVE = "专属",
    SYSTEM = "系统",
    CUSTOM = "自定义",
}

export enum FocusEnum {
    // 综合
    GENERAL = 1,
    // 获客
    ACQUIRE = 2,
    // 运营
    OPERATE = 3,
    // 养号
    NURTURE = 4,
}

export const FocusEnumMap: Record<FocusEnum, string> = {
    [FocusEnum.ACQUIRE]: "获客",
    [FocusEnum.OPERATE]: "运营",
    [FocusEnum.NURTURE]: "养号",
    [FocusEnum.GENERAL]: "综合",
};

// ─── 类型定义 ──────────────────────────────────────────────────

export interface ScheduleRaw {
    id: number;
    user_id: number;
    persona_id: number;
    template_id: number;
    start_time: string;
    end_time: string;
    task_category: string;
    scene: number;
    platform: { order: number; account_type: number }[];
    remark: string;
    status: number;
    create_time: string;
    update_time: string | null;
    is_default: number;
}

export interface TemplateRaw {
    id: number;
    type: number;
    user_id: number;
    persona_id: number;
    name: string;
    category_id: number;
    operation_preference: FocusEnum;
    description: string;
    status: number;
    is_modified: number;
    is_system_generated: number;
    original_id: number | null;
    create_time: string;
    update_time: string;
    schedule_count: number;
    schedule: ScheduleRaw[];
}

export interface TaskItem {
    id: number;
    time: string;
    title: string;
    platform: string;
    remark: string;
    scene: number;
    status: number;
    platformRaw: { order: number; account_type: number }[];
    is_default: number;
}

export interface TemplateItem {
    id: string;
    name: string;
    type: TemplateTypeEnum;
    industry: IndustryEnum | string;
    focus: keyof typeof FocusEnumMap;
    desc: string;
    schedule_count: number;
    isSystem: boolean;
    tasks: TaskItem[];
}

export interface CategoryItem {
    id: number;
    name: string;
}

export interface PlatformOption {
    type: number;
    name: string;
}

export interface TaskConfigItem {
    label: string;
    scene: number;
    platforms: PlatformOption[];
}

export interface AddableSceneItem {
    scene: number;
    name: string;
    /** 后端开放的平台 account_type；接口未下发时为 null，表示不做平台过滤 */
    platforms: number[] | null;
}

export interface SchedulePayload {
    start_time: string;
    end_time: string;
    scene: number;
    platform: { order: number; account_type: number }[];
}

// ─── taskConfig ────────────────────────────────────────────────

const XHS_TYPE = AppTypeEnum.XHS;
const KUAISHOU_TYPE = AppTypeEnum.KUAISHOU;
const DOYIN_TYPE = AppTypeEnum.DOUYIN;
const SPH_TYPE = AppTypeEnum.SPH;
const WX_TYPE = AppTypeEnum.WECHAT;

export enum TaskScene {
    COMMENT_GET_CUSTOMER = 1,
    PRIVATE_MESSAGE_GET_CUSTOMER = 2,
    LEAVE_TRAIL_GET_CUSTOMER = 3,
    VIDEO_NUMBER_GET_CUSTOMER = 4,
    VIDEO_PUBLISH = 5,
    PRIVATE_MESSAGE_RECEIVE = 6,
    FRIEND_CIRCLE_PUBLISH = 7,
    FRIEND_CIRCLE_INTERACTION = 8,
    AUTO_ADD_FRIEND = 9,
    AUTO_HONGBAO = 10,
    COMMENT_RECEIVE = 11,
    CITY_EXPOSURE = 12,
    CITY_INTERCEPT = 13,
    GROUP_PURCHASE_INTERCEPT = 14,
    COMMENT_LIKE = 15,
}

export const taskConfig: Record<TaskScene, TaskConfigItem> = {
    [TaskScene.COMMENT_GET_CUSTOMER]: {
        label: "截流评论获客",
        scene: 1,
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    [TaskScene.PRIVATE_MESSAGE_GET_CUSTOMER]: {
        label: "截流私信获客",
        scene: 2,
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    [TaskScene.LEAVE_TRAIL_GET_CUSTOMER]: {
        label: "留痕获客",
        scene: 3,
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
        ],
    },
    [TaskScene.VIDEO_NUMBER_GET_CUSTOMER]: {
        label: "视频号获客",
        scene: 4,
        platforms: [{ type: SPH_TYPE, name: "视频号" }],
    },
    [TaskScene.VIDEO_PUBLISH]: {
        label: "视频发布",
        scene: 5,
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
            { type: SPH_TYPE, name: "视频号" },
        ],
    },
    [TaskScene.PRIVATE_MESSAGE_RECEIVE]: {
        label: "私信接管/个微接管",
        scene: 6,
        platforms: [
            { type: WX_TYPE, name: "微信" },
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    [TaskScene.FRIEND_CIRCLE_PUBLISH]: {
        label: "朋友圈发布",
        scene: 7,
        platforms: [{ type: WX_TYPE, name: "微信" }],
    },
    [TaskScene.FRIEND_CIRCLE_INTERACTION]: {
        label: "朋友圈互动",
        scene: 8,
        platforms: [{ type: WX_TYPE, name: "微信" }],
    },
    [TaskScene.AUTO_ADD_FRIEND]: {
        label: "自动加好友",
        scene: 9,
        platforms: [{ type: WX_TYPE, name: "微信" }],
    },
    [TaskScene.AUTO_HONGBAO]: {
        label: "自动养号",
        scene: 10,
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    [TaskScene.COMMENT_RECEIVE]: {
        label: "评论接管",
        scene: 11,
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    [TaskScene.CITY_EXPOSURE]: {
        label: "同城曝光",
        scene: 12,
        platforms: [{ type: DOYIN_TYPE, name: "抖音" }],
    },
    [TaskScene.CITY_INTERCEPT]: {
        label: "同城截流",
        scene: 13,
        platforms: [{ type: DOYIN_TYPE, name: "抖音" }],
    },
    [TaskScene.GROUP_PURCHASE_INTERCEPT]: {
        label: "团购截流",
        scene: 14,
        platforms: [{ type: DOYIN_TYPE, name: "抖音" }],
    },
    [TaskScene.COMMENT_LIKE]: {
        label: "评论点赞",
        scene: 15,
        platforms: [{ type: SPH_TYPE, name: "视频号" }],
    },
};

export const taskConfigList: TaskConfigItem[] = Object.values(taskConfig).sort((a, b) => a.scene - b.scene);
export const taskLabelList: string[] = taskConfigList.map((c) => c.label);

/** 将接口场景列表与本地平台配置合并；无平台配置、平台全部关闭的场景跳过 */
export const buildSelectableTaskConfigList = (scenes: AddableSceneItem[]): TaskConfigItem[] => {
    const result: TaskConfigItem[] = [];
    for (const item of scenes) {
        const config = taskConfig[item.scene as TaskScene];
        if (!config?.platforms?.length) continue;
        // 后端下发开放平台时按后台平台开关过滤，未下发（旧接口）保持本地全量
        const platforms = item.platforms
            ? config.platforms.filter((p) => item.platforms?.includes(p.type))
            : config.platforms;
        if (!platforms.length) continue;
        result.push({
            label: item.name || config.label,
            scene: item.scene,
            platforms,
        });
    }
    return result;
};

const normalizeAddableScenes = (raw: unknown): AddableSceneItem[] => {
    const list = Array.isArray(raw) ? raw : (raw as any)?.lists ?? (raw as any)?.data ?? [];
    if (!Array.isArray(list)) return [];
    return list
        .map((item: any) => ({
            scene: Number(item?.scene),
            name: String(item?.name ?? ""),
            platforms: Array.isArray(item?.platforms)
                ? item.platforms.map((p: any) => Number(p)).filter((p: number) => Number.isFinite(p))
                : null,
        }))
        .filter((item: AddableSceneItem) => Number.isFinite(item.scene) && item.scene > 0);
};

// ─── 工具函数 ──────────────────────────────────────────────────

export const timeToMins = (timeStr: string): number => {
    if (!timeStr) return 0;
    const [h, m] = timeStr.split(":");
    return parseInt(h, 10) * 60 + parseInt(m, 10);
};

export const minsToTime = (mins: number): string => {
    const h = Math.floor(mins / 60)
        .toString()
        .padStart(2, "0");
    const m = (mins % 60).toString().padStart(2, "0");
    return `${h}:${m}`;
};

export const MINUTES_PER_PLATFORM = 10;

export const shouldLockTaskEndTime = (scene: number, platformCount: number) =>
    scene === TaskScene.VIDEO_PUBLISH || platformCount > 1;

export const resolveLockedEndTime = (
    scene: number,
    startTime: string,
    platformCount: number,
    fallbackEnd: string,
) =>
    shouldLockTaskEndTime(scene, platformCount) && platformCount > 0
        ? minsToTime(timeToMins(startTime) + platformCount * MINUTES_PER_PLATFORM)
        : fallbackEnd;

export const buildPlatformPayload = (scene: TaskScene, names: string[]): { order: number; account_type: number }[] => {
    const platformOptions = taskConfig[scene]?.platforms ?? [];
    return names
        .map((name, idx) => {
            const found = platformOptions.find((p) => p.name === name);
            return found ? { order: idx + 1, account_type: found.type } : null;
        })
        .filter(Boolean) as { order: number; account_type: number }[];
};

export const getPlatformName = (scene: TaskScene, accountType: number): string => {
    const config = taskConfig[scene];
    if (config) {
        const found = config.platforms.find((p) => p.type == accountType);
        if (found) return found.name;
    }
    for (const c of Object.values(taskConfig)) {
        const found = c.platforms.find((p) => p.type == accountType);
        if (found) return found.name;
    }
    return `未知(${accountType})`;
};

const toTaskItem = (s: ScheduleRaw): TaskItem => {
    const platformNames = [...s.platform]
        .sort((a, b) => Number(a.order) - Number(b.order))
        .map((p) => getPlatformName(s.scene, p.account_type))
        .join("、");
    const endTime = resolveLockedEndTime(s.scene, s.start_time, s.platform.length, s.end_time);
    return {
        id: s.id,
        time: `${s.start_time}-${endTime}`,
        title: s.task_category,
        platform: platformNames,
        remark: s.remark ?? "",
        scene: s.scene,
        status: s.status ?? 1,
        platformRaw: s.platform.map((p) => ({
            order: Number(p.order),
            account_type: Number(p.account_type),
        })),
        is_default: s.is_default ?? 0,
    };
};

export const toTemplateItem = (raw: TemplateRaw): TemplateItem => {
    let type: TemplateTypeEnum;
    if (raw.type === TemplateTypeEnum.FIXED) type = TemplateTypeEnum.FIXED;
    else if (raw.type === TemplateTypeEnum.SYSTEM) type = TemplateTypeEnum.SYSTEM;
    else type = TemplateTypeEnum.EDITABLE;
    return {
        id: String(raw.id),
        name: raw.name,
        type,
        industry:
            raw.type === TemplateTypeEnum.EDITABLE
                ? IndustryEnum.CUSTOM
                : type === TemplateTypeEnum.SYSTEM
                ? IndustryEnum.SYSTEM
                : IndustryEnum.EXCLUSIVE,
        focus: raw.operation_preference,
        desc: raw.description ?? "",
        schedule_count: raw.schedule_count,
        isSystem: raw.type === 3,
        tasks: (raw.schedule ?? []).map(toTaskItem),
    };
};

const buildSchedulePayload = (
    tasks: TaskItem[],
    allowedPlatformsByScene?: Map<number, number[]>,
): SchedulePayload[] => {
    return tasks.map((t) => {
        const [start_time, rawEnd] = t.time.split("-");
        const allowed = allowedPlatformsByScene?.get(t.scene);
        let platform = [...t.platformRaw].sort((a, b) => a.order - b.order);
        if (allowed) {
            platform = platform
                .filter((p) => allowed.includes(p.account_type))
                .map((p, idx) => ({ order: idx + 1, account_type: p.account_type }));
        }
        return {
            start_time,
            end_time: resolveLockedEndTime(t.scene, start_time, platform.length, rawEnd),
            scene: t.scene,
            platform,
        };
    });
};

// ─── Store 定义 ────────────────────────────────────────────────

export const useTaskStore = defineStore("taskFlow", () => {
    const currentTemplate = ref<TemplateItem | null>(null);
    const currentTemplateId = ref<string>("");
    const currentTasks = ref<TaskItem[]>([]);
    const isLoading = ref<boolean>(false);
    const personaId = ref<number>(0);
    const previewTemplate = ref<TemplateItem | null>(null);
    const previewTasks = ref<TaskItem[]>([]);
    /** 接口返回的可添加场景；失败时为空，选择器回退本地 taskConfigList */
    const addableScenes = ref<AddableSceneItem[]>([]);
    const addableScenesLoaded = ref(false);
    const addableScenesFailed = ref(false);

    const clearCurrentTemplate = () => {
        currentTemplate.value = null;
        currentTemplateId.value = "";
        currentTasks.value = [];
    };

    const isSystem = computed(() => currentTemplate.value?.isSystem || previewTemplate.value?.isSystem || false);
    const isReadOnly = computed(() => {
        return previewTemplate.value
            ? previewTemplate.value?.type == TemplateTypeEnum.FIXED ||
                  previewTemplate.value?.type == TemplateTypeEnum.SYSTEM
            : currentTemplate.value?.type == TemplateTypeEnum.FIXED ||
                  currentTemplate.value?.type == TemplateTypeEnum.SYSTEM;
    });

    /** 严格可添加列表：仅接口 allow_add=1 且平台开放的场景；失败时回退本地全量 */
    const selectableTaskConfigList = computed<TaskConfigItem[]>(() => {
        if (addableScenesLoaded.value && !addableScenesFailed.value) {
            return buildSelectableTaskConfigList(addableScenes.value);
        }
        return taskConfigList;
    });

    const selectableTaskLabelList = computed(() => selectableTaskConfigList.value.map((c) => c.label));

    const getTaskConfigByScene = (scene: number): TaskConfigItem | undefined => {
        return (
            selectableTaskConfigList.value.find((c) => c.scene === scene) ??
            taskConfig[scene as TaskScene]
        );
    };

    /**
     * 编辑弹窗可选列表：可添加场景 + 当前节点场景（若已关闭仍需回显）。
     * 已关闭类型标签追加「（已关闭）」，与后台模板编辑一致。
     * 纯函数，不污染全局 addableScenes，避免「添加任务」带出已关闭类型。
     */
    const getEditSelectableTaskConfigList = (
        currentScene: number,
        fallbackName?: string,
    ): TaskConfigItem[] => {
        const base = selectableTaskConfigList.value;
        if (!currentScene || base.some((item) => item.scene === currentScene)) return base;
        const config = taskConfig[currentScene as TaskScene];
        if (!config?.platforms?.length) return base;
        const name = fallbackName || config.label;
        return [
            ...base,
            {
                label: name.includes("（已关闭）") ? name : `${name}（已关闭）`,
                scene: currentScene,
                platforms: config.platforms,
            },
        ];
    };

    const fetchAddableScenes = async () => {
        try {
            const res = await getWorkflowSceneListsApi();
            addableScenes.value = normalizeAddableScenes(res);
            addableScenesFailed.value = false;
        } catch {
            addableScenes.value = [];
            addableScenesFailed.value = true;
        } finally {
            addableScenesLoaded.value = true;
        }
    };

    // ─── 初始化（仅设置 personaId + 拉当前模板详情）────────────
    const init = async (pId: number) => {
        personaId.value = pId;
        clearCurrentTemplate();
        await Promise.all([fetchTemplateDetail(), fetchAddableScenes()]);
    };

    // ─── 获取当前使用中的模板详情 ──────────────────────────────
    const fetchTemplateDetail = async () => {
        if (!personaId.value) return;
        isLoading.value = true;
        try {
            const res = await getPersonaUsedTaskWorkTemplateDetailApi({
                persona_id: personaId.value,
            });
            if (res) {
                const tpl = toTemplateItem(res as TemplateRaw);
                currentTemplate.value = tpl;
                currentTemplateId.value = tpl.id;
                currentTasks.value = tpl.tasks;
            } else clearCurrentTemplate();
        } catch {
            clearCurrentTemplate();
        } finally {
            isLoading.value = false;
        }
    };

    // ─── 查看指定模板详情（预览模式，不影响当前套用状态）────────
    const fetchTemplateById = async (templateId: string) => {
        isLoading.value = true;
        try {
            const res = await getTaskWorkTemplateDetailApi({ id: Number(templateId) });
            if (res) {
                const tpl = toTemplateItem(res as TemplateRaw);
                previewTemplate.value = tpl;
                previewTasks.value = tpl.tasks;
            }
        } finally {
            isLoading.value = false;
        }
    };

    // 清空预览数据（离开预览页时调用）
    const clearPreview = () => {
        previewTemplate.value = null;
        previewTasks.value = [];
    };

    // ─── 切换模板 ──────────────────────────────────────────────
    const switchTemplate = async (tplId: string) => {
        currentTemplateId.value = tplId;
        await useCurrentTemplate();
        await fetchTemplateDetail();
    };

    // ─── 新增自定义模板 ────────────────────────────────────────
    const createTemplate = async (name: string) => {
        if (!personaId.value) return;
        await addTaskWorkTemplateApi({ persona_id: personaId.value, name });
    };

    // ─── 确保存在可编辑模板（无模板时添加任务使用）────────────
    const ensureEditableTemplate = async (name: string) => {
        if (currentTemplateId.value) return;
        if (!personaId.value) throw "人设信息异常，请刷新后重试";

        const res = await addTaskWorkTemplateApi({
            persona_id: personaId.value,
            name,
        });
        if (!res) throw "新建任务流失败，请重试";

        const newTemplateId = String(res?.id ?? res);
        await useTaskWorkTemplateApi({
            persona_id: personaId.value,
            template_id: Number(newTemplateId),
        });

        currentTemplateId.value = newTemplateId;
        currentTemplate.value = {
            id: newTemplateId,
            name,
            type: TemplateTypeEnum.EDITABLE,
            industry: IndustryEnum.CUSTOM,
            focus: FocusEnum.GENERAL,
            desc: "",
            schedule_count: 0,
            isSystem: false,
            tasks: [],
        };
        currentTasks.value = [];
    };

    // ─── 删除自定义模板 ────────────────────────────────────────
    const deleteTemplate = async (id: string) => {
        await deleteTaskWorkTemplateApi({
            persona_id: personaId.value,
            template_id: id,
        });
        // 若删除的是当前使用中的模板，清空当前模板状态，由页面决定后续跳转
        if (currentTemplateId.value === id) {
            currentTemplate.value = null;
            currentTemplateId.value = "";
            currentTasks.value = [];
        }
    };

    // ─── 添加任务节点 ──────────────────────────────────────────
    const addTask = async (task: { time: string; scene: TaskScene; platforms: string[] }) => {
        if (!currentTemplateId.value) throw "请先创建任务流";
        const config = taskConfig[task.scene];
        if (!config) throw "任务类型异常，请重新选择";
        const [start_time, end_time] = task.time.split("-");
        const newScheduleItem: SchedulePayload = {
            start_time,
            end_time,
            scene: task.scene,
            platform: buildPlatformPayload(task.scene, task.platforms),
        };
        isLoading.value = true;
        try {
            await updateTaskWorkTemplateNodeApi({
                persona_id: personaId.value,
                template_id: Number(currentTemplateId.value),
                schedule: [...buildSchedulePayload(currentTasks.value), newScheduleItem],
            });
            await fetchTemplateDetail();
        } finally {
            isLoading.value = false;
        }
    };

    // ─── 预览自定义模板时添加任务节点 ──────────────────────────
    const addPreviewTask = async (
        templateId: string,
        task: { time: string; scene: TaskScene; platforms: string[] },
    ) => {
        if (!templateId) throw "模板信息异常，请刷新后重试";
        const config = taskConfig[task.scene];
        if (!config) throw "任务类型异常，请重新选择";
        const [start_time, end_time] = task.time.split("-");
        const newScheduleItem: SchedulePayload = {
            start_time,
            end_time,
            scene: task.scene,
            platform: buildPlatformPayload(task.scene, task.platforms),
        };
        isLoading.value = true;
        try {
            await updateTaskWorkTemplateNodeApi({
                persona_id: personaId.value,
                template_id: Number(templateId),
                schedule: [...buildSchedulePayload(previewTasks.value), newScheduleItem],
            });
            await fetchTemplateById(templateId);
        } finally {
            isLoading.value = false;
        }
    };

    // ─── 预览自定义模板时更新任务节点 ──────────────────────────
    const updatePreviewTask = async (
        templateId: string,
        nodeId: number,
        task: { time: string; scene: TaskScene; platforms: string[] },
    ) => {
        if (!templateId) throw "模板信息异常，请刷新后重试";
        const config = taskConfig[task.scene];
        if (!config) throw "任务类型异常，请重新选择";
        const [start_time, end_time] = task.time.split("-");
        const updatedSchedule: SchedulePayload[] = previewTasks.value.map((t) => {
            if (t.id === nodeId) {
                return {
                    start_time,
                    end_time,
                    scene: task.scene,
                    status: t.status,
                    platform: buildPlatformPayload(task.scene, task.platforms),
                };
            }
            const [s, e] = t.time.split("-");
            return {
                start_time: s,
                end_time: e,
                scene: t.scene,
                status: t.status,
                platform: [...t.platformRaw].sort((a, b) => a.order - b.order),
            };
        });
        isLoading.value = true;
        try {
            await updateTaskWorkTemplateNodeApi({
                persona_id: personaId.value,
                template_id: Number(templateId),
                schedule: updatedSchedule,
            });
            await fetchTemplateById(templateId);
        } finally {
            isLoading.value = false;
        }
    };

    // ─── 更新任务节点 ──────────────────────────────────────────
    const updateTask = async (nodeId: number, task: { time: string; scene: TaskScene; platforms: string[] }) => {
        if (!currentTemplateId.value) return;
        const config = taskConfig[task.scene];
        if (!config) return;
        const [start_time, end_time] = task.time.split("-");
        const updatedSchedule: SchedulePayload[] = currentTasks.value.map((t) => {
            if (t.id === nodeId) {
                return {
                    start_time,
                    end_time,
                    scene: task.scene,
                    status: t.status,
                    platform: buildPlatformPayload(task.scene, task.platforms),
                };
            }
            const [s, e] = t.time.split("-");
            return {
                start_time: s,
                end_time: e,
                scene: t.scene,
                status: t.status,
                platform: [...t.platformRaw].sort((a, b) => a.order - b.order),
            };
        });
        isLoading.value = true;
        try {
            await updateTaskWorkTemplateNodeApi({
                persona_id: personaId.value,
                template_id: Number(currentTemplateId.value),
                schedule: updatedSchedule,
            });
            await fetchTemplateDetail();
        } finally {
            isLoading.value = false;
        }
    };

    // ─── 切换任务开关状态 ──────────────────────────────────────
    const toggleTaskStatus = async (nodeId: number, newStatus: number) => {
        if (!currentTemplateId.value) return;
        const task = currentTasks.value.find((t) => t.id === nodeId);
        if (!task) return;
        const prevStatus = task.status;
        task.status = newStatus;
        try {
            await updateTaskWorkTemplateNodeStatusApi({
                persona_id: personaId.value,
                template_id: Number(currentTemplateId.value),
                schedule_id: nodeId,
                status: newStatus,
            });
        } catch {
            task.status = prevStatus;
        }
    };

    // ─── 预览自定义模板时切换任务开关状态 ──────────────────────
    const togglePreviewTaskStatus = async (templateId: string, nodeId: number, newStatus: number) => {
        if (!templateId) return;
        const task = previewTasks.value.find((t) => t.id === nodeId);
        if (!task) return;
        const prevStatus = task.status;
        task.status = newStatus;
        try {
            await updateTaskWorkTemplateNodeStatusApi({
                persona_id: personaId.value,
                template_id: Number(templateId),
                schedule_id: nodeId,
                status: newStatus,
            });
        } catch {
            task.status = prevStatus;
        }
    };

    // ─── 删除任务节点 ──────────────────────────────────────────
    const deleteTask = async (id: number): Promise<void> => {
        if (!currentTemplateId.value) return;
        const remainingTasks = currentTasks.value.filter((t) => t.id !== id);
        currentTasks.value = remainingTasks;
        try {
            await updateTaskWorkTemplateNodeApi({
                persona_id: personaId.value,
                template_id: Number(currentTemplateId.value),
                schedule: buildSchedulePayload(remainingTasks),
            });
        } catch (error) {
            await fetchTemplateDetail();
            throw error;
        }
    };

    // ─── 预览自定义模板时删除任务节点 ──────────────────────────
    const deletePreviewTask = async (templateId: string, id: number): Promise<void> => {
        if (!templateId) return;
        const prevTasks = [...previewTasks.value];
        const remainingTasks = previewTasks.value.filter((t) => t.id !== id);
        previewTasks.value = remainingTasks;
        try {
            await updateTaskWorkTemplateNodeApi({
                persona_id: personaId.value,
                template_id: Number(templateId),
                schedule: buildSchedulePayload(remainingTasks),
            });
            await fetchTemplateById(templateId);
        } catch (error) {
            previewTasks.value = prevTasks;
            throw error;
        }
    };

    // ─── 重置模板 ──────────────────────────────────────────────
    const resetCurrentTemplate = async () => {
        if (!currentTemplateId.value) return;
        isLoading.value = true;
        try {
            await resetTaskWorkTemplateApi({
                persona_id: personaId.value,
                template_id: Number(currentTemplateId.value),
            });
            await fetchTemplateDetail();
        } finally {
            isLoading.value = false;
        }
    };

    // ─── 使用模板 ──────────────────────────────────────────────
    const useCurrentTemplate = async () => {
        if (!currentTemplateId.value) return;
        uni.showLoading({ title: "使用中...", mask: true });
        try {
            await useTaskWorkTemplateApi({
                persona_id: personaId.value,
                template_id: Number(currentTemplateId.value),
            });
            uni.hideLoading();
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({ title: error, icon: "none", duration: 3000 });
        }
    };

    // ─── 克隆模板 ──────────────────────────────────────────────
    const cloneTemplate = async (source: "current" | "preview" = "current"): Promise<void> => {
        const sourceTemplate = source === "preview" ? previewTemplate.value : currentTemplate.value;
        const sourceTasks = source === "preview" ? previewTasks.value : currentTasks.value;

        if (!sourceTemplate) return;

        const allowedPlatformsByScene = new Map<number, number[]>();
        for (const item of addableScenes.value) {
            if (Array.isArray(item.platforms)) {
                allowedPlatformsByScene.set(item.scene, item.platforms);
            }
        }
        const tasksSnapshot = buildSchedulePayload(sourceTasks, allowedPlatformsByScene);
        const cloneName = sourceTemplate.name;

        isLoading.value = true;
        try {
            const res = await addTaskWorkTemplateApi({
                persona_id: personaId.value,
                name: cloneName,
            });
            if (!res) throw "新建模板失败";
            const newId = String(res?.id ?? res);
            if (tasksSnapshot.length > 0) {
                await updateTaskWorkTemplateNodeApi({
                    persona_id: personaId.value,
                    template_id: Number(newId),
                    schedule: tasksSnapshot,
                    operation_preference: Number(sourceTemplate.focus),
                });
            }
            currentTemplateId.value = newId;
            await useCurrentTemplate();
            await fetchTemplateDetail();
        } finally {
            isLoading.value = false;
        }
    };

    return {
        currentTemplateId,
        currentTasks,
        isLoading,
        personaId,
        currentTemplate,
        isSystem,
        isReadOnly,
        previewTemplate,
        previewTasks,
        addableScenes,
        addableScenesLoaded,
        addableScenesFailed,
        selectableTaskConfigList,
        selectableTaskLabelList,
        init,
        fetchTemplateDetail,
        fetchTemplateById,
        fetchAddableScenes,
        getEditSelectableTaskConfigList,
        getTaskConfigByScene,
        clearPreview,
        switchTemplate,
        createTemplate,
        ensureEditableTemplate,
        deleteTemplate,
        addTask,
        addPreviewTask,
        updatePreviewTask,
        updateTask,
        toggleTaskStatus,
        togglePreviewTaskStatus,
        deleteTask,
        deletePreviewTask,
        resetCurrentTemplate,
        useCurrentTemplate,
        cloneTemplate,
    };
});
