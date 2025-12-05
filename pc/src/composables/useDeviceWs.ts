import useWebSocket, { WebSocketOptions } from "@/composables/useWebSocket";
import { DeviceCmdEnum, DeviceCmdCodeEnum, DeviceWsMessage } from "@/enums/appEnums";
import { useUserStore } from "@/stores/user";
// 应用版本
const APP_VERSION = "1.0.0";

type DeviceWsEvent = "open" | "message" | "close" | "success" | "error";

type DeviceWsEventCallback<T = unknown> = (data: T) => void;

export default function useDeviceWs(options?: WebSocketOptions) {
    const userStore = useUserStore();

    const { userInfo } = toRefs(userStore);
    const wsUrl = `wss://${location.host}/wss`;

    const {
        on,
        send: wsSend,
        isConnected,
        reconnect,
    } = useWebSocket(wsUrl, {
        ...options,
    });

    // 事件触发器
    const triggerEvent = <D = { error: string; code: DeviceCmdCodeEnum }>(event: DeviceWsEvent, data?: D) => {
        const handler = eventHandlers.get(event);
        if (handler) handler(data!);
    };

    // 事件处理器
    const eventHandlers = new Map<DeviceWsEvent, DeviceWsEventCallback>();

    // 监听事件
    const onEvent = <D = unknown>(event: DeviceWsEvent, callback: DeviceWsEventCallback<D>) => {
        eventHandlers.set(event, callback);
    };

    // 重新定义send事件，需要添加而外参数
    const send = (data: any) => {
        if (!isConnected.value) {
            feedback.msgError(DeviceWsMessage[DeviceCmdCodeEnum.CONNECT_ERROR]);
            return;
        }
        const { appType } = data;
        return wsSend({
            type: data.type,
            content: {
                ...data.content,
                userId: userInfo.value.id,
                deviceId: data.deviceId || undefined,
                accountType: data.accountType,
            },
            deviceId: data.deviceId || "",
            messageId: Date.now(),
            appVersion: APP_VERSION,
            appType,
        });
    };

    // 监听连接事件
    on("open", () => {
        triggerEvent("open");
        // 绑定Ws
        send({
            type: DeviceCmdEnum.BIND_WS,
            content: {
                type: DeviceCmdEnum.BIND_WS,
                sourceType: "pc",
            },
        });
    });

    // 监听关闭事件
    on("close", () => {
        triggerEvent("close", {
            error: DeviceWsMessage[DeviceCmdCodeEnum.CONNECT_ERROR],
            code: DeviceCmdCodeEnum.CONNECT_ERROR,
        });
    });

    // 监听错误事件
    on("error", (error: any) => {
        triggerEvent("error", error);
    });

    // 监听消息事件
    on("message", (data: any) => {
        let { type, code, content, deviceId } = data;
        // 判断 content 是不是json格式
        content = isJson(content) ? JSON.parse(content) : content;

        if (code == DeviceCmdCodeEnum.SUCCESS) {
            switch (code) {
                case DeviceCmdCodeEnum.SUCCESS:
                case DeviceCmdCodeEnum.INIT_COMPLETE:
                case DeviceCmdCodeEnum.CHECK_INIT:
                case DeviceCmdCodeEnum.DEVICE_ONLINE:
                    triggerEvent("success", {
                        ...data,
                        content,
                    });
                    break;
                default:
                    triggerEvent("error", {
                        error: content.msg,
                        code: DeviceCmdCodeEnum.PUSH_MESSAGE_ERROR,
                        deviceCode: deviceId,
                    });
                    break;
            }
        } else {
            triggerEvent("error", {
                error: content.msg,
                type,
                code,
                deviceCode: deviceId,
            });
        }
    });

    return {
        on,
        send,
        reconnect,
        isConnected,
        onEvent,
    };
}
