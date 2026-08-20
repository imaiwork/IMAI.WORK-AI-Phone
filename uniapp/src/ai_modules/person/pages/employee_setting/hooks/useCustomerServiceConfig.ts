import { computed, reactive, ref, type Ref } from "vue";
import { getAgentDetail, updateCustomerServiceConfig } from "@/api/person";
import { AppTypeEnum } from "@/enums/appEnums";

/**
 * 智能客服配置 hook（对接 /aiPersona.agentConfig/detail + updateCustomerService）
 *
 * 数据模型按"AI员工-智能客服"4 个分块组织：
 *  1. 社媒平台（评论 + 私信）—— 每个平台独立持有 type / agent / speech / comment_only_like
 *  2. 微信私信   —— wechat_chat_*
 *  3. 朋友圈互动 —— moments_*（含执行动作：仅点赞/仅评论/评论+点赞）
 *  4. 截流转化   —— shutoff_comment / shutoff_msg（当前 UI 仅暴露固定话术）
 *
 * 顶层 enables（comment / dm / wechat_chat / moments）当前 UI 通过单一"智能客服"开关统一控制，
 * 详情接口若返回独立值会原样回写到 enables，保存时按"4 个分块统一启停"重新发送。
 */

export enum CsReplyMode {
    AI = 1,
    FIXED = 2,
}

export enum MomentsAction {
    LIKE_ONLY = 1,
    COMMENT_ONLY = 2,
    BOTH = 3,
}

// 社媒平台 ↔ 后端 platform_type 对齐：3小红书 4抖音 5快手（视频号已下线）
export type SocialPlatform = "xhs" | "dy" | "ks";
export const SOCIAL_PLATFORMS: { key: SocialPlatform; label: string; type: AppTypeEnum }[] = [
    { key: "xhs", label: "小红书", type: AppTypeEnum.XHS },
    { key: "dy", label: "抖音", type: AppTypeEnum.DOUYIN },
    { key: "ks", label: "快手", type: AppTypeEnum.KUAISHOU },
];
const TYPE_TO_PLATFORM: Record<number, SocialPlatform> = SOCIAL_PLATFORMS.reduce((acc, item) => {
    acc[item.type] = item.key;
    return acc;
}, {} as Record<number, SocialPlatform>);

type AgentTarget = "social-dm" | "social-comment" | "wechat" | "moments";

interface ScriptBlock {
    scripts: string[];
}

/** 绑定智能体在当前空间的可用性：ok 可用 / unavailable 跨团队不可用 / deleted 已删除 */
export type AgentBindStatus = "ok" | "unavailable" | "deleted";

// 社媒平台：私信(dm) 与评论(comment) 各自独立选择智能体与预设话术；回复方式按平台共用
interface PlatformConfig {
    replyMode: CsReplyMode;
    dmAgentId: string;
    dmAgentName: string;
    dmAgentStatus: AgentBindStatus;
    dmAgentStatusText: string;
    commentAgentId: string;
    commentAgentName: string;
    commentAgentStatus: AgentBindStatus;
    commentAgentStatusText: string;
    dmScripts: string[];
    commentScripts: string[];
    commentLikeOnly: boolean;
}

export const formatBoundAgentLabel = (name: string, status: AgentBindStatus = "ok"): string => {
    const n = String(name || "").trim();
    if (status === "deleted") return n ? `${n}（已被删除）` : "智能体已被删除";
    if (status === "unavailable") return n ? `${n}（不可用）` : "智能体不可用";
    return n || "请选择智能体";
};

const normalizeAgentStatus = (raw: any): AgentBindStatus => {
    const s = String(raw || "ok");
    if (s === "deleted" || s === "unavailable") return s;
    return "ok";
};

const toInt = (value: any, fallback = 0): number => {
    const n = Number(value);
    return Number.isFinite(n) ? n : fallback;
};

const normalizeScripts = (raw: any): string[] => {
    if (Array.isArray(raw)) return raw.map((item) => String(item ?? "")).filter((item) => item.trim() !== "");
    if (typeof raw === "string" && raw.trim()) return [raw.trim()];
    return [];
};

const buildEmptyPlatformConfig = (): PlatformConfig => ({
    replyMode: CsReplyMode.AI,
    dmAgentId: "",
    dmAgentName: "",
    dmAgentStatus: "ok",
    dmAgentStatusText: "",
    commentAgentId: "",
    commentAgentName: "",
    commentAgentStatus: "ok",
    commentAgentStatusText: "",
    dmScripts: [],
    commentScripts: [],
    commentLikeOnly: false,
});

export function useCustomerServiceConfig(personId: Ref<string>) {
    const loading = ref(false);
    const saving = ref(false);
    // agentConfig 主键 id，保存时回传
    const agentConfigId = ref<string | number>("");

    // 顶层接管开关：详情回填后保留，保存时随 UI 单一开关统一刷新
    const enables = reactive({
        comment: 1,
        dm: 1,
        wechat: 1,
        moments: 1,
    });

    // 社媒：每个平台独立持有完整配置
    const buildPlatformsMap = (): Record<SocialPlatform, PlatformConfig> =>
        SOCIAL_PLATFORMS.reduce((acc, item) => {
            acc[item.key] = buildEmptyPlatformConfig();
            return acc;
        }, {} as Record<SocialPlatform, PlatformConfig>);

    const social = reactive({
        activePlatform: "xhs" as SocialPlatform,
        platforms: buildPlatformsMap(),
    });

    const socialCurrent = computed<PlatformConfig>(() => social.platforms[social.activePlatform]);

    const wechat = reactive({
        replyMode: CsReplyMode.AI,
        agentId: "",
        agentName: "",
        agentStatus: "ok" as AgentBindStatus,
        agentStatusText: "",
        scripts: [] as string[],
    });

    const moments = reactive({
        action: MomentsAction.BOTH,
        replyMode: CsReplyMode.AI,
        agentId: "",
        agentName: "",
        agentStatus: "ok" as AgentBindStatus,
        agentStatusText: "",
        scripts: [] as string[],
    });

    // 截流块：保留 type / agentId 以便未来在 UI 暴露；当前 UI 仅展示固定话术
    const shutoffComment = reactive({
        replyMode: CsReplyMode.FIXED as CsReplyMode,
        agentId: "",
        agentName: "",
        agentStatus: "ok" as AgentBindStatus,
        agentStatusText: "",
        scripts: [] as string[],
    });
    const shutoffMsg = reactive({
        replyMode: CsReplyMode.FIXED as CsReplyMode,
        agentId: "",
        agentName: "",
        agentStatus: "ok" as AgentBindStatus,
        agentStatusText: "",
        scripts: [] as string[],
    });

    // 当前正在选择智能体的目标块；social 按 activePlatform 分发到对应平台槽位
    const agentTarget = ref<AgentTarget>("social-dm");

    const beginPickAgent = (target: AgentTarget): string => {
        agentTarget.value = target;
        if (target === "social-dm") return socialCurrent.value.dmAgentId;
        if (target === "social-comment") return socialCurrent.value.commentAgentId;
        if (target === "wechat") return wechat.agentId;
        return moments.agentId;
    };

    const applyAgent = (agent: any): void => {
        const id = String(agent?.id ?? "");
        const name = String(agent?.name ?? "");
        if (agentTarget.value === "social-dm") {
            const slot = socialCurrent.value;
            slot.dmAgentId = id;
            slot.dmAgentName = name;
            slot.dmAgentStatus = "ok";
            slot.dmAgentStatusText = "";
            return;
        }
        if (agentTarget.value === "social-comment") {
            const slot = socialCurrent.value;
            slot.commentAgentId = id;
            slot.commentAgentName = name;
            slot.commentAgentStatus = "ok";
            slot.commentAgentStatusText = "";
            return;
        }
        const block = agentTarget.value === "wechat" ? wechat : moments;
        block.agentId = id;
        block.agentName = name;
        block.agentStatus = "ok";
        block.agentStatusText = "";
    };

    // 话术增删 / 移动（通用）
    const addScript = (block: ScriptBlock, value: string, maxItems = 0): void => {
        const text = String(value ?? "").trim();
        if (!text) return;
        if (block.scripts.includes(text)) {
            uni.$u.toast("话术已存在");
            return;
        }
        if (maxItems > 0 && block.scripts.length >= maxItems) {
            uni.$u.toast(`最多添加 ${maxItems} 条`);
            return;
        }
        block.scripts.push(text);
    };

    const removeScript = (block: ScriptBlock, index: number): void => {
        block.scripts.splice(index, 1);
    };

    const moveScript = (block: ScriptBlock, index: number, direction: "up" | "down"): void => {
        const arr = block.scripts;
        const target = direction === "up" ? index - 1 : index + 1;
        if (index < 0 || index >= arr.length || target < 0 || target >= arr.length) return;
        const tmp = arr[index];
        arr[index] = arr[target];
        arr[target] = tmp;
    };

    const normalizeReplyMode = (value: any, fallback = CsReplyMode.AI): CsReplyMode =>
        toInt(value) === CsReplyMode.FIXED ? CsReplyMode.FIXED : fallback;

    const applyDetail = (data: Record<string, any>): void => {
        agentConfigId.value = data?.id ?? "";

        enables.comment = toInt(data?.comment_enabled, 1);
        enables.dm = toInt(data?.dm_enabled, 1);
        enables.wechat = toInt(data?.wechat_chat_enabled, 1);
        enables.moments = toInt(data?.moments_enabled, 1);

        // 社媒：重置 + 按 platform_agent_config 回填
        social.platforms = buildPlatformsMap();
        const platformMap = (data?.platform_agent_config ?? {}) as Record<string, any>;
        Object.keys(platformMap).forEach((typeKey) => {
            const platform = TYPE_TO_PLATFORM[toInt(typeKey)];
            if (!platform) return;
            const raw = platformMap[typeKey] || {};
            const slot = social.platforms[platform];
            const dmRaw = raw.dm;
            const commentRaw = raw.comment;
            if (dmRaw || commentRaw) {
                // 新结构：私信(dm) / 评论(comment) 独立配置（智能体 + 预设话术）
                slot.replyMode = normalizeReplyMode((dmRaw ?? commentRaw)?.type);
                slot.dmAgentId = dmRaw?.agent_id ? String(dmRaw.agent_id) : "";
                slot.dmAgentName = String(dmRaw?.agent_name ?? "");
                slot.dmAgentStatus = normalizeAgentStatus(dmRaw?.agent_status);
                slot.dmAgentStatusText = String(dmRaw?.agent_status_text ?? "");
                slot.commentAgentId = commentRaw?.agent_id ? String(commentRaw.agent_id) : "";
                slot.commentAgentName = String(commentRaw?.agent_name ?? "");
                slot.commentAgentStatus = normalizeAgentStatus(commentRaw?.agent_status);
                slot.commentAgentStatusText = String(commentRaw?.agent_status_text ?? "");
                slot.dmScripts = normalizeScripts(dmRaw?.speech);
                slot.commentScripts = normalizeScripts(commentRaw?.speech);
                slot.commentLikeOnly = toInt(commentRaw?.comment_only_like) === 1;
            } else {
                // 旧结构（扁平）：私信 / 评论共用同一智能体与话术
                slot.replyMode = normalizeReplyMode(raw.type);
                const id = raw.agent_id ? String(raw.agent_id) : "";
                const name = String(raw.agent_name ?? "");
                const status = normalizeAgentStatus(raw.agent_status);
                const statusText = String(raw.agent_status_text ?? "");
                slot.dmAgentId = id;
                slot.dmAgentName = name;
                slot.dmAgentStatus = status;
                slot.dmAgentStatusText = statusText;
                slot.commentAgentId = id;
                slot.commentAgentName = name;
                slot.commentAgentStatus = status;
                slot.commentAgentStatusText = statusText;
                const scripts = normalizeScripts(raw.speech);
                slot.dmScripts = [...scripts];
                slot.commentScripts = [...scripts];
                slot.commentLikeOnly = toInt(raw.comment_only_like) === 1;
            }
        });

        wechat.replyMode = normalizeReplyMode(data?.wechat_chat_type);
        wechat.agentId = data?.wechat_chat_agent_id ? String(data.wechat_chat_agent_id) : "";
        wechat.agentName = String(data?.wechat_chat_agent_name ?? "");
        wechat.agentStatus = normalizeAgentStatus(data?.wechat_chat_agent_status);
        wechat.agentStatusText = String(data?.wechat_chat_agent_status_text ?? "");
        wechat.scripts = normalizeScripts(data?.wechat_chat_speech);

        moments.action = toInt(data?.moments_action) || MomentsAction.BOTH;
        moments.replyMode = normalizeReplyMode(data?.moments_type);
        moments.agentId = data?.moments_agent_id ? String(data.moments_agent_id) : "";
        moments.agentName = String(data?.moments_agent_name ?? "");
        moments.agentStatus = normalizeAgentStatus(data?.moments_agent_status);
        moments.agentStatusText = String(data?.moments_agent_status_text ?? "");
        moments.scripts = normalizeScripts(data?.moments_speech);

        shutoffComment.replyMode = normalizeReplyMode(data?.shutoff_comment_type, CsReplyMode.FIXED);
        shutoffComment.agentId = data?.shutoff_comment_agent_id ? String(data.shutoff_comment_agent_id) : "";
        shutoffComment.agentName = String(data?.shutoff_comment_agent_name ?? "");
        shutoffComment.agentStatus = normalizeAgentStatus(data?.shutoff_comment_agent_status);
        shutoffComment.agentStatusText = String(data?.shutoff_comment_agent_status_text ?? "");
        shutoffComment.scripts = normalizeScripts(data?.shutoff_comment_speech);

        shutoffMsg.replyMode = normalizeReplyMode(data?.shutoff_msg_type, CsReplyMode.FIXED);
        shutoffMsg.agentId = data?.shutoff_msg_agent_id ? String(data.shutoff_msg_agent_id) : "";
        shutoffMsg.agentName = String(data?.shutoff_msg_agent_name ?? "");
        shutoffMsg.agentStatus = normalizeAgentStatus(data?.shutoff_msg_agent_status);
        shutoffMsg.agentStatusText = String(data?.shutoff_msg_agent_status_text ?? "");
        shutoffMsg.scripts = normalizeScripts(data?.shutoff_msg_speech);
    };

    const loadConfig = async (): Promise<void> => {
        if (!personId.value) return;
        try {
            loading.value = true;
            const data = await getAgentDetail({ persona_id: personId.value });
            applyDetail(data || {});
        } catch {
            uni.$u.toast("智能客服配置获取失败");
        } finally {
            loading.value = false;
        }
    };

    const buildPlatformPayload = (slot: PlatformConfig) => {
        const isAI = slot.replyMode === CsReplyMode.AI;
        const isFixed = slot.replyMode === CsReplyMode.FIXED;
        return {
            dm: {
                type: slot.replyMode,
                agent_id: isAI ? toInt(slot.dmAgentId) : 0,
                speech: isFixed ? slot.dmScripts : [],
            },
            comment: {
                type: slot.replyMode,
                agent_id: isAI ? toInt(slot.commentAgentId) : 0,
                speech: isFixed ? slot.commentScripts : [],
                comment_only_like: slot.commentLikeOnly ? 1 : 0,
            },
        };
    };

    const buildPayload = (): Record<string, any> => {
        const platformAgentConfig: Record<string, any> = {};
        SOCIAL_PLATFORMS.forEach((platform) => {
            platformAgentConfig[String(platform.type)] = buildPlatformPayload(social.platforms[platform.key]);
        });

        const payload: Record<string, any> = {
            persona_id: personId.value,
            comment_enabled: enables.comment,
            dm_enabled: enables.dm,
            wechat_chat_enabled: enables.wechat,
            moments_enabled: enables.moments,
            platform_agent_config: platformAgentConfig,
            wechat_chat_type: wechat.replyMode,
            wechat_chat_agent_id: wechat.replyMode === CsReplyMode.AI ? toInt(wechat.agentId) : 0,
            wechat_chat_speech: wechat.replyMode === CsReplyMode.FIXED ? wechat.scripts : [],
            moments_action: moments.action,
            moments_type: moments.replyMode,
            moments_agent_id: moments.replyMode === CsReplyMode.AI ? toInt(moments.agentId) : 0,
            moments_speech: moments.replyMode === CsReplyMode.FIXED ? moments.scripts : [],
            shutoff_comment_type: shutoffComment.replyMode,
            shutoff_comment_agent_id: shutoffComment.replyMode === CsReplyMode.AI ? toInt(shutoffComment.agentId) : 0,
            shutoff_comment_speech: shutoffComment.replyMode === CsReplyMode.FIXED ? shutoffComment.scripts : [],
            shutoff_msg_type: shutoffMsg.replyMode,
            shutoff_msg_agent_id: shutoffMsg.replyMode === CsReplyMode.AI ? toInt(shutoffMsg.agentId) : 0,
            shutoff_msg_speech: shutoffMsg.replyMode === CsReplyMode.FIXED ? shutoffMsg.scripts : [],
        };
        // 编辑模式带 id；新建走 persona_id 查找/创建
        if (agentConfigId.value !== "" && agentConfigId.value !== null) {
            payload.id = agentConfigId.value;
        }
        return payload;
    };

    const validate = (): boolean => {
        // 社媒：每个平台独立校验；AI 模式需选智能体，FIXED 模式需有话术（仅点赞模式跳过文本校验）
        for (const platform of SOCIAL_PLATFORMS) {
            const slot = social.platforms[platform.key];
            if (slot.replyMode === CsReplyMode.AI) {
                if (!toInt(slot.dmAgentId)) {
                    uni.$u.toast(`【${platform.label}】请选择私信智能体`);
                    return false;
                }
                if (slot.dmAgentStatus !== "ok") {
                    uni.$u.toast(
                        `【${platform.label}】私信智能体${
                            slot.dmAgentStatus === "deleted" ? "已被删除" : "不可用"
                        }，请重新绑定`
                    );
                    return false;
                }
                // 评论仅点赞时评论不回复，无需评论智能体
                if (!slot.commentLikeOnly && !toInt(slot.commentAgentId)) {
                    uni.$u.toast(`【${platform.label}】请选择评论智能体`);
                    return false;
                }
                if (!slot.commentLikeOnly && slot.commentAgentStatus !== "ok") {
                    uni.$u.toast(
                        `【${platform.label}】评论智能体${
                            slot.commentAgentStatus === "deleted" ? "已被删除" : "不可用"
                        }，请重新绑定`
                    );
                    return false;
                }
            }
            if (slot.replyMode === CsReplyMode.FIXED) {
                if (!slot.dmScripts.length) {
                    uni.$u.toast(`【${platform.label}】请至少添加一条私信话术`);
                    return false;
                }
                // 评论仅点赞时评论不回复，无需评论话术
                if (!slot.commentLikeOnly && !slot.commentScripts.length) {
                    uni.$u.toast(`【${platform.label}】请至少添加一条评论话术`);
                    return false;
                }
            }
        }
        if (wechat.replyMode === CsReplyMode.AI) {
            if (!toInt(wechat.agentId)) {
                uni.$u.toast("【微信私信接管】请选择关联智能体");
                return false;
            }
            if (wechat.agentStatus !== "ok") {
                uni.$u.toast(
                    `【微信私信接管】智能体${wechat.agentStatus === "deleted" ? "已被删除" : "不可用"}，请重新绑定`
                );
                return false;
            }
        }
        if (wechat.replyMode === CsReplyMode.FIXED && !wechat.scripts.length) {
            uni.$u.toast("【微信私信接管】请至少添加一条话术");
            return false;
        }
        if (moments.action !== MomentsAction.LIKE_ONLY) {
            if (moments.replyMode === CsReplyMode.AI) {
                if (!toInt(moments.agentId)) {
                    uni.$u.toast("【朋友圈互动接管】请选择关联智能体");
                    return false;
                }
                if (moments.agentStatus !== "ok") {
                    uni.$u.toast(
                        `【朋友圈互动接管】智能体${
                            moments.agentStatus === "deleted" ? "已被删除" : "不可用"
                        }，请重新绑定`
                    );
                    return false;
                }
            }
            if (moments.replyMode === CsReplyMode.FIXED && !moments.scripts.length) {
                uni.$u.toast("【朋友圈互动接管】请至少添加一条话术");
                return false;
            }
        }
        return true;
    };

    const trimScripts = (): void => {
        SOCIAL_PLATFORMS.forEach((platform) => {
            const slot = social.platforms[platform.key];
            slot.dmScripts = slot.dmScripts.filter((s) => s.trim() !== "");
            slot.commentScripts = slot.commentScripts.filter((s) => s.trim() !== "");
        });
        wechat.scripts = wechat.scripts.filter((s) => s.trim() !== "");
        moments.scripts = moments.scripts.filter((s) => s.trim() !== "");
        shutoffComment.scripts = shutoffComment.scripts.filter((s) => s.trim() !== "");
        shutoffMsg.scripts = shutoffMsg.scripts.filter((s) => s.trim() !== "");
    };

    const saveConfig = async (): Promise<boolean> => {
        if (!personId.value || saving.value) return false;
        trimScripts();
        if (!validate()) return false;
        try {
            saving.value = true;
            uni.showLoading({ title: "保存中...", mask: true });
            await updateCustomerServiceConfig(buildPayload());
            uni.hideLoading();
            uni.$u.toast("智能客服配置已保存");
            return true;
        } catch (error: any) {
            uni.hideLoading();
            uni.$u.toast(error || "保存失败，请重试");
            return false;
        } finally {
            saving.value = false;
        }
    };

    return {
        loading,
        saving,
        enables,
        social,
        socialCurrent,
        socialPlatforms: SOCIAL_PLATFORMS,
        wechat,
        moments,
        shutoffComment,
        shutoffMsg,
        beginPickAgent,
        applyAgent,
        addScript,
        removeScript,
        moveScript,
        loadConfig,
        saveConfig,
        formatBoundAgentLabel,
    };
}
