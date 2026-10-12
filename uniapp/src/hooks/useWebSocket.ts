import { DeviceWsMessage, DeviceCmdCodeEnum } from '@/enums/appEnums'

type WebSocketEventCallback<T = unknown> = (data: T) => void

export interface WebSocketOptions {
    /** 最大重连次数 (默认 5 次) */
    maxRetries?: number
    /** 重连基础间隔 (默认 1000ms) */
    retryBaseDelay?: number
    /** 心跳间隔 (默认 300s) */
    heartbeatInterval?: number
    /** 心跳消息 (默认 { type: "ping" }) */
    heartbeatMessage?: Record<string, unknown>
    /** 手动发送心跳 */
    manualHeartbeat?: boolean
    /** 手动启动连接 (默认自动连接) */
    manual?: boolean
}

export default function useWebSocket<T = unknown>(url: string, options: WebSocketOptions = {}) {
    // 状态管理
    const socket = shallowRef<UniApp.SocketTask | null>(null)
    const isConnected = ref(false)
    const retryCount = ref(0)

    // 配置合并
    const {
        maxRetries = 5,
        retryBaseDelay = 1000,
        heartbeatInterval = 30000,
        heartbeatMessage = { type: 'ping' },
        manualHeartbeat = false,
        manual = false
    } = options

    // 事件回调存储
    const eventHandlers = new Map<string, WebSocketEventCallback<any>>()

    // 定时器管理
    let reconnectTimer: ReturnType<typeof setTimeout> | null = null
    let heartbeatTimer: ReturnType<typeof setInterval> | null = null
    const pendingMessages: unknown[] = []

    // 核心连接方法
    const connect = () => {
        if (socket.value) return
        if (reconnectTimer) {
            clearTimeout(reconnectTimer)
            reconnectTimer = null
        }

        // 连接失败 / 关闭时只回收本次 task，避免误伤 close() 后新建的连接
        let task: UniApp.SocketTask | null = null
        let failed = false
        const releaseTask = () => {
            if (task && socket.value === task) socket.value = null
        }

        task = uni.connectSocket({
            url,
            // 连接成功与否以 onOpen 为准，success 回调无业务意义
            fail: (err) => {
                // 小程序端 WebSocket 并发超上限等情况直接 fail，不会走 onError/onClose
                console.warn('[ws] connectSocket fail', url, err)
                failed = true
                isConnected.value = false
                releaseTask()
                triggerEvent('error', {
                    error: '连接失败',
                    code: DeviceCmdCodeEnum.CONNECT_ERROR
                })
                scheduleReconnect()
            }
        })
        // fail 可能同步触发，此时不能再把已失败的 task 挂上去
        socket.value = failed ? null : task

        // 连接成功
        task.onOpen(() => {
            if (socket.value !== task) return
            isConnected.value = true
            retryCount.value = 0
            startHeartbeat()
            triggerEvent('open')
            flushPendingMessages()
        })

        // 消息接收
        task.onMessage((event) => {
            if (socket.value !== task) return
            try {
                const data = JSON.parse(event.data as string) as T
                triggerEvent('message', data)
                resetHeartbeat() // 收到消息重置心跳
            } catch (error) {
                triggerEvent('error', {
                    error: '消息解析失败',
                    code: DeviceCmdCodeEnum.PARSE_ERROR
                })
            }
        })

        // 连接关闭
        task.onClose((res) => {
            // close() 主动关闭时 socket 已置空，这里是滞后触发的回调，不重连
            if (socket.value !== task) return
            console.warn('[ws] onClose', url, res)
            isConnected.value = false
            cleanup()
            releaseTask()
            // 小程序中没有 wasClean 属性, 默认非正常关闭都进行重连
            scheduleReconnect()
            triggerEvent('close')
        })

        // 错误处理
        task.onError((err) => {
            if (socket.value !== task) return
            console.warn('[ws] onError', url, err)
            // 部分端（尤其 iOS 握手失败）只触发 onError 不触发 onClose，
            // 这里直接按断开处理并安排重连，否则死 task 会一直占着 socket，永远连不上
            isConnected.value = false
            cleanup()
            releaseTask()
            task?.close({})
            triggerEvent('error', {
                error: '连接失败',
                code: DeviceCmdCodeEnum.CONNECT_ERROR
            })
            scheduleReconnect()
        })
    }

    // 事件触发器
    const triggerEvent = <D = unknown>(event: string, data?: D) => {
        const handler = eventHandlers.get(event)
        if (handler) handler(data!)
    }

    // 心跳管理
    const startHeartbeat = () => {
        if (heartbeatTimer) clearInterval(heartbeatTimer)
        heartbeatTimer = setInterval(() => {
            if (isConnected.value) {
                if (!manualHeartbeat) {
                    send(heartbeatMessage)
                } else {
                    triggerEvent('heartbeat')
                }
            }
        }, heartbeatInterval)
    }

    const resetHeartbeat = () => {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer)
            startHeartbeat()
        }
    }

    // 重连调度 (指数退避)
    const scheduleReconnect = () => {
        if (reconnectTimer || socket.value || retryCount.value >= maxRetries) return
        const delay = retryBaseDelay * Math.pow(2, retryCount.value)
        retryCount.value += 1

        reconnectTimer = setTimeout(() => {
            reconnectTimer = null
            connect()
        }, delay)
    }

    // 资源清理
    const cleanup = () => {
        if (heartbeatTimer) clearInterval(heartbeatTimer)
        if (reconnectTimer) clearTimeout(reconnectTimer)
        heartbeatTimer = null
        reconnectTimer = null
    }

    const sendNow = (data: unknown) => {
        socket.value?.send({
            data: JSON.stringify(data)
        })
    }

    const flushPendingMessages = () => {
        while (isConnected.value && socket.value && pendingMessages.length > 0) {
            const data = pendingMessages.shift()
            sendNow(data)
        }
    }

    // 公开方法
    const send = (data: unknown) => {
        if (isConnected.value && socket.value) {
            sendNow(data)
            return true
        }

        pendingMessages.push(data)
        if (!socket.value) {
            connect()
        }
        return false
    }

    const close = (code?: number, reason?: string) => {
        cleanup()
        pendingMessages.length = 0
        const task = socket.value
        // 先置空再 close：滞后触发的 onClose 会被当作过期回调忽略，不再触发重连
        socket.value = null
        isConnected.value = false
        retryCount.value = 0
        task?.close({
            code,
            reason
        })
    }

    // 手动重连：重置重试计数，保证多次失败后仍可再次尝试
    const reconnect = () => {
        if (socket.value) return
        retryCount.value = 0
        connect()
    }

    const on = <D = unknown>(
        event: 'open' | 'message' | 'close' | 'error' | 'heartbeat',
        callback: WebSocketEventCallback<D>
    ) => {
        eventHandlers.set(event, callback)
    }

    // 自动连接（除非设置为手动模式）
    if (!manual) {
        connect()
    }

    // 组件卸载时清理
    onUnmounted(() => {
        // close(1000, "Component unmounted");
        // eventHandlers.clear();
    })

    return {
        socket,
        isConnected,
        retryCount,
        send,
        close,
        on,
        reconnect
    }
}
