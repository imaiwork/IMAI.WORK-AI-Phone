import { useCallbacks } from "./callbacks";

export class EventManage<T extends Function> {
    private callbacksMap: Map<string, ReturnType<typeof useCallbacks<T>>>;
    constructor() {
        this.callbacksMap = new Map();
    }
    addEventListener(type: string, listener: T) {
        if (typeof listener !== "function") {
            throw new Error("listener must be function");
        }
        let callbacks = this.callbacksMap.get(type);
        if (!callbacks) {
            callbacks = useCallbacks<T>();
            this.callbacksMap.set(type, callbacks);
        }
        callbacks.add(listener);
    }

    dispatchEvent(type: string, ev: any) {
        const callbacks = this.callbacksMap.get(type);
        if (callbacks) {
            callbacks.list().forEach((callback) => {
                callback(ev);
            });
        }
    }

    removeEventListener(type: string, listener?: T) {
        const callbacks = this.callbacksMap.get(type);
        if (!callbacks) {
            return;
        }
        if (listener) {
            callbacks.remove(listener);
            if (callbacks.list().length === 0) {
                this.callbacksMap.delete(type);
            }
        } else {
            this.callbacksMap.delete(type);
        }
    }
}
