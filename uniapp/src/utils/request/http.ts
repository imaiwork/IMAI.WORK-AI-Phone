import { merge } from "lodash-es";
import { isFunction } from "@vue/shared";
import { HttpRequestOptions, RequestConfig, RequestEventStreamConfig, RequestOptions, UploadFileOption } from "./type";
import { RequestCodeEnum, RequestErrMsgEnum, RequestMethodsEnum } from "@/enums/requestEnums";
import requestCancel from "./cancel";
import { objectToQuery } from "../util";
import { isObject } from "mathjs";
import { parse } from "jsonc-parser";
/**
 * @description 将流转换为utf-8
 */
function decodeUTF8(arr: Uint8Array) {
    let str = "";
    for (let i = 0; i < arr.length; i++) {
        str += String.fromCharCode(arr[i]);
    }
    return decodeURIComponent(escape(str));
}

function isStreamResponse(header?: any) {
    const contentType = header?.["Content-Type"] || header?.["content-type"] || header.get("content-type")!;
    if (typeof contentType !== "string") return false;
    return contentType.includes("text/event-stream");
}

export default class HttpRequest {
    private readonly options: HttpRequestOptions;
    private requestTask: any;
    constructor(options: HttpRequestOptions) {
        this.options = options;
    }
    /**
     * @description 重新请求
     */
    retryRequest(options: RequestOptions, config: RequestConfig) {
        const { retryCount, retryTimeout } = config;
        if (!retryCount || options.method?.toUpperCase() == RequestMethodsEnum.POST) {
            return Promise.reject();
        }
        uni.showLoading({ title: "加载中..." });
        config.hasRetryCount = config.hasRetryCount || 0;
        if (config.hasRetryCount >= retryCount) {
            return Promise.reject();
        }
        config.hasRetryCount++;
        config.requestHooks.requestInterceptorsHook = (options) => options;
        return new Promise((resolve) => setTimeout(resolve, retryTimeout))
            .then(() => this.request(options, config))
            .finally(() => uni.hideLoading());
    }
    /**
     * @description get请求
     */
    get<T = any>(options: RequestOptions, config?: Partial<RequestConfig>): Promise<T> {
        return this.request({ ...options, method: RequestMethodsEnum.GET }, config);
    }

    /**
     * @description post请求
     */
    post<T = any>(options: RequestOptions, config?: Partial<RequestConfig>): Promise<T> {
        return this.request({ ...options, method: RequestMethodsEnum.POST }, config);
    }
    /**
     * @description 事件流
     */
    eventStream(options: RequestOptions, config?: RequestEventStreamConfig) {
        let mergeOptions: RequestOptions = merge({}, this.options.requestOptions, options);
        const mergeConfig: RequestEventStreamConfig = merge({}, this.options, config);
        const { requestInterceptorsHook, responseInterceptorsHook } = mergeConfig.requestHooks || {};
        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions, mergeConfig as RequestConfig);
        }

        const { onmessage, onclose, onstart } = mergeConfig;
        return new Promise((resolve, reject) => {
            try {
                // #ifdef H5
                const decoder = new TextDecoder();
                const push = async (controller: any, reader: any) => {
                    try {
                        const { value, done } = await reader.read();
                        if (done) {
                            controller.close();
                            onclose?.();
                        } else {
                            onmessage?.(decoder.decode(value));
                            controller.enqueue(value);
                            push(controller, reader);
                        }
                    } catch (error) {
                        onclose?.();
                    }
                };

                if (mergeOptions.method!.toUpperCase() == RequestMethodsEnum.GET) {
                    if (isObject(mergeOptions.data)) {
                        mergeOptions.data = objectToQuery(mergeOptions.data as Record<string, any>);
                    }
                    mergeOptions.url = `${mergeOptions.url}?${mergeOptions.data}`;
                }
                let body = undefined;
                if (mergeOptions.method!.toUpperCase() == RequestMethodsEnum.POST) {
                    body = isObject(mergeOptions.data)
                        ? JSON.stringify(mergeOptions.data)
                        : (mergeOptions.data as string);
                }
                fetch(mergeOptions.url, {
                    ...mergeOptions,
                    body,
                    headers: {
                        "content-type": "application/json; charset=utf-8",
                        ...mergeOptions.header,
                        Accept: "text/event-stream",
                    },
                })
                    .then(async (response) => {
                        if (response.status == 200) {
                            if (isStreamResponse(response.headers)) {
                                const reader = response.body!.getReader();

                                onstart?.(reader);
                                new ReadableStream({
                                    start(controller) {
                                        push(controller, reader);
                                    },
                                });
                            } else {
                                //@ts-ignore
                                response.data = await response.json();
                                return response;
                            }
                        } else {
                            reject(response.statusText);
                        }
                    })
                    .then(async (response: any) => {
                        if (!response) {
                            resolve(response);
                            return;
                        }
                        if (responseInterceptorsHook && isFunction(responseInterceptorsHook)) {
                            try {
                                response = await responseInterceptorsHook(
                                    response,
                                    mergeConfig as RequestConfig,
                                    mergeOptions
                                );
                                resolve(response);
                            } catch (error) {
                                reject(error);
                            }
                            return;
                        }
                        resolve(response);
                    })
                    .catch((error) => {
                        reject(error);
                    });
                // #endif
                // #ifdef APP-PLUS
                let body = undefined;
                if (mergeOptions.method!.toUpperCase() == RequestMethodsEnum.POST) {
                    body = isObject(mergeOptions.data)
                        ? JSON.stringify(mergeOptions.data)
                        : (mergeOptions.data as string);
                }
                // 创建一个新的APP-PLUS XMLHttpRequest对象
                const xhr = new plus.net.XMLHttpRequest();
                // 记录最后返回的数据长度
                let lastResponseLength = 0;
                onstart?.(xhr);

                function processNewData(responseText: string) {
                    const newResponse = responseText.substring(lastResponseLength).trim();
                    if (newResponse) {
                        // 处理新的响应数据...
                        onmessage?.(newResponse);
                    }
                    lastResponseLength = responseText.length;
                }
                // xhr.onloadstart = (e) => {
                //     const timer = setInterval(() => {
                //         console.log(xhr)
                //         if (xhr.readyState == 4) {
                //             clearInterval(timer)
                //         }
                //     }, 50)
                // }
                // 设置onreadystatechange回调函数
                xhr.onreadystatechange = function () {
                    console.log(xhr);
                    if (xhr.readyState == 3) {
                        // 当readyState为3时，表示正在接收服务器响应
                        processNewData(xhr.responseText);
                    }
                };

                xhr.open(mergeOptions.method, mergeOptions.url, true);
                xhr.setRequestHeader("content-type", "application/json; charset=utf-8");
                xhr.setRequestHeader("Accept", "text/event-stream");
                xhr.setRequestHeader("token", mergeOptions.header.token);
                xhr.responseType = "text";
                xhr.onload = () => onclose?.();
                xhr.send(body);
                // #endif
                // #ifdef MP-WEIXIN
                let header: Record<string, any> = {};
                const requestTask = uni.request({
                    ...mergeOptions,
                    enableChunked: true,
                    responseType: "arraybuffer",
                    async success(response) {
                        if (response.statusCode !== 200) {
                            reject(response);
                            return;
                        }
                        resolve(response);
                    },
                    fail(error) {
                        reject(error);
                    },
                    complete() {
                        onclose?.();
                    },
                });
                onstart?.(requestTask);
                //@ts-ignore
                requestTask.onHeadersReceived((response) => {
                    header = response.header;
                });
                //@ts-ignore
                requestTask.onChunkReceived(async (response) => {
                    const arrayBuffer = response.data;
                    const uint8Array = new Uint8Array(arrayBuffer);
                    const str = decodeUTF8(uint8Array);
                    if (isStreamResponse(header)) {
                        onmessage?.(str);
                    } else {
                        const data = parse(str);
                        if (responseInterceptorsHook && isFunction(responseInterceptorsHook)) {
                            try {
                                const response = await responseInterceptorsHook(
                                    { data, statusCode: 200 },
                                    mergeConfig as RequestConfig,
                                    mergeOptions
                                );
                                resolve(response);
                            } catch (error) {
                                reject(error);
                            }
                            return;
                        }
                    }
                });
                // #endif
            } catch (e) {
                reject();
            }
        });
    }
    /**
     * @description 上传图片
     */
    uploadFile(options: UploadFileOption, config?: Partial<RequestConfig>) {
        let mergeOptions: RequestOptions = merge({}, this.options.requestOptions, options);
        const mergeConfig: RequestConfig = merge({}, this.options, config);
        const { requestInterceptorsHook, responseInterceptorsHook, responseInterceptorsCatchHook } =
            mergeConfig.requestHooks || {};
        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions, mergeConfig);
        }
        return new Promise((resolve, reject) => {
            this.requestTask = uni.uploadFile({
                ...mergeOptions,
                header: {
                    ...mergeOptions.header,
                },
                async success(response) {
                    if (response.statusCode == 200) {
                        response.data = parse(response.data);
                        if (responseInterceptorsHook && isFunction(responseInterceptorsHook)) {
                            try {
                                response = await responseInterceptorsHook(response, mergeConfig, mergeOptions);
                                resolve(response);
                            } catch (error) {
                                reject(error);
                            }
                            return;
                        }

                        resolve(response);
                    } else {
                        reject(response.errMsg);
                    }
                },
                fail(err) {
                    if (err.errMsg == RequestErrMsgEnum.UPLOAD_ABORT) {
                        return reject(err.errMsg);
                    }
                    if (responseInterceptorsCatchHook && isFunction(responseInterceptorsCatchHook)) {
                        reject(responseInterceptorsCatchHook(mergeOptions, err));
                        return;
                    }
                    reject(err.errMsg || err);
                },
            });
            this.requestTask.onProgressUpdate(({ progress }: any) => {
                mergeConfig.onProgress?.(progress, mergeOptions);
            });
            const { ignoreCancel } = mergeConfig;
            !ignoreCancel && requestCancel.add(options.url, this.requestTask);
        });
    }
    /**
     * @description 请求函数
     */
    async request(options: RequestOptions, config?: Partial<RequestConfig>): Promise<any> {
        let mergeOptions: RequestOptions = merge({}, this.options.requestOptions, options);
        const mergeConfig: RequestConfig = merge({}, this.options, config);
        const { requestInterceptorsHook, responseInterceptorsHook, responseInterceptorsCatchHook } =
            mergeConfig.requestHooks || {};
        if (requestInterceptorsHook && isFunction(requestInterceptorsHook)) {
            mergeOptions = requestInterceptorsHook(mergeOptions, mergeConfig);
        }
        return new Promise((resolve, reject) => {
            this.requestTask = uni.request({
                ...mergeOptions,
                async success(response) {
                    if (response.statusCode !== 200) {
                        return reject();
                    }
                    if (responseInterceptorsHook && isFunction(responseInterceptorsHook)) {
                        try {
                            response = await responseInterceptorsHook(response, mergeConfig, mergeOptions);
                            resolve(response);
                        } catch (error) {
                            reject(error);
                        }
                        return;
                    }
                    resolve(response);
                },
                fail: async (err) => {
                    if (err.errMsg == RequestErrMsgEnum.TIMEOUT) {
                        this.retryRequest(mergeOptions, mergeConfig)
                            .then((res) => resolve(res))
                            .catch((err) => reject(err));
                        return;
                    }
                    if (err.errMsg == RequestErrMsgEnum.ABORT) {
                        reject({ message: "请求已终止", errno: RequestCodeEnum.ABORT });
                        return;
                    }
                    if (responseInterceptorsCatchHook && isFunction(responseInterceptorsCatchHook)) {
                        reject(await responseInterceptorsCatchHook(mergeOptions, err));
                        return;
                    }
                    reject(err);
                },
                complete(err) {
                    if (err.errMsg !== RequestErrMsgEnum.ABORT) {
                        requestCancel.remove(options.url);
                    }
                },
            });
            const { ignoreCancel } = mergeConfig;
            !ignoreCancel && requestCancel.add(options.url, this.requestTask);
        });
    }
    // 新增取消请求的方法
    cancelRequest() {
        if (this.requestTask) {
            this.requestTask.abort();
        }
    }
}
