import { getMemberConsumption, getTeamConsumption } from "@/api/team";
import { usePaging } from "@/composables/usePaging";
import { CONSUME_RANGE_FILTERS, type ConsumeRangeKey } from "../_enums";

/** 消耗明细:成员积分明细弹窗 + 企业全员消耗列表 + 消耗详情弹窗 */
export function useTeamConsumption() {
    // 成员积分明细弹窗
    const showConsumption = ref(false);
    const consumptionUserId = ref<number>(0);
    const consumeMemberName = ref("");
    const {
        pager: consumptionPager,
        getLists: getConsumptionLists,
        resetPage: resetConsumption,
    } = usePaging({
        fetchFun: (params: any) => getMemberConsumption({ user_id: consumptionUserId.value, ...params }),
    });
    const onConsumption = (row: any) => {
        consumptionUserId.value = row.id;
        consumeMemberName.value = row.nickname || "成员";
        showConsumption.value = true;
        resetConsumption();
    };

    // 企业全员消耗列表(左导航「消耗明细」独立页)
    // 列表类型:consume=消耗列表 / transfer=算力流转(划拨/回收/制卡/OEM等)
    const consumeTab = ref<"consume" | "transfer">("consume");
    const teamConsumeUser = ref<number | undefined>(undefined);
    const consumeKeyword = ref("");
    const consumeRange = ref<ConsumeRangeKey>("all");
    // 自定义时间范围(ms 时间戳对);选中时优先于快捷时间
    const consumeDateRange = ref<[number, number] | null>(null);
    const consumeBiz = ref<string>("");
    const consumeQuery = reactive<{
        user_id?: number;
        keyword?: string;
        range?: string;
        list_type?: string;
        biz?: string;
        start_time?: string;
        end_time?: string;
    }>({});
    const {
        pager: teamConsumePager,
        getLists: getTeamConsumeLists,
        resetPage: resetTeamConsume,
    } = usePaging({
        fetchFun: (params: any) => getTeamConsumption({ ...consumeQuery, ...params }),
    });

    const syncConsumeQuery = () => {
        if (teamConsumeUser.value) consumeQuery.user_id = teamConsumeUser.value;
        else delete consumeQuery.user_id;
        const kw = consumeKeyword.value.trim();
        if (kw) consumeQuery.keyword = kw;
        else delete consumeQuery.keyword;
        const dr = consumeDateRange.value;
        if (dr && dr.length === 2) {
            // 自定义范围:不传 range;后端 ListsValidate 校验 date 规则,须传日期字符串
            // (end 取当天 23:59:59 含全天)
            delete consumeQuery.range;
            const fmt = (ms: number) => {
                const d = new Date(Number(ms));
                const p = (n: number) => String(n).padStart(2, "0");
                return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
            };
            consumeQuery.start_time = `${fmt(dr[0])} 00:00:00`;
            consumeQuery.end_time = `${fmt(dr[1])} 23:59:59`;
        } else {
            delete consumeQuery.start_time;
            delete consumeQuery.end_time;
            const hit = CONSUME_RANGE_FILTERS.find((f) => f.key === consumeRange.value);
            if (hit?.range) consumeQuery.range = hit.range;
            else delete consumeQuery.range;
        }
        consumeQuery.list_type = consumeTab.value;
        if (consumeBiz.value) consumeQuery.biz = consumeBiz.value;
        else delete consumeQuery.biz;
    };

    const onTeamConsumeFilter = () => {
        syncConsumeQuery();
        resetTeamConsume();
    };

    /** 重置全部筛选项并重新拉取(离开消耗明细分区再回来时,不残留上次查询条件) */
    const resetConsumeFilters = () => {
        consumeTab.value = "consume";
        teamConsumeUser.value = undefined;
        consumeKeyword.value = "";
        consumeRange.value = "all";
        consumeDateRange.value = null;
        consumeBiz.value = "";
        onTeamConsumeFilter();
    };

    const setConsumeRange = (key: ConsumeRangeKey) => {
        if (consumeRange.value === key && !consumeDateRange.value) return;
        consumeRange.value = key;
        consumeDateRange.value = null; // 点快捷时间即放弃自定义范围
        onTeamConsumeFilter();
    };

    const setConsumeTab = (tab: "consume" | "transfer") => {
        if (consumeTab.value === tab) return;
        consumeTab.value = tab;
        consumeBiz.value = ""; // 两个 tab 的业务类型选项不同,切换时重置
        onTeamConsumeFilter();
    };

    /** 消耗列表合计(extend.total_cost,与成员「累计消耗」同口径) */
    const consumeTotalCost = computed(() => Number(teamConsumePager.extend?.total_cost) || 0);
    /** 算力流转:划出/入账合计 */
    const transferTotalOut = computed(() => Number(teamConsumePager.extend?.total_out) || 0);
    const transferTotalIn = computed(() => Number(teamConsumePager.extend?.total_in) || 0);
    /** 业务类型下拉选项(随 tab 变化,由后端下发) */
    const consumeBizOptions = computed<{ key: string; label: string }[]>(
        () => teamConsumePager.extend?.biz_options || []
    );
    const consumeSumLabel = computed(() => {
        if (consumeDateRange.value) return "所选时间团队消耗（算力）";
        const hit = CONSUME_RANGE_FILTERS.find((f) => f.key === consumeRange.value);
        return hit?.sumLabel || "团队消耗（算力）";
    });

    // 消耗详情弹窗(仅基础信息，不展示产出)
    const showOutput = ref(false);
    const outputRow = ref<any>(null);
    const onViewOutput = (row: any) => {
        outputRow.value = row;
        showOutput.value = true;
    };

    return {
        showConsumption,
        consumeMemberName,
        consumptionPager,
        getConsumptionLists,
        resetConsumption,
        onConsumption,
        consumeTab,
        setConsumeTab,
        teamConsumeUser,
        consumeKeyword,
        consumeRange,
        consumeDateRange,
        consumeBiz,
        consumeBizOptions,
        teamConsumePager,
        getTeamConsumeLists,
        resetTeamConsume,
        resetConsumeFilters,
        onTeamConsumeFilter,
        setConsumeRange,
        consumeTotalCost,
        transferTotalOut,
        transferTotalIn,
        consumeSumLabel,
        showOutput,
        outputRow,
        onViewOutput,
    };
}
