import { getPublishAccountList } from "@/api/device";

export default function useGlobalSettings() {
    // 微信列表
    const { optionsData } = useDictOptions<{
        wechatLists: any[];
    }>({
        wechatLists: {
            api: getPublishAccountList,
            params: {
                page_size: 999,
                type: 1,
            },
            transformData: (data: any) => data.lists,
        },
    });

    return {
        optionsData,
    };
}
