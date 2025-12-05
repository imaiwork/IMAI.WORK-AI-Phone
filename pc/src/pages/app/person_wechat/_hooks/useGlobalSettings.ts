import { getWeChatLists } from "@/api/person_wechat";

export default function useGlobalSettings() {
	// 微信列表
	const { optionsData } = useDictOptions<{
		wechatLists: any[];
	}>({
		wechatLists: {
			api: getWeChatLists,
			params: {
				page_size: 999,
			},
			transformData: (data: any) => data.lists,
		},
	});

	return {
		optionsData,
	};
}
