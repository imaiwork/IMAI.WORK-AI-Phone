/** 切换/退出团队时，对 IP 人设智能客服绑定的影响说明 */

/** 切换空间影响要点（弹窗可扫读） */
export const TEAM_SPACE_SWITCH_IMPACTS = [
    "IP人设「智能客服」中绑定的其他团队智能体将暂时不可用",
    "需重新绑定后才能继续使用；切回原团队后可自动恢复",
    "若智能体已被删除，请重新选择",
] as const;

export const TEAM_SPACE_SWITCH_TIP = TEAM_SPACE_SWITCH_IMPACTS.join("；") + "。";

export const TEAM_LEAVE_BIND_TIP =
    "退出后，IP人设「智能客服」中绑定的团队智能体将暂时不可用，需重新绑定；重新加入并切回该团队后可自动恢复。若智能体已被删除，请重新选择。";

export const TEAM_DISBAND_BIND_TIP =
    "解散后将回到个人空间，IP人设「智能客服」中绑定的团队智能体将不可用，需重新绑定。";
