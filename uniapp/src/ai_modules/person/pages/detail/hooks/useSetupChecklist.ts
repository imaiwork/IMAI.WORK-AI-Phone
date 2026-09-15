import { isCloudRunMode, RunModeApiEnum, SETUP_THRESHOLD, SetupStepEnum } from "@/ai_modules/person/enums";

/** setupProgress 接口返回：只给判定要用的计数和一点回显文案，不下发业务对象 */
export interface PersonaSetupSummary {
    /** 追踪词数量 */
    keyword_count: number;
    /** 剪辑原片库素材数量 */
    material_count: number;
    /** 可用于合成的数字人形象数量（会员到期冻结后为 0） */
    avatar_count: number;
    /** 可用于合成的音色数量（会员到期冻结后为 0） */
    voice_count: number;
    /** 绑进人设、用户没删过的形象数（含冻结），素材库 tab 的计数用它 */
    avatar_total?: number;
    /** 绑进人设、用户没删过的音色数（含冻结），素材库 tab 的计数用它 */
    voice_total?: number;
    /** 已绑定设备数量 */
    device_count: number;
    /** 已绑定平台账号数量 */
    account_count: number;
    /** 运行模式，见 RunModeApiEnum */
    run_mode: number;
    /** 发布时段是否已确认 */
    publish_plan_done: boolean | number;
    /** 是否已有自动生成任务记录（含排队、处理中及失败），不代表已有成功产出 */
    first_run_done?: boolean | number;
    /** 下一个发布时段开始的秒级时间戳，0 表示没有可用时段 */
    next_run_at?: number;
    /** 今日生成成功条数 */
    today_success?: number;
    /** 今日生成失败条数 */
    today_failed?: number;
    /** 在途的生成任务数 */
    generating?: number;
    /** 离线设备数 */
    device_offline_count?: number;
    /** 发布时段的 HH:MM 列表；无手机模式不下发 */
    publish_slots?: string[];
    /** 无手机模式每天出几条；手机模式为 0 */
    daily_video_count?: number;
    /** 成品直发库素材数 */
    direct_material_count?: number;
    /** 人设的发布方式：1 剪辑原片库 2 成品直发库 */
    publish_mode?: number;
    /** 成品直发库使用方式，见 ProductUseModeEnum */
    product_use_mode?: number;
    /** 成品直发库随机规则，见 ProductReuseModeEnum */
    product_reuse_mode?: number;
    /** 人设是否处于启用中；false = 被会员到期冻结，两条 24h 合成 cron 都不会捞它 */
    persona_enabled?: boolean;
    /** 以下为完成后折叠行的回显，缺省时降级成计数 */
    keyword_preview?: string[];
    default_avatar_name?: string;
    default_voice_name?: string;
    publish_plan_desc?: string;
}

/** 某些步骤本身就是一道选择题，选项卡即行动，不再额外压一个按钮 */
export interface SetupStepOption {
    value: number;
    title: string;
    desc: string;
    recommended?: boolean;
}

export interface SetupStep {
    key: SetupStepEnum;
    /** 步骤标题。展开态、折叠态、空态引导共用这一份，跨屏口径不许各写各的 */
    title: string;
    /** 折叠且未完成时的一句话说明 */
    brief: string;
    /** 展开态说明，必须同时讲清「为什么要做」和「做到什么程度算够」 */
    detail: string;
    /** 展开态行动按钮文案 */
    actionText: string;
    /** 完成后折叠行带出的实证，让用户看见自己的进展 */
    evidence: string;
    /** 有数量门槛的步骤：展开时显示「已有 N 个 · 还差 M 个」，别让用户自己数 */
    progressText: string;
    /** 仅追踪词步骤有：给新用户的示例词 */
    samples?: string[];
    /** 仅发布方式步骤有：两条路各自的代价，选哪条就去哪 */
    options?: SetupStepOption[];
    /** 有 options 的步骤当前落在哪一项，单选圈据此显示选中态 */
    currentValue?: number;
    done: boolean;
}

export interface SetupChecklistState {
    steps: SetupStep[];
    /** 已完成步数 */
    doneCount: number;
    /** 必须项总步数 */
    totalStep: number;
    /** 进度条百分比 */
    progressPercent: number;
    /** 当前要展开的那一步；全部完成时为空 */
    currentStepKey: SetupStepEnum | "";
    /** 卡片主标题，随剩余步数变化 */
    headline: string;
    /** 必须项是否已全部完成 */
    isSetupDone: boolean;
}

/** 步骤文案是数据不是逻辑，集中放这里，保证一处改处处改 */
const STEP_META: Record<
    SetupStepEnum,
    Pick<SetupStep, "title" | "brief" | "detail" | "actionText"> & {
        samples?: string[];
        options?: SetupStepOption[];
    }
> = {
    [SetupStepEnum.KEYWORD]: {
        title: "设置追踪词",
        brief: `填 ${SETUP_THRESHOLD.KEYWORD_MIN}-8 个，越具体越准`,
        detail: `手机模式下，AI 会按追踪词查找爆款参考。建议填 ${SETUP_THRESHOLD.KEYWORD_MIN}-8 个，越具体越准。`,
        actionText: "去设置追踪词",
        samples: ["敏感肌", "换季护肤", "屏障修复"],
    },
    [SetupStepEnum.MATERIAL]: {
        title: "上传素材",
        brief: `至少 ${SETUP_THRESHOLD.MATERIAL_MIN} 段视频或图片，AI 拿它剪成片`,
        detail: "用于 AI 剪辑，建议竖屏、每段 5–10 秒。",
        actionText: "去上传素材",
    },
    [SetupStepEnum.AVATAR_VOICE]: {
        title: "选形象和音色",
        brief: "决定成片里谁出镜、用什么声音说话",
        detail: "添加出镜形象和配音音色，也可为每个数字人单独绑定声音。",
        actionText: "去选形象和音色",
    },
    [SetupStepEnum.PUBLISH_MODE]: {
        title: "设置发布方式",
        brief: "绑一台 AI 手机自动发，或云端出片自己发",
        detail: "这决定成片做好之后怎么发出去。绑 AI 手机能全程不用管，无手机模式则由你自己下载后手动发。选错了随时能改。",
        actionText: "去设置发布方式",
        options: [
            {
                value: RunModeApiEnum.PHONE,
                title: "绑一台 AI 手机",
                desc: "成片自动发到绑定账号，全程不用管。需要一台常开、联网的安卓手机。",
                recommended: true,
            },
            {
                value: RunModeApiEnum.CLOUD,
                title: "无手机模式",
                desc: "云端出片，你自己下载后手动发。不占设备，但每条都要动手，而且不会自动去找爆款选题。",
            },
        ],
    },
    [SetupStepEnum.PUBLISH_PLAN]: {
        title: "确认发布时段",
        brief: "每天几点发、发几条，发到哪个账号",
        detail: "在任务流中确认发布时间、条数及平台账号。",
        actionText: "去确认发布时段",
    },
};

/** 成品直发的人设不做 AI 剪辑，第 2 步要的是成品而不是剪辑素材 */
const DIRECT_MATERIAL_META: Pick<SetupStep, "title" | "brief" | "detail" | "actionText"> = {
    title: "添加成品",
    brief: "已剪好的视频或图片，直接发布不再加工",
    detail: `成品直发不做 AI 剪辑，直接发布你上传的成品。每条只能用一次，至少先放 ${SETUP_THRESHOLD.DIRECT_MATERIAL_MIN} 条。`,
    actionText: "去添加成品",
};

/**
 * 成品直发没有「无手机模式」这条路：成品是用户自己传的，云端没有可合成的东西，
 * 发布只能交给绑定的 AI 手机（发布建单 cron 也只捞手机模式）。
 * 清单里再把这个选项摆出来，用户选完服务端会归一回手机模式，界面却报「已切到无手机模式」。
 */
const DIRECT_PUBLISH_MODE_META: Pick<SetupStep, "detail" | "options"> = {
    detail: "成品直发靠绑定的 AI 手机把你上传的成品发出去，不走云端合成，所以没有无手机模式可选。",
    options: [
        {
            value: RunModeApiEnum.PHONE,
            title: "绑一台 AI 手机",
            desc: "成品自动发到绑定账号，全程不用管。需要一台常开、联网的安卓手机。",
            recommended: true,
        },
    ],
};

/** 无手机模式不按发布时段出片，而是按每日条数，这一步问的是另一件事 */
const CLOUD_PUBLISH_PLAN_META: Pick<SetupStep, "title" | "brief" | "detail" | "actionText"> = {
    title: "确认每日出片条数",
    brief: "云端每天做几条，不看发布时段",
    detail: "无手机模式不按时段发布，而是每天在云端做固定条数的成片，你自己下载后发。没设过就按每天 1 条来。",
    actionText: "去确认条数",
};

const STEP_ORDER: SetupStepEnum[] = [
    SetupStepEnum.KEYWORD,
    SetupStepEnum.MATERIAL,
    SetupStepEnum.AVATAR_VOICE,
    SetupStepEnum.PUBLISH_MODE,
    SetupStepEnum.PUBLISH_PLAN,
];

const pad = (value: number): string => String(value).padStart(2, "0");

/** 把 next_run_at 渲染成「今天 09:00」；传 0 返回空串，调用方据此不展示 */
export const formatNextRun = (timestamp: number): string => {
    if (!timestamp) return "";
    const target = new Date(timestamp * 1000);
    const now = new Date();
    const isToday =
        target.getFullYear() === now.getFullYear() &&
        target.getMonth() === now.getMonth() &&
        target.getDate() === now.getDate();
    return `${isToday ? "今天" : "明天"} ${pad(target.getHours())}:${pad(target.getMinutes())}`;
};

const toCount = (value: unknown): number => Number(value) || 0;

/** 拼实证文案，自动丢掉空片段，避免出现「8 段 · 」这种尾巴 */
const joinEvidence = (...parts: (string | undefined | false)[]): string =>
    parts.filter((item): item is string => Boolean(item)).join(" · ");

export const useSetupChecklist = (summary: Ref<PersonaSetupSummary | null | undefined>) => {
    const steps = computed<SetupStep[]>(() => {
        const data = summary.value;
        const keywordCount = toCount(data?.keyword_count);
        // 成品直发的人设看成品库，剪辑合成的看剪辑原片库
        const isDirectSend = toCount(data?.publish_mode) === 2;
        const materialCount = isDirectSend
            ? toCount(data?.direct_material_count)
            : toCount(data?.material_count);
        const materialMin = isDirectSend
            ? SETUP_THRESHOLD.DIRECT_MATERIAL_MIN
            : SETUP_THRESHOLD.MATERIAL_MIN;
        const avatarCount = toCount(data?.avatar_count);
        const voiceCount = toCount(data?.voice_count);
        const deviceCount = toCount(data?.device_count);
        const accountCount = toCount(data?.account_count);
        const isCloud = isCloudRunMode(data?.run_mode);

        const doneMap: Record<SetupStepEnum, boolean> = {
            [SetupStepEnum.KEYWORD]: keywordCount >= SETUP_THRESHOLD.KEYWORD_MIN,
            [SetupStepEnum.MATERIAL]: materialCount >= materialMin,
            [SetupStepEnum.AVATAR_VOICE]: avatarCount > 0 && voiceCount > 0,
            // 无手机模式不需要绑设备，这一步靠模式本身达成
            [SetupStepEnum.PUBLISH_MODE]: isCloud || deviceCount > 0,
            [SetupStepEnum.PUBLISH_PLAN]: Boolean(data?.publish_plan_done),
        };

        const evidenceMap: Record<SetupStepEnum, string> = {
            [SetupStepEnum.KEYWORD]: joinEvidence(
                `${keywordCount} 个`,
                (data?.keyword_preview ?? []).slice(0, 2).join("、"),
            ),
            [SetupStepEnum.MATERIAL]: `${materialCount} ${isDirectSend ? "条" : "段"}`,
            [SetupStepEnum.AVATAR_VOICE]:
                joinEvidence(data?.default_avatar_name, data?.default_voice_name) ||
                `形象 ${avatarCount} 个 · 音色 ${voiceCount} 个`,
            [SetupStepEnum.PUBLISH_MODE]: isCloud
                ? "无手机模式 · 云端出片"
                : `手机模式 · ${deviceCount} 台设备`,
            [SetupStepEnum.PUBLISH_PLAN]:
                data?.publish_plan_desc ||
                (isCloud
                    ? `每天 ${toCount(data?.daily_video_count) || 1} 条`
                    : joinEvidence("已设置", accountCount ? `${accountCount} 个账号` : "")),
        };

        // 只有带数量门槛的两步需要进度提示，其余是「有/没有」的开关
        const gap = (count: number, min: number, unit: string): string =>
            count >= min ? "" : `已有 ${count} ${unit} · 还差 ${min - count} ${unit}`;
        const progressMap: Partial<Record<SetupStepEnum, string>> = {
            [SetupStepEnum.KEYWORD]: gap(keywordCount, SETUP_THRESHOLD.KEYWORD_MIN, "个"),
            [SetupStepEnum.MATERIAL]: gap(materialCount, materialMin, isDirectSend ? "条" : "段"),
        };

        return STEP_ORDER.map((key) => ({
            key,
            ...(key === SetupStepEnum.PUBLISH_PLAN && isCloud
                ? CLOUD_PUBLISH_PLAN_META
                : key === SetupStepEnum.MATERIAL && isDirectSend
                  ? DIRECT_MATERIAL_META
                  : STEP_META[key]),
            ...(key === SetupStepEnum.KEYWORD && isCloud
                ? { detail: `设置 ${SETUP_THRESHOLD.KEYWORD_MIN}-8 个追踪词。无手机模式不自动采集爆款，可在爆款库手动导入参考链接。` }
                : {}),
            ...(key === SetupStepEnum.PUBLISH_MODE && isDirectSend ? DIRECT_PUBLISH_MODE_META : {}),
            // 单选圈原来是个恒空的静态圆点，选完没有任何视觉变化，用户只能靠标题那行反推
            ...(key === SetupStepEnum.PUBLISH_MODE
                ? { currentValue: isCloud ? RunModeApiEnum.CLOUD : RunModeApiEnum.PHONE }
                : {}),
            done: doneMap[key],
            evidence: evidenceMap[key],
            // 门槛与单位在上面已按发布方式算好，这里不能再写死成 5 段，
            // 否则成品直发的人设会显示「0 / 5 段」，与步骤里说的「至少 1 条」自相矛盾
            progressText: key === SetupStepEnum.MATERIAL && !doneMap[key]
                ? `${materialCount} / ${materialMin} ${isDirectSend ? "条" : "段"}`
                : progressMap[key] ?? "",
        }));
    });

    const totalStep = computed(() => steps.value.length);

    const doneCount = computed(() => steps.value.filter((step) => step.done).length);

    const progressPercent = computed(() =>
        totalStep.value ? Math.round((doneCount.value / totalStep.value) * 100) : 0,
    );

    const isSetupDone = computed(() => Boolean(totalStep.value) && doneCount.value === totalStep.value);

    // 只展开第一个未完成的步骤，一次只问用户一件事
    const currentStepKey = computed<SetupStepEnum | "">(
        () => steps.value.find((step) => !step.done)?.key ?? "",
    );

    const headline = computed(() => {
        const remain = totalStep.value - doneCount.value;
        if (!remain) return "全部就绪，今晚就会自动开始";
        if (!doneCount.value) return `再完成 ${remain} 步，就能 24 小时自动产内容`;
        return `还差 ${remain} 步，今晚就能开始跑`;
    });

    const checklistState = computed<SetupChecklistState>(() => ({
        steps: steps.value,
        doneCount: doneCount.value,
        totalStep: totalStep.value,
        progressPercent: progressPercent.value,
        currentStepKey: currentStepKey.value,
        headline: headline.value,
        isSetupDone: isSetupDone.value,
    }));

    // 必须项齐了但尚无自动生成任务记录：运行面板显示首次等待状态。
    const isFirstRunPending = computed(
        () => isSetupDone.value && !summary.value?.first_run_done,
    );

    const nextRunAt = computed(() => Number(summary.value?.next_run_at) || 0);

    return {
        checklistState,
        currentStepKey,
        doneCount,
        isFirstRunPending,
        isSetupDone,
        nextRunAt,
        progressPercent,
        steps,
        totalStep,
    };
};
