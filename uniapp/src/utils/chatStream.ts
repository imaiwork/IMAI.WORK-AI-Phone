import { RequestCodeEnum } from "@/enums/requestEnums";

/** 从流式/接口异常中解析可展示文案 */
export const resolveChatErrorMessage = (error: any, fallback = "发生错误"): string => {
    if (error?.errno === RequestCodeEnum.ABORT) {
        return "用户已停止内容生成";
    }
    if (typeof error === "string" && error) return error;
    if (error?.msg) return String(error.msg);
    if (error?.errMsg) return String(error.errMsg);
    return fallback;
};

/** 流式通道误推了业务失败 JSON（非 SSE data）时取出错误文案 */
export const parseChatStreamErrorPayload = (data: any): string => {
    if (!data || typeof data !== "object") return "";
    if (typeof data.code === "undefined") return "";
    if (data.code === RequestCodeEnum.SUCCESS) return "";
    return String(data.msg || "发生错误");
};
