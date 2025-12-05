import { RequestMethodsEnum } from '@/enums/requestEnums'
import axios, {
    AxiosError,
    type AxiosInstance,
    type AxiosRequestConfig,
    type AxiosResponse
} from 'axios'
import { isFunction, merge, cloneDeep, isObject } from 'lodash'
import axiosCancel from './cancel'
import type { FileParams, RequestData, RequestOptions } from './type'

export class Axios {
    private axiosInstance: AxiosInstance
    private readonly config: AxiosRequestConfig
    private readonly options: RequestOptions
    constructor(config: AxiosRequestConfig) {
        this.config = config
        this.options = config.requestOptions
        this.axiosInstance = axios.create(config)
        this.setupInterceptors()
    }

    /**
     * @description 获取axios实例
     */
    getAxiosInstance() {
        return this.axiosInstance
    }

    /**
     * @description 设置拦截器
     */
    setupInterceptors() {
        if (!this.config.axiosHooks) {
            return
        }
        const {
            requestInterceptorsHook,
            requestInterceptorsCatchHook,
            responseInterceptorsHook,
            responseInterceptorsCatchHook
        } = this.config.axiosHooks
        this.axiosInstance.interceptors.request.use(
            (config) => {
                this.addCancelToken(config)
                if (isFunction(requestInterceptorsHook)) {
                    config = requestInterceptorsHook(config)
                }
                return config
            },
            (err: Error) => {
                if (isFunction(requestInterceptorsCatchHook)) {
                    requestInterceptorsCatchHook(err)
                }
                return err
            }
        )
        this.axiosInstance.interceptors.response.use(
            (response: AxiosResponse<RequestData>) => {
                this.removeCancelToken(response.config.url!)
                if (isFunction(responseInterceptorsHook)) {
                    response = responseInterceptorsHook(response)
                }
                return response
            },
            (err: AxiosError) => {
                if (isFunction(responseInterceptorsCatchHook)) {
                    responseInterceptorsCatchHook(err)
                }
                if (err.code != AxiosError.ERR_CANCELED) {
                    this.removeCancelToken(err.config?.url!)
                }

                if (err.code == AxiosError.ECONNABORTED || err.code == AxiosError.ERR_NETWORK) {
                    return new Promise((resolve) => setTimeout(resolve, 500)).then(() =>
                        this.retryRequest(err)
                    )
                }
                return Promise.reject(err)
            }
        )
    }

    /**
     * @description 添加CancelToken
     */
    addCancelToken(config: AxiosRequestConfig) {
        const { ignoreCancelToken } = config.requestOptions
        !ignoreCancelToken && axiosCancel.add(config)
    }

    /**
     * @description 移除CancelToken
     */
    removeCancelToken(url: string) {
        axiosCancel.remove(url)
    }

    /**
     * @description 重新请求
     */
    retryRequest(error: AxiosError) {
        const config = error.config
        const { retryCount, isOpenRetry } = config.requestOptions
        if (!isOpenRetry || config.method?.toUpperCase() == RequestMethodsEnum.POST) {
            return Promise.reject(error)
        }
        config.retryCount = config.retryCount ?? 0

        if (config.retryCount >= retryCount) {
            return Promise.reject(error)
        }
        config.retryCount++

        return this.axiosInstance.request(config)
    }
    /**
     * @description get请求
     */
    get<T = any>(
        config: Partial<AxiosRequestConfig>,
        options?: Partial<RequestOptions>
    ): Promise<T> {
        return this.request({ ...config, method: RequestMethodsEnum.GET }, options)
    }

    /**
     * @description post请求
     */
    post<T = any>(
        config: Partial<AxiosRequestConfig>,
        options?: Partial<RequestOptions>
    ): Promise<T> {
        return this.request({ ...config, method: RequestMethodsEnum.POST }, options)
    }
    /**
     * @description: 文件上传
     */
    uploadFile(
        config: Partial<AxiosRequestConfig>,
        params: FileParams,
        options?: Partial<RequestOptions>
    ) {
        const formData = new FormData()
        const customFilename = params.name || 'file'
        formData.append(customFilename, params.file)
        if (params.data) {
            Object.keys(params.data).forEach((key) => {
                const value = params.data![key]
                if (isObject(value)) {
                    formData.append(key, JSON.stringify(value))
                } else {
                    formData.append(key, value)
                }
            })
        }
        return this.post(
            {
                timeout: 30 * 1000,
                ...config,
                data: formData
            },
            {
                ignoreCancelToken: true,
                showProgress: false,
                ...options
            }
        )
    }
    /**
     * @description 请求函数
     */
    request<T = any>(
        config: Partial<AxiosRequestConfig>,
        options?: Partial<RequestOptions>
    ): Promise<any> {
        const opt: RequestOptions = merge({}, this.options, options)
        const axiosConfig: AxiosRequestConfig = {
            ...cloneDeep(config),
            requestOptions: opt
        }
        const { urlPrefix } = opt
        // 拼接请求前缀如api
        if (urlPrefix) {
            axiosConfig.url = `${urlPrefix}${config.url}`
        }
        return new Promise((resolve, reject) => {
            this.axiosInstance
                .request<any, AxiosResponse<RequestData<T>>>(axiosConfig)
                .then((res) => {
                    resolve(res)
                })
                .catch((err) => {
                    reject(err)
                })
        })
    }
}
