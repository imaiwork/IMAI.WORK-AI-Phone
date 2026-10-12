import { getClient } from "@/utils/env";

/** PC 终端。环境变量 NUXT_CLIENT 未配置时回退，避免序列化丢掉空值。 */
const DEFAULT_CLIENT = "4";

function resolveClient() {
    return getClient() || DEFAULT_CLIENT;
}

// 公开:获取注册模式 + 默认邀请来源(决定登录弹窗是否显示邀请码输入框)
export function getRegisterMode() {
    return $request.get({ url: "/login/registerMode" });
}

// 登录
export function login(params: any) {
    return $request.post({
        url: "/login/account",
        params: { ...params, terminal: resolveClient() },
    });
}
// 登录
export function logout() {
    return $request.post({ url: "/login/logout" });
}

//注册
export function register(params: any) {
    return $request.post({
        url: "/login/register",
        params: { ...params, channel: resolveClient() },
    });
}

//向微信请求code的链接
export function getWxCode(params?: any) {
    return $request.get({
        url: "/login/qrcode",
        params,
    });
}

export function checkTicket(params?: any) {
    return $request.post({
        url: "/login/ticket",
        params,
    });
}

export function wxLogin(params: any) {
    return $request.post({ url: "/login/scanLogin", params });
}

//发送邮箱验证码
export function sendEmailCode(params: any) {
    return $request.post({ url: "/email/sendCode", params });
}

//获取图形验证码
export function captcha() {
    return $request.get({ url: "/login/captcha" });
}

// 获取小程序扫码授权状态
export function getMnpScanStatus(params: any) {
    return $request.post({ url: "/login/mnpAuthStatus", params });
}
