import request from '@/utils/request'

export function getUserCenter(header?: any) {
    return request.get({ url: '/user/center', header })
}

// 个人信息
export function getUserInfo() {
    return request.get({ url: '/user/info' }, { isAuth: true })
}

// 会员等级 + 配额 + 用量(会员订阅弹窗)
export function getMemberQuota() {
    return request.get({ url: '/user/memberQuota' })
}

// 个人编辑
export function userEdit(data: any) {
    return request.post({ url: '/user/setInfo', data }, { isAuth: true })
}

// 绑定手机
export function userBindMobile(data: any, header?: any) {
    return request.post({ url: '/user/bindMobile', data, header }, { isAuth: true })
}

// 微信电话
export function userMnpMobile(data: any, header?: any) {
    return request.post({ url: '/user/getMobileByMnp', data, header }, { isAuth: true })
}

// 更改手机号
export function userChangePwd(data: any) {
    return request.post({ url: '/user/changePassword', data }, { isAuth: true })
}

//忘记密码
export function forgotPassword(data: Record<string, any>) {
    return request.post({ url: '/user/resetPassword', data })
}

//余额明细
export function accountLog(data: any) {
    return request.get({ url: '/account_log/lists', data })
}

export function feedbackPost(data: any) {
    return request.post({ url: '/feedback/add', data })
}
//注销账号
export function cancelled(data?: any) {
    return request.post({ url: '/login/cancelled', data })
}
// 小程序绑定微信
export const apiBindwx = (params: any, header?: any) =>
    request.post({ url: '/login/mnpAuthBind', data: params, header }, { isAuth: true })

// 公众号绑定
export function OaAuthBind(data: Record<string, any>) {
    return request.post({ url: '/login/oaAuthBind', data })
}

// 绑定上级
export function bindUser(data: any, token: string) {
    return request.post({
        url: '/user/bindUser',
        data: { ...data, terminal: 4 },
        header: { token }
    })
}

// 订单列表
export function userOrderLists(data: any) {
    return request.get({ url: '/Recharge/lists', data })
}

// 激活key
export function userBindKey(data: any) {
    return request.post({ url: '/user/keyActivation', data })
}

// 点击分享链接
export function userShareLink(data: any) {
    return request.get({
        url: '/user/getShareUrl',
        data: { ...data, terminal: 4 }
    })
}

// 我的团队
export function userGroupUserList(data: any) {
    return request.get({ url: '/user/getGroupUserList', data })
}

//  获取团队key列表
export function getTeamKeyLists() {
    return request.get({ url: '/user/getKeyList' })
}

// 利润明细
export function userProfitLists(data: any) {
    return request.get({ url: '/AccountLog/lists', data })
}

// 获取tokens消耗配置
export function getTokensConfig() {
    return request.get({ url: '/user/getModelConfigList' })
}

// 获取RPA二维码
export function getRpaQrcode() {
    return request.get({ url: '/user/getDeviceBindCode' })
}

// 重新生成RPA二维码
export function getRpaQrcodeStatus() {
    return request.post({ url: '/user/getDeviceBindStatus' })
}

// 获取代理下级列表，传直属下级的 user_id 可查看该下级的下级
export function getAgentSubList(data: any) {
    return request.get({ url: '/distributionAgent.distributionAgent/subLists', data })
}

// 获取下级充值流水明细
export function getAgentSubRechargeList(data: any) {
    return request.get({ url: '/distributionAgent.distributionAgent/rechargeLists', data })
}

// 获取下级概要（基本信息 + 本人充值业绩）
export function getAgentSubSummary(data: any) {
    return request.get({ url: '/distributionAgent.distributionAgent/subSummary', data })
}

// 获取代理卡密列表
export function getAgentCardList(data: any) {
    return request.get({ url: '/distributionAgent.distributionAgentCard/lists', data })
}

// 生成卡密
export function generateAgentCard(data: any) {
    return request.post({ url: '/distributionAgent.distributionAgentCard/generate', data })
}

// 删除卡密
export function deleteAgentCard(data: any) {
    return request.post({ url: '/distributionAgent.DistributionAgentCard/delete', data })
}

// 获取卡密套餐
export function getAgentCardPackageList(data: any) {
    return request.get({ url: '/distributionAgent.DistributionAgentCard/packages', data })
}

// 设置代理用户联系二维码
export function setAgentUserContactQrcode(data: any) {
    return request.post({ url: '/distributionAgent.DistributionAgent/setQrCode', data })
}

// 获取代理用户信息
export function getAgentUserInfo() {
    return request.get({ url: '/distributionAgent.DistributionAgent/info' })
}

// 获取代理用户上级二维码
export function getAgentUserParentQrcode() {
    return request.get({ url: '/distributionAgent.DistributionAgent/getSuperiorQrCode' })
}

// 获取代理分享二维码
export function getAgentUserShareQrcode(data: any) {
    return request.get({ url: '/distributionAgent.DistributionAgent/getBindMnpCode', data })
}

// 赠送算力
export function agentGiftTokens(data: any) {
    return request.post({ url: '/distributionAgent.DistributionAgent/giftTokens', data })
}

// 设置代理等级
export function setAgentLevel(data: any) {
    return request.post({ url: '/distributionAgent.DistributionAgent/setLevel', data })
}

// 移除代理用户
export function deleteAgentSub(data: any) {
    return request.post({ url: '/distributionAgent.DistributionAgent/removeSub', data })
}

// 获取代理等级
export function getAgentLevel() {
    return request.get({ url: '/distributionAgent.DistributionAgent/getAgentConfig' })
}
