/**
 * @param {string} path
 * @returns {Boolean}
 */
export function isExternal(path: string) {
    return /^(https?:|mailto:|tel:)/.test(path);
}

// 验证手机号码
export function validateMobile(mobile: string) {
    return /^(13[0-9]|14[01456879]|15[0-35-9]|16[2567]|17[0-8]|18[0-9]|19[0-35-9])\d{8}$/.test(mobile);
}
