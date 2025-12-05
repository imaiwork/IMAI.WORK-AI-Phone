import { reactive, toRaw } from "vue";

interface Options {
	[propName: string]: {
		api: (_arg: any) => Promise<any>;
		params?: Record<string, any>;
		transformData?(data: any): any;
	};
}

// {
//     dict: {
//         api: dictData,
//         params: { name: 'user' },
//         transformData(data: any) {
//             return data.list
//         }
//     }
// }

export function useDictOptions<T = any>(options: Options) {
	const optionsData: any = reactive({});
	const optionsKey = Object.keys(options);
	const apiLists = optionsKey.map((key) => {
		const value = options[key];
		optionsData[key] = [];
		return () => value.api(toRaw(value.params) || {});
	});

	const refresh = async () => {
		const res = await Promise.allSettled<Promise<any>>(
			apiLists.map((api) => api())
		);
		res.forEach((item, index) => {
			const key = optionsKey[index];
			if (item.status == "fulfilled") {
				const { transformData } = options[key];
				const data = transformData
					? transformData(item.value)
					: item.value;
				optionsData[key] = data;
			}
		});
	};
	refresh();
	return {
		optionsData: optionsData as T,
		refresh,
	};
}
