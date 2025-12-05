import { isObject } from "@vue/shared";
import { cloneDeep } from "lodash";
import html2Canvas, { type Html2CanvasOptions } from "./html2canvas";
import feedback from "./feedback";

/**
 * @description 添加单位
 * @param {String | Number} value 值 100
 * @param {String} unit 单位 px em rem
 */
export const addUnit = (value: string | number, unit = "px") => {
    return !Object.is(Number(value), NaN) ? `${value}${unit}` : value;
};

/**
 * @description 添加单位
 * @param {unknown} value
 * @return {Boolean}
 */
export const isEmpty = (value: unknown) => {
    return value == null && typeof value == "undefined";
};

/**
 * @description 树转数组，队列实现广度优先遍历
 * @param {Array} data  数据
 * @param {Object} props `{ children: 'children' }`
 */

export const treeToArray = (data: any[], props = { children: "children" }) => {
    data = cloneDeep(data);
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
 * @description 数组转
 * @param {Array} data  数据
 * @param {Object} props `{ parent: 'pid', children: 'children' }`
 */

export const arrayToTree = (data: any[], props = { id: "id", parentId: "pid", children: "children" }) => {
    data = cloneDeep(data);
    const { id, parentId, children } = props;
    const result: any[] = [];
    const map = new Map();
    data.forEach((item) => {
        map.set(item[id], item);
        const parent = map.get(item[parentId]);
        if (parent) {
            parent[children] = parent[children] ?? [];
            parent[children].push(item);
        } else {
            result.push(item);
        }
    });
    return result;
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
        const part = encodeURIComponent(props) + "=";
        if (!isEmpty(value)) {
            if (isObject(value)) {
                for (const key of Object.keys(value)) {
                    if (!isEmpty(value[key])) {
                        const params = props + "[" + key + "]";
                        const subPart = encodeURIComponent(params) + "=";
                        query += subPart + encodeURIComponent(value[key]) + "&";
                    }
                }
            } else {
                query += part + encodeURIComponent(value) + "&";
            }
        }
    }
    return query.slice(0, -1);
}

/**
 * @description 时间格式化
 * @param dateTime { number } 时间戳
 * @param fmt { string } 时间格式
 * @return { string }
 */
// yyyy:mm:dd|yyyy:mm|yyyy年mm月dd日|yyyy年mm月dd日 hh时MM分等,可自定义组合
export const timeFormat = (dateTime: number, fmt = "yyyy-mm-dd") => {
    // 如果为null,则格式化当前时间
    if (!dateTime) {
        dateTime = Number(new Date());
    }
    // 如果dateTime长度为10或者13，则为秒和毫秒的时间戳，如果超过13位，则为其他的时间格式
    if (dateTime.toString().length == 10) {
        dateTime *= 1000;
    }
    const date = new Date(dateTime);
    let ret;
    const opt: any = {
        "y+": date.getFullYear().toString(), // 年
        "m+": (date.getMonth() + 1).toString(), // 月
        "d+": date.getDate().toString(), // 日
        "h+": date.getHours().toString(), // 时
        "M+": date.getMinutes().toString(), // 分
        "s+": date.getSeconds().toString(), // 秒
    };
    for (const k in opt) {
        ret = new RegExp("(" + k + ")").exec(fmt);
        if (ret) {
            fmt = fmt.replace(ret[1], ret[1].length == 1 ? opt[k] : opt[k].padStart(ret[1].length, "0"));
        }
    }
    return fmt;
};

/**
 * @description 获取不重复的id
 * @param length { Number } id的长度
 * @return { String } id
 */
export const getNonDuplicateID = (length = 8) => {
    let idStr = Date.now().toString(36);
    idStr += Math.random().toString(36).substring(3, length);
    return idStr;
};

/**
 *
 * @export
 * @param {string} str
 * @param {number} frontLen
 * @param {number} endLen
 * @param {string} separator
 * @return {string}
 * 222222 => 2****2
 */
export function replaceSeparatorInStr(str: string, frontLen: number, endLen: number, separator: string) {
    const len = endLen - frontLen;
    let separators = "";
    for (let i = 0; i < len; i++) {
        separators += separator;
    }
    return str.substring(0, frontLen) + separators + str.substring(endLen);
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
export const download = (url: string, name?: string) => {
    const aTag = document.createElement("a");
    document.body.appendChild(aTag);
    aTag.href = url;
    aTag.download = name || getNonDuplicateID();
    aTag.target = "_blank";
    aTag.click();
    aTag.remove();
};

/**
 * @description 是否是JSON
 * @param {string} value
 * @return {Boolean}
 */
export const isJson = (value: string) => {
    try {
        JSON.parse(value);
        return true;
    } catch (error) {
        return false;
    }
};

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
        a.download = filename || url.split("/").pop() || "";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(urlObject);
    } catch (error) {
        console.error("There has been a problem with your fetch operation:", error);
        throw error;
    }
}
