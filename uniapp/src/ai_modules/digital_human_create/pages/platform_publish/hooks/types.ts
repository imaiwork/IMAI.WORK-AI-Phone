// ─── 类型 & 常量 ─────────────────────────────────────────────────────────────

export interface MaterialItem {
    url: string;
    pic: string;
    type: "image" | "video";
    name?: string;
    duration?: number;
    id?: number;
}

export interface CopywriterItem {
    title: string;
    content: string;
    topic: string[];
    poi: string;
}

export interface PublishFormData {
    name: string;
    materialList: MaterialItem[];
    copywriterList: CopywriterItem[];
}

export const STEPS = [
    { step: 1, title: "选择素材" },
    { step: 2, title: "填写文案" },
];

export const createDefaultFormData = (): PublishFormData => ({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "发布任务",
    materialList: [],
    copywriterList: [],
});
