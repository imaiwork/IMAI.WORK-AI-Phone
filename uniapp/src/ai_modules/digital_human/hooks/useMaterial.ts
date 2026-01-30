import { batchGetVideoInfoByUrl } from "@/api/app";

export const useMaterial = (materialListRef: any) => {
    // 补全视频时长并格式化
    const processAndAppend = async (options: {
        rawList: any[];
        urlField: string; // 原始数据中代表地址的字段名
        replaceIndex?: number;
        type: "video" | "image";
        onSuccess?: () => void;
    }) => {
        const { rawList, urlField, type, onSuccess } = options;

        if (type === "video") {
            const needFetch = rawList.filter((item) => !item.duration);
            if (needFetch.length > 0) {
                uni.showLoading({ title: "信息获取中...", mask: true });
                try {
                    const { results } = await batchGetVideoInfoByUrl({
                        video_urls: needFetch.map((item) => item[urlField]),
                    });
                    const durationMap = new Map(results.map((r: any) => [r.url, r.data.duration]));

                    rawList.forEach((item) => {
                        if (!item.duration) {
                            item.duration = durationMap.get(item[urlField]) || 0;
                        }
                    });
                } finally {
                    uni.hideLoading();
                }
            }
        }

        const formatted = rawList.map((item) => ({
            pic: item.pic,
            url: item[urlField],
            type: type,
            duration: item.duration || 0,
        }));

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
