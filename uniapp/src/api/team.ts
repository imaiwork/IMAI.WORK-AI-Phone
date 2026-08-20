import request from "@/utils/request";

/** 当前用户团队信息 */
export function getTeamInfo() {
    return request.get({ url: "/team/info" }, { ignoreCancel: true });
}

/** 开通团队(成为团队主) */
export function createTeam(data: { name: string }) {
    return request.post({ url: "/team/create", data });
}

/** 通过邀请码加入团队 */
export function joinTeam(data: { code: string }) {
    return request.post({ url: "/team/join", data });
}

/** 我加入/创建的全部企业 */
export function getMyTeams() {
    return request.get({ url: "/team/myTeams" });
}

/** 切换当前企业(team_id=0 切回个人空间) */
export function switchTeam(data: { team_id: number }) {
    return request.post({ url: "/team/switchTeam", data });
}

/** 团队主/管理员生成邀请码 */
export function inviteTeamMember(data: { max_uses?: number; expire_time?: number } = {}) {
    return request.post({ url: "/team/invite", data });
}

/** 团队主修改团队名称 */
export function setTeamName(data: { name: string }) {
    return request.post({ url: "/team/setName", data });
}

/** 成员退出当前团队 */
export function leaveTeam() {
    return request.post({ url: "/team/leave" });
}

/** 团队主解散团队 */
export function disbandTeam() {
    return request.post({ url: "/team/disband" });
}

/** 团队成员列表(分页,支持 keyword / team_role) */
export function getTeamMembers(data?: { page_no?: number; page_size?: number; keyword?: string; team_role?: number }) {
    return request.get({ url: "/team/members", data }, { ignoreCancel: true });
}

/** 超管修改成员角色(1=成员 3=管理员) */
export function setMemberRole(data: { user_id: number; role: number }) {
    return request.post({ url: "/team/setMemberRole", data });
}

/** 设置成员到期时间(expire 为 unix 秒，0=永久) */
export function setMemberExpire(data: { user_id: number; expire: number }) {
    return request.post({ url: "/team/setMemberExpire", data });
}

/** 修改成员企业算力(设为目标值) */
export function setMemberTokens(data: { user_id: number; tokens: number }) {
    return request.post({ url: "/team/setMemberTokens", data });
}

/** 移出团队成员 */
export function removeTeamMember(data: { user_id: number }) {
    return request.post({ url: "/team/removeMember", data });
}

/** 成员下拉选项(全量,消耗明细筛选用) */
export function getMemberOptions() {
    return request.get({ url: "/team/memberOptions" }, { ignoreCancel: true });
}

/** 企业算力消耗明细(全员合集; range=today|7d|30d|month) */
export function getTeamConsumption(data?: any) {
    return request.get({ url: "/team/consumption", data }, { ignoreCancel: true });
}

/** 某成员算力明细(创始人/管理员) */
export function getMemberConsumption(data: { user_id: number; page_no?: number; page_size?: number }) {
    return request.get({ url: "/team/memberConsumption", data }, { ignoreCancel: true });
}

/** 某条消耗记录的产出结果 */
export function getConsumptionOutput(data: { log_id: number }) {
    return request.get({ url: "/team/consumptionOutput", data }, { ignoreCancel: true });
}
