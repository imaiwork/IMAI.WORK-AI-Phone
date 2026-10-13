import { batchGetVideoInfoByUrl } from "@/api/app";

interface ImageValidateOptions {
    maxResolution?: { width: number; height: number };
    maxImageSize?: number;
}

interface ProcessAndAppendOptions extends ImageValidateOptions {
    isParseVideoElement?: boolean;
    rawList: any[];
    urlField: string;
    replaceIndex?: number;
    maxDuration?: number;
    onSuccess?: (formatted: any[]) => void;
}

const getImageResolution = (url: string): Promise<{ width: number; height: number }> => {
    return new Promise((resolve, reject) => {
        // #ifdef H5
        const img = new Image();
        img.onload = () => resolve({ width: img.naturalWidth, height: img.naturalHeight });
        img.onerror = () => reject(new Error(`图片加载失败: ${url}`));
        img.src = url;
        // #endif

        // #ifndef H5
        uni.getImageInfo({
            src: url,
            success: (res) => resolve({ width: res.width, height: res.height }),
            fail: () => reject(new Error(`图片信息获取失败: ${url}`)),
        });
        // #endif
    });
};

const getImageFileSize = (url: string): Promise<number> => {
    return new Promise((resolve) => {
        // #ifdef H5
        fetch(url, { method: "HEAD" })
            .then((res) => {
                const size = Number(res.headers.get("content-length") ?? 0);
                resolve(size);
            })
            .catch(() => resolve(0));
        // #endif

        // #ifndef H5
        resolve(0);
        // #endif
    });
};

/**
 * 校验图片是否合规
 * @returns true = 合规，false = 不合规需过滤
 */
const validateImage = async (
    item: any,
    urlField: string,
    maxResolution: { width: number; height: number },
    maxImageSize: number,
): Promise<boolean> => {
    const url: string = item[urlField];

    try {
        // ---- 文件大小校验 ----
        const fileSize: number = item.size > 0 ? item.size : await getImageFileSize(url);
        if (fileSize > 0 && fileSize > maxImageSize) {
            return false;
        }

        // ---- 分辨率校验 ----
        let width: number = item.width ?? 0;
        let height: number = item.height ?? 0;
        if (!width || !height) {
            const resolution = await getImageResolution(url);
            width = resolution.width;
            height = resolution.height;
            item.width = width;
            item.height = height;
        }
        if (width > maxResolution.width || height > maxResolution.height) {
            return false;
        }

        return true;
    } catch {
        return true;
    }
};

export const useMaterial = (materialListRef: any) => {
    const processAndAppend = async (options: ProcessAndAppendOptions) => {
        const {
            isParseVideoElement = true,
            rawList,
            urlField,
            maxDuration = 60,
            maxResolution = { width: 2000, height: 2000 },
            maxImageSize = 50 * 1024 * 1024, // 50MB
            onSuccess,
        } = options;

        // ---- 视频时长补全 ----
        const needFetch = rawList.filter(
            (item) => item.type === "video" && (!item.duration || Number(item.duration) <= 0),
        );
        if (needFetch.length > 0) {
            uni.showLoading({ title: "信息获取中...", mask: true });
            try {
                const { results } = isParseVideoElement
                    ? await batchGetVideoInfoByUrl({
                          video_urls: needFetch.map((item) => item[urlField]),
                      })
                    : { results: [] };

                const durationMap = new Map(results.map((r: any) => [r.url, r.data.duration]));

                rawList.forEach((item) => {
                    if (item.type === "video" && (!item.duration || Number(item.duration) <= 0)) {
                        item.duration = durationMap.get(item[urlField]) ?? -1;
                    }
                });
            } finally {
                uni.hideLoading();
            }
        }

        // ---- 视频时长过滤（duration > 0 且 < maxDuration 才合规，即过滤掉 >= maxDuration）----
        const afterVideoFilter = rawList.filter((item) => {
            if (item.type === "image") return true;
            if (item.type === "video") {
                const duration = Number(item.duration); // ✅ 兼容字符串类型
                return duration > 0 && duration < maxDuration; // ✅ 严格小于，过滤掉 >= maxDuration
            }
            return false;
        });

        if (!afterVideoFilter.length) {
            uni.showToast({ title: "没有符合条件的素材", icon: "none" });
            return;
        }

        // ---- 图片合规校验 ----
        const hasImageItems = afterVideoFilter.some((item) => item.type === "image");

        let formatted: any[];
        if (hasImageItems) {
            uni.showLoading({ title: "图片校验中...", mask: true });
            try {
                const validFlags = await Promise.all(
                    afterVideoFilter.map(
                        (item) =>
                            item.type === "image"
                                ? validateImage(item, urlField, maxResolution, maxImageSize)
                                : Promise.resolve(true), // 非图片直接放行
                    ),
                );
                formatted = afterVideoFilter.filter((_, index) => validFlags[index]);
            } finally {
                uni.hideLoading();
            }
        } else {
            formatted = afterVideoFilter;
        }

        // ---- 统一判空提示 ----
        if (!formatted.length) {
            uni.showToast({ title: "没有符合条件的素材", icon: "none" });
            return;
        }

        // ---- 写入列表 ----
        if (options.replaceIndex !== undefined && options.replaceIndex >= 0) {
            materialListRef.value[options.replaceIndex] = formatted[0];
        } else {
            materialListRef.value = [...materialListRef.value, ...formatted];
        }
        onSuccess?.(formatted);
    };

    return {
        processAndAppend,
    };
};
