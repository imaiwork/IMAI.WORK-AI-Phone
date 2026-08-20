import request from "@/utils/request";

// 设备列表
export function getDeviceLists(params: any) {
    return request.get({ url: "/sv.device/lists", params });
}

// 删除设备
export function deleteDevice(params: any) {
    return request.post({ url: "/sv.device/remove", data: params });
}

// 可兑换 CDK 列表
export function getAvailableCodesLists(params: {
    device_id: number | string;
    page_no?: number;
    page_size?: number;
    page_type?: number | string;
    code?: string;
}) {
    return request.get({ url: "/sv.device/availableCDKLists", params });
}

// 激活设备
export function redeemDevice(data: { device_id: number | string; cdk_id: number | string }) {
    return request.post({ url: "/sv.device/redeem", data });
}

// 设备转移用户
export function deviceTransfer(data: { device_id: number | string; to_user_id: number | string }) {
    return request.post({ url: "/sv.device/deviceTransfer", data });
}
