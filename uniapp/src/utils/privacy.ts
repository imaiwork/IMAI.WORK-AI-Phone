/**
 * 微信小程序隐私协议闸门。
 *
 * manifest 的 mp-weixin.__usePrivacyCheck__ 已开启：调用相册/相机等隐私接口前必须先让用户
 * 同意《用户隐私保护指引》，否则原生接口直接失败，fail 回调只有一句英文：
 *   - `api scope is not declared in the privacy agreement`：后台没声明该接口对应的隐私类型
 *   - `privacy permission is not authorized`：用户还没同意
 * 用户看不懂，运营也分不清是配置问题还是用户拒绝。
 *
 * 本工具统一两件事：调接口前走官方授权弹窗；把隐私类失败归一成中文提示。
 */

/** 微信「后台未声明隐私类型」的 errno */
export const PRIVACY_ERRNO = 112;

/** 对客提示：声明缺失与用户拒绝共用一句，不把英文 errMsg 直接弹给用户 */
export const PRIVACY_TIP = "请先同意《用户隐私保护指引》";

export interface PrivacyError {
    errMsg: string;
    errno: number;
    privacy: true;
}

/** 是否隐私相关失败：errno 112 / 原生 errMsg 含 privacy / 本工具自己 reject 的标记 */
export const isPrivacyError = (err?: any): boolean => {
    if (!err) return false;
    if (err.privacy === true || err.errno === PRIVACY_ERRNO) return true;
    return /privacy/i.test(String(err.errMsg || ""));
};

export const privacyError = (): PrivacyError => ({
    errMsg: PRIVACY_TIP,
    errno: PRIVACY_ERRNO,
    privacy: true
});

/**
 * 调隐私接口前确保用户已同意隐私协议。
 * 返回 false 表示未同意（已弹过引导，调用方不要再弹通用错误）。
 * 非小程序端、或基础库 < 2.32.3 没有该 API 时直接放行。
 */
export const ensurePrivacyAuthorized = (): Promise<boolean> => {
    // #ifdef MP-WEIXIN
    return new Promise<boolean>((resolve) => {
        const wxApi = (globalThis as any)?.wx;
        if (typeof wxApi?.requirePrivacyAuthorize !== "function") {
            resolve(true);
            return;
        }
        wxApi.requirePrivacyAuthorize({
            success: () => resolve(true),
            fail: () => {
                uni.showModal({
                    title: "需要同意隐私保护指引",
                    content:
                        "选择相册或相机前需先同意《用户隐私保护指引》。若已同意仍失败，请在微信小程序后台「设置-服务内容声明」中声明「选中的照片或视频信息」。",
                    confirmText: "查看指引",
                    success: (res) => {
                        if (res.confirm && typeof wxApi?.openPrivacyContract === "function") {
                            wxApi.openPrivacyContract({});
                        }
                    }
                });
                resolve(false);
            }
        });
    });
    // #endif
    // #ifndef MP-WEIXIN
    return Promise.resolve(true);
    // #endif
};
