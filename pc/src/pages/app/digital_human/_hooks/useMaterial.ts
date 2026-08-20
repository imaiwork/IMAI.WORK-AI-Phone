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

const { show: showSpin, hide: hideSpin } = useGlobalSpin();

// ---- 获取图片分辨率 ----
const getImageResolution = (url: string): Promise<{ width: number; height: number }> => {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve({ width: img.naturalWidth, height: img.naturalHeight });
        img.onerror = () => reject(new Error(`图片加载失败: ${url}`));
        img.src = url;
    });
};

// ---- 获取图片文件大小（HEAD 请求，失败时放行）----
const getImageFileSize = (url: string): Promise<number> => {
    return fetch(url, { method: "HEAD" })
        .then((res) => Number(res.headers.get("content-length") ?? 0))
        .catch(() => 0);
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
        return true; // 获取失败时不拦截，放行
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

        // ---- Step 1: 补全视频时长 ----
        const needFetch = rawList.filter((item) => item.type === "video" && (!item.duration || item.duration <= 0));
        if (needFetch.length > 0) {
            showSpin({ text: "信息获取中..." });
            try {
                const { results } = isParseVideoElement
                    ? await batchGetVideoInfoByUrl({
                          video_urls: needFetch.map((item) => item[urlField]),
                      })
                    : { results: [] };

                const durationMap = new Map(
                    results
                        .filter((r: any) => r.data.duration <= maxDuration)
                        .map((r: any) => [r.url, r.data.duration]),
                );

                rawList.forEach((item) => {
                    if (item.type === "video" && (!item.duration || item.duration <= 0)) {
                        item.duration = durationMap.get(item[urlField]) || 0;
                    }
                });
            } finally {
                hideSpin();
            }
        }

        // ---- Step 2: 过滤超时长视频 ----
        const afterVideoFilter = rawList.filter(
            (item) => item.type === "image" || (item.type === "video" && item.duration <= maxDuration),
        );

        if (!afterVideoFilter.length) {
            feedback.msgWarning("没有符合条件的素材");
            return;
        }

        // ---- Step 3: 校验图片分辨率 & 大小 ----
        let formatted: any[];
        const imageItems = afterVideoFilter.filter((item) => item.type === "image");
        if (imageItems.length > 0) {
            showSpin({ text: "图片校验中..." });
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
                hideSpin();
            }
        } else {
            formatted = afterVideoFilter;
        }

        if (!formatted.length) {
            feedback.msgWarning("没有符合条件的素材");
            return;
        }

        // ---- Step 4: 写入列表 ----
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
