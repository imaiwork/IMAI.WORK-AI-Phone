import { getClient } from "@/utils/env";

// 登录
export function login(params: any) {
    return $request.post({
        url: "/login/account",
        params: { ...params, terminal: getClient() },
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
        params: { ...params, channel: getClient() },
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
