<template>
    <popup
        ref="popupRef"
        title="关联智能体"
        :async="true"
        width="760px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="py-2" v-loading="loading">
            <div class="flex flex-col gap-4">
                <!-- 社媒平台接管：评论 + 私信共用，按平台独立配置 -->
                <div class="rounded-2xl p-4 shadow-sm" style="background: #ffffff; border: 1px solid #f3f4f6">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                            style="background: #fff0f0">
                            <Icon name="el-icon-VideoPlay" color="#FF4D4F" />
                        </div>
                        <div class="flex flex-col justify-center">
                            <span class="text-sm font-extrabold" style="color: #1a1a1a">社媒平台接管</span>
                            <span class="text-xs mt-0.5" style="color: #999999">小红书 / 抖音 / 快手（评论 + 私信）</span>
                        </div>
                    </div>
                    <div class="rounded-xl px-3 py-2 mb-3 flex items-start gap-2" style="background: #f0f5ff">
                        <el-icon style="color: #3b71e8; margin-top: 2px; flex-shrink: 0"><InfoFilled /></el-icon>
                        <span class="text-xs leading-relaxed" style="color: #3b71e8">
                            为防止重复执行，RPA将通过<strong>「点赞」</strong>动作标记已处理的评论，以下所有互动均默认附带点赞。
                        </span>
                    </div>
                    <!-- 平台 tab：每个平台独立持有 type / agent / speech / comment_only_like -->
                    <div class="flex rounded-2xl p-1 gap-1 mb-4" style="background: #f5f7fa">
                        <div
                            v-for="plat in socialPlatforms"
                            :key="plat.key"
                            class="flex-1 flex items-center justify-center gap-1 py-2 rounded-xl cursor-pointer transition-all"
                            :style="
                                activeSocialPlatform === plat.key
                                    ? 'background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.08)'
                                    : ''
                            "
                            @click="activeSocialPlatform = plat.key">
                            <span
                                class="text-sm font-semibold"
                                :style="activeSocialPlatform === plat.key ? 'color:#3B71E8' : 'color:#9CA3AF'">
                                {{ plat.label }}
                            </span>
                            <span
                                v-if="
                                    socialPlatformConfigs[plat.key].dmAgentId ||
                                    socialPlatformConfigs[plat.key].commentAgentId ||
                                    socialPlatformConfigs[plat.key].dmFixedScripts.length ||
                                    socialPlatformConfigs[plat.key].commentFixedScripts.length
                                "
                                class="inline-block w-1.5 h-1.5 rounded-full"
                                style="background: #3b71e8"></span>
                        </div>
                    </div>

                    <ReplyModeSelector
                        :reply-mode="currentSocialPlatform.replyMode"
                        :dual-agent="true"
                        :dm-agent-id="currentSocialPlatform.dmAgentId"
                        :dm-agent-name="currentSocialPlatform.dmAgentName"
                        :comment-agent-id="currentSocialPlatform.commentAgentId"
                        :comment-agent-name="currentSocialPlatform.commentAgentName"
                        :dm-fixed-scripts="currentSocialPlatform.dmFixedScripts"
                        :comment-fixed-scripts="currentSocialPlatform.commentFixedScripts"
                        :dm-script-input="currentSocialPlatform.dmScriptInput"
                        :comment-script-input="currentSocialPlatform.commentScriptInput"
                        :dm-script-error="currentSocialPlatform.dmScriptError"
                        :comment-script-error="currentSocialPlatform.commentScriptError"
                        :dm-is-collapsed="getCollapsed(`social_${activeSocialPlatform}_dm`)"
                        :comment-is-collapsed="getCollapsed(`social_${activeSocialPlatform}_comment`)"
                        :agent-options="agentOptions"
                        :show-like-only="false"
                        :show-ai="true"
                        :source-key="`social_${activeSocialPlatform}`"
                        :is-collapsed="getCollapsed(`social_${activeSocialPlatform}`)"
                        @update:reply-mode="currentSocialPlatform.replyMode = $event"
                        @update:dm-agent-id="currentSocialPlatform.dmAgentId = $event"
                        @update:dm-agent-name="currentSocialPlatform.dmAgentName = $event"
                        @update:comment-agent-id="currentSocialPlatform.commentAgentId = $event"
                        @update:comment-agent-name="currentSocialPlatform.commentAgentName = $event"
                        @update:dm-fixed-scripts="currentSocialPlatform.dmFixedScripts = $event"
                        @update:comment-fixed-scripts="currentSocialPlatform.commentFixedScripts = $event"
                        @update:dm-script-input="currentSocialPlatform.dmScriptInput = $event"
                        @update:comment-script-input="currentSocialPlatform.commentScriptInput = $event"
                        @toggle-dm-collapse="toggleCollapse(`social_${activeSocialPlatform}_dm`)"
                        @expand-dm-collapse="expandCollapse(`social_${activeSocialPlatform}_dm`)"
                        @clear-dm-script-error="currentSocialPlatform.dmScriptError = false"
                        @toggle-comment-collapse="toggleCollapse(`social_${activeSocialPlatform}_comment`)"
                        @expand-comment-collapse="expandCollapse(`social_${activeSocialPlatform}_comment`)"
                        @clear-comment-script-error="currentSocialPlatform.commentScriptError = false" />

                    <div
                        class="mt-3 flex items-center justify-between rounded-xl px-3 py-3"
                        style="background: #f8fafc; border: 1px solid #eef2f7">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold" style="color: #1a1a1a">评论区仅点赞</span>
                            <span class="text-xs mt-0.5" style="color: #9ca3af">
                                开启后当前平台评论区只点赞不回复，私信仍可正常回复
                            </span>
                        </div>
                        <el-switch v-model="currentSocialPlatform.commentLikeOnly" />
                    </div>
                </div>

                <!-- 其他配置项 -->
                <div
                    v-for="item in configList"
                    :key="item.id"
                    class="rounded-2xl p-4 shadow-sm"
                    style="background: #ffffff; border: 1px solid #f3f4f6">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                            :style="{ background: item.iconBg }">
                            <Icon :name="item.icon" :color="item.iconColor" />
                        </div>
                        <div class="flex flex-col justify-center">
                            <span class="text-sm font-extrabold" style="color: #1a1a1a">{{ item.title }}</span>
                            <span class="text-xs mt-0.5" style="color: #999999">{{ item.desc }}</span>
                        </div>
                    </div>

                    <!-- 朋友圈执行动作切换 -->
                    <div v-if="item.id === 'moments_interact'" class="mb-4">
                        <span class="text-xs font-semibold mb-2 block" style="color: #374151">执行动作</span>
                        <div class="flex rounded-2xl p-1 gap-1" style="background: #f5f7fa">
                            <div
                                v-for="tab in momentsTabs"
                                :key="tab.value"
                                class="flex-1 flex items-center justify-center py-2 rounded-xl cursor-pointer transition-all"
                                :style="
                                    item.momentsAction === tab.value
                                        ? 'background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.08)'
                                        : ''
                                "
                                @click="item.momentsAction = tab.value">
                                <span
                                    class="text-sm font-semibold"
                                    :style="item.momentsAction === tab.value ? 'color:#3B71E8' : 'color:#9CA3AF'">
                                    {{ tab.label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <template
                        v-if="!(item.id === 'moments_interact' && item.momentsAction === MomentsActionEnum.LIKE_ONLY)">
                        <ReplyModeSelector
                            :reply-mode="item.replyMode"
                            :agent-id="item.agentId"
                            :agent-name="item.agentName"
                            :fixed-scripts="item.fixedScripts"
                            :script-input="item.scriptInput"
                            :script-error="item.scriptError"
                            :agent-options="agentOptions"
                            :show-like-only="false"
                            :show-ai="!isShutoffItem(item.id)"
                            :source-key="item.id"
                            :is-collapsed="getCollapsed(item.id)"
                            @update:reply-mode="item.replyMode = $event"
                            @update:agent-id="item.agentId = $event"
                            @update:agent-name="item.agentName = $event"
                            @update:fixed-scripts="item.fixedScripts = $event"
                            @update:script-input="item.scriptInput = $event"
                            @toggle-collapse="toggleCollapse(item.id)"
                            @expand-collapse="expandCollapse(item.id)"
                            @clear-script-error="item.scriptError = false" />
                    </template>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { ref, computed, shallowRef, h, defineComponent } from "vue";
import {
    InfoFilled,
    Cpu,
    List,
    Star,
    Plus,
    Close,
    ArrowDown,
    ArrowUp,
    CircleCloseFilled,
} from "@element-plus/icons-vue";
import { ElSelect, ElOption, ElInput, ElButton, ElIcon } from "element-plus";
import { getPersonAgent, updateCustomerServiceConfig } from "@/api/ai_application/device/person";
import { getAgentLists } from "@/api/ai_application/agent";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";

// ─── 枚举 ─────────────────────────────────────────────────────────
enum CommentReplyModeEnum {
    AI = 1,
    FIXED = 2,
    LIKE_REPLY = 3,
}

enum MomentsActionEnum {
    LIKE_ONLY = 1,
    COMMENT_ONLY = 2,
    BOTH = 3,
}

// 社媒平台 ↔ 后端 platform_type：3小红书 4抖音 5快手（视频号已下线）
type SocialPlatformKey = "xhs" | "dy" | "ks";
interface SocialPlatformMeta {
    key: SocialPlatformKey;
    label: string;
    type: number;
}
const socialPlatforms: SocialPlatformMeta[] = [
    { key: "xhs", label: "小红书", type: 3 },
    { key: "dy", label: "抖音", type: 4 },
    { key: "ks", label: "快手", type: 5 },
];
const SOCIAL_TYPE_TO_KEY: Record<number, SocialPlatformKey> = socialPlatforms.reduce(
    (acc, item) => {
        acc[item.type] = item.key;
        return acc;
    },
    {} as Record<number, SocialPlatformKey>,
);

const momentsTabs = [
    { label: "仅点赞", value: MomentsActionEnum.LIKE_ONLY },
    { label: "仅评论", value: MomentsActionEnum.COMMENT_ONLY },
    { label: "评论+点赞", value: MomentsActionEnum.BOTH },
];

// ─── 截流模块判断 ──────────────────────────────────────────────────
const SHUTOFF_IDS = new Set(["shutoff_comment", "shutoff_msg"]);
const isShutoffItem = (id: string): boolean => SHUTOFF_IDS.has(id);

// ─── 类型定义 ─────────────────────────────────────────────────────
/** 与小程序一致：ok 可用 / unavailable 跨团队不可用 / deleted 已删除 */
type AgentBindStatus = "ok" | "unavailable" | "deleted";

interface AgentOption {
    id: string;
    name: string;
    status?: AgentBindStatus;
    statusText?: string;
}

const normalizeAgentStatus = (raw: any): AgentBindStatus => {
    const s = String(raw || "ok");
    if (s === "deleted" || s === "unavailable") return s;
    return "ok";
};

const formatAgentOptionLabel = (agent: AgentOption): string => {
    const n = String(agent.name || "").trim() || `智能体#${agent.id}`;
    if (agent.status === "deleted") return `${n}（已被删除）`;
    if (agent.status === "unavailable") return `${n}（不可用）`;
    return n;
};

interface SocialPlatformConfig {
    replyMode: CommentReplyModeEnum;
    // 私信(dm) / 评论(comment) 各自独立智能体与预设话术；回复方式按平台共用
    dmAgentId: string;
    dmAgentName: string;
    commentAgentId: string;
    commentAgentName: string;
    dmFixedScripts: string[];
    commentFixedScripts: string[];
    dmScriptInput: string;
    commentScriptInput: string;
    dmScriptError: boolean;
    commentScriptError: boolean;
    commentLikeOnly: boolean;
}

interface ConfigItem {
    id: string;
    title: string;
    desc: string;
    icon: any;
    iconBg: string;
    iconColor: string;
    agentIdField: string;
    agentNameField: string;
    replyModeField: string;
    fixedScriptsField: string;
    momentsActionField: string;
    agentId: string;
    agentName: string;
    replyMode: CommentReplyModeEnum;
    fixedScripts: string[];
    scriptInput: string;
    scriptError: boolean;
    momentsAction: MomentsActionEnum;
}

// ─── ScriptList（render 函数）─────────────────────────────────────
const COLLAPSE_THRESHOLD = 3;

const ScriptList = defineComponent({
    name: "ScriptList",
    props: {
        scripts: { type: Array as () => string[], required: true },
        isCollapsed: { type: Boolean, required: true },
    },
    emits: ["remove", "blur", "update", "toggle-collapse"],
    setup(props, { emit }) {
        return () => {
            if (!props.scripts.length) return null;

            const visibleScripts =
                props.scripts.length > COLLAPSE_THRESHOLD && props.isCollapsed
                    ? props.scripts.slice(0, COLLAPSE_THRESHOLD)
                    : props.scripts;

            const items = visibleScripts.map((scriptItem, idx) =>
                h("div", { class: "flex items-center gap-2", key: idx }, [
                    h(
                        "div",
                        {
                            class: "flex-1 bg-white rounded-xl px-3 py-2 shadow-sm",
                            style: scriptItem === "" ? "border: 1px solid #FF4D4F" : "border: 1px solid #E5E7EB",
                        },
                        [
                            h(ElInput, {
                                modelValue: scriptItem,
                                placeholder: "请输入回复话术",
                                size: "small",
                                "onUpdate:modelValue": (val: string) => emit("update", idx, val),
                                onBlur: () => emit("blur", idx),
                            }),
                        ],
                    ),
                    h(
                        ElButton,
                        {
                            circle: true,
                            size: "small",
                            style: "flex-shrink: 0",
                            onClick: () => emit("remove", idx),
                        },
                        () => h(ElIcon, null, () => h(Close)),
                    ),
                ]),
            );

            const toggleRow =
                props.scripts.length > COLLAPSE_THRESHOLD
                    ? h(
                          "div",
                          {
                              class: "flex items-center justify-center gap-1 py-2 rounded-xl cursor-pointer",
                              style: "background: #F5F7FA",
                              onClick: () => emit("toggle-collapse"),
                          },
                          [
                              h(
                                  "span",
                                  { class: "text-xs font-medium", style: "color: #3B71E8" },
                                  props.isCollapsed ? `展开查看全部 ${props.scripts.length} 条` : "收起",
                              ),
                              h(ElIcon, { style: "color: #3B71E8; font-size: 12px" }, () =>
                                  props.isCollapsed ? h(ArrowDown) : h(ArrowUp),
                              ),
                          ],
                      )
                    : null;

            return h("div", { class: "flex flex-col gap-2 mb-3" }, [...items, toggleRow]);
        };
    },
});

// ─── ReplyModeSelector（render 函数）─────────────────────────────
// ✅ 新增 showAi prop，控制是否显示 AI 智能回复选项
const ReplyModeSelector = defineComponent({
    name: "ReplyModeSelector",
    props: {
        replyMode: { type: Number as () => CommentReplyModeEnum, required: true },
        agentId: { type: String, default: "" },
        agentName: { type: String, default: "" },
        // 社媒平台：私信(dm) / 评论(comment) 各自独立选择智能体
        dualAgent: { type: Boolean, default: false },
        dmAgentId: { type: String, default: "" },
        dmAgentName: { type: String, default: "" },
        commentAgentId: { type: String, default: "" },
        commentAgentName: { type: String, default: "" },
        fixedScripts: { type: Array as () => string[], default: () => [] },
        scriptInput: { type: String, default: "" },
        scriptError: { type: Boolean, default: false },
        // 社媒平台 dualAgent 模式：私信 / 评论 各一组预设话术
        dmFixedScripts: { type: Array as () => string[], default: () => [] },
        commentFixedScripts: { type: Array as () => string[], default: () => [] },
        dmScriptInput: { type: String, default: "" },
        commentScriptInput: { type: String, default: "" },
        dmScriptError: { type: Boolean, default: false },
        commentScriptError: { type: Boolean, default: false },
        dmIsCollapsed: { type: Boolean, default: true },
        commentIsCollapsed: { type: Boolean, default: true },
        agentOptions: { type: Array as () => AgentOption[], required: true },
        showLikeOnly: { type: Boolean, default: false },
        showAi: { type: Boolean, default: true }, // ✅ 新增：是否显示 AI 选项
        sourceKey: { type: String, required: true },
        isCollapsed: { type: Boolean, required: true },
    },
    emits: [
        "update:replyMode",
        "update:agentId",
        "update:agentName",
        "update:dmAgentId",
        "update:dmAgentName",
        "update:commentAgentId",
        "update:commentAgentName",
        "update:fixedScripts",
        "update:scriptInput",
        "update:dmFixedScripts",
        "update:commentFixedScripts",
        "update:dmScriptInput",
        "update:commentScriptInput",
        "toggle-collapse",
        "expand-collapse",
        "clear-script-error",
        "toggle-dm-collapse",
        "expand-dm-collapse",
        "clear-dm-script-error",
        "toggle-comment-collapse",
        "expand-comment-collapse",
        "clear-comment-script-error",
    ],
    setup(props, { emit }) {
        // 选择智能体后同步回填 id 与 name；idEvent/nameEvent 决定写回单选槽位还是私信/评论独立槽位
        const makeAgentChange = (idEvent: string, nameEvent: string) => (val: string) => {
            const matched = props.agentOptions.find((a) => a.id === val);
            emit(idEvent as any, val ?? "");
            emit(nameEvent as any, matched?.name ?? "");
        };

        const renderAgentRow = (label: string, value: string, onChange: (val: string) => void) => {
            const meta = props.agentOptions.find((a) => a.id === value);
            const status = meta?.status || "ok";
            const warn =
                value && status !== "ok"
                    ? h(
                          "div",
                          { class: "mt-1 text-xs leading-relaxed", style: "color: #EA580C" },
                          meta?.statusText || "请重新绑定智能体",
                      )
                    : null;
            return h("div", { class: "mt-3", onClick: (e: Event) => e.stopPropagation() }, [
                h(
                    "div",
                    {
                        class: "flex items-center justify-between bg-white rounded-xl px-3 h-10 shadow-sm",
                        style:
                            value && status !== "ok"
                                ? "border: 1px solid #FDBA74"
                                : "border: 1px solid #E5E9F0",
                    },
                    [
                        h("span", { class: "text-xs", style: "color: #4B5563" }, label),
                        h(
                            ElSelect,
                            {
                                modelValue: value,
                                placeholder: "请选择智能体",
                                clearable: true,
                                filterable: true,
                                size: "small",
                                style: "width: 200px",
                                "onUpdate:modelValue": onChange,
                                onClick: (e: Event) => e.stopPropagation(),
                            },
                            () =>
                                props.agentOptions.map((agent) =>
                                    h(ElOption, {
                                        key: agent.id,
                                        label: formatAgentOptionLabel(agent),
                                        value: agent.id,
                                    }),
                                ),
                        ),
                    ],
                ),
                warn,
            ]);
        };

        // 固定话术编辑器：单组（关联智能体）与社媒私信/评论双组共用同一渲染逻辑
        const renderFixedEditor = (cfg: {
            label?: string;
            scripts: string[];
            scriptInput: string;
            scriptError: boolean;
            isCollapsed: boolean;
            onUpdateScripts: (v: string[]) => void;
            onUpdateInput: (v: string) => void;
            onClearError: () => void;
            onToggleCollapse: () => void;
            onExpandCollapse: () => void;
        }) => {
            const addScript = () => {
                const val = cfg.scriptInput.trim();
                if (!val) return;
                cfg.onUpdateScripts([...cfg.scripts, val]);
                cfg.onUpdateInput("");
                cfg.onClearError();
                cfg.onExpandCollapse();
            };
            return h(
                "div",
                {
                    class: "mt-3",
                    onClick: (e: Event) => e.stopPropagation(),
                },
                [
                    cfg.label
                        ? h(
                              "p",
                              { class: "text-xs font-semibold mb-2", style: "color: #374151" },
                              cfg.label,
                          )
                        : null,
                    h(ScriptList, {
                        scripts: cfg.scripts,
                        isCollapsed: cfg.isCollapsed,
                        onRemove: (idx: number) =>
                            cfg.onUpdateScripts(cfg.scripts.filter((_, i) => i !== idx)),
                        onBlur: (idx: number) => {
                            const val = cfg.scripts[idx]?.trim();
                            const newScripts = [...cfg.scripts];
                            if (!val) newScripts.splice(idx, 1);
                            else newScripts[idx] = val;
                            cfg.onUpdateScripts(newScripts);
                        },
                        onUpdate: (idx: number, val: string) => {
                            const newScripts = [...cfg.scripts];
                            newScripts[idx] = val;
                            cfg.onUpdateScripts(newScripts);
                        },
                        "onToggle-collapse": cfg.onToggleCollapse,
                    }),
                    h("div", { class: "flex items-center gap-2 mb-3" }, [
                        h(
                            "div",
                            {
                                class: "flex-1 bg-white rounded-xl px-3 py-2 shadow-sm",
                                style: cfg.scriptError
                                    ? "border: 1px solid #FF4D4F"
                                    : "border: 1px solid #E5E7EB",
                            },
                            [
                                h(ElInput, {
                                    modelValue: cfg.scriptInput,
                                    placeholder: "请输入回复话术",
                                    size: "small",
                                    "onUpdate:modelValue": (val: string) => cfg.onUpdateInput(val),
                                    onKeyup: (e: KeyboardEvent) => {
                                        if (e.key === "Enter") addScript();
                                    },
                                }),
                            ],
                        ),
                        h(
                            ElButton,
                            {
                                circle: true,
                                size: "small",
                                style: "flex-shrink: 0",
                                onClick: addScript,
                            },
                            () => h(ElIcon, null, () => h(Plus)),
                        ),
                    ]),
                    h(
                        "div",
                        {
                            class: "w-full py-2 bg-white rounded-xl flex items-center justify-center gap-1 cursor-pointer",
                            style: "border: 1px dashed #A3C0FF",
                            onClick: addScript,
                        },
                        [
                            h(ElIcon, { style: "color: #3B71E8" }, () => h(Plus)),
                            h("span", { class: "text-sm font-bold", style: "color: #3B71E8" }, "添加话术"),
                        ],
                    ),
                    h("div", { class: "flex items-center gap-1 mt-2 px-1" }, [
                        h(ElIcon, { style: "color: #9CA3AF; font-size: 12px; flex-shrink: 0" }, () =>
                            h(InfoFilled),
                        ),
                        h(
                            "span",
                            { class: "text-xs", style: "color: #9CA3AF" },
                            "每条代表一条独立话术，系统将随机抽取",
                        ),
                    ]),
                    cfg.scriptError
                        ? h("div", { class: "flex items-center gap-1 mt-2" }, [
                              h(ElIcon, { style: "color: #FF4D4F; font-size: 12px" }, () =>
                                  h(CircleCloseFilled),
                              ),
                              h(
                                  "span",
                                  { class: "text-xs", style: "color: #FF4D4F" },
                                  "请至少添加一条话术内容",
                              ),
                          ])
                        : null,
                ],
            );
        };

        const renderModeCard = (
            mode: CommentReplyModeEnum,
            opts: {
                icon: any;
                activeIconColor: string;
                activeBg: string;
                label: string;
                desc: string;
                extra?: () => any;
            },
        ) => {
            const isActive = props.replyMode === mode;
            return h(
                "div",
                {
                    class: "rounded-2xl overflow-hidden cursor-pointer transition-all",
                    style: isActive
                        ? "border: 2px solid #3B71E8; background: #F8FAFF"
                        : "border: 2px solid #EEEEEE; background: #F7F8FA",
                    onClick: () => emit("update:replyMode", mode),
                },
                [
                    h("div", { class: "flex items-start gap-3 p-4" }, [
                        h(
                            "div",
                            {
                                class: "w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0",
                                style: isActive
                                    ? `background: ${opts.activeBg}`
                                    : "background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1)",
                            },
                            [
                                h(
                                    ElIcon,
                                    { style: `color: ${isActive ? opts.activeIconColor : "#9CA3AF"}; font-size: 18px` },
                                    () => h(opts.icon),
                                ),
                            ],
                        ),
                        h("div", { class: "flex-1 min-w-0" }, [
                            h("div", { class: "flex items-center justify-between mb-1" }, [
                                h("span", { class: "font-bold text-sm", style: "color: #1A1A1A" }, opts.label),
                                h(
                                    "div",
                                    {
                                        class: "w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0",
                                        style: isActive
                                            ? "border-color: #3B71E8; background: #3B71E8"
                                            : "border-color: #D1D5DB; background: #fff",
                                    },
                                    isActive ? [h("div", { class: "w-2 h-2 rounded-full bg-white" })] : [],
                                ),
                            ]),
                            h("span", { class: "text-xs", style: "color: #9CA3AF" }, opts.desc),
                            isActive && opts.extra ? opts.extra() : null,
                        ]),
                    ]),
                ],
            );
        };

        return () => {
            // ✅ showAi=false 时不渲染 AI 卡片
            const aiCard = props.showAi
                ? renderModeCard(CommentReplyModeEnum.AI, {
                      icon: Cpu,
                      activeIconColor: "#3B71E8",
                      activeBg: "#EBF1FF",
                      label: "AI 智能回复",
                      desc: "调用智能体生成专属回复",
                      extra: () =>
                          props.dualAgent
                              ? h("div", null, [
                                    renderAgentRow(
                                        "私信智能体",
                                        props.dmAgentId,
                                        makeAgentChange("update:dmAgentId", "update:dmAgentName"),
                                    ),
                                    renderAgentRow(
                                        "评论智能体",
                                        props.commentAgentId,
                                        makeAgentChange("update:commentAgentId", "update:commentAgentName"),
                                    ),
                                ])
                              : renderAgentRow(
                                    "关联智能体",
                                    props.agentId,
                                    makeAgentChange("update:agentId", "update:agentName"),
                                ),
                  })
                : null;

            const fixedCard = renderModeCard(CommentReplyModeEnum.FIXED, {
                icon: List,
                activeIconColor: "#3B71E8",
                activeBg: "#EBF1FF",
                label: "固定话术回复",
                desc: "随机抽取一条预设话术进行回复",
                extra: () =>
                    props.dualAgent
                        ? h("div", null, [
                              renderFixedEditor({
                                  label: "私信预设话术",
                                  scripts: props.dmFixedScripts,
                                  scriptInput: props.dmScriptInput,
                                  scriptError: props.dmScriptError,
                                  isCollapsed: props.dmIsCollapsed,
                                  onUpdateScripts: (v) => emit("update:dmFixedScripts", v),
                                  onUpdateInput: (v) => emit("update:dmScriptInput", v),
                                  onClearError: () => emit("clear-dm-script-error"),
                                  onToggleCollapse: () => emit("toggle-dm-collapse"),
                                  onExpandCollapse: () => emit("expand-dm-collapse"),
                              }),
                              renderFixedEditor({
                                  label: "评论预设话术",
                                  scripts: props.commentFixedScripts,
                                  scriptInput: props.commentScriptInput,
                                  scriptError: props.commentScriptError,
                                  isCollapsed: props.commentIsCollapsed,
                                  onUpdateScripts: (v) => emit("update:commentFixedScripts", v),
                                  onUpdateInput: (v) => emit("update:commentScriptInput", v),
                                  onClearError: () => emit("clear-comment-script-error"),
                                  onToggleCollapse: () => emit("toggle-comment-collapse"),
                                  onExpandCollapse: () => emit("expand-comment-collapse"),
                              }),
                          ])
                        : renderFixedEditor({
                              scripts: props.fixedScripts,
                              scriptInput: props.scriptInput,
                              scriptError: props.scriptError,
                              isCollapsed: props.isCollapsed,
                              onUpdateScripts: (v) => emit("update:fixedScripts", v),
                              onUpdateInput: (v) => emit("update:scriptInput", v),
                              onClearError: () => emit("clear-script-error"),
                              onToggleCollapse: () => emit("toggle-collapse"),
                              onExpandCollapse: () => emit("expand-collapse"),
                          }),
            });

            const likeCard = props.showLikeOnly
                ? renderModeCard(CommentReplyModeEnum.LIKE_REPLY, {
                      icon: Star,
                      activeIconColor: "#FF4D8D",
                      activeBg: "#FFF0F5",
                      label: "仅点赞（不回复）",
                      desc: "仅点赞，不发表评论",
                  })
                : null;

            return h("div", { class: "flex flex-col gap-3" }, [aiCard, fixedCard, likeCard]);
        };
    },
});

// ─── 页面状态 ─────────────────────────────────────────────────────
const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const loading = ref(false);
const personId = ref("");
const detailData = ref<any>({});
const agentOptions = ref<AgentOption[]>([]);

// ─── 折叠状态 ─────────────────────────────────────────────────────
const scriptCollapsedMap = ref<Record<string, boolean>>({});

const getCollapsed = (key: string): boolean => scriptCollapsedMap.value[key] !== false;

const toggleCollapse = (key: string) => {
    scriptCollapsedMap.value = { ...scriptCollapsedMap.value, [key]: !getCollapsed(key) };
};

const expandCollapse = (key: string) => {
    scriptCollapsedMap.value = { ...scriptCollapsedMap.value, [key]: false };
};

// ─── 社媒平台配置（每个平台独立持有 type / agent / speech / comment_only_like）──────────
const buildEmptyPlatformConfig = (): SocialPlatformConfig => ({
    replyMode: CommentReplyModeEnum.AI,
    dmAgentId: "",
    dmAgentName: "",
    commentAgentId: "",
    commentAgentName: "",
    dmFixedScripts: [],
    commentFixedScripts: [],
    dmScriptInput: "",
    commentScriptInput: "",
    dmScriptError: false,
    commentScriptError: false,
    commentLikeOnly: false,
});
const socialPlatformConfigs = ref<Record<SocialPlatformKey, SocialPlatformConfig>>(
    socialPlatforms.reduce((acc, item) => {
        acc[item.key] = buildEmptyPlatformConfig();
        return acc;
    }, {} as Record<SocialPlatformKey, SocialPlatformConfig>),
);
const activeSocialPlatform = ref<SocialPlatformKey>("xhs");
const currentSocialPlatform = computed(() => socialPlatformConfigs.value[activeSocialPlatform.value]);

// ─── 顶层接管开关（detail 回填后保留，保存时统一发送）──────────
const enables = ref({ comment: 1, dm: 1, wechat: 1, moments: 1 });

// ─── 其他配置列表 ─────────────────────────────────────────────────
const configList = ref<ConfigItem[]>([
    {
        id: "wechat_1v1",
        title: "微信1V1私聊接管",
        desc: "个微自动回复聊天",
        icon: "el-icon-ChatLineRound",
        iconBg: "#E6F8F3",
        iconColor: "#00C08E",
        agentIdField: "wechat_chat_agent_id",
        agentNameField: "wechat_chat_agent_name",
        replyModeField: "wechat_chat_type",
        fixedScriptsField: "wechat_chat_speech",
        momentsActionField: "",
        agentId: "",
        agentName: "",
        replyMode: CommentReplyModeEnum.AI,
        fixedScripts: [],
        scriptInput: "",
        scriptError: false,
        momentsAction: MomentsActionEnum.BOTH,
    },
    {
        id: "moments_interact",
        title: "朋友圈互动接管",
        desc: "朋友圈自动点赞/评论",
        icon: "el-icon-Connection",
        iconBg: "#E6F0FF",
        iconColor: "#0065FB",
        agentIdField: "moments_agent_id",
        agentNameField: "moments_agent_name",
        replyModeField: "moments_type",
        fixedScriptsField: "moments_speech",
        momentsActionField: "moments_action",
        agentId: "",
        agentName: "",
        replyMode: CommentReplyModeEnum.AI,
        fixedScripts: [],
        scriptInput: "",
        scriptError: false,
        momentsAction: MomentsActionEnum.BOTH,
    },
    {
        id: "shutoff_comment",
        title: "截流评论",
        desc: "评论区截流自动回复",
        icon: "el-icon-ChatSquare",
        iconBg: "#FFF3E0",
        iconColor: "#FF6D00",
        agentIdField: "shutoff_comment_agent_id",
        agentNameField: "shutoff_commnet_agent_name",
        replyModeField: "shutoff_comment_type",
        fixedScriptsField: "shutoff_comment_speech",
        momentsActionField: "",
        agentId: "",
        agentName: "",
        replyMode: CommentReplyModeEnum.FIXED, // ✅ 默认固定话术
        fixedScripts: [],
        scriptInput: "",
        scriptError: false,
        momentsAction: MomentsActionEnum.BOTH,
    },
    {
        id: "shutoff_msg",
        title: "截流私信",
        desc: "私信截流自动回复",
        icon: "el-icon-Message",
        iconBg: "#F3E5F5",
        iconColor: "#9C27B0",
        agentIdField: "shutoff_msg_agent_id",
        agentNameField: "shuoff_msg_agent_name",
        replyModeField: "shutoff_msg_type",
        fixedScriptsField: "shutoff_msg_speech",
        momentsActionField: "",
        agentId: "",
        agentName: "",
        replyMode: CommentReplyModeEnum.FIXED, // ✅ 默认固定话术
        fixedScripts: [],
        scriptInput: "",
        scriptError: false,
        momentsAction: MomentsActionEnum.BOTH,
    },
]);

// ─── 工具 ─────────────────────────────────────────────────────────
const normalizeScripts = (raw: any): string[] => {
    if (Array.isArray(raw)) return raw;
    if (raw) return [String(raw)];
    return [];
};

const flushPendingInputs = () => {
    socialPlatforms.forEach((plat) => {
        const slot = socialPlatformConfigs.value[plat.key];
        const dmVal = slot.dmScriptInput.trim();
        if (dmVal) {
            slot.dmFixedScripts = [...slot.dmFixedScripts, dmVal];
            slot.dmScriptInput = "";
        }
        const commentVal = slot.commentScriptInput.trim();
        if (commentVal) {
            slot.commentFixedScripts = [...slot.commentFixedScripts, commentVal];
            slot.commentScriptInput = "";
        }
    });
    configList.value.forEach((item) => {
        const val = item.scriptInput.trim();
        if (val) {
            item.fixedScripts = [...item.fixedScripts, val];
            item.scriptInput = "";
        }
    });
};

// ─── 校验 ─────────────────────────────────────────────────────────
const validateConfig = (): boolean => {
    for (const plat of socialPlatforms) {
        const slot = socialPlatformConfigs.value[plat.key];
        if (slot.replyMode === CommentReplyModeEnum.AI) {
            if (!slot.dmAgentId) {
                activeSocialPlatform.value = plat.key;
                feedback.msgWarning(`【${plat.label}】请选择私信智能体`);
                return false;
            }
            const dmMeta = agentOptions.value.find((a) => a.id === slot.dmAgentId);
            if (dmMeta?.status && dmMeta.status !== "ok") {
                activeSocialPlatform.value = plat.key;
                feedback.msgWarning(
                    `【${plat.label}】私信智能体${dmMeta.status === "deleted" ? "已被删除" : "不可用"}，请重新绑定`,
                );
                return false;
            }
            // 评论仅点赞时评论不回复，无需评论智能体
            if (!slot.commentLikeOnly && !slot.commentAgentId) {
                activeSocialPlatform.value = plat.key;
                feedback.msgWarning(`【${plat.label}】请选择评论智能体`);
                return false;
            }
            if (!slot.commentLikeOnly && slot.commentAgentId) {
                const commentMeta = agentOptions.value.find((a) => a.id === slot.commentAgentId);
                if (commentMeta?.status && commentMeta.status !== "ok") {
                    activeSocialPlatform.value = plat.key;
                    feedback.msgWarning(
                        `【${plat.label}】评论智能体${
                            commentMeta.status === "deleted" ? "已被删除" : "不可用"
                        }，请重新绑定`,
                    );
                    return false;
                }
            }
        }
        if (slot.replyMode === CommentReplyModeEnum.FIXED) {
            if (!slot.dmFixedScripts.length) {
                slot.dmScriptError = true;
                activeSocialPlatform.value = plat.key;
                feedback.msgWarning(`【${plat.label}】请至少添加一条私信话术`);
                return false;
            }
            // 评论仅点赞时评论不回复，无需评论话术
            if (!slot.commentLikeOnly && !slot.commentFixedScripts.length) {
                slot.commentScriptError = true;
                activeSocialPlatform.value = plat.key;
                feedback.msgWarning(`【${plat.label}】请至少添加一条评论话术`);
                return false;
            }
        }
    }
    for (const item of configList.value) {
        const isLikeOnly = item.id === "moments_interact" && item.momentsAction === MomentsActionEnum.LIKE_ONLY;
        if (isLikeOnly) continue;
        if (item.replyMode === CommentReplyModeEnum.AI && !isShutoffItem(item.id)) {
            if (!item.agentId) {
                feedback.msgWarning(`【${item.title}】请选择关联智能体`);
                return false;
            }
            const meta = agentOptions.value.find((a) => a.id === item.agentId);
            if (meta?.status && meta.status !== "ok") {
                feedback.msgWarning(
                    `【${item.title}】智能体${meta.status === "deleted" ? "已被删除" : "不可用"}，请重新绑定`,
                );
                return false;
            }
        }
        if (item.replyMode === CommentReplyModeEnum.FIXED && !item.fixedScripts.length) {
            item.scriptError = true;
            feedback.msgWarning(`【${item.title}】请至少添加一条话术内容`);
            return false;
        }
    }
    return true;
};

// ─── 保存 ─────────────────────────────────────────────────────────
const toInt = (v: any, fallback = 0): number => {
    const n = Number(v);
    return Number.isFinite(n) ? n : fallback;
};

const buildPlatformAgentConfig = (): Record<string, any> => {
    const result: Record<string, any> = {};
    socialPlatforms.forEach((plat) => {
        const slot = socialPlatformConfigs.value[plat.key];
        const isAI = slot.replyMode === CommentReplyModeEnum.AI;
        const isFixed = slot.replyMode === CommentReplyModeEnum.FIXED;
        result[String(plat.type)] = {
            dm: {
                type: slot.replyMode,
                agent_id: isAI ? toInt(slot.dmAgentId) : 0,
                speech: isFixed ? slot.dmFixedScripts : [],
            },
            comment: {
                type: slot.replyMode,
                agent_id: isAI ? toInt(slot.commentAgentId) : 0,
                speech: isFixed ? slot.commentFixedScripts : [],
                comment_only_like: slot.commentLikeOnly ? 1 : 0,
            },
        };
    });
    return result;
};

const handleSave = async () => {
    flushPendingInputs();
    if (!validateConfig()) return;

    const listParams = configList.value.reduce<Record<string, any>>((acc, item) => {
        const useAi = item.replyMode === CommentReplyModeEnum.AI;
        acc[item.agentIdField] = useAi ? toInt(item.agentId) : 0;
        acc[item.replyModeField] = item.replyMode;
        acc[item.fixedScriptsField] = useAi ? [] : item.fixedScripts;
        if (item.id === "moments_interact") acc[item.momentsActionField] = item.momentsAction;
        return acc;
    }, {});

    const payload: Record<string, any> = {
        persona_id: personId.value,
        comment_enabled: enables.value.comment,
        dm_enabled: enables.value.dm,
        wechat_chat_enabled: enables.value.wechat,
        moments_enabled: enables.value.moments,
        platform_agent_config: buildPlatformAgentConfig(),
        ...listParams,
    };
    if (detailData.value?.id) payload.id = detailData.value.id;

    await updateCustomerServiceConfig(payload);

    close();
    emit("success");
};

const { isLock, lockFn } = useLockFn(handleSave);
const close = () => emit("close");

// ─── 初始化 ──────────────────────────────────────────────────────
const getDetail = async () => {
    loading.value = true;
    try {
        const agentRes = await getPersonAgent({ persona_id: personId.value });
        const { lists } = await getAgentLists({ user_id: agentRes.user_id, source: 0, page_size: 25000 });
        detailData.value = agentRes;

        // 可选列表通常只有「人设归属用户自己的 + 系统」智能体；
        // 团队共享绑定（其他成员创建）不在列表里，ElSelect 会回显原始 id。
        // 把详情里已绑定的智能体（含名称/可用性）合并进选项，与小程序展示对齐。
        const optionMap = new Map<string, AgentOption>();
        (lists ?? []).forEach((agent: any) => {
            const id = agent?.id != null ? String(agent.id) : "";
            if (!id) return;
            optionMap.set(id, {
                id,
                name: String(agent.name || "").trim() || `智能体#${id}`,
            });
        });
        const upsertBoundAgent = (id: any, name: any, status: any, statusText: any) => {
            const sid = id != null && Number(id) > 0 ? String(id) : "";
            if (!sid) return;
            const prev = optionMap.get(sid);
            optionMap.set(sid, {
                id: sid,
                name: String(name || prev?.name || "").trim() || `智能体#${sid}`,
                status: normalizeAgentStatus(status),
                statusText: String(statusText || prev?.statusText || ""),
            });
        };

        enables.value.comment = agentRes.comment_enabled ?? 1;
        enables.value.dm = agentRes.dm_enabled ?? 1;
        enables.value.wechat = agentRes.wechat_chat_enabled ?? 1;
        enables.value.moments = agentRes.moments_enabled ?? 1;

        // 社媒：重置后按 platform_agent_config 回填，未返回的平台保留空配置
        const freshPlatforms = socialPlatforms.reduce(
            (acc, item) => {
                acc[item.key] = buildEmptyPlatformConfig();
                return acc;
            },
            {} as Record<SocialPlatformKey, SocialPlatformConfig>,
        );
        const platformMap = (agentRes.platform_agent_config ?? {}) as Record<string, any>;
        Object.keys(platformMap).forEach((typeKey) => {
            const key = SOCIAL_TYPE_TO_KEY[Number(typeKey)];
            if (!key) return;
            const raw = platformMap[typeKey] || {};
            const slot = freshPlatforms[key];
            const dmRaw = raw.dm;
            const commentRaw = raw.comment;
            if (dmRaw || commentRaw) {
                // 新结构：私信(dm) / 评论(comment) 独立配置
                const base = dmRaw ?? commentRaw ?? {};
                slot.replyMode =
                    Number(base.type) === CommentReplyModeEnum.FIXED
                        ? CommentReplyModeEnum.FIXED
                        : CommentReplyModeEnum.AI;
                slot.dmAgentId = dmRaw?.agent_id ? String(dmRaw.agent_id) : "";
                slot.dmAgentName = dmRaw?.agent_name ?? "";
                slot.commentAgentId = commentRaw?.agent_id ? String(commentRaw.agent_id) : "";
                slot.commentAgentName = commentRaw?.agent_name ?? "";
                slot.dmFixedScripts = normalizeScripts(dmRaw?.speech);
                slot.commentFixedScripts = normalizeScripts(commentRaw?.speech);
                slot.commentLikeOnly = Number(commentRaw?.comment_only_like) === 1;
                upsertBoundAgent(
                    dmRaw?.agent_id,
                    dmRaw?.agent_name,
                    dmRaw?.agent_status,
                    dmRaw?.agent_status_text,
                );
                upsertBoundAgent(
                    commentRaw?.agent_id,
                    commentRaw?.agent_name,
                    commentRaw?.agent_status,
                    commentRaw?.agent_status_text,
                );
            } else {
                // 旧结构（扁平）：私信 / 评论共用同一智能体与话术
                slot.replyMode =
                    Number(raw.type) === CommentReplyModeEnum.FIXED
                        ? CommentReplyModeEnum.FIXED
                        : CommentReplyModeEnum.AI;
                const id = raw.agent_id ? String(raw.agent_id) : "";
                const name = raw.agent_name ?? "";
                slot.dmAgentId = id;
                slot.dmAgentName = name;
                slot.commentAgentId = id;
                slot.commentAgentName = name;
                const scripts = normalizeScripts(raw.speech);
                slot.dmFixedScripts = [...scripts];
                slot.commentFixedScripts = [...scripts];
                slot.commentLikeOnly = Number(raw.comment_only_like) === 1;
                upsertBoundAgent(raw.agent_id, raw.agent_name, raw.agent_status, raw.agent_status_text);
            }
        });
        socialPlatformConfigs.value = freshPlatforms;

        configList.value.forEach((item) => {
            item.agentId = agentRes[item.agentIdField] ? String(agentRes[item.agentIdField]) : "";
            item.agentName = agentRes[item.agentNameField] ?? "";
            if (isShutoffItem(item.id)) {
                item.replyMode = CommentReplyModeEnum.FIXED;
            } else {
                item.replyMode = agentRes[item.replyModeField] ?? CommentReplyModeEnum.AI;
            }
            item.fixedScripts = normalizeScripts(agentRes[item.fixedScriptsField]);
            if (item.id === "moments_interact") {
                item.momentsAction = agentRes[item.momentsActionField] ?? MomentsActionEnum.BOTH;
            }
            const statusField = `${item.agentIdField.replace(/_id$/, "")}_status`;
            const statusTextField = `${statusField}_text`;
            // shutoff_comment 历史字段拼写不一致，名称已用 agentNameField；状态仍走标准字段
            const statusKey =
                item.id === "shutoff_comment"
                    ? "shutoff_comment_agent_status"
                    : item.id === "shutoff_msg"
                      ? "shutoff_msg_agent_status"
                      : statusField;
            const statusTextKey = `${statusKey}_text`;
            upsertBoundAgent(
                agentRes[item.agentIdField],
                agentRes[item.agentNameField],
                agentRes[statusKey] ?? agentRes[statusField],
                agentRes[statusTextKey] ?? agentRes[statusTextField],
            );
        });

        agentOptions.value = Array.from(optionMap.values());
    } finally {
        loading.value = false;
    }
};

const open = (id: string) => {
    personId.value = id;
    getDetail();
    popupRef.value?.open();
};

defineExpose({ open });
</script>

<style scoped></style>
