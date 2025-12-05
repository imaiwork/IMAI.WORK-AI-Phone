import request from "@/utils/request";

export const getLiveSetting = () => {
    return request.get({
        url: "/ai_application/live/setting",
    });
};
