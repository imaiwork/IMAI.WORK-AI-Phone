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
        this.controller = new AbortController();

        mergeOptions.requestOptions = merge({}, this.requestOptions, requestOptions);
        const { requestInterceptorsHook, responseInterceptorsHook } = this.requestOptions;
        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions);
        }
        const { onmessage, onclose, onstart } = requestOptions;
        return new Promise((resolve, reject) => {
            const push = async (controller, reader) => {
                try {
                    const { value, done } = await reader.read();
                    if (done) {
                        controller.close();
                        onclose?.();
                    } else {
                        onmessage?.(new TextDecoder().decode(value));
                        controller.enqueue(value);
                        push(controller, reader);
                    }
                } catch (error) {
                    onclose?.();
                }
            };
            let body = undefined;
            let url = `${mergeOptions.baseURL}${mergeOptions.url}`;
            if (mergeOptions.method.toUpperCase() == RequestMethodsEnum.GET) {
                url = `${url}?${objectToQuery(mergeOptions.params)}`;
            }
            if (mergeOptions.method.toUpperCase() == RequestMethodsEnum.POST) {
                body = JSON.stringify(mergeOptions.body);
            }
            fetch(url, {
                ...mergeOptions,
                signal: this.controller.signal,
                body,
                headers: {
                    accept: "text/event-stream",
                    "Content-Type": "application/json",
                    ...mergeOptions.headers,
                },
            })
                .then(async (response) => {
                    if (response.status == 200) {
                        if (response.headers.get("content-type").includes("text/event-stream")) {
                            const reader = response.body!.getReader();
                            onstart?.(reader);

                            new ReadableStream({
                                start(controller) {
                                    push(controller, reader);
                                },
                            });
                        } else {
                            //@ts-ignore
                            response._data = await response.json();
                            return response;
                        }
                    } else {
                        reject(response.statusText);
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

                            resolve(response);
                        } catch (error) {
                            reject(error);
                        }
                        return;
                    }
                    resolve(response);
                })
                .catch((err) => {
                    console.log("err", err);
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
        mergeOptions.url = `${mergeOptions.baseURL}${mergeOptions.url}`;

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
        sseInstance.addEventListener("error", (ev) => {
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

        // 如果没有提供进度回调，则使用原来的方法
        if (!onProgress) {
            return this.request({
                ...options,
                method: RequestMethodsEnum.POST,
                body: formData,
            });
        }

        // 使用 XMLHttpRequest 实现进度监听
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            let mergeOptions = merge({}, this.fetchOptions, options);

            const { requestInterceptorsHook } = this.requestOptions;
            if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
                mergeOptions = requestInterceptorsHook(mergeOptions);
            }
            // 构建完整URL
            const url = `${mergeOptions.baseURL}${mergeOptions.url}`;

            // 打开连接
            xhr.open(RequestMethodsEnum.POST, url, true);

            // 设置请求头
            const headers = { ...options.headers, ...mergeOptions.headers };
            Object.keys(headers).forEach((key) => {
                // 对于文件上传，不要设置 Content-Type，让浏览器自动设置
                if (key.toLowerCase() !== "content-type") {
                    xhr.setRequestHeader(key, headers[key]);
                }
            });

            // 注册上传进度事件
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    onProgress(percent);
                }
            };

            // 注册完成事件
            xhr.onload = () => {
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

            // 注册错误事件
            xhr.onerror = () => {
                reject(new Error("网络错误，上传失败"));
            };

            // 注册中止事件
            xhr.onabort = () => {
                reject(new Error("上传已取消"));
            };

            // 发送请求
            xhr.send(formData);

            // 生成请求唯一标识并注册到取消令牌管理器
            const method = RequestMethodsEnum.POST;
            const requestParams = options.params || {};
            // 使用传入的requestKey或生成新的requestKey
            const requestKey = params.requestKey || CancelTokenManager.generateRequestKey(url, method, requestParams);

            // 创建一个可以取消XHR请求的控制器
            const controller = {
                abort: (reason?: any) => {
                    xhr.abort();
                },
            } as AbortController;

            // 注册到取消令牌管理器
            cancelTokenManager.addRequest(requestKey, controller);
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

        // 生成请求唯一标识
        const url = `${mergeOptions.baseURL || ""}${mergeOptions.requestOptions.apiPrefix}${mergeOptions.url}`;
        const method = mergeOptions.method || "GET";
        this.requestKey = CancelTokenManager.generateRequestKey(url, method, fetchOptions.params || {});

        // 注册到取消令牌管理器
        cancelTokenManager.addRequest(this.requestKey, this.controller);
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
