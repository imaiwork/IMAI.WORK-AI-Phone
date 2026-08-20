/** 视频模板使用方式：1=自动随机，2=自定义多选 */
export enum TemplateModeEnum {
    Auto = 1,
    Custom = 2,
}

/** 生成类型 API 数字（与合成规则 generation_types 一致） */
export enum SynthTypeApiEnum {
    DigitalHuman = 1,
    Material = 3,
    News = 4,
}

/** 闪剪模板 scene：按生成类型映射 */
export const SYNTH_TYPE_SCENE: Record<number, string> = {
    [SynthTypeApiEnum.DigitalHuman]: "virtualman",
    [SynthTypeApiEnum.Material]: "oralMixCutting",
    [SynthTypeApiEnum.News]: "newsMixCutting",
};

export interface TemplateConfigItem {
    mode: TemplateModeEnum;
    template_ids: string[];
    scene: string;
    selected_count: number;
}

export type TemplateConfigMap = Record<string, TemplateConfigItem>;
