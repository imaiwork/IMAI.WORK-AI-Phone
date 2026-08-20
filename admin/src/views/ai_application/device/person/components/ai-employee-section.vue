<template>
    <el-card class="!border-none !rounded-2xl" shadow="never">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-1 h-5 rounded-sm bg-gradient-to-b from-[#3B71E8] to-[#6366F1]" />
                <span class="text-base font-semibold text-[#1e1e2e]">AI 员工团队</span>
                <span class="text-xs text-[#9ca3af] ml-2">轻点员工卡片配置执行细节</span>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="inline-flex items-center gap-1">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#22c55e]" />
                    <span class="font-bold text-[#1a1a1a]">{{ runningCount }}</span>
                    <span class="text-[#6b7280]">运行中</span>
                </span>
                <span class="text-[#e5e7eb]">·</span>
                <span class="inline-flex items-center gap-1">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#9ca3af]" />
                    <span class="font-bold text-[#1a1a1a]">{{ pausedCount }}</span>
                    <span class="text-[#6b7280]">已暂停</span>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div
                v-for="row in employeeRows"
                :key="row.key"
                class="flex flex-col rounded-2xl px-4 py-4 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(0,0,0,0.06)]"
                :style="{
                    background: employeeStatus[row.key] ? row.cardBg : '#F9FAFB',
                    border: `1px solid ${employeeStatus[row.key] ? row.cardBorder : '#E5E7EB'}`,
                    opacity: employeeStatus[row.key] ? 1 : 0.7,
                }"
                @click="handleOpenConfig(row.key)">
                <div class="flex items-start gap-3">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                        :style="{ background: employeeStatus[row.key] ? row.iconBg : '#F3F4F6' }">
                        {{ row.emoji }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-sm font-extrabold text-[#1a1a1a] truncate">{{ row.name }}</span>
                            <el-tag
                                :type="employeeStatus[row.key] ? 'success' : 'info'"
                                effect="light"
                                size="small"
                                round>
                                {{ employeeStatus[row.key] ? "运行中" : "已暂停" }}
                            </el-tag>
                        </div>
                        <div class="text-xs text-[#6b7280] line-clamp-2">{{ row.desc }}</div>
                    </div>
                    <div v-if="!row.hideStatusSwitch" class="flex-shrink-0" @click.stop>
                        <el-switch v-model="employeeStatus[row.key]" @change="handleStatusChange" />
                    </div>
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-dashed border-[#e5e7eb]">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="tag in row.tags"
                            :key="tag"
                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] text-[#3b71e8] bg-[#EBF1FF]">
                            {{ tag }}
                        </span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-[#3b71e8]">
                        配置
                        <el-icon><ArrowRight /></el-icon>
                    </span>
                </div>
            </div>
        </div>

        <trending-pop v-if="showTrendingPop" ref="trendingPopRef" @success="onSuccess" @close="showTrendingPop = false" />
        <editor-pop v-if="showEditorPop" ref="editorPopRef" @success="onSuccess" @close="showEditorPop = false" />
        <operator-pop v-if="showOperatorPop" ref="operatorPopRef" @success="onSuccess" @close="showOperatorPop = false" />
        <agent-pop v-if="showAgentPop" ref="agentPopRef" @success="onSuccess" @close="showAgentPop = false" />
        <traffic-pop v-if="showTrafficPop" ref="trafficPopRef" @success="onSuccess" @close="showTrafficPop = false" />
        <interaction-pop
            v-if="showInteractionPop"
            ref="interactionPopRef"
            @success="onSuccess"
            @close="showInteractionPop = false" />
    </el-card>
</template>

<script setup lang="ts">
import { computed, reactive, ref, nextTick, watch } from "vue";
import { ArrowRight } from "@element-plus/icons-vue";
import { updatePersonOption, getMaterialList } from "@/api/ai_application/device/person";
import feedback from "@/utils/feedback";
import TrendingPop from "./trending-pop.vue";
import EditorPop from "./editor-pop.vue";
import OperatorPop from "./operator-pop.vue";
import AgentPop from "./agent-pop.vue";
import TrafficPop from "./traffic-pop.vue";
import InteractionPop from "./interaction-pop.vue";

type EmployeeKey = "trending" | "editor" | "operator" | "cs" | "leads" | "private";

const props = defineProps<{ detail: Record<string, any> }>();
const emit = defineEmits(["refresh"]);

interface EmployeeRow {
    key: EmployeeKey;
    name: string;
    desc: string;
    emoji: string;
    iconBg: string;
    cardBg: string;
    cardBorder: string;
    tags: string[];
    /** 隐藏右上角运行状态开关（热点追踪 / 视频剪辑 / 内容发布默认常开，不暴露开关） */
    hideStatusSwitch?: boolean;
}

const employeeRows: EmployeeRow[] = [
    {
        key: "trending",
        name: "热点追踪",
        desc: "追踪全网爆款热点内容",
        emoji: "🔥",
        iconBg: "#FEE2E2",
        cardBg: "#FFF7F5",
        cardBorder: "#FECACA",
        tags: ["关键词", "AI 推荐"],
        hideStatusSwitch: true,
    },
    {
        key: "editor",
        name: "视频剪辑",
        desc: "自动生成短视频内容",
        emoji: "🎬",
        iconBg: "#DBEAFE",
        cardBg: "#F4F8FF",
        cardBorder: "#BFDBFE",
        tags: ["数字人口播", "新闻体混剪"],
        hideStatusSwitch: true,
    },
    {
        key: "operator",
        name: "内容发布",
        desc: "AI 自动生成标题与正文",
        emoji: "📢",
        iconBg: "#FEF3C7",
        cardBg: "#FFFBEB",
        cardBorder: "#FDE68A",
        tags: ["自动生成", "购物车"],
        hideStatusSwitch: true,
    },
    {
        key: "cs",
        name: "智能客服",
        desc: "24 小时自动回复私信评论",
        emoji: "💬",
        iconBg: "#E0E7FF",
        cardBg: "#F5F7FF",
        cardBorder: "#C7D2FE",
        tags: ["视频号", "小红书", "抖音", "快手"],
    },
    {
        key: "leads",
        name: "自动获客",
        desc: "主动触达潜在目标客户",
        emoji: "🎯",
        iconBg: "#D1FAE5",
        cardBg: "#F0FDF4",
        cardBorder: "#A7F3D0",
        tags: ["视频号获客", "截流设置"],
    },
    {
        key: "private",
        name: "私域运营",
        desc: "维护微信私域客户关系",
        emoji: "🧑‍🤝‍🧑",
        iconBg: "#F3E8FF",
        cardBg: "#FAF5FF",
        cardBorder: "#E9D5FF",
        tags: ["加好友", "自动加群", "朋友圈"],
    },
];

const employeeStatus = reactive<Record<EmployeeKey, boolean>>({
    trending: true,
    editor: true,
    operator: true,
    cs: true,
    leads: true,
    private: false,
});

const runningCount = computed(() => Object.values(employeeStatus).filter(Boolean).length);
const pausedCount = computed(() => Object.values(employeeStatus).filter((v) => !v).length);

// 与 uniapp 一致：global_option 顶层与 auto_clues/private_operation.status 控制开关
const GLOBAL_TOP_MAP = {
    trending: "hot_words",
    editor: "video_clip",
    operator: "content_publish",
    cs: "customer_service",
} as const;

const toBool = (v: any, fallback = false): boolean => {
    if (v === 1 || v === "1" || v === true) return true;
    if (v === 0 || v === "0" || v === false) return false;
    return fallback;
};

// 按人设类型取对应子对象：1 个人IP → individual，2 企业服务 → enterprise，3 本地商家 → local
const getPersonaSub = (): Record<string, any> => {
    const byType: Record<number, any> = {
        1: props.detail?.individual,
        2: props.detail?.enterprise,
        3: props.detail?.local,
    };
    return byType[props.detail?.persona_type] ?? props.detail?.individual ?? props.detail?.enterprise ?? props.detail?.local ?? {};
};

let hydrating = false;
const hydrateFromDetail = () => {
    const sub = getPersonaSub();
    const opt: Record<string, any> = sub.global_option ?? props.detail?.global_option ?? {};
    hydrating = true;
    try {
        (Object.keys(GLOBAL_TOP_MAP) as Array<keyof typeof GLOBAL_TOP_MAP>).forEach((uiKey) => {
            const apiKey = GLOBAL_TOP_MAP[uiKey];
            if (opt[apiKey] !== undefined) employeeStatus[uiKey] = toBool(opt[apiKey], employeeStatus[uiKey]);
        });
        if (opt?.auto_clues?.status !== undefined)
            employeeStatus.leads = toBool(opt.auto_clues.status, employeeStatus.leads);
        if (opt?.private_operation?.status !== undefined)
            employeeStatus.private = toBool(opt.private_operation.status, employeeStatus.private);
    } finally {
        nextTick(() => {
            hydrating = false;
        });
    }
};

watch(() => props.detail, hydrateFromDetail, { immediate: true, deep: true });

// 防抖一次性保存 global_option（开关变更 → 后端）
let saveTimer: ReturnType<typeof setTimeout> | null = null;
const handleStatusChange = () => {
    if (hydrating) return;
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(saveStatus, 300);
};

const buildGlobalOptionPayload = (): Record<string, any> => {
    const sub = getPersonaSub();
    const existing: Record<string, any> = sub.global_option ?? props.detail?.global_option ?? {};
    return {
        ...existing,
        hot_words: employeeStatus.trending ? 1 : 0,
        video_clip: employeeStatus.editor ? 1 : 0,
        content_publish: employeeStatus.operator ? 1 : 0,
        customer_service: employeeStatus.cs ? 1 : 0,
        auto_clues: {
            ...(existing.auto_clues || {}),
            status: employeeStatus.leads ? 1 : 0,
        },
        private_operation: {
            ...(existing.private_operation || {}),
            status: employeeStatus.private ? 1 : 0,
        },
    };
};

const saveStatus = async () => {
    if (!props.detail?.id) return;
    try {
        await updatePersonOption({
            id: props.detail.id,
            global_option: buildGlobalOptionPayload(),
        });
    } catch (error: any) {
        feedback.msgError(error?.message || "员工状态保存失败");
        // 失败时回滚到 detail 当前状态
        hydrateFromDetail();
    }
};

// 各员工卡片对应的弹窗
const showTrendingPop = ref(false);
const trendingPopRef = ref<InstanceType<typeof TrendingPop>>();
const showEditorPop = ref(false);
const editorPopRef = ref<InstanceType<typeof EditorPop>>();
const showOperatorPop = ref(false);
const operatorPopRef = ref<InstanceType<typeof OperatorPop>>();
const showAgentPop = ref(false);
const agentPopRef = ref<InstanceType<typeof AgentPop>>();
const showTrafficPop = ref(false);
const trafficPopRef = ref<InstanceType<typeof TrafficPop>>();
const showInteractionPop = ref(false);
const interactionPopRef = ref<InstanceType<typeof InteractionPop>>();

// 与 uniapp 一致：进入 AI 合成规则前查询素材库是否为空，用于「纯素材库」冲突提示
const queryMaterialEmpty = async (id: string | number): Promise<boolean> => {
    try {
        const { lists = [], count = 0 } = await getMaterialList({
            persona_id: id,
            page_no: 1,
            page_size: 1,
        });
        return Number(count || lists.length) === 0;
    } catch {
        return false;
    }
};

const handleOpenConfig = async (key: EmployeeKey) => {
    const personId = props.detail?.id;
    if (!personId) {
        feedback.msgWarning("请先保存人设基础信息");
        return;
    }
    if (key === "trending") {
        showTrendingPop.value = true;
        await nextTick();
        // 追踪词取人设子表；duration / publish_day 取详情最外层（对齐 uniapp）
        trendingPopRef.value?.open(personId, {
            hot_words: getPersonaSub().hot_words,
            duration: props.detail?.duration,
            publish_day: props.detail?.publish_day,
        });
        return;
    }
    if (key === "editor") {
        showEditorPop.value = true;
        await nextTick();
        const isMaterialEmpty = await queryMaterialEmpty(personId);
        editorPopRef.value?.open(personId, { isMaterialEmpty });
        return;
    }
    if (key === "operator") {
        showOperatorPop.value = true;
        await nextTick();
        operatorPopRef.value?.open(personId);
        return;
    }
    if (key === "cs") {
        showAgentPop.value = true;
        await nextTick();
        agentPopRef.value?.open(personId);
        return;
    }
    if (key === "leads") {
        showTrafficPop.value = true;
        await nextTick();
        trafficPopRef.value?.open(personId);
        return;
    }
    if (key === "private") {
        showInteractionPop.value = true;
        await nextTick();
        interactionPopRef.value?.open(personId, buildGlobalOptionPayload());
        return;
    }
};

const onSuccess = () => emit("refresh");
</script>

<style scoped>
:deep(.el-card__body) {
    padding: 24px 28px;
}
</style>
