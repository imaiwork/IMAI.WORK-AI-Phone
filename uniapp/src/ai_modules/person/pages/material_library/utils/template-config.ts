import {
    SynthTypeApiEnum,
    SynthTypeEnum,
    SYNTH_TYPE_SCENE,
    TemplateConfigItem,
    TemplateConfigMap,
    TemplateModeEnum,
} from "../enums";

const SYNTH_UI_TO_API: Record<string, number> = {
    [SynthTypeEnum.DigitalHuman]: SynthTypeApiEnum.DigitalHuman,
    [SynthTypeEnum.Material]: SynthTypeApiEnum.Material,
    [SynthTypeEnum.News]: SynthTypeApiEnum.News,
};

/** 全部生成类型（含当前未勾选），用于保留各类型模板缓存 */
export const ALL_SYNTH_API_TYPES: number[] = [
    SynthTypeApiEnum.DigitalHuman,
    SynthTypeApiEnum.Material,
    SynthTypeApiEnum.News,
];

const SYNTH_API_SHORT_LABEL: Record<number, string> = {
    [SynthTypeApiEnum.DigitalHuman]: "口播",
    [SynthTypeApiEnum.News]: "新闻体",
    [SynthTypeApiEnum.Material]: "混剪",
};

const SYNTH_API_FULL_LABEL: Record<number, string> = {
    [SynthTypeApiEnum.DigitalHuman]: "数字人口播",
    [SynthTypeApiEnum.News]: "新闻体",
    [SynthTypeApiEnum.Material]: "纯素材混剪",
};

export const synthUiToApi = (uiType: string): number | undefined => SYNTH_UI_TO_API[uiType];

export const getSynthApiShortLabel = (apiType: number): string => SYNTH_API_SHORT_LABEL[apiType] || String(apiType);

export const getSynthApiFullLabel = (apiType: number): string => SYNTH_API_FULL_LABEL[apiType] || String(apiType);

export const createDefaultTemplateItem = (apiType: number): TemplateConfigItem => ({
    mode: TemplateModeEnum.Auto,
    template_ids: [],
    scene: SYNTH_TYPE_SCENE[apiType] || "",
    selected_count: 0,
});

/** 规范化单条配置；自定义模式保证 template_ids / selected_count 一致 */
export const normalizeTemplateItem = (apiType: number, raw?: Partial<TemplateConfigItem> | null): TemplateConfigItem => {
    const base = createDefaultTemplateItem(apiType);
    if (!raw || typeof raw !== "object") return base;

    const mode = Number(raw.mode) === TemplateModeEnum.Custom ? TemplateModeEnum.Custom : TemplateModeEnum.Auto;
    const ids = Array.isArray(raw.template_ids)
        ? raw.template_ids.map((id) => String(id)).filter(Boolean)
        : [];

    if (mode === TemplateModeEnum.Auto) {
        return {
            ...base,
            mode: TemplateModeEnum.Auto,
            template_ids: [],
            selected_count: 0,
            // scene 始终按生成类型校正，避免脏数据串场景
            scene: base.scene,
        };
    }

    return {
        ...base,
        mode: TemplateModeEnum.Custom,
        template_ids: ids,
        selected_count: ids.length,
        scene: base.scene,
    };
};

/** 按指定生成类型组装 template_config（仅包含传入的类型） */
export const buildTemplateConfigForTypes = (
    apiTypes: number[],
    source: TemplateConfigMap = {},
): TemplateConfigMap => {
    const result: TemplateConfigMap = {};
    apiTypes.forEach((apiType) => {
        const key = String(apiType);
        result[key] = normalizeTemplateItem(apiType, source[key]);
    });
    return result;
};

export const buildTemplateSummary = (apiTypes: number[], config: TemplateConfigMap): string => {
    if (!apiTypes.length) return "请先选择生成类型";

    const parts = apiTypes.map((apiType) => {
        const item = normalizeTemplateItem(apiType, config[String(apiType)]);
        const name = getSynthApiShortLabel(apiType);
        if (item.mode === TemplateModeEnum.Custom) {
            return `${name} 已选${item.selected_count}个`;
        }
        return `${name} 自动随机`;
    });

    const allAuto = apiTypes.every((apiType) => {
        const item = normalizeTemplateItem(apiType, config[String(apiType)]);
        return item.mode === TemplateModeEnum.Auto;
    });

    if (allAuto && apiTypes.length > 1) return "全部类型 · 自动随机";
    return parts.join(" · ");
};

/** 自定义模式下是否存在未勾选模板的类型 */
export const findEmptyCustomType = (
    apiTypes: number[],
    config: TemplateConfigMap,
): number | null => {
    for (const apiType of apiTypes) {
        const item = normalizeTemplateItem(apiType, config[String(apiType)]);
        if (item.mode === TemplateModeEnum.Custom && item.template_ids.length === 0) {
            return apiType;
        }
    }
    return null;
};
