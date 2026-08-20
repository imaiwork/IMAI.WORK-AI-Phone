import { ClientEnum } from "@/enums/appEnums";
import { getClient, handleClientEvent } from "./client";

/**
 * @description: 开发模式
 */
export function isDevMode(): boolean {
    return import.meta.env.DEV;
}

/**
 * @description: 生成模式
 */
export function isProdMode(): boolean {
    return import.meta.env.PROD;
}

export const isMiniProgram = (() => {
    const userAgent = navigator.userAgent;
    if (/miniProgram/i.test(userAgent) && /micromessenger/i.test(userAgent)) {
        return true;
    } else {
        return false;
    }
})();

// 由 vite define 注入：微信开发者工具（envVersion === "develop"）是否按正式版处理
declare const __DEVELOP_AS_RELEASE__: boolean;

// 是否是正式版本
export function isReleaseVersion(): boolean {
    // #ifdef MP-WEIXIN
    const { miniProgram } = wx.getAccountInfoSync();
    return (
        miniProgram.envVersion === "release" ||
        miniProgram.version != "" ||
        (__DEVELOP_AS_RELEASE__ && miniProgram.envVersion == "develop")
    );
    // #endif
    // #ifndef MP-WEIXIN
    return true;
    // #endif
}
