import { parse } from "jsonc-parser";
import { EventManage } from "../event";
import { ContentTypeEnum } from "~/enums/requestEnums";
interface EventData {
    type: EventType;
    data: null | undefined | Record<string, any>;
    errorType?: "connectError" | "readError" | "responseError";
}
type EventCallback = (ev: EventData) => any;

enum ReadyState {
    CONNECTING,
    OPEN,
    RECEIVING,
    CLOSED,
}
enum EventType {
    ERROR = "error",
    MESSAGE = "message",
    OPEN = "open",
    CLOSE = "close",
}

enum ErrorType {
    CONNECT_ERROR = "connectError",
    READ_ERROR = "readError",
    RESPONSE_ERROR = "responseError",
}

const createEvent = (type: EventType, data: any, errorType?: ErrorType): EventData => {
    const event: EventData = {
        type,
        data,
    };
    if (type === EventType.ERROR || type === EventType.CLOSE) {
        event.errorType = errorType;
    }
    return event;
};

const DATA_PREFIX = "data:";
export class Sse extends EventManage<EventCallback> {
    readyState: ReadyState | null;
    onerror: EventCallback | null;
    onmessage: EventCallback | null;
    onopen: EventCallback | null;
    onclose: EventCallback | null;

    controller: AbortController | null | undefined;
    response: Response | null | undefined;
    constructor(public url: string, public options: RequestInit) {
        super();
        this.readyState = null;
        this.onopen = null;
        this.onmessage = null;
        this.onerror = null;
        this.onclose = null;
    }

    async connect() {
        try {
            this.readyState = ReadyState.CONNECTING;
            this.controller = new AbortController();
            const response = await fetch(this.url, {
                ...this.options,
                signal: this.controller.signal,
            } as RequestInit);
            this.response = response;
            if (response.status === 200) {
                this.triggerEvent(EventType.OPEN, response);
                //json报错
                const isJson = response.headers?.get("Content-Type")?.includes(ContentTypeEnum.JSON);
                if (isJson) {
                    const data = await response.json();
                    this.triggerEvent(EventType.ERROR, data, ErrorType.RESPONSE_ERROR);
                } else {
                    this.read();
                }
            } else {
                throw response;
            }
        } catch (error) {
            if (this.readyState === ReadyState.CLOSED) {
                return;
            }
            this.triggerEvent(EventType.ERROR, error, ErrorType.CONNECT_ERROR);
        }
    }

    private triggerEvent(type: EventType, data: any, errorType?: ErrorType) {
        try {
            if (type === EventType.OPEN) {
                this.readyState = ReadyState.OPEN;
            }
            if (type === EventType.MESSAGE) {
                this.readyState = ReadyState.RECEIVING;
            }
            if (type === EventType.ERROR || type === EventType.CLOSE) {
                this.readyState = ReadyState.CLOSED;
            }
            const event = createEvent(type, data, errorType);
            this[`on${type}`]?.(event);
            this.dispatchEvent(type, event);
        } catch (error) {
            //外部调用钩子的错误
            console.error(error);
        }
    }

    async push(
        controller: ReadableStreamDefaultController<Uint8Array>,
        reader: ReadableStreamDefaultReader<Uint8Array>
    ) {
        try {
            const { value, done } = await reader.read();
            if (done) {
                controller.close();
                this.triggerEvent(EventType.CLOSE, null);
            } else {
                controller.enqueue(value);
                this.push(controller, reader);
                const textValue = new TextDecoder().decode(value);
                textValue
                    .trim()
                    .split(DATA_PREFIX)
                    .forEach((text) => {
                        if (text) {
                            const resultJson = parse(text);
                            if (typeof resultJson !== "object") {
                                return;
                            }
                            this.triggerEvent(EventType.MESSAGE, resultJson);
                            if (resultJson.error) {
                                this.triggerEvent(EventType.ERROR, resultJson.error, ErrorType.RESPONSE_ERROR);
                                return;
                            }

                            const { choices } = resultJson;
                            if (!choices) return;
                            const dataJson = {
                                type: resultJson.object,
                                id: resultJson.id,
                                index: choices[0]?.index,
                                data: choices[0]?.delta?.content,
                            };

                            if (dataJson.type) {
                                this.triggerEvent(dataJson.type, dataJson);
                            }
                        }
                    });
            }
        } catch (error) {
            if (this.readyState !== ReadyState.CLOSED) {
                this.triggerEvent(EventType.ERROR, error, ErrorType.READ_ERROR);
            }
        }
    }

    read() {
        const reader = this.response?.body!.getReader();
        if (reader) {
            new ReadableStream({
                start: (controller) => {
                    this.push(controller, reader);
                },
            });
        } else {
            this.triggerEvent(EventType.ERROR, null, ErrorType.READ_ERROR);
        }
    }
    // 取消请求
    abort() {
        this.triggerEvent(EventType.CLOSE, null);
        this.controller?.abort();
    }
}
