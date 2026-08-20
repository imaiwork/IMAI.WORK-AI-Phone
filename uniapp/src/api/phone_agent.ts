import request from "@/utils/request";

export interface PhoneAgentTask {
    id: number;
    task_id: string;
    user_id: number;
    device_code: string;
    message: string;
    execution_message?: string;
    plan_display?: string;
    plan_status?: string;
    analyze_model?: string;
    model: string;
    status: string;
    current_turn: number;
    error_msg: string;
    started_at: number;
    finished_at: number;
    create_time: string;
    update_time: string;
    conversation_id?: string;
    message_id?: string;
    reply_mode?: string;
    last_message?: string;
}

export interface PhoneAgentEvent {
    id: number;
    task_id: string;
    device_code: string;
    event_type: string;
    event_data: any;
    create_time: string;
    update_time: string;
}

export interface PhoneAgentMessage {
    id: string;
    role: string;
    type: string;
    content: string;
    status?: string;
    turn_no?: number;
    action_no?: number;
    create_time?: number | string;
    extra?: Record<string, any>;
}

export interface PhoneAgentConversation {
    id: number;
    conversation_id: string;
    title: string;
    last_message: string;
    last_task_id: string;
    task_count: number;
    last_task_status: string;
    status_text: string;
    device_code: string;
    device_name?: string;
    device_model?: string;
    devices?: Array<{
        device_code: string;
        device_name?: string;
        device_model?: string;
    }>;
    device_count?: number;
    create_time: number | string;
    update_time: number | string;
}

export const dispatchPhoneAgentTask = (data: { message: string; device_code: string; model?: string }) => {
    return request.post<PhoneAgentTask>({ url: "/phoneAgent.PhoneAgent/dispatch", data });
};

export const getPhoneAgentTaskDetail = (data: { task_id: string }) => {
    return request.get({ url: "/phoneAgent.PhoneAgent/detail", data });
};

export const getPhoneAgentConversationDetail = (data: { conversation_id: string }) => {
    return request.get({ url: "/phoneAgent.PhoneAgent/conversationDetail", data });
};

export const getPhoneAgentTaskHistory = (data: { page_no: number; page_size: number }) => {
    return request.get<{ lists: PhoneAgentConversation[]; count: number }>({
        url: "/phoneAgent.PhoneAgent/history",
        data,
    });
};

export const getPhoneAgentTaskEvents = (data: { task_id: string; last_id?: number | string }) => {
    return request.get({ url: "/phoneAgent.PhoneAgent/events", data });
};

export const cancelPhoneAgentTask = (data: { task_id: string }) => {
    return request.post<PhoneAgentTask>({ url: "/phoneAgent.PhoneAgent/cancel", data });
};

export const deletePhoneAgentConversation = (data: { conversation_id: string }) => {
    return request.post({ url: "/phoneAgent.PhoneAgent/deleteConversation", data });
};
