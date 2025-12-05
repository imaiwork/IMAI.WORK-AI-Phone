import dayjs from "dayjs";
import html2Canvas, { Html2CanvasOptions } from "./html2canvas";

/**
 * @description 添加单位
 * @param {String | Number} value 值 100
 * @param {String} unit 单位 px em rem
 */
export const addUnit = (value: string | number, unit = "px") => {
    return !Object.is(Number(value), NaN) ? `${value}${unit}` : value;
};

/**
 * @description 树转数组，队列实现广度优先遍历
 * @param {Array} data  数据
 * @param {Object} props `{ children: 'children' }`
 */

export const treeToArray = (data: any[], props = { children: "children" }) => {
    data = JSON.parse(JSON.stringify(data));
    const { children } = props;
    const newData = [];
    const queue: any[] = [];
    data.forEach((child: any) => queue.push(child));
    while (queue.length) {
        const item: any = queue.shift();
        if (item[children]) {
            item[children].forEach((child: any) => queue.push(child));
            delete item[children];
        }
        newData.push(item);
    }
    return newData;
};

/**
 * @description 获取正确的路经
 * @param {String} path  数据
 */
export function getNormalPath(path: string) {
    if (path.length === 0 || !path || path == "undefined") {
        return path;
    }
    const newPath = path.replace("//", "/");
    const length = newPath.length;
    if (newPath[length - 1] === "/") {
        return newPath.slice(0, length - 1);
    }
    return newPath;
}

/**
 * @description对象格式化为Query语法
 * @param { Object } params
 * @return {string} Query语法
 */
export function objectToQuery(params: Record<string, any>): string {
    let query = "";
    for (const props of Object.keys(params)) {
        const value = params[props];

        if (!isEmpty(value)) {
            query += props + "=" + value + "&";
        }
    }
    return query.slice(0, -1);
}

/**
 * @description 将window.location.search转换为对象
 * @return {Object}
 */
export function searchQueryToObject(): Record<string, any> {
    const searchParams = new URLSearchParams(window.location.search);
    const result = {};
    for (const [key, value] of searchParams.entries()) {
        if (value == "undefined" || value == "null") {
            result[key] = undefined;
        } else {
            result[key] = value;
        }
    }
    return result;
}

/**
 * 将字节大小转换为适当的文件大小单位（B, KB, MB, GB）, 保留两位小数,可以传参, 如果小于1KB则显示B
 *
 * @param sizeInBytes - 以字节为单位的文件大小
 * @param precision - 保留小数位数
 * @returns 格式化后的文件大小字符串，包含适当的单位
 */
export const formatFileSize = (sizeInBytes: any, precision = 2): string => {
    const units = ["B", "KB", "MB", "GB"];
    let unitIndex = 0;

    while (sizeInBytes >= 1024 && unitIndex < units.length - 1) {
        sizeInBytes /= 1024;
        unitIndex++;
    }
    return unitIndex === 0
        ? `${sizeInBytes.toFixed(precision)}B`
        : `${sizeInBytes.toFixed(precision)}${units[unitIndex]}`;
};

/**
 * 将秒数格式化为 HH:MM:SS 的时间字符串
 *
 * @param seconds - 要格式化的时间（以秒为单位）
 * @returns 格式化后的时间字符串（格式为 HH:MM:SS）
 */
export function formatAudioTime(seconds: number, isShowHours = false): string {
    if (!seconds) return isShowHours ? "00:00:00" : "00:00";
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    // 判断seconds 是不是大于3600
    if (seconds > 3600) {
        return isShowHours
            ? `${hours < 10 ? "0" : ""}${hours}:${minutes < 10 ? "0" : ""}${minutes}:${secs < 10 ? "0" : ""}${secs}`
            : `${hours < 10 ? "0" : ""}${hours}:${minutes < 10 ? "0" : ""}${minutes}:${secs < 10 ? "0" : ""}${secs}`;
    }
    return `${minutes < 10 ? "0" : ""}${minutes}:${secs < 10 ? "0" : ""}${secs}`;
}

/**
 * 将图片 URL 转换为 Base64 编码字符串
 *
 * @param imageUrl - 图片的 URL
 * @returns Promise<string> - 返回包含 Base64 编码字符串的 Promise
 * @throws 如果获取图片或转换过程中出错，将抛出错误
 */
export async function convertToBase64(imageUrl) {
    try {
        const response = await fetch(imageUrl);
        const blob = await response.blob();
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => {
                resolve(reader.result);
            };
            reader.onerror = () => {
                reject(new Error("Error converting image to Base64"));
            };
            reader.readAsDataURL(blob);
        });
    } catch (error) {
        throw new Error(`Error fetching image: ${error.message}`);
    }
}

/**
 * 从给定的 URL 下载文件并使用指定的文件名保存
 *
 * @param url - 要下载的文件的 URL
 * @param filename - 本地保存文件时使用的文件名
 * @throws 如果网络请求失败，将抛出错误
 */
export async function downloadFile(url: string, filename?: string): Promise<void> {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error("Network response was not ok");
        }
        const blob = await response.blob();
        const urlObject = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = urlObject;
        a.download = filename || url.split("/").pop();
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(urlObject);
    } catch (error) {
        console.error("There has been a problem with your fetch operation:", error);
        throw error;
    }
}

/**
 * 用于添加或替换查询参数
 * @param {Object} newParams - 需要添加或替换的查询参数对象
 */
export function replaceState(newParams: any): void {
    // 获取当前的查询参数
    const searchParams = new URLSearchParams(window.location.search);

    // 添加或替换查询参数
    for (const [key, value] of Object.entries(newParams)) {
        if (value === null || value === undefined || value === "") {
            searchParams.delete(key);
        } else {
            searchParams.set(key, String(value));
        }
    }

    const newSearch = searchParams.toString();
    // 构造新的 URL 并替换
    const newUrl = newSearch ? `${window.location.pathname}?${newSearch}` : window.location.pathname;
    window.history.replaceState(null, null, newUrl);
}

/**
 * base64 转 blob
 * @param base64
 * @returns Blob
 */
export async function base64ToBlob(base64URL: string, fileName: string): Promise<Blob> {
    const response = await fetch(base64URL);
    const blob = await response.blob();

    // 创建 File 对象并增加 name 属性
    const file = new File([blob], fileName, { type: blob.type });

    return file;
}

/**
 * 将数组分割成指定数量的子数组
 * @param array 原始数组
 * @param columns 子数组数量
 * @returns 分割后的数组
 */
export const chunkArray = (array: any[], columns: number) => {
    // 创建一个包含指定数量空数组的数组
    const result = Array.from({ length: columns }, () => []);
    // 将原始数组中的每个元素添加到结果数组的子数组中
    array.forEach((item, index) => {
        result[index % columns].push(item);
    });

    return result;
};

/**
 * @description 组合异步任务
 * @param  { string } task 异步任务
 */

export function series(...task: Array<(_arg: any) => any>) {
    return function (_arg?: any): Promise<any> {
        return new Promise((resolve, reject) => {
            const iteratorTask = task.values();
            const next = (res?: any) => {
                const nextTask = iteratorTask.next();
                if (nextTask.done) {
                    resolve(res);
                } else {
                    Promise.resolve(nextTask.value(res)).then(next).catch(reject);
                }
            };
            next(_arg);
        });
    };
}

/**
 * 将字符串转换为对象
 * @param {string} str
 * @returns {Object}
 */
export const convertStringToObject = (str: string) => {
    const result = {};
    const pairs = str.split("&");
    for (let pair of pairs) {
        const [key, value] = pair.split("=");
        if (key) {
            result[decodeURIComponent(key)] = decodeURIComponent(value);
        }
    }
    return result;
};

/**
 * 设置表单数据
 * @param data 数据
 * @param sourceData 数据源
 */
export const setFormData = (data: Record<any, any>, sourceData: Record<any, any>) => {
    for (const key in sourceData) {
        if (data[key] != null && data[key] != undefined) {
            sourceData[key] = data[key];
        }
    }
};

/**
 * 截取视频第一帧作为封面
 * @param url 视频URL
 * @returns 视频宽高、封面文件
 */
/**
 * 截取视频第一帧作为封面
 * @param url 视频URL
 * @param options 可选配置项
 * @returns Promise<{file: Blob, width: number, height: number} | false>
 */
export const getVideoFirstFrame = (
    url: string,
    options: {
        width?: number;
        quality?: number;
        frameTime?: number;
    } = {}
): Promise<{ file: Blob; width: number; height: number; duration: number }> => {
    const { width = 443, quality = 0.7, frameTime = 0.5 } = options;

    return new Promise((resolve) => {
        const video = document.createElement("video");
        const canvas = document.createElement("canvas");
        const context = canvas.getContext("2d");

        if (!context) {
            resolve(null);
            return;
        }

        // 视频基本设置
        video.src = url;
        video.muted = true;
        video.playsInline = true;
        video.preload = "auto";
        video.crossOrigin = "anonymous";

        // 清理函数
        const cleanup = () => {
            URL.revokeObjectURL(video.src);
            video.remove();
            canvas.remove();
        };

        // 错误处理
        video.addEventListener("error", () => {
            cleanup();
            resolve(null);
        });

        // 视频元数据加载完成后处理
        video.addEventListener("loadedmetadata", () => {
            const aspectRatio = video.videoWidth / video.videoHeight;
            canvas.width = width;
            canvas.height = width / aspectRatio;
            video.currentTime = frameTime;

            // 视频跳转到指定时间后截图
            video.addEventListener(
                "seeked",
                async () => {
                    try {
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const fileResult = await base64ToBlob(
                            canvas.toDataURL("image/jpeg", quality),
                            `${dayjs().format("YYYYMMDDHHmmss")}.jpg`
                        );

                        resolve({
                            file: fileResult,
                            width: video.videoWidth,
                            height: video.videoHeight,
                            duration: video.duration,
                        });
                    } catch (error) {
                        console.error("获取视频首帧失败:", error);
                        resolve(null);
                    } finally {
                        cleanup();
                    }
                },
                { once: true }
            );
        });

        video.load();
    });
};

/**
 * @param url 图片地址
 * @param filename 文件名
 * @returns File
 */

export const urlToFile = async (url: string, filename: string): Promise<File> => {
    const response = await fetch(url);
    const blob = await response.blob();
    return new File([blob], filename, { type: blob.type });
};

/**
 * @description 下载HTML为图片
 * @param {HTMLElement} el dom元素
 * @param {String} params 参数配置
 * @param {String} canvasOptions html2Canvas参数配置
 */
export const downloadHtml2Image = async (
    el: HTMLElement,
    params?: {
        type?: "png" | "jpeg";
        name?: string;
    },
    canvasOptions?: Html2CanvasOptions
) => {
    try {
        const { type = "png", name = "file" } = params || {};
        const canvas = await html2Canvas(el, {
            useCORS: true,
            backgroundColor: "transparent",
            ...canvasOptions,
        });
        const dataURL = canvas.toDataURL(`image/${type}`);
        download(dataURL, name);
    } catch (error: any) {
        feedback.msgError("下载失败，请重试");
        throw new Error(error);
    }
};

/**
 * @description 下载文件
 * @param {String} url  文件url
 * @param {String} name 下载的文件名称
 */
export const download = (url: string, name: string) => {
    const aTag = document.createElement("a");
    document.body.appendChild(aTag);
    aTag.href = url;
    aTag.download = name;
    aTag.target = "_blank";
    aTag.click();
    aTag.remove();
};
