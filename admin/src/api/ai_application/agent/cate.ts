import request from "@/utils/request";

// 获取分类列表
export function getCateList(params: any) {
    return request.get({ url: "/agent.agentCate/lists", params });
}

// 获取分类详情
export function getCateDetail(params: any) {
    return request.get({ url: "/agent.agentCate/detail", params });
}

// 添加分类
export function addCate(data: any) {
    return request.post({ url: "/agent.agentCate/add", data });
}

// 编辑分类
export function editCate(data: any) {
    return request.post({ url: "/agent.agentCate/update", data });
}

// 删除分类
export function deleteCate(data: any) {
    return request.post({ url: "/agent.agentCate/delete", data });
}

// 修改状态
export function changeStatus(data: any) {
    return request.post({ url: "/agent.agentCate/changeStatus", data });
}
