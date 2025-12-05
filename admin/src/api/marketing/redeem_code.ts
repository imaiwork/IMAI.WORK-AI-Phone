import request from "@/utils/request";

//卡密列表
export function cardcodeLists(params: any) {
    return request.get({ url: "/cardcode.cardCode/lists", params }, { ignoreCancelToken: true });
}

//套餐列表
export function cardcodePackageLists() {
    return request.get({ url: "/cardcode.cardCode/getPackageList" });
}

export type CardCodeFormType = {
    type: number; // 是  类型
    card_num: string; // 是  卡密数量
    valid_start_time: string; // 是  有效开始时间
    valid_end_time: string; // 是  有效结束时间
    remark: string; // 否  备注
    rule_type: number; // 是 生成规则
    balance: string; // 否  电力值
};

//卡密新增
export function cardcodeAdd(params: CardCodeFormType) {
    return request.post({ url: "/cardcode.cardCode/add", params });
}

//卡密删除
export function cardcodeDel(params: { id: number }) {
    return request.post({ url: "/cardcode.cardCode/del", params });
}

//卡密使用详情
export function cardcodeDetails(params: { id: number }) {
    return request.get({ url: "/cardcode.cardCode/detail", params });
}

//卡密记录列表
export function cardcodeRecordLists(params: any) {
    return request.get({ url: "/cardcode.cardCodeRecord/lists", params }, { ignoreCancelToken: true });
}

//卡密设置
export function cardcodeConfigGet() {
    return request.get({ url: "/cardcode.cardCode/getConfig" });
}

//获取卡密设置
export function cardcodeConfigSet(params: any) {
    return request.post({ url: "/cardcode.cardCode/setConfig", params });
}
