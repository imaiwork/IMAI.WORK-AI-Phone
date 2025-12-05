import { reactive, toRaw } from "vue";

// 分页钩子函数
interface Options {
    page?: number;
    size?: number;
    isScroll?: Boolean;
    fetchFun: (_arg: any) => Promise<any>;
    params?: Record<any, any>;
    firstLoading?: boolean;
}

export function usePaging(options: Options) {
    const { page = 1, size = 15, isScroll = false, fetchFun, params = {}, firstLoading = false } = options;
    // 记录分页初始参数
    const paramsInit: Record<any, any> = Object.assign({}, toRaw(params));
    // 分页数据
    const pager = reactive({
        page,
        size,
        loading: firstLoading,
        isLoad: false,
        count: 0,
        lists: [] as any[],
        extend: {} as Record<string, any>,
    });
    // 请求分页接口
    const getLists = async (data?: any, showLoading = true, isConcat = true) => {
        showLoading && (pager.loading = true);
        return await fetchFun({
            page_no: pager.page,
            page_size: pager.size,
            ...params,
            ...data,
        })
            .then((res: any) => {
                pager.count = res?.count;
                const lists = res?.lists || res?.list || res || [];
                if (isScroll) {
                    pager.lists = isConcat ? pager.lists.concat(lists) : lists;
                    pager.isLoad = !(lists.length < (params.page_size || size));
                } else {
                    pager.lists = lists;
                }
                pager.extend = res?.extend;
                return Promise.resolve(res);
            })
            .catch((err: any) => {
                return Promise.reject(err);
            })
            .finally(() => {
                pager.loading = false;
            });
    };

    // 重置为第一页
    const resetPage = (data?: any) => {
        pager.page = 1;
        if (params.page_no) {
            params.page_no = 1;
        }
        if (isScroll) {
            pager.lists = [];
        }
        pager.isLoad = true;
        return getLists(data);
    };
    // 重置参数
    const resetParams = (data?: any) => {
        Object.keys(paramsInit).forEach((item) => {
            params[item] = paramsInit[item];
        });
        pager.isLoad = true;
        return getLists(data);
    };
    return {
        pager,
        getLists,
        resetParams,
        resetPage,
    };
}
