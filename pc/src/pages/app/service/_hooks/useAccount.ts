import { getAccountList as apiGetAccountList } from "@/api/service";

// 账号列表
const accountLists = ref<any[]>([]);
// 当前账号
const currentAccount = ref<any>({});

export default function useAccount(params?: any) {
    const queryParams = reactive({
        page_no: 1,
        ...params,
    });
    const {
        pager: accountPager,
        getLists: getAccountList,
        resetPage: resetAccountPage,
        resetParams: resetAccountParams,
    } = usePaging({
        fetchFun: apiGetAccountList,
        params: queryParams,
    });

    const handleSelectAccount = (data: any) => {
        const { account, status } = data;
        if (status == 1) {
            currentAccount.value = data;
        }
    };

    return {
        accountLists,
        accountPager,
        queryParams,
        currentAccount,
        getAccountList,
        resetAccountPage,
        resetAccountParams,
        handleSelectAccount,
    };
}
