import request from "@/utils/request";

// 获取代理等级配置
export const getAgentGradeConfig = () => {
    return request.post({ url: "/setting.distributionAgentConfig/getConfig" });
};

// 设置代理等级配置
export const setAgentGradeConfig = (params: any) => {
    return request.post({ url: "/setting.distributionAgentConfig/setConfig", params });
};

// 代理用户详情
export const getAgentUserDetail = (params: any) => {
    return request.get({ url: "/distributionAgent.distributionAgentUser/detail", params });
};

// 代理用户下级列表
export const getAgentUserLowerList = (params: any) => {
    return request.get({ url: "/distributionAgent.distributionAgentUser/subLists", params });
};

// 获取代理套餐列表
export const getAgentPackageList = (params: any) => {
    return request.get({ url: "/cardcode.cardPackage/lists", params });
};

// 新增代理套餐
export const addAgentPackage = (params: any) => {
    return request.post({ url: "/cardcode.cardPackage/add", params });
};

// 编辑代理套餐
export const editAgentPackage = (params: any) => {
    return request.post({ url: "/cardcode.cardPackage/edit", params });
};

// 删除代理套餐
export const deleteAgentPackage = (params: any) => {
    return request.post({ url: "/cardcode.cardPackage/delete", params });
};
