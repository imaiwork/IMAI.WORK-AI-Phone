export const AGENT_UNAVAILABLE_TIP = "该智能体当前会员不可用";

export type AgentAccessStatus = "free" | "member";

export const normalizeLevelId = (value: unknown) => {
    if (value === undefined || value === null || value === "") return null;
    const levelId = Number(value);
    return Number.isFinite(levelId) ? levelId : null;
};

export const normalizeMemberLevelIds = (value: unknown) => {
    if (Array.isArray(value)) {
        return value.map((levelId) => Number(levelId)).filter((levelId) => Number.isFinite(levelId));
    }

    if (typeof value === "string") {
        const text = value.trim();
        if (!text) return [];

        try {
            const parsed = JSON.parse(text);
            if (Array.isArray(parsed)) return normalizeMemberLevelIds(parsed);
        } catch {
            // fallback to comma-separated member_level_ids
        }

        return text
            .split(",")
            .map((levelId) => Number(levelId.trim()))
            .filter((levelId) => Number.isFinite(levelId));
    }

    return [];
};

export const isUserOwnedAgent = (agent: any) => Number(agent?.source) === 1;

export const shouldShowAgentAccessTag = (agent: any) => !isUserOwnedAgent(agent);

export const isMemberOnlyAgent = (agent: any) => Number(agent?.permissions) === 1;

export const canUseAgent = (agent: any, userInfo: any) => {
    if (!agent || isUserOwnedAgent(agent) || !isMemberOnlyAgent(agent)) return true;

    const userLevelId = normalizeLevelId(userInfo?.level_id);
    if (userLevelId === null) return false;

    return normalizeMemberLevelIds(agent.member_level_ids).includes(userLevelId);
};

export const getAgentAccessStatus = (agent: any, userInfo: any): AgentAccessStatus =>
    canUseAgent(agent, userInfo) ? "free" : "member";

export const getAgentAccessTagText = (agent: any, userInfo: any) =>
    getAgentAccessStatus(agent, userInfo) === "free" ? "免费使用" : "仅会员可用";
