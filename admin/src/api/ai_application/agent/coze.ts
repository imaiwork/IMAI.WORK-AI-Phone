import request from "@/utils/request";

// coze智能体列表
export function getCozeAgentList(params: any) {
    return request.get({ url: "/coze.cozeAgent/lists", params });
}

// coze智能体详情
export function getCozeAgentDetail(params: any) {
    return request.get({ url: "/coze.cozeAgent/detail", params });
}

// coze智能体新增
export function cozeAgentAdd(params: any) {
    return request.post({ url: "/coze.cozeAgent/add", params });
}

// coze智能体更新
export function cozeAgentUpdate(params: any) {
    return request.post({ url: "/coze.cozeAgent/update", params });
}

// coze智能体删除
export function cozeAgentDelete(params: any) {
    return request.post({ url: "/coze.cozeAgent/delete", params });
}

// coze智能体配置详情
export function getCozeAgentConfig() {
    return request.get({ url: "/coze.cozeConfig/detail" });
}

// coze智能体配置添加
export function cozeConfigAdd(params: any) {
    return request.post({ url: "/coze.cozeConfig/add", data: params });
}

// coze智能体配置修改
export function cozeConfigUpdate(params: any) {
    return request.post({ url: "/coze.cozeConfig/update", data: params });
}

// coze智能体记录列表
export function getCozeAgentRecordList(params: any) {
    return request.get({ url: "/coze.cozeLog/lists", params });
}

// coze智能体记录删除
export function cozeAgentRecordDelete(params: any) {
    return request.post({ url: "/coze.cozeLog/delete", params });
}

// 查看coze智能体记录回复
export function getCozeAgentRecordReply(params: any) {
    return request.get({ url: "/coze.cozeLog/reply", params });
}
