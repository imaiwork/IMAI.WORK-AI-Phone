import request from "@/utils/request";

export function getUserCenter(header?: any) {
    return request.get({ url: "/user/center", header });
}

// 个人信息
export function getUserInfo() {
    return request.get({ url: "/user/info" }, { isAuth: true });
}

// 个人编辑
export function userEdit(data: any) {
    return request.post({ url: "/user/setInfo", data }, { isAuth: true });
}

// 绑定手机
export function userBindMobile(data: any, header?: any) {
    return request.post({ url: "/user/bindMobile", data, header }, { isAuth: true });
}

// 微信电话
export function userMnpMobile(data: any, header?: any) {
    return request.post({ url: "/user/getMobileByMnp", data, header }, { isAuth: true });
}

// 更改手机号
export function userChangePwd(data: any) {
    return request.post({ url: "/user/changePassword", data }, { isAuth: true });
}

//忘记密码
export function forgotPassword(data: Record<string, any>) {
    return request.post({ url: "/user/resetPassword", data });
}

//余额明细
export function accountLog(data: any) {
    return request.get({ url: "/account_log/lists", data });
}

export function feedbackPost(data: any) {
    return request.post({ url: "/feedback/add", data });
}
//注销账号
export function cancelled(data?: any) {
    return request.post({ url: "/login/cancelled", data });
}
// 小程序绑定微信
export const apiBindwx = (params: any, header?: any) =>
    request.post({ url: "/login/mnpAuthBind", data: params, header }, { isAuth: true });

// 公众号绑定
export function OaAuthBind(data: Record<string, any>) {
    return request.post({ url: "/login/oaAuthBind", data });
}

// 绑定上级
export function bindUser(data: any, token: string) {
    return request.post({
        url: "/user/bindUser",
        data: { ...data, terminal: 4 },
        header: { token },
    });
}

// 订单列表
export function userOrderLists(data: any) {
    return request.get({ url: "/Recharge/lists", data });
}

// 激活key
export function userBindKey(data: any) {
    return request.post({ url: "/user/keyActivation", data });
}

// 点击分享链接
export function userShareLink(data: any) {
    return request.get({
        url: "/user/getShareUrl",
        data: { ...data, terminal: 4 },
    });
}

// 我的团队
export function userGroupUserList(data: any) {
    return request.get({ url: "/user/getGroupUserList", data });
}

//  获取团队key列表
export function getTeamKeyLists() {
    return request.get({ url: "/user/getKeyList" });
}

// 利润明细
export function userProfitLists(data: any) {
    return request.get({ url: "/AccountLog/lists", data });
}

// 获取tokens消耗配置
export function getTokensConfig() {
    return request.get({ url: "/user/getModelConfigList" });
}

// 获取RPA二维码
export function getRpaQrcode() {
    return request.get({ url: "/user/getDeviceBindCode" });
}

// 重新生成RPA二维码
export function getRpaQrcodeStatus() {
    return request.post({ url: "/user/getDeviceBindStatus" });
}
