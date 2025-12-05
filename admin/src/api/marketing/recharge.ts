import request from "@/utils/request";
//获取充值套餐配置
export function getRechargeConfig() {
    return request.get({ url: "/recharge.package/getConfig" });
}
//设置充值套餐配置
export function setRechargeConfig(params: any) {
    return request.post({ url: "/recharge.package/setConfig", params });
}

// 充值套餐列表
export function getRechargeLists(params: any) {
    return request.get({ url: "/recharge.GiftPackage/lists", params });
}

// 添加充值套餐
export function rechargeAdd(params: any) {
    return request.post({ url: "/recharge.GiftPackage/add", params });
}
// 编辑充值套餐
export function rechargeEdit(params: any) {
    return request.post({ url: "/recharge.GiftPackage/edit", params });
}

// 删除充值套餐
export function rechargeDelete(params: any) {
    return request.get({ url: "/recharge.GiftPackage/delete", params });
}

// 充值套餐详情
export function getRechargeDetail(params: any) {
    return request.get({ url: "/recharge.GiftPackage/detail", params });
}

// 修改套餐状态
export function rechargeStatus(params: any) {
    return request.post({ url: "/recharge.package/status", params });
}

// 兑换CDK
export function rechargeCDK(params: any) {
    return request.post({ url: "/recharge.CDK/cdkExchangeTokens", params });
}

// 秘钥修改
export function rechargeSecret(params: any) {
    return request.post({ url: "/recharge.CDK/cdkReplaceAuth", params });
}

// 充值设置
export function rechargeSettingConfig(params: any) {
    return request.post({ url: "/recharge.recharge/setConfig", params });
}

// 获取充值设置
export function getRechargeSettingConfig() {
    return request.get({ url: "/recharge.recharge/getConfig" });
}
