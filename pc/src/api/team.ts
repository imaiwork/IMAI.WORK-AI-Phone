// 团队(团队版)- 团队主/成员作用域，对接后端 /api/team/*
// $request 为全局请求实例，POST 用 params，token 自动携带

// 当前用户团队信息
export function getTeamInfo() {
    return $request.get({ url: "/team/info" }, { ignoreCancel: true });
}

// 开通团队(成为团队主)
export function createTeam(params: { name: string }) {
    return $request.post({ url: "/team/create", params });
}

// 团队主生成邀请码
export function inviteTeamMember(params: { max_uses?: number; expire_time?: number } = {}) {
    return $request.post({ url: "/team/invite", params });
}

// 通过邀请码加入团队
export function joinTeam(params: { code: string }) {
    return $request.post({ url: "/team/join", params });
}

// 团队成员列表(团队主)
export function getTeamMembers(params?: any) {
    return $request.get({ url: "/team/members", params });
}

// 成员下拉选项(全量,消耗明细筛选用)
export function getMemberOptions() {
    return $request.get({ url: "/team/memberOptions" });
}

// 团队主给团队用户划拨算力(从团队主名下划出)
export function allocateTokens(params: { user_id: number; tokens: number }) {
    return $request.post({ url: "/team/allocateTokens", params });
}

// 租户信息(品牌/小程序)
export function getTeamTenant() {
    return $request.get({ url: "/team/tenant" });
}

// 保存租户信息(品牌/小程序)
export function setTeamTenant(params: Record<string, any>) {
    return $request.post({ url: "/team/setTenant", params });
}

// 设置成员到期时间(expire 为 unix 秒，0=永久)
export function setMemberExpire(params: { user_id: number; expire: number }) {
    return $request.post({ url: "/team/setMemberExpire", params });
}

// 成员主动退团(团队主不可退)
export function leaveTeam() {
    return $request.post({ url: "/team/leave" });
}

// 团队主移除成员/解除散客归属
export function removeTeamMember(params: { user_id: number }) {
    return $request.post({ url: "/team/removeMember", params });
}

// 超管修改成员角色(1=成员 3=管理员)
export function setMemberRole(params: { user_id: number; role: number }) {
    return $request.post({ url: "/team/setMemberRole", params });
}

// 管理员及以上修改成员企业算力(设为目标值)
export function setMemberTokens(params: { user_id: number; tokens: number }) {
    return $request.post({ url: "/team/setMemberTokens", params });
}

// 团队主解散团队(企业)
export function disbandTeam() {
    return $request.post({ url: "/team/disband" });
}

// 团队主修改企业名称
export function setTeamName(params: { name: string }) {
    return $request.post({ url: "/team/setName", params });
}

// 升级企业OEM(扣算力预缴费→待站长审核)
export function upgradeOem(params: { mobile: string; code: string }) {
    return $request.post({ url: "/team/upgradeOem", params });
}

// OEM 站点归属用户列表(散客,区别于成员)
export function getAttributedUsers() {
    return $request.get({ url: "/team/attributedUsers" });
}

// 申请开通某个授权功能
export function requestFeature(params: { key: string }) {
    return $request.post({ url: "/team/requestFeature", params });
}

// 我加入/创建的全部企业(自己创建的排第一)
export function getMyTeams() {
    return $request.get({ url: "/team/myTeams" });
}

// 切换当前企业
export function switchTeam(params: { team_id: number }) {
    return $request.post({ url: "/team/switchTeam", params });
}

// 成员算力消耗明细
export function getMemberConsumption(params: { user_id: number; page_no: number; page_size: number }) {
    return $request.get({ url: "/team/memberConsumption", params });
}

// 企业算力消耗明细(全员合集; range=today|7d|30d)
export function getTeamConsumption(params: {
    user_id?: number;
    keyword?: string;
    range?: string;
    start_time?: number;
    end_time?: number;
    page_no: number;
    page_size: number;
}) {
    return $request.get({ url: "/team/consumption", params });
}

// 某条消耗记录的产出结果
export function getConsumptionOutput(params: { log_id: number }) {
    return $request.get({ url: "/team/consumptionOutput", params });
}

// 小程序当前版本号
export function getMnpVersion() {
    return $request.get({ url: "/team/mnpVersion" });
}

// 提交小程序代码到微信
export function uploadMnp(params: { upload_version: string; upload_desc: string }) {
    return $request.post({ url: "/team/uploadMnp", params });
}

// 上传小程序代码包(zip of mp-weixin)
export function uploadMnpCode(params: { file: File }, onProgress?: (percent: number) => void) {
    return $request.uploadFile(
        { url: "/team/uploadMnpCode", requestOptions: { ignoreCancel: true } },
        params,
        onProgress,
    );
}

// 团队自有卡密套餐列表(管理与制卡下拉共用)
export function getTeamCardPackages(params?: { page_no?: number; page_size?: number }) {
    return $request.get({ url: "/team.teamCard/packages", params });
}

// 新增/编辑自有套餐(名称 + 算力 + 到期时间戳，0=永久)
export function saveTeamCardPackage(params: { id?: number; name: string; tokens: number; expire_time: number }) {
    return $request.post({ url: "/team.teamCard/packageSave", params });
}

// 删除自有套餐
export function deleteTeamCardPackage(params: { id: number }) {
    return $request.post({ url: "/team.teamCard/packageDelete", params });
}

// 生成卡密(算力卡 type=5 / 会员兑换码 type=6)
export function generateTeamCard(params: Record<string, any>) {
    return $request.post({ url: "/team.teamCard/generate", params });
}

// 会员等级选项(生成会员兑换码用)
export function getTeamCardMemberLevels() {
    return $request.get({ url: "/team.teamCard/memberLevels" });
}

// 卡密列表
export function getTeamCardLists(params: {
    status?: number;
    type?: number;
    sn?: string;
    page_no: number;
    page_size: number;
}) {
    return $request.get({ url: "/team.teamCard/lists", params });
}

// 转移未使用卡密给成员
export function transferTeamCard(params: { id: number; to_user_id: number }) {
    return $request.post({ url: "/team.teamCard/transfer", params });
}

// 删除卡密
export function deleteTeamCard(params: { id: number }) {
    return $request.post({ url: "/team.teamCard/delete", params });
}

// OEM 站长调整站点用户算力
export function setSiteUserTokens(params: { user_id: number; tokens: number }) {
    return $request.post({ url: "/team/setSiteUserTokens", params });
}
