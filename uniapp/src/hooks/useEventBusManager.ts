// useEventListener.js
import { onBeforeUnmount } from "vue";

// 核心存储：Map<eventName: string, handlers: Set<Function>>
const eventRegistry = new Map<string, Set<(payload: any) => void>>();

/**
 * @description 全局事件触发方法 (emit)。
 * @param {string} eventName 要触发的事件名称。
 * @param {any} payload 传递给监听器的参数。
 */
export const emit = (eventName: string, payload: any) => {
    const handlers = eventRegistry.get(eventName);

    if (handlers) {
        // 遍历所有注册的监听器并执行
        handlers.forEach((handler) => {
            try {
                handler(payload);
            } catch (error) {
                console.error(`[Bus Error] Event "${eventName}" handler failed:`, error);
            }
        });
    }
};

/**
 * @description 动态事件监听管理器 Hook。
 * 暴露 on 和 off 方法，并在组件卸载时自动清理所有通过此 Hook 注册的监听器。
 */
export const useEventBusManager = () => {
    // 存储当前组件实例通过此 Hook 注册的所有监听器
    // Map<eventName: string, Set<callback: Function>>
    const registeredHandlers = new Map<string, Set<(payload: any) => void>>();

    /**
     * 注册事件监听器。
     * @param {string} eventName 事件名称。
     * @param {Function} callback 事件触发时的回调函数。
     */
    const on = (eventName: string, callback: (payload: any) => void) => {
        if (typeof eventName !== "string" || typeof callback !== "function") {
            console.error("Bus.on: eventName 必须是字符串，callback 必须是函数。");
            return;
        }

        // 1. 注册到全局中心 (eventRegistry)
        if (!eventRegistry.has(eventName)) {
            eventRegistry.set(eventName, new Set());
        }
        eventRegistry.get(eventName)!.add(callback);

        // 2. 记录到组件的注册列表，以便卸载时清理
        if (!registeredHandlers.has(eventName)) {
            registeredHandlers.set(eventName, new Set());
        }
        registeredHandlers.get(eventName)!.add(callback);
    };

    /**
     * 移除事件监听器。
     * @param {string} eventName 事件名称。
     * @param {Function} callback 事件触发时的回调函数。
     */
    const off = (eventName: string, callback: (payload: any) => void) => {
        // 移除全局监听器
        const handlers = eventRegistry.get(eventName);
        if (handlers) {
            handlers.delete(callback);

            if (handlers.size === 0) {
                eventRegistry.delete(eventName);
            }
        }

        // 移除当前 hook 实例的记录
        const locallyRegistered = registeredHandlers.get(eventName);
        if (locallyRegistered) {
            locallyRegistered.delete(callback);
            if (locallyRegistered.size === 0) {
                registeredHandlers.delete(eventName);
            }
        }
    };

    // --- 自动清理逻辑 ---
    onBeforeUnmount(() => {
        // 遍历所有记录的监听器，进行批量清理
        registeredHandlers.forEach((callbacks, eventName) => {
            const globalHandlers = eventRegistry.get(eventName);
            if (globalHandlers) {
                callbacks.forEach((callback) => {
                    globalHandlers.delete(callback);
                });

                if (globalHandlers.size === 0) {
                    eventRegistry.delete(eventName);
                }
            }
        });
        registeredHandlers.clear();
    });

    return {
        on,
        off,
        emit,
    };
};

// 导出所有方法供外部使用
export default {
    emit,
    useEventBusManager,
};
