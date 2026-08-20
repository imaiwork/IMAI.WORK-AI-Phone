import request from "@/utils/request";

// 获取代理等级配置
export const getAgentGradeConfig = () => {
    return request.post({ url: "/setting.distributionAgentConfig/getConfig" });
};

// 设置代理等级配置
export const setAgentGradeConfig = (params: any) => {
    return request.post({ url: "/setting.distributionAgentConfig/setConfig", params });
};

// 添加代理等级
export const addAgentGrade = (params: any) => {
    return request.post({ url: "/setting.distributionAgentConfig/addLevel", params });
};

// 删除代理等级
export const delAgentGrade = (params: any) => {
    return request.post({ url: "/setting.distributionAgentConfig/delLevel", params });
};

// 获取下级人数上限
export const getAgentSubLimits = () => {
    return request.post({ url: "/setting.distributionAgentConfig/getSubLimits" });
};

// 设置下级人数上限
export const setAgentSubLimits = (params: any) => {
    return request.post({ url: "/setting.distributionAgentConfig/setSubLimits", params });
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
