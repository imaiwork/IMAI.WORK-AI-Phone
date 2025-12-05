import { DeviceWsMessage, DeviceCmdCodeEnum } from "@/enums/appEnums";

type WebSocketEventCallback<T = unknown> = (data: T) => void;

export interface WebSocketOptions {
    /** 最大重连次数 (默认 5 次) */
    maxRetries?: number;
    /** 重连基础间隔 (默认 1000ms) */
    retryBaseDelay?: number;
    /** 心跳间隔 (默认 300s) */
    heartbeatInterval?: number;
    /** 心跳消息 (默认 { type: "ping" }) */
    heartbeatMessage?: Record<string, unknown>;
    /** 手动发送心跳 */
    manualHeartbeat?: boolean;
    /** 手动启动连接 (默认自动连接) */
    manual?: boolean;
}

export default function useWebSocket<T = unknown>(url: string, options: WebSocketOptions = {}) {
    // 状态管理
    const socket: Ref<WebSocket | null> = ref(null);
    const isConnected = ref(false);
    const retryCount = ref(0);

    // 配置合并
    const {
        maxRetries = 5,
        retryBaseDelay = 1000,
        heartbeatInterval = 30000,
        heartbeatMessage = { type: "ping" },
        manualHeartbeat = false,
        manual = false,
    } = options;

    // 事件回调存储
    const eventHandlers = new Map<string, WebSocketEventCallback>();

    // 定时器管理
    let reconnectTimer: NodeJS.Timeout | null = null;
    let heartbeatTimer: NodeJS.Timeout | null = null;

    // 核心连接方法
    const connect = () => {
        if (typeof window !== "undefined" && !socket.value) {
            socket.value = new WebSocket(url);

            // 连接成功
            socket.value.onopen = () => {
                isConnected.value = true;
                retryCount.value = 0;
                startHeartbeat();
                triggerEvent("open");
            };

            // 消息接收
            socket.value.onmessage = (event: MessageEvent) => {
                try {
                    const data = JSON.parse(event.data) as T;
                    triggerEvent("message", data);
                    resetHeartbeat(); // 收到消息重置心跳
                } catch (error) {
                    triggerEvent("error", {
                        error: "消息解析失败",
                        code: DeviceCmdCodeEnum.PARSE_ERROR,
                    });
                }
            };

            // 连接关闭
            socket.value.onclose = (event: CloseEvent) => {
                isConnected.value = false;
                cleanup();
                if (!event.wasClean && retryCount.value < maxRetries) {
                    scheduleReconnect();
                }
                triggerEvent("close", event);
            };

            // 错误处理
            socket.value.onerror = (error: Event) => {
                const { currentTarget } = error;
                if (currentTarget) {
                    const { readyState } = currentTarget as WebSocket;
                    if (readyState === WebSocket.CLOSED) {
                        // feedback.msgError(DeviceWsMessage[DeviceCmdCodeEnum.CONNECT_ERROR]);
                    }
                }
                triggerEvent("error", {
                    error: "连接失败",
                    code: DeviceCmdCodeEnum.CONNECT_ERROR,
                });
                socket.value?.close();
            };
        }
    };

    // 事件触发器
    const triggerEvent = <D = unknown>(event: string, data?: D) => {
        const handler = eventHandlers.get(event);
        if (handler) handler(data!);
    };

    // 心跳管理
    const startHeartbeat = () => {
        heartbeatTimer = setInterval(() => {
            if (isConnected.value) {
                if (!manualHeartbeat) {
                    socket.value?.send(JSON.stringify(heartbeatMessage));
                } else {
                    triggerEvent("heartbeat");
                }
            }
        }, heartbeatInterval);
    };

    const resetHeartbeat = () => {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            startHeartbeat();
        }
    };

    // 重连调度 (指数退避)
    const scheduleReconnect = () => {
        const delay = retryBaseDelay * Math.pow(2, retryCount.value);
        retryCount.value += 1;

        reconnectTimer = setTimeout(() => {
            if (retryCount.value <= maxRetries) {
                connect();
            }
        }, delay);
    };

    // 资源清理
    const cleanup = () => {
        if (heartbeatTimer) clearInterval(heartbeatTimer);
        if (reconnectTimer) clearTimeout(reconnectTimer);
        heartbeatTimer = null;
        reconnectTimer = null;
    };

    // 公开方法
    const send = (data: unknown) => {
        if (isConnected.value) {
            socket.value?.send(JSON.stringify(data));
        }
    };

    const close = (code?: number, reason?: string) => {
        socket.value?.close(code, reason);
        cleanup();
    };

    const on = <D = unknown>(
        event: "open" | "message" | "close" | "error" | "heartbeat",
        callback: WebSocketEventCallback<D>
    ) => {
        eventHandlers.set(event, callback);
    };

    // 自动连接（除非设置为手动模式）
    if (!manual) {
        connect();
    }

    // 组件卸载时清理
    onUnmounted(() => {
        close(1000, "Component unmounted");
        eventHandlers.clear();
    });

    return {
        socket,
        isConnected,
        retryCount,
        send,
        close,
        on,
        reconnect: connect,
    };
}
