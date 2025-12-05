const formData = ref<any>({});

type CreateFormEvent = "update:formData";

type CreateFormEventCallback<D = unknown> = (data: D) => void;

// 事件触发器
const triggerEvent = <D = any>(event: CreateFormEvent, data?: D) => {
    const handler = eventHandlers.get(event);
    if (handler) handler(data!);
};

// 事件处理器
const eventHandlers = new Map<CreateFormEvent, CreateFormEventCallback>();

// 监听事件
const onEvent = <D = unknown>(event: CreateFormEvent, callback: CreateFormEventCallback<D>) => {
    eventHandlers.set(event, callback);
};

export default function useCreateForm() {
    const setFormData = (data: any) => {
        formData.value = data;
        triggerEvent("update:formData", data);
    };
    return {
        formData,
        setFormData,
        onEvent,
    };
}
