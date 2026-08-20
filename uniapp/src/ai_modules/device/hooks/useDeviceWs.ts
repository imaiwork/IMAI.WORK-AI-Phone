import useWebSocket, { WebSocketOptions } from "@/hooks/useWebSocket";
import { DeviceCmdEnum, DeviceCmdCodeEnum, DeviceWsMessage } from "@/enums/appEnums";
import { useUserStore } from "@/stores/user";
import config from "@/config";
import { isJson } from "@/utils/util";

// 应用版本
const APP_VERSION = config.version;

type DeviceWsEvent = "open" | "message" | "close" | "success" | "error";

type DeviceWsEventCallback<T = unknown> = (data: T) => void;

export default function useDeviceWs(options?: WebSocketOptions) {
    const userStore = useUserStore();
    const { userInfo } = toRefs(userStore);
    const { baseUrl } = config;
    const wsUrl = `wss://${baseUrl.split("//")[1]}wss`;

    const {
        socket,
        on,
        send: wsSend,
        isConnected,
        reconnect,
        close,
    } = useWebSocket(wsUrl, {
        ...options,
    });

    /** 当前连接已成功 bind 的 userId，避免无 userId 绑失败后收不到推送 */
    const boundUserId = ref(0);

    // 事件触发器
    const triggerEvent = <D = { error: string; code: DeviceCmdCodeEnum }>(event: DeviceWsEvent, data?: D) => {
        const handler = eventHandlers.get(event);
        if (handler) handler(data!);
    };

    // 事件处理器
    const eventHandlers = new Map<DeviceWsEvent, DeviceWsEventCallback>();

    // 监听事件
    const onEvent = <D = unknown>(event: DeviceWsEvent, callback: DeviceWsEventCallback<unknown>) => {
        eventHandlers.set(event, callback as DeviceWsEventCallback<unknown>);
    };

    const getUserId = () => Number(userInfo.value?.id || 0);

    // 重新定义send事件，需要添加而外参数
    const send = (data: any) => {
        if (!isConnected.value) {
            reconnect();
            // uni.$u.toast(DeviceWsMessage[DeviceCmdCodeEnum.CONNECT_ERROR]);
            // return;
        }
        const { appType } = data;
        return wsSend({
            type: data.type,
            content: {
                ...data.content,
                userId: getUserId() || userInfo.value?.id,
                deviceId: data.deviceId || undefined,
                accountType: data.accountType,
            },
            deviceId: data.deviceId || "",
            messageId: Date.now(),
            appVersion: APP_VERSION,
            appType,
        });
    };

    /** 有有效 userId 且已连接时才 bind；用户信息晚到时会补绑 */
    const bindSocket = () => {
        const userId = getUserId();
        if (!isConnected.value || !userId) return;
        if (boundUserId.value === userId) return;

        send({
            type: DeviceCmdEnum.BIND_WS,
            content: {
                type: DeviceCmdEnum.BIND_WS,
                sourceType: "mprog",
            },
        });
        boundUserId.value = userId;
    };

    // 监听连接事件
    on("open", () => {
        boundUserId.value = 0;
        triggerEvent("open");
        bindSocket();
    });

    // 用户信息接口晚于 WS 连接时，补发 bindSocket
    watch(
        () => getUserId(),
        (userId) => {
            if (userId && isConnected.value) {
                bindSocket();
            }
        },
    );

    // 监听关闭事件
    on("close", () => {
        boundUserId.value = 0;
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

        // 进度指令可能把业务 code 放在 content 内。外层常是 200（投递成功），
        // 账号已被占用等失败在 content.code（如 4012），不能被外层 200 盖掉。
        const innerCode = content && typeof content === "object" ? content.code : undefined;
        const isBizFail =
            innerCode != null &&
            innerCode != DeviceCmdCodeEnum.SUCCESS &&
            innerCode != DeviceCmdCodeEnum.INIT_COMPLETE &&
            innerCode != DeviceCmdCodeEnum.CHECK_INIT;
        const resolvedCode = isBizFail ? innerCode : (code ?? innerCode);
        const isSuccess =
            resolvedCode == DeviceCmdCodeEnum.SUCCESS ||
            resolvedCode == DeviceCmdCodeEnum.INIT_COMPLETE ||
            resolvedCode == DeviceCmdCodeEnum.CHECK_INIT;

        if (isSuccess) {
            triggerEvent("success", {
                ...data,
                content,
            });
        } else {
            if (type == "pong") return;
            triggerEvent("error", {
                error: content?.msg || DeviceWsMessage[DeviceCmdCodeEnum.PUSH_MESSAGE_ERROR],
                type,
                code: resolvedCode,
                content,
                appType: data.appType,
                deviceCode: deviceId,
            });
        }
    });

    return {
        socket,
        on,
        send,
        reconnect,
        isConnected,
        onEvent,
        close,
    };
}
