import {
    FetchOptions,
    $fetch,
    $Fetch,
    FetchResponse,
    RequestOptions,
    FileParams,
    RequestEventStreamOptions,
} from "ofetch";
import { merge } from "lodash-es";
import { isFunction } from "../validate";
import { ContentTypeEnum, RequestMethodsEnum } from "@/enums/requestEnums";
import { Sse } from "./sse";
import { cancelTokenManager, CancelTokenManager } from "./cancel";

/**
 * 将对象转换为URL查询字符串
 * @param obj 待转换的对象
 * @returns 查询字符串
 */
function objectToQuery(obj: Record<string, any>): string {
    if (!obj) return "";
    const params = [];
    for (const key in obj) {
        if (Object.prototype.hasOwnProperty.call(obj, key)) {
            const value = obj[key];
            if (value !== undefined && value !== null) {
                params.push(`${encodeURIComponent(key)}=${encodeURIComponent(value)}`);
            }
        }
    }
    return params.join("&");
}

export interface UserFetchOptions extends Partial<FetchOptions> {
    url: string;
}

export class Request {
    private requestOptions: RequestOptions;
    private fetchInstance: $Fetch;
    private controller: AbortController;
    private requestKey: string;
    constructor(private fetchOptions: FetchOptions) {
        this.fetchInstance = $fetch.create(fetchOptions);
        this.requestOptions = fetchOptions.requestOptions;
    }

    getInstance() {
        return this.fetchInstance;
    }
    /**
     * @description get请求
     */
    get(fetchOptions: FetchOptions, requestOptions?: Partial<RequestOptions>) {
        return this.request({ ...fetchOptions, method: RequestMethodsEnum.GET }, requestOptions);
    }

    /**
     * @description post请求
     */
    post(fetchOptions: FetchOptions, requestOptions?: Partial<RequestOptions>) {
        return this.request({ ...fetchOptions, method: RequestMethodsEnum.POST }, requestOptions);
    }
    /**
     * @description eventStream请求，无法使用$fetch
     */
    async eventStream(fetchOptions: FetchOptions, requestOptions?: Partial<RequestEventStreamOptions>) {
        let mergeOptions = merge({}, this.fetchOptions, fetchOptions);
        const controller = new AbortController();

        mergeOptions.requestOptions = merge({}, this.requestOptions, requestOptions);

        const { ignoreCancel = false } = mergeOptions.requestOptions;
        const url = `${mergeOptions.baseURL || ""}${mergeOptions.url}`;
        const method = mergeOptions.method || "GET";
        const requestKey = CancelTokenManager.generateRequestKey(url, method, fetchOptions.params || {}, ignoreCancel);
        cancelTokenManager.addRequest(requestKey, controller, ignoreCancel); // Register controller

        const { requestInterceptorsHook, responseInterceptorsHook } = this.requestOptions;
        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions);
        }
        const { onmessage, onclose, onstart } = requestOptions;
        return new Promise((resolve, reject) => {
            const push = async (controllerStream, reader) => {
                try {
                    const { value, done } = await reader.read();
                    if (done) {
                        controllerStream.close();
                        onclose?.();
                        cancelTokenManager.removeRequest(requestKey);
                    } else {
                        onmessage?.(new TextDecoder().decode(value));
                        controllerStream.enqueue(value);
                        push(controllerStream, reader);
                    }
                } catch (error) {
                    onclose?.();
                    if (error.name !== "AbortError") {
                        cancelTokenManager.removeRequest(requestKey);
                    }
                }
            };
            let body = undefined;
            let reqUrl = `${mergeOptions.baseURL}${mergeOptions.url}`;
            if (mergeOptions.method.toUpperCase() == RequestMethodsEnum.GET) {
                reqUrl = `${reqUrl}?${objectToQuery(mergeOptions.params)}`;
            }
            if (mergeOptions.method.toUpperCase() == RequestMethodsEnum.POST) {
                body = JSON.stringify(mergeOptions.body);
            }
            fetch(reqUrl, {
                ...mergeOptions,
                signal: controller.signal,
                body,
                headers: {
                    accept: "text/event-stream",
                    "Content-Type": "application/json",
                    ...mergeOptions.headers,
                },
            })
                .then(async (response) => {
                    if (response.status == 200) {
                        if (response.headers.get("content-type")?.includes("text/event-stream")) {
                            const reader = response.body!.getReader();
                            onstart?.(reader);

                            new ReadableStream({
                                start(controllerStream) {
                                    push(controllerStream, reader);
                                },
                            });
                        } else {
                            //@ts-ignore
                            response._data = await response.json();
                            cancelTokenManager.removeRequest(requestKey);
                            return response;
                        }
                    } else {
                        reject(response.statusText);
                        cancelTokenManager.removeRequest(requestKey);
                    }
                })
                .then(async (response) => {
                    if (!response) {
                        resolve(response);
                        return;
                    }
                    if (responseInterceptorsHook && isFunction(responseInterceptorsHook)) {
                        try {
                            response = await responseInterceptorsHook(response, mergeOptions);
                            cancelTokenManager.removeRequest(requestKey);
                            resolve(response);
                        } catch (error) {
                            reject(error);
                            cancelTokenManager.removeRequest(requestKey);
                        }
                        return;
                    }
                    cancelTokenManager.removeRequest(requestKey);
                    resolve(response);
                })
                .catch((err) => {
                    if (err.name !== "AbortError") {
                        cancelTokenManager.removeRequest(requestKey);
                    }
                    reject(err);
                });
        });
    }
    sse(fetchOptions: UserFetchOptions, requestOptions?: Partial<RequestOptions>) {
        let mergeOptions = merge({}, this.fetchOptions, fetchOptions);
        mergeOptions.requestOptions = merge({}, this.requestOptions, requestOptions);
        const { requestInterceptorsHook, responseInterceptorsHook, responseInterceptorsCatchHook } =
            this.requestOptions;
        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions) as FetchOptions & UserFetchOptions;
        }

        const url = `${mergeOptions.baseURL}${mergeOptions.url}`;
        const method = mergeOptions.method || "GET";
        const { ignoreCancel = false } = mergeOptions.requestOptions;
        const requestKey = CancelTokenManager.generateRequestKey(url, method, fetchOptions.params || {}, ignoreCancel);

        if (mergeOptions.method?.toUpperCase() === RequestMethodsEnum.GET && mergeOptions.params) {
            mergeOptions.url = `${mergeOptions.url}?${objectToQuery(mergeOptions.params!)}`;
        }
        if (mergeOptions.method?.toUpperCase() === RequestMethodsEnum.POST) {
            mergeOptions.body = JSON.stringify(mergeOptions.body);
        }
        mergeOptions.headers = {
            accept: ContentTypeEnum.EVENT_STREAM,
            "Content-Type": ContentTypeEnum.JSON,
            ...mergeOptions.headers,
        };

        const sseInstance = new Sse(mergeOptions.url, mergeOptions as RequestInit);

        const controller = {
            abort: (reason?: any) => {
                sseInstance.abort();
            },
        } as AbortController;
        cancelTokenManager.addRequest(requestKey, controller, ignoreCancel);

        sseInstance.addEventListener("error", (ev) => {
            cancelTokenManager.removeRequest(requestKey);

            if (ev.errorType === "responseError") {
                responseInterceptorsHook?.(
                    {
                        ...sseInstance.response!,
                        _data: {
                            ...ev.data,
                            msg: ev.data?.message,
                        },
                        sse: true,
                    } as any,
                    mergeOptions
                );
            } else {
                responseInterceptorsCatchHook?.(ev);
            }
        });
        sseInstance.addEventListener("close", () => {
            cancelTokenManager.removeRequest(requestKey);
        });

        sseInstance.connect();

        return sseInstance;
    }

    /**
     * @description 构建文件上传的FormData
     * @private
     */
    private buildFormData(params: FileParams & { requestKey?: string }): FormData {
        const formData = new FormData();
        const customFilename = params.name || "file";

        // 添加文件到表单
        formData.append(customFilename, params.file);

        // 添加其他参数到表单
        Object.keys(params).forEach((key) => {
            if (key !== "file" && key !== "requestKey") {
                formData.append(key, params[key]);
            }
        });

        // 添加数据对象中的参数
        if (params.data) {
            Object.keys(params.data).forEach((key) => {
                const value = params.data![key];
                if (Array.isArray(value)) {
                    value.forEach((item) => {
                        formData.append(`${key}[]`, item);
                    });
                    return;
                }
                formData.append(key, params.data![key]);
            });
        }

        return formData;
    }
    /**
     * @description 上传文件
     * @param options 请求选项
     * @param params 文件参数
     * @param onProgress 上传进度回调函数，参数为0-100的进度百分比
     */
    uploadFile(
        options: FetchOptions,
        params: FileParams & { requestKey?: string },
        onProgress?: (percent: number) => void
    ) {
        const formData = this.buildFormData(params);

        let mergeOptions = merge({}, this.fetchOptions, options);
        const { ignoreCancel = false } = mergeOptions.requestOptions || {};

        // 如果没有提供进度回调，则使用原来的方法
        if (!onProgress) {
            return this.request(
                {
                    ...options,
                    method: RequestMethodsEnum.POST,
                    body: formData,
                },
                options.requestOptions
            );
        }

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            const { requestInterceptorsHook } = this.requestOptions;
            if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
                mergeOptions = requestInterceptorsHook(mergeOptions);
            }

            const url = `${mergeOptions.baseURL}${mergeOptions.url}`;

            const method = RequestMethodsEnum.POST;
            const requestParams = options.params || {};
            let requestKey =
                params.requestKey || CancelTokenManager.generateRequestKey(url, method, requestParams, ignoreCancel);

            const controller = {
                abort: (reason?: any) => {
                    xhr.abort();
                },
            } as AbortController;
            cancelTokenManager.addRequest(requestKey, controller, ignoreCancel);

            xhr.open(RequestMethodsEnum.POST, url, true);

            const headers = { ...options.headers, ...mergeOptions.headers };
            Object.keys(headers).forEach((key) => {
                if (key.toLowerCase() !== "content-type") {
                    xhr.setRequestHeader(key, headers[key]);
                }
            });

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    onProgress(percent);
                }
            };

            xhr.onload = () => {
                cancelTokenManager.removeRequest(requestKey);

                if (xhr.status >= 200 && xhr.status < 300) {
                    let response;
                    try {
                        response = JSON.parse(xhr.responseText);
                        resolve(response.data);
                    } catch (e) {
                        response = xhr.responseText;
                        resolve(response.data);
                        return;
                    }
                } else {
                    reject(new Error(`上传失败，状态码: ${xhr.status}`));
                }
            };

            xhr.onerror = () => {
                cancelTokenManager.removeRequest(requestKey);
                reject(new Error("网络错误，上传失败"));
            };

            xhr.onabort = () => {
                cancelTokenManager.removeRequest(requestKey);
                reject(new Error("上传已取消"));
            };

            xhr.send(formData);
        });
    }
    /**
     * @description 请求函数
     */
    request(fetchOptions: FetchOptions, requestOptions?: Partial<RequestOptions>): Promise<any> {
        this.controller = new AbortController();

        let mergeOptions = merge({}, this.fetchOptions, fetchOptions);
        mergeOptions.signal = this.controller.signal;
        mergeOptions.requestOptions = merge({}, this.requestOptions, requestOptions);

        const { ignoreCancel = false } = mergeOptions.requestOptions;

        // 生成请求唯一标识
        const url = `${mergeOptions.baseURL || ""}${mergeOptions.requestOptions.apiPrefix}${mergeOptions.url}`;
        const method = mergeOptions.method || "GET";
        this.requestKey = CancelTokenManager.generateRequestKey(url, method, fetchOptions.params || {}, ignoreCancel);

        // 注册到取消令牌管理器，传入ignoreCancel参数
        cancelTokenManager.addRequest(this.requestKey, this.controller, ignoreCancel);

        const { requestInterceptorsHook, responseInterceptorsHook, responseInterceptorsCatchHook } =
            this.requestOptions;

        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions);
        }

        return new Promise((resolve, reject) => {
            return this.fetchInstance
                .raw(mergeOptions.url, mergeOptions)
                .then(async (response: FetchResponse<any>) => {
                    if (responseInterceptorsHook && isFunction(responseInterceptorsHook)) {
                        try {
                            response = await responseInterceptorsHook(response, mergeOptions);
                            // 请求成功后从管理器中移除
                            cancelTokenManager.removeRequest(this.requestKey);
                            resolve(response);
                        } catch (error) {
                            reject(error);
                        }
                        return;
                    }
                    resolve(response);
                })
                .catch((err) => {
                    // 请求失败后从管理器中移除（除非是被取消的请求）
                    if (err.name !== "AbortError") {
                        cancelTokenManager.removeRequest(this.requestKey);
                    }

                    if (responseInterceptorsCatchHook && isFunction(responseInterceptorsCatchHook)) {
                        reject(responseInterceptorsCatchHook(err));
                        return;
                    }
                    reject(err);
                });
        });
    }
    /**
     * @description 取消当前请求
     * @param message 取消原因
     */
    cancelRequest(message: string = "取消请求") {
        if (this.controller) {
            this.controller.abort({
                type: "cancel",
                message,
            });

            // 从管理器中移除
            if (this.requestKey) {
                cancelTokenManager.removeRequest(this.requestKey);
            }
        }
    }

    /**
     * @description 获取当前请求的唯一标识
     */
    getRequestKey(): string {
        return this.requestKey;
    }
}
