import { ref, watch, type Ref } from "vue";
import { HomeModalType } from "../enums";
import { getAutoPipeline } from "@/api/app";
import config from "@/config";

// ============ AI 自动干活流水线 ↔ display/autoPipeline ============
// 后端按 group/item 业务标识返回，前端做图标 + tag 色 + modalType 本地映射
export type TagStyle = "orange" | "purple" | "blue" | "green" | "pink" | "gray";

// 接口原始结构
export type ApiPipelineItem = {
    key: string;
    name: string;
    tag: string;
    // 新格式：{ 时段: 状态文本 }（如 { "08:30-09:00": "已完成" }），无时段为 []；兼容旧的 string[]
    time_text: Record<string, string> | string[];
    time_slots: { start_time: string; end_time: string }[];
    status: number;
    status_text: string;
};
export type ApiPipelineGroup = {
    key: string;
    title: string;
    subtitle: string;
    items: ApiPipelineItem[];
};

// 前端归一化后的模板用结构
// 每个时段携带自身状态色（同一任务下不同时段状态可能不同）
export type TaskTimeText = { text: string; statusText: string; chipClass: string };
export type Task = {
    key: string;
    title: string;
    tag: string;
    tagClass: string;
    timeTexts: TaskTimeText[];
    status: number;
    statusText: string;
    statusClass: string;
    modalType?: HomeModalType | "";
};
export type Workflow = {
    id: string;
    icon: string;
    title: string;
    subtitle: string;
    taskCount: number;
    expandable: boolean;
    modalType?: HomeModalType | "";
    tasks?: Task[];
};

// group 业务标识 → 图标 + 整面板点击的弹窗类型（仅无 item 的面板用 modalType，如全网自动发布）
const GROUP_TYPE_MAP: Record<string, { icon: string; modalType?: HomeModalType }> = {
    content: { icon: config.baseUrl + "static/images/mp/workflow_layers.svg" },
    auto_publish: {
        icon: config.baseUrl + "static/images/mp/workflow_send.svg",
        modalType: HomeModalType.PUBLISH,
    },
    publish: {
        icon: config.baseUrl + "static/images/mp/workflow_send.svg",
        modalType: HomeModalType.PUBLISH,
    },
    peer_customer: { icon: config.baseUrl + "static/images/mp/workflow_crosshair.svg" },
    chat_wechat: { icon: config.baseUrl + "static/images/mp/workflow_message.svg" },
};
const DEFAULT_GROUP_ICON = config.baseUrl + "static/images/mp/workflow_layers.svg";

// item 业务标识 (key) → 弹窗类型
const ITEM_KEY_TO_MODAL: Record<string, HomeModalType> = {
    viral_topic: HomeModalType.HOT,
    ai_content: HomeModalType.CREATE,
    peer_all_network: HomeModalType.RIVAL_INTERNET,
    nearby_customer: HomeModalType.RIVAL_LOCAL,
    peer_store_nearby: HomeModalType.STORE_TARGET,
    business_invite: HomeModalType.WX_VIDEO_LEAD,
    social_private_reply: HomeModalType.CHAT_SOCIAL,
    social_comment_reply: HomeModalType.CHAT_COMMENT,
    wechat_customer: HomeModalType.CHAT_WECHAT,
    wechat_circle: HomeModalType.CHAT_MOMENTS,
};

// item 业务标识 (key) → tag 配色（接口不返回 tag_style，前端按业务固定）
const ITEM_TAG_STYLE: Record<string, TagStyle> = {
    viral_topic: "orange",
    ai_content: "purple",
    peer_all_network: "blue",
    nearby_customer: "purple",
    peer_store_nearby: "orange",
    business_invite: "green",
    social_private_reply: "blue",
    social_comment_reply: "purple",
    wechat_customer: "green",
    wechat_circle: "pink",
};

const TAG_STYLE_CLASS: Record<TagStyle, string> = {
    orange: "text-warning bg-warning-light-9",
    purple: "text-[#9333ea] bg-[#faf5ff]",
    blue: "text-primary bg-primary-light-9",
    green: "text-success bg-success-light-9",
    pink: "text-[#db2777] bg-[#fdf2f8]",
    gray: "text-[#6b7280] bg-[#f3f4f6]",
};

// status: 0 待执行 / 1 执行中 / 2 已完成 / 3 失败
const STATUS_BADGE_CLASS: Record<number, string> = {
    0: "text-[#9ca3af] bg-[#f9fafb]",
    1: "text-primary bg-primary-light-9",
    2: "text-success bg-success-light-9",
    3: "text-error bg-error-light-9",
};
const STATUS_CHIP_CLASS: Record<number, string> = {
    0: "text-[#9ca3af] bg-[#f3f4f6]",
    1: "text-primary bg-primary-light-9",
    2: "text-success bg-success-light-9",
    3: "text-error bg-error-light-9",
};

// 时段状态文本 → status 码（接口在 time_text 里只给状态文本，需反查着色）
const STATUS_TEXT_TO_CODE: Record<string, number> = {
    待执行: 0,
    执行中: 1,
    已完成: 2,
    失败: 3,
};

// 时段文案归一化：新格式 { 时段: 状态文本 } 按各自状态着色；旧格式 string[] 用任务整体状态色兜底
const normalizeTimeTexts = (
    raw: ApiPipelineItem["time_text"],
    fallbackChip: string,
): TaskTimeText[] => {
    if (Array.isArray(raw)) {
        return raw.map((text) => ({ text: String(text), statusText: "", chipClass: fallbackChip }));
    }
    if (raw && typeof raw === "object") {
        return Object.entries(raw).map(([text, statusText]) => {
            const code = STATUS_TEXT_TO_CODE[String(statusText)];
            return {
                text,
                statusText: String(statusText ?? ""),
                chipClass: STATUS_CHIP_CLASS[code] || fallbackChip,
            };
        });
    }
    return [];
};

const normalizeItem = (raw: ApiPipelineItem): Task => {
    const status = Number(raw.status) || 0;
    const tagStyle = ITEM_TAG_STYLE[raw.key] || "blue";
    const fallbackChip = STATUS_CHIP_CLASS[status] || STATUS_CHIP_CLASS[0];
    return {
        key: raw.key,
        title: raw.name,
        tag: raw.tag,
        tagClass: TAG_STYLE_CLASS[tagStyle],
        timeTexts: normalizeTimeTexts(raw.time_text, fallbackChip),
        status,
        statusText: raw.status_text,
        statusClass: STATUS_BADGE_CLASS[status] || STATUS_BADGE_CLASS[0],
        modalType: ITEM_KEY_TO_MODAL[raw.key] || "",
    };
};

const normalizeGroup = (raw: ApiPipelineGroup): Workflow => {
    const meta = GROUP_TYPE_MAP[raw.key] || { icon: DEFAULT_GROUP_ICON };
    const items = Array.isArray(raw.items) ? raw.items : [];
    return {
        id: raw.key,
        icon: meta.icon,
        title: raw.title,
        subtitle: raw.subtitle,
        taskCount: items.length,
        expandable: items.length > 0,
        modalType: meta.modalType,
        tasks: items.map(normalizeItem),
    };
};

export interface UseWorkflowPanelsDeps {
    /** 当前激活人设 key（变化时重拉，由 usePersonas 提供） */
    activePersona: Ref<string>;
    /** 当前激活人设的真实后端 id（演示/未登录为空，空则不发请求） */
    personaId: Ref<string | number>;
}

// 首页 AI 工作流面板：display/autoPipeline → 归一化 → 模板用 Workflow[]
export function useWorkflowPanels(deps: UseWorkflowPanelsDeps) {
    const { activePersona, personaId } = deps;

    const workflowPanels = ref<Workflow[]>([]);

    const refreshWorkflowPanels = async (): Promise<void> => {
        if (!personaId.value) {
            workflowPanels.value = [];
            return;
        }
        try {
            const data: any = await getAutoPipeline({ persona_id: personaId.value });
            const groups: ApiPipelineGroup[] = Array.isArray(data?.groups) ? data.groups : [];
            workflowPanels.value = groups.map(normalizeGroup);
        } catch (error) {
            workflowPanels.value = [];
            console.warn("getAutoPipeline failed", error);
        }
    };

    // 人设切换自动重拉面板
    watch(activePersona, refreshWorkflowPanels);

    return {
        workflowPanels,
        refreshWorkflowPanels,
    };
}
