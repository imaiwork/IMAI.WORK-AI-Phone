import request from "@/utils/request";

// 获取微信列表
export const getWeChatLists = (data: any) => {
    return request.get({ url: "/wechat.wechat/lists", data });
};
