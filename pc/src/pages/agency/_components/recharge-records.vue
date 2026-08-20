<template>
    <popup
        ref="popupRef"
        width="820px"
        top="12vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Tickets" :size="20" />
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">
                            充值流水详情
                        </span>
                        <span class="text-[11px] text-slate-400 font-bold mt-1 truncate">
                            {{ targetUser.nickname || "--" }}
                            <span v-if="targetUser.mobile" class="font-mono">· {{ targetUser.mobile }}</span>
                        </span>
                    </div>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="px-6 pt-5">
                <div class="flex items-center gap-4 px-5 py-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">累计充值</span>
                        <span class="text-xl font-[1000] text-slate-900 mt-1">￥{{ summary.amount }}</span>
                    </div>
                    <div class="w-px h-8 bg-slate-200"></div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">充值笔数</span>
                        <span class="text-xl font-[1000] text-slate-900 mt-1">{{ summary.count }}</span>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5">
                <ElTable :data="pager.lists" max-height="420" class="custom-table" v-loading="pager.loading">
                    <ElTableColumn label="订单号" min-width="200">
                        <template #default="{ row }">
                            <span class="font-mono text-xs font-bold text-slate-600 select-all">{{ row.sn }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="充值套餐" min-width="160">
                        <template #default="{ row }">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700">{{ row.package_name || "--" }}</span>
                                <span class="text-[10px] text-primary font-black mt-0.5">
                                    {{ row.package_tokens || 0 }} 点算力
                                </span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="充值金额" min-width="110" align="center">
                        <template #default="{ row }">
                            <span class="font-[1000] text-slate-900">￥{{ row.order_amount }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="支付方式" min-width="110" align="center">
                        <template #default="{ row }">
                            <span class="text-xs font-bold text-slate-500">{{ row.pay_way_desc || "--" }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="支付时间" min-width="150" align="center">
                        <template #default="{ row }">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-600">
                                    {{ row.pay_time?.split(" ")[0] }}
                                </span>
                                <span class="text-[10px] text-slate-300">{{ row.pay_time?.split(" ")[1] }}</span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <div class="py-14 flex flex-col items-center">
                            <ElEmpty :image-size="90" description="该用户暂无充值记录" />
                        </div>
                    </template>
                </ElTable>

                <div class="flex justify-end mt-4">
                    <pagination v-model="pager" layout="prev, pager, next" @change="getLists" />
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getAgentSubRechargeList, getAgentSubSummary } from "@/api/user";

const emit = defineEmits<{
    (e: "close"): void;
}>();

const popupRef = ref();

const targetUser = reactive({
    user_id: 0,
    nickname: "",
    mobile: "",
});

const summary = reactive({
    amount: 0,
    count: 0,
});

const queryParams = reactive({
    user_id: 0,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getAgentSubRechargeList,
    params: queryParams,
});

const fetchSummary = async () => {
    try {
        const res: any = await getAgentSubSummary({ user_id: queryParams.user_id });
        summary.amount = res?.self_recharge_amount ?? 0;
        summary.count = res?.self_recharge_count ?? 0;
    } catch (error) {
        summary.amount = 0;
        summary.count = 0;
    }
};

const open = (row: any) => {
    targetUser.user_id = row?.user_id ?? 0;
    targetUser.nickname = row?.nickname ?? "";
    targetUser.mobile = row?.mobile ?? "";
    queryParams.user_id = targetUser.user_id;
    summary.amount = row?.recharge_amount ?? 0;
    summary.count = row?.recharge_count ?? 0;

    popupRef.value?.open();
    resetPage();
    fetchSummary();
};

const close = () => {
    popupRef.value?.close();
    emit("close");
};

defineExpose({ open, close });
</script>
