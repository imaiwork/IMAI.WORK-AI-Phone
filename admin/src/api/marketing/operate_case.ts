import request from "@/utils/request";

// 获取运营案例列表
export function getOperateCaseList(data: any) {
    return request.get({ url: "/catering.cateringFranchise/lists", params: data });
}

// 添加运营案例
export function addOperateCase(data: any) {
    return request.post({ url: "/catering.cateringFranchise/add", data });
}

// 编辑运营案例
export function editOperateCase(data: any) {
    return request.post({ url: "/catering.cateringFranchise/edit", data });
}

// 删除运营案例
export function deleteOperateCase(data: any) {
    return request.post({ url: "/catering.cateringFranchise/delete", params: data });
}

// 获取运营案例详情
export function getOperateCaseDetail(data: any) {
    return request.get({ url: "/catering.cateringFranchise/detail", params: data });
}
