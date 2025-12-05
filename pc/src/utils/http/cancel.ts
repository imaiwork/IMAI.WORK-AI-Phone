/**
 * 请求取消令牌管理器
 * 用于管理和取消HTTP请求
 */
export class CancelTokenManager {
    // 存储所有请求的控制器映射
    private requestMap: Map<string, AbortController>;
    // 单例实例
    private static instance: CancelTokenManager;

    private constructor() {
        this.requestMap = new Map<string, AbortController>();
    }

    /**
     * 获取单例实例
     */
    public static getInstance(): CancelTokenManager {
        if (!CancelTokenManager.instance) {
            CancelTokenManager.instance = new CancelTokenManager();
        }
        return CancelTokenManager.instance;
    }

    /**
     * 添加请求控制器
     * @param key 请求的唯一标识
     * @param controller 请求的控制器
     */
    public addRequest(key: string, controller: AbortController): void {
        // 如果已存在相同key的请求，先取消旧请求
        if (this.requestMap.has(key)) {
            this.cancelRequest(key);
        }
        this.requestMap.set(key, controller);
    }

    /**
     * 移除请求控制器
     * @param key 请求的唯一标识
     */
    public removeRequest(key: string): void {
        if (this.requestMap.has(key)) {
            this.requestMap.delete(key);
        }
    }

    /**
     * 取消指定请求
     * @param key 请求的唯一标识
     * @param message 取消原因
     */
    public cancelRequest(key: string, message: string = "请求已取消"): void {
        if (this.requestMap.has(key)) {
            const controller = this.requestMap.get(key);
            controller?.abort({
                type: "cancel",
                message,
            });
            this.removeRequest(key);
        }
    }

    /**
     * 取消匹配URL的所有请求
     * @param urlPattern URL匹配模式（字符串或正则表达式）
     * @param message 取消原因
     */
    public cancelRequestsByUrl(urlPattern: string | RegExp, message: string = "请求已取消"): void {
        this.requestMap.forEach((controller, key) => {
            if (typeof urlPattern === "string" ? key.includes(urlPattern) : urlPattern.test(key)) {
                controller.abort({
                    type: "cancel",
                    message,
                });
                this.requestMap.delete(key);
            }
        });
    }

    /**
     * 取消所有请求
     * @param message 取消原因
     */
    public cancelAllRequests(message: string = "所有请求已取消"): void {
        this.requestMap.forEach((controller) => {
            controller.abort({
                type: "cancel",
                message,
            });
        });
        this.requestMap.clear();
    }

    /**
     * 生成请求的唯一标识
     * @param url 请求URL
     * @param method 请求方法
     * @returns 请求的唯一标识
     */
    public static generateRequestKey(url: string, method: string, params: any): string {
        return `${method}:${url}:${Date.now()}`;
    }

    /**
     * 获取当前活跃请求数量
     */
    public getActiveRequestCount(): number {
        return this.requestMap.size;
    }
}

// 导出单例实例
export const cancelTokenManager = CancelTokenManager.getInstance();

// 导出便捷方法
export const cancelRequest = (key: string, message?: string) => {
    cancelTokenManager.cancelRequest(key, message);
};

export const cancelRequestsByUrl = (urlPattern: string | RegExp, message?: string) => {
    cancelTokenManager.cancelRequestsByUrl(urlPattern, message);
};

export const cancelAllRequests = (message?: string) => {
    cancelTokenManager.cancelAllRequests(message);
};
