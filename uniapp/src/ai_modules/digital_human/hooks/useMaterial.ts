import { batchGetVideoInfoByUrl } from "@/api/app";

export const useMaterial = (materialListRef: any) => {
    // 补全视频时长并格式化
    const processAndAppend = async (options: {
        // 是否解析视频元素, 默认解析
        isParseVideoElement?: boolean;
        rawList: any[];
        urlField: string; // 原始数据中代表地址的字段名
        replaceIndex?: number;
        type: "video" | "image";
        maxDuration?: number;
        onSuccess?: () => void;
    }) => {
        const { isParseVideoElement = true, rawList, urlField, type, maxDuration = 60, onSuccess } = options;

        if (type === "video") {
            const needFetch = rawList.filter((item) => !item.duration || item.duration <= 0);
            if (needFetch.length > 0) {
                uni.showLoading({ title: "信息获取中...", mask: true });
                try {
                    const { results } = isParseVideoElement
                        ? await batchGetVideoInfoByUrl({
                              video_urls: needFetch.map((item) => item[urlField]),
                          })
                        : { results: [] };
                    const durationMap = new Map(
                        results
                            .filter((r: any) => r.data.duration <= maxDuration)
                            .map((r: any) => [r.url, r.data.duration])
                    );

                    rawList.forEach((item) => {
                        if (!item.duration || item.duration <= 0) {
                            item.duration = durationMap.get(item[urlField]) || 0;
                        }
                    });
                } catch (error) {
                } finally {
                    uni.hideLoading();
                }
            }
        }
        const formatted = rawList
            .filter((item) => item.duration <= maxDuration)
            .map((item) => ({
                pic: item.pic,
                url: item[urlField],
                type: type,
                duration: parseFloat(item.duration) || 0,
            }));
        if (!formatted.length) {
            uni.showToast({
                title: "没有符合条件的素材",
                icon: "none",
            });
            return;
        }
        if (options.replaceIndex !== undefined && options.replaceIndex >= 0) {
            materialListRef.value[options.replaceIndex] = formatted[0];
        } else {
            materialListRef.value = [...materialListRef.value, ...formatted];
        }

        onSuccess?.();
    };

    return {
        processAndAppend,
    };
};
