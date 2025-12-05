import request from "@/utils/request";

// 账号列表
export const getAccountList = (params: any) => {
    return request.get({ url: "/sv.account/lists", params });
};
