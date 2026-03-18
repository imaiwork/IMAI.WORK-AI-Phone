import { getClient } from "@/utils/env";

export function getUserCenter(headers?: any) {
    return $request.get({ url: "/user/center", headers });
}

// 个人信息
export function getUserInfo() {
    return $request.get({ url: "/user/info" });
}

// 个人编辑
export function userEdit(params: any) {
    return $request.post({ url: "/user/setInfo", params });
}

// 绑定手机
export function userBindMobile(params: any, headers?: any) {
    return $request.post({ url: "/user/bindMobile", params, headers }, { withToken: !headers?.token });
}

// 微信电话
export function userMnpMobile(params: any) {
    return $request.post({ url: "/user/getMobileByMnp", params });
}

// 更改密码
export function userChangePwd(params: any) {
    return $request.post({ url: "/user/changePassword", params });
}

//忘记密码
export function forgotPassword(params: Record<string, any>) {
    return $request.post({ url: "/user/resetPassword", params });
}

// 获取tokens消耗配置
export function getTokensConfig() {
    return $request.get({ url: "/user/getModelConfigList" });
}

// tokens消耗记录
export function getTokensRecord(params: any) {
    return $request.get({ url: "/account_log/lists", params });
}

// 获取RPA二维码
export function getRpaQrcode() {
    return $request.get({ url: "/user/getDeviceBindCode" });
}

// 重新生成RPA二维码
export function getRpaQrcodeStatus() {
    return $request.post({ url: "/user/getDeviceBindStatus" });
}

// 获取代理下级列表
export function getAgentSubList(params: any) {
    return $request.get({ url: "/distributionAgent.distributionAgent/subLists", params });
}

// 获取代理卡密列表
export function getAgentCardList(params: any) {
    return $request.get({ url: "/distributionAgent.distributionAgentCard/lists", params });
}

// 生成卡密
export function generateAgentCard(params: any) {
    return $request.post({ url: "/distributionAgent.distributionAgentCard/generate", params });
}

// 删除卡密
export function deleteAgentCard(params: any) {
    return $request.post({ url: "/distributionAgent.DistributionAgentCard/delete", params });
}

// 获取卡密套餐
export function getAgentCardPackageList(params: any) {
    return $request.get({ url: "/distributionAgent.DistributionAgentCard/packages", params });
}

// 设置代理用户联系二维码
export function setAgentUserContactQrcode(params: any) {
    return $request.post({ url: "/distributionAgent.DistributionAgent/setQrCode", params });
}

// 获取代理用户信息
export function getAgentUserInfo() {
    return $request.get({ url: "/distributionAgent.DistributionAgent/info" });
}

// 获取代理用户上级二维码
export function getAgentUserParentQrcode() {
    return $request.get({ url: "/distributionAgent.DistributionAgent/getSuperiorQrCode" });
}

// 获取代理分享二维码
export function getAgentUserShareQrcode(params: any) {
    return $request.get({ url: "/distributionAgent.DistributionAgent/getBindMnpCode", params });
}

// 获取代理分享连接
export function getAgentUserShareLink(params: any) {
    return $request.get({ url: "/distributionAgent.DistributionAgent/getBindMnpUrl", params });
}

// 赠送算力
export function agentGiftTokens(params: any) {
    return $request.post({ url: "/distributionAgent.DistributionAgent/giftTokens", params });
}

// 设置代理等级
export function setAgentLevel(params: any) {
    return $request.post({ url: "/distributionAgent.DistributionAgent/setLevel", params });
}

// 移除代理用户
export function deleteAgentSub(params: any) {
    return $request.post({ url: "/distributionAgent.DistributionAgent/removeSub", params });
}

// 获取代理等级
export function getAgentLevel() {
    return $request.get({ url: "/distributionAgent.DistributionAgent/getAgentConfig" });
}
