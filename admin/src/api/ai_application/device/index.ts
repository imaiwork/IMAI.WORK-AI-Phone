import request from "@/utils/request";

// 设备列表
export function getDeviceLists(params: any) {
    return request.get({ url: "/sv.device/lists", params });
}

// 删除设备
export function deleteDevice(params: any) {
    return request.post({ url: "/sv.device/remove", data: params });
}
