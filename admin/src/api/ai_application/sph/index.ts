import request from "@/utils/request";

// 获取扣费记录
export const getCostRecord = (params: any) => request.get({ url: "/sv.crawlingTask/lists", params });
