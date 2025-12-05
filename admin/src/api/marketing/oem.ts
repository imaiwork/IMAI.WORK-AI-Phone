import request from "@/utils/request";

// 获取站点OEM信息
export const getSiteOemInfo = async () => {
    return request.get({ url: "/oem.oem/getInfo" });
};

// 获取OEM列表
export const getOemList = async (params: any) => {
    return request.get({ url: "/oem.oem/lists", params });
};

// OEM 添加
export const addOem = async (params: any) => {
    return request.post({ url: "/oem.oem/add", params });
};

// OEM 编辑
export const editOem = async (params: any) => {
    return request.post({ url: "/oem.oem/edit", params });
};

// OEM 删除
export const deleteOem = async (params: any) => {
    return request.post({ url: "/oem.oem/delete", params });
};

// OEM 状态修改
export const changeOemStatus = async (params: any) => {
    return request.post({ url: "/oem.oem/changeStatus", params });
};
